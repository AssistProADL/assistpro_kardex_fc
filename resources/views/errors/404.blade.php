<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página No Encontrada | AssistPro ER®</title>

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --ap-blue: #000099;
            --ap-blue-light: #0000bb;
            --ap-text-gray: #6c757d;
            --ap-border: #e9ecef;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #2d3748;
        }

        .error-container {
            width: 100%;
            max-width: 650px;
            text-align: center;
            animation: fadeInUp 0.7s ease-out;
        }

        .logo-section {
            margin-bottom: 3rem;
        }

        .logo-img {
            height: 55px;
        }

        .error-illustration {
            margin: 2rem 0 3rem;
            position: relative;
        }

        .error-code {
            font-size: 160px;
            font-weight: 800;
            line-height: 1;
            color: var(--ap-blue);
            letter-spacing: -6px;
            opacity: 0.1;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 0;
        }

        .illustration-icon {
            font-size: 100px;
            position: relative;
            z-index: 1;
            display: inline-block;
        }

        h1 {
            font-size: 36px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 1rem;
            letter-spacing: -0.5px;
        }

        .error-message {
            font-size: 18px;
            color: var(--ap-text-gray);
            line-height: 1.7;
            margin-bottom: 3rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .divider {
            width: 80px;
            height: 3px;
            background: var(--ap-blue);
            margin: 2.5rem auto;
        }

        .buttons-wrapper {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2.5rem;
        }

        .btn {
            padding: 1rem 2.25rem;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.625rem;
            border: none;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
        }

        .btn-primary {
            background-color: var(--ap-blue);
            color: white;
            box-shadow: 0 2px 8px rgba(0, 0, 153, 0.15);
        }

        .btn-primary:hover {
            background-color: var(--ap-blue-light);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 153, 0.25);
        }

        .btn-secondary {
            background-color: white;
            color: var(--ap-blue);
            border: 2px solid var(--ap-blue);
        }

        .btn-secondary:hover {
            background-color: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .btn:active {
            transform: translateY(0);
        }

        .info-section {
            margin-top: 4rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid var(--ap-blue);
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .info-title {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            color: var(--ap-blue);
            font-weight: 600;
            font-size: 15px;
            margin-bottom: 0.75rem;
        }

        .info-text {
            font-size: 14px;
            color: var(--ap-text-gray);
            line-height: 1.6;
        }

        .footer {
            margin-top: 4rem;
            padding-top: 2rem;
            border-top: 1px solid var(--ap-border);
            font-size: 13px;
            color: var(--ap-text-gray);
        }

        .footer strong {
            color: #1a202c;
            font-weight: 600;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .error-code {
                font-size: 120px;
            }

            .illustration-icon {
                font-size: 80px;
            }

            h1 {
                font-size: 28px;
            }

            .error-message {
                font-size: 16px;
            }

            .buttons-wrapper {
                flex-direction: column;
                align-items: stretch;
                padding: 0 1rem;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="error-container">
        <div class="logo-section">
            <img src="/assistpro_kardex_fc/public/assets/logo/assistpro-er.svg" alt="AssistPro ER" class="logo-img">
        </div>

        <div class="error-illustration">
            <div class="error-code">404</div>
            <div class="illustration-icon">🔍</div>
        </div>

        <h1>Página No Encontrada</h1>

        <div class="divider"></div>

        <p class="error-message">
            Lo sentimos, la página que está buscando no existe o ha sido movida.
            Por favor, utilice los enlaces a continuación para continuar navegando.
        </p>

        <div class="buttons-wrapper">
            <a href="/assistpro_kardex_fc/public/login.php" class="btn btn-primary">
                <i class="bi bi-house-door"></i>
                Volver al Inicio
            </a>
            <a href="/assistpro_kardex_fc/public/dashboard/index.php" class="btn btn-secondary">
                <i class="bi bi-speedometer2"></i>
                Ir al Dashboard
            </a>
        </div>

        <div class="info-section">
            <div class="info-title">
                <i class="bi bi-info-circle"></i>
                Información
            </div>
            <div class="info-text">
                Si cree que esto es un error del sistema, por favor contacte al administrador
                o intente acceder desde el menú principal de la aplicación.
            </div>
        </div>

        <div class="footer">
            <strong>AssistPro ER®</strong> - Business Intelligence Suite<br>
            &copy; {{ date('Y') }} Adventech Logística. Todos los derechos reservados.
        </div>
    </div>
</body>

</html>