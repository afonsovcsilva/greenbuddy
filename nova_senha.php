<?php
include_once("db.php");
session_start();

$mensagem = "";
$token_valido = false;
$id_utilizador = null;

// 1. Verificar se o token foi passado no URL
if (isset($_GET['token'])) {
    $token = trim($_GET['token']);
    $agora = date("Y-m-d H:i:s");

    // Verificar se o token existe e se ainda é válido (data de expiração superior a "agora")
    $sql = "SELECT id_utilizador FROM utilizadores WHERE token_recuperacao = ? AND token_expira > ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $token, $agora);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $id_utilizador = $user['id_utilizador'];
        $token_valido = true;
    } else {
        $mensagem = "error|Este link de recuperação é inválido ou já expirou. Por favor, peça um novo link.";
    }
} else {
    $mensagem = "error|Acesso inválido. Nenhum token foi fornecido.";
}

// 2. Processar o formulário quando o utilizador define a nova senha
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn_atualizar'])) {
    $nova_senha = $_POST['nova_senha'];
    $conf_senha = $_POST['conf_senha'];
    $id_utilizador = $_POST['id_utilizador'];
    $token_atual = $_POST['token_atual'];

    // Validar se as duas senhas coincidem
    if ($nova_senha !== $conf_senha) {
        $mensagem = "error|As palavras-pass não coincidem!";
        $token_valido = true; // Mantém o formulário aberto para tentar de novo
    } else if (strlen($nova_senha) < 6) { // Validação simples de tamanho mínimo
        $mensagem = "error|A nova palavra-pass deve tener pelo menos 6 caracteres.";
        $token_valido = true;
    } else {
        // Encriptar a nova palavra-pass por segurança
        $senha_encriptada = password_hash($nova_senha, PASSWORD_DEFAULT);

        $sql_update = "UPDATE utilizadores SET senha = ?, token_recuperacao = NULL, token_expira = NULL WHERE id_utilizador = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $senha_encriptada, $id_utilizador);

        if ($stmt_update->execute()) {
            $mensagem = "success|Palavra-pass atualizada com sucesso! Já podes fazer login.";
            $token_valido = false; // Fecha o formulário, pois já foi concluído
        } else {
            $mensagem = "error|Erro ao atualizar a palavra-pass. Tenta novamente.";
            $token_valido = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>GreenBuddy - Nova Palavra-pass</title>
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

        .alert { padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 0.85rem; text-align: center; line-height: 1.4; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .alert-error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }

        @media (max-height: 700px) {
            .auth-card { padding: 20px; }
            .auth-card h2 { font-size: 1.4rem; }
            .input-group { margin-bottom: 8px; }
            .input-group input { padding: 8px; }
        }
    </style>
</head>
<body>

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
        
        <?php if ($token_valido): ?>
            <h2>Nova Palavra-pass</h2>
            <p>Escolha a sua nova palavra-pass de acesso ao GreenBuddy.</p>
            
            <form action="nova_senha.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
                <input type="hidden" name="id_utilizador" value="<?php echo $id_utilizador; ?>">
                <input type="hidden" name="token_atual" value="<?php echo htmlspecialchars($token); ?>">

                <div class="input-group">
                    <label>Nova Palavra-pass</label>
                    <input type="password" name="nova_senha" required placeholder="Mínimo 6 caracteres">
                </div>

                <div class="input-group">
                    <label>Confirmar Nova Palavra-pass</label>
                    <input type="password" name="conf_senha" required placeholder="Repita a palavra-pass">
                </div>
                
                <button type="submit" name="btn_atualizar" class="btn-auth">Atualizar Palavra-pass</button>
            </form>
        <?php else: ?>
            <a class="toggle-link" href="login.php" style="font-size: 0.9rem; margin-top: 20px;">Ir para a página de Login</a>
        <?php endif; ?>
    </div>

</body>
</html>