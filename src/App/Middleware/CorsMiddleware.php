<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class CorsMiddleware implements MiddlewareInterface
{
  public function process(Request $request, RequestHandler $handler): Response
  {
    // Handle preflight request
    // Browser kirim OPTIONS dulu sebelum request asli (GET/POST/PUT/DELETE)
    // Kalau tidak dihandle, request dari frontend langsung diblokir browser
    if ($request->getMethod() === 'OPTIONS') {
      $response = new \Slim\Psr7\Response();
      return $this->addCorsHeaders($response);
    }

    $response = $handler->handle($request);
    return $this->addCorsHeaders($response);
  }

  private function addCorsHeaders(Response $response): Response
  {
    return $response
      // Izinkan request dari frontend React (Vite default port 5173)
      ->withHeader('Access-Control-Allow-Origin', 'http://localhost:5173')

      // Method yang diizinkan
      ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')

      // Header yang diizinkan dikirim frontend
      // Authorization → untuk JWT token
      // Content-Type  → untuk JSON body
      ->withHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type')

      // Izinkan frontend baca response header
      ->withHeader('Access-Control-Allow-Credentials', 'true');
  }
}
