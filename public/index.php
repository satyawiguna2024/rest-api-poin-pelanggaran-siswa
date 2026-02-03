<?php

declare(strict_types=1);

use DI\Container;
use Slim\Factory\AppFactory;

use App\Config\Database;
use App\Repositories\UsersRepositories;

require dirname(__DIR__) . '/vendor/autoload.php';

// set up DI\Container
$container = new Container();

$container->set(PDO::class, fn() => Database::connect());
$container->set(UsersRepositories::class, fn($c) => new UsersRepositories($c->get(PDO::class)));

AppFactory::setContainer($container);

// App
$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

(require __DIR__ . '/../src/App/Routes/api.php')($app);

$app->run();