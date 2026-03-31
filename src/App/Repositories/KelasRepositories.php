<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class KelasRepositories
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
        INSERT INTO kelas (nama_kelas, guru)
        VALUES (:nama_kelas, :guru)
    ");

    $stmt->execute([
      'nama_kelas' => $data['nama_kelas'],
      'guru' => $data['guru']
    ]);

    return $this->db->lastInsertId();
  }

  // find all kelas
  public function findAll()
  {
    $stmt = $this->db->prepare("
      SELECT
        k.id, k.nama_kelas, k.created_at, k.updated_at,
        g.nuptk, g.nama, g.telepon, g.jabatan
      FROM kelas k
      LEFT JOIN guru g ON g.nuptk = k.guru
      ORDER BY k.id ASC
    ");

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function findById($id)
  {
    $stmt = $this->db->prepare("
      SELECT
        k.id, k.nama_kelas, k.created_at, k.updated_at,
        g.nuptk, g.nama, g.telepon, g.jabatan
      FROM kelas k
      LEFT JOIN guru g ON g.nuptk = k.guru
      WHERE k.id = :id
      LIMIT 1
    ");

    $stmt->execute(['id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // update
  public function update($id, $data)
  {
    $stmt = $this->db->prepare("
        UPDATE kelas
        SET nama_kelas = :nama_kelas, guru = :guru
        WHERE id = :id
    ");

    $stmt->execute([
      'nama_kelas' => $data['nama_kelas'],
      'guru' => $data['guru'], // id guru hasil lookup dari nuptk
      'id'  => $id
    ]);
  }
}
