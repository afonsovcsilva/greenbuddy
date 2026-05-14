<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenBuddy - O Teu Vaso Inteligente</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { margin: 0; min-height: 100vh; overflow-x: hidden; }

        /* NAVBAR MOBILE FIRST */
        .navbar {
            width: 100%;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between; 
            align-items: center;
            position: fixed; 
            top: 0;
            left: 0;
            box-sizing: border-box;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        @media (min-width: 768px) {
            .navbar { padding: 20px 60px; }
        }

        .logo-mini img { 
            height: 50px; 
            display: block; 
        }

        @media (min-width: 768px) {
            .logo-mini img { height: 70px; }
        }

        .btn-login {
            background: linear-gradient(45deg, #2d5a27, #4caf50);
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.75rem;
            box-shadow: 0 4px 15px rgba(45, 90, 39, 0.3);
            transition: all 0.4s ease;
        }

        @media (min-width: 768px) {
            .btn-login { padding: 14px 35px; font-size: 0.85rem; }
        }

        .btn-login:hover { transform: scale(1.05); }

        /* HERO SECTION - MOBILE FIRST (Coluna) */
        .hero {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 120px 20px 60px; 
            gap: 40px;
            text-align: center;
        }

        /* Ajuste para Desktop */
        @media (min-width: 900px) {
            .hero { 
                flex-direction: row; 
                text-align: left; 
                padding: 200px 10% 100px;
                gap: 60px;
            }
        }

        .hero-text { 
            width: 100%;
            background: rgba(255, 255, 255, 0.6);
            padding: 30px;
            border-radius: 30px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            animation: fadeInSlide 1s ease-out;
        }

        @media (min-width: 900px) {
            .hero-text { max-width: 550px; padding: 40px; }
        }

        .hero-text h1 { 
            font-size: 2.5rem; 
            color: #1b3d17; 
            margin-bottom: 15px;
            line-height: 1.1;
        }

        @media (min-width: 900px) {
            .hero-text h1 { font-size: 4.5rem; }
        }

        .features-list {
            list-style: none;
            padding: 0;
            margin: 20px 0;
            font-size: 0.9rem; 
            text-align: left;
        }

        .login-instruction-banner {
            display: block;
            background-color: #557a4e;
            color: white;
            padding: 12px 20px;
            border-radius: 50px;
            margin-top: 15px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .product-img img {
            width: 100%;
            max-width: 280px;
            filter: drop-shadow(0 20px 40px rgba(0,0,0,0.1));
            animation: float 6s ease-in-out infinite;
        }

        @media (min-width: 900px) {
            .product-img img { max-width: 550px; }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }

        @keyframes fadeInSlide {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="logo-mini">
            <img src="img/logotipo_PAP.png" alt="Logo GreenBuddy">
        </div>
        <a href="login.php" class="btn-login">Entrar</a>
    </nav>

    <section class="hero">
        <div class="hero-text">
            <h1>GreenBuddy</h1>
            <p>Um sistema de rega autónomo desenvolvido com <strong>tecnologia Arduino</strong>.</p>
            
            <ul class="features-list">
                <li><strong>Leitura de Humidade:</strong> Monitorização em tempo real.</li>
                <li><strong>Controlo Web:</strong> Define limites no teu telemóvel.</li>
                <li><strong>Rega Inteligente:</strong> Automação total 24/7.</li>
            </ul>

            <div class="login-instruction-banner">
                Clica em <strong>"Entrar"</strong> para gerir o teu vaso.
            </div>
        </div>
        
        <div class="product-img">
            <img src="img/logotipo_PAP.png" alt="GreenBuddy Logo">
        </div>
    </section>

</body>
</html>