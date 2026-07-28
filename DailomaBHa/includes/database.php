<?php
class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        $config = json_decode(file_get_contents(__DIR__ . '/../config/config.json'), true);
        try {
            $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset=utf8mb4";
            $this->conn = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode(["error" => "Database connection breakdown: " . $e->getMessage()]));
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance->conn;
    }
}