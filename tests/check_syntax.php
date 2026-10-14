<?php

declare(strict_types=1);

$dir = new RecursiveDirectoryIterator(dirname(__DIR__));
$iterator = new RecursiveIteratorIterator($dir);

$errors = 0;
$checked = 0;

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getPathname();
        $output = [];
        $returnVar = 0;
        exec('php -l ' . escapeshellarg($path) . ' 2>&1', $output, $returnVar);
        
        $checked++;
        if ($returnVar !== 0) {
            echo "ERRO DE SINTAXE EM: {$path}\n";
            echo implode("\n", $output) . "\n\n";
            $errors++;
        }
    }
}

echo "VERIFICAÇÃO CONCLUÍDA:\n";
echo "Total de arquivos PHP analisados: {$checked}\n";
echo "Erros encontrados: {$errors}\n";

if ($errors > 0) {
    exit(1);
}
