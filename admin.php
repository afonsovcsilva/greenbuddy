<?php
require "db.php";
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['acao'])) {
    $acao = $_GET['acao'];
    $id_alvo = isset($_GET['id']) ? intval($_GET['id']) : 0;

    // 1. Ações em Massa
    if ($acao === 'bloquear_todos') {
        $stmt = $conn->prepare("UPDATE utilizadores SET status = 'bloqueado' WHERE is_admin != 1");
        $stmt->execute();
        
        if (isset($_GET['ajax_action'])) {
            header('Content-Type: application/json');
            echo json_encode(["status" => "success"]);
            exit();
        }
    }
    elseif ($acao === 'desbloquear_todos') {
        $stmt = $conn->prepare("UPDATE utilizadores SET status = 'ativo' WHERE is_admin != 1");
        $stmt->execute();
        
        if (isset($_GET['ajax_action'])) {
            header('Content-Type: application/json');
            echo json_encode(["status" => "success"]);
            exit();
        }
    }
    // 2. Ações de Utilizador Individual
    elseif ($id_alvo > 0 && $id_alvo != $_SESSION['user_id'] && ($acao === 'bloquear' || $acao === 'desbloquear' || $acao === 'remover')) {
        if ($acao === 'bloquear') {
            $stmt = $conn->prepare("UPDATE utilizadores SET status = 'bloqueado' WHERE id_utilizador = ?");
            $stmt->bind_param("i", $id_alvo);
            $stmt->execute();
        } 
        elseif ($acao === 'desbloquear') {
            $stmt = $conn->prepare("UPDATE utilizadores SET status = 'ativo' WHERE id_utilizador = ?");
            $stmt->bind_param("i", $id_alvo);
            $stmt->execute();
        } 
        elseif ($acao === 'remover') {
            // ---- LOGICA DE ENVIO DE EMAIL ANTES DE REMOVER ----
            $stmt_email = $conn->prepare("SELECT email FROM utilizadores WHERE id_utilizador = ?");
            $stmt_email->bind_param("i", $id_alvo);
            $stmt_email->execute();
            $res_email = $stmt_email->get_result();

            if ($res_email->num_rows > 0) {
                $user_data = $res_email->fetch_assoc();
                $email_destino = $user_data['email'];

                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'greenbuddy.app.26@gmail.com';
                    $mail->Password   = 'SUA_PALAVRA_PASSE_DE_APLICACAO'; // <--- Chave Google aqui
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->CharSet    = 'UTF-8';

                    $mail->setFrom('greenbuddy.app.26@gmail.com', 'GreenBuddy App');
                    $mail->addAddress($email_destino);

                    $mail->isHTML(true);
                    $mail->Subject = 'Aviso Importante de Segurança - GreenBuddy';
                    $mail->Body    = "
                        <div style='font-family: sans-serif; padding: 20px; color: #333;'>
                            <h2 style='color: #cc4444;'>Conta Eliminada</h2>
                            <p>Olá,</p>
                            <p>Lamentamos informar, mas a sua conta foi apagada do nosso system pois violava os nossos termos de segurança e privacidade.</p>
                            <br>
                            <p>Atentamente,<br><b>Equipa GreenBuddy</b></p>
                        </div>
                    ";
                    $mail->AltBody = 'A sua conta foi apagada pois violava os nossos termos de segurança e privacidade.';

                    $mail->send();
                } catch (Exception $e) {   
                }
            }
            // ----------------------------------------------------

            $stmt1 = $conn->prepare("DELETE FROM vasos WHERE id_utilizador = ?");
            $stmt1->bind_param("i", $id_alvo);
            $stmt1->execute();

            $stmt2 = $conn->prepare("DELETE FROM utilizadores WHERE id_utilizador = ?");
            $stmt2->bind_param("i", $id_alvo);
            $stmt2->execute();
        }

        // RESPOSTA AJAX PARA UTILIZADORES
        if (isset($_GET['ajax_action'])) {
            header('Content-Type: application/json');
            echo json_encode(["status" => "success"]);
            exit();
        }
    }
    // 3. Ações de Vaso Individual
    elseif ($id_alvo > 0 && ($acao === 'desativar_vaso' || $acao === 'ativar_vaso')) {
        if ($acao === 'desativar_vaso') {
            $stmt = $conn->prepare("UPDATE vasos SET status_vaso = 'desativado' WHERE id_vaso = ?");
            $stmt->bind_param("i", $id_alvo);
            $stmt->execute();
        }
        elseif ($acao === 'ativar_vaso') {
            $stmt = $conn->prepare("UPDATE vasos SET status_vaso = 'ativo' WHERE id_vaso = ?");
            $stmt->bind_param("i", $id_alvo);
            $stmt->execute();
        }

        // RESPOSTA AJAX PARA VASOS
        if (isset($_GET['ajax_action'])) {
            header('Content-Type: application/json');
            echo json_encode(["status" => "success"]);
            exit();
        }
    }

    // Redirecionamento de segurança caso alguém tente aceder diretamente pelo URL sem AJAX
    header("Location: admin.php");
    exit();
}

