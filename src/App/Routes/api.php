<?php

use App\Controllers\AuthController;
use App\Controllers\Role\AdminController;
use App\Controllers\Role\GuruController;
use App\Controllers\Role\SiswaController;
use App\Middleware\JwtMiddleware;
use App\Middleware\RoleMiddleware;
use Slim\App;

return function (App $app) {
    // Login -> untuk semua (admin, guru, siswa)
    $app->post('/api/auth/login', [AuthController::class, 'login']);

    // Admin -> membuat users/akun (admin, guru, siswa) -> dibarengi dengan input personal data
    $app->group('/api', function ($group) {
        // group create users - only admin
        $group->group('/auth', function ($auth) {
            $auth->post('/register-admin', [AuthController::class, 'createAdmin']);
            $auth->post('/register-guru', [AuthController::class, 'createGuru']);
            $auth->post('/register-siswa', [AuthController::class, 'createSiswa']);
        });

        // crud group admin
        $group->group('/admin', function ($admin) {
            $admin->get('', [AdminController::class, 'index']);
            $admin->get('/{id}', [AdminController::class, 'show']);
            $admin->put('/{id}', [AdminController::class, 'update']);
            $admin->delete('/{id}', [AdminController::class, 'destroy']);
        });

        // crud group guru
        $group->group('/guru', function ($guru) {
            $guru->get('', [GuruController::class, 'index']);
            $guru->get('/{id}', [GuruController::class, 'show']);
            $guru->put('/{id}', [GuruController::class, 'update']);
            $guru->delete('/{id}', [GuruController::class, 'destroy']);
        });

        // crud group siswa
        $group->group('/siswa', function ($siswa) {
            $siswa->get('', [SiswaController::class, 'index']);
            $siswa->get('/{id}', [SiswaController::class, 'show']);
            $siswa->put('/{id}', [SiswaController::class, 'update']);
            $siswa->delete('/{id}', [SiswaController::class, 'destroy']);
        });
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
