<?php
session_start();
session_unset();
session_destroy();

// Garante que o redirecionamento vai para o login e não para outro lado
header("Location: login.php");
exit();
?>