<?php
// Desativa a exibição de avisos simples (Notices) do servidor,
// mas mantém os erros graves ativos caso algo falhe criticamente.
ini_set('display_errors', 0); 
error_reporting(E_ALL & ~E_NOTICE);

include_once("db.php");
session_start();

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

$user_id = $_SESSION['user_id'];

// =========================================================================
// BLOCO DE DIAGNÓSTICO E VERIFICAÇÃO EM TEMPO REAL
// =========================================================================
$sql_check_deleted = "SELECT * FROM utilizadores WHERE id_utilizador = ?";
$stmt_check_deleted = $conn->prepare($sql_check_deleted);
$stmt_check_deleted->bind_param("i", $user_id);
$stmt_check_deleted->execute();
$res_check_deleted = $stmt_check_deleted->get_result();

if ($res_check_deleted->num_rows === 0) {
    // A conta REALMENTE não existe na BD (foi apagada)
    if (isset($_GET['ajax_check']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'apagado']);
        exit();
    }
    session_destroy();
    header("Location: login.php?erro=conta_eliminada"); 
    exit(); 
} else {
    // A conta EXISTE. Vamos ler os dados dela para verificar possíveis colunas de bloqueio
    $dados_conta = $res_check_deleted->fetch_assoc();
    
    $esta_bloqueado = false;
    if (isset($dados_conta['status']) && $dados_conta['status'] == 0) $esta_bloqueado = true;
    if (isset($dados_conta['bloqueado']) && $dados_conta['bloqueado'] == 1) $esta_bloqueado = true;
    if (isset($dados_conta['ativo']) && $dados_conta['ativo'] == 0) $esta_bloqueado = true;

    if ($esta_bloqueado) {
        if (isset($_GET['ajax_check']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'apagado']);
            exit();
        }
        session_destroy();
        header("Location: login.php?erro=conta_eliminada"); 
        exit();
    }
}

// Resposta padrão para o JavaScript se a conta estiver ativa
if (isset($_GET['ajax_check'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'ativo',
        'debug_info' => [
            'id' => $dados_conta['id_utilizador'],
            'username' => $dados_conta['username']
        ]
    ]);
    exit();
}
// =========================================================================

$mensagem = "";
$mensagem_perfil = "";
$sucesso_perfil = false;
$abrir_modal_erro = false;

// --- LÓGICA DA ÁREA DO UTILIZADOR (ATUALIZAÇÃO DE PERFIL) ---
if (isset($_POST['btn_atualizar_perfil'])) {
    $novo_user = trim($_POST['username']);
    $novo_email = trim($_POST['email']);
    $novo_tel = trim($_POST['telemovel']);
    $nova_pass = $_POST['password'];

    $sql_atual = "SELECT username, email FROM utilizadores WHERE id_utilizador = ?";
    $stmt_atual = $conn->prepare($sql_atual);
    $stmt_atual->bind_param("i", $user_id);
    $stmt_atual->execute();
    $dados_atuais = $stmt_atual->get_result()->fetch_assoc();

    $conflito = false;

    if ($novo_user !== $dados_atuais['username']) {
        $sql_check_user = "SELECT id_utilizador FROM utilizadores WHERE username = ?";
        $stmt_check_user = $conn->prepare($sql_check_user);
        $stmt_check_user->bind_param("s", $novo_user);
        $stmt_check_user->execute();
        if ($stmt_check_user->get_result()->num_rows > 0) {
            $mensagem_perfil = "Este Nome de Utilizador já está a ser utilizado por outra conta.";
            $conflito = true;
        }
    }

    if (!$conflito && $novo_email !== $dados_atuais['email']) {
        $sql_check_email = "SELECT id_utilizador FROM utilizadores WHERE email = ?";
        $stmt_check_email = $conn->prepare($sql_check_email);
        $stmt_check_email->bind_param("s", $novo_email);
        $stmt_check_email->execute();
        if ($stmt_check_email->get_result()->num_rows > 0) {
            $mensagem_perfil = "Este Endereço de Email já está a ser utilizado por outra conta.";
            $conflito = true;
        }
    }

    if (!$conflito) {
        $sql_update = "UPDATE utilizadores SET username = ?, email = ?, telemovel = ? WHERE id_utilizador = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sssi", $novo_user, $novo_email, $novo_tel, $user_id);
        
        if ($stmt_update->execute()) {
            $sucesso_perfil = true;
            $mensagem_perfil = "Dados atualizados com sucesso!";
            
            if (!empty($nova_pass)) {
                $pass_encriptada = password_hash($nova_pass, PASSWORD_DEFAULT);
                $sql_pass = "UPDATE utilizadores SET password = ? WHERE id_utilizador = ?";
                $stmt_pass = $conn->prepare($sql_pass);
                $stmt_pass->bind_param("si", $pass_encriptada, $user_id);
                $stmt_pass->execute();
            }
        } else {
            $mensagem_perfil = "Erro ao atualizar os dados na base de dados.";
        }
    }
}

