<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenBuddy - O Teu Vaso Inteligente</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { margin: 0; min-height: 100vh; overflow-x: hidden; }

        /* NAVBAR COM BLUR E POSIÇÃO FIXA */
        .navbar {
            width: 100%;
            padding: 20px 60px;
            display: flex;
            justify-content: space-between; 
            align-items: center;
            position: fixed; 
            top: 0;
            left: 0;
            box-sizing: border-box;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logo-mini img { 
            height: 70px; 
            display: block; 
        }

        /* O BOTÃO APETITOSO (RESTAURADO E MELHORADO) */
        .btn-login {
            background: linear-gradient(45deg, #2d5a27, #4caf50);
            color: white;
            padding: 14px 35px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-size: 0.85rem;
            box-shadow: 0 4px 15px rgba(45, 90, 39, 0.4), inset 0 0 10px rgba(255,255,255,0.2);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .btn-login:hover {
            transform: scale(1.1) translateY(-3px);
            box-shadow: 0 12px 25px rgba(76, 175, 80, 0.5);
            background: linear-gradient(45deg, #3e7a36, #66bb6a);
        }

        /* Efeito de brilho "passante" */
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(120deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: all 0.6s;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        /* CONTEÚDO DESCIDO PARA EVITAR ESPAÇO VAZIO */
        .hero {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 250px 10% 100px; 
            gap: 60px;
            flex-wrap: wrap;
        }

        .hero-text { 
            max-width: 600px; 
            background: rgba(255, 255, 255, 0.6);
            padding: 40px;
            border-radius: 30px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            animation: fadeInSlide 1s ease-out;
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
        }

        .hero-text h1 { 
            font-size: 4.5rem; 
            color: #1b3d17; 
            margin-bottom: 20px;
            letter-spacing: -3px;
            line-height: 1;
        }

        .product-img img {
            max-width: 550px;
            filter: drop-shadow(0 30px 60px rgba(0,0,0,0.15));
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(2deg); }
        }

        @keyframes fadeInSlide {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @media (max-width: 768px) {
            .navbar { padding: 15px 30px; }
            .hero { padding-top: 150px; }
            .hero-text h1 { font-size: 3rem; }
            .product-img img { max-width: 320px; }
        }
    </style>
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

</body>
</html>