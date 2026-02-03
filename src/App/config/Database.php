<?php

declare(strict_types=1);

namespace App\Config;

use PDO;

class Database
{
  public static function connect()
  {
    $dsn = "mysql:host=127.0.0.1;port=8889;dbname=poin_pelanggaran;charset=utf8";

    $pdo = new PDO($dsn, 'root', 'root', [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    return $pdo;
  }
}
