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

  public function login(Request $request, Response $response): Response
  {
    $data = $request->getParsedBody();

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
      'token' => $token
    ]));

    return $response;
  }

  public function register(Request $request, Response $response): Response
  {
    $data = $request->getParsedBody();

    $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

    $this->users->create([
      'username' => trim($data['username']),
      'email' => $data['email'],
      'password' => $hashedPassword,
      'role' => $data['role']
    ]);

    $response->getBody()->write(json_encode([
      'message' => 'Register berhasil'
    ]));

    return $response->withStatus(201);
  }
}
