<?php
class Database {
    private static $host = "localhost";
    private static $db_name = "webapi_demo";
    private static $username = "root";
    private static $password = "";
    private static $conn = null;

    public static function getConnection() {
        if (self::$conn === null) {
            try {
                self::$conn = new PDO(
                    "mysql:host=".self::$host.";dbname=".self::$db_name.";charset=utf8",
                    self::$username,
                    self::$password
                );
                self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode([
                    "status" => "error",
                    "message" => "Database connection failed: " . $e->getMessage()
                ]);
                exit;
            }
        }
        return self::$conn;
    }
}
