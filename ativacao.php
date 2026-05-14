<?php
include_once("db.php");
session_start();

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

$user_id = $_SESSION['user_id'];
$mensagem = "";

// Lógica para adicionar novo vaso
if (isset($_POST['btn_ativar'])) {
    $mac = $_POST['mac_address'];
    
    // Atualiza o MAC address do utilizador
    $sql = "UPDATE utilizadores SET mac_address = ? WHERE id_utilizador = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $mac, $user_id);
    
    if ($stmt->execute()) {
        // Recarrega a página para mostrar o novo vaso
        header("Location: ativacao.php");
        exit();
    } else {
        $mensagem = "Erro ao registar o dispositivo.";
    }
}

// Consulta para verificar se o utilizador já tem um vaso
$sql_check = "SELECT mac_address FROM utilizadores WHERE id_utilizador = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("i", $user_id);
$stmt_check->execute();
$result = $stmt_check->get_result();
$user_data = $result->fetch_assoc();

$tem_vaso = !empty(trim($user_data['mac_address'] ?? ""));
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenBuddy - Meus Vasos</title>
    <style>
        body { 
            background: #f0f7f0; 
            font-family: 'Segoe UI', sans-serif; 
            margin: 0; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            min-height: 100vh; 
        }

        /* Header e Botão Adicionar */
        .header {
            width: 100%;
            max-width: 800px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .btn-add {
            background: #2d5a27;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            transition: 0.3s;
        }

        .btn-add:hover { background: #3e7a36; }

        /* Estado Vazio */
        .empty-state {
            margin-top: 100px;
            text-align: center;
            color: #888;
        }

        .empty-state i { font-size: 50px; display: block; margin-bottom: 10px; }

        /* Card do Vaso */
        .devices-container {
            width: 90%;
            max-width: 500px;
            margin-top: 20px;
        }

        .device-card {
            background: white;
            padding: 20px;
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: 0.3s;
            border: 2px solid transparent;
            text-decoration: none;
            color: inherit;
        }

        .device-card:hover {
            transform: translateY(-5px);
            border-color: #2d5a27;
        }

        .device-icon {
            background: #e8f5e9;
            width: 50px;
            height: 50px;
            border-radius: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 15px;
            font-size: 24px;
        }

        .device-info h3 { margin: 0; color: #2d5a27; }
        .device-info p { margin: 2px 0 0; font-size: 0.8rem; color: #777; }

        /* Modal (Formulário Oculto) */
        #modal-add {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 100;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 30px;
            text-align: center;
            width: 90%;
            max-width: 350px;
        }

        .modal-content input {
            width: 100%;
            padding: 12px;
            margin: 20px 0;
            border-radius: 10px;
            border: 1px solid #ddd;
            box-sizing: border-box;
            text-align: center;
        }

        .btn-cancel {
            background: none;
            border: none;
            color: #888;
            cursor: pointer;
            margin-top: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <img src="img/logotipo_PAP.png" style="height: 40px;">
        <button class="btn-add" onclick="openModal()">+ Adicionar Vaso</button>
    </div>

    <?php if (!$tem_vaso): ?>
        <div class="empty-state">
            <div style="font-size: 60px;">🪴</div>
            <h2>Sem vasos conectados</h2>
            <p>Clica no botão superior para registar o teu primeiro vaso.</p>
        </div>
    <?php else: ?>
        <div class="devices-container">
            <a href="recebe.php" class="device-card">
                <div class="device-icon">🌿</div>
                <div class="device-info">
                    <h3>Meu Vaso Principal</h3>
                    <p>MAC: <?php echo $user_data['mac_address']; ?></p>
                </div>
            </a>
        </div>
    <?php endif; ?>

    <div id="modal-add">
        <div class="modal-content">
            <h3>Novo Dispositivo</h3>
            <p>Insere o código MAC do teu vaso</p>
            <form method="POST">
                <input type="text" name="mac_address" placeholder="00:00:00:00:00:00" required>
                <button type="submit" name="btn_ativar" class="btn-add" style="width: 100%;">Vincular Agora</button>
            </form>
            <button class="btn-cancel" onclick="closeModal()">Cancelar</button>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('modal-add').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('modal-add').style.display = 'none';
        }

        // Fecha o modal se clicar fora dele
        window.onclick = function(event) {
            var modal = document.getElementById('modal-add');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>

</body>
</html>