// --- LÓGICA PARA ADICIONAR NOVO VASO ---
if (isset($_POST['btn_ativar'])) {
    $mac = strtoupper(trim($_POST['mac_address']));
    $nome_padrao = "Meu Vaso Principal";
    $mac_limpo = preg_replace('/[^0-9A-F]/', '', $mac);
    
    if (strlen($mac_limpo) !== 12) {
        $mensagem = "Este MAC address é inválido, por favor insira um MAC address válido.";
        $abrir_modal_erro = true;
    } else {
        $mac_formatado = implode(':', str_split($mac_limpo, 2));

        $sql_check_exists = "SELECT mac_address FROM dispositivos_validos WHERE mac_address = ?";
        $stmt_exists = $conn->prepare($sql_check_exists);
        $stmt_exists->bind_param("s", $mac_formatado);
        $stmt_exists->execute();
        $res_exists = $stmt_exists->get_result();

        if ($res_exists->num_rows === 0) {
            header("Location: inexistente.php");
            exit();
        }

        $sql_device_check = "SELECT id_vaso FROM vasos WHERE mac_address = ?";
        $stmt_device = $conn->prepare($sql_device_check);
        $stmt_device->bind_param("s", $mac_formatado);
        $stmt_device->execute();
        $res_device = $stmt_device->get_result();

        if ($res_device->num_rows > 0) {
            $mensagem = "Este dispositivo já está personalizado a uma conta.";
            $abrir_modal_erro = true;
        } else {
            $sql = "INSERT INTO vasos (id_utilizador, mac_address, nome_vaso) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $user_id, $mac_formatado, $nome_padrao);
            
            if ($stmt->execute()) {
                // --- INÍCIO DA ALTERAÇÃO PEDIDA ---
                // Obtém o ID do vaso que o MySQL acabou de gerar automaticamente
                $novo_id_vaso = $conn->insert_id;
                $seco_inicial = 0;
                $humido_inicial = 0;

                // Insere automaticamente a configuração inicial com 0 para esse novo ID
                $sql_config = "INSERT INTO vaso_config (id, seco_limite, humido_limite) VALUES (?, ?, ?)";
                $stmt_config = $conn->prepare($sql_config);
                $stmt_config->bind_param("iii", $novo_id_vaso, $seco_inicial, $humido_inicial);
                $stmt_config->execute();
                // --- FIM DA ALTERAÇÃO PEDIDA ---

                header("Location: ativacao.php");
                exit();
            } else {
                $mensagem = "Erro ao registar o dispositivo na base de dados.";
                $abrir_modal_erro = true;
            }
        }
    }
}

// --- LÓGICA PARA EDITAR NOME DE UM VASO ---
if (isset($_POST['btn_editar_nome'])) {
    $id_vaso = $_POST['id_vaso'];
    $novo_nome = trim($_POST['novo_nome']);
    
    $sql = "UPDATE vasos SET nome_vaso = ? WHERE id_vaso = ? AND id_utilizador = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $novo_nome, $id_vaso, $user_id);
    
    if ($stmt->execute()) {
        header("Location: ativacao.php");
        exit();
    }
}

