<?php
// Deteta se existem as variáveis de ambiente do Railway, caso contrário usa o teu localhost
$host     = getenv('MYSQLHOST')     ?: "mysql"; 
$dbname   = getenv('MYSQLDATABASE') ?: "greenbuddydb";
$username = getenv('MYSQLUSER')     ?: "green";
$passsn   = getenv('MYSQLPASSWORD') ?: "buddy";
$port     = getenv('MYSQLPORT')     ?: "3306";

// Conexão utilizando mysqli (passando também a porta configurada)
$conn = new mysqli($host, $username, $passsn, $dbname, $port);

// Verificar a ligação
if ($conn->connect_error) {
    die("Erro na ligação à base de dados: " . $conn->connect_error);
}

// Configura o charset para evitar problemas com acentos e caracteres especiais
$conn->set_charset("utf8");
?>