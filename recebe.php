<?php
require "db.php"; 
session_start(); 
date_default_timezone_set('Europe/Lisbon');

// Captura o ID do vaso que vem da URL (GET) ou do formulário (POST). Se não vier nenhum, assume o 1.
$id_vaso = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id_vaso']) ? intval($_POST['id_vaso']) : 1);

// --- NOVA CONSULTA: Puxar o nome personalizado do vaso ---
$nome_vaso_exibicao = "GreenBuddy";
$stmt_nome = $conn->prepare("SELECT nome_vaso FROM vasos WHERE id_vaso = ? LIMIT 1");
if ($stmt_nome) {
    $stmt_nome->bind_param("i", $id_vaso);
    $stmt_nome->execute();
    $res_nome = $stmt_nome->get_result();
    if ($vaso_row = $res_nome->fetch_assoc()) {
        if (!empty($vaso_row['nome_vaso'])) {
            $nome_vaso_exibicao = $vaso_row['nome_vaso'];
        }
    }
}

// --- 1. ATUALIZAÇÃO DA CONFIGURAÇÃO (POST) ---
// Quando o utilizador altera os valores de 0 para o que quiser e clica em Guardar
if (isset($_POST['update_config'])) {
    $seco = intval($_POST['seco_limite']);
    $humido = intval($_POST['humido_limite']);
    $id_post = intval($_POST['id_vaso']); 
    
    // Atualiza APENAS o vaso correspondente usando Prepared Statements
    $stmt = $conn->prepare("UPDATE vaso_config SET seco_limite = ?, humido_limite = ? WHERE id = ?");
    $stmt->bind_param("iii", $seco, $humido, $id_post);
    $stmt->execute();
    
    // Redireciona mantendo o ID do vaso correto na URL
    header("Location: recebe.php?id=" . $id_post); 
    exit;
}

// --- 2. RECEBER DADOS DO SENSOR/HARDWARE (GET) ---
$humidade = $_GET['humidade'] ?? null;
$mac = $_GET['mac'] ?? null;

if ($humidade !== null) {
    $data = date("Y-m-d"); $hora = date("H:i:s");
    $stmt = $conn->prepare("INSERT INTO vaso_humidade (data, hora, percentagem, mac_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $data, $hora, $humidade, $mac);
    $stmt->execute();
    
    // Retorna as configurações do vaso atual para o hardware
    $stmt_config = $conn->prepare("SELECT * FROM vaso_config WHERE id = ?");
    $stmt_config->bind_param("i", $id_vaso);
    $stmt_config->execute();
    $res_config = $stmt_config->get_result();
    $config = $res_config->fetch_assoc();
    
    echo "CONF_SECO:" . ($config['seco_limite'] ?? 0) . "|CONF_HUMIDO:" . ($config['humido_limite'] ?? 0);
    exit;
}

// --- 3. RESPOSTA PARA O GRÁFICO/PAINEL (AJAX) ---
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    // Pega a última leitura de humidade geral
    $res_atual = $conn->query("SELECT percentagem, hora, data FROM vaso_humidade ORDER BY id_humidade DESC LIMIT 1");
    $dados = $res_atual->fetch_assoc();
    
    // Pega as configurações específicas deste vaso
    $stmt_config = $conn->prepare("SELECT * FROM vaso_config WHERE id = ?");
    $stmt_config->bind_param("i", $id_vaso);
    $stmt_config->execute();
    $res_config = $stmt_config->get_result();
    $config = $res_config->fetch_assoc();
    
    echo json_encode([
        "percentagem" => $dados['percentagem'] ?? 0,
        "hora" => $dados['hora'] ?? "--:--",
        "seco" => $config['seco_limite'] ?? 0,
        "humido" => $config['humido_limite'] ?? 0
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
        :root { --primary: #2d5a27; --accent: #4caf50; --bg: #f0f7f0; --text: #1a3317; }
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
    </style>
</head>
<body>
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
        <h3>Configuração de Rega</h3>
        <form method="POST">
            <input type="hidden" name="id_vaso" value="<?php echo $id_vaso; ?>">
            
            <div class="config-group">
                <div class="config-item">
                    <label>Seco</label>
                    <input type="number" name="seco_limite" id="input-seco">
                </div>
                <div class="config-item">
                    <label>Húmido</label>
                    <input type="number" name="humido_limite" id="input-humido">
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

        function atualizar() {
            // Descobre o ID do vaso atual a partir da URL do browser
            const urlParams = new URLSearchParams(window.location.search);
            const idVaso = urlParams.get('id') || 1;

            // Faz o pedido AJAX enviando o ID correto
            fetch('recebe.php?ajax=1&id=' + idVaso)
                .then(r => r.json())
                .then(data => {
                    const v = parseInt(data.percentagem);
                    gaugeChart.data.datasets[0].data = [v, 100 - v];
                    gaugeChart.update();
                    
                    document.getElementById('humidade-valor').innerText = v + "%";
                    document.getElementById('hora-atualizacao').innerText = "Leitura: " + data.hora;
                    
                    // Só atualiza os inputs se o utilizador não estiver a escrever neles
                    if(document.activeElement.tagName !== "INPUT") {
                        document.getElementById('input-seco').value = data.seco;
                        document.getElementById('input-humido').value = data.humido;
                    }
                });
        }

        // Corre a atualização a cada 5 segundos
        setInterval(atualizar, 5000); 
        atualizar();
    </script>
</body>
</html>