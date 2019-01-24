<?php
declare(strict_types=1);

require('../vendor/autoload.php');

class Application
{

    public function __construct()
    {
        $dotenv = new \Symfony\Component\Dotenv\Dotenv();
        $dotenv->load(__DIR__ . '/../.env');

        $GLOBALS['db'] = new \PDO(
            'mysql:host=' . $_ENV['ACTIVECOLLAB_DB_HOSTNAME'] . ';dbname=' . $_ENV['ACTIVECOLLAB_DB_DATABASE'],
            $_ENV['ACTIVECOLLAB_DB_USERNAME'],
            $_ENV['ACTIVECOLLAB_DB_PASSWORD']
        );

        header('Content-Type: application/json');;
    }

    /**
     * @param $apiKey
     * @return bool
     */
    protected function authenticate($apiKey): bool
    {
            $query = 'SELECT users.id AS id, users.type AS type
            FROM api_subscriptions
            INNER JOIN users
            ON api_subscriptions.user_id = users.id
            WHERE CONCAT(user_id, "-", token_id) = :apiKey
            AND users.archived_on IS NULL
            AND users.is_trashed = 0
            AND users.trashed_on IS NULL
            AND users.is_archived = 0';

        /** @var \PDOStatement $statement */
        $statement = $GLOBALS['db']->prepare($query);
        $statement->bindParam(':apiKey', $apiKey);
        if ($statement->execute() === false) {
            return false;
        }
        $GLOBALS['user'] = $statement->fetch(\PDO::FETCH_ASSOC);
        return true;
    }

    public function handle(): string
    {
        if (!isset($_SERVER['HTTP_X_ANGIE_AUTHAPITOKEN']) || empty($_SERVER['HTTP_X_ANGIE_AUTHAPITOKEN'])) {
            http_response_code(400);
            return json_encode(['error' => 'X-Angie-AuthApiToken missing.']);
        }

        $authApiToken = filter_var($_SERVER['HTTP_X_ANGIE_AUTHAPITOKEN'], FILTER_SANITIZE_STRING);
        if (!$this->authenticate($authApiToken)) {
            http_response_code(403);
            return json_encode(['error' => 'X-Angie-AuthApiToken invalid.']);
        }

        // Dispatch
        $dispatcher = \FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $r) {
            $r->addRoute('GET', '/tasks/{id:\d+}[/]',
                ['controller' => \Phorax\ActiveCollabApi\Controller\TasksController::class, 'action' => 'get']);
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
                {
                    http_response_code(404);
                    break;
                }
            case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                {
                    http_response_code(403);
                    break;
                }
            case FastRoute\Dispatcher::FOUND:
                {
                    $controllerName = $routeInfo[1]['controller'];
                    $actionName = $routeInfo[1]['action'] . 'Action';

                    if (class_exists($controllerName)) {
                        $class = new $controllerName();
                        if (method_exists($class, $actionName)) {
                            return $class->{$actionName}($routeInfo[2]);
                        } else {
                            http_response_code(404);
                            return json_encode(['error' => 'Not found.']);
                        }
                    } else {
                        http_response_code(400);
                        return json_encode(['error' => 'Not found.']);
                    }
                    break;
                }
        }
        return '';
    }
}

$application = new Application();
echo $application->handle();
