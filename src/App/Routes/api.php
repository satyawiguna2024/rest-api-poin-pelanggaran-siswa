<?php

use App\Controllers\AuthController;
use App\Middleware\JwtMiddleware;
use App\Middleware\RoleMiddleware;
use Slim\App;

return function (App $app) {
    // Login - accessible untuk semua (admin, guru, siswa)
    $app->post('/api/auth/login', [AuthController::class, 'login']);

    // $app->post('/api/auth/register-admin', [AuthController::class, 'registerAdmin']);
    

    // Admin only - endpoint baru per role
    $app->post('/api/admin/create-admin', [AuthController::class, 'createAdmin'])
        ->add(new RoleMiddleware(['admin']))
        ->add(new JwtMiddleware());

    $app->post('/api/admin/create-guru', [AuthController::class, 'createGuru'])
        ->add(new RoleMiddleware(['admin']))
        ->add(new JwtMiddleware());

    $app->post('/api/admin/create-siswa', [AuthController::class, 'createSiswa'])
        ->add(new RoleMiddleware(['admin']))
        ->add(new JwtMiddleware());

    // Protected route - untuk test JWT validation
    $app->get('/api/auth/response', function ($req, $res) {
        $user = $req->getAttribute('user');
        
        $res->getBody()->write(json_encode([
            'message' => 'Akses berhasil',
            'id' => $user->id,
            'role' => $user->role
        ]));
        return $res;
    })->add(new JwtMiddleware());
};
