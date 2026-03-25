<?php

namespace App\Controllers;

use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use App\Repositories\UsersRepositories;

class AuthController
{
  private UsersRepositories $users;

  private string $jwtSecret = "JX4CaWk5Yu98tLwF+JqN59SH4K4l4FAlYp7q75cIz8g";

  public function __construct(UsersRepositories $users)
  {
    $this->users = $users;
  }

  public function login(Request $request, Response $response)
  {
    $data = $request->getParsedBody();

    // Validasi input
    if (empty($data['username']) || empty($data['password'])) {
      $response->getBody()->write(json_encode([
        'message' => 'Username dan password harus diisi'
      ]));
      return $response->withStatus(400);
    }

    $user = $this->users->findByUsername($data['username']);

    if (!$user || !password_verify($data['password'], $user['password'])) {
      $response->getBody()->write(json_encode([
        'message' => 'Username atau password salah'
      ]));
      return $response->withStatus(401);
    }

    $payload = [
      'id' => $user['id'],
      'role' => $user['role'],
      'exp' => time() + (60 * 60) // 1 jam
    ];

    $token = JWT::encode($payload, $this->jwtSecret, 'HS256');

    $response->getBody()->write(json_encode([
      'message' => 'Login berhasil',
      'token' => $token,
      'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'password' => $user['password'],
        'role' => $user['role']
      ]
    ]));

