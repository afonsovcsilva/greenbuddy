<?php
require "db.php";
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'logoff']);
    exit();
}

$id_utilizador = $_SESSION['user_id'];
$id_vaso = isset($_GET['id_vaso']) ? intval($_GET['id_vaso']) : 0;

// 1. Verifica se a conta do utilizador foi bloqueada
$stmt = $conn->prepare("SELECT status FROM utilizadores WHERE id_utilizador = ?");
$stmt->bind_param("i", $id_utilizador);
$stmt->execute();
$res_user = $stmt->get_result()->fetch_assoc();

if (!$res_user || $res_user['status'] === 'bloqueado') {
    echo json_encode(['status' => 'conta_bloqueada']);
    exit();
}

// 2. Se estiver numa página de um vaso específico, verifica o estado do vaso
if ($id_vaso > 0) {
    $stmt_vaso = $conn->prepare("SELECT status_vaso FROM vasos WHERE id_vaso = ? AND id_utilizador = ?");
    $stmt_vaso->bind_param("ii", $id_vaso, $id_utilizador);
    $stmt_vaso->execute();
    $res_vaso = $stmt_vaso->get_result()->fetch_assoc();

    if ($res_vaso && $res_vaso['status_vaso'] === 'desativado') {
        echo json_encode(['status' => 'vaso_desativado']);
        exit();
    }
}

echo json_encode(['status' => 'ok']);
exit();