<?php
declare(strict_types = 1);

require('../vendor/autoload.php');

// .env
$dotenv = new \Symfony\Component\Dotenv\Dotenv();
$dotenv->load(__DIR__ . '/../.env');

// Database
/** @var PDO */
$GLOBALS['db'] = new \PDO(
    'mysql:host=' . $_ENV['ACTIVECOLLAB_DB_HOSTNAME'] . ';dbname=' . $_ENV['ACTIVECOLLAB_DB_DATABASE'],
    $_ENV['ACTIVECOLLAB_DB_USERNAME'],
    $_ENV['ACTIVECOLLAB_DB_PASSWORD']
);

// Authentication
if (!isset($_SERVER['HTTP_X_ANGIE_AUTHAPITOKEN']) || empty($_SERVER['HTTP_X_ANGIE_AUTHAPITOKEN'])) {
    http_response_code(400);
    echo json_encode([ 'error' => 'HTTP_X_ANGIE_AUTHAPITOKEN missing' ]);
    exit;
}

$authApiToken = filter_var($_SERVER['HTTP_X_ANGIE_AUTHAPITOKEN'], FILTER_SANITIZE_STRING);
/** @var \PDOStatement $statement */
$statement = $GLOBALS['db']->prepare('SELECT COUNT(*) AS total FROM api_subscriptions WHERE CONCAT(user_id, "-", token_id) = :token');
$statement->bindParam('token', $authApiToken);
$statement->execute();
$apiTokenResult = $statement->fetch();
if (intval($apiTokenResult['total']) !== 1) {
    http_response_code(403);
    echo json_encode([ 'error' => 'HTTP_X_ANGIE_AUTHAPITOKEN invalid' ]);
    exit;
}

// Dispatch
$dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) {
    $r->addRoute( 'GET', '/tasks/{id:\d+}[/]', [ 'controller' => \Phorax\ActiveCollabApi\Controller\TasksController::class, 'action' => 'get' ]);
});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        die('Not found');
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        die('Not allowed');
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        $controllerName = $routeInfo[1]['controller'];
        $actionName = $routeInfo[1]['action'] . 'Action';

        if (class_exists($controllerName)) {
            $class = new $controllerName();
            if (method_exists($class, $actionName)) {
                echo json_encode($class->{$actionName}($routeInfo[2]));
                return;
            } else {
                http_response_code(400);
                exit;
            }
        } else {
            http_response_code(400);
            exit;
        }
        break;
}