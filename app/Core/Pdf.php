<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Gerador de Documentos em PDF 1.4 em PHP puro (sem dependência de extensões externas).
 * Gera streams binários PDF válidos com tipografia Helvetica, cabeçalho timbrado oficial,
 * tabelas institucionais, caixas de assinatura e renderização vetorial de QR Codes.
 */
class Pdf
{
    // Dimensões A4 em pontos (72 pontos por polegada): 210mm x 297mm
    public const PAGE_WIDTH = 595.28;
    public const PAGE_HEIGHT = 841.89;
    public const MARGIN_LEFT = 40.0;
    public const MARGIN_RIGHT = 40.0;
    public const MARGIN_TOP = 40.0;
    public const MARGIN_BOTTOM = 40.0;

    private array $pages = [];
    private int $currentPage = -1;
    private float $cursorY = 0.0;
    private int $pageNumber = 0;
    private string $documentTitle = 'Documento Institucional';

    public function __construct(string $title = 'Documento Institucional')
    {
        $this->documentTitle = $title;
        $this->addPage();
    }

    public static function create(string $title = 'Documento Institucional'): self
    {
        return new self($title);
    }

    public function addPage(): self
    {
        $this->currentPage++;
        $this->pageNumber++;
        $this->pages[$this->currentPage] = [
            'stream' => '',
            'pageNumber' => $this->pageNumber
        ];
        $this->cursorY = self::PAGE_HEIGHT - self::MARGIN_TOP;
        return $this;
    }

    public function checkPageBreak(float $requiredHeight): void
    {
        if (($this->cursorY - $requiredHeight) < self::MARGIN_BOTTOM + 30) {
            $this->drawFooter();
            $this->addPage();
            $this->drawMiniHeader();
        }
    }

    public function drawHeader(
        string $title,
        string $subtitle = 'Órgão de Gestão Social e Desenvolvimento Assistencial',
        string $protocol = ''
    ): self {
        $startY = $this->cursorY;
        $contentWidth = self::PAGE_WIDTH - (self::MARGIN_LEFT + self::MARGIN_RIGHT);

        // Faixa de destaque superior
        $this->drawRect(self::MARGIN_LEFT, $startY - 45, $contentWidth, 45, [11, 31, 51], true);

        // Título principal e subtítulo timbrado
        $this->drawText($title, self::MARGIN_LEFT + 15, $startY - 20, 14, true, [255, 255, 255]);
        $this->drawText($subtitle, self::MARGIN_LEFT + 15, $startY - 35, 9, false, [180, 210, 240]);

        if (!empty($protocol)) {
            $this->drawText('PROTOCOLO / REGISTRO: ' . $protocol, self::MARGIN_LEFT + $contentWidth - 200, $startY - 25, 8, true, [255, 255, 255]);
        }

        $this->cursorY = $startY - 60;
        return $this;
    }

    public function drawMiniHeader(): void
    {
        $startY = $this->cursorY;
        $contentWidth = self::PAGE_WIDTH - (self::MARGIN_LEFT + self::MARGIN_RIGHT);
        $this->drawText($this->documentTitle . ' (Continuação)', self::MARGIN_LEFT, $startY - 10, 9, true, [100, 116, 139]);
        $this->drawLine(self::MARGIN_LEFT, $startY - 15, self::MARGIN_LEFT + $contentWidth, $startY - 15, [226, 232, 240], 0.75);
        $this->cursorY = $startY - 30;
    }

    public function drawFooter(string $additionalInfo = ''): self
    {
        $contentWidth = self::PAGE_WIDTH - (self::MARGIN_LEFT + self::MARGIN_RIGHT);
        $y = self::MARGIN_BOTTOM;

        $this->drawLine(self::MARGIN_LEFT, $y + 15, self::MARGIN_LEFT + $contentWidth, $y + 15, [203, 213, 225], 0.5);
        $textLeft = 'Sistema de Gestão Social e Assistencial — Emitido em: ' . date('d/m/Y H:i:s');
        if (!empty($additionalInfo)) {
            $textLeft .= ' | ' . $additionalInfo;
        }
        $textRight = 'Página ' . $this->pageNumber;

        $this->drawText($textLeft, self::MARGIN_LEFT, $y + 4, 8, false, [100, 116, 139]);
        $this->drawText($textRight, self::PAGE_WIDTH - self::MARGIN_RIGHT - 40, $y + 4, 8, false, [100, 116, 139]);

        return $this;
    }

