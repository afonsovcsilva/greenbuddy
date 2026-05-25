<?php
require "db.php";
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "desconectado"]);
    exit();
}

// Procura o estado atual do utilizador na Base de Dados
$stmt = $conn->prepare("SELECT status FROM utilizadores WHERE id_utilizador = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$resultado = $stmt->get_result()->fetch_assoc();

if ($resultado && $resultado['status'] === 'bloqueado') {
    echo json_encode(["status" => "bloqueado"]);
} else {
    echo json_encode(["status" => "ativo"]);
}
exit();