// FUNÇÃO PARA GERAR AS LINHAS DA TABELA
function renderizarTabelaUtilizadores($conn, $pesquisa = '') {
    if (!empty($pesquisa)) {
        $query = "SELECT id_utilizador, username, email, telemovel, status, is_admin FROM utilizadores 
                  WHERE username LIKE ? OR email LIKE ?
                  ORDER BY is_admin DESC, username ASC";
        $stmt = $conn->prepare($query);
        $termo = "%" . $pesquisa . "%";
        $stmt->bind_param("ss", $termo, $termo);
        $stmt->execute();
        $resultado = $stmt->get_result();
    } else {
        $query = "SELECT id_utilizador, username, email, telemovel, status, is_admin FROM utilizadores ORDER BY is_admin DESC, username ASC";
        $resultado = $conn->query($query);
    }

    $html = "";

    while ($user = $resultado->fetch_assoc()) {
        $badge_admin = ($user['is_admin'] == 1) ? '<span class="badge badge-admin">Admin</span>' : '';
        $badge_status = ($user['status'] === 'bloqueado') ? '<span class="badge badge-blocked">Bloqueado</span>' : '<span class="badge badge-active">Ativo</span>';
        
        $stmt_vasos = $conn->prepare("SELECT id_vaso, nome_vaso, mac_address, status_vaso FROM vasos WHERE id_utilizador = ?");
        $stmt_vasos->bind_param("i", $user['id_utilizador']);
        $stmt_vasos->execute();
        $res_vasos = $stmt_vasos->get_result();
        
        $total_vasos = $res_vasos->num_rows;
        $vasos_html = "";
        
        if ($total_vasos > 0) {
            $vasos_html .= "<button class='btn-toggle-vasos' onclick='toggleVasos({$user['id_utilizador']})'>
                                Ver Vasos ({$total_vasos}) <span id='seta-{$user['id_utilizador']}' class='seta'>▼</span>
                            </button>";
            
            $vasos_html .= "<div id='lista-vasos-{$user['id_utilizador']}' class='vasos-collapse-container'>";
            $vasos_html .= "<div style='padding-top: 10px;'>"; 
            
            while ($v = $res_vasos->fetch_assoc()) {
                $status_cor = ($v['status_vaso'] === 'desativado') ? '#d9534f' : '#4caf50';
                $texto_botao = ($v['status_vaso'] === 'desativado') ? 'Ativar Vaso' : 'Desativar Vaso';
                $acao_botao = ($v['status_vaso'] === 'desativado') ? 'ativar_vaso' : 'desativar_vaso';
                
                $vasos_html .= "<div style='margin-bottom: 8px; padding: 8px; background: #f9fbf9; border-radius: 6px; border-left: 4px solid {$status_cor}; text-align: left;'>
                    <strong>" . htmlspecialchars($v['nome_vaso']) . "</strong> <span style='font-size:0.75rem; color:#888;'>({$v['mac_address']})</span><br>
                    <button onclick='executarAcao(\"{$acao_botao}\", {$v['id_vaso']})' style='font-size: 0.7rem; padding: 2px 6px; margin-top: 5px; cursor:pointer; background: transparent; border: 1px solid {$status_cor}; color: {$status_cor}; border-radius: 4px;'>{$texto_botao}</button>
                </div>";
            }
            
            $vasos_html .= "</div></div>";
        } else {
            $vasos_html = '<em style="color:#bbb; font-size: 0.85rem;">Nenhum vaso</em>';
        }
        
        $botoes = '';
        if ($user['is_admin'] != 1) {
            if ($user['status'] === 'bloqueado') {
                $botoes .= '<button onclick="executarAcao(\'desbloquear\', '.$user['id_utilizador'].')" class="btn-action btn-unblock">Desbloquear Conta</button> ';
            } else {
                $botoes .= '<button onclick="executarAcao(\'bloquear\', '.$user['id_utilizador'].')" class="btn-action btn-block">Bloquear Conta</button> ';
            }
            $botoes .= '<button onclick="removerUtilizador('.$user['id_utilizador'].')" class="btn-action btn-remove">Remover</button>';
        } else {
            $botoes = '<span style="color: #bbb; font-size: 0.8rem; font-style: italic;">Sem ações</span>';
        }

        $html .= "<tr data-user-id='{$user['id_utilizador']}'>
            <td><strong>#{$user['id_utilizador']}</strong></td>
            <td>" . htmlspecialchars($user['username']) . " {$badge_admin}</td>
            <td>" . htmlspecialchars($user['email']) . "</td>
            <td>" . htmlspecialchars($user['telemovel'] ?? 'Não inserido') . "</td>
            <td class='vasos-list'>{$vasos_html}</td>
            <td>{$badge_status}</td>
            <td class='actions'>{$botoes}</td>
        </tr>";
    }

    if ($html == "") {
        $html = "<tr><td colspan='7' style='text-align:center; color:#888; padding: 30px;'>Nenhum utilizador encontrado com esse critério.</td></tr>";
    }

    return $html;
}

