<?php
require "db.php"; 
session_start();

$erro = "";
$modo = $_GET['modo'] ?? 'login';

// --- LÓGICA DE LOGIN ---
if (isset($_POST['btn-login'])) {
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $pass = $_POST['senha'];

    $stmt = $conn->prepare("SELECT id_utilizador, username, senha FROM utilizadores WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($pass, $row['senha'])) {
            $_SESSION['user_id'] = $row['id_utilizador'];
            $_SESSION['username'] = $row['username'];
            header("Location: recebe.php");
            exit;
        } else {
            $erro = "Senha incorreta!";
        }
    } else {
        $erro = "Utilizador não encontrado!";
    }
}

// --- LÓGICA DE REGISTO ---
if (isset($_POST['btn-registar'])) {
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $pass = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $mail = mysqli_real_escape_string($conn, $_POST['email']);
    $tel  = mysqli_real_escape_string($conn, $_POST['telemovel']);

    $stmt = $conn->prepare("INSERT INTO utilizadores (username, senha, email, telemovel) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $user, $pass, $mail, $tel);
    
    if ($stmt->execute()) {
        header("Location: login.php?modo=login&sucesso=1");
        exit;
    } else {
        $erro = "Erro ao registar: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenBuddy - Login</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            /* Mesmo degradê da index para consistência */
            background: linear-gradient(135deg, #f0f4f1 0%, #d9e8d1 100%);
            margin: 0; 
            font-family: 'Segoe UI', Roboto, sans-serif; 
        }

        .auth-card { 
            background: rgba(255, 255, 255, 0.7); /* Efeito de vidro */
            backdrop-filter: blur(15px);
            padding: 2.5rem; 
            border-radius: 30px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.1); 
            width: 100%; 
            max-width: 400px; 
            text-align: center; 
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        .logo-login img { 
            max-width: 160px; 
            margin-bottom: 1rem;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.05));
        }

        h2 { color: #1b3d17; margin-bottom: 1.5rem; font-weight: 700; }

        .input-group { text-align: left; margin-bottom: 20px; }
        .input-group label { 
            display: block; 
            font-size: 0.9rem; 
            color: #2d5a27; 
            margin-bottom: 8px; 
            font-weight: 600;
        }

        input { 
            width: 100%; 
            padding: 14px; 
            border: 1px solid rgba(0,0,0,0.1); 
            border-radius: 12px; 
            box-sizing: border-box; 
            font-size: 1rem; 
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: #2d5a27;
            box-shadow: 0 0 0 4px rgba(45, 90, 39, 0.1);
        }

        .btn { 
            width: 100%; 
            padding: 15px; 
            background: #2d5a27; 
            color: white; 
            border: none; 
            border-radius: 12px; 
            cursor: pointer; 
            font-weight: bold; 
            font-size: 1rem; 
            transition: all 0.3s ease; 
            margin-top: 10px; 
            box-shadow: 0 4px 12px rgba(45, 90, 39, 0.2);
        }

        .btn:hover { 
            background: #3e7a36; 
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(45, 90, 39, 0.3);
        }

        .btn-voltar { 
            display: inline-block;
            width: 100%; 
            padding: 12px; 
            background: transparent; 
            color: #666; 
            text-decoration: none; 
            border-radius: 12px; 
            font-weight: 600; 
            font-size: 0.9rem; 
            margin-top: 15px;
            transition: 0.3s;
            border: 1px solid #ddd;
        }

        .btn-voltar:hover { 
            background: rgba(0,0,0,0.05); 
            color: #333;
        }

        .erro-msg { color: #dc3545; background: #f8d7da; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.85rem; border: 1px solid #f5c6cb; }
        .sucesso-msg { color: #155724; background: #d4edda; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.85rem; border: 1px solid #c3e6cb; }
        
        .toggle-link { 
            display: block; 
            margin-top: 20px; 
            font-size: 0.9rem; 
            color: #2d5a27; 
            text-decoration: none; 
            font-weight: bold;
        }
        
        .toggle-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="logo-login">
        <img src="img/logotipo_PAP.png" alt="GreenBuddy">
    </div>

    <?php 
        if($erro) echo "<div class='erro-msg'>$erro</div>"; 
        if(isset($_GET['sucesso'])) echo "<div class='sucesso-msg'>Conta criada com sucesso! Faça login.</div>";
    ?>

    <?php if ($modo == 'login'): ?>
        <h2>Bem-Vindo</h2>
        <form method="POST">
            <div class="input-group">
                <label>Utilizador</label>
                <input type="text" name="username" placeholder="seu username" required>
            </div>
            <div class="input-group">
                <label>Senha</label>
                <input type="password" name="senha" placeholder="sua senha" required>
            </div>
            <button type="submit" name="btn-login" class="btn">Entrar na Dashboard</button>
            <a href="index.php" class="btn-voltar">← Voltar à Loja</a>
            <a href="login.php?modo=registo" class="toggle-link">Não tens conta? Criar agora</a>
        </form>
    <?php else: ?>
        <h2>Registar GreenBuddy</h2>
        <form method="POST">
            <div class="input-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Como te queres chamar?" required>
            </div>
            <div class="input-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="exemplo@email.com" required>
            </div>
            <div class="input-group">
                <label>Telemóvel</label>
                <input type="text" name="telemovel" placeholder="9xxxxxxxx" required>
            </div>
            <div class="input-group">
                <label>Senha</label>
                <input type="password" name="senha" placeholder="Escolhe uma senha forte" required>
            </div>
            <button type="submit" name="btn-registar" class="btn">Confirmar Registo</button>
            <a href="index.php" class="btn-voltar">← Cancelar</a>
            <a href="login.php?modo=login" class="toggle-link">Já tens conta? Fazer Login</a>
        </form>
    <?php endif; ?>
</div>

</body>
</html>