<?php
session_start();

$host = "127.0.0.1";
$usuario = getenv('ALPERIT_DB_USER') ?: 'root';
$contrasena = getenv('ALPERIT_DB_PASSWORD') ?: '';
$base_datos = "peritaje_autos";

$conn = new mysqli($host, $usuario, $contrasena, $base_datos);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM usuarios WHERE email = '$email' LIMIT 1";
    $resultado = $conn->query($sql);

    if ($resultado && $resultado->num_rows > 0) {
        $user = $resultado->fetch_assoc();

        if ($password === $user['password']) {

            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['rol'] = $user['rol'];

            header("Location: index.php");
            exit();

        } else {
            $error = "Contraseña incorrecta";
        }

    } else {
        $error = "Usuario no encontrado";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | Alperit System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    margin: 0;
    min-height: 100vh;
    font-family: 'Segoe UI', sans-serif;
    background:
        radial-gradient(circle at 20% 20%, rgba(47,109,246,0.14), transparent 25%),
        radial-gradient(circle at 80% 80%, rgba(255,255,255,0.03), transparent 25%),
        linear-gradient(135deg, #040404, #0b1017 55%, #05070b);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    overflow: hidden;
}

.login-container {
    width: 100%;
    max-width: 430px;
    padding: 22px;
    animation: fadeIn 0.6s ease;
    position: relative;
    z-index: 2;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ===== CABECERA / MARCA ===== */
.brand {
    position: relative;
    margin-bottom: 34px;
    padding-top: 8px;
    text-align: center;
}

.brand::before {
    content: "";
    position: absolute;
    top: -6px;
    left: 50%;
    transform: translateX(-50%);
    width: 340px;
    height: 150px;
    background: radial-gradient(circle, rgba(255,255,255,0.18) 0%, rgba(255,255,255,0.08) 30%, rgba(255,255,255,0.025) 58%, transparent 76%);
    filter: blur(22px);
    pointer-events: none;
    z-index: 0;
}

.brand::after {
    content: "";
    position: absolute;
    top: 24px;
    left: 50%;
    transform: translateX(-50%);
    width: 310px;
    height: 70px;
    background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.11) 50%, transparent 100%);
    filter: blur(18px);
    opacity: 0.9;
    pointer-events: none;
    z-index: 0;
}

.brand-content {
    position: relative;
    z-index: 2;
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}

.brand-main {
    display: inline-flex;
    align-items: center;
    gap: 16px;
}

.brand-icon {
    width: 66px;
    height: 66px;
    display: flex;
    align-items: center;
    justify-content: center;
    filter: drop-shadow(0 0 16px rgba(255,255,255,0.18));
    flex-shrink: 0;
}

.brand-icon svg {
    width: 100%;
    height: 100%;
}

.brand-title {
    margin: 0;
    font-size: 30px;
    font-weight: 700;
    line-height: 1;
    letter-spacing: 0.3px;
    color: #ffffff;
    text-shadow: 0 0 20px rgba(255,255,255,0.07);
}

.brand-title .light {
    font-weight: 300;
    color: rgba(255,255,255,0.92);
}

.brand-subtitle {
    margin: 0;
    font-size: 13px;
    color: #9da8b8;
    letter-spacing: 0.35px;
}

/* ===== CARD ===== */
.card-login {
    background: rgba(12,14,19,0.88);
    padding: 32px;
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,0.05);
    box-shadow:
        0 24px 60px rgba(0,0,0,0.62),
        inset 0 1px 0 rgba(255,255,255,0.03);
    backdrop-filter: blur(12px);
}

.form-label {
    font-size: 13px;
    color: #d4dbea;
    margin-bottom: 7px;
}

.form-control {
    background: rgba(255,255,255,0.035);
    border: 1px solid rgba(255,255,255,0.08);
    color: #fff;
    border-radius: 11px;
    padding: 13px 14px;
    transition: all 0.2s ease;
}

.form-control::placeholder {
    color: #7f8897;
}

.form-control:focus {
    border-color: #2f6df6;
    box-shadow: 0 0 0 0.2rem rgba(47,109,246,0.20);
    background: rgba(255,255,255,0.05);
    color: #fff;
}

.btn-login {
    width: 100%;
    background: linear-gradient(90deg, #3d76f0, #2b63e0);
    border: none;
    padding: 13px;
    border-radius: 11px;
    font-weight: 600;
    margin-top: 12px;
    transition: all 0.25s ease;
    color: #fff;
    box-shadow: 0 10px 26px rgba(47,109,246,0.20);
}

.btn-login:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 28px rgba(47,109,246,0.34);
}

.alert {
    font-size: 13px;
    border-radius: 10px;
}

.footer {
    text-align: center;
    font-size: 12px;
    color: #707887;
    margin-top: 16px;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 480px) {
    .login-container {
        max-width: 100%;
        padding: 16px;
    }

    .brand {
        margin-bottom: 28px;
    }

    .brand::before {
        width: 280px;
        height: 130px;
    }

    .brand::after {
        width: 240px;
        height: 60px;
    }

    .brand-main {
        gap: 12px;
    }

    .brand-icon {
        width: 54px;
        height: 54px;
    }

    .brand-title {
        font-size: 24px;
    }

    .brand-subtitle {
        font-size: 12px;
    }

    .card-login {
        padding: 24px;
    }
}
</style>
</head>

<body>

<div class="login-container">

    <div class="brand">
        <div class="brand-content">
            <div class="brand-main">
                <div class="brand-icon">
                    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 74C22 42 33 20 50 20C67 20 78 42 82 74" stroke="url(#stroke1)" stroke-width="8.5" stroke-linecap="round"/>
                        <path d="M29 69L50 55L71 69" fill="url(#fill1)"/>
                        <circle cx="50" cy="48" r="6.3" fill="white"/>
                        <defs>
                            <linearGradient id="stroke1" x1="18" y1="20" x2="82" y2="74" gradientUnits="userSpaceOnUse">
                                <stop stop-color="white"/>
                                <stop offset="1" stop-color="#b8c0cf"/>
                            </linearGradient>
                            <linearGradient id="fill1" x1="29" y1="55" x2="71" y2="69" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#ffffff"/>
                                <stop offset="1" stop-color="#9ba5b6"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>

                <h1 class="brand-title">
                    Alperit <span class="light">System</span>
                </h1>
            </div>

            <p class="brand-subtitle">Sistema de peritaje vehicular</p>
        </div>
    </div>

    <div class="card-login">

        <?php if (isset($error)): ?>
            <div class="alert alert-danger text-center"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Correo</label>
                <input type="email" name="email" class="form-control" placeholder="Ingresá tu email" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control" placeholder="Ingresá tu contraseña" required>
            </div>

            <button type="submit" class="btn btn-login">Ingresar</button>
        </form>

        <div class="footer">
            Acceso seguro · Alperit System
        </div>

    </div>

</div>

</body>
</html>