    public function addHeading(string $text, int $level = 1): self
    {
        $this->checkPageBreak(30);
        $contentWidth = self::PAGE_WIDTH - (self::MARGIN_LEFT + self::MARGIN_RIGHT);

        if ($level === 1) {
            $this->drawRect(self::MARGIN_LEFT, $this->cursorY - 20, $contentWidth, 22, [241, 245, 249], true);
            $this->drawRect(self::MARGIN_LEFT, $this->cursorY - 20, 4, 22, [15, 76, 129], true);
            $this->drawText($text, self::MARGIN_LEFT + 12, $this->cursorY - 14, 11, true, [15, 23, 42]);
            $this->cursorY -= 32;
        } elseif ($level === 2) {
            $this->drawText($text, self::MARGIN_LEFT, $this->cursorY - 10, 10, true, [15, 76, 129]);
            $this->drawLine(self::MARGIN_LEFT, $this->cursorY - 14, self::MARGIN_LEFT + $contentWidth, $this->cursorY - 14, [226, 232, 240], 0.5);
            $this->cursorY -= 24;
        } else {
            $this->drawText($text, self::MARGIN_LEFT, $this->cursorY - 10, 9, true, [51, 65, 85]);
            $this->cursorY -= 18;
        }

        return $this;
    }

    public function addParagraph(string $text, int $fontSize = 9, bool $bold = false, array $color = [15, 23, 42]): self
    {
        $contentWidth = self::PAGE_WIDTH - (self::MARGIN_LEFT + self::MARGIN_RIGHT);
        $lines = $this->wordWrap($text, $contentWidth, $fontSize);

        $lineHeight = $fontSize * 1.45;
        $this->checkPageBreak(count($lines) * $lineHeight + 10);

        foreach ($lines as $line) {
            $this->drawText($line, self::MARGIN_LEFT, $this->cursorY - $fontSize, $fontSize, $bold, $color);
            $this->cursorY -= $lineHeight;
        }
        $this->cursorY -= 6;

        return $this;
    }

    public function addKeyValueGrid(array $items, int $columns = 2): self
    {
        $contentWidth = self::PAGE_WIDTH - (self::MARGIN_LEFT + self::MARGIN_RIGHT);
        $colWidth = $contentWidth / $columns;
        $rowHeight = 26.0;

        $totalItems = count($items);
        $numRows = (int)ceil($totalItems / $columns);

        $this->checkPageBreak($numRows * $rowHeight + 10);

        $i = 0;
        foreach ($items as $label => $value) {
            $colIndex = $i % $columns;
            $rowIndex = (int)floor($i / $columns);

            $x = self::MARGIN_LEFT + ($colIndex * $colWidth);
            $y = $this->cursorY - ($rowIndex * $rowHeight);

            // Rótulo
            $this->drawText(mb_strtoupper((string)$label), $x, $y - 7, 7, true, [100, 116, 139]);
            // Valor
            $valStr = (string)($value ?? '—');
            if (mb_strlen($valStr) > 40) {
                $valStr = mb_substr($valStr, 0, 38) . '...';
            }
            $this->drawText($valStr, $x, $y - 19, 9, false, [15, 23, 42]);

            $i++;
        }

        $this->cursorY -= ($numRows * $rowHeight) + 8;
        return $this;
    }

