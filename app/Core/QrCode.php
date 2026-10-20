<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Gerador matricial e vetorial de QR Code em PHP puro (sem dependências de extensões compiladas).
 * Implementa codificação Byte (8-bit) com correção de erro Reed-Solomon (Nível L/M)
 * e produz matrizes de módulos, código SVG e vetores para inclusão em relatórios e PDFs.
 */
class QrCode
{
    private string $data;
    private array $matrix = [];
    private int $size = 0;
    private int $version = 1;

    // GF(256) Log e Antilog tables para Reed-Solomon (polinômio 0x11D = 285)
    private static array $exp = [];
    private static array $log = [];
    private static bool $tablesInitialized = false;

    // Capacidade de dados em modo Byte para Nível L (Versões 1 a 6)
    private static array $versionCapacities = [
        1 => 17,
        2 => 32,
        3 => 53,
        4 => 78,
        5 => 106,
        6 => 134,
    ];

    // Total de palavras-código e palavras de ECC por versão para Nível L
    private static array $versionDetails = [
        1 => ['total' => 26, 'ecc' => 7, 'blocks' => 1],
        2 => ['total' => 44, 'ecc' => 10, 'blocks' => 1],
        3 => ['total' => 70, 'ecc' => 15, 'blocks' => 1],
        4 => ['total' => 100, 'ecc' => 20, 'blocks' => 1],
        5 => ['total' => 134, 'ecc' => 26, 'blocks' => 1],
        6 => ['total' => 172, 'ecc' => 36, 'blocks' => 2],
    ];

    public function __construct(string $data)
    {
        $this->data = $data;
        self::initTables();
        $this->generate();
    }

    public static function create(string $data): self
    {
        return new self($data);
    }

    public static function svg(string $data, int $pixelSize = 160): string
    {
        return (new self($data))->toSvg($pixelSize);
    }

