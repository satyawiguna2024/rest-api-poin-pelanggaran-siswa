<?php

declare(strict_types=1);

namespace App\Repositories\Role;

use PDO;

class AdminRepositories
{
  private PDO $db;

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  // read all admin
  public function findAll()
  {
    $stmt = $this->db->prepare("
      SELECT
        u.id, u.username, u.email, u.role, u.status,
        a.nuptk, a.nama, a.alamat, a.tanggal_lahir, a.jenis_kelamin, a.agama, a.telepon, a.jabatan
      FROM users u
      JOIN admin a ON a.id_users = u.id
      WHERE u.role = 'admin'
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
        a.nuptk, a.nama, a.alamat, a.tanggal_lahir, a.jenis_kelamin, a.agama, a.telepon, a.jabatan
      FROM users u
      JOIN admin a ON a.id_users = u.id
      WHERE u.id = :id AND u.role = 'admin'
      LIMIT 1
    ");
    $stmt->execute(['id' => $id]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // update
  public function update($id, $data)
  {
    // Update tabel admin (data personal)
    $stmtAdmin = $this->db->prepare("
      UPDATE admin
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

  // delete data admin
  public function delete($id)
  {
    // hapus profil admin dulu (hindari foreign key error)
    $stmtAdmin = $this->db->prepare("DELETE FROM admin WHERE id_users = :id");
    $stmtAdmin->execute(['id' => $id]);

    // setelah itu hapus dari tabel users
    $stmtUser = $this->db->prepare("DELETE FROM users WHERE id = :id");
    $stmtUser->execute(['id' => $id]);
  }
}