    public function addTable(array $headers, array $rows, array $colWidthPercents = []): self
    {
        $contentWidth = self::PAGE_WIDTH - (self::MARGIN_LEFT + self::MARGIN_RIGHT);
        $numCols = count($headers);

        // Larguras proporcionais
        if (empty($colWidthPercents)) {
            $colWidths = array_fill(0, $numCols, $contentWidth / $numCols);
        } else {
            $colWidths = [];
            foreach ($colWidthPercents as $pct) {
                $colWidths[] = ($pct / 100.0) * $contentWidth;
            }
        }

        $rowHeight = 20.0;
        $totalHeight = (count($rows) + 1) * $rowHeight + 10;
        $this->checkPageBreak(min($totalHeight, 100));

        // Cabeçalho da tabela
        $this->drawRect(self::MARGIN_LEFT, $this->cursorY - $rowHeight, $contentWidth, $rowHeight, [241, 245, 249], true);
        $this->drawRect(self::MARGIN_LEFT, $this->cursorY - $rowHeight, $contentWidth, $rowHeight, [203, 213, 225], false, 0.5);

        $curX = self::MARGIN_LEFT;
        foreach ($headers as $idx => $headerText) {
            $w = $colWidths[$idx] ?? 60.0;
            $this->drawText(mb_strtoupper($headerText), $curX + 6, $this->cursorY - 14, 8, true, [71, 85, 105]);
            $curX += $w;
        }
        $this->cursorY -= $rowHeight;

        // Linhas de dados
        foreach ($rows as $rIdx => $row) {
            $this->checkPageBreak($rowHeight + 15);

            $isZebra = ($rIdx % 2 === 1);
            if ($isZebra) {
                $this->drawRect(self::MARGIN_LEFT, $this->cursorY - $rowHeight, $contentWidth, $rowHeight, [248, 250, 252], true);
            }
            $this->drawLine(self::MARGIN_LEFT, $this->cursorY - $rowHeight, self::MARGIN_LEFT + $contentWidth, $this->cursorY - $rowHeight, [226, 232, 240], 0.5);

            $curX = self::MARGIN_LEFT;
            foreach ($row as $cIdx => $cellText) {
                $w = $colWidths[$cIdx] ?? 60.0;
                $str = (string)$cellText;
                if (mb_strlen($str) > 35) {
                    $str = mb_substr($str, 0, 32) . '...';
                }
                $this->drawText($str, $curX + 6, $this->cursorY - 14, 8, false, [30, 41, 59]);
                $curX += $w;
            }
            $this->cursorY -= $rowHeight;
        }

        $this->cursorY -= 12;
        return $this;
    }

    public function addAlertBox(string $text, string $type = 'info'): self
    {
        $contentWidth = self::PAGE_WIDTH - (self::MARGIN_LEFT + self::MARGIN_RIGHT);
        $lines = $this->wordWrap($text, $contentWidth - 24, 8);
        $boxHeight = max(32.0, count($lines) * 12.0 + 16.0);

        $this->checkPageBreak($boxHeight + 10);

        $colors = [
            'info' => ['bg' => [238, 246, 255], 'border' => [147, 197, 253], 'bar' => [3, 105, 161]],
            'warning' => ['bg' => [254, 249, 195], 'border' => [253, 224, 71], 'bar' => [180, 83, 9]],
            'success' => ['bg' => [240, 253, 244], 'border' => [134, 239, 172], 'bar' => [21, 128, 61]],
        ];
        $c = $colors[$type] ?? $colors['info'];

        $this->drawRect(self::MARGIN_LEFT, $this->cursorY - $boxHeight, $contentWidth, $boxHeight, $c['bg'], true);
        $this->drawRect(self::MARGIN_LEFT, $this->cursorY - $boxHeight, $contentWidth, $boxHeight, $c['border'], false, 0.75);
        $this->drawRect(self::MARGIN_LEFT, $this->cursorY - $boxHeight, 4, $boxHeight, $c['bar'], true);

        $lineY = $this->cursorY - 14;
        foreach ($lines as $line) {
            $this->drawText($line, self::MARGIN_LEFT + 14, $lineY, 8, false, [30, 41, 59]);
            $lineY -= 12;
        }

        $this->cursorY -= $boxHeight + 10;
        return $this;
    }

    public function addSignatures(string $labelLeft, string $roleLeft, string $labelRight = '', string $roleRight = ''): self
    {
        $this->checkPageBreak(80);
        $contentWidth = self::PAGE_WIDTH - (self::MARGIN_LEFT + self::MARGIN_RIGHT);

        $yLine = $this->cursorY - 45;

        if (empty($labelRight)) {
            // Assinatura única centralizada
            $w = 260.0;
            $x = self::MARGIN_LEFT + ($contentWidth - $w) / 2;
            $this->drawLine($x, $yLine, $x + $w, $yLine, [100, 116, 139], 1.0);
            $this->drawText($labelLeft, $x + 10, $yLine - 14, 9, true, [15, 23, 42]);
            $this->drawText($roleLeft, $x + 10, $yLine - 26, 8, false, [100, 116, 139]);
        } else {
            // Duas assinaturas lado a lado
            $w = ($contentWidth - 40) / 2;
            $x1 = self::MARGIN_LEFT;
            $x2 = self::MARGIN_LEFT + $w + 40;

            $this->drawLine($x1, $yLine, $x1 + $w, $yLine, [100, 116, 139], 1.0);
            $this->drawText($labelLeft, $x1 + 10, $yLine - 14, 9, true, [15, 23, 42]);
            $this->drawText($roleLeft, $x1 + 10, $yLine - 26, 8, false, [100, 116, 139]);

            $this->drawLine($x2, $yLine, $x2 + $w, $yLine, [100, 116, 139], 1.0);
            $this->drawText($labelRight, $x2 + 10, $yLine - 14, 9, true, [15, 23, 42]);
            $this->drawText($roleRight, $x2 + 10, $yLine - 26, 8, false, [100, 116, 139]);
        }

        $this->cursorY = $yLine - 45;
        return $this;
    }

