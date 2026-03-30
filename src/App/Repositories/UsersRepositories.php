<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class UsersRepositories
{
  private PDO $db;

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  public function findByUsername(string $username)
  {
    $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username AND status = 'Y'");
    $stmt->execute(['username' => $username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function findByEmail(string $email)
  {
    $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function findGuruByNuptk(string $nuptk)
  {
    $stmt = $this->db->prepare("SELECT nuptk FROM guru WHERE nuptk = :nuptk LIMIT 1");
    $stmt->execute(['nuptk' => $nuptk]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function findSiswaByNis(string $nis)
  {
    $stmt = $this->db->prepare("SELECT nis FROM siswa WHERE nis = :nis LIMIT 1");
    $stmt->execute(['nis' => $nis]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function findAdminByNuptk(string $nuptk)
  {
    $stmt = $this->db->prepare("SELECT nuptk FROM admin WHERE nuptk = :nuptk LIMIT 1");
    $stmt->execute(['nuptk' => $nuptk]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function create(array $data)
  {
    $stmt = $this->db->prepare("
        INSERT INTO users (username, email, password, role, status)
        VALUES (:username, :email, :password, :role, 'Y')
    ");

    return $stmt->execute([
      'username' => $data['username'],
      'email' => $data['email'],
      'password' => $data['password'],
      'role' => $data['role']
    ]);
  }

  public function createUserWithProfile(array $data): array
  {
    $this->db->beginTransaction();

    try {
      // pembuatan gabungan satu insert dengan siswa dan data ortu_wali_siswa
      $idOrtu = null;

      // aksi penambahan data users role siswa + include tambah data ortu_wali_siswa
      if ($data['role'] === 'siswa') {
        // Kumpulkan semua field ortu dari request
        $ortuFields = [
          'nama_ayah', 'nama_ibu', 'nama_wali',
          'pekerjaan_ayah', 'pekerjaan_ibu', 'pekerjaan_wali',
          'telepon_ayah', 'telepon_ibu', 'telepon_wali',
          'alamat_ayah', 'alamat_ibu', 'alamat_wali'
        ];

        // mencari minimal ada 1 field ortu yang diisi
        $adaDataOrtu = false;
        foreach ($ortuFields as $field) {
          if (!empty(trim((string)($data[$field] ?? '')))) {
            $adaDataOrtu = true;
            break;
          }
        }

        if ($adaDataOrtu) {
          // query add ortu_wali_siswa
          $stmtOrtu = $this->db->prepare("
            INSERT INTO ortu_wali_siswa ( nama_ayah, nama_ibu, nama_wali, pekerjaan_ayah, pekerjaan_ibu, pekerjaan_wali, telepon_ayah, telepon_ibu, telepon_wali, alamat_ayah, alamat_ibu, alamat_wali )
            VALUES ( :nama_ayah, :nama_ibu, :nama_wali, :pekerjaan_ayah, :pekerjaan_ibu, :pekerjaan_wali, :telepon_ayah, :telepon_ibu, :telepon_wali, :alamat_ayah, :alamat_ibu, :alamat_wali )
          ");
  
          $stmtOrtu->execute([
            'nama_ayah'      => $this->normalizeNullable($data['nama_ayah'] ?? null),
            'nama_ibu'       => $this->normalizeNullable($data['nama_ibu'] ?? null),
            'nama_wali'      => $this->normalizeNullable($data['nama_wali'] ?? null),
            'pekerjaan_ayah' => $this->normalizeNullable($data['pekerjaan_ayah'] ?? null),
            'pekerjaan_ibu'  => $this->normalizeNullable($data['pekerjaan_ibu'] ?? null),
            'pekerjaan_wali' => $this->normalizeNullable($data['pekerjaan_wali'] ?? null),
            'telepon_ayah'   => $this->normalizeNullable($data['telepon_ayah'] ?? null),
            'telepon_ibu'    => $this->normalizeNullable($data['telepon_ibu'] ?? null),
            'telepon_wali'   => $this->normalizeNullable($data['telepon_wali'] ?? null),
            'alamat_ayah'    => $this->normalizeNullable($data['alamat_ayah'] ?? null),
            'alamat_ibu'     => $this->normalizeNullable($data['alamat_ibu'] ?? null),
            'alamat_wali'    => $this->normalizeNullable($data['alamat_wali'] ?? null),
          ]);
  
          $idOrtu = (int) $this->db->lastInsertId();
        }

        // inject id ortu ke data siswa
        $data['id_ortu_wali_siswa'] = $idOrtu;
      }

      $this->create($data);
      $userId = (int) $this->db->lastInsertId();

      if ($data['role'] === 'admin') {
        $this->createAdminProfile($userId, $data);
        $this->db->commit();

        return [
          'user_id' => $userId,
          'profile_key' => 'nuptk',
          'profile_value' => $data['nuptk']
        ];
      }

      if ($data['role'] === 'guru') {
        $this->createGuruProfile($userId, $data);
        $this->db->commit();

        return [
          'user_id' => $userId,
          'profile_key' => 'nuptk',
          'profile_value' => $data['nuptk']
        ];
      }

      $this->createSiswaProfile($userId, $data);
      $this->db->commit();

      return [
        'user_id' => $userId,
        'profile_key' => 'nis',
        'profile_value' => $data['nis'],
        'id_ortu' => $idOrtu // tambah idOrtu
      ];
    } catch (\Throwable $th) {
      if ($this->db->inTransaction()) {
        $this->db->rollBack();
      }

      throw $th;
    }
  }

  private function createAdminProfile(int $userId, array $data): void
  {
    $stmt = $this->db->prepare("
      INSERT INTO admin ( nuptk, id_users, nama, alamat, tanggal_lahir, jenis_kelamin, agama, telepon, jabatan ) 
      VALUES ( :nuptk, :id_users, :nama, :alamat, :tanggal_lahir, :jenis_kelamin, :agama, :telepon, :jabatan )
    ");

    $stmt->execute([
      'nuptk' => $data['nuptk'],
      'id_users' => $userId,
      'nama' => $data['nama'],
      'alamat' => $data['alamat'] ?? null,
      'tanggal_lahir' => $data['tanggal_lahir'],
      'jenis_kelamin' => $data['jenis_kelamin'],
      'agama' => $data['agama'],
      'telepon' => $data['telepon'] ?? null,
      'jabatan' => $data['jabatan'] ?? 'admin sekolah'
    ]);
  }

  private function createGuruProfile(int $userId, array $data): void
  {
    $stmt = $this->db->prepare("
      INSERT INTO guru ( nuptk, id_users, nama, alamat, tanggal_lahir, jenis_kelamin, agama, telepon, jabatan )
      VALUES ( :nuptk, :id_users, :nama, :alamat, :tanggal_lahir, :jenis_kelamin, :agama, :telepon, :jabatan )
    ");

    $stmt->execute([
      'nuptk' => $data['nuptk'],
      'id_users' => $userId,
      'nama' => $data['nama'],
      'alamat' => $data['alamat'] ?? null,
      'tanggal_lahir' => $data['tanggal_lahir'],
      'jenis_kelamin' => $data['jenis_kelamin'],
      'agama' => $data['agama'],
      'telepon' => $data['telepon'] ?? null,
      'jabatan' => $data['jabatan'] ?? 'guru ngajar'
    ]);
  }

  private function createSiswaProfile(int $userId, array $data): void
  {
    $stmt = $this->db->prepare("
      INSERT INTO siswa ( nis, id_users, id_ortu_wali_siswa, id_kelas, nama, alamat, tanggal_lahir, jenis_kelamin, agama, telepon )
      VALUES ( :nis, :id_users, :id_ortu_wali_siswa, :id_kelas, :nama, :alamat, :tanggal_lahir, :jenis_kelamin, :agama, :telepon )
    ");

    $stmt->execute([
      'nis' => $data['nis'],
      'id_users' => $userId,
      'id_ortu_wali_siswa' => $data['id_ortu_wali_siswa'] ?? null,
      'id_kelas' => $data['id_kelas'] ?? null,
      'nama' => $data['nama'],
      'alamat' => $data['alamat'] ?? null,
      'tanggal_lahir' => $data['tanggal_lahir'],
      'jenis_kelamin' => $data['jenis_kelamin'],
      'agama' => $data['agama'],
      'telepon' => $data['telepon'] ?? null
    ]);
  }

  private function normalizeNullable($value): ?string
  {
    if ($value === null) return null;
    $trimmed = trim((string) $value);
    return $trimmed === '' ? null : $trimmed;
  }
}
