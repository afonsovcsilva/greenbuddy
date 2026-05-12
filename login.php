<?php
// 1. LIGAÇÃO À BASE DE DADOS E LÓGICA DE PROCESSAMENTO
include_once("db.php");
session_start();

$mensagem = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // CENÁRIO A: PROCESSAR REGISTO
    if (isset($_POST['btn_registo'])) {
        $user = $_POST['usuario']; // Username vindo do formulário
        $email = $_POST['email'];
        $tele = $_POST['telemovel'];
        $pass = password_hash($_POST['senha'], PASSWORD_DEFAULT);

        // Query ajustada para a estrutura da tua tabela
        $sql = "INSERT INTO utilizadores (username, senha, email, telemovel) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $user, $pass, $email, $tele);

        if ($stmt->execute()) {
            $mensagem = "success|Registo feito com sucesso! Faz login agora.";
        } else {
            $mensagem = "error|Erro ao registar: " . $conn->error;
        }
    }

    // CENÁRIO B: PROCESSAR LOGIN
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
                // Sessão compatível com o teu recebe.php
                $_SESSION['user_id'] = $row['id_utilizador']; 
                $_SESSION['username'] = $user;
                
                header("Location: recebe.php");
                exit();
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenBuddy - Acesso</title>
    <style>
        body { 
            margin: 0; min-height: 100vh; display: flex;
            justify-content: center; align-items: center;
            overflow: hidden; background: #f0f7f0; font-family: 'Segoe UI', sans-serif;
        }

        .leaf-bg { position: fixed; z-index: -1; opacity: 0.2; filter: blur(1px); animation: floatLeaf 8s infinite ease-in-out; }
        @keyframes floatLeaf { 0%, 100% { transform: translateY(0) rotate(0deg); } 50% { transform: translateY(-30px) rotate(10deg); } }

        .auth-card {
            background: rgba(255, 255, 255, 0.7); padding: 40px; border-radius: 40px;
            backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 30px 60px rgba(0,0,0,0.1); width: 100%; max-width: 420px;
            text-align: center; position: relative;
        }

        .auth-card h2 { font-size: 2.2rem; color: #1b3d17; margin-bottom: 5px; letter-spacing: -1.5px; }
        .auth-card p { color: #555; margin-bottom: 20px; font-size: 0.9rem; }

        .input-group { margin-bottom: 15px; text-align: left; }
        .input-group label { display: block; margin-bottom: 5px; color: #2d5a27; font-weight: 600; font-size: 0.85rem; }
        .input-group input { 
            width: 100%; padding: 12px 18px; border-radius: 12px; border: 1px solid rgba(45, 90, 39, 0.15);
            background: rgba(255, 255, 255, 0.8); box-sizing: border-box; transition: 0.3s;
        }
        .input-group input:focus { outline: none; border-color: #4caf50; transform: translateY(-2px); background: white; }

        .btn-auth {
            background: linear-gradient(135deg, #2d5a27 0%, #4caf50 100%); color: white;
            padding: 14px; width: 100%; border: none; border-radius: 50px;
            font-weight: 800; text-transform: uppercase; letter-spacing: 1px;
            cursor: pointer; margin-top: 10px; transition: 0.4s;
        }
        .btn-auth:hover { transform: scale(1.03); box-shadow: 0 10px 20px rgba(45,90,39,0.2); }

        .toggle-link { display: block; margin-top: 20px; color: #2d5a27; text-decoration: none; font-weight: 600; font-size: 0.85rem; cursor: pointer; }
        .back-home { position: absolute; top: 30px; left: 30px; color: #2d5a27; text-decoration: none; font-weight: 700; }
        .hidden { display: none; }

        .alert { padding: 10px; border-radius: 10px; margin-bottom: 15px; font-size: 0.9rem; font-weight: 600; }
        .alert-error { background: #ffebee; color: #c62828; }
        .alert-success { background: #e8f5e9; color: #2e7d32; }
    </style>
</head>
<body>

    <div class="leaf-bg" style="top: 10%; right: 10%; font-size: 2rem;">🌿</div>
    <div class="leaf-bg" style="bottom: 10%; left: 5%; font-size: 2.5rem; animation-delay: 3s;">🍃</div>

    <a href="index.php" class="back-home">← Voltar</a>

    <div class="auth-card">
        <img src="img/logotipo_PAP.png" alt="Logo" style="height: 60px; margin-bottom: 10px;">

        <?php if ($mensagem !== ""): 
            $parts = explode("|", $mensagem); ?>
            <div class="alert alert-<?php echo $parts[0]; ?>">
                <?php echo $parts[1]; ?>
            </div>
        <?php endif; ?>
        
        <div id="login-section" <?php if(isset($_POST['btn_registo'])) echo 'class="hidden"'; ?>>
            <h2>Login</h2>
            <p>Bem-vindo ao teu ecossistema.</p>
            <form action="login.php" method="POST">
                <div class="input-group">
                    <label>Utilizador</label>
                    <input type="text" name="usuario" placeholder="nome do utilizador" required>
                </div>
                <div class="input-group">
                    <label>Palavra-passe</label>
                    <input type="password" name="senha" placeholder="••••••••" required>
                </div>
                <button type="submit" name="btn_login" class="btn-auth">Entrar</button>
            </form>
            <a class="toggle-link" onclick="toggleAuth()">Ainda não tens conta? Regista-te</a>
        </div>

        <div id="register-section" <?php if(!isset($_POST['btn_registo']) || (isset($parts) && $parts[0] == 'success')) echo 'class="hidden"'; ?>>
            <h2>Registo</h2>
            <p>Começa a cuidar das tuas plantas.</p>
            <form action="login.php" method="POST">
                <div class="input-group">
                    <label>Utilizador</label>
                    <input type="text" name="usuario" placeholder="Como te queres chamar" required>
                </div>
                <div class="input-group">
                    <label>E-mail</label>
                    <input type="email" name="email" placeholder="exemplo@gmail.com" required>
                </div>
                <div class="input-group">
                    <label>Telemóvel</label>
                    <input type="tel" name="telemovel" placeholder="9xxxxxxxx" required>
                </div>
                <div class="input-group">
                    <label>Palavra-passe</label>
                    <input type="password" name="senha" placeholder="Cria uma senha" required>
                </div>
                <button type="submit" name="btn_registo" class="btn-auth">Criar Conta</button>
            </form>
            <a class="toggle-link" onclick="toggleAuth()">Já tens conta? Faz login</a>
        </div>
    </div>

    <script>
        function toggleAuth() {
            document.getElementById('login-section').classList.toggle('hidden');
            document.getElementById('register-section').classList.toggle('hidden');
            // Esconde alertas ao trocar de formulário
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(a => a.style.display = 'none');
        }
    </script>
</body>
</html>