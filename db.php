<?php
declare(strict_types=1);

const DB_HOST    = '127.0.0.1';
const DB_NAME    = 'recipe_site';
const DB_USER    = 'root';   // change if your MySQL user is different
const DB_PASS    = '';       // XAMPP default is an empty password
const DB_CHARSET = 'utf8mb4';

final class Database
{
    private static ?PDO $pdo = null;

    /** Returns the shared PDO instance (created on first use). */
    public static function connect(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);

            try {
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // throw on SQL errors
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // rows as arrays
                    PDO::ATTR_EMULATE_PREPARES   => false,                   // real prepared statements
                ]);
            } catch (PDOException $e) {
                error_log('DB connection failed: ' . $e->getMessage());
                http_response_code(500);
                exit('Database connection failed. Check the settings in db.php and make sure MySQL is running.');
            }
        }
        return self::$pdo;
    }
}
