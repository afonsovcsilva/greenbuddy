<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenBuddy - Vaso Inexistente</title>
    <style>
        body { 
            background: #f0f7f0; 
            font-family: 'Segoe UI', sans-serif; 
            margin: 0; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
        }
        .error-card {
            background: white;
            padding: 40px;
            border-radius: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        .icon { font-size: 60px; margin-bottom: 15px; }
        h2 { color: #c62828; margin-top: 0; }
        p { color: #666; font-size: 0.95rem; line-height: 1.5; margin-bottom: 25px; }
        .btn-voltar {
            display: inline-block;
            background: #2d5a27;
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: bold;
            transition: 0.3s;
        }
        .btn-voltar:hover { background: #3e7a36; }
    </style>
</head>
<body>

    <div class="error-card">
        <div class="icon">🔍❌</div>
        <h2>Vaso Inexistente</h2>
        <p>O endereço MAC introduzido não corresponde a nenhum dispositivo GreenBuddy fabricado ou registado no nosso sistema.</p>
        <a href="ativacao.php" class="btn-voltar">Voltar para os meus Vasos</a>
    </div>

</body>
</html>