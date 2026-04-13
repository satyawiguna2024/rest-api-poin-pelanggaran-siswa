<?php

declare(strict_types=1);

namespace App\Controllers\Role;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Repositories\Role\AdminRepositories;

class AdminController
{
  private AdminRepositories $adminRepo;

  public function __construct(AdminRepositories $adminRepo)
  {
    $this->adminRepo = $adminRepo;
  }

  public function index(Request $request, Response $response)
  {
    $admins = $this->adminRepo->findAll();
    $totalData = !empty($admins) ? $admins[0]['total_data'] : 0;

    $response->getBody()->write(json_encode([
      'message' => 'Berhasil mendapatkan semua data Admin',
      'status' => 'success',
      'items' => $totalData,
      'data' => $admins
    ]));

    return $response->withStatus(200);
  }

  public function show(Request $request, Response $response, $args)
  {
    // $args berisi parameter dari URL, misal /admins/5 → $args['id'] = '5'
    $id = $args['id'];

    $admin = $this->adminRepo->findById($id);

    if (!$admin) {
      $response->getBody()->write(json_encode([
        'message' => 'Admin tidak ditemukan',
      ]));
      return $response->withStatus(404);
    }

    $response->getBody()->write(json_encode([
      'message' => 'Berhasil mendapatkan data admin',
      'status' => 'success',
      'data' => $admin
    ]));

    return $response->withStatus(200);
  }

  // update admin data
  public function update(Request $request, Response $response, $args)
  {
    $id = $args['id'];
    $data = $request->getParsedBody();
    $existing = $this->adminRepo->findById($id);

    if (!$existing) {
      $response->getBody()->write(json_encode([
        'message' => 'Admin tidak ditemukan'
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
        'fields'  => $missing
      ]));
      return $response->withStatus(400);
    }

    // validasi format tanggal
    $parsedDate = \DateTime::createFromFormat('Y-m-d', $data['tanggal_lahir']);
    if (!$parsedDate || $parsedDate->format('Y-m-d') !== $data['tanggal_lahir']) {
      $response->getBody()->write(json_encode([
        'message' => 'Format tanggal_lahir harus Y-m-d',
      ]));
      return $response->withStatus(400);
    }

    // validasi jenis kelamin
    if (!in_array($data['jenis_kelamin'], ['L', 'P'], true)) {
      $response->getBody()->write(json_encode([
        'message' => 'Jenis kelamin hanya boleh "L" atau "P"',
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
      'jabatan'       => isset($data['jabatan']) && trim($data['jabatan']) !== '' ? trim($data['jabatan']) : 'admin sekolah',
    ];

    try {
      $this->adminRepo->update($id, $payload);

      $response->getBody()->write(json_encode([
        'message' => 'Data admin berhasil diupdate',
        'status' => 'success',
        'data'    => array_merge(['id' => $id], $payload)
      ]));

      return $response->withStatus(200);
    } catch (\Exception $e) {
      $response->getBody()->write(json_encode([
        'message' => 'Gagal mengupdate admin',
        'status' => 'error',
        'error'   => $e->getMessage()
      ]));
      return $response->withStatus(500);
    }
  }

  // delete data users role admin
  public function destroy(Request $request, Response $response, $args)
  {
    $id = $args['id'];

    // mengambil info admin yang sedang login dari JWT
    $loggedInUser = $request->getAttribute('user');

    // mencegah admin menghapus akunnya sendiri
    if ($loggedInUser->id === $id) {
      $response->getBody()->write(json_encode([
        'message' => 'Tidak dapat menghapus akun sendiri',
      ]));
      return $response->withStatus(403);
    }

    // Cek admin target ada atau tidak
    $existing = $this->adminRepo->findById($id);
    if (!$existing) {
      $response->getBody()->write(json_encode([
        'message' => 'Admin tidak ditemukan',
      ]));
      return $response->withStatus(404);
    }

    try {
      $this->adminRepo->delete($id);

      $response->getBody()->write(json_encode([
        'message' => 'Admin berhasil dihapus',
        'status' => 'success'
      ]));

      return $response->withStatus(200);
    } catch (\Exception $e) {
      $response->getBody()->write(json_encode([
        'message' => 'Gagal menghapus admin',
        'status' => 'error',
        'error'   => $e->getMessage()
      ]));
      return $response->withStatus(500);
    }
  }
}
