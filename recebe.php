<?php
require "db.php"; 
session_start(); 
date_default_timezone_set('Europe/Lisbon');

// Se não houver sessão, podes redirecionar para o login aqui (opcional)
// if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

if (isset($_POST['update_config'])) {
    $seco = intval($_POST['seco_limite']);
    $humido = intval($_POST['humido_limite']);
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
        :root { 
            --primary: #2d5a27; 
            --accent: #4caf50;
            --bg: #f0f7f0; 
            --white: #ffffff;
            --text: #1a3317;
        }

        * { box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body { 
            background: radial-gradient(circle at top right, #e8f5e9, var(--bg));
            margin: 0; 
            padding: 20px; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            min-height: 100vh;
            color: var(--text);
        }

        .header { 
            width: 100%; 
            max-width: 450px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
        }

        .logo-text { font-weight: 800; font-size: 1.4rem; color: var(--primary); letter-spacing: -1px; }
        .btn-logout { text-decoration: none; color: #cc4444; font-size: 0.85rem; font-weight: 600; padding: 8px 15px; background: rgba(204,68,68,0.1); border-radius: 12px; transition: 0.3s; }
        .btn-logout:hover { background: rgba(204,68,68,0.2); }

        .card { 
            background: rgba(255, 255, 255, 0.8); 
            backdrop-filter: blur(10px);
            padding: 30px; 
            border-radius: 35px; 
            width: 100%; 
            max-width: 450px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.06); 
            margin-bottom: 25px; 
            border: 1px solid rgba(255,255,255,0.5);
            text-align: center;
        }

        h3 { margin-top: 0; font-size: 1.1rem; font-weight: 600; color: #444; margin-bottom: 20px; }

        .gauge-container { 
            position: relative; 
            width: 85%; 
            margin: 0 auto 10px; 
        }

        .value-display { 
            position: absolute; 
            top: 65%; 
            left: 50%; 
            transform: translate(-50%, -50%); 
            font-size: 3rem; 
            font-weight: 800; 
            color: var(--primary);
        }

        .update-tag { 
            font-size: 0.75rem; 
            background: #e1ede0; 
            color: var(--primary); 
            padding: 5px 12px; 
            border-radius: 20px; 
            display: inline-block;
            margin-top: 5px;
        }

        .config-group { 
            display: flex; 
            gap: 15px; 
            margin-bottom: 25px;
        }

        .config-item {
            flex: 1;
            background: #f9fbf9;
            padding: 15px;
            border-radius: 20px;
            border: 1px solid #eee;
        }

        .config-item label { display: block; font-size: 0.7rem; font-weight: 600; color: #888; text-transform: uppercase; margin-bottom: 8px; }

        input[type="number"] { 
            width: 100%; 
            background: transparent; 
            border: none; 
            font-size: 1.5rem; 
            font-weight: 700; 
            color: var(--primary); 
            text-align: center;
            outline: none;
        }

        .btn-update { 
            background: linear-gradient(135deg, var(--primary), var(--accent)); 
            color: white; 
            border: none; 
            padding: 18px; 
            width: 100%; 
            border-radius: 20px; 
            font-weight: 700; 
            font-size: 1rem;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(45,90,39,0.2);
            transition: 0.3s;
        }

        .btn-update:hover { transform: translateY(-2px); box-shadow: 0 15px 25px rgba(45,90,39,0.3); }

    </style>
</head>
<body>
    <div class="header">
        <span class="logo-text">GreenBuddy</span>
        <a href="logout.php" class="btn-logout">terminar sessão</a>
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
        <h3 style="text-align: left;">Configuração de Rega</h3>
        <form method="POST">
            <div class="config-group">
                <div class="config-item">
                    <label>Seco</label>
                    <input type="number" name="seco_limite" id="input-seco" placeholder="0">
                </div>
                <div class="config-item">
                    <label>Húmido</label>
                    <input type="number" name="humido_limite" id="input-humido" placeholder="0">
                </div>
            </div>
            <button type="submit" name="update_config" class="btn-update">Guardar Alterações</button>
        </form>
    </div>

    <script>
        const ctx = document.getElementById('gaugeChart').getContext('2d');
        
        // Criar um gradiente para o gráfico
        const gradient = ctx.createLinearGradient(0, 0, 0, 200);
        gradient.addColorStop(0, '#4caf50');
        gradient.addColorStop(1, '#2d5a27');

        const gaugeChart = new Chart(ctx, {
            type: 'doughnut',
            data: { 
                datasets: [{ 
                    data: [0, 100], 
                    backgroundColor: [gradient, '#f0f0f0'], 
                    borderWidth: 0,
                    circumference: 180, 
                    rotation: 270, 
                    cutout: '85%',
                    borderRadius: 20
                }] 
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: true,
                animation: { duration: 2000, easing: 'easeOutQuart' },
                plugins: { legend: { display: false }, tooltip: { enabled: false } } 
            }
        });

        function atualizar() {
            fetch('recebe.php?ajax=1')
                .then(r => r.json())
                .then(data => {
                    const v = parseInt(data.percentagem);
                    gaugeChart.data.datasets[0].data = [v, 100 - v];
                    gaugeChart.update();
                    
                    document.getElementById('humidade-valor').innerText = v + "%";
                    document.getElementById('hora-atualizacao').innerText = "Leitura: " + data.hora;
                    
                    if(document.activeElement.tagName !== "INPUT") {
                        document.getElementById('input-seco').value = data.seco;
                        document.getElementById('input-humido').value = data.humido;
                    }
                }).catch(e => console.error("Erro na leitura"));
        }

        setInterval(atualizar, 5000); 
        atualizar();
    </script>
</body>
</html>