<?php

declare(strict_types=1);

namespace App\Repositories\Role;

use PDO;

class GuruRepositories
{
  private PDO $db;

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  // read all guru
  public function findAll()
  {
    $stmt = $this->db->prepare("
      SELECT
        u.id, u.username, u.email, u.role, u.status,
        g.nuptk, g.nama, g.alamat, g.tanggal_lahir, g.jenis_kelamin, g.agama, g.telepon, g.jabatan, g.created_at, g.updated_at,
        COUNT(*) OVER() as total_data
      FROM users u
      JOIN guru g ON g.id_users = u.id
      WHERE u.role = 'guru'
      ORDER BY u.id ASC
    ");
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // get by id
  public function findById($id)
  {
    $stmt = $this->db->prepare("
      SELECT
        u.id, u.username, u.email, u.role, u.status,
        g.nuptk, g.nama, g.alamat, g.tanggal_lahir, g.jenis_kelamin, g.agama, g.telepon, g.jabatan, g.created_at, g.updated_at
      FROM users u
      JOIN guru g ON g.id_users = u.id
      WHERE u.id = :id AND u.role = 'guru'
      LIMIT 1
    ");
    $stmt->execute(['id' => $id]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // find by nuptk
  public function findGuruByNuptk(string $nuptk)
  {
    $stmt = $this->db->prepare("SELECT nuptk, nama, jabatan, telepon FROM guru WHERE nuptk = :nuptk  LIMIT 1");
    $stmt->execute(['nuptk' => $nuptk]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // update data guru
  public function update($id, $data)
  {
    // Update tabel admin (data personal)
    $stmtAdmin = $this->db->prepare("
      UPDATE guru
      SET nama = :nama, alamat = :alamat, tanggal_lahir = :tanggal_lahir, jenis_kelamin = :jenis_kelamin, agama = :agama, telepon = :telepon, jabatan = :jabatan
      WHERE id_users = :id_users
    ");
    $stmtAdmin->execute([
      'nama'          => $data['nama'],
      'alamat'        => $data['alamat'],
      'tanggal_lahir' => $data['tanggal_lahir'],
      'jenis_kelamin' => $data['jenis_kelamin'],
      'agama'         => $data['agama'],
      'telepon'       => $data['telepon'],
      'jabatan'       => $data['jabatan'],
      'id_users'      => $id
    ]);
  }

  // delete data guru
  public function delete($id)
  {
    // hapus profil guru dulu (hindari foreign key error)
    $stmtAdmin = $this->db->prepare("DELETE FROM guru WHERE id_users = :id");
    $stmtAdmin->execute(['id' => $id]);

    // setelah itu hapus dari tabel users
    $stmtUser = $this->db->prepare("DELETE FROM users WHERE id = :id");
    $stmtUser->execute(['id' => $id]);
  }
}
