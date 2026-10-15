<?php
/**
 * SIPWAY Higher Education - Database Connection
 * Single PDO connection reused across the application
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (APP_DEBUG) {
                die('Database connection failed: ' . $e->getMessage());
            }
            error_log('SIPWAY DB Error: ' . $e->getMessage());
            die('A database error occurred. Please try again later.');
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    // Prevent cloning
    private function __clone() {}
}

/**
 * Helper function to get PDO connection
 */
function getDB() {
    return Database::getInstance()->getConnection();
}
