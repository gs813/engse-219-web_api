<?php
require_once "database.php";
require_once "response.php";

class webapiController {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // GET product by ID
    public function getProductById($id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM products WHERE id=?");
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                Response::json($product, 200);
            } else {
                Response::json(["status"=>"error","message"=>"Product not found"],404);
            }
        } catch(PDOException $e) {
            Response::json(["status"=>"error","message"=>"Database error: ".$e->getMessage()],500);
        }
    }

    // GET all products
    public function getAllProducts($filters = []) {
        try {
            $sql = "SELECT * FROM products";
            $params = [];
            if (!empty($filters)) {
                $conditions = [];
                foreach($filters as $field => $value) {
                    $conditions[] = "$field LIKE ?";
                    $params[] = "%$value%";
                }
                $sql .= " WHERE ".implode(" AND ", $conditions);
            }
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::json($products, 200);
        } catch(PDOException $e) {
            Response::json(["status"=>"error","message"=>"Database error: ".$e->getMessage()],500);
        }
    }

    // POST create product
    public function createProduct($data) {
        $required = ["sku","name","brand","category","price","stock"];
        foreach($required as $field) {
            if (!isset($data[$field])) {
                Response::json(["status"=>"error","message"=>"Missing field: $field"],400);
            }
        }

        try {
            $stmt = $this->conn->prepare(
                "INSERT INTO products (sku,name,brand,category,model,price,stock,warranty_months) VALUES (?,?,?,?,?,?,?,?)"
            );
            $success = $stmt->execute([
                $data["sku"],
                $data["name"],
                $data["brand"],
                $data["category"],
                $data["model"] ?? null,
                number_format((float)$data["price"],2,'.',''),
                (int)$data["stock"],
                isset($data["warranty_months"]) ? (int)$data["warranty_months"] : 12
            ]);

            if ($success) {
                Response::json(["status"=>"success","message"=>"Product created successfully"],201);
            } else {
                Response::json(["status"=>"error","message"=>"Failed to create product"],500);
            }
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                Response::json(["status"=>"error","message"=>"SKU already exists"],409);
            } else {
                Response::json(["status"=>"error","message"=>"Database error: ".$e->getMessage()],500);
            }
        }
    }

    // PUT full update product
    public function updateProduct($id, $data) {
        $required = ["sku","name","brand","category","price","stock"];
        foreach($required as $field) {
            if (!isset($data[$field])) {
                Response::json(["status"=>"error","message"=>"Missing field: $field"],400);
            }
        }

        try {
            $stmt = $this->conn->prepare(
                "UPDATE products SET sku=?, name=?, brand=?, category=?, model=?, price=?, stock=?, warranty_months=? WHERE id=?"
            );
            $stmt->execute([
                $data["sku"],
                $data["name"],
                $data["brand"],
                $data["category"],
                $data["model"] ?? null,
                number_format((float)$data["price"],2,'.',''),
                (int)$data["stock"],
                isset($data["warranty_months"]) ? (int)$data["warranty_months"] : 12,
                $id
            ]);

            if ($stmt->rowCount() > 0) {
                $this->getProductById($id);
            } else {
                Response::json(["status"=>"error","message"=>"Product not found or no changes made"],404);
            }
        } catch(PDOException $e) {
            Response::json(["status"=>"error","message"=>"Database error: ".$e->getMessage()],500);
        }
    }

    // PATCH partial update product
    public function patchProduct($id, $data) {
        if (empty($data)) {
            Response::json(["status"=>"error","message"=>"No data provided"],400);
        }

        $fields = [];
        $params = [];
        $allowed = ["sku","name","brand","category","model","price","stock","warranty_months"];

        foreach($allowed as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field=?";
                if ($field == "price") {
                    $params[] = number_format((float)$data[$field],2,'.','');
                } elseif ($field == "stock" || $field == "warranty_months") {
                    $params[] = (int)$data[$field];
                } else {
                    $params[] = $data[$field];
                }
            }
        }

        if (empty($fields)) {
            Response::json(["status"=>"error","message"=>"No valid fields to update"],400);
        }

        $params[] = $id;

        try {
            $sql = "UPDATE products SET ".implode(',', $fields)." WHERE id=?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            if ($stmt->rowCount() > 0) {
                $this->getProductById($id);
            } else {
                Response::json(["status"=>"error","message"=>"Product not found or no changes made"],404);
            }
        } catch(PDOException $e) {
            Response::json(["status"=>"error","message"=>"Database error: ".$e->getMessage()],500);
        }
    }

    // DELETE product
    public function deleteProduct($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM products WHERE id=?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                Response::json(["status"=>"success","message"=>"Product deleted successfully"],200);
            } else {
                Response::json(["status"=>"error","message"=>"Product not found"],404);
            }
        } catch(PDOException $e) {
            Response::json(["status"=>"error","message"=>"Database error: ".$e->getMessage()],500);
        }
    }
}