    private static function initTables(): void
    {
        if (self::$tablesInitialized) {
            return;
        }

        self::$exp = array_fill(0, 512, 0);
        self::$log = array_fill(0, 256, 0);

        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$exp[$i] = $x;
            self::$exp[$i + 255] = $x;
            self::$log[$x] = $i;
            $x <<= 1;
            if ($x >= 256) {
                $x ^= 0x11D;
            }
        }
        self::$tablesInitialized = true;
    }

    private function generate(): void
    {
        $len = strlen($this->data);
        $this->version = 1;
        foreach (self::$versionCapacities as $v => $cap) {
            if ($len <= $cap) {
                $this->version = $v;
                break;
            }
            $this->version = $v;
        }

        $this->size = 17 + 4 * $this->version;
        $this->matrix = array_fill(0, $this->size, array_fill(0, $this->size, null));

        // 1. Padrões fixos de localização (Finder patterns nos 3 cantos)
        $this->addFinderPattern(0, 0);
        $this->addFinderPattern($this->size - 7, 0);
        $this->addFinderPattern(0, $this->size - 7);

        // Separadores em torno dos finders
        $this->addSeparators();

        // 2. Padrões de alinhamento (para Versão >= 2)
        if ($this->version >= 2) {
            $alignPos = [
                2 => [6, 18],
                3 => [6, 22],
                4 => [6, 26],
                5 => [6, 30],
                6 => [6, 34],
            ];
            $pos = $alignPos[$this->version] ?? [6, $this->size - 7];
            foreach ($pos as $r) {
                foreach ($pos as $c) {
                    if ($this->matrix[$r][$c] === null) {
                        $this->addAlignmentPattern($r, $c);
                    }
                }
            }
        }

        // 3. Linhas de temporização (Timing patterns)
        for ($i = 8; $i < $this->size - 8; $i++) {
            if ($this->matrix[6][$i] === null) {
                $this->matrix[6][$i] = ($i % 2 === 0);
            }
            if ($this->matrix[$i][6] === null) {
                $this->matrix[$i][6] = ($i % 2 === 0);
            }
        }

        // 4. Módulo escuro permanente
        $this->matrix[4 * $this->version + 9][8] = true;

        // 5. Reserva de formato de informação (em torno dos finders)
        $this->reserveFormatInfo();

        // 6. Codificação dos dados e ECC
        $bitStream = $this->buildBitStream();
        $this->placeDataBits($bitStream);

        // 7. Aplicação da Máscara (Padrão 0: (row + col) % 2 == 0) e Formato
        $this->applyMaskAndFormat(0);
    }

    private function addFinderPattern(int $row, int $col): void
    {
        for ($r = 0; $r < 7; $r++) {
            for ($c = 0; $c < 7; $c++) {
                if ($r === 0 || $r === 6 || $c === 0 || $c === 6 ||
                    ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4)) {
                    $this->matrix[$row + $r][$col + $c] = true;
                } else {
                    $this->matrix[$row + $r][$col + $c] = false;
                }
            }
        }
    }

    private function addSeparators(): void
    {
        for ($i = 0; $i < 8; $i++) {
            // Canto superior esquerdo
            if ($i < $this->size) {
                if (isset($this->matrix[7][$i]) && $this->matrix[7][$i] === null) $this->matrix[7][$i] = false;
                if (isset($this->matrix[$i][7]) && $this->matrix[$i][7] === null) $this->matrix[$i][7] = false;
            }
            // Canto superior direito
            $c = $this->size - 8 + $i;
            if ($c >= 0 && $c < $this->size) {
                if (isset($this->matrix[7][$c]) && $this->matrix[7][$c] === null) $this->matrix[7][$c] = false;
            }
            if ($i < 8 && isset($this->matrix[$i][$this->size - 8]) && $this->matrix[$i][$this->size - 8] === null) {
                $this->matrix[$i][$this->size - 8] = false;
            }
            // Canto inferior esquerdo
            $r = $this->size - 8 + $i;
            if ($r >= 0 && $r < $this->size) {
                if (isset($this->matrix[$r][7]) && $this->matrix[$r][7] === null) $this->matrix[$r][7] = false;
            }
            if ($i < 8 && isset($this->matrix[$this->size - 8][$i]) && $this->matrix[$this->size - 8][$i] === null) {
                $this->matrix[$this->size - 8][$i] = false;
            }
        }
    }

    private function addAlignmentPattern(int $rowCenter, int $colCenter): void
    {
        for ($r = -2; $r <= 2; $r++) {
            for ($c = -2; $c <= 2; $c++) {
                $isBorder = (abs($r) === 2 || abs($c) === 2);
                $isCenter = ($r === 0 && $c === 0);
                $this->matrix[$rowCenter + $r][$colCenter + $c] = ($isBorder || $isCenter);
            }
        }
    }

    private function reserveFormatInfo(): void
    {
        for ($i = 0; $i < 9; $i++) {
            if ($this->matrix[8][$i] === null) $this->matrix[8][$i] = false;
            if ($this->matrix[$i][8] === null) $this->matrix[$i][8] = false;
        }
        for ($i = 0; $i < 8; $i++) {
            $col = $this->size - 8 + $i;
            if ($this->matrix[8][$col] === null) $this->matrix[8][$col] = false;
            $row = $this->size - 8 + $i;
            if ($this->matrix[$row][8] === null) $this->matrix[$row][8] = false;
        }
    }

    private function buildBitStream(): array
    {
        $details = self::$versionDetails[$this->version];
        $totalCodewords = $details['total'];
        $eccCodewords = $details['ecc'];
        $dataCodewordsCount = $totalCodewords - $eccCodewords;

        // Modo 8-bit Byte: 0100
        $bits = [false, true, false, false];

        // Indicador de comprimento de caractere (8 bits para v1-v9)
        $len = strlen($this->data);
        for ($i = 7; $i >= 0; $i--) {
            $bits[] = (($len >> $i) & 1) === 1;
        }

        // Dados brutos
        for ($idx = 0; $idx < $len; $idx++) {
            $byte = ord($this->data[$idx]);
            for ($i = 7; $i >= 0; $i--) {
                $bits[] = (($byte >> $i) & 1) === 1;
            }
        }

        // Terminador (até 4 zeros)
        $maxDataBits = $dataCodewordsCount * 8;
        $terminatorLen = min(4, $maxDataBits - count($bits));
        for ($i = 0; $i < $terminatorLen; $i++) {
            $bits[] = false;
        }

        // Alinhamento em byte
        while (count($bits) % 8 !== 0) {
            $bits[] = false;
        }

        // Pad bytes (0xEC e 0x11 alternados)
        $padBytes = [0xEC, 0x11];
        $padIdx = 0;
        while (count($bits) < $maxDataBits) {
            $pad = $padBytes[$padIdx % 2];
            for ($i = 7; $i >= 0; $i--) {
                $bits[] = (($pad >> $i) & 1) === 1;
            }
            $padIdx++;
        }

        // Converte bits para bytes de dados
        $dataBytes = [];
        for ($i = 0; $i < count($bits); $i += 8) {
            $byteVal = 0;
            for ($b = 0; $b < 8; $b++) {
                if ($bits[$i + $b]) {
                    $byteVal |= (1 << (7 - $b));
                }
            }
            $dataBytes[] = $byteVal;
        }

        // Cálculo de ECC Reed-Solomon
        $eccBytes = $this->calculateReedSolomon($dataBytes, $eccCodewords);

        // Intercalação e conversão final em bits
        $allBytes = array_merge($dataBytes, $eccBytes);
        $finalBits = [];
        foreach ($allBytes as $byte) {
            for ($i = 7; $i >= 0; $i--) {
                $finalBits[] = (($byte >> $i) & 1) === 1;
            }
        }

        // Adiciona remainder bits se necessário
        $remainders = [1 => 0, 2 => 7, 3 => 7, 4 => 7, 5 => 7, 6 => 7];
        $rem = $remainders[$this->version] ?? 0;
        for ($i = 0; $i < $rem; $i++) {
            $finalBits[] = false;
        }

        return $finalBits;
    }

    private function calculateReedSolomon(array $data, int $eccCount): array
    {
        // Polinômio gerador para $eccCount
        $gen = [1];
        for ($i = 0; $i < $eccCount; $i++) {
            $next = [1, self::$exp[$i]];
            $temp = array_fill(0, count($gen) + 1, 0);
            for ($j = 0; $j < count($gen); $j++) {
                for ($k = 0; $k < count($next); $k++) {
                    $term = ($gen[$j] === 0 || $next[$k] === 0) ? 0 :
                        self::$exp[(self::$log[$gen[$j]] + self::$log[$next[$k]]) % 255];
                    $temp[$j + $k] ^= $term;
                }
            }
            $gen = $temp;
        }

        // Divisão polinomial
        $res = array_merge($data, array_fill(0, $eccCount, 0));
        for ($i = 0; $i < count($data); $i++) {
            $coef = $res[$i];
            if ($coef !== 0) {
                $logCoef = self::$log[$coef];
                for ($j = 0; $j < count($gen); $j++) {
                    if ($gen[$j] !== 0) {
                        $res[$i + $j] ^= self::$exp[($selfLog = self::$log[$gen[$j]] + $logCoef) % 255];
                    }
                }
            }
        }

        return array_slice($res, count($data));
    }

    private function placeDataBits(array $bits): void
    {
        $bitIdx = 0;
        $numBits = count($bits);
        $row = $this->size - 1;
        $dir = -1; // sobe (-1) ou desce (+1)

        for ($col = $this->size - 1; $col > 0; $col -= 2) {
            if ($col === 6) {
                // Pula coluna de temporização vertical
                $col--;
            }

            while ($row >= 0 && $row < $this->size) {
                for ($c = 0; $c < 2; $c++) {
                    $curCol = $col - $c;
                    if ($this->matrix[$row][$curCol] === null) {
                        $val = ($bitIdx < $numBits) ? $bits[$bitIdx] : false;
                        $this->matrix[$row][$curCol] = $val;
                        $bitIdx++;
                    }
                }
                $row += $dir;
            }

            $dir = -$dir;
            $row += $dir;
        }
    }

    private function applyMaskAndFormat(int $maskPattern): void
    {
        // Aplica a máscara nos módulos de dados (não toca nos fixos)
        for ($r = 0; $r < $this->size; $r++) {
            for ($c = 0; $c < $this->size; $c++) {
                if ($this->isDataModule($r, $c)) {
                    $invert = false;
                    switch ($maskPattern) {
                        case 0:
                            $invert = (($r + $c) % 2 === 0);
                            break;
                        case 1:
                            $invert = ($r % 2 === 0);
                            break;
                        case 2:
                            $invert = ($c % 3 === 0);
                            break;
                        default:
                            $invert = (($r + $c) % 2 === 0);
                    }
                    if ($invert) {
                        $this->matrix[$r][$c] = !$this->matrix[$r][$c];
                    }
                }
            }
        }

        // Bits de informação de formato para Nível L (01) e Máscara 0 (000) -> 15 bits com BCH
        // L + Mask 0: 01000 -> BCH 15,5 = 111011111000100 ^ 101010000010010 = 010001111010110
        $formatBits = [
            false, true, false, false, false,
            true, true, true, true, false,
            true, false, true, true, false
        ];

        // Grava formato nas duas posições padrão
        // Ao redor do Finder superior esquerdo
        $idx = 0;
        for ($col = 0; $col <= 8; $col++) {
            if ($col === 6) continue;
            $this->matrix[8][$col] = $formatBits[$idx++];
        }
        for ($row = 7; $row >= 0; $row--) {
            if ($row === 6) continue;
            $this->matrix[$row][8] = $formatBits[$idx++];
        }

        // Ao redor dos Finders inferior esquerdo e superior direito
        $idx2 = 0;
        for ($row = $this->size - 1; $row >= $this->size - 7; $row--) {
            $this->matrix[$row][8] = $formatBits[$idx2++];
        }
        $this->matrix[8][$this->size - 8] = $formatBits[$idx2++];
        for ($col = $this->size - 7; $col < $this->size; $col++) {
            $this->matrix[8][$col] = $formatBits[$idx2++];
        }
    }

    private function isDataModule(int $r, int $c): bool
    {
        // Finders e separadores
        if ($r < 9 && $c < 9) return false;
        if ($r < 9 && $c >= $this->size - 8) return false;
        if ($r >= $this->size - 8 && $c < 9) return false;

        // Timing
        if ($r === 6 || $c === 6) return false;

        // Alinhamento
        if ($this->version >= 2) {
            $alignPos = [
                2 => [18], 3 => [22], 4 => [26], 5 => [30], 6 => [34]
            ];
            $pos = $alignPos[$this->version] ?? [];
            foreach ($pos as $ar) {
                foreach ($pos as $ac) {
                    if (abs($r - $ar) <= 2 && abs($c - $ac) <= 2) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public function getMatrix(): array
    {
        return $this->matrix;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function toSvg(int $pixelSize = 160, string $darkColor = '#0b1f33', string $lightColor = '#ffffff'): string
    {
        $quietZone = 4;
        $totalModules = $this->size + ($quietZone * 2);
        $moduleSize = $pixelSize / $totalModules;

        $svg = [];
        $svg[] = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="%d" height="%d">',
            $pixelSize, $pixelSize, $pixelSize, $pixelSize
        );
        $svg[] = sprintf('<rect width="100%%" height="100%%" fill="%s"/>', htmlspecialchars($lightColor, ENT_QUOTES));

        $pathData = [];
        for ($r = 0; $r < $this->size; $r++) {
            for ($c = 0; $c < $this->size; $c++) {
                if ($this->matrix[$r][$c]) {
                    $x = ($c + $quietZone) * $moduleSize;
                    $y = ($r + $quietZone) * $moduleSize;
                    $pathData[] = sprintf('M%.2f,%.2fh%.2fv%.2fh-%.2fz', $x, $y, $moduleSize, $moduleSize, $moduleSize);
                }
            }
        }

        $svg[] = sprintf('<path d="%s" fill="%s"/>', implode(' ', $pathData), htmlspecialchars($darkColor, ENT_QUOTES));
        $svg[] = '</svg>';

        return implode('', $svg);
    }

    public function toDataUri(int $pixelSize = 160): string
    {
        $svg = $this->toSvg($pixelSize);
        return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
    }
}
