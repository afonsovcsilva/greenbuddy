<?php
include_once("db.php");
session_start();

$mensagem = ""; 

// --- LÓGICA DE ENVIO DE E-MAIL (Simples) ---
function enviarCodigoVerificacao($email, $codigo) {
    $assunto = "Codigo de Verificacao - GreenBuddy";
    $corpo = "O teu codigo de verificacao e: " . $codigo;
    $headers = "From: no-reply@greenbuddy.com";
    return mail($email, $assunto, $corpo, $headers);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. SOLICITAR REGISTO (Envia Código)
    if (isset($_POST['btn_registo'])) {
        $codigo = rand(100000, 999999);
        
        // Guardamos os dados na sessão para usar depois da verificação
        $_SESSION['temp_registo'] = [
            'usuario' => $_POST['usuario'],
            'email' => $_POST['email'],
            'telemovel' => $_POST['telemovel'],
            'senha' => password_hash($_POST['senha'], PASSWORD_DEFAULT),
            'codigo' => $codigo
        ];

        // Tenta enviar o email
        if (enviarCodigoVerificacao($_POST['email'], $codigo)) {
            $mensagem = "success|Código enviado para o teu e-mail!";
            $_SESSION['aguardando_verificacao'] = true;
        } else {
            $mensagem = "error|Erro ao enviar e-mail. Verifica as configurações do servidor.";
        }
    }

    // 2. CONFIRMAR CÓDIGO E GRAVAR NA BD
    if (isset($_POST['btn_verificar'])) {
        $codigo_inserido = $_POST['codigo_verificacao'];
        $dados = $_SESSION['temp_registo'];

        if ($codigo_inserido == $dados['codigo']) {
            $sql = "INSERT INTO utilizadores (username, senha, email, telemovel) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $dados['usuario'], $dados['senha'], $dados['email'], $dados['telemovel']);

            if ($stmt->execute()) {
                $mensagem = "success|Conta criada com sucesso! Já podes fazer login.";
                unset($_SESSION['temp_registo'], $_SESSION['aguardando_verificacao']);
            } else {
                $mensagem = "error|Erro ao gravar no banco: " . $conn->error;
            }
        } else {
            $mensagem = "error|Código incorreto!";
            $_SESSION['aguardando_verificacao'] = true; // Mantém no ecrã de código
        }
    }

    // 3. LOGIN
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
            } else { $mensagem = "error|Palavra-passe incorreta!"; }
        } else { $mensagem = "error|Utilizador não encontrado!"; }
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
        body { margin: 0; min-height: 100vh; display: flex; justify-content: center; align-items: center; overflow: hidden; background: #f0f7f0; font-family: 'Segoe UI', sans-serif; }
        .leaf-bg { position: fixed; z-index: -1; opacity: 0.2; filter: blur(1px); animation: floatLeaf 8s infinite ease-in-out; }
        @keyframes floatLeaf { 0%, 100% { transform: translateY(0) rotate(0deg); } 50% { transform: translateY(-30px) rotate(10deg); } }
        .auth-card { background: rgba(255, 255, 255, 0.7); padding: 25px 35px; border-radius: 30px; backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.6); box-shadow: 0 20px 40px rgba(0,0,0,0.08); width: 90%; max-width: 350px; text-align: center; position: relative; }
        .auth-card h2 { font-size: 1.8rem; color: #1b3d17; margin: 10px 0 5px; letter-spacing: -1px; }
        .auth-card p { color: #555; margin-bottom: 15px; font-size: 0.85rem; }
        .input-group { margin-bottom: 12px; text-align: left; }
        .input-group label { display: block; margin-bottom: 4px; color: #2d5a27; font-weight: 600; font-size: 0.8rem; }
        .input-group input { width: 100%; padding: 10px 15px; border-radius: 10px; border: 1px solid rgba(45, 90, 39, 0.15); background: rgba(255, 255, 255, 0.8); box-sizing: border-box; transition: 0.3s; font-size: 0.85rem; }
        .btn-auth { background: linear-gradient(135deg, #2d5a27 0%, #4caf50 100%); color: white; padding: 12px; width: 100%; border: none; border-radius: 50px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; font-size: 0.8rem; cursor: pointer; margin-top: 5px; transition: 0.4s; }
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

        <div id="verify-section" <?php if(!isset($_SESSION['aguardando_verificacao'])) echo 'class="hidden"'; ?>>
            <h2>Verificar E-mail</h2>
            <p>Introduz o código que enviamos para o teu e-mail.</p>
            <form action="login.php" method="POST">
                <div class="input-group">
                    <label>Código de 6 dígitos</label>
                    <input type="text" name="codigo_verificacao" placeholder="000000" required>
                </div>
                <button type="submit" name="btn_verificar" class="btn-auth">Verificar Código</button>
            </form>
            <a class="toggle-link" href="login.php">Cancelar</a>
        </div>
        
        <div id="login-section" <?php if(isset($_POST['btn_registo']) || isset($_SESSION['aguardando_verificacao'])) echo 'class="hidden"'; ?>>
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
            <a class="toggle-link" onclick="toggleAuth('register')">Ainda não tens conta? Regista-te</a>
        </div>

        <div id="register-section" class="hidden">
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
                <button type="submit" name="btn_registo" class="btn-auth">Enviar Código</button>
            </form>
            <a class="toggle-link" onclick="toggleAuth('login')">Já tens conta? Faz login</a>
        </div>
    </div>

    <script>
        function toggleAuth(target) {
            const login = document.getElementById('login-section');
            const register = document.getElementById('register-section');
            const verify = document.getElementById('verify-section');
            const alerts = document.querySelectorAll('.alert');

            alerts.forEach(a => a.style.display = 'none');
            verify.classList.add('hidden');

            if(target === 'register') {
                login.classList.add('hidden');
                register.classList.remove('hidden');
            } else {
                login.classList.remove('hidden');
                register.classList.add('hidden');
            }
        }
    </script>
</body>
</html>