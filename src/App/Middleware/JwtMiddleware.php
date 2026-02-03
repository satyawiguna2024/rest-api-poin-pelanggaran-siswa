<?php

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JwtMiddleware
{
  private string $secret = "JX4CaWk5Yu98tLwF+JqN59SH4K4l4FAlYp7q75cIz8g";

  public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
  {
    $auth = $request->getHeaderLine('Authorization');

    if (!$auth) {
      return $this->unauthorized();
    }

    $token = str_replace('Bearer ', '', $auth);

    try {
      JWT::decode($token, new Key($this->secret, 'HS256'));
    } catch (\Exception $e) {
      return $this->unauthorized();
    }

    return $handler->handle($request);
  }

  private function unauthorized(): ResponseInterface
  {
    $response = new \Slim\Psr7\Response();
    $response->getBody()->write(json_encode(['message' => 'Unauthorized']));
    return $response->withStatus(401);
  }
}
