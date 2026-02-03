<?php

use App\Controllers\AuthController;
use App\Middleware\JwtMiddleware;
use Slim\App;

return function (App $app) {
    $app->post('/login', [AuthController::class, 'login']);
    $app->post('/register', [AuthController::class, 'register']);

    $app->get('/profile', function ($req, $res) {
        $res->getBody()->write(json_encode([
            'message' => 'Akses berhasil'
        ]));
        return $res;
    })->add(new JwtMiddleware());
};
