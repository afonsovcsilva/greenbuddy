<?php
session_start();
session_unset();
session_destroy();
header("Location: login.php"); // Altera para index.php ou login.php conforme o teu projeto
exit;
?>