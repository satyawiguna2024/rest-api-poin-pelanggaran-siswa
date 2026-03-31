<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Repositories\KelasRepositories;
use App\Repositories\Role\GuruRepositories;

class KelasController
{
  private KelasRepositories $kelasRepo;
  private GuruRepositories $guruRepo;

  public function __construct(KelasRepositories $kelasRepo, GuruRepositories $guruRepo)
  {
    $this->kelasRepo = $kelasRepo;
    $this->guruRepo = $guruRepo;
  }

  // create kelas
  public function store(Request $request, Response $response)
  {
    $data = $request->getParsedBody();

    // 1. Validasi nama_kelas wajib
    if (empty(trim($data['nama_kelas'] ?? ''))) {
      $response->getBody()->write(json_encode([
        'message' => 'nama_kelas wajib diisi'
      ]));
      return $response->withStatus(400);
    }

    $idGuru = null;
    if (!empty(trim($data['guru'] ?? ''))) {
      $guru = $this->guruRepo->findGuruByNuptk(trim($data['guru']));
      if (!$guru) {
        $response->getBody()->write(json_encode([
          'message' => 'Guru dengan NUPTK tersebut tidak ditemukan'
        ]));
        return $response->withStatus(404);
      }

      $idGuru = $guru['nuptk'];
    }

    try {
      $newId = $this->kelasRepo->create([
        'nama_kelas' => trim($data['nama_kelas']),
        'guru' => $idGuru
      ]);

      $response->getBody()->write(json_encode([
        'message' => 'Kelas berhasil dibuat',
        'data' => [
          'id' => $newId,
          'nama_kelas' => trim($data['nama_kelas']),
          'guru' => $data['guru'] ?? null
        ]
      ]));

      return $response->withStatus(201);
    } catch (\Exception $e) {
      $response->getBody()->write(json_encode([
        'message' => 'Gagal membuat kelas',
        'error'   => $e->getMessage()
      ]));
      return $response->withStatus(500);
    }
  }

  // get all data kelas
  public function index(Request $request, Response $response)
  {
    $kelasData = $this->kelasRepo->findAll();

    $data = array_map(function ($kelas) {
      return [
        'id' => $kelas['id'],
        'nama_kelas' => $kelas['nama_kelas'],
        'created_at' => $kelas['created_at'],
        'updated_at' => $kelas['updated_at'],
        'guru' => [
          'nuptk' => $kelas['nuptk'],
          'nama' => $kelas['nama'],
          'telepon' => $kelas['telepon'],
          'jabatan' => $kelas['jabatan']
        ],
      ];
    }, $kelasData);

    $response->getBody()->write(json_encode([
      'message' => 'Berhasil mendapatkan data kelas',
      'status' => 'success',
      'data' => $data
    ]));

    return $response->withStatus(200);
  }

  // get by id data kelas
  public function show(Request $request, Response $response, $args)
  {
    $id    = $args['id'];
    $kelas = $this->kelasRepo->findById($id);

    if (!$kelas) {
      $response->getBody()->write(json_encode([
        'message' => 'Kelas tidak ditemukan'
      ]));
      return $response->withStatus(404);
    }

    $data = [
      'id' => $kelas['id'],
      'id' => $kelas['nama_kelas'],
      'created_at' => $kelas['created_at'],
      'updated_at' => $kelas['updated_at'],

      'guru' => [
        'nuptk' => $kelas['nuptk'],
        'nama' => $kelas['nama'],
        'telepon' => $kelas['telepon'],
        'jabatan' => $kelas['jabatan']
      ],
    ];

    $response->getBody()->write(json_encode([
      'message' => 'Berhasil mendapatkan data kelas berdasarkan id',
      'status' => 'success',
      'data' => $data
    ]));

    return $response->withStatus(200);
  }

  // update kelas
  public function update(Request $request, Response $response, $args)
  {
    $id = $args['id'];
    $data = $request->getParsedBody();

    $existing = $this->kelasRepo->findById($id);
    if (!$existing) {
      $response->getBody()->write(json_encode([
        'message' => 'Kelas tidak ditemukan'
      ]));
      return $response->withStatus(404);
    }

    if (empty(trim($data['nama_kelas'] ?? ''))) {
      $response->getBody()->write(json_encode([
        'message' => 'nama_kelas wajib diisi'
      ]));
      return $response->withStatus(400);
    }

    // 4. Cari guru berdasarkan nuptk kalau dikirim
    $idGuru = null;
    if (!empty(trim($data['guru'] ?? ''))) {
      $guru = $this->guruRepo->findGuruByNuptk(trim($data['guru']));
      if (!$guru) {
        $response->getBody()->write(json_encode([
          'message' => 'Guru dengan NUPTK tersebut tidak ditemukan'
        ]));
        return $response->withStatus(404);
      }
      $idGuru = $guru['nuptk'];
    }

    try {
      $this->kelasRepo->update($id, [
        'nama_kelas' => trim($data['nama_kelas']),
        'guru' => $idGuru
      ]);

      $response->getBody()->write(json_encode([
        'message' => 'Kelas berhasil diupdate',
        'data' => [
          'id' => $id,
          'nama_kelas' => trim($data['nama_kelas']),
          'guru' => $data['guru'] ?? null
        ]
      ]));

      return $response->withStatus(200);
    } catch (\Exception $e) {
      $response->getBody()->write(json_encode([
        'message' => 'Gagal mengupdate kelas',
        'error'   => $e->getMessage()
      ]));
      return $response->withStatus(500);
    }
  }
}
