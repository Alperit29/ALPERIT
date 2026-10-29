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
    die("ID de peritaje no especificado.");
}

$id_peritaje = (int) $_GET['id'];

$sql = "SELECT 
    p.id AS id_peritaje,
    p.fecha_peritaje,
    p.estado_general,
    p.observaciones,
    p.precio_estimado,
    u.nombre AS perito,
    v.patente,
    v.marca,
    v.modelo,
    v.anio,
    v.kilometros
FROM peritajes p
INNER JOIN vehiculos v ON p.vehiculo_id = v.id
INNER JOIN usuarios u ON p.usuario_id = u.id
WHERE p.id = $id_peritaje
LIMIT 1";

$resultado = $conn->query($sql);

if (!$resultado || $resultado->num_rows === 0) {
    die("Peritaje no encontrado.");
}

$peritaje = $resultado->fetch_assoc();

$sqlFotos = "SELECT * FROM fotos_peritaje WHERE peritaje_id = $id_peritaje ORDER BY id DESC";
$fotos = $conn->query($sqlFotos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Peritaje</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6f9;
            font-family: Arial, sans-serif;
        }

        .navbar-custom {
            background: linear-gradient(90deg, #b6b7b9, #333638);
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
            color: #0f1013;
            font-weight: 700;
        }

        .dato-box {
            background: #eef4ff;
            border-radius: 12px;
            padding: 14px 16px;
            font-weight: 600;
            color: #06080c;
        }

        .foto-card {
            background: #fff;
            border-radius: 12px;
            padding: 8px;
            box-shadow: 0 6px 14px rgba(0,0,0,0.08);
        }

        .foto-card img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-radius: 10px;
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
                <li class="nav-item"><a class="nav-link" href="buscar.php">Búsqueda</a></li>
            </ul>
           <span class="usuario-navbar">Usuario: <?php echo $_SESSION['nombre']; ?></span>
        </div>
    </div>
</nav>

<div class="container py-5">

    <div class="card card-custom p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="titulo-seccion mb-1"><?php echo $peritaje['marca'] . " " . $peritaje['modelo']; ?></h2>
                <p class="text-muted mb-0">Detalle completo del peritaje</p>
            </div>
            <a href="index.php" class="btn btn-outline-dark">Volver</a>
        </div>

        <div class="row g-3">
            <div class="col-md-3"><div class="dato-box">Patente: <?php echo $peritaje['patente']; ?></div></div>
            <div class="col-md-3"><div class="dato-box">Año: <?php echo $peritaje['anio']; ?></div></div>
            <div class="col-md-3"><div class="dato-box">Km: <?php echo number_format($peritaje['kilometros'], 0, ',', '.'); ?></div></div>
            <div class="col-md-3">
                <div class="dato-box">
                    Estado: 
                    <?php
                    $estado = strtolower($peritaje['estado_general']);
                    $badge = 'bg-secondary';

                    if ($estado == 'excelente') $badge = 'bg-primary';
                    if ($estado == 'muy bueno') $badge = 'bg-success';
                    if ($estado == 'bueno') $badge = 'bg-success';
                    if ($estado == 'regular') $badge = 'bg-warning text-dark';
                    if ($estado == 'deficiente') $badge = 'bg-danger';
                    ?>
                    <span class="badge <?php echo $badge; ?>">
                        <?php echo $peritaje['estado_general']; ?>
                    </span>
                </div>
            </div>

            <div class="col-md-4"><div class="dato-box">Precio: $<?php echo number_format($peritaje['precio_estimado'], 0, ',', '.'); ?></div></div>
            <div class="col-md-4"><div class="dato-box">Fecha: <?php echo date("d/m/Y", strtotime($peritaje['fecha_peritaje'])); ?></div></div>
            <div class="col-md-4"><div class="dato-box">Perito: <?php echo $peritaje['perito']; ?></div></div>

            <div class="col-12">
                <div class="dato-box">
                    Observaciones:<br>
                    <span style="font-weight: 400; color: #333;">
                        <?php echo nl2br($peritaje['observaciones']); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-custom p-4">
        <h3 class="titulo-seccion mb-4">Fotos del Peritaje</h3>

        <div class="row g-4">
            <?php if ($fotos && $fotos->num_rows > 0): ?>
                <?php while($foto = $fotos->fetch_assoc()): ?>
                    <div class="col-md-4">
                        <div class="foto-card">
                            <img src="<?php echo $foto['ruta_foto']; ?>" alt="Foto del peritaje">
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-warning mb-0">Este peritaje no tiene fotos cargadas.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

</body>
</html>
<?php
$conn->close();
?>