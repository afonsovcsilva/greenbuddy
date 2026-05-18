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
    $sql = "UPDATE utilizadores SET mac_address = ?, nome_vaso = NULL WHERE id_utilizador = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $mac, $user_id);
    
    if ($stmt->execute()) {
        header("Location: ativacao.php");
        exit();
    } else {
        $mensagem = "Erro ao registar o dispositivo.";
    }
}

// Lógica para Editar o nome do vaso
if (isset($_POST['btn_editar_nome'])) {
    $novo_nome = $_POST['novo_nome'];
    
    $sql = "UPDATE utilizadores SET nome_vaso = ? WHERE id_utilizador = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $novo_nome, $user_id);
    
    if ($stmt->execute()) {
        header("Location: ativacao.php");
        exit();
    }
}

// Lógica para Apagar/Desvincular o vaso
if (isset($_POST['btn_remover_vaso'])) {
    $sql = "UPDATE utilizadores SET mac_address = NULL, nome_vaso = NULL WHERE id_utilizador = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        header("Location: ativacao.php");
        exit();
    }
}

// Consulta para verificar se o utilizador já tem um vaso
$sql_check = "SELECT mac_address, nome_vaso FROM utilizadores WHERE id_utilizador = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("i", $user_id);
$stmt_check->execute();
$result = $stmt_check->get_result();
$user_data = $result->fetch_assoc();

