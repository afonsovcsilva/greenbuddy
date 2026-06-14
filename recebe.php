<?php
require "db.php"; 
date_default_timezone_set('Europe/Lisbon');

// =========================================================================
// --- FUNÇÃO AUXILIAR: MENSAGEM DE AVISO DO RESERVATÓRIO ---
// =========================================================================
function calcularAutonomiaRestante($seco, $humido) {
    $limiteSeco = intval($seco);
    $limiteHumido = intval($humido);
    
    if ($limiteHumido <= $limiteSeco) {
        return ["texto" => "Dados Inválidos"];
    }

    // Retorna apenas a mensagem fixa solicitada para o utilizador
    return ["texto" => "Verificar a água de mês em mês"];
}

// =========================================================================
// --- 1. RECEBER DADOS DO SENSOR/HARDWARE (GET) ---
// =========================================================================
$humidade = $_GET['humidade'] ?? null;
$mac = $_GET['mac'] ?? null;

if ($humidade !== null) {
    $data = date("Y-m-d"); 
    $hora = date("H:i:s");
    
    $stmt = $conn->prepare("INSERT INTO vaso_humidade (data, hora, percentagem, mac_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $data, $hora, $humidade, $mac);
    $stmt->execute();
    
    $id_vaso_sensor = 1;
    if ($mac !== null) {
        $sql_mac = "SELECT id_vaso FROM vasos WHERE mac_address = ? LIMIT 1"; 
        $stmt_mac = $conn->prepare($sql_mac);
        if ($stmt_mac) {
            $stmt_mac->bind_param("s", $mac);
            $stmt_mac->execute();
            $res_mac = $stmt_mac->get_result();
            if ($res_mac->num_rows > 0) {
                $dados_mac = $res_mac->fetch_assoc();
                $id_vaso_sensor = $dados_mac['id_vaso'];
            }
        }
    } else if (isset($_GET['id'])) {
        $id_vaso_sensor = intval($_GET['id']);
    }

    $stmt_config = $conn->prepare("SELECT * FROM vaso_config WHERE id = ?");
    $stmt_config->bind_param("i", $id_vaso_sensor);
    $stmt_config->execute();
    $res_config = $stmt_config->get_result();
    $config = $res_config->fetch_assoc();
    
    $status_rega = isset($config['status_rega']) ? $config['status_rega'] : 1;
    echo "CONF_SECO:" . ($config['seco_limite'] ?? 0) . "|CONF_HUMIDO:" . ($config['humido_limite'] ?? 0) . "|STATUS_REGA:" . $status_rega;
    exit; 
}

// =========================================================================
// --- 2. CONTROLO DE SESSÃO DO UTILIZADOR ---
// =========================================================================
session_start(); 

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

$user_id = $_SESSION['user_id'];
$id_vaso = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id_vaso']) ? intval($_POST['id_vaso']) : 1);

$esta_bloqueado = false;
$nome_vaso_exibicao = "GreenBuddy";

$sql_check_vaso = "SELECT status_vaso, nome_vaso FROM vasos WHERE id_vaso = ? LIMIT 1";
$stmt_check_vaso = $conn->prepare($sql_check_vaso);
$stmt_check_vaso->bind_param("i", $id_vaso);
$stmt_check_vaso->execute();
$res_vaso = $stmt_check_vaso->get_result();

if ($res_vaso->num_rows === 0) {
    $esta_bloqueado = true;
} else {
    $dados_vaso = $res_vaso->fetch_assoc();
    if (!empty($dados_vaso['nome_vaso'])) {
        $nome_vaso_exibicao = $dados_vaso['nome_vaso'];
    }
    if (!isset($dados_vaso['status_vaso']) || trim($dados_vaso['status_vaso']) !== 'ativo') {
        $esta_bloqueado = true;
    }
}

$sql_check_user = "SELECT status FROM utilizadores WHERE id_utilizador = ? LIMIT 1";
$stmt_check_user = $conn->prepare($sql_check_user);
$stmt_check_user->bind_param("i", $user_id);
$stmt_check_user->execute();
$res_user = $stmt_check_user->get_result();

if ($res_user->num_rows === 0) {
    $esta_bloqueado = true;
} else {
    $dados_conta = $res_user->fetch_assoc();
    if (isset($dados_conta['status']) && trim($dados_conta['status']) !== 'ativo') {
        $esta_bloqueado = true;
    }
}

if ($esta_bloqueado) {
    if (isset($_GET['ajax']) || isset($_GET['ajax_check']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'apagado']);
        exit();
    }
}

