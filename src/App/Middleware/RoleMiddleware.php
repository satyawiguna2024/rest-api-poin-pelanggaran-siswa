<?php

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RoleMiddleware
{
  private string $secret = "JX4CaWk5Yu98tLwF+JqN59SH4K4l4FAlYp7q75cIz8g";
  private array $allowedRoles;

  public function __construct(array $allowedRoles)
  {
    $this->allowedRoles = $allowedRoles;
  }

  public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
  {
    // Ambil authorization header
    $auth = $request->getHeaderLine('Authorization');

    if (!$auth) {
      return $this->forbidden('Token tidak ditemukan');
    }

    $token = str_replace('Bearer ', '', $auth);

    try {
      // Decode token
      $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
      
      // Validasi apakah role user termasuk dalam allowed roles
      if (!in_array($decoded->role, $this->allowedRoles)) {
        return $this->forbidden('Anda tidak memiliki akses ke resource ini');
      }

      // Simpan user data ke request untuk digunakan di controller
      $request = $request->withAttribute('user', $decoded);

      return $handler->handle($request);
    } catch (\Exception $e) {
      return $this->forbidden('Token tidak valid');
    }
  }

  private function forbidden(string $message): ResponseInterface
  {
    $response = new \Slim\Psr7\Response();
    $response->getBody()->write(json_encode(['message' => $message]));
    return $response->withStatus(403);
  }
}
