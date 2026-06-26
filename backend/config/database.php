<?php
/**
 * Database Connection - PDO Singleton
 */

declare(strict_types=1);

namespace Config;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $instance = null;

    /** Prevent instantiation */
    private function __construct() {}
    private function __clone() {}

    /**
     * Return the single PDO instance, creating it on first call.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }

        return self::$instance;
    }

    private static function createConnection(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
            PDO::MYSQL_ATTR_FOUND_ROWS   => true,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

            // Enforce strict SQL mode and timezone
            $pdo->exec("SET time_zone = '+03:30'");
            $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO'");

            return $pdo;

        } catch (PDOException $e) {
            // Never expose credentials or internal details in production
            if (APP_ENV === 'development') {
                throw new RuntimeException('Database connection failed: ' . $e->getMessage());
            }
            throw new RuntimeException('خطا در اتصال به پایگاه داده. لطفاً بعداً تلاش کنید.');
        }
    }
}