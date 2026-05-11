<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenBuddy - O Teu Vaso Inteligente</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Estilos específicos da Landing Page com Design Moderno */
        body {
            /* Fundo com degradê moderno em vez de branco chapado */
            background: linear-gradient(135deg, #f0f4f1 0%, #d9e8d1 100%);
            margin: 0;
            min-height: 100vh;
        }

        .navbar {
            width: 100%;
            padding: 30px 60px;
            display: flex;
            justify-content: flex-end; /* Empurra o botão para a direita */
            align-items: center;
            position: absolute;
            top: 0;
            box-sizing: border-box;
            z-index: 100;
        }

        .logo-mini {
            position: absolute;
            left: 50%;
            transform: translateX(-50%); /* Garante o centro perfeito na horizontal */
        }

        .logo-mini img {
            height: 80px; /* Tamanho atualizado para 80px */
            display: block;
        }

        .btn-login {
            background: #2d5a27;
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(45, 90, 39, 0.2);
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: #3e7a36;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(45, 90, 39, 0.3);
        }

        .hero {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 180px 10% 80px; /* Aumentado ligeiramente para acomodar o logo de 80px */
            gap: 50px;
            flex-wrap: wrap;
        }

        .hero-text { 
            max-width: 550px; 
            background: rgba(255, 255, 255, 0.4); /* Efeito de vidro */
            padding: 40px;
            border-radius: 30px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .hero-text h1 { 
            font-size: 4rem; 
            color: #1b3d17; 
            margin-bottom: 20px;
            letter-spacing: -2px;
        }

        .hero-text p { 
            font-size: 1.25rem; 
            color: #444; 
            line-height: 1.8; 
            margin-bottom: 30px;
        }

        .product-img {
            position: relative;
        }

        .product-img img {
            max-width: 500px;
            filter: drop-shadow(0 30px 50px rgba(0,0,0,0.15));
            animation: float 6s ease-in-out infinite;
        }

        /* Animação para o vaso flutuar levemente */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }

        .price-tag {
            font-size: 2.5rem;
            color: #2d5a27;
            font-weight: 800;
            margin-bottom: 25px;
            display: block;
        }

        .btn-buy {
            background: #ffc107;
            color: #1b3d17;
            padding: 20px 50px;
            border-radius: 15px;
            text-decoration: none;
            font-size: 1.4rem;
            font-weight: bold;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(255, 193, 7, 0.2);
        }

        .btn-buy:hover {
            background: #ffca2c;
            transform: scale(1.05);
            box-shadow: 0 15px 30px rgba(255, 193, 7, 0.3);
        }

        /* Ajuste para ecrãs pequenos */
        @media (max-width: 768px) {
            .navbar { padding: 20px; }
            .hero-text h1 { font-size: 2.5rem; }
            .product-img img { max-width: 300px; }
            .logo-mini { position: relative; left: 0; transform: none; margin-bottom: 15px; }
            .navbar { flex-direction: column; position: relative; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="logo-mini">
            <img src="img/logotipo_PAP.png" alt="Logo">
        </div>

        <a href="login.php" class="btn-login">Iniciar Sessão</a>
    </nav>

    <section class="hero">
        <div class="hero-text">
            <h1>GreenBuddy</h1>
            <p>Nunca mais deixes as tuas plantas morrerem. O sistema de rega inteligente que cuida do que é importante para ti, de forma automática e controlada pelo telemóvel.</p>
            <span class="price-tag">49,99€</span>
            <a href="#" class="btn-buy">Comprar Agora</a>
        </div>
        
        <div class="product-img">
            <img src="img/logotipo_PAP.png" alt="GreenBuddy Vaso">
        </div>
    </section>

</body>
</html>