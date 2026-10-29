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

$patenteBuscada = "";
$vehiculo = null;
$peritajes = null;

if (isset($_GET['patente']) && !empty(trim($_GET['patente']))) {
    $patenteBuscada = trim($_GET['patente']);

    $sqlVehiculo = "SELECT * FROM vehiculos WHERE patente = '$patenteBuscada' AND activo = 1 LIMIT 1";
    $resultadoVehiculo = $conn->query($sqlVehiculo);

    if ($resultadoVehiculo && $resultadoVehiculo->num_rows > 0) {
        $vehiculo = $resultadoVehiculo->fetch_assoc();

        $vehiculo_id = $vehiculo['id'];

        $sqlPeritajes = "SELECT 
            p.id,
            p.fecha_peritaje,
            p.estado_general,
            p.observaciones,
            p.precio_estimado,
            u.nombre AS perito
        FROM peritajes p
        INNER JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.vehiculo_id = $vehiculo_id
        ORDER BY p.id DESC";

        $peritajes = $conn->query($sqlPeritajes);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Vehículo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6f9;
            font-family: Arial, sans-serif;
        }

        .navbar-custom {
            background: linear-gradient(90deg, #bebfc0, #2b3030);
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
            color: #060911;
            font-weight: 700;
        }

        .btn-principal {
            background: #575858;
            border: none;
            color: #fff;
        }

        .btn-principal:hover {
            background: #7a7a7c;
            color: #fff;
        }

        .tabla thead {
            background: #e9eef6;
        }

        .dato-box {
            background: #eef4ff;
            border-radius: 12px;
            padding: 12px 16px;
            font-weight: 600;
            color: #07080c;
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
                <li class="nav-item"><a class="nav-link" href="peritajes.php">Peritajes</a></li>
                <li class="nav-item"><a class="nav-link active" href="buscar.php">Búsqueda</a></li>
            </ul>
            <span class="usuario-navbar">Usuario: <?php echo $_SESSION['nombre']; ?></span>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="card card-custom p-4 mb-4">
        <h2 class="titulo-seccion mb-4">Buscar Vehículo por Patente</h2>

        <form method="GET" action="buscar.php" class="row g-3">
            <div class="col-md-10">
                <input type="text" name="patente" class="form-control" placeholder="Ingresar patente" value="<?php echo htmlspecialchars($patenteBuscada); ?>" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-principal w-100">Buscar</button>
            </div>
        </form>
    </div>

    <?php if ($patenteBuscada != ""): ?>
        <?php if ($vehiculo): ?>
            <div class="card card-custom p-4 mb-4">
                <h2 class="titulo-seccion mb-1">
    <?php echo $vehiculo['marca'] . " " . $vehiculo['modelo']; ?>
</h2>
<p class="text-muted mb-2">
    Patente: <?php echo $vehiculo['patente']; ?>
</p>
<p class="text-muted mb-4">Ficha del vehículo consultado</p>
                <div class="row g-3">
                    <div class="col-md-3"><div class="dato-box">Patente: <?php echo $vehiculo['patente']; ?></div></div>
                    <div class="col-md-3"><div class="dato-box">Marca: <?php echo $vehiculo['marca']; ?></div></div>
                    <div class="col-md-3"><div class="dato-box">Modelo: <?php echo $vehiculo['modelo']; ?></div></div>
                    <div class="col-md-3"><div class="dato-box">Año: <?php echo $vehiculo['anio']; ?></div></div>
                    <div class="col-md-3"><div class="dato-box">Km: <?php echo number_format($vehiculo['kilometros'], 0, ',', '.'); ?></div></div>
                </div>
            </div>

            <div class="card card-custom p-4">
                <h3 class="titulo-seccion mb-3">Historial de Peritajes</h3>

                <div class="table-responsive">
                    <table class="table tabla table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Observaciones</th>
                                <th>Precio Estimado</th>
                                <th>Perito</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($peritajes && $peritajes->num_rows > 0): ?>
                              <?php while($fila = $peritajes->fetch_assoc()): ?>
    <tr>
        <td><?php echo $fila['id']; ?></td>
        <td><?php echo $fila['fecha_peritaje']; ?></td>
        <td><?php echo $fila['estado_general']; ?></td>
        <td><?php echo $fila['observaciones']; ?></td>
        <td>$<?php echo number_format($fila['precio_estimado'], 0, ',', '.'); ?></td>
        <td><?php echo $fila['perito']; ?></td>
    </tr>

    <?php
    $peritaje_id = $fila['id'];
    $sqlFotos = "SELECT * FROM fotos_peritaje WHERE peritaje_id = $peritaje_id";
    $resultadoFotos = $conn->query($sqlFotos);
    ?>

    <?php if ($resultadoFotos && $resultadoFotos->num_rows > 0): ?>
        <tr>
            <td colspan="6">
                <div style="display:flex; gap:10px; flex-wrap:wrap; padding:10px 0;">
                    <?php while($foto = $resultadoFotos->fetch_assoc()): ?>
                        <div style="border:1px solid #ddd; border-radius:10px; padding:6px; background:#fff;">
                            <img src="<?php echo $foto['ruta_foto']; ?>" alt="Foto peritaje" style="width:140px; height:100px; object-fit:cover; border-radius:8px;">
                        </div>
                    <?php endwhile; ?>
                </div>
            </td>
        </tr>
    <?php endif; ?>
<?php endwhile; ?>  
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Este vehículo no tiene peritajes cargados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                No se encontró ningún vehículo con la patente <strong><?php echo htmlspecialchars($patenteBuscada); ?></strong>.
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

</body>
</html>
<?php
$conn->close();
?>