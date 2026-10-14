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

    public function numeric(string $field, string $label): self
    {
        $val = $this->data[$field] ?? null;
        if ($val !== null && $val !== '' && !is_numeric(str_replace(',', '.', (string)$val))) {
            $this->errors[$field] = "O campo {$label} deve ser um valor numérico válido.";
        }
        return $this;
    }

    public function min(string $field, float $minVal, string $label): self
    {
        $val = $this->data[$field] ?? null;
        if ($val !== null && $val !== '') {
            $num = (float)str_replace(',', '.', (string)$val);
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

        $clean = preg_replace('/\D/', '', $val);
        if (strlen($clean) !== 11 || preg_match('/^(\d)\1{10}$/', $clean)) {
            $this->errors[$field] = "O campo {$label} possui um CPF inválido.";
            return $this;
        }

        // Validação dos dígitos verificadores do CPF
        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += ((int)$clean[$c]) * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if (((int)$clean[$c]) !== $d) {
                $this->errors[$field] = "O campo {$label} possui um CPF inválido.";
                return $this;
            }
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
