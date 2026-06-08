<?php
include_once("db.php");
session_start();

// Importar as classes do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ajusta o caminho se não usares Composer (ex: 'PHPMailer/Exception.php')

$mensagem = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['btn_recuperar'])) {
        // Limpa espaços em branco e converte para minúsculas
        $email = strtolower(trim($_POST['email']));

        // 1. Verifica se o e-mail existe na base de dados
        $sql = "SELECT id_utilizador, username FROM utilizadores WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // 2. Gerar um Token único e definir expiração (1 hora)
            $token = bin2hex(random_bytes(32)); 
            $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));
            
            // 3. Guardar o token na base de dados para este utilizador
            $sql_update = "UPDATE utilizadores SET token_recuperacao = ?, token_expira = ? WHERE email = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("sss", $token, $expira, $email);
            
            if ($stmt_update->execute()) {
                
                // 4. Configurar e enviar o e-mail com o PHPMailer
                $mail = new PHPMailer(true);

                try {
                    // Configurações do Servidor de Envio (Gmail/SMTP)
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';             
                    $mail->SMTPAuth   = true;

                    $mail->Username   = 'greenbuddy.app.26@gmail.com'; 
                    $mail->Password   = 'rhgy ffla gysz hgav'; // Insere aqui os 16 caracteres gerados na Google
                    
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->CharSet    = 'UTF-8';

                    // Destinatários
                    $mail->setFrom('greenbuddy.app.26@gmail.com', 'GreenBuddy');
                    $mail->addAddress($email, $user['username']);

                    // Link de recuperação dinâmico
                    $link = "https://musical-space-robot-xrwggvxx94jx2vqv5-7878.app.github.dev/nova_senha.php?token=" . $token;

                    // Conteúdo do E-mail
                    $mail->isHTML(true);
                    $mail->Subject = 'GreenBuddy - Recuperação de Palavra-pass';
                    $mail->Body    = "
                        <h3>Olá, {$user['username']}!</h3>
                        <p>Recebemos um pedido para redefinir a tua palavra-pass no GreenBuddy.</p>
                        <p>Para escolheres uma nova palavra-pass, clica no link abaixo:</p>
                        <p><a href='{$link}' style='background:#2d5a27; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; display:inline-block;'>Redefinir Palavra-pass</a></p>
                        <br>
                        <p><small>Este link é válido por 1 hora. Se não pediste isto, podes ignorar este e-mail.</small></p>
                    ";

                    $mail->send();
                    $mensagem = "success|E-mail de recuperação enviado! Verifica a tua caixa de entrada.";
                    
                } catch (Exception $e) {
                    $mensagem = "error|O e-mail não pôde ser enviado. Erro: {$mail->ErrorInfo}";
                }
            } else {
                $mensagem = "error|Erro interno no servidor. Tente novamente.";
            }

        } else {
            $mensagem = "error|E-mail inválido! Procuraste por: '" . htmlspecialchars($email) . "'. Registos encontrados na BD: " . $result->num_rows;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>GreenBuddy - Recuperar Palavra-pass</title>
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

        .input-group { margin-bottom: 15px; text-align: left; }
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

        .alert { padding: 8px; border-radius: 8px; margin-bottom: 10px; font-size: 0.8rem; text-align: center; }
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

    <a href="login.php" class="back-home">← Voltar</a>

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
        
        <div id="recovery-section">
            <h2>Recuperar Senha</h2>
            <p>Introduz o teu e-mail para receberes as instruções de redefinição.</p>
            
            <form action="recuperar.php" method="POST">
                <div class="input-group">
                    <label>E-mail da Conta</label>
                    <input type="email" name="email" required placeholder="exemplo@email.com">
                </div>
                
                <button type="submit" name="btn_recuperar" class="btn-auth">Enviar Instruções</button>
            </form>
            
            <a class="toggle-link" href="login.php">Voltar ao Login</a>
        </div>
    </div>

</body>
</html>