    public function addQrCodeValidation(QrCode $qrCode, string $validationCode, string $authUrl = ''): self
    {
        $this->checkPageBreak(85);
        $contentWidth = self::PAGE_WIDTH - (self::MARGIN_LEFT + self::MARGIN_RIGHT);
        $boxHeight = 75.0;

        $this->drawRect(self::MARGIN_LEFT, $this->cursorY - $boxHeight, $contentWidth, $boxHeight, [248, 250, 252], true);
        $this->drawRect(self::MARGIN_LEFT, $this->cursorY - $boxHeight, $contentWidth, $boxHeight, [226, 232, 240], false, 0.75);

        // Desenha os módulos do QR Code
        $matrix = $qrCode->getMatrix();
        $qrModules = $qrCode->getSize();
        $qrPixelSize = 58.0;
        $modSize = $qrPixelSize / $qrModules;
        $qrX = self::MARGIN_LEFT + 10;
        $qrY = $this->cursorY - $boxHeight + 8;

        for ($r = 0; $r < $qrModules; $r++) {
            for ($c = 0; $c < $qrModules; $c++) {
                if ($matrix[$r][$c]) {
                    $mx = $qrX + ($c * $modSize);
                    $my = $qrY + (($qrModules - 1 - $r) * $modSize);
                    $this->drawRect($mx, $my, $modSize, $modSize, [11, 31, 51], true);
                }
            }
        }

        // Textos ao lado do QR Code
        $tx = self::MARGIN_LEFT + 80;
        $this->drawText('VALIDAÇÃO DIGITAL DE AUTENTICIDADE', $tx, $this->cursorY - 20, 9, true, [15, 76, 129]);
        $this->drawText('Código de Verificação: ' . $validationCode, $tx, $this->cursorY - 34, 8, true, [15, 23, 42]);
        $this->drawText('Aponte a câmera do celular para conferir o prontuário e atestar a veracidade deste documento.', $tx, $this->cursorY - 48, 8, false, [100, 116, 139]);
        if (!empty($authUrl)) {
            $this->drawText('Endereço de consulta: ' . $authUrl, $tx, $this->cursorY - 60, 7, false, [3, 105, 161]);
        }

        $this->cursorY -= ($boxHeight + 12);
        return $this;
    }

    // Primitivas gráficas em sintaxe nativa PDF
    private function drawText(string $text, float $x, float $y, int $fontSize = 10, bool $bold = false, array $rgb = [0, 0, 0]): void
    {
        $cleanText = $this->sanitizePdfString($text);
        $fontName = $bold ? '/F2' : '/F1';
        $r = number_format($rgb[0] / 255.0, 3, '.', '');
        $g = number_format($rgb[1] / 255.0, 3, '.', '');
        $b = number_format($rgb[2] / 255.0, 3, '.', '');

        $stream = sprintf(
            "BT %s %d Tf %s %s %s rg 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n",
            $fontName, $fontSize, $r, $g, $b, $x, $y, $cleanText
        );
        $this->pages[$this->currentPage]['stream'] .= $stream;
    }

    private function drawRect(float $x, float $y, float $w, float $h, array $rgb, bool $fill = true, float $lineWidth = 1.0): void
    {
        $r = number_format($rgb[0] / 255.0, 3, '.', '');
        $g = number_format($rgb[1] / 255.0, 3, '.', '');
        $b = number_format($rgb[2] / 255.0, 3, '.', '');

        if ($fill) {
            $stream = sprintf(
                "q %s %s %s rg %.2f %.2f %.2f %.2f re f Q\n",
                $r, $g, $b, $x, $y, $w, $h
            );
        } else {
            $stream = sprintf(
                "q %.2f w %s %s %s RG %.2f %.2f %.2f %.2f re S Q\n",
                $lineWidth, $r, $g, $b, $x, $y, $w, $h
            );
        }
        $this->pages[$this->currentPage]['stream'] .= $stream;
    }

