<?php
require "db.php"; 
session_start(); 
date_default_timezone_set('Europe/Lisbon');

if (isset($_POST['update_config'])) {
    $seco = $_POST['seco_limite'];
    $humido = $_POST['humido_limite'];
    $conn->query("UPDATE vaso_config SET seco_limite = $seco, humido_limite = $humido WHERE id = 1");
    header("Location: recebe.php"); exit;
}

$humidade = $_GET['humidade'] ?? null;
if ($humidade !== null) {
    $data = date("Y-m-d"); $hora = date("H:i:s");
    $stmt = $conn->prepare("INSERT INTO vaso_humidade (data, hora, percentagem) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $data, $hora, $humidade);
    $stmt->execute();
    $res_config = $conn->query("SELECT * FROM vaso_config WHERE id = 1");
    $config = $res_config->fetch_assoc();
    echo "CONF_SECO:" . $config['seco_limite'] . "|CONF_HUMIDO:" . $config['humido_limite'];
    exit;
}

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $res_atual = $conn->query("SELECT percentagem, hora, data FROM vaso_humidade ORDER BY id_humidade DESC LIMIT 1");
    $dados = $res_atual->fetch_assoc();
    $res_config = $conn->query("SELECT * FROM vaso_config WHERE id = 1");
    $config = $res_config->fetch_assoc();
    echo json_encode([
        "percentagem" => $dados['percentagem'] ?? 0,
        "hora" => $dados['hora'] ?? "--:--",
        "seco" => $config['seco_limite'],
        "humido" => $config['humido_limite']
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenBuddy - Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --primary: #2d5a27; --bg: #f4f7f4; }
        body { font-family: sans-serif; background: var(--bg); margin: 0; padding: 15px; display: flex; flex-direction: column; align-items: center; }
        .header { width: 100%; max-width: 500px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card { background: white; padding: 20px; border-radius: 20px; width: 100%; max-width: 500px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 20px; box-sizing: border-box; text-align: center;}
        .gauge-container { position: relative; width: 100%; margin: 10px 0; }
        .value-display { position: absolute; top: 60%; left: 50%; transform: translate(-50%, -50%); font-size: 2.5rem; font-weight: bold; }
        .config-row { display: flex; justify-content: space-between; align-items: center; margin: 15px 0; }
        input[type="number"] { width: 60px; padding: 8px; border-radius: 8px; border: 1px solid #ddd; text-align: center; }
        .btn-update { background: var(--primary); color: white; border: none; padding: 15px; width: 100%; border-radius: 12px; font-weight: bold; }
        .btn-logout { text-decoration: none; color: #d33; font-size: 0.9rem; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <span style="font-weight: bold; color: var(--primary);">GreenBuddy App</span>
        <a href="logout.php" class="btn-logout">Sair</a>
    </div>

    <div class="card">
        <h3>Humidade do Solo</h3>
        <div class="gauge-container">
            <canvas id="gaugeChart"></canvas>
            <div class="value-display" id="humidade-valor">--%</div>
        </div>
        <p id="hora-atualizacao" style="font-size: 0.8rem; color: #888;">A carregar dados...</p>
    </div>

    <div class="card">
        <h3 style="text-align: left;">Configurações</h3>
        <form method="POST">
            <div class="config-row"><span>Limite Seco:</span><input type="number" name="seco_limite" id="input-seco"></div>
            <div class="config-row"><span>Limite Húmido:</span><input type="number" name="humido_limite" id="input-humido"></div>
            <button type="submit" name="update_config" class="btn-update">Guardar Configuração</button>
        </form>
    </div>

    <script>
        const ctx = document.getElementById('gaugeChart').getContext('2d');
        const gaugeChart = new Chart(ctx, {
            type: 'doughnut',
            data: { datasets: [{ data: [0, 100], backgroundColor: ['#2d5a27', '#eee'], circumference: 180, rotation: 270, cutout: '80%' }] },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });

        function atualizar() {
            fetch('recebe.php?ajax=1').then(r => r.json()).then(data => {
                const v = parseInt(data.percentagem);
                gaugeChart.data.datasets[0].data = [v, 100-v];
                gaugeChart.update();
                document.getElementById('humidade-valor').innerText = v + "%";
                document.getElementById('hora-atualizacao').innerText = "Última leitura: " + data.hora;
                if(document.activeElement.tagName !== "INPUT") {
                    document.getElementById('input-seco').value = data.seco;
                    document.getElementById('input-humido').value = data.humido;
                }
            });
        }
        setInterval(atualizar, 5000); atualizar();
    </script>
</body>
</html>