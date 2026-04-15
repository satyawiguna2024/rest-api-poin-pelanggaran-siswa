<?php

namespace App\Controllers;

use App\Repositories\PelanggaranSiswaRepositories;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PelanggaranSiswaController
{
  private $repo;

  public function __construct(PelanggaranSiswaRepositories $repo)
  {
    $this->repo = $repo;
  }

  // CREATE
  public function create(Request $request, Response $response)
  {
    $data = $request->getParsedBody();

    $this->repo->create($data);

    $response->getBody()->write(json_encode([
      "message" => "Data berhasil ditambahkan"
    ]));

    return $response->withHeader('Content-Type', 'application/json');
  }

  // READ ALL
  public function index(Request $request, Response $response)
  {
    $data = $this->repo->getAll();

    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
  }

  // READ BY ID
  public function show(Request $request, Response $response, $args)
  {
    $data = $this->repo->getById($args['id']);

    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
  }

  // UPDATE
  public function update(Request $request, Response $response, $args)
  {
    $data = $request->getParsedBody();

    $this->repo->update($args['id'], $data);

    $response->getBody()->write(json_encode([
      "message" => "Data berhasil diupdate"
    ]));

    return $response->withHeader('Content-Type', 'application/json');
  }
}