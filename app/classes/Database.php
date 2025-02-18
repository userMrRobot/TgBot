<?php

namespace app\classes;

use PDO;

class Database
{

    private static $instance = null;
    private $conn;
    private $host = DB_HOST;
    private $dbName = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASSWORD;

    private function __construct()
    {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbName}";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            echo 'Подключились';
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\Exception $e) {
            die("Ошибка подключения: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function prepare($query)
    {
        return $this->conn->prepare($query);
    }
    // Методы для работы с транзакциями
    // запуск
    public function beginTransaction()
    {
        return $this->conn->beginTransaction();
    }

    //фикс
    public function commit()
    {
        return $this->conn->commit();
    }

    //откат
    public function rollBack()
    {
        return $this->conn->rollBack();
    }

    private function __clone()
    {
    }


}