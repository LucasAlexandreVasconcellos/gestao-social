<?php

declare(strict_types=1);

// Ponto de entrada único da aplicação (Front Controller)
define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/app/Core/Autoloader.php';
require_once ROOT_PATH . '/app/Core/polyfills.php';

use App\Core\Autoloader;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;

// Registro do Autoloader PSR-4 nativo
Autoloader::register();
Autoloader::addNamespace('App', ROOT_PATH . '/app');

// Garante que o diretório de armazenamento de sessão exista e seja gravável
$storageSessionPath = ROOT_PATH . '/storage/sessions';
if (!is_dir($storageSessionPath)) {
    @mkdir($storageSessionPath, 0777, true);
}
if (is_dir($storageSessionPath) && is_writable($storageSessionPath)) {
    session_save_path($storageSessionPath);
}

// Inicialização segura de sessão
Session::start();

// Configurações e Injeção de Dependências
require_once ROOT_PATH . '/config/app.php';

// Inicialização da camada de Views
View::init(ROOT_PATH . '/app/Views');

// Roteamento
$router = new Router();
require_once ROOT_PATH . '/routes/web.php';

// Execução da requisição
$request = new Request();
$router->dispatch($request);
