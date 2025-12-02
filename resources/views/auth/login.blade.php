<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Login · AssistPro ER®</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --ap-blue: #000099;
            --ap-bg-input: #f8f9fa;
            --ap-border-input: #e9ecef;
            --ap-text-gray: #6c757d;
        }

        body {
            min-height: 100vh;
            background: url('/assistpro_kardex_fc/public/assets/br/warehouse-br.jpg') center/cover no-repeat fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            color: #333;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 2.5rem;
            background-color: #ffffff;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            margin: 1rem;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-img {
            height: 60px;
            margin-bottom: 1.5rem;
        }

        .app-title {
            color: var(--ap-blue);
            font-weight: 700;
            font-size: 1.75rem;
            margin-bottom: 0.25rem;
            letter-spacing: -0.5px;
        }

        .app-subtitle {
            color: #444;
            font-size: 1rem;
            font-weight: 400;
        }

        /* Form Styles */
        .input-group-custom {
            margin-bottom: 1.5rem;
        }

        .input-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper .form-control {
            background-color: var(--ap-bg-input);
            border: 1px solid var(--ap-border-input);
            border-radius: 12px;
            padding: 0.75rem 1rem 0.75rem 3rem;
            height: 54px;
            font-size: 1rem;
            color: #333;
            box-shadow: none;
            transition: all 0.2s ease;
        }

        .input-wrapper .form-control:focus {
            border-color: var(--ap-blue);
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(0, 0, 153, 0.1);
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 1.2rem;
            z-index: 10;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            cursor: pointer;
            z-index: 10;
            padding: 5px;
        }

        .btn-login {
            background-color: var(--ap-blue);
            color: #fff;
            border: none;
            border-radius: 12px;
            height: 54px;
            width: 100%;
            font-weight: 600;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            margin-top: 1rem;
        }

        .btn-login:hover {
            background-color: #000077;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 153, 0.2);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .footer-text {
            text-align: center;
            margin-top: 3rem;
            font-size: 0.85rem;
            color: var(--ap-text-gray);
            line-height: 1.5;
        }

        .alert-custom {
            border-radius: 12px;
            font-size: 0.9rem;
            border: none;
            background-color: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>

<body>

    <div class="login-container">

        <div class="logo-section">
            <img src="/assistpro_kardex_fc/public/assets/logo/assistpro-er.svg" alt="AssistPro ER" class="logo-img">
            <div class="app-title">AssistPro ER®</div>
            <div class="app-subtitle">Business Intelligence Suite</div>
        </div>

        @if(!empty($error))
            <div class="alert alert-custom d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <div>{{ $error }}</div>
            </div>
        @endif

        <form id="frmLogin" autocomplete="off">

            <div class="input-group-custom">
                <label class="input-label">Usuario</label>
                <div class="input-wrapper">
                    <i class="bi bi-person input-icon"></i>
                    <input type="text" class="form-control" id="user" name="user" placeholder="Ingrese su usuario"
                        required value="{{ $username ?? '' }}" autofocus>
                </div>
            </div>

            <div class="input-group-custom">
                <label class="input-label">Contraseña</label>
                <div class="input-wrapper">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" class="form-control" id="pass" name="pass"
                        placeholder="Ingrese su contraseña" required>
                    <i class="bi bi-eye password-toggle" id="togglePass"></i>
                </div>
            </div>

            <button type="submit" class="btn btn-login" id="btnLogin">
                INICIAR SESIÓN <i class="bi bi-arrow-right"></i>
            </button>

            <div class="footer-text">
                &copy; {{ date('Y') }} Adventech Logística.<br>
                Todos los derechos reservados.
            </div>
        </form>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        $(document).ready(function () {
            // Toggle Password Visibility
            $('#togglePass').on('click', function () {
                const input = $('#pass');
                const icon = $(this);

                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    icon.removeClass('bi-eye').addClass('bi-eye-slash');
                } else {
                    input.attr('type', 'password');
                    icon.removeClass('bi-eye-slash').addClass('bi-eye');
                }
            });

            $('#frmLogin').on('submit', function (e) {
                e.preventDefault();

                var $btn = $('#btnLogin');
                var $user = $('#user');
                var $pass = $('#pass');
                var $alert = $('.alert');

                // Reset errors
                if ($alert.length) $alert.remove();

                // Loading state
                var originalText = $btn.html();
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Cargando...');

                console.log('Sending login request:', {
                    user: $user.val(),
                    pass: '***'
                });

                $.ajax({
                    url: '/assistpro_kardex_fc/public/api/login',
                    method: 'POST',
                    data: {
                        user: $user.val(),
                        pass: $pass.val()
                    },
                    dataType: 'json',
                    success: function (resp) {
                        console.log('Success response:', resp);
                        if (resp.success) {
                            window.location.href = resp.data.redirect;
                        } else {
                            showError(resp.message || 'Error desconocido');
                            $btn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX Error:', {
                            status: xhr.status,
                            statusText: xhr.statusText,
                            responseText: xhr.responseText,
                            error: error
                        });
                        var msg = 'Error de conexión';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        } else if (xhr.responseText) {
                            msg = 'Error del servidor: ' + xhr.status;
                        }
                        showError(msg);
                        $btn.prop('disabled', false).html(originalText);
                    }
                });
            });

            function showError(msg) {
                var html = `
                    <div class="alert alert-custom d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                        <div>${msg}</div>
                    </div>
                `;
                $('.logo-section').after(html);
            }
        });
    </script>

</body>

</html>