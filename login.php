<?php
include_once("db.php");
session_start();

$mensagem = ""; 

function enviarCodigoVerificacao($email, $codigo) {
    $assunto = "Codigo de Verificacao - GreenBuddy";
    $corpo = "O teu codigo de verificacao e: " . $codigo;
    $headers = "From: no-reply@greenbuddy.com";
    return mail($email, $assunto, $corpo, $headers);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['btn_registo'])) {
        $codigo = rand(100000, 999999);
        $_SESSION['temp_registo'] = [
            'usuario' => $_POST['usuario'],
            'email' => $_POST['email'],
            'telemovel' => $_POST['telemovel'],
            'senha' => password_hash($_POST['senha'], PASSWORD_DEFAULT),
            'codigo' => $codigo
        ];

        if (enviarCodigoVerificacao($_POST['email'], $codigo)) {
            $mensagem = "success|Código enviado para o teu e-mail!";
            $_SESSION['aguardando_verificacao'] = true;
        } else {
            $mensagem = "error|Erro ao enviar e-mail.";
        }
    }

    if (isset($_POST['btn_verificar'])) {
        $codigo_inserido = $_POST['codigo_verificacao'];
        $dados = $_SESSION['temp_registo'];

        if ($codigo_inserido == $dados['codigo']) {
            $sql = "INSERT INTO utilizadores (username, senha, email, telemovel) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $dados['usuario'], $dados['senha'], $dados['email'], $dados['telemovel']);

            if ($stmt->execute()) {
                $mensagem = "success|Conta criada! Faz login.";
                unset($_SESSION['temp_registo'], $_SESSION['aguardando_verificacao']);
            }
        } else {
            $mensagem = "error|Código incorreto!";
            $_SESSION['aguardando_verificacao'] = true;
        }
    }

    if (isset($_POST['btn_login'])) {
        $user = $_POST['usuario'];
        $pass = $_POST['senha'];

        $sql = "SELECT id_utilizador, senha FROM utilizadores WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (password_verify($pass, $row['senha'])) {
                $_SESSION['user_id'] = $row['id_utilizador']; 
                $_SESSION['username'] = $user;
                header("Location: recebe.php");
                exit();
            } else { $mensagem = "error|Senha incorreta!"; }
        } else { $mensagem = "error|Utilizador inexistente!"; }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenBuddy - Acesso</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; justify-content: center; align-items: center; background: #f0f7f0; font-family: 'Segoe UI', sans-serif; padding: 20px; box-sizing: border-box;}
        .auth-card { background: white; padding: 30px 20px; border-radius: 25px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        .input-group { margin-bottom: 15px; text-align: left; }
        .input-group label { display: block; margin-bottom: 5px; color: #2d5a27; font-weight: 600; font-size: 0.85rem; }
        .input-group input { width: 100%; padding: 12px; border-radius: 12px; border: 1px solid #ddd; font-size: 1rem; box-sizing: border-box; }
        .btn-auth { background: #2d5a27; color: white; padding: 15px; width: 100%; border: none; border-radius: 15px; font-weight: bold; text-transform: uppercase; cursor: pointer; margin-top: 10px; }
        .toggle-link { display: block; margin-top: 20px; color: #2d5a27; font-size: 0.85rem; text-decoration: none; cursor: pointer; }
        .back-home { position: absolute; top: 20px; left: 20px; text-decoration: none; color: #2d5a27; font-weight: bold; }
        .hidden { display: none; }
        .alert { padding: 10px; border-radius: 10px; margin-bottom: 15px; font-size: 0.85rem; }
        .alert-error { background: #fee; color: #c33; }
        .alert-success { background: #efe; color: #383; }
    </style>
</head>
<body>
    <a href="index.php" class="back-home">← Sair</a>
    <div class="auth-card">
        <img src="img/logotipo_PAP.png" alt="Logo" style="height: 40px; margin-bottom: 10px;">
        <?php if ($mensagem !== ""): $parts = explode("|", $mensagem); ?>
            <div class="alert alert-<?php echo $parts[0]; ?>"><?php echo $parts[1]; ?></div>
        <?php endif; ?>

        <div id="login-section" <?php if(isset($_SESSION['aguardando_verificacao'])) echo 'class="hidden"'; ?>>
            <h2>Login</h2>
            <form action="login.php" method="POST">
                <div class="input-group"><label>Utilizador</label><input type="text" name="usuario" required></div>
                <div class="input-group"><label>Senha</label><input type="password" name="senha" required></div>
                <button type="submit" name="btn_login" class="btn-auth">Entrar</button>
            </form>
            <a class="toggle-link" onclick="toggleAuth('register')">Criar nova conta</a>
        </div>

        <div id="register-section" class="hidden">
            <h2>Registo</h2>
            <form action="login.php" method="POST">
                <div class="input-group"><label>Utilizador</label><input type="text" name="usuario" required></div>
                <div class="input-group"><label>E-mail</label><input type="email" name="email" required></div>
                <div class="input-group"><label>Telemóvel</label><input type="tel" name="telemovel" required></div>
                <div class="input-group"><label>Senha</label><input type="password" name="senha" required></div>
                <button type="submit" name="btn_registo" class="btn-auth">Enviar Código</button>
            </form>
            <a class="toggle-link" onclick="toggleAuth('login')">Já tenho conta</a>
        </div>
    </div>
    <script>
        function toggleAuth(target) {
            document.getElementById('login-section').classList.toggle('hidden', target === 'register');
            document.getElementById('register-section').classList.toggle('hidden', target === 'login');
        }
    </script>
</body>
</html>