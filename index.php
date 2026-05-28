<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenBuddy - O Teu Vaso Inteligente</title>
    <link rel="stylesheet" href="style.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2d5a27">
</head>
<body>

    <nav class="navbar">
        <div class="logo-mini">
            <img src="img/logotipo_PAP.png" alt="Logo GreenBuddy">
        </div>
        <a href="login.php" class="btn-login">Iniciar Sessão</a>
    </nav>

    <section class="hero">
        <div class="hero-text">
            <h1>GreenBuddy</h1>
            <p>Um sistema de rega autónomo desenvolvido com <strong>tecnologia Arduino</strong>.</p>
            
            <ul class="features-list">
                <li><strong>Leitura de Humidade:</strong> Monitorização real do solo.</li>
                <li><strong>Controlo Personalizado:</strong> Define os teus limites via web.</li>
                <li><strong>Rega Inteligente:</strong> Ativação e paragem automática.</li>
                <li><strong>Ciclo Infinito:</strong> Automação total 24/7.</li>
            </ul>

            <p class="login-instruction">Para entrares no sistema, clica em <strong>"Iniciar Sessão"</strong> no topo da página.</p>
        </div>
        
        <div class="product-img">
            <img src="img/logotipo_PAP.png" alt="GreenBuddy Logo">
        </div>
    </section>

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js');
        }
    </script>
</body>
</html>