    private function drawLine(float $x1, float $y1, float $x2, float $y2, array $rgb = [0, 0, 0], float $lineWidth = 1.0): void
    {
        $r = number_format($rgb[0] / 255.0, 3, '.', '');
        $g = number_format($rgb[1] / 255.0, 3, '.', '');
        $b = number_format($rgb[2] / 255.0, 3, '.', '');

        $stream = sprintf(
            "q %.2f w %s %s %s RG %.2f %.2f m %.2f %.2f l S Q\n",
            $lineWidth, $r, $g, $b, $x1, $y1, $x2, $y2
        );
        $this->pages[$this->currentPage]['stream'] .= $stream;
    }

    private function sanitizePdfString(string $text): string
    {
        // Converte UTF-8 em ISO-8859-1 (Latin1) nativo do Type1 Helvetica do PDF
        $latin1 = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text) ?: $text;
        // Escapa parênteses e barras invertidas
        $latin1 = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $latin1);
        return $latin1;
    }

    private function wordWrap(string $text, float $maxWidth, int $fontSize): array
    {
        // Aproximação de largura média por caractere para Helvetica (0.52 * fontSize)
        $charWidth = $fontSize * 0.52;
        $maxCharsPerLine = (int)max(15, floor($maxWidth / $charWidth));

        $words = preg_split('/\s+/', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $w) {
            $testLine = $currentLine === '' ? $w : $currentLine . ' ' . $w;
            if (mb_strlen($testLine) <= $maxCharsPerLine) {
                $currentLine = $testLine;
            } else {
                if ($currentLine !== '') {
                    $lines[] = $currentLine;
                }
                $currentLine = $w;
            }
        }
        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return $lines;
    }

    /**
     * Compila e gera o binário do arquivo PDF 1.4 completo com tabela XREF e Trailer.
     */
    public function output(): string
    {
        // Garante rodapé na última página
        $this->drawFooter();

        $objects = [];
        $xref = [];

        // 1: Catalog
        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";

        // 2: Pages Collection
        $numPages = count($this->pages);
        $kids = [];
        // Fontes em 3 e 4, Páginas a partir de 5
        $pageObjIndex = 5;
        for ($p = 0; $p < $numPages; $p++) {
            $kids[] = ($pageObjIndex + ($p * 2)) . " 0 R";
        }
        $objects[2] = sprintf(
            "<< /Type /Pages /Kids [%s] /Count %d /MediaBox [0 0 %.2f %.2f] >>",
            implode(' ', $kids), $numPages, self::PAGE_WIDTH, self::PAGE_HEIGHT
        );

        // 3: Fonte Regular (Helvetica)
        $objects[3] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";

        // 4: Fonte Negrito (Helvetica-Bold)
        $objects[4] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";

        // Gera objetos de página e conteúdo
        for ($p = 0; $p < $numPages; $p++) {
            $pageId = $pageObjIndex + ($p * 2);
            $contentId = $pageId + 1;

            $contentStream = $this->pages[$p]['stream'];
            $streamLen = strlen($contentStream);

            // Objeto Page
            $objects[$pageId] = sprintf(
                "<< /Type /Page /Parent 2 0 R /Contents %d 0 R /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> >>",
                $contentId
            );

            // Objeto Content Stream
            $objects[$contentId] = sprintf(
                "<< /Length %d >>\nstream\n%sendstream",
                $streamLen, $contentStream
            );
        }

        // Montagem do buffer com cabeçalho
        $pdf = "%PDF-1.4\n%\xe2\xe3\xcf\xd3\n";
        $totalObjs = count($objects);

        for ($i = 1; $i <= $totalObjs; $i++) {
            $xref[$i] = strlen($pdf);
            $pdf .= sprintf("%d 0 obj\n%s\nendobj\n", $i, $objects[$i]);
        }

        // Tabela XREF
        $startXref = strlen($pdf);
        $pdf .= sprintf("xref\n0 %d\n", $totalObjs + 1);
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $totalObjs; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $xref[$i]);
        }

        // Trailer
        $pdf .= sprintf(
            "trailer\n<< /Size %d /Root 1 0 R >>\nstartxref\n%d\n%%%%EOF\n",
            $totalObjs + 1, $startXref
        );

        return $pdf;
    }

    public function download(string $filename = 'documento.pdf'): void
    {
        $bin = $this->output();
        if (!headers_sent()) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
            header('Content-Length: ' . strlen($bin));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
        }
        echo $bin;
        exit;
    }

    public function inline(string $filename = 'documento.pdf'): void
    {
        $bin = $this->output();
        if (!headers_sent()) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . rawurlencode($filename) . '"');
            header('Content-Length: ' . strlen($bin));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
        }
        echo $bin;
        exit;
    }
}
