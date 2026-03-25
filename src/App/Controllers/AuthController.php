<?php

namespace App\Controllers;

use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use App\Repositories\UsersRepositories;

class AuthController
{
  private UsersRepositories $users;

  private string $jwtSecret = "JX4CaWk5Yu98tLwF+JqN59SH4K4l4FAlYp7q75cIz8g";

  public function __construct(UsersRepositories $users)
  {
    $this->users = $users;
  }

  public function login(Request $request, Response $response)
  {
    $data = $request->getParsedBody();

    // Validasi input
    if (empty($data['username']) || empty($data['password'])) {
      $response->getBody()->write(json_encode([
        'message' => 'Username dan password harus diisi'
      ]));
      return $response->withStatus(400);
    }

    $user = $this->users->findByUsername($data['username']);

    if (!$user || !password_verify($data['password'], $user['password'])) {
      $response->getBody()->write(json_encode([
        'message' => 'Username atau password salah'
      ]));
      return $response->withStatus(401);
    }

    $payload = [
      'id' => $user['id'],
      'role' => $user['role'],
      'exp' => time() + (60 * 60) // 1 jam
    ];

    $token = JWT::encode($payload, $this->jwtSecret, 'HS256');

    $response->getBody()->write(json_encode([
      'message' => 'Login berhasil',
      'token' => $token,
      'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'password' => $user['password'],
        'role' => $user['role']
      ]
    ]));

    return $response->withStatus(200);
  }

  public function registerAdmin(Request $request, Response $response)
  {
    $data = $request->getParsedBody();

    //Validasi input
    if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
      $response->getBody()->write(json_encode([
        'message' => 'Username, email, dan password harus diisi'
      ]));
      return $response->withStatus(400);
    }

    //Mengecek apakah username sudah terdaftar
    if ($this->users->findByUsername($data['username'])) {
      $response->getBody()->write(json_encode([
        'message' => 'Username sudah terdaftar'
      ]));
      return $response->withStatus(409);
    }

    //Validasi format email (basic validation)
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
      $response->getBody()->write(json_encode([
        'message' => 'Format email tidak valid'
      ]));
      return $response->withStatus(400);
    }

    //Validasi panjang password (minimal 6 karakter)
    if (strlen($data['password']) < 6) {
      $response->getBody()->write(json_encode([
        'message' => 'Password minimal 6 karakter'
      ]));
      return $response->withStatus(400);
    }

    //Hash password dengan bcrypt
    $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

    try {
      $this->users->create([
        'username' => trim($data['username']),
        'email' => trim($data['email']),
        'password' => $hashedPassword,
        'role' => 'admin'
      ]);

      $response->getBody()->write(json_encode([
        'message' => 'Admin berhasil dibuat! Silakan login',
        'data' => [
          'username' => $data['username'],
          'email' => $data['email'],
          'role' => 'admin'
        ]
      ]));

      return $response->withStatus(201);
    } catch (\Exception $e) {
      $response->getBody()->write(json_encode([
        'message' => 'Gagal membuat admin',
        'error' => $e->getMessage()
      ]));
      return $response->withStatus(500);
    }
  }

  // function create user khusus admin
  public function createUser(Request $request, Response $response)
  {
    // Ambil user yang sedang login dari JWT token
    $adminUser = $request->getAttribute('user');

    // Validasi $adminUser tidak null
    if (!$adminUser || !isset($adminUser->role)) {
      $response->getBody()->write(json_encode([
        'message' => 'Token tidak valid atau user tidak ditemukan'
      ]));
      return $response->withStatus(401);
    }

    // Double check apakah role adalah admin
    if ($adminUser->role !== 'admin') {
      $response->getBody()->write(json_encode([
        'message' => 'Hanya admin yang dapat membuat user baru'
      ]));
      return $response->withStatus(403);
    }

    $data = $request->getParsedBody();

    // Validasi input
    if (empty($data['username']) || empty($data['email']) || empty($data['password']) || empty($data['role'])) {
      $response->getBody()->write(json_encode([
        'message' => 'Semua field (username, email, password, role) harus diisi'
      ]));
      return $response->withStatus(400);
    }

    // Validasi role hanya boleh guru atau siswa
    if (!in_array($data['role'], ['guru', 'siswa'])) {
      $response->getBody()->write(json_encode([
        'message' => 'Role hanya boleh "guru" atau "siswa"'
      ]));
      return $response->withStatus(400);
    }

    // Cek apakah username sudah terdaftar
    if ($this->users->findByUsername($data['username'])) {
      $response->getBody()->write(json_encode([
        'message' => 'Username sudah terdaftar'
      ]));
      return $response->withStatus(409);
    }

    // Hash password
    $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

    try {
      // Buat user baru
      $this->users->create([
        'username' => trim($data['username']),
        'email' => trim($data['email']),
        'password' => $hashedPassword,
        'role' => $data['role']
      ]);

      $response->getBody()->write(json_encode([
        'message' => 'User berhasil dibuat',
        'data' => [
          'username' => $data['username'],
          'email' => $data['email'],
          'role' => $data['role'],
          'created_by' => $adminUser->role
        ]
      ]));

      return $response->withStatus(201);
    } catch (\Exception $e) {
      $response->getBody()->write(json_encode([
        'message' => 'Gagal membuat user',
        'error' => $e->getMessage()
      ]));
      return $response->withStatus(500);
    }
  }
}
