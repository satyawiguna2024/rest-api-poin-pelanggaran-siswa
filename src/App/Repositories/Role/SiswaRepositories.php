<?php

declare(strict_types=1);

namespace App\Repositories\Role;

use PDO;

class SiswaRepositories
{
  private PDO $db;

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  // read all siswa
  public function findAll()
  {
    $stmt = $this->db->prepare("
      SELECT
        u.id, u.username, u.email, u.role, u.status,
        s.nis, s.id_users, s.id_kelas, s.nama, s.alamat, s.tanggal_lahir, s.jenis_kelamin, s.agama, s.telepon,
        k.id, k.guru, k.nama_kelas,
        o.id, o.nama_ayah, o.nama_ibu, o.pekerjaan_ayah, o.pekerjaan_ibu, o.telepon_ayah, o.telepon_ibu, o.alamat_ayah, o.alamat_ibu,
        COUNT(*) OVER() as total_data
      FROM users u
      JOIN siswa s ON s.id_users = u.id
      LEFT JOIN kelas k ON k.id = s.id_kelas
      LEFT JOIN ortu_wali_siswa o ON o.id = s.id_ortu_wali_siswa
      WHERE u.role = 'siswa'
      ORDER BY u.id ASC
    ");
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function findById($id)
  {
    $stmt = $this->db->prepare("
      SELECT
        u.id, u.username, u.email, u.role, u.status,
        s.nis, s.id_users, s.id_kelas, s.nama, s.alamat, s.tanggal_lahir, s.jenis_kelamin, s.agama, s.telepon,
        k.id, k.guru, k.nama_kelas,
        o.id, o.nama_ayah, o.nama_ibu, o.pekerjaan_ayah, o.pekerjaan_ibu, o.telepon_ayah, o.telepon_ibu, o.alamat_ayah, o.alamat_ibu
      FROM users u
      JOIN siswa s ON s.id_users = u.id
      LEFT JOIN kelas k ON k.id = s.id_kelas
      LEFT JOIN ortu_wali_siswa o ON o.id = s.id_ortu_wali_siswa
      WHERE u.id = :id AND u.role = 'siswa'
      LIMIT 1
    ");

    $stmt->execute(['id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // update siswa + ortu_wali_siswa
  public function update($id, $data)
  {
    $stmtSiswa = $this->db->prepare("
      UPDATE siswa
      SET nama = :nama,
          alamat = :alamat,
          tanggal_lahir = :tanggal_lahir,
          jenis_kelamin = :jenis_kelamin,
          agama = :agama,
          telepon = :telepon,
          id_kelas = :id_kelas
      WHERE id_users = :id_users
    ");

    $stmtSiswa->execute([
      'nama'          => $data['nama'],
      'alamat'        => $data['alamat'],
      'tanggal_lahir' => $data['tanggal_lahir'],
      'jenis_kelamin' => $data['jenis_kelamin'],
      'agama'         => $data['agama'],
      'telepon'       => $data['telepon'],
      'id_kelas'      => $data['id_kelas'],
      'id_users'      => $id
    ]);

    if (!empty($data['id_ortu'])) {
      // Sudah punya ortu → langsung update
      $stmtOrtu = $this->db->prepare("
        UPDATE ortu_wali_siswa
        SET nama_ayah      = :nama_ayah,
            nama_ibu       = :nama_ibu,
            nama_wali      = :nama_wali,
            pekerjaan_ayah = :pekerjaan_ayah,
            pekerjaan_ibu  = :pekerjaan_ibu,
            pekerjaan_wali = :pekerjaan_wali,
            telepon_ayah   = :telepon_ayah,
            telepon_ibu    = :telepon_ibu,
            telepon_wali   = :telepon_wali,
            alamat_ayah    = :alamat_ayah,
            alamat_ibu     = :alamat_ibu,
            alamat_wali    = :alamat_wali
        WHERE id = :id_ortu
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
        'id_ortu'        => $data['id_ortu']
      ]);
    } else {
      // Belum punya ortu → cek dulu ada data ortu yang dikirim tidak
      $ortuFields = [
        'nama_ayah',
        'nama_ibu',
        'nama_wali',
        'pekerjaan_ayah',
        'pekerjaan_ibu',
        'pekerjaan_wali',
        'telepon_ayah',
        'telepon_ibu',
        'telepon_wali',
        'alamat_ayah',
        'alamat_ibu',
        'alamat_wali'
      ];

      $adaDataOrtu = false;
      foreach ($ortuFields as $field) {
        if (!empty(trim((string)($data[$field] ?? '')))) {
          $adaDataOrtu = true;
          break;
        }
      }

      // melakukan insert dulu setelah itu menyambungkan ke tabel siswa
      if ($adaDataOrtu) {
        $stmtInsertOrtu = $this->db->prepare("
          INSERT INTO ortu_wali_siswa (
              nama_ayah, nama_ibu, nama_wali,
              pekerjaan_ayah, pekerjaan_ibu, pekerjaan_wali,
              telepon_ayah, telepon_ibu, telepon_wali,
              alamat_ayah, alamat_ibu, alamat_wali
          ) VALUES (
              :nama_ayah, :nama_ibu, :nama_wali,
              :pekerjaan_ayah, :pekerjaan_ibu, :pekerjaan_wali,
              :telepon_ayah, :telepon_ibu, :telepon_wali,
              :alamat_ayah, :alamat_ibu, :alamat_wali
          )
        ");

        $stmtInsertOrtu->execute([
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

        $newIdOrtu = $this->db->lastInsertId();

        // meyambungkan id ortu baru kedalam tabel siswa
        $stmtLinkOrtu = $this->db->prepare("
          UPDATE siswa
          SET id_ortu_wali_siswa = :id_ortu
          WHERE id_users = :id_users
        ");

        $stmtLinkOrtu->execute([
          'id_ortu'  => $newIdOrtu,
          'id_users' => $id
        ]);
      }
    }
  }

  // delete function
  public function delete($id)
  {
    // Ambil id_ortu_wali_siswa sebelum dihapus
    // Karena setelah baris siswa dihapus, kita tidak bisa ambil info ini lagi
    $stmtGetOrtu = $this->db->prepare("SELECT id_ortu_wali_siswa FROM siswa WHERE id_users = :id");
    $stmtGetOrtu->execute(['id' => $id]);
    $siswa = $stmtGetOrtu->fetch(PDO::FETCH_ASSOC);

    // Hapus dari tabel siswa dulu
    $stmtSiswa = $this->db->prepare("DELETE FROM siswa WHERE id_users = :id");
    $stmtSiswa->execute(['id' => $id]);

    // Hapus ortu kalau ada
    if (!empty($siswa['id_ortu_wali_siswa'])) {
      $stmtOrtu = $this->db->prepare("DELETE FROM ortu_wali_siswa WHERE id = :id");
      $stmtOrtu->execute(['id' => $siswa['id_ortu_wali_siswa']]);
    }

    // setelah hapus dari tabel users
    $stmtUser = $this->db->prepare("DELETE FROM users WHERE id = :id");
    $stmtUser->execute(['id' => $id]);
  }

  private function normalizeNullable($value)
  {
    if ($value === null) return null;
    $trimmed = trim((string) $value);
    return $trimmed === '' ? null : $trimmed;
  }
}
