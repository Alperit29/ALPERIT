<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
?>
<?php
$host = "127.0.0.1";
$usuario = getenv('ALPERIT_DB_USER') ?: 'root';
$contrasena = getenv('ALPERIT_DB_PASSWORD') ?: '';
$base_datos = "peritaje_autos";

$conn = new mysqli($host, $usuario, $contrasena, $base_datos);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID de vehículo no especificado.");
}

$id = (int) $_GET['id'];

$sql = "SELECT * FROM vehiculos WHERE id = $id LIMIT 1";
$resultado = $conn->query($sql);

if (!$resultado || $resultado->num_rows === 0) {
    die("Vehículo no encontrado.");
}

$vehiculo = $resultado->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Vehículo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6f9;
            font-family: Arial, sans-serif;
        }

        .navbar-custom {
            background: linear-gradient(90deg, #bfc0c2, #333435);
        }

        .navbar-brand,
        .nav-link,
        .usuario-navbar {
            color: #fff !important;
            font-weight: 600;
        }

        .card-custom {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }

        .titulo-seccion {
            color: #111213;
            font-weight: 700;
        }

        .btn-principal {
            background: #5d5d5f;
            border: none;
            color: #fff;
        }

        .btn-principal:hover {
            background: #939396;
            color: #fff;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a class="navbar-brand" href="index.php">Sistema de Peritaje de Vehículos</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto me-3">
                <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
            </ul>
            <span class="usuario-navbar">Usuario: <?php echo $_SESSION['nombre']; ?></span>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card card-custom p-4">
                <h2 class="titulo-seccion mb-4">Editar Vehículo</h2>

                <form action="actualizar_vehiculo.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo $vehiculo['id']; ?>">

                    <div class="mb-3">
                        <label for="patente" class="form-label">Patente</label>
                        <input type="text" class="form-control" id="patente" name="patente" value="<?php echo $vehiculo['patente']; ?>" maxlength="15" required>
                    </div>

                    <div class="mb-3">
                        <label for="marca" class="form-label">Marca</label>
                        <input type="text" class="form-control" id="marca" name="marca" value="<?php echo $vehiculo['marca']; ?>" maxlength="50" required>
                    </div>

                    <div class="mb-3">
                        <label for="modelo" class="form-label">Modelo</label>
                        <input type="text" class="form-control" id="modelo" name="modelo" value="<?php echo $vehiculo['modelo']; ?>" maxlength="50" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="anio" class="form-label">Año</label>
                            <input type="number" class="form-control" id="anio" name="anio" value="<?php echo $vehiculo['anio']; ?>" min="1900" max="2100" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="kilometros" class="form-label">Kilómetros</label>
                            <input type="number" class="form-control" id="kilometros" name="kilometros" value="<?php echo $vehiculo['kilometros']; ?>" min="0" required>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-principal">Guardar cambios</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

</body>
</html>
<?php
$conn->close();
?>