<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenBuddy - O Teu Vaso Inteligente</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Estilos críticos de estrutura e animação de fundo */
        body { 
            margin: 0; 
            min-height: 100vh; 
            overflow-x: hidden;
            background: #f0f7f0; 
            position: relative;
        }

        /* Elementos Decorativos de Folhas no Fundo */
        .leaf-bg {
            position: fixed;
            z-index: -1;
            opacity: 0.15;
            filter: blur(2px);
            animation: floatLeaf 10s ease-in-out infinite;
        }

        @keyframes floatLeaf {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-40px) rotate(15deg); }
        }

        /* Navbar Premium */
        .navbar {
            width: 100%;
            padding: 25px 60px;
            display: flex;
            justify-content: space-between; 
            align-items: center;
            position: fixed; 
            top: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
        }

        .logo-mini img { height: 75px; }

        /* Botão Iniciar Sessão - Super Apetitoso */
        .btn-login {
            background: linear-gradient(135deg, #2d5a27 0%, #4caf50 100%);
            color: white;
            padding: 15px 40px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 0.85rem;
            box-shadow: 0 10px 20px rgba(45, 90, 39, 0.3), inset 0 4px 10px rgba(255,255,255,0.3);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .btn-login:hover {
            transform: scale(1.1) translateY(-5px);
            box-shadow: 0 15px 30px rgba(76, 175, 80, 0.4);
        }

        /* Conteúdo Principal */
        .hero {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 240px 10% 100px; 
            gap: 80px;
            flex-wrap: wrap;
        }

        .hero-text { 
            max-width: 620px; 
            background: rgba(255, 255, 255, 0.7);
            padding: 50px;
            border-radius: 40px;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 30px 60px rgba(0,0,0,0.08);
            animation: slideUp 1s ease-out;
        }

        .hero-text h1 { 
            font-size: 5rem; 
            color: #1b3d17; 
            margin-bottom: 25px;
            letter-spacing: -4px;
            background: linear-gradient(to bottom, #1b3d17, #2d5a27);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .product-img img {
            max-width: 580px;
            filter: drop-shadow(0 40px 80px rgba(0,0,0,0.2));
            animation: floatProduct 6s ease-in-out infinite;
        }

        @keyframes floatProduct {
            0%, 100% { transform: translateY(0) rotate(-2deg); }
            50% { transform: translateY(-30px) rotate(2deg); }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .navbar { padding: 15px 30px; }
            .hero-text h1 { font-size: 3.5rem; }
            .product-img img { max-width: 100%; }
        }
    </style>
</head>
<body>

    <div class="leaf-bg" style="top: 10%; left: 5%; width: 100px;">🌱</div>
    <div class="leaf-bg" style="top: 60%; left: 85%; width: 150px; animation-delay: 2s;">🌿</div>
    <div class="leaf-bg" style="top: 80%; left: 10%; width: 80px; animation-delay: 4s;">🍃</div>

    <nav class="navbar">
        <div class="logo-mini">
            <img src="img/logotipo_PAP.png" alt="Logo">
        </div>
        <a href="login.php" class="btn-login">Iniciar Sessão</a>
    </nav>

    <section class="hero">
        <div class="hero-text">
            <h1>GreenBuddy</h1>
            <p style="font-size: 1.2rem; color: #444; margin-bottom: 30px;">O futuro da rega doméstica, alimentado por <strong>Arduino</strong> e paixão pela natureza.</p>
            
            <ul class="features-list">
                <li><strong>Leitura de Humidade:</strong> Sensores de solo de alta precisão.</li>
                <li><strong>Controlo Remoto:</strong> Define os teus parâmetros onde quer que estejas.</li>
                <li><strong>Inteligência Autónoma:</strong> Rega apenas quando a planta realmente precisa.</li>
                <li><strong>Eco-Friendly:</strong> Otimização máxima do consumo de água.</li>
            </ul>

            <div class="login-instruction" style="margin-top: 30px;">
                ✨ Pronto para começar? Clica em <strong>Iniciar Sessão</strong> acima.
            </div>
        </div>
        
        <div class="product-img">
            <img src="img/logotipo_PAP.png" alt="GreenBuddy Vaso">
        </div>
    </section>

</body>
</html>