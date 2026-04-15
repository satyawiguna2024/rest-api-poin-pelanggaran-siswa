<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class PelanggaranSiswaRepositories
{
  private PDO $db;

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  // CREATE
  public function create($data)
  {
    $sql = "INSERT INTO pelanggaran_siswa (nis, id_jenis_pelanggaran, tanggal, keterangan) 
            VALUES (:nis, :jenis, NOW(), :keterangan)";

    $stmt = $this->db->prepare($sql);
    return $stmt->execute([
      'nis' => $data['nis'],
      'jenis' => $data['id_jenis_pelanggaran'],
      'keterangan' => $data['keterangan']
    ]);
  }

  // READ ALL
  public function getAll()
  {
    $sql = "
      SELECT 
        ps.id, ps.tanggal, ps.keterangan,
        jp.id as jp_id, jp.nama_pelanggaran, jp.poin,
        s.id_users, s.nis, s.id_kelas, s.nama as nama_siswa, s.jenis_kelamin, s.agama, s.telepon as telepon_siswa, s.alamat as alamat_siswa, s.tanggal_lahir,
        k.nama_kelas,
        o.id as id_ortu, o.nama_ayah, o.nama_ibu, o.nama_wali, o.pekerjaan_ayah, o.pekerjaan_ibu, o.pekerjaan_wali, o.telepon_ayah, o.telepon_ibu, o.telepon_wali, o.alamat_ayah, o.alamat_ibu, o.alamat_wali
      FROM pelanggaran_siswa ps
      LEFT JOIN jenis_pelanggaran jp ON jp.id = ps.id_jenis_pelanggaran
      LEFT JOIN siswa s ON s.nis = ps.nis
      LEFT JOIN kelas k ON k.id = s.id_kelas
      LEFT JOIN ortu_wali_siswa o ON o.id = s.id_ortu_wali_siswa
      ORDER BY ps.id DESC
    ";

    $stmt = $this->db->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formattedList = [];
    foreach ($results as $row) {
      $formattedList[] = $this->formatData($row);
    }

    return $formattedList;
  }

  // READ BY ID
  public function getById($id)
  {
    $sql = "
      SELECT 
        ps.id, ps.tanggal, ps.keterangan,
        jp.id as jp_id, jp.nama_pelanggaran, jp.poin,
        s.id_users, s.nis, s.id_kelas, s.nama as nama_siswa, s.jenis_kelamin, s.agama, s.telepon as telepon_siswa, s.alamat as alamat_siswa, s.tanggal_lahir,
        k.nama_kelas,
        o.id as id_ortu, o.nama_ayah, o.nama_ibu, o.nama_wali, o.pekerjaan_ayah, o.pekerjaan_ibu, o.pekerjaan_wali, o.telepon_ayah, o.telepon_ibu, o.telepon_wali, o.alamat_ayah, o.alamat_ibu, o.alamat_wali
      FROM pelanggaran_siswa ps
      LEFT JOIN jenis_pelanggaran jp ON jp.id = ps.id_jenis_pelanggaran
      LEFT JOIN siswa s ON s.nis = ps.nis
      LEFT JOIN kelas k ON k.id = s.id_kelas
      LEFT JOIN ortu_wali_siswa o ON o.id = s.id_ortu_wali_siswa
      WHERE ps.id = :id
      LIMIT 1
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->formatData($row) : null;
  }

  // UPDATE
  public function update($id, $data)
  {
    $sql = "UPDATE pelanggaran_siswa 
            SET nis = :nis,
            id_jenis_pelanggaran = :jenis,
            keterangan = :keterangan
            WHERE id = :id";

    $stmt = $this->db->prepare($sql);
    return $stmt->execute([
      'id' => $id,
      'nis' => $data['nis'],
      'jenis' => $data['id_jenis_pelanggaran'],
      'keterangan' => $data['keterangan']
    ]);
  }

  // DELETE (optional)
  // public function delete($id)
  // {
  //   $sql = "DELETE FROM pelanggaran_siswa WHERE id_pelanggaran_siswa = :id";
  //   $stmt = $this->db->prepare($sql);
  //   return $stmt->execute(['id' => $id]);
  // }

  private function formatData($row)
  {
    return [
      'id' => $row['id'],
      'tanggal' => $row['tanggal'],
      'keterangan' => $row['keterangan'],
      'jenis_pelanggaran' => [
        'id' => $row['jp_id'],
        'nama_pelanggaran' => $row['nama_pelanggaran'],
        'poin' => $row['poin']
      ],
      'siswa' => [
        'id_users' => $row['id_users'],
        'nis' => $row['nis'],
        'nama' => $row['nama_siswa'],
        'jenis_kelamin' => $row['jenis_kelamin'],
        'tanggal_lahir' => $row['tanggal_lahir'],
        'agama' => $row['agama'],
        'telepon' => $row['telepon_siswa'],
        'alamat' => $row['alamat_siswa'],
        'kelas' => [
          'id' => $row['id_kelas'],
          'nama_kelas' => $row['nama_kelas']
        ],
        'ortu_wali' => [
          'id_ortu' => $row['id_ortu'],
          'nama_ayah' => $row['nama_ayah'],
          'nama_ibu' => $row['nama_ibu'],
          'nama_wali' => $row['nama_wali'] ?? null,
          'pekerjaan_ayah' => $row['pekerjaan_ayah'],
          'pekerjaan_ibu' => $row['pekerjaan_ibu'],
          'pekerjaan_wali' => $row['pekerjaan_wali'] ?? null,
          'telepon_ayah' => $row['telepon_ayah'],
          'telepon_ibu' => $row['telepon_ibu'],
          'telepon_wali' => $row['telepon_wali'] ?? null,
          'alamat_ayah' => $row['alamat_ayah'],
          'alamat_ibu' => $row['alamat_ibu'],
          'alamat_wali' => $row['alamat_wali'] ?? null,
        ]
      ]
    ];
  }
}