<?php
require "db.php"; 
session_start(); 
date_default_timezone_set('Europe/Lisbon');

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

// 1. NAVEGAÇÃO WEB: Captura o id_vaso vindo do ativacao.php
if (isset($_GET['id'])) {
    $_SESSION['id_vaso_atual'] = intval($_GET['id']);
}

$id_vaso = $_SESSION['id_vaso_atual'] ?? null;

if (!$id_vaso) {
    header("Location: ativacao.php");
    exit;
}

// Procura as informações do vaso atual
$stmt_vaso = $conn->prepare("SELECT mac_address, nome_vaso, status_vaso FROM vasos WHERE id_vaso = ? AND id_utilizador = ?");
$stmt_vaso->bind_param("ii", $id_vaso, $_SESSION['user_id']);
$stmt_vaso->execute();
$dados_vaso = $stmt_vaso->get_result()->fetch_assoc();

if (!$dados_vaso) {
    header("Location: ativacao.php");
    exit;
}

$mac_do_vaso = $dados_vaso['mac_address'];
$nome_do_vaso = $dados_vaso['nome_vaso'];
// Guarda se o vaso está desativado (aceita 'desativado' ou o termo em inglês 'desactivated')
$vaso_desativado = ($dados_vaso['status_vaso'] === 'desativado' || $dados_vaso['status_vaso'] === 'desactivated');


// 2. ATUALIZAR CONFIGURAÇÃO (Só processa se o vaso NÃO estiver desativado)
if (isset($_POST['update_config']) && !$vaso_desativado) {
    $seco = intval($_POST['seco_limite']);
    $humido = intval($_POST['humido_limite']);
    
    $stmt_up = $conn->prepare("INSERT INTO vaso_config (id, seco_limite, humido_limite) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE seco_limite = ?, humido_limite = ?");
    $stmt_up->bind_param("iiiii", $id_vaso, $seco, $humido, $seco, $humido);
    $stmt_up->execute();
    
    header("Location: recebe.php"); 
    exit;
}


// 3. ENTRADA DE DADOS DO ARDUINO / ESP32 (O circuito continua a ser rejeitado se estiver desativado)
$humidade = $_GET['humidade'] ?? null;
$mac_param = $_GET['mac'] ?? null;

if ($humidade !== null && $mac_param !== null) {
    $mac_recebido = strtoupper(trim($mac_param));
    $mac_recebido = str_replace(':', '', $mac_recebido);
    
    $data = date("Y-m-d"); 
    $hora = date("H:i:s");
    
    $stmt_busca = $conn->prepare("SELECT id_vaso, status_vaso FROM vasos WHERE mac_address = ?");
    $stmt_busca->bind_param("s", $mac_recebido);
    $stmt_busca->execute();
    $res_busca = $stmt_busca->get_result()->fetch_assoc();
    
    if ($res_busca) {
        if ($res_busca['status_vaso'] === 'desativado' || $res_busca['status_vaso'] === 'desactivated') {
            echo "ERRO: Vaso desativado pela administracao.";
            exit;
        }

        $id_vaso_arduino = $res_busca['id_vaso'];
        
        $stmt_ins = $conn->prepare("INSERT INTO vaso_humidade (data, hora, percentagem, mac_address) VALUES (?, ?, ?, ?)");
        $stmt_ins->bind_param("ssss", $data, $hora, $humidade, $mac_recebido);
        $stmt_ins->execute();
        
        $stmt_c = $conn->prepare("SELECT seco_limite, humido_limite FROM vaso_config WHERE id = ?");
        $stmt_c->bind_param("i", $id_vaso_arduino);
        $stmt_c->execute();
        $config = $stmt_c->get_result()->fetch_assoc();
        
        $seco_limite = $config['seco_limite'] ?? 20;
        $humido_limite = $config['humido_limite'] ?? 80;
        
        echo "CONF_SECO:" . $seco_limite . "|CONF_HUMIDO:" . $humido_limite;
    } else {
        echo "ERRO: MAC [" . $mac_recebido . "] nao registado.";
    }
    exit;
}


