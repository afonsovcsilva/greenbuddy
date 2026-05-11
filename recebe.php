<?php
require "db.php"; 
session_start(); 

// Segurança: Redireciona se não houver sessão (opcional, mas recomendado)
if (!isset($_SESSION['user_id']) && !isset($_GET['humidade'])) {
    // header("Location: login.php"); // Descomenta esta linha se quiseres forçar login
}

date_default_timezone_set('Europe/Lisbon');

// 1. LÓGICA DE ATUALIZAÇÃO DE CONFIGURAÇÃO (SITE)
if (isset($_POST['update_config'])) {
    $seco = $_POST['seco_limite'];
    $humido = $_POST['humido_limite'];
    $conn->query("UPDATE vaso_config SET seco_limite = $seco, humido_limite = $humido WHERE id = 1");
    header("Location: recebe.php"); 
    exit;
}

// 2. LÓGICA DE INSERÇÃO E RESPOSTA (ARDUINO)
$humidade = $_GET['humidade'] ?? null;
if ($humidade !== null) {
    $data = date("Y-m-d");
    $hora = date("H:i:s");

    $stmt = $conn->prepare("INSERT INTO vaso_humidade (data, hora, percentagem) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $data, $hora, $humidade);
    $stmt->execute();
    $stmt->close();
    
    $res_config = $conn->query("SELECT * FROM vaso_config WHERE id = 1");
    $config = $res_config->fetch_assoc();
    echo "CONF_SECO:" . $config['seco_limite'] . "|CONF_HUMIDO:" . $config['humido_limite'];
    exit;
}

// 3. LÓGICA PARA O AJAX (DASHBOARD)
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    $res_atual = $conn->query("SELECT percentagem, hora, data FROM vaso_humidade ORDER BY id_humidade DESC LIMIT 1");
    $dados = $res_atual->fetch_assoc();

    $res_config = $conn->query("SELECT * FROM vaso_config WHERE id = 1");
    $config = $res_config->fetch_assoc();

    $sql_rega = "SELECT data, hora FROM vaso_humidade WHERE percentagem >= " . $config['humido_limite'] . " ORDER BY id_humidade DESC LIMIT 1";
    $res_rega = $conn->query($sql_rega);
    $ultima_rega = $res_rega->fetch_assoc();

    echo json_encode([
        "percentagem" => $dados['percentagem'] ?? 0,
        "hora" => $dados['hora'] ?? "--:--",
        "seco" => $config['seco_limite'],
        "humido" => $config['humido_limite'],
        "ultima_rega" => $ultima_rega ? date('d/m H:i', strtotime($ultima_rega['data'] . " " . $ultima_rega['hora'])) : "Sem registo"
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
        :root {
            --primary: #2d5a27;
            --accent: #007bff;
            --danger: #dc3545;
            --bg: linear-gradient(135deg, #f0f4f1 0%, #d9e8d1 100%);
        }

        body { 
            font-family: 'Segoe UI', system-ui, sans-serif; 
            background: var(--bg); 
            margin: 0; 
            padding: 20px; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            min-height: 100vh;
            color: #1b3d17;
        }
        
        .logout-container { 
            width: 100%; 
            max-width: 450px; 
            display: flex; 
            justify-content: flex-end; 
            margin-bottom: 15px; 
        }

        .btn-sair { 
            background: rgba(220, 53, 69, 0.1); 
            color: var(--danger); 
            text-decoration: none; 
            padding: 10px 20px; 
            border-radius: 12px; 
            font-weight: bold; 
            font-size: 0.9rem; 
            transition: 0.3s;
            border: 1px solid var(--danger);
        }

        .btn-sair:hover { 
            background: var(--danger); 
            color: white;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        /* Cartão com efeito de vidro */
        .card { 
            background: rgba(255, 255, 255, 0.7); 
            backdrop-filter: blur(10px);
            padding: 2.5rem; 
            border-radius: 30px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.05); 
            text-align: center; 
            width: 100%; 
            max-width: 450px; 
            margin-bottom: 25px; 
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-sizing: border-box;
        }

        h2 { margin-top: 0; font-weight: 700; color: var(--primary); }

        .gauge-container { 
            position: relative; 
            margin: 20px auto; 
            width: 100%;
        }

        .value-display { 
            position: absolute; 
            top: 65%; 
            left: 50%; 
            transform: translate(-50%, -50%); 
            font-size: 3.5rem; 
            font-weight: 800; 
        }

        .info-rega { 
            margin-top: 20px; 
            padding: 18px; 
            background: rgba(255, 255, 255, 0.5);
            border-radius: 18px; 
            border-left: 6px solid var(--accent); 
            text-align: left; 
            font-size: 0.95rem;
        }

        .config-panel { 
            background: rgba(255, 255, 255, 0.8); 
            padding: 2rem; 
            border-radius: 30px; 
            width: 100%; 
            max-width: 450px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.04);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-sizing: border-box;
        }

        .config-row { 
            display: flex; 
            justify-content: space-between; 
            margin: 15px 0; 
            align-items: center; 
            font-weight: 600;
        }

        input[type="number"] { 
            width: 70px; 
            padding: 10px; 
            border-radius: 12px; 
            border: 1px solid rgba(0,0,0,0.1); 
            text-align: center; 
            font-size: 1rem;
            font-weight: bold;
            background: white;
        }

        .btn-update { 
            background: var(--primary); 
            color: white; 
            border: none; 
            padding: 15px; 
            width: 100%; 
            border-radius: 15px; 
            cursor: pointer; 
            font-weight: bold; 
            font-size: 1rem;
            margin-top: 15px; 
            transition: 0.3s;
            box-shadow: 0 8px 16px rgba(45, 90, 39, 0.2);
        }

        .btn-update:hover { 
            background: #3e7a36; 
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(45, 90, 39, 0.3);
        }

        #hora-atualizacao { color: #888; font-style: italic; }
    </style>
</head>
<body>

<div class="logout-container">
    <a href="logout.php" class="btn-sair">Terminar Sessão</a>
</div>

<div class="card">
    <h2>Estado do Vaso</h2>
    <div class="gauge-container">
        <canvas id="gaugeChart"></canvas>
        <div class="value-display" id="humidade-valor">--%</div>
    </div>
    
    <div class="info-rega">
        <small style="color:#666; display:block; margin-bottom: 4px;">🚿 Última Atividade de Rega:</small>
        <span id="txt-ultima-rega" style="font-weight:bold; color:var(--accent); font-size: 1.1rem;">A carregar...</span>
    </div>
    
    <p style="font-size: 0.85rem; margin-top: 20px;" id="hora-atualizacao">Sincronizando dados...</p>
</div>

<div class="config-panel">
    <h3 style="margin-top:0; color: var(--primary);">⚙️ Configurações</h3>
    <form method="POST">
        <div class="config-row">
            <span>Solo Seco <small>(Ligar)</small>:</span>
            <input type="number" name="seco_limite" id="input-seco" min="0" max="100">
        </div>
        <div class="config-row">
            <span>Solo Húmido <small>(Parar)</small>:</span>
            <input type="number" name="humido_limite" id="input-humido" min="0" max="100">
        </div>
        <button type="submit" name="update_config" class="btn-update">Guardar Alterações</button>
    </form>
</div>

<script>
    const ctx = document.getElementById('gaugeChart').getContext('2d');
    const gaugeChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [0, 100],
                backgroundColor: ['#2d5a27', '#e9ecef'],
                circumference: 180,
                rotation: 270,
                cutout: '82%',
                borderRadius: 20
            }]
        },
        options: { 
            responsive: true, 
            aspectRatio: 1.6, 
            plugins: { legend: { display: false } },
            animation: { duration: 1500, easing: 'easeOutQuart' }
        }
    });

    function atualizar() {
        fetch('recebe.php?ajax=1')
            .then(r => r.json())
            .then(data => {
                const v = parseInt(data.percentagem);
                gaugeChart.data.datasets[0].data = [v, 100 - v];
                
                // Mudar cor conforme estado
                let cor = '#28a745'; // Verde (OK)
                if (v < data.seco) cor = '#dc3545'; // Vermelho (Seco)
                else if (v < data.humido) cor = '#ffc107'; // Amarelo (Médio)
                
                gaugeChart.data.datasets[0].backgroundColor[0] = cor;
                gaugeChart.update();

                document.getElementById('humidade-valor').innerText = v + "%";
                document.getElementById('humidade-valor').style.color = cor;
                document.getElementById('txt-ultima-rega').innerText = data.ultima_rega;
                document.getElementById('hora-atualizacao').innerText = "Vaso sincronizado às: " + data.hora;
                
                // Só atualiza os inputs se o utilizador não estiver a escrever
                if(document.activeElement.tagName !== "INPUT") {
                    document.getElementById('input-seco').value = data.seco;
                    document.getElementById('input-humido').value = data.humido;
                }
            });
    }

    setInterval(atualizar, 3000);
    atualizar();
</script>
</body>
</html>