$tem_vaso = !empty(trim($user_data['mac_address'] ?? ""));
$nome_exibicao = !empty(trim($user_data['nome_vaso'] ?? "")) ? $user_data['nome_vaso'] : "Meu Vaso Principal";
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

        /* Header Atualizado para 3 elementos */
        .header {
            width: 100%;
            max-width: 800px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }

        /* Botão Novo de Sair */
        .btn-logout-back {
            text-decoration: none;
            color: #cc4444;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 10px 20px;
            background: rgba(204,68,68,0.1);
            border-radius: 50px;
            transition: 0.3s;
        }

        .btn-logout-back:hover {
            background: rgba(204,68,68,0.2);
        }

        .btn-add {
            background: #2d5a27;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-add:hover { background: #3e7a36; }

        .empty-state {
            margin-top: 100px;
            text-align: center;
            color: #888;
        }

        .devices-container {
            width: 90%;
            max-width: 550px;
            margin-top: 20px;
            position: relative;
        }

        .card-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            border: 2px solid transparent;
            transition: 0.3s;
        }

        .card-wrapper:hover {
            transform: translateY(-5px);
            border-color: #2d5a27;
        }

        .device-card {
            display: flex;
            align-items: center;
            padding: 20px;
            flex-grow: 1;
            text-decoration: none;
            color: inherit;
            cursor: pointer;
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

        .options-menu {
            position: relative;
            margin-right: 15px;
        }

        .btn-dots {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #888;
            cursor: pointer;
            padding: 10px;
            border-radius: 50px;
            line-height: 1;
        }

        .btn-dots:hover {
            background: #f0f0f0;
            color: #333;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 40px;
            background-color: white;
            min-width: 130px;
            box-shadow: 0px 8px 16px rgba(0,0,0,0.15);
            border-radius: 12px;
            z-index: 10;
            overflow: hidden;
            border: 1px solid #eee;
        }

        .dropdown-content button {
            width: 100%;
            background: none;
            border: none;
            padding: 12px 16px;
            text-align: left;
            font-size: 0.9rem;
            cursor: pointer;
            color: #333;
        }

        .dropdown-content button:hover {
            background-color: #f5f5f5;
        }

        .dropdown-content button.delete-option {
            color: #c62828;
        }

        .dropdown-content button.delete-option:hover {
            background-color: #ffebee;
        }

        .modal {
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
            box-sizing: border-box;
        }

        .modal-content input {
            width: 100%;
            padding: 12px;
            margin: 20px 0;
            border-radius: 10px;
            border: 1px solid #ddd;
            box-sizing: border-box;
            text-align: center;
            font-size: 1rem;
        }

        .btn-cancel {
            background: none;
            border: none;
            color: #888;
            cursor: pointer;
            margin-top: 15px;
            font-weight: 600;
        }

        .btn-danger {
            background: #c62828;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 50px;
            font-weight: bold;
            cursor: pointer;
        }
        .btn-danger:hover { background: #b71c1c; }
    </style>
</head>
<body>

    <div class="header">
        <a href="logout.php" class="btn-logout-back">Terminar Sessão</a>
        
        <img src="img/logotipo_PAP.png" style="height: 40px;">
        
        <button class="btn-add" onclick="openModal('modal-add')">+ Adicionar Vaso</button>
    </div>

    <?php if (!$tem_vaso): ?>
        <div class="empty-state">
            <div style="font-size: 60px;">🪴</div>
            <h2>Sem vasos conectados</h2>
            <p>Clica no botão superior para registar o teu primeiro vaso.</p>
        </div>
    <?php else: ?>
        <div class="devices-container">
            <div class="card-wrapper">
                <a href="recebe.php" class="device-card">
                    <div class="device-icon">🌿</div>
                    <div class="device-info">
                        <h3><?php echo htmlspecialchars($nome_exibicao); ?></h3>
                        <p>MAC: <?php echo htmlspecialchars($user_data['mac_address']); ?></p>
                    </div>
                </a>

                <div class="options-menu">
                    <button class="btn-dots" onclick="toggleDropdown(event)">⋮</button>
                    <div id="myDropdown" class="dropdown-content">
                        <button onclick="openModal('modal-edit')">✏️ Editar Nome</button>
                        <button class="delete-option" onclick="openModal('modal-delete')">🗑️ Apagar Vaso</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div id="modal-add" class="modal">
        <div class="modal-content">
            <h3>Novo Dispositivo</h3>
            <p>Insere o código do teu vaso</p>
            <form method="POST">
                <input type="text" name="mac_address" placeholder="00:00:00:00:00:00" required>
                <button type="submit" name="btn_ativar" class="btn-add" style="width: 100%; padding: 12px; border-radius: 50px;">Vincular Agora</button>
            </form>
            <button class="btn-cancel" onclick="closeModal('modal-add')">Cancelar</button>
        </div>
    </div>

    <div id="modal-edit" class="modal">
        <div class="modal-content">
            <h3>Editar Nome do Vaso</h3>
            <p>Escolhe um nome para identificar o teu vaso</p>
            <form method="POST">
                <input type="text" name="novo_nome" value="<?php echo htmlspecialchars($nome_exibicao); ?>" required maxlength="50">
                <button type="submit" name="btn_editar_nome" class="btn-add" style="width: 100%; padding: 12px; border-radius: 50px;">Guardar Nome</button>
            </form>
            <button class="btn-cancel" onclick="closeModal('modal-edit')">Cancelar</button>
        </div>
    </div>

    <div id="modal-delete" class="modal">
        <div class="modal-content">
            <div style="font-size: 40px; margin-bottom: 10px;">⚠️</div>
            <h3>Apagar Dispositivo?</h3>
            <p>Tens a certeza que queres desvincular este vaso? Terás de introduzir o código novamente se quiseres voltar a aceder.</p>
            <form method="POST">
                <button type="submit" name="btn_remover_vaso" class="btn-danger">Sim, Eliminar</button>
            </form>
            <button class="btn-cancel" onclick="closeModal('modal-delete')">Cancelar</button>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
            var dropdown = document.getElementById("myDropdown");
            if(dropdown) dropdown.style.display = "none";
        }
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        function toggleDropdown(event) {
            event.stopPropagation();
            var dropdown = document.getElementById("myDropdown");
            if (dropdown.style.display === "block") {
                dropdown.style.display = "none";
            } else {
                dropdown.style.display = "block";
            }
        }

        window.onclick = function(event) {
            if (!event.target.matches('.btn-dots')) {
                var dropdown = document.getElementById("myDropdown");
                if (dropdown && dropdown.style.display === "block") {
                    dropdown.style.display = "none";
                }
            }
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>

</body>
</html>