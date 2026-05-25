<?php
include_once("db.php");
session_start();

$mensagem = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['btn_registo'])) {
        $user = $_POST['usuario'];
        $email = $_POST['email'];
        $tele = $_POST['telemovel'];
        $pass = password_hash($_POST['senha'], PASSWORD_DEFAULT);

        $sql = "INSERT INTO utilizadores (username, senha, email, telemovel) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $user, $pass, $email, $tele);

        if ($stmt->execute()) {
            $mensagem = "success|Registo feito com sucesso! Faz login agora.";
        } else {
            $mensagem = "error|Erro ao registar: " . $conn->error;
        }
    }

    if (isset($_POST['btn_login'])) {
        $user = $_POST['usuario'];
        $pass = $_POST['senha'];

        // ATUALIZADO: Busca também as colunas status e is_admin
        $sql = "SELECT id_utilizador, username, senha, status, is_admin FROM utilizadores WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            
            if (password_verify($pass, $row['senha'])) {
                
                // ATUALIZADO: Verifica primeiro se o utilizador está bloqueado
                if (isset($row['status']) && $row['status'] === 'bloqueado') {
                    $mensagem = "error|A sua conta encontra-se temporariamente bloqueada. Contacte o suporte.";
                } else {
                    $_SESSION['user_id'] = $row['id_utilizador']; 
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['is_admin'] = $row['is_admin'] ?? 0; // Guarda se é admin na sessão

                    // ATUALIZADO: Redirecionamento Inteligente com base no cargo
                    if ($_SESSION['is_admin'] == 1) {
                        header("Location: admin.php"); // Administrador vai para o painel de controlo
                    } else {
                        header("Location: ativacao.php"); // Utilizador comum vai para os vasos
                    }
                    exit();
                }
                
            } else {
                $mensagem = "error|Palavra-passe incorreta!";
            }
        } else {
            $mensagem = "error|Utilizador não encontrado!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>GreenBuddy - Acesso</title>
    <link rel="manifest" href="manifest.json">
    <style>
        body { 
            margin: 0; min-height: 100vh; display: flex;
            justify-content: center; align-items: center;
            background: #f0f7f0; font-family: 'Segoe UI', sans-serif;
            padding: 10px;
            overflow: hidden; 
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.7); 
            padding: 30px; 
            border-radius: 30px;
            backdrop-filter: blur(20px); 
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1); 
            width: 100%; 
            max-width: 380px;
            text-align: center; 
            position: relative;
        }

        .auth-card h2 { font-size: 1.8rem; color: #1b3d17; margin-bottom: 2px; }
        .auth-card p { font-size: 0.85rem; margin-bottom: 15px; color: #555; }

        .input-group { margin-bottom: 10px; text-align: left; }
        .input-group label { display: block; margin-bottom: 2px; color: #2d5a27; font-weight: 600; font-size: 0.75rem; }
        .input-group input { 
            width: 100%; padding: 10px; border-radius: 10px; border: 1px solid rgba(45, 90, 39, 0.15);
            background: rgba(255, 255, 255, 0.8); box-sizing: border-box; font-size: 0.9rem;
        }

        .btn-auth {
            background: linear-gradient(135deg, #2d5a27 0%, #4caf50 100%); color: white;
            padding: 12px; width: 100%; border: none; border-radius: 50px;
            font-weight: 800; text-transform: uppercase; cursor: pointer; font-size: 0.8rem;
            margin-top: 5px;
        }

        .toggle-link { display: block; margin-top: 15px; color: #2d5a27; text-decoration: none; font-weight: 600; font-size: 0.75rem; cursor: pointer; }
        
        .back-home { position: absolute; top: 15px; left: 15px; color: #2d5a27; text-decoration: none; font-weight: 700; font-size: 0.8rem; z-index: 10; }
        
        .hidden { display: none; }

        .alert { padding: 8px; border-radius: 8px; margin-bottom: 10px; font-size: 0.8rem; }
        .alert-success { background: #e8f5e9; color: #2e7d32; }
        .alert-error { background: #ffebee; color: #c62828; }

        @media (max-height: 700px) {
            .auth-card { padding: 20px; }
            .auth-card h2 { font-size: 1.4rem; }
            .auth-card img { height: 40px !important; }
            .input-group { margin-bottom: 8px; }
            .input-group input { padding: 8px; }
        }
    </style>
</head>
<body>

    <a href="index.php" id="seta-voltar" class="back-home">← Voltar</a>

    <div class="auth-card">
        <img src="img/logotipo_PAP.png" alt="Logo" style="height: 50px; margin-bottom: 5px;">

        <?php if ($mensagem !== ""): 
            $parts = explode("|", $mensagem); 
            $tipo = ($parts[0] == 'success') ? 'success' : 'error';
        ?>
            <div class="alert alert-<?php echo $tipo; ?>">
                <?php echo $parts[1]; ?>
            </div>
        <?php endif; ?>
        
        <div id="login-section" <?php if(isset($_POST['btn_registo'])) echo 'class="hidden"'; ?>>
            <h2>Login</h2>
            <p>Entra no teu painel.</p>
            <form action="login.php" method="POST">
                <div class="input-group">
                    <label>Utilizador</label>
                    <input type="text" name="usuario" required>
                </div>
                <div class="input-group">
                    <label>Palavra-passe</label>
                    <input type="password" name="senha" required>
                </div>
                <button type="submit" name="btn_login" class="btn-auth">Entrar</button>
            </form>
            <a class="toggle-link" onclick="mostrarRegisto()">Criar nova conta</a>
        </div>

        <div id="register-section" <?php if(!isset($_POST['btn_registo']) || (isset($parts) && $parts[0] == 'success')) echo 'class="hidden"'; ?>>
            <h2>Registo</h2>
            <p>Cria a tua conta.</p>
            <form action="login.php" method="POST">
                <div class="input-group">
                    <label>Utilizador</label>
                    <input type="text" name="usuario" required>
                </div>
                <div class="input-group">
                    <label>E-mail</label>
                    <input type="email" name="email" required>
                </div>
                <div class="input-group">
                    <label>Telemóvel</label>
                    <input type="tel" name="telemovel" required>
                </div>
                <div class="input-group">
                    <label>Palavra-passe</label>
                    <input type="password" name="senha" required>
                </div>
                <button type="submit" name="btn_registo" class="btn-auth">Registar</button>
            </form>
            <a class="toggle-link" href="login.php">Já tenho conta</a>
        </div>
    </div>

    <script>
        function mostrarRegisto() {
            document.getElementById('login-section').classList.add('hidden');
            document.getElementById('register-section').classList.remove('hidden');
            document.getElementById('seta-voltar').setAttribute('href', 'login.php');
        }

        window.onload = function() {
            var reg_section = document.getElementById('register-section');
            if (reg_section && !reg_section.classList.contains('hidden')) {
                document.getElementById('seta-voltar').setAttribute('href', 'login.php');
            }
        }
    </script>
</body>
</html>