// Verifica se existem utilizadores comuns ativos
function obterEstadoBotaoMassa($conn) {
    $res = $conn->query("SELECT COUNT(*) as ativos FROM utilizadores WHERE is_admin != 1 AND status = 'ativo'");
    $row = $res->fetch_assoc();
    return ($row['ativos'] > 0) ? 'bloquear' : 'desbloquear';
}

// Resposta estrita do AJAX para atualização da tabela
if (isset($_GET['atualizar_tabela']) && $_GET['atualizar_tabela'] == 1) {
    $pesquisa = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    header('Content-Type: application/json');
    echo json_encode([
        'html' => renderizarTabelaUtilizadores($conn, $pesquisa),
        'estado_botao' => obterEstadoBotaoMassa($conn)
    ]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenBuddy - Consola Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #1a3317; --accent: #4caf50; --bg: #f4f7f4; --danger: #d9534f; --warning: #f0ad4e; }
        * { box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: var(--bg); margin: 0; padding: 20px; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; width: 100%; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #e0e6e0; padding-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        h1 { color: var(--primary); margin: 0; font-weight: 700; font-size: 1.6rem; }
        .btn-logout { text-decoration: none; color: white; background: var(--primary); padding: 10px 20px; border-radius: 10px; font-weight: 600; font-size: 0.9rem; }
        .card { background: white; padding: 20px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); overflow-x: auto; }
        .card-header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        h2 { font-size: 1.1rem; color: #555; margin: 0; text-transform: uppercase; letter-spacing: 1px; }
        .controls-wrapper { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; width: 100%; max-width: 650px; justify-content: flex-end; }
        .search-container { position: relative; flex: 1; min-width: 250px; }
        .search-input { width: 100%; padding: 10px 15px; border: 2px solid #e0e6e0; border-radius: 12px; font-size: 0.9rem; outline: none; transition: 0.2s; }
        .search-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1); }
        .btn-bulk-toggle { color: white; border: none; padding: 11px 20px; border-radius: 12px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: background 0.2s; white-space: nowrap; }
        .btn-bulk-toggle.state-block { background: var(--danger); }
        .btn-bulk-toggle.state-unblock { background: var(--accent); }
        table { width: 100%; border-collapse: collapse; text-align: left; min-width: 800px; }
        th { background: #edf2ed; padding: 15px; color: var(--primary); font-weight: 600; font-size: 0.85rem; }
        td { padding: 15px; border-bottom: 1px solid #eee; font-size: 0.85rem; vertical-align: middle; }
        .badge { padding: 5px 10px; border-radius: 12px; font-size: 0.72rem; font-weight: 600; text-transform: uppercase; }
        .badge-active { background: #e8f5e9; color: #2e7d32; }
        .badge-blocked { background: #ffebee; color: #c62828; }
        .badge-admin { background: #e3f2fd; color: #1565c0; }
        .vasos-list { width: 320px; }
        .btn-toggle-vasos { background: #edf2ed; border: 1px solid #d4ded4; color: var(--primary); padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; width: 100%; justify-content: space-between; }
        .seta { font-size: 0.65rem; transition: transform 0.3s ease; display: inline-block; }
        .seta.rodada { transform: rotate(180deg); }
        .vasos-collapse-container { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; }
        .vasos-collapse-container.aberto { max-height: 300px; overflow-y: auto; }
        .actions { display: flex; flex-direction: column; gap: 5px; min-width: 130px; }
        .btn-action { border: none; font-size: 0.75rem; font-weight: 600; padding: 8px 12px; border-radius: 8px; color: white; transition: 0.2s; cursor: pointer; text-align: center; }
        .btn-block { background: var(--warning); }
        .btn-unblock { background: var(--accent); }
        .btn-remove { background: var(--danger); }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div>
            <h1>Consola Admin PWA</h1>
            <p style="margin: 5px 0 0; color: #777; font-size: 0.85rem;">Gestão global de utilizadores e vasos IoT</p>
        </div>
        <a href="logout.php" class="btn-logout">Sair do Painel</a>
    </div>

    <div class="card">
        <div class="card-header-actions">
            <h2>Contas e Dispositivos Registados</h2>
            <div class="controls-wrapper">
                <div class="search-container">
                    <input type="text" id="input-pesquisa" class="search-input" placeholder="Pesquisar por nome ou email..." oninput="verificarAtualizacoes()">
                </div>
                <button id="btn-massa" onclick="alternarTodosUtilizadores()" class="btn-bulk-toggle <?php echo (obterEstadoBotaoMassa($conn) === 'bloquear') ? 'state-block' : 'state-unblock'; ?>">
                    <?php echo (obterEstadoBotaoMassa($conn) === 'bloquear') ? '⚠️ Bloquear Todas as Contas' : '✅ Desbloquear Todas as Contas'; ?>
                </button>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Utilizador</th>
                    <th>Email</th>
                    <th>Telefone</th>
                    <th>Vasos Cadastrados (Ações)</th>
                    <th>Estado Conta</th>
                    <th>Ações Conta</th>
                </tr>
            </thead>
            <tbody id="tabela-corpo">
                <?php echo renderizarTabelaUtilizadores($conn); ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    let estadoAtualGlobal = '<?php echo obterEstadoBotaoMassa($conn); ?>';
    let menusAbertos = {};

    function toggleVasos(userId) {
        const container = document.getElementById(`lista-vasos-${userId}`);
        const seta = document.getElementById(`seta-${userId}`);
        if (container.classList.contains('aberto')) {
            container.classList.remove('aberto');
            seta.classList.remove('rodada');
            menusAbertos[userId] = false;
        } else {
            container.classList.add('aberto');
            seta.classList.add('rodada');
            menusAbertos[userId] = true;
        }
    }

    function verificarAtualizacoes() {
        const termoPesquisa = encodeURIComponent(document.getElementById('input-pesquisa').value);
        fetch(`admin.php?atualizar_tabela=1&q=${termoPesquisa}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('tabela-corpo').innerHTML = data.html;
                Object.keys(menusAbertos).forEach(userId => {
                    if (menusAbertos[userId]) {
                        const container = document.getElementById(`lista-vasos-${userId}`);
                        const seta = document.getElementById(`seta-${userId}`);
                        if (container && seta) {
                            container.classList.add('aberto');
                            seta.classList.add('rodada');
                        }
                    }
                });

                const btnMassa = document.getElementById('btn-massa');
                estadoAtualGlobal = data.estado_botao;
                if (estadoAtualGlobal === 'bloquear') {
                    btnMassa.className = "btn-bulk-toggle state-block";
                    btnMassa.innerHTML = "⚠️ Bloquear Todas as Contas";
                } else {
                    btnMassa.className = "btn-bulk-toggle state-unblock";
                    btnMassa.innerHTML = "✅ Desbloquear Todas as Contas";
                }
            })
            .catch(err => console.log('Erro ao sincronizar:', err));
    }

    setInterval(verificarAtualizacoes, 4000);

    function executarAcao(acao, id) {
        fetch(`admin.php?acao=${acao}&id=${id}&ajax_action=1`)
            .then(response => response.json())
            .then(data => {
                verificarAtualizacoes();
            })
            .catch(err => {
                console.error('Erro ao executar a ação:', err);
                window.location.reload();
            });
    }

    function alternarTodosUtilizadores() {
        if (estadoAtualGlobal === 'bloquear') {
            if (confirm('ATENÇÃO: Tens a certeza que desejas BLOQUEAR TODOS os utilizadores?')) {
                fetch('admin.php?acao=bloquear_todos&ajax_action=1').then(() => verificarAtualizacoes());
            }
        } else {
            if (confirm('Desejas DESBLOQUEAR TODOS os utilizadores?')) {
                fetch('admin.php?acao=desbloquear_todos&ajax_action=1').then(() => verificarAtualizacoes());
            }
        }
    }

    function removerUtilizador(id) {
        if (confirm('Tens a certeza que queres eliminar permanentemente este utilizador e todos os seus vasos?')) {
            fetch(`admin.php?acao=remover&id=${id}&ajax_action=1`).then(() => verificarAtualizacoes());
        }
    }
</script>
</body>
</html>