    return $response->withStatus(200);
  }

  public function registerAdmin(Request $request, Response $response)
  {
    $data = (array) $request->getParsedBody();

    //Validasi input
    if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
      $response->getBody()->write(json_encode([
        'message' => 'Username, email, dan password harus diisi'
      ]));
      return $response->withStatus(400);
    }

    //Mengecek apakah username sudah terdaftar
    if ($this->users->findByUsername($data['username'])) {
      $response->getBody()->write(json_encode([
        'message' => 'Username sudah terdaftar'
      ]));
      return $response->withStatus(409);
    }

    if ($this->users->findByEmail($data['email'])) {
      $response->getBody()->write(json_encode([
        'message' => 'Email sudah terdaftar'
      ]));
      return $response->withStatus(409);
    }

    //Validasi format email (basic validation)
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
      $response->getBody()->write(json_encode([
        'message' => 'Format email tidak valid'
      ]));
      return $response->withStatus(400);
    }

    //Validasi panjang password (minimal 6 karakter)
    if (strlen($data['password']) < 6) {
      $response->getBody()->write(json_encode([
        'message' => 'Password minimal 6 karakter'
      ]));
      return $response->withStatus(400);
    }

    //Hash password dengan bcrypt
    $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

    try {
      $this->users->create([
        'username' => trim($data['username']),
        'email' => trim($data['email']),
        'password' => $hashedPassword,
        'role' => 'admin'
      ]);

      $response->getBody()->write(json_encode([
        'message' => 'Admin berhasil dibuat! Silakan login',
        'data' => [
          'username' => $data['username'],
          'email' => $data['email'],
          'role' => 'admin'
        ]
      ]));

      return $response->withStatus(201);
    } catch (\Exception $e) {
      $response->getBody()->write(json_encode([
        'message' => 'Gagal membuat admin',
        'error' => $e->getMessage()
      ]));
      return $response->withStatus(500);
    }
  }

  // function create user khusus admin
  public function createUser(Request $request, Response $response)
  {
    return $this->createUserByRole($request, $response);
  }

  public function createGuru(Request $request, Response $response)
  {
    return $this->createUserByRole($request, $response, 'guru');
  }

  public function createSiswa(Request $request, Response $response)
  {
    return $this->createUserByRole($request, $response, 'siswa');
  }

  private function createUserByRole(
    Request $request,
    Response $response,
    ?string $forcedRole = null
  )
  {
    // Ambil user yang sedang login dari JWT token
    $adminUser = $request->getAttribute('user');

    // Validasi $adminUser tidak null
    if (!$adminUser || !isset($adminUser->role)) {
      $response->getBody()->write(json_encode([
        'message' => 'Token tidak valid atau user tidak ditemukan'
      ]));
      return $response->withStatus(401);
    }

    // Double check apakah role adalah admin
    if ($adminUser->role !== 'admin') {
      $response->getBody()->write(json_encode([
        'message' => 'Hanya admin yang dapat membuat user baru'
      ]));
      return $response->withStatus(403);
    }

    $data = (array) $request->getParsedBody();

    if ($forcedRole !== null) {
      if (
        isset($data['role']) &&
        trim((string) $data['role']) !== '' &&
        $data['role'] !== $forcedRole
      ) {
        $response->getBody()->write(json_encode([
          'message' => 'Role tidak sesuai dengan endpoint yang dipilih'
        ]));
        return $response->withStatus(400);
      }

      $data['role'] = $forcedRole;
    }

    // Validasi role hanya boleh guru atau siswa
    if (empty($data['role']) || !in_array($data['role'], ['guru', 'siswa'], true)) {
      $response->getBody()->write(json_encode([
        'message' => 'Role hanya boleh "guru" atau "siswa"'
      ]));
      return $response->withStatus(400);
    }

    $requiredFields = [
      'username',
      'email',
      'password',
      'role',
      'nama',
      'tanggal_lahir',
      'jenis_kelamin',
      'agama'
    ];

    if ($data['role'] === 'guru') {
      $requiredFields[] = 'nuptk';
    }

    if ($data['role'] === 'siswa') {
      $requiredFields[] = 'nis';
    }

    $missingFields = $this->getMissingFields($data, $requiredFields);

    if ($missingFields !== []) {
      $response->getBody()->write(json_encode([
        'message' => 'Field wajib belum diisi',
        'fields' => $missingFields
      ]));
      return $response->withStatus(400);
    }

    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
      $response->getBody()->write(json_encode([
        'message' => 'Format email tidak valid'
      ]));
      return $response->withStatus(400);
    }

    if (strlen((string) $data['password']) < 6) {
      $response->getBody()->write(json_encode([
        'message' => 'Password minimal 6 karakter'
      ]));
      return $response->withStatus(400);
    }

    if (!in_array($data['jenis_kelamin'], ['L', 'P'], true)) {
      $response->getBody()->write(json_encode([
        'message' => 'Jenis kelamin hanya boleh "L" atau "P"'
      ]));
      return $response->withStatus(400);
    }

    if (!$this->isValidDate($data['tanggal_lahir'])) {
      $response->getBody()->write(json_encode([
        'message' => 'Format tanggal_lahir harus Y-m-d'
      ]));
      return $response->withStatus(400);
    }

    // Cek apakah username sudah terdaftar
    if ($this->users->findByUsername(trim($data['username']))) {
      $response->getBody()->write(json_encode([
        'message' => 'Username sudah terdaftar'
      ]));
      return $response->withStatus(409);
    }

    if ($this->users->findByEmail(trim($data['email']))) {
      $response->getBody()->write(json_encode([
        'message' => 'Email sudah terdaftar'
      ]));
      return $response->withStatus(409);
    }

    if ($data['role'] === 'guru' && $this->users->findGuruByNuptk(trim($data['nuptk']))) {
      $response->getBody()->write(json_encode([
        'message' => 'NUPTK sudah terdaftar'
      ]));
      return $response->withStatus(409);
    }

    if ($data['role'] === 'siswa' && $this->users->findSiswaByNis(trim($data['nis']))) {
      $response->getBody()->write(json_encode([
        'message' => 'NIS sudah terdaftar'
      ]));
      return $response->withStatus(409);
    }

    // Hash password
    $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

    $payload = [
      'username' => trim($data['username']),
      'email' => trim($data['email']),
      'password' => $hashedPassword,
      'role' => $data['role'],
      'nama' => trim($data['nama']),
      'alamat' => $this->normalizeNullableString($data['alamat'] ?? null),
      'tanggal_lahir' => $data['tanggal_lahir'],
      'jenis_kelamin' => $data['jenis_kelamin'],
      'agama' => trim($data['agama']),
      'telepon' => $this->normalizeNullableString($data['telepon'] ?? null)
    ];

    if ($data['role'] === 'guru') {
      $payload['nuptk'] = trim($data['nuptk']);
      $payload['jabatan'] = $this->normalizeNullableString($data['jabatan'] ?? null) ?? 'guru ngajar';
    }

    if ($data['role'] === 'siswa') {
      $payload['nis'] = trim($data['nis']);
      $payload['id_kelas'] = isset($data['id_kelas']) && $data['id_kelas'] !== ''
        ? (int) $data['id_kelas']
        : null;
      $payload['id_ortu_wali_siswa'] = isset($data['id_ortu_wali_siswa']) && $data['id_ortu_wali_siswa'] !== ''
        ? (int) $data['id_ortu_wali_siswa']
        : null;
    }

    try {
      $createdUser = $this->users->createUserWithProfile($payload);

      $response->getBody()->write(json_encode([
        'message' => 'User dan data personal berhasil dibuat',
        'data' => [
          'id_users' => $createdUser['user_id'],
          'username' => $payload['username'],
          'email' => $payload['email'],
          'role' => $data['role'],
          $createdUser['profile_key'] => $createdUser['profile_value'],
          'created_by' => $adminUser->role
        ]
      ]));

      return $response->withStatus(201);
    } catch (\Exception $e) {
      $response->getBody()->write(json_encode([
        'message' => 'Gagal membuat user',
        'error' => $e->getMessage()
      ]));
      return $response->withStatus(500);
    }
  }

  private function getMissingFields(array $data, array $fields): array
  {
    $missingFields = [];

    foreach ($fields as $field) {
      if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
        $missingFields[] = $field;
      }
    }

    return $missingFields;
  }

  private function isValidDate(string $date): bool
  {
    $parsedDate = \DateTime::createFromFormat('Y-m-d', $date);

    return $parsedDate !== false && $parsedDate->format('Y-m-d') === $date;
  }

  private function normalizeNullableString($value): ?string
  {
    if ($value === null) {
      return null;
    }

    $normalized = trim((string) $value);

    return $normalized === '' ? null : $normalized;
  }
}
