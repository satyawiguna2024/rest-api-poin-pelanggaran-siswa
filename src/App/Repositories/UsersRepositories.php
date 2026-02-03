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
    $stmt = $this->db->prepare(
        "SELECT * FROM users 
      WHERE username = :username AND status = 'Y'"
      );
    $stmt->execute(['username' => $username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function create(array $data): bool
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
}
