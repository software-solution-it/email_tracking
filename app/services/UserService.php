<?php

class User {
    private $conn;
    private $table = "users";
    private $roleTable = "roles";
    private $roleFunctionalityTable = "role_functionality";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($name, $email, $password, $verificationCode, $expirationDateTime, $role_id) {
        $query = "INSERT INTO users (name, email, password, verification_code, code_expiration, role_id, is_verified) 
                  VALUES (:name, :email, :password, :verification_code, :code_expiration, :role_id, 0)";
        
        $stmt = $this->conn->prepare($query);
    
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $password);
        $stmt->bindParam(":verification_code", $verificationCode);
        $stmt->bindParam(":code_expiration", $expirationDateTime); 
        $stmt->bindParam(":role_id", $role_id);
    
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        } else {
            return false;
        }
    }
    

    public function update($id, $name, $email, $role_id) {
        $query = "UPDATE " . $this->table . " 
                  SET name = :name, email = :email, role_id = :role_id 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":role_id", $role_id);

        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);

        return $stmt->execute();
    }

    public function getUserById($id) {
        $query = "SELECT u.id, u.name, u.email, r.role_name, u.created_at 
                  FROM " . $this->table . " u 
                  JOIN " . $this->roleTable . " r ON u.role_id = r.id 
                  WHERE u.id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function checkUserAccess($user_id, $functionality_name) {
        $query = "SELECT rf.functionality_name 
                  FROM " . $this->table . " u 
                  JOIN " . $this->roleTable . " r ON u.role_id = r.id 
                  JOIN " . $this->roleFunctionalityTable . " rf ON r.id = rf.role_id 
                  WHERE u.id = :user_id AND rf.functionality_name = :functionality_name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":functionality_name", $functionality_name);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
    }

    public function getAllRoles() {
        $query = "SELECT id, role_name FROM " . $this->roleTable;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateRole($id, $role_id) {
        $query = "UPDATE " . $this->table . " SET role_id = :role_id WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":role_id", $role_id);

        return $stmt->execute();
    }

    public function findByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":email", $email);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
