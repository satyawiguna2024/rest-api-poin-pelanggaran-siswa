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
        g.nuptk, g.nama, g.telepon, g.jabatan,
        COUNT(*) OVER() as total_data,
        (SELECT COUNT(id_users) FROM siswa s WHERE s.id_kelas = k.id) as jumlah_siswa
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
        g.nuptk, g.nama, g.telepon, g.jabatan,
        (SELECT COUNT(id_users) FROM siswa s WHERE s.id_kelas = k.id) as jumlah_siswa
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
      'guru' => $data['guru'],
      'id'  => $id
    ]);
  }

  // get siswa full list grouped by kelas
  public function getAllSiswaGroupedByKelas()
  {
    $stmt = $this->db->prepare("
      SELECT 
        s.id_kelas, s.id_users, s.nis, s.nama, s.jenis_kelamin, s.agama, s.telepon, s.alamat, s.tanggal_lahir,
        o.id as id_ortu, o.nama_ayah, o.nama_ibu, o.pekerjaan_ayah, o.pekerjaan_ibu, o.telepon_ayah, o.telepon_ibu, o.alamat_ayah, o.alamat_ibu
      FROM siswa s
      LEFT JOIN ortu_wali_siswa o ON o.id = s.id_ortu_wali_siswa
      WHERE s.id_kelas IS NOT NULL
    ");
    $stmt->execute();
    $siswaList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $grouped = [];
    foreach ($siswaList as $siswa) {
      $kelasId = $siswa['id_kelas'];
      
      $grouped[$kelasId][] = [
        'id_users' => $siswa['id_users'],
        'nis' => $siswa['nis'],
        'nama' => $siswa['nama'],
        'jenis_kelamin' => $siswa['jenis_kelamin'],
        'tanggal_lahir' => $siswa['tanggal_lahir'],
        'agama' => $siswa['agama'],
        'telepon' => $siswa['telepon'],
        'alamat' => $siswa['alamat'],
        'ortu_wali' => [
          'id_ortu' => $siswa['id_ortu'],
          'nama_ayah' => $siswa['nama_ayah'],
          'nama_ibu' => $siswa['nama_ibu'],
          'pekerjaan_ayah' => $siswa['pekerjaan_ayah'],
          'pekerjaan_ibu' => $siswa['pekerjaan_ibu'],
          'telepon_ayah' => $siswa['telepon_ayah'],
          'telepon_ibu' => $siswa['telepon_ibu'],
          'alamat_ayah' => $siswa['alamat_ayah'],
          'alamat_ibu' => $siswa['alamat_ibu'],
        ]
      ];
    }
    return $grouped;
  }

  public function getSiswaByKelasId($kelasId)
  {
    $stmt = $this->db->prepare("
      SELECT 
        s.id_users, s.nis, s.nama, s.jenis_kelamin, s.agama, s.telepon, s.alamat, s.tanggal_lahir,
        o.id as id_ortu, o.nama_ayah, o.nama_ibu, o.pekerjaan_ayah, o.pekerjaan_ibu, o.telepon_ayah, o.telepon_ibu, o.alamat_ayah, o.alamat_ibu
      FROM siswa s
      LEFT JOIN ortu_wali_siswa o ON o.id = s.id_ortu_wali_siswa
      WHERE s.id_kelas = :id_kelas
    ");
    $stmt->execute(['id_kelas' => $kelasId]);
    $siswaList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formattedList = [];
    foreach ($siswaList as $siswa) {
      $formattedList[] = [
        'id_users' => $siswa['id_users'],
        'nis' => $siswa['nis'],
        'nama' => $siswa['nama'],
        'jenis_kelamin' => $siswa['jenis_kelamin'],
        'tanggal_lahir' => $siswa['tanggal_lahir'],
        'agama' => $siswa['agama'],
        'telepon' => $siswa['telepon'],
        'alamat' => $siswa['alamat'],
        'ortu_wali' => [
          'id_ortu' => $siswa['id_ortu'],
          'nama_ayah' => $siswa['nama_ayah'],
          'nama_ibu' => $siswa['nama_ibu'],
          'pekerjaan_ayah' => $siswa['pekerjaan_ayah'],
          'pekerjaan_ibu' => $siswa['pekerjaan_ibu'],
          'telepon_ayah' => $siswa['telepon_ayah'],
          'telepon_ibu' => $siswa['telepon_ibu'],
          'alamat_ayah' => $siswa['alamat_ayah'],
          'alamat_ibu' => $siswa['alamat_ibu'],
        ]
      ];
    }
    return $formattedList;
  }
}
