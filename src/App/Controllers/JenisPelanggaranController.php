<?php

namespace App\Controllers;

use App\Repositories\JenisPelanggaranRepositories;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


class JenisPelanggaranController
{
  private JenisPelanggaranRepositories $pelanggaranRepo;

  public function __construct(JenisPelanggaranRepositories $pelanggaranRepo)
  {
    $this->pelanggaranRepo = $pelanggaranRepo;
  }

  // get all
  public function index(Request $request, Response $response)
  {
    $data = $this->pelanggaranRepo->findAll();

    $response->getBody()->write(json_encode([
      'message' => 'Berhasil mendapatkan semua data jenis pelanggaran',
      'status'  => 'success',
      'data' => $data
    ]));

    return $response->withStatus(200);
  }

  // get by id
  public function show(Request $request, Response $response, $args)
  {
    $id = $args['id'];
    $data = $this->pelanggaranRepo->findById($id);

    if (!$data) {
      $response->getBody()->write(json_encode([
        'message' => 'Pelanggaran tidak ditemukan',
      ]));
      return $response->withStatus(404);
    }

    $response->getBody()->write(json_encode([
      'message' => 'Berhasil mendapatkan data jenis pelanggaran',
      'status' => 'success',
      'data' => $data
    ]));

    return $response->withStatus(200);
  }

  // create
  public function store(Request $request, Response $response)
  {
    $data = $request->getParsedBody();

    $missing = [];
    if (empty(trim($data['nama_pelanggaran'] ?? ''))) {
      $missing[] = 'nama_pelanggaran';
    }
    if (!isset($data['poin']) || trim((string)$data['poin']) === '') {
      $missing[] = 'poin';
    }

    if ($missing !== []) {
      $response->getBody()->write(json_encode([
        'message' => 'Field wajib belum diisi',
        'status'  => 'error',
        'fields'  => $missing
      ]));
      return $response->withStatus(400);
    }

    // validasi poin harus angka dan tidak boleh negatif
    // is_numeric → cek apakah nilainya angka (bisa integer atau float)
    if (!is_numeric($data['poin']) || (int)$data['poin'] < 0) {
      $response->getBody()->write(json_encode([
        'message' => 'Poin harus berupa angka dan tidak boleh negatif',
      ]));
      return $response->withStatus(400);
    }

    // 3. Cek duplikat nama pelanggaran
    $existing = $this->pelanggaranRepo->findByNama(trim($data['nama_pelanggaran']));
    if ($existing) {
      $response->getBody()->write(json_encode([
        'message' => 'Nama pelanggaran sudah terdaftar',
      ]));
      return $response->withStatus(409);
    }

    try {
      $newId = $this->pelanggaranRepo->create([
        'nama_pelanggaran' => trim($data['nama_pelanggaran']),
        'poin' => $data['poin']
      ]);

      $response->getBody()->write(json_encode([
        'message' => 'Pelanggaran berhasil dibuat',
        'status'  => 'success',
        'data' => [
          'id' => $newId,
          'nama_pelanggaran' => trim($data['nama_pelanggaran']),
          'poin' => $data['poin']
        ]
      ]));

      return $response->withStatus(201);
    } catch (\Exception $e) {
      $response->getBody()->write(json_encode([
        'message' => 'Gagal membuat pelanggaran',
        'status'  => 'error',
        'error'   => $e->getMessage()
      ]));
      return $response->withStatus(500);
    }
  }

  // update
  public function update(Request $request, Response $response, $args)
  {
    $id = $args['id'];
    $data = $request->getParsedBody();

    $existing = $this->pelanggaranRepo->findById($id);
    if (!$existing) {
      $response->getBody()->write(json_encode([
        'message' => 'Jenis Pelanggaran tidak ditemukan',
      ]));
      return $response->withStatus(404);
    }

    $missing = [];
    if (empty(trim($data['nama_pelanggaran'] ?? ''))) {
      $missing[] = 'nama_pelanggaran';
    }
    if (!isset($data['poin']) || trim((string)$data['poin']) === '') {
      $missing[] = 'poin';
    }

    if ($missing !== []) {
      $response->getBody()->write(json_encode([
        'message' => 'Field wajib belum diisi',
        'status'  => 'error',
        'fields'  => $missing
      ]));
      return $response->withStatus(400);
    }

    // validasi poin
    if (!is_numeric($data['poin']) || $data['poin'] < 0) {
      $response->getBody()->write(json_encode([
        'message' => 'Poin harus berupa angka dan tidak boleh negatif',
      ]));
      return $response->withStatus(400);
    }

    // Cek duplikat nama — abaikan milik sendiri
    $byNama = $this->pelanggaranRepo->findByNama(trim($data['nama_pelanggaran']));
    if ($byNama && (int)$byNama['id'] !== $id) {
      $response->getBody()->write(json_encode([
        'message' => 'Nama pelanggaran sudah digunakan',
      ]));
      return $response->withStatus(409);
    }


    try {
      $this->pelanggaranRepo->update($id, [
        'nama_pelanggaran' => trim($data['nama_pelanggaran']),
        'poin' => $data['poin']
      ]);

      $response->getBody()->write(json_encode([
        'message' => 'Pelanggaran berhasil diupdate',
        'status' => 'success',
        'data' => [
          'id' => $id,
          'nama_pelanggaran' => trim($data['nama_pelanggaran']),
          'poin' => $data['poin']
        ]
      ]));

      return $response->withStatus(200);
    } catch (\Exception $e) {
      $response->getBody()->write(json_encode([
        'message' => 'Gagal mengupdate pelanggaran',
        'status'  => 'error',
        'error'   => $e->getMessage()
      ]));
      return $response->withStatus(500);
    }
  }

  // destroy
  public function destroy(Request $request, Response $response, $args)
  {
    $id = $args['id'];

    $existing = $this->pelanggaranRepo->findById($id);
    if (!$existing) {
      $response->getBody()->write(json_encode([
        'message' => 'Pelanggaran tidak ditemukan',
      ]));
      return $response->withStatus(404);
    }

    try {
      $this->pelanggaranRepo->delete($id);

      $response->getBody()->write(json_encode([
        'message' => 'Pelanggaran berhasil dihapus',
        'status'  => 'success'
      ]));

      return $response->withStatus(200);
    } catch (\Exception $e) {
      $response->getBody()->write(json_encode([
        'message' => 'Gagal menghapus pelanggaran',
        'status'  => 'error',
        'error'   => $e->getMessage()
      ]));
      return $response->withStatus(500);
    }
  }
}
