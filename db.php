<?php

$host = "mysql"; //"127.0.0.1";      // servidor (ou IP)
$dbname = "greenbuddydb";     // nome da base de dados
$username = "green";       // utilizador MySQL
$passn = "buddy";           // password (vazia em XAMPP)
/*
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password
    );

    // ativa erros (importante para debug)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Erro na ligação à base de dados: " . $e->getMessage());
}
*/
$conn = new mysqli($host, $username, $passn, $dbname);

// verificar ligação
if ($conn->connect_error) {
    die("Erro na ligação à base de dados: " . $conn->connect_error);
}

?>