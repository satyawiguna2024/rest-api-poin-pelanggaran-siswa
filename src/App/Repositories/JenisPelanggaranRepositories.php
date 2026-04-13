<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class JenisPelanggaranRepositories
{
  private PDO $db;

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  // create
  public function create($data)
  {
    $stmt = $this->db->prepare("
      INSERT INTO jenis_pelanggaran (nama_pelanggaran, poin)
      VALUES (:nama_pelanggaran, :poin)
    ");

    $stmt->execute([
      'nama_pelanggaran' => $data['nama_pelanggaran'],
      'poin' => $data['poin']
    ]);

    return $this->db->lastInsertId();
  }

  // get all jenis_pelanggaran
  public function findAll()
  {
    $stmt = $this->db->prepare("
      SELECT id, nama_pelanggaran, poin, created_at, updated_at
      FROM jenis_pelanggaran
      ORDER BY id ASC
    ");

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // find by id jenis_pelanggaran
  public function findById($id)
  {
    $stmt = $this->db->prepare("
      SELECT id, nama_pelanggaran, poin, created_at, updated_at
      FROM jenis_pelanggaran
      WHERE id = :id
      LIMIT 1
    ");

    $stmt->execute(['id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // Dipakai untuk cek duplikat nama pelanggaran saat create & update
  public function findByNama(string $nama): array|false
  {
    $stmt = $this->db->prepare("
      SELECT id, nama_pelanggaran
      FROM jenis_pelanggaran
      WHERE nama_pelanggaran = :nama
      LIMIT 1
    ");

    $stmt->execute(['nama' => $nama]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // update jenis_pelanggaran
  public function update($id, $data)
  {
    $stmt = $this->db->prepare("
      UPDATE jenis_pelanggaran
      SET nama_pelanggaran = :nama_pelanggaran, poin = :poin
      WHERE id = :id
    ");

    $stmt->execute([
      'nama_pelanggaran' => $data['nama_pelanggaran'],
      'poin' => $data['poin'],
      'id' => $id
    ]);
  }

  // delete
  public function delete(int $id): void
  {
    $stmt = $this->db->prepare("DELETE FROM jenis_pelanggaran WHERE id = :id");
    $stmt->execute(['id' => $id]);
  }
}
