<?php
// 1. LIGAÇÃO À BASE DE DADOS E LÓGICA DE PROCESSAMENTO
include_once("db.php");
session_start();

$mensagem = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // CENÁRIO A: PROCESSAR REGISTO
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

        /* CARD REDUZIDO */
        .auth-card {
            background: rgba(255, 255, 255, 0.7); 
            padding: 25px 35px; /* Padding reduzido */
            border-radius: 30px; /* Bordas ligeiramente menos circulares */
            backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 20px 40px rgba(0,0,0,0.08); 
            width: 90%; 
            max-width: 350px; /* Largura máxima reduzida de 420px para 350px */
            text-align: center; position: relative;
        }

        .auth-card h2 { font-size: 1.8rem; color: #1b3d17; margin: 10px 0 5px; letter-spacing: -1px; }
        .auth-card p { color: #555; margin-bottom: 15px; font-size: 0.85rem; }

        .input-group { margin-bottom: 12px; text-align: left; }
        .input-group label { display: block; margin-bottom: 4px; color: #2d5a27; font-weight: 600; font-size: 0.8rem; }
        .input-group input { 
            width: 100%; 
            padding: 10px 15px; /* Padding dos inputs reduzido */
            border-radius: 10px; border: 1px solid rgba(45, 90, 39, 0.15);
            background: rgba(255, 255, 255, 0.8); box-sizing: border-box; transition: 0.3s;
            font-size: 0.85rem;
        }
        .input-group input:focus { outline: none; border-color: #4caf50; transform: translateY(-1px); background: white; }

        .btn-auth {
            background: linear-gradient(135deg, #2d5a27 0%, #4caf50 100%); color: white;
            padding: 12px; width: 100%; border: none; border-radius: 50px;
            font-weight: 800; text-transform: uppercase; letter-spacing: 1px;
            font-size: 0.8rem;
            cursor: pointer; margin-top: 5px; transition: 0.4s;
        }
        .btn-auth:hover { transform: scale(1.02); box-shadow: 0 8px 15px rgba(45,90,39,0.15); }

        .toggle-link { display: block; margin-top: 15px; color: #2d5a27; text-decoration: none; font-weight: 600; font-size: 0.8rem; cursor: pointer; }
        .back-home { position: absolute; top: 20px; left: 20px; color: #2d5a27; text-decoration: none; font-weight: 700; font-size: 0.85rem; }
        .hidden { display: none; }

        .alert { padding: 8px; border-radius: 8px; margin-bottom: 12px; font-size: 0.8rem; font-weight: 600; }
        .alert-error { background: #ffebee; color: #c62828; }
        .alert-success { background: #e8f5e9; color: #2e7d32; }
    </style>
</head>
<body>

    <div class="leaf-bg" style="top: 10%; right: 10%; font-size: 2rem;">🌿</div>
    <div class="leaf-bg" style="bottom: 10%; left: 5%; font-size: 2.5rem; animation-delay: 3s;">🍃</div>

    <a href="index.php" class="back-home">← Voltar</a>

    <div class="auth-card">
        <img src="img/logotipo_PAP.png" alt="Logo" style="height: 50px; margin-bottom: 5px;">

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
                    <input type="text" name="usuario" placeholder="Username" required>
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
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(a => a.style.display = 'none');
        }
    </script>
</body>
</html>