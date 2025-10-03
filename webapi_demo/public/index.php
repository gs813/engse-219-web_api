<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../src/database.php";
require_once __DIR__ . "/../src/response.php";
require_once __DIR__ . "/../src/webapiController.php";

$controller = new webapiController();
$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
$path = trim($path, '/');
$segments = $path === '' ? [] : explode('/', $path);

// Test endpoint
if (isset($segments[0]) && $segments[0] === 'test') {
    Response::json(["status"=>"success","message"=>"API is working","method"=>$method,"segments"=>$segments],200);
}

// Root path
if (empty($segments)) {
    switch ($method) {
        case 'GET':
            $controller->getAllProducts();
            break;
        case 'POST':
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) Response::json(["status"=>"error","message"=>"Invalid JSON"],400);
            $controller->createProduct($data);
            break;
        default:
            Response::json(["status"=>"error","message"=>"Method not allowed"],405);
    }
    exit;
}

// /products/{id}
if ($segments[0] === 'products') {
    if (count($segments) == 2) {
        $id = (int)$segments[1];

        switch ($method) {
            case 'GET': $controller->getProductById($id); break;
            case 'PUT':
                $data = json_decode(file_get_contents("php://input"), true);
                if (!$data) Response::json(["status"=>"error","message"=>"Invalid JSON"],400);
                $controller->updateProduct($id,$data); break;
            case 'PATCH':
                $data = json_decode(file_get_contents("php://input"), true);
                if (!$data) Response::json(["status"=>"error","message"=>"Invalid JSON"],400);
                $controller->patchProduct($id,$data); break;
            case 'DELETE': $controller->deleteProduct($id); break;
            default: Response::json(["status"=>"error","message"=>"Method not allowed"],405);
        }
        exit;
    } else {
        Response::json(["status"=>"error","message"=>"Invalid URL format"],400);
    }
}

// Route ไม่ถูกต้อง
Response::json(["status"=>"error","message"=>"Route not found"],404);
