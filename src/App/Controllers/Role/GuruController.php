<?php

declare(strict_types=1);

namespace App\Controllers\Role;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Repositories\Role\GuruRepositories;

class GuruController
{
  private GuruRepositories $guruRepo;

  public function __construct(GuruRepositories $guruRepo)
  {
    $this->guruRepo = $guruRepo;
  }

  // mengambil semua data guru
  public function index(Request $request, Response $response)
  {
    $guruData = $this->guruRepo->findAll();

    $data = array_map(function ($guru) {
      return [
        'nuptk' => $guru['nuptk'],
        'nama' => $guru['nama'],
        'alamat' => $guru['alamat'],
        'tanggal_lahir' => $guru['tanggal_lahir'],
        'jenis_kelamin' => $guru['jenis_kelamin'],
        'agama' => $guru['agama'],
        'telepon' => $guru['telepon'],
        'jabatan' => $guru['jabatan'],
        'user_account' => [
          'id' => $guru['id'],
          'username' => $guru['username'],
          'email' => $guru['email'],
          'role' => $guru['role'],
          'status' => $guru['status']
        ]
      ];
    }, $guruData);

    $response->getBody()->write(json_encode([
      'message' => 'Berhasil mendapatkan semua data Guru',
      'status' => 'success',
      'data' => $data
    ]));

    return $response->withStatus(200);
  }

  public function show(Request $request, Response $response, $args)
  {
    // $args berisi parameter dari URL, misal /guru/5 → $args['id'] = '5'
    $id = $args['id'];

    $guru = $this->guruRepo->findById($id);

    if (!$guru) {
      $response->getBody()->write(json_encode([
        'message' => 'Guru tidak ditemukan',
      ]));
      return $response->withStatus(404);
    }

    $data = [
      'nuptk' => $guru['nuptk'],
      'nama' => $guru['nama'],
      'alamat' => $guru['alamat'],
      'tanggal_lahir' => $guru['tanggal_lahir'],
      'jenis_kelamin' => $guru['jenis_kelamin'],
      'agama' => $guru['agama'],
      'telepon' => $guru['telepon'],
      'jabatan' => $guru['jabatan'],
      'user_account' => [
        'id' => $guru['id'],
        'username' => $guru['username'],
        'email' => $guru['email'],
        'role' => $guru['role'],
        'status' => $guru['status']
      ]
    ];

    $response->getBody()->write(json_encode([
      'message' => 'Berhasil mendapatkan data guru',
      'status' => 'success',
      'data' => $data
    ]));

    return $response->withStatus(200);
  }

  // update guru data
  public function update(Request $request, Response $response, $args)
  {
    $id = $args['id'];
    $data = $request->getParsedBody();
    $existing = $this->guruRepo->findById($id);

    if (!$existing) {
      $response->getBody()->write(json_encode([
        'message' => 'Guru tidak ditemukan',
      ]));
      return $response->withStatus(404);
    }

    // field yang wajib di isi
    $requiredFields = ['nama', 'tanggal_lahir', 'jenis_kelamin', 'agama'];

    $missing = [];
    foreach ($requiredFields as $field) {
      if (empty(trim((string)($data[$field] ?? '')))) {
        $missing[] = $field;
      }
    }

    if ($missing !== []) {
      $response->getBody()->write(json_encode([
        'message' => 'Field wajib belum diisi',
        'status' => 'failed',
        'fields'  => $missing
      ]));
      return $response->withStatus(400);
    }

    // validasi format tanggal
    $parsedDate = \DateTime::createFromFormat('Y-m-d', $data['tanggal_lahir']);
    if (!$parsedDate || $parsedDate->format('Y-m-d') !== $data['tanggal_lahir']) {
      $response->getBody()->write(json_encode([
        'message' => 'Format tanggal_lahir harus Y-m-d',
        'status' => 'failed'
      ]));
      return $response->withStatus(400);
    }

    // validasi jenis kelamin
    if (!in_array($data['jenis_kelamin'], ['L', 'P'], true)) {
      $response->getBody()->write(json_encode([
        'message' => 'Jenis kelamin hanya boleh "L" atau "P"',
        'status' => "failed"
      ]));
      return $response->withStatus(400);
    }

    // payload
    $payload = [
      'nama'          => trim($data['nama']),
      'alamat'        => isset($data['alamat']) && trim($data['alamat']) !== '' ? trim($data['alamat']) : null,
      'tanggal_lahir' => $data['tanggal_lahir'],
      'jenis_kelamin' => $data['jenis_kelamin'],
      'agama'         => trim($data['agama']),
      'telepon'       => isset($data['telepon']) && trim($data['telepon']) !== '' ? trim($data['telepon']) : null,
      'jabatan'       => isset($data['jabatan']) && trim($data['jabatan']) !== '' ? trim($data['jabatan']) : 'guru mapel',
    ];

    try {
      $this->guruRepo->update($id, $payload);

      $response->getBody()->write(json_encode([
        'message' => 'Data guru berhasil diupdate',
        'status' => 'success',
        'data'    => array_merge(['id' => $id], $payload)
      ]));

      return $response->withStatus(200);
    } catch (\Exception $e) {
      $response->getBody()->write(json_encode([
        'message' => 'Gagal mengupdate guru',
        'status' => 'error',
        'error'   => $e->getMessage()
      ]));
      return $response->withStatus(500);
    }
  }

  // delete data users role guru
  public function destroy(Request $request, Response $response, $args)
  {
    $id = $args['id'];

    // mengambil info guru yang sedang login dari JWT
    $loggedInUser = $request->getAttribute('user');

    // mencegah guru menghapus akunnya sendiri
    if ($loggedInUser->id === $id) {
      $response->getBody()->write(json_encode([
        'message' => 'Tidak dapat menghapus akun sendiri',
      ]));
      return $response->withStatus(403);
    }

    // Cek guru target ada atau tidak
    $existing = $this->guruRepo->findById($id);
    if (!$existing) {
      $response->getBody()->write(json_encode([
        'message' => 'Guru tidak ditemukan',
      ]));
      return $response->withStatus(404);
    }

    try {
      $this->guruRepo->delete($id);

      $response->getBody()->write(json_encode([
        'message' => 'Guru berhasil dihapus',
        'status' => 'success'
      ]));

      return $response->withStatus(200);
    } catch (\Exception $e) {
      $response->getBody()->write(json_encode([
        'message' => 'Gagal menghapus guru',
        'status' => 'error',
        'error'   => $e->getMessage()
      ]));
      return $response->withStatus(500);
    }
  }
}
