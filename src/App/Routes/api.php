<?php

use App\Controllers\AuthController;
use App\Middleware\JwtMiddleware;
use App\Middleware\RoleMiddleware;
use Slim\App;

return function (App $app) {
    // Login -> untuk semua (admin, guru, siswa)
    $app->post('/api/auth/login', [AuthController::class, 'login']);

    // Admin -> membuat users/akun (admin, guru, siswa) -> dibarengi dengan input personal data
    $app->group('/api/admin', function($group) {
        $group->post('/create-admin', [AuthController::class, 'createAdmin']);
        $group->post('/create-guru', [AuthController::class, 'createGuru']);
        $group->post('/create-siswa', [AuthController::class, 'createSiswa']);
    })->add(new RoleMiddleware(['admin']))->add(new JwtMiddleware());

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
