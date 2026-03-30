<?php

declare(strict_types=1);

namespace App\Controllers\Role;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Repositories\Role\SiswaRepositories;
use App\Repositories\UsersRepositories;

class SiswaController
{
  private SiswaRepositories $siswaRepo;
  private UsersRepositories $usersRepo;

  public function __construct(SiswaRepositories $siswaRepo, UsersRepositories $usersRepo)
  {
    $this->siswaRepo = $siswaRepo;
    $this->usersRepo = $usersRepo;
  }

  // get all siswa
  public function index(Request $request, Response $response)
  {
    $siswa = $this->siswaRepo->findAll();

    $response->getBody()->write(json_encode([
      'message' => 'Berhasil mendapatkan semua siswa',
      'status' => 'success',
      'data'    => $siswa
    ]));

    return $response->withStatus(200);
  }

  // get siswa by id
  public function show(Request $request, Response $response, $args)
  {
    $id = $args['id'];
    $siswa = $this->siswaRepo->findById($id);

    if (!$siswa) {
      $response->getBody()->write(json_encode([
        'message' => 'Siswa tidak ditemukan'
      ]));
      return $response->withStatus(404);
    }

    $response->getBody()->write(json_encode([
      'message' => 'Berhasil mendapatkan siswa berdasarkan id',
      'status' => 'success',
      'data'    => $siswa
    ]));

    return $response->withStatus(200);
  }

  // update siswa + include ortu_wali_siswa
  public function update(Request $request, Response $response, $args)
  {
    $id = $args['id'];
    $data = $request->getParsedBody();

    $existing = $this->siswaRepo->findById($id);
    if (!$existing) {
      $response->getBody()->write(json_encode([
        'message' => 'Siswa tidak ditemukan'
      ]));
      return $response->withStatus(404);
    }

    // field wajib siswa
    $requiredFields = [ 'nama', 'tanggal_lahir', 'jenis_kelamin', 'agama' ];

    $missing = [];
    foreach ($requiredFields as $field) {
      if (empty(trim($data[$field] ?? ''))) {
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
        'message' => 'Format tanggal_lahir harus Y-m-d'
      ]));
      return $response->withStatus(400);
    }

    // validasi jenis kelamin
    if (!in_array($data['jenis_kelamin'], ['L', 'P'], true)) {
      $response->getBody()->write(json_encode([
        'message' => 'Jenis kelamin hanya boleh "L" atau "P"'
      ]));
      return $response->withStatus(400);
    }

    // id_ortu diambil dari data existing (hasil findById)
    // supaya repository tahu apakah harus UPDATE atau INSERT ortu
    $payload = [
      'nama'          => trim($data['nama']),
      'alamat'        => !empty(trim($data['alamat'] ?? '')) ? trim($data['alamat']) : null,
      'tanggal_lahir' => $data['tanggal_lahir'],
      'jenis_kelamin' => $data['jenis_kelamin'],
      'agama'         => trim($data['agama']),
      'telepon'       => !empty(trim($data['telepon'] ?? '')) ? trim($data['telepon']) : null,
      'id_kelas'      => !empty($data['id_kelas']) ? $data['id_kelas'] : null,

      // ini adalah kunci untuk tahu UPDATE atau INSERT di repository
      'id_ortu'       => $existing['id_ortu'] ?? null,

      // field table ortu_wali_siswa
      'nama_ayah'      => $data['nama_ayah'] ?? null,
      'nama_ibu'       => $data['nama_ibu'] ?? null,
      'nama_wali'      => $data['nama_wali'] ?? null,
      'pekerjaan_ayah' => $data['pekerjaan_ayah'] ?? null,
      'pekerjaan_ibu'  => $data['pekerjaan_ibu'] ?? null,
      'pekerjaan_wali' => $data['pekerjaan_wali'] ?? null,
      'telepon_ayah'   => $data['telepon_ayah'] ?? null,
      'telepon_ibu'    => $data['telepon_ibu'] ?? null,
      'telepon_wali'   => $data['telepon_wali'] ?? null,
      'alamat_ayah'    => $data['alamat_ayah'] ?? null,
      'alamat_ibu'     => $data['alamat_ibu'] ?? null,
      'alamat_wali'    => $data['alamat_wali'] ?? null,
    ];

    try {
      $this->siswaRepo->update($id, $payload);

      $response->getBody()->write(json_encode([
        'message' => 'Data siswa berhasil diupdate',
        'status' => 'success',
        'data'    => $payload
      ]));

      return $response->withStatus(200);
    } catch (\Exception $e) {
      $response->getBody()->write(json_encode([
        'message' => 'Gagal mengupdate siswa',
        'error'   => $e->getMessage()
      ]));
      return $response->withStatus(500);
    }
  }

  // delete
  public function destroy(Request $request, Response $response, $args)
    {
        $id = $args['id'];

        // Cek siswa ada atau tidak
        $existing = $this->siswaRepo->findById($id);
        if (!$existing) {
            $response->getBody()->write(json_encode([
                'message' => 'Siswa tidak ditemukan'
            ]));
            return $response->withStatus(404);
        }

        try {
            $this->siswaRepo->delete($id);

            $response->getBody()->write(json_encode([
                'message' => 'Siswa berhasil dihapus'
            ]));

            return $response->withStatus(200);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'message' => 'Gagal menghapus siswa',
                'error'   => $e->getMessage()
            ]));
            return $response->withStatus(500);
        }
    }
}