// 4. RETORNO DO AJAX (Se a página web tentar pedir dados via AJAX de um vaso bloqueado, retorna vazio)
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    if ($vaso_desativado) {
        echo json_encode(["disabled" => true]);
        exit;
    }
    
    $stmt_h = $conn->prepare("SELECT percentagem, hora FROM vaso_humidade WHERE mac_address = ? ORDER BY id_humidade DESC LIMIT 1");
    $stmt_h->bind_param("s", $mac_do_vaso);
    $stmt_h->execute();
    $dados = $stmt_h->get_result()->fetch_assoc();
    
    $stmt_conf = $conn->prepare("SELECT seco_limite, humido_limite FROM vaso_config WHERE id = ?");
    $stmt_conf->bind_param("i", $id_vaso);
    $stmt_conf->execute();
    $config = $stmt_conf->get_result()->fetch_assoc();
    
    echo json_encode([
        "percentagem" => $dados['percentagem'] ?? 0,
        "hora" => $dados['hora'] ?? "--:--",
        "seco" => $config['seco_limite'] ?? 20,
        "humido" => $config['humido_limite'] ?? 80
    ]);
    exit;
}
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
        :root { --primary: #2d5a27; --accent: #4caf50; --bg: #f0f7f0; --text: #1a3317; --danger: #cc4444; }
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
        .mac-sub { font-size: 0.8rem; color: #666; font-weight: normal; }
        
        /* Estilos do Bloco de Aviso */
        .card-disabled { border: 2px dashed var(--danger); background: rgba(255, 235, 235, 0.9); }
        .danger-icon { font-size: 3.5rem; color: var(--danger); margin-bottom: 10px; display: block; animation: pulse 2s infinite; }
        .disabled-text { color: #555; font-size: 0.95rem; line-height: 1.6; margin-bottom: 15px; }
        .phone-link { display: inline-block; font-size: 1.3rem; font-weight: 800; color: var(--danger); text-decoration: none; background: white; padding: 10px 20px; border-radius: 15px; box-shadow: 0 5px 15px rgba(204,68,68,0.1); }
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.08); } 100% { transform: scale(1); } }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <span class="logo-text"><?php echo htmlspecialchars($nome_do_vaso); ?></span><br>
            <span class="mac-sub">MAC: <?php echo htmlspecialchars($mac_do_vaso); ?></span>
        </div>
        <a href="ativacao.php" class="btn-logout">Voltar</a>
    </div>
    
    <?php if ($vaso_desativado): ?>
        <div class="card card-disabled">
            <span class="danger-icon">🔒</span>
            <h3>Acesso Suspenso</h3>
            <p class="disabled-text">
                Este vaso foi desativado pelo admin. Espere até que ele o contacte ou o reative.<br>
                Caso queira contactá-lo, ligue para:
            </p>
            <a href="tel:938490159" class="phone-link">📞 938490159</a>
        </div>
    <?php else: ?>
        <div class="card">
            <h3>Estado do Solo</h3>
            <div class="gauge-container"><canvas id="gaugeChart"></canvas><div class="value-display" id="humidade-valor">--%</div></div>
            <span class="update-tag" id="hora-atualizacao">A sincronizar...</span>
        </div>
        
        <div class="card">
            <h3>Configuração de Rega</h3>
            <form method="POST">
                <div class="config-group">
                    <div class="config-item"><label>Seco</label><input type="number" name="seco_limite" id="input-seco"></div>
                    <div class="config-item"><label>Húmido</label><input type="number" name="humido_limite" id="input-humido"></div>
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

            function atualizar() {
                fetch('recebe.php?ajax=1').then(r => r.json()).then(data => {
                    if (data.disabled) return; // Interrompe se o vaso for desativado remotamente
                    
                    const v = parseInt(data.percentagem);
                    gaugeChart.data.datasets[0].data = [v, 100 - v];
                    gaugeChart.update();
                    document.getElementById('humidade-valor').innerText = v + "%";
                    document.getElementById('hora-atualizacao').innerText = "Leitura: " + data.hora;
                    if(document.activeElement.tagName !== "INPUT") {
                        document.getElementById('input-seco').value = data.seco;
                        document.getElementById('input-humido').value = data.humido;
                    }
                });
            }
            setInterval(atualizar, 5000); atualizar();
        </script>
    <?php endif; ?>
</body>
</html>