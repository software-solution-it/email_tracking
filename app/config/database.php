<?php

class Database {
    private $host = "lsf-database.c3w06g60idzh.us-east-1.rds.amazonaws.com";
    private $db_name = "mail";
    private $username = "lsfuseradmin";
    private $password = "AVNS_ba0oSBBV3VPddx0lTqH";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            throw new Exception("Failed to connect to the database." . $exception);
        }
        return $this->conn;
    }
}