if (isset($_GET['ajax_check'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ativo']);
    exit();
}

// =========================================================================
// --- 3. ATUALIZAÇÃO DA CONFIGURAÇÃO (POST) ---
// =========================================================================
if (isset($_POST['update_config'])) {
    $seco = max(0, min(100, intval($_POST['seco_limite'])));
    $humido = max(0, min(100, intval($_POST['humido_limite'])));
    $id_post = intval($_POST['id_vaso']); 
    
    $agora_string = date("Y-m-d H:i:s");
    
    $stmt = $conn->prepare("UPDATE vaso_config SET seco_limite = ?, humido_limite = ?, data_reset = ? WHERE id = ?");
    $stmt->bind_param("iisi", $seco, $humido, $agora_string, $id_post);
    $stmt->execute();
    
    header("Location: recebe.php?id=" . $id_post); 
    exit;
}

// =========================================================================
// --- 4. RESPOSTA PARA O GRÁFICO/PAINEL (AJAX) ---
// =========================================================================
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    $res_atual = $conn->query("SELECT percentagem, hora, data FROM vaso_humidade ORDER BY id_humidade DESC LIMIT 1");
    $dados = $res_atual->fetch_assoc();
    
    $stmt_config = $conn->prepare("SELECT * FROM vaso_config WHERE id = ?");
    $stmt_config->bind_param("i", $id_vaso);
    $stmt_config->execute();
    $res_config = $stmt_config->get_result();
    $config = $res_config->fetch_assoc();
    
    $autonomia_dados = calcularAutonomiaRestante(
        $config['seco_limite'] ?? 0, 
        $config['humido_limite'] ?? 0
    );
    
    echo json_encode([
        "status" => "ativo",
        "percentagem" => $dados['percentagem'] ?? 0,
        "hora" => $dados['hora'] ?? "--:--",
        "seco" => $config['seco_limite'] ?? 0,
        "humido" => $config['humido_limite'] ?? 0,
        "autonomia" => $autonomia_dados['texto']
    ]);
    exit;
}

$stmt_view = $conn->prepare("SELECT status_rega FROM vaso_config WHERE id = ?");
$stmt_view->bind_param("i", $id_vaso);
$stmt_view->execute();
$res_view = $stmt_view->get_result()->fetch_assoc();
$vaso_ligado = (isset($res_view['status_rega']) && intval($res_view['status_rega']) === 0) ? false : true;
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenBuddy - Painel de Controlo</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2d5a27; --accent: #4caf50; --bg: #f0f7f0; --text: #1a3317; --water: #2196F3; }
        * { box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: radial-gradient(circle at top right, #e8f5e9, var(--bg)); margin: 0; padding: 20px; display: flex; flex-direction: column; align-items: center; min-height: 100vh; color: var(--text); }
        .header { width: 100%; max-width: 450px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .logo-text { font-weight: 800; font-size: 1.4rem; color: var(--primary); letter-spacing: -1px; }
        .btn-logout { text-decoration: none; color: #cc4444; font-size: 0.85rem; font-weight: 600; padding: 8px 15px; background: rgba(204,68,68,0.1); border-radius: 12px; }
        .card { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); padding: 30px; border-radius: 35px; width: 100%; max-width: 450px; box-shadow: 0 20px 40px rgba(0,0,0,0.06); margin-bottom: 25px; border: 1px solid rgba(255,255,255,0.5); text-align: center; }
        .gauge-container { position: relative; width: 85%; margin: 0 auto 10px; }
        .value-display { position: absolute; top: 65%; left: 50%; transform: translate(-50%, -50%); font-size: 3rem; font-weight: 800; color: var(--primary); }
        .update-tag { font-size: 0.75rem; background: #e1ede0; color: var(--primary); padding: 5px 12px; border-radius: 20px; display: inline-block; margin-top: 5px; }
        .config-group { display: flex; gap: 15px; margin-bottom: 25px; }
        .config-item { flex: 1; background: #f9fbf9; padding: 15px; border-radius: 20px; border: 1px solid #eee; }
        .config-item label { display: block; font-size: 0.7rem; font-weight: 600; color: #888; text-transform: uppercase; margin-bottom: 8px; }
        input[type="number"] { width: 100%; background: transparent; border: none; font-size: 1.5rem; font-weight: 700; color: var(--primary); text-align: center; outline: none; }
        .btn-update { background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; border: none; padding: 18px; width: 100%; border-radius: 20px; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .btn-update:hover { transform: translateY(-2px); box-shadow: 0 15px 25px rgba(45,90,39,0.3); }

        .autonomia-container { margin-top: 10px; }
        .autonomia-v { font-size: 1.4rem; font-weight: 700; color: var(--water); display: block; margin: 10px 0; }
        .autonomia-sub { font-size: 0.8rem; color: #666; display: block; }

        #bloqueio-remoto {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(14, 26, 13, 0.98);
            z-index: 999999;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 24px;
            box-sizing: border-box;
        }
        .alerta-bloqueio {
            background: #ffffff;
            padding: 40px 30px;
            border-radius: 32px;
            text-align: center;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 25px 50px rgba(0,0,0,0.4);
            border: 1px solid rgba(0,0,0,0.05);
        }
        .alerta-bloqueio h2 { color: #d9534f; margin-top: 15px; font-size: 1.6rem; font-weight: 800; }
        .alerta-bloqueio p { color: #4a5568; font-size: 0.95rem; line-height: 1.6; margin: 15px 0 20px; }
        .email-contacto { font-weight: 700; color: var(--primary); background: #edf7ed; padding: 6px 12px; border-radius: 8px; display: inline-block; word-break: break-all; margin-top: 5px; }
        .hidden { display: none !important; }
    </style>
</head>
<body>

    <div id="bloqueio-remoto" class="<?php echo $esta_bloqueado ? '' : 'hidden'; ?>">
        <div class="alerta-bloqueio">
            <span style="font-size: 4.5rem; display: block; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));">🚫</span>
            <h2>Vaso Desativado</h2>
            <p>
                O seu vaso foi desativado pelo admin...<br>
                <span class="email-contacto">greenbuddy.app.26@gmail.com</span>
            </p>
        </div>
    </div>

    <div class="header">
        <span class="logo-text"><?php echo htmlspecialchars($nome_vaso_exibicao); ?></span>
        <a href="ativacao.php" class="btn-logout">Voltar aos Vasos</a>
    </div>
    
    <div class="card">
        <h3>Estado do Solo</h3>
        <div class="gauge-container">
            <canvas id="gaugeChart"></canvas>
            <div class="value-display" id="humidade-valor">--%</div>
        </div>
        <span class="update-tag" id="hora-atualizacao">A sincronizar...</span>
    </div>

    <div class="card">
        <h3>Reservatório de água</h3>
        <div class="autonomia-container">
            <span style="font-size: 2.5rem;">💧</span>
            <span class="autonomia-v" id="txt-tempo-restante">A calcular...</span>
            <span class="autonomia-sub" id="txt-detalhe-calculo">Manutenção Preventiva</span>
        </div>
    </div>

    <div class="card">
        <h3>Configuração de Rega</h3>
        <form method="POST">
            <input type="hidden" name="id_vaso" value="<?php echo $id_vaso; ?>">
            
            <div class="config-group">
                <div class="config-item">
                    <label>Seco</label>
                    <input type="number" name="seco_limite" id="input-seco" min="0" max="100">
                </div>
                <div class="config-item">
                    <label>Húmido</label>
                    <input type="number" name="humido_limite" id="input-humido" min="0" max="100">
                </div>
            </div>
            <button type="submit" name="update_config" class="btn-update">Guardar Alterações</button>
        </form>
    </div>

    <script>
        const ctx = document.getElementById('gaugeChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 200);
        gradient.addColorStop(0, '#4caf50'); gradient.addColorStop(1, '#2d5a27');
        
        const gaugeChart = new Chart(ctx, {
            type: 'doughnut',
            data: { datasets: [{ data: [0, 100], backgroundColor: [gradient, '#f0f0f0'], borderWidth: 0, circumference: 180, rotation: 270, cutout: '85%', borderRadius: 20 }] },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });

        function alternarBloqueioEcran(ativar) {
            const telaBloqueio = document.getElementById('bloqueio-remoto');
            if (ativar) {
                telaBloqueio.classList.remove('hidden');
            } else {
                telaBloqueio.classList.add('hidden');
            }
        }

        function atualizar() {
            const urlParams = new URLSearchParams(window.location.search);
            const idVaso = urlParams.get('id') || 1;

            fetch('recebe.php?ajax=1&id=' + idVaso)
                .then(r => r.json())
                .then(data => {
                    if (data && data.status === 'apagado') {
                        alternarBloqueioEcran(true);
                        return;
                    }

                    alternarBloqueioEcran(false);

                    if (data && data.percentagem !== undefined) {
                        const v = parseInt(data.percentagem);
                        gaugeChart.data.datasets[0].data = [v, 100 - v];
                        gaugeChart.update();
                        document.getElementById('humidade-valor').innerText = v + "%";
                        document.getElementById('hora-atualizacao').innerText = "Leitura: " + data.hora;
                    }
                    
                    if (data && data.seco !== undefined && data.humido !== undefined) {
                        document.getElementById('txt-tempo-restante').innerText = data.autonomia;

                        if(document.activeElement.tagName !== "INPUT") {
                            document.getElementById('input-seco').value = data.seco;
                            document.getElementById('input-humido').value = data.humido;
                        }
                    }
                })
                .catch(err => console.log('A aguardar dados...'));
        }

        setInterval(atualizar, 3000); 
        atualizar();

        function monitorarConta() {
            const urlParams = new URLSearchParams(window.location.search);
            const idVaso = urlParams.get('id') || 1;
            
            fetch('recebe.php?id=' + idVaso + '&ajax_check=1', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data && data.status === 'apagado') {
                    alternarBloqueioEcran(true);
                } else {
                    alternarBloqueioEcran(false);
                }
            })
            .catch(err => console.log('A verificar conta...'));
        }
        setInterval(monitorarConta, 2000); 
        monitorarConta();
    </script>
</body>
</html>