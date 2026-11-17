<?php

declare(strict_types=1);

namespace App\Validators;

final class FormValidator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $field, string $label): self
    {
        $val = trim((string)($this->data[$field] ?? ''));
        if ($val === '') {
            $this->errors[$field] = "O campo {$label} é obrigatório.";
        }
        return $this;
    }

    public function minLength(string $field, int $min, string $label): self
    {
        $val = trim((string)($this->data[$field] ?? ''));
        $len = function_exists('mb_strlen') ? mb_strlen($val, 'UTF-8') : strlen($val);
        if ($val !== '' && $len < $min) {
            $this->errors[$field] = "O campo {$label} deve conter no mínimo {$min} caracteres.";
        }
        return $this;
    }

    public static function normalizeMoneyInput(mixed $val): float
    {
        if ($val === null || $val === '') {
            return 0.0;
        }
        if (is_float($val) || is_int($val)) {
            return (float)$val;
        }
        $str = trim((string)$val);
        $str = preg_replace('/[^\d,\.\-]/', '', $str);
        if ($str === '' || $str === null) {
            return 0.0;
        }
        if (str_contains($str, ',')) {
            $str = str_replace('.', '', $str);
            $str = str_replace(',', '.', $str);
        }
        return is_numeric($str) ? (float)$str : 0.0;
    }

    public function numeric(string $field, string $label): self
    {
        $val = $this->data[$field] ?? null;
        if ($val !== null && $val !== '') {
            $str = trim((string)$val);
            if (str_contains($str, ',')) {
                $str = str_replace('.', '', $str);
                $str = str_replace(',', '.', $str);
            }
            if (!is_numeric($str)) {
                $this->errors[$field] = "O campo {$label} deve ser um valor numérico válido.";
            }
        }
        return $this;
    }

    public function min(string $field, float $minVal, string $label): self
    {
        $val = $this->data[$field] ?? null;
        if ($val !== null && $val !== '') {
            $num = self::normalizeMoneyInput($val);
            if ($num < $minVal) {
                $this->errors[$field] = "O campo {$label} deve ser maior ou igual a {$minVal}.";
            }
        }
        return $this;
    }

    public function date(string $field, string $label): self
    {
        $val = trim((string)($this->data[$field] ?? ''));
        if ($val !== '') {
            $norm = str_replace('/', '-', $val);
            $timestamp = strtotime($norm);
            if (!$timestamp) {
                $this->errors[$field] = "O campo {$label} deve conter uma data válida.";
            }
        }
        return $this;
    }

    public function cpf(string $field, string $label, bool $required = false): self
    {
        $val = trim((string)($this->data[$field] ?? ''));
        if ($val === '') {
            if ($required) {
                $this->errors[$field] = "O campo {$label} é obrigatório.";
            }
            return $this;
        }

        if (!self::isValidCpf($val)) {
            $this->errors[$field] = "O campo {$label} possui um CPF inválido.";
        }

        return $this;
    }

    public function cpfOrCnpj(string $field, string $label, bool $required = false): self
    {
        $val = trim((string)($this->data[$field] ?? ''));
        if ($val === '') {
            if ($required) {
                $this->errors[$field] = "O campo {$label} é obrigatório.";
            }
            return $this;
        }

        $clean = preg_replace('/\D/', '', $val);
        if (strlen($clean) === 11) {
            if (!self::isValidCpf($clean)) {
                $this->errors[$field] = "O campo {$label} possui um CPF inválido.";
            }
        } elseif (strlen($clean) === 14) {
            if (!self::isValidCnpj($clean)) {
                $this->errors[$field] = "O campo {$label} possui um CNPJ inválido.";
            }
        } else {
            $this->errors[$field] = "O campo {$label} deve conter um CPF (11 dígitos) ou CNPJ (14 dígitos) válido.";
        }

        return $this;
    }

    public static function isValidCpf(string $val): bool
    {
        $clean = preg_replace('/\D/', '', $val);
        if (strlen($clean) !== 11 || preg_match('/^(\d)\1{10}$/', $clean)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += ((int)$clean[$c]) * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if (((int)$clean[$c]) !== $d) {
                return false;
            }
        }

        return true;
    }

    public static function isValidCnpj(string $val): bool
    {
        $clean = preg_replace('/\D/', '', $val);
        if (strlen($clean) !== 14 || preg_match('/^(\d)\1{13}$/', $clean)) {
            return false;
        }

        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += ((int)$clean[$i]) * $weights1[$i];
        }
        $rem = $sum % 11;
        $d1 = $rem < 2 ? 0 : 11 - $rem;
        if ((int)$clean[12] !== $d1) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += ((int)$clean[$i]) * $weights2[$i];
        }
        $rem = $sum % 11;
        $d2 = $rem < 2 ? 0 : 11 - $rem;
        return (int)$clean[13] === $d2;
    }

    /**
     * Valida política institucional de senha forte:
     * mínimo 8 caracteres, 1 maiúscula, 1 minúscula, 1 número e 1 caractere especial.
     */
    public function strongPassword(string $field, string $label, bool $required = true): self
    {
        $val = (string)($this->data[$field] ?? '');
        if ($val === '') {
            if ($required) {
                $this->errors[$field] = "O campo {$label} é obrigatório.";
            }
            return $this;
        }

        $len = function_exists('mb_strlen') ? mb_strlen($val, 'UTF-8') : strlen($val);
        if ($len < 8) {
            $this->errors[$field] = "A {$label} deve ter no mínimo 8 caracteres.";
            return $this;
        }

        if (!preg_match('/[A-Z]/', $val) || !preg_match('/[a-z]/', $val) || !preg_match('/\d/', $val) || !preg_match('/[^A-Za-z0-9]/', $val)) {
            $this->errors[$field] = "A {$label} deve conter letra maiúscula, minúscula, número e símbolo especial.";
        }

        return $this;
    }

    public function cep(string $field, string $label): self
    {
        $val = trim((string)($this->data[$field] ?? ''));
        if ($val !== '') {
            $clean = preg_replace('/\D/', '', $val);
            if (strlen($clean) !== 8) {
                $this->errors[$field] = "O campo {$label} deve conter um CEP válido com 8 dígitos.";
            }
        }
        return $this;
    }

    public function email(string $field, string $label, bool $required = false): self
    {
        $val = trim((string)($this->data[$field] ?? ''));
        if ($val === '') {
            if ($required) {
                $this->errors[$field] = "O campo {$label} é obrigatório.";
            }
            return $this;
        }

        if (!filter_var($val, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "O campo {$label} deve conter um endereço de e-mail válido.";
        }

        return $this;
    }

    public function inList(string $field, array $allowed, string $label): self
    {
        $val = trim((string)($this->data[$field] ?? ''));
        if ($val !== '' && !in_array($val, $allowed, true)) {
            $this->errors[$field] = "O valor selecionado para {$label} não é válido.";
        }
        return $this;
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): ?string
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
}