// --- LÓGICA PARA REMOVER UM VASO ---
if (isset($_POST['btn_remover_vaso'])) {
    $id_vaso = $_POST['id_vaso'];
    
    $sql = "DELETE FROM vasos WHERE id_vaso = ? AND id_utilizador = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_vaso, $user_id);
    
    if ($stmt->execute()) {
        header("Location: ativacao.php");
        exit();
    }
}

// Buscar dados atuais do utilizador para os inputs
$sql_user = "SELECT username, email, telemovel FROM utilizadores WHERE id_utilizador = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$dados_user = $stmt_user->get_result()->fetch_assoc();

// Consulta todos os vasos deste utilizador específico
$sql_get = "SELECT id_vaso, mac_address, nome_vaso FROM vasos WHERE id_utilizador = ?";
$stmt_get = $conn->prepare($sql_get);
$stmt_get->bind_param("i", $user_id);
$stmt_get->execute();
$result = $stmt_get->get_result();

$lista_vasos = [];
while ($row = $result->fetch_assoc()) {
    $lista_vasos[] = $row;
}

$tem_vasos = (count($lista_vasos) > 0);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2d5a27">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="manifest.json">

    <title>GreenBuddy - Área Pessoal</title>
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

        .header {
            width: 100%;
            max-width: 800px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }

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

        .btn-logout-back:hover { background: rgba(204,68,68,0.2); }

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

        .navigation-tabs {
            display: flex;
            gap: 10px;
            margin: 10px 0 20px 0;
            background: #e2ede2;
            padding: 5px;
            border-radius: 30px;
            width: 90%;
            max-width: 550px;
            box-sizing: border-box;
        }

        .tab-button {
            flex: 1;
            border: none;
            background: none;
            padding: 12px;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            color: #555;
            transition: 0.3s;
            font-size: 0.95rem;
        }

        .tab-button.active {
            background: white;
            color: #2d5a27;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        .tab-content {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .empty-state {
            margin-top: 60px;
            text-align: center;
            color: #888;
        }

        .devices-container, .profile-container {
            width: 90%;
            max-width: 550px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .profile-card {
            background: white;
            padding: 30px;
            border-radius: 25px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            box-sizing: border-box;
            width: 100%;
        }

        .profile-card h2 {
            margin-top: 0;
            color: #2d5a27;
            font-size: 1.4rem;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 15px;
            text-align: left;
        }

        .form-group label {
            font-size: 0.85rem;
            color: #555;
            font-weight: 600;
            padding-left: 5px;
        }

        .form-group input {
            padding: 12px 15px;
            border-radius: 12px;
            border: 1px solid #ddd;
            font-size: 0.95rem;
            outline: none;
            transition: 0.2s;
            background: #fbfdfb;
        }

        .form-group input:focus {
            border-color: #2d5a27;
            box-shadow: 0 0 0 3px rgba(45, 90, 39, 0.1);
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
            z-index: 1;
        }

        .card-wrapper:hover {
            transform: translateY(-3px);
            border-color: #2d5a27;
        }

        .card-wrapper:focus-within {
            z-index: 999;
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

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 40px;
            background-color: white;
            min-width: 140px;
            box-shadow: 0px 8px 16px rgba(0,0,0,0.15);
            border-radius: 12px;
            z-index: 1000;
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

        .dropdown-content button:hover { background-color: #f5f5f5; }
        .dropdown-content button.delete-option { color: #c62828; }
        .dropdown-content button.delete-option:hover { background-color: #ffebee; }

        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 10000;
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
            margin: 15px 0;
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

        .alert-error {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 15px;
            border: 1px solid #ffcdd2;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 15px;
            border: 1px solid #c8e6c9;
        }

        #bloqueio-remoto {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(0, 0, 0, 0.95);
            z-index: 99999;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }
        .alerta-bloqueio {
            background: white;
            padding: 40px;
            border-radius: 30px;
            text-align: center;
            max-width: 400px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }
        .alerta-bloqueio h2 { color: #d9534f; margin-top: 0; font-size: 1.8rem; }
        .alerta-bloqueio p { color: #555; font-size: 0.95rem; line-height: 1.6; }
        .btn-logout-bloqueio {
            display: inline-block;
            background: #d9534f;
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 700;
            margin-top: 20px;
            text-transform: uppercase;
            font-size: 0.85rem;
            transition: 0.2s;
        }
        .btn-logout-bloqueio:hover { background: #c9302c; transform: scale(1.05); }
        .hidden { display: none !important; }
    </style>
</head>
<body>

    <div id="bloqueio-remoto" class="hidden">
        <div class="alerta-bloqueio">
            <span style="font-size: 4rem;">🚫</span>
            <h2>Conta Bloqueada</h2>
            <p>A sua conta foi temporariamente suspensa pela administração do sistema. Para mais informações ou suporte, contacte o administrador.</p>
            <a href="logout.php" class="btn-logout-bloqueio">Terminar Sessão</a>
        </div>
    </div>

    <div class="header">
        <a href="logout.php" class="btn-logout-back">Terminar Sessão</a>
        <img src="img/logotipo_PAP.png" style="height: 40px;">
        <button class="btn-add" onclick="openModal('modal-add')">+ Adicionar Vaso</button>
    </div>

    <div class="navigation-tabs">
        <button id="tab-vasos" class="tab-button active" onclick="switchTab('vasos')">🌱 Meus Vasos</button>
        <button id="tab-perfil" class="tab-button" onclick="switchTab('perfil')">👤 Minha Conta</button>
    </div>

    <div id="conteudo-vasos" class="tab-content">
        <?php if (!$tem_vasos): ?>
            <div class="empty-state">
                <div style="font-size: 60px;">🪴</div>
                <h2>Sem vasos conectados</h2>
                <p>Clica no botão superior para registar o teu primeiro vaso.</p>
            </div>
        <?php else: ?>
            <div class="devices-container">
                <?php foreach ($lista_vasos as $vaso): ?>
                    <div class="card-wrapper">
                        <a href="recebe.php?id=<?php echo $vaso['id_vaso']; ?>" class="device-card">
                            <div class="device-icon">🌿</div>
                            <div class="device-info">
                                <h3><?php echo htmlspecialchars($vaso['nome_vaso']); ?></h3>
                                <p>MAC: <?php echo htmlspecialchars($vaso['mac_address']); ?></p>
                            </div>
                        </a>

                        <div class="options-menu">
                            <button style="background: none; border: none; font-size: 1.5rem; color: #888; cursor: pointer; padding: 5px 10px; border-radius: 50%;" onclick="toggleDropdown(event, 'dropdown-<?php echo $vaso['id_vaso']; ?>')">⋮</button>
                            <div id="dropdown-<?php echo $vaso['id_vaso']; ?>" class="dropdown-content">
                                <button onclick="openModalEdit('<?php echo $vaso['id_vaso']; ?>', '<?php echo htmlspecialchars($vaso['nome_vaso']); ?>')">✏️ Editar Nome</button>
                                <button class="delete-option" onclick="openModalDelete('<?php echo $vaso['id_vaso']; ?>')">🗑️ Apagar Vaso</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div id="conteudo-perfil" class="tab-content hidden">
        <div class="profile-container">
            <div class="profile-card">
                <h2>Definições de Perfil</h2>
                
                <?php if (!empty($mensagem_perfil)): ?>
                    <div class="<?php echo $sucesso_perfil ? 'alert-success' : 'alert-error'; ?>">
                        <?php echo $mensagem_perfil; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="username">Nome de Utilizador</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($dados_user['username']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Endereço de Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($dados_user['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="telemovel">Número de Telemóvel</label>
                        <input type="tel" id="telemovel" name="telemovel" value="<?php echo htmlspecialchars($dados_user['telemovel']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="password">Nova Palavra-passe (Deixar em branco para manter a atual)</label>
                        <input type="password" id="password" name="password" placeholder="••••••••">
                    </div>

                    <button type="submit" name="btn_atualizar_perfil" class="btn-add" style="width: 100%; padding: 12px; border-radius: 50px; margin-top: 10px;">Guardar Alterações</button>
                </form>
            </div>
        </div>
    </div>

    <div id="modal-add" class="modal">
        <div class="modal-content">
            <h3>Novo Dispositivo</h3>
            <p>Insere o código do teu vaso</p>
            
            <?php if ($mensagem !== ""): ?>
                <div class="alert-error">
                    <?php echo $mensagem; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="text" name="mac_address" placeholder="AA:BB:CC:DD:EE:FF" value="<?php echo isset($_POST['mac_address']) ? htmlspecialchars($_POST['mac_address']) : ''; ?>" required>
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
                <input type="hidden" name="id_vaso" id="edit-id-vaso">
                <input type="text" name="novo_nome" id="edit-nome-vaso" required maxlength="50">
                <button type="submit" name="btn_editar_nome" class="btn-add" style="width: 100%; padding: 12px; border-radius: 50px;">Guardar Nome</button>
            </form>
            <button class="btn-cancel" onclick="closeModal('modal-edit')">Cancelar</button>
        </div>
    </div>

    <div id="modal-delete" class="modal">
        <div class="modal-content">
            <div style="font-size: 40px; margin-bottom: 10px;">⚠️</div>
            <h3>Apagar Dispositivo?</h3>
            <p>Tens a certeza que queres desvincular este vaso?</p>
            <form method="POST">
                <input type="hidden" name="id_vaso" id="delete-id-vaso">
                <button type="submit" name="btn_remover_vaso" class="btn-danger">Sim, Eliminar</button>
            </form>
            <button class="btn-cancel" onclick="closeModal('modal-delete')">Cancelar</button>
        </div>
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('Service Worker ativo!', reg))
                    .catch(err => console.log('Erro Service Worker:', err));
            });
        }

        // Loop de monitorização em segundo plano (Corrigido e funcional)
        function monitorarConta() {
            fetch('ativacao.php?ajax_check=1', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data && data.status === 'apagado') {
                    document.getElementById('bloqueio-remoto').classList.remove('hidden');
                }
            })
            .catch(err => console.log('A monitorizar conta...'));
        }
        setInterval(monitorarConta, 3000);
        monitorarConta();

        function switchTab(tab) {
            const conteudoVasos = document.getElementById('conteudo-vasos');
            const conteudoPerfil = document.getElementById('conteudo-perfil');
            const btnVasos = document.getElementById('tab-vasos');
            const btnPerfil = document.getElementById('tab-perfil');

            if (tab === 'vasos') {
                conteudoVasos.classList.remove('hidden');
                conteudoPerfil.classList.add('hidden');
                btnVasos.classList.add('active');
                btnPerfil.classList.remove('active');
            } else {
                conteudoVasos.classList.add('hidden');
                conteudoPerfil.classList.remove('hidden');
                btnVasos.classList.remove('active');
                btnPerfil.classList.add('active');
            }
        }

        // Se houver uma mensagem de perfil vinda do PHP, força a aba de Perfil a abrir
        <?php if (!empty($mensagem_perfil)): ?>
            switchTab('perfil');
        <?php endif; ?>

        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
            fecharTodosDropdowns();
        }

        // CORREÇÃO AQUI: .style.display em vez de .style.none
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        function openModalEdit(idVaso, nomeVaso) {
            document.getElementById('edit-id-vaso').value = idVaso;
            document.getElementById('edit-nome-vaso').value = nomeVaso;
            openModal('modal-edit');
        }

        function openModalDelete(idVaso) {
            document.getElementById('delete-id-vaso').value = idVaso;
            openModal('modal-delete');
        }

        function toggleDropdown(event, idDropdown) {
            event.stopPropagation();
            var targetDropdown = document.getElementById(idDropdown);
            var estadoAtual = targetDropdown.style.display;
            fecharTodosDropdowns();
            if (estadoAtual !== "block") {
                targetDropdown.style.display = "block";
            }
        }

        function fecharTodosDropdowns() {
            var dropdowns = document.getElementsByClassName("dropdown-content");
            for (var i = 0; i < dropdowns.length; i++) {
                dropdowns[i].style.display = "none";
            }
        }

        window.onclick = function(event) {
            if (!event.target.closest('.options-menu')) {
                fecharTodosDropdowns();
            }
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        <?php if ($abrir_modal_erro): ?>
            window.onload = function() {
                openModal('modal-add');
            }
        <?php endif; ?>
    </script>
</body>
</html>