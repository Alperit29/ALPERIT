<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli(
    "127.0.0.1",
    "root",
    "",
    "peritaje_autos"
);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$idVisita = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($idVisita <= 0) {
    die("Visita no válida.");
}

$sqlVisita = "
    SELECT
        v.*,
        c.nombre AS cliente_nombre,
        ve.marca,
        ve.modelo,
        ve.anio,
        ve.patente
    FROM visitas v
    INNER JOIN clientes c ON c.id = v.cliente_id
    INNER JOIN vehiculos ve ON ve.id = v.vehiculo_id
    WHERE v.id = ?
";

$stmt = $conn->prepare($sqlVisita);
$stmt->bind_param("i", $idVisita);
$stmt->execute();

$resultado = $stmt->get_result();
$visita = $resultado->fetch_assoc();

if (!$visita) {
    die("Visita no encontrada.");
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Detalle de visita - Alperit System</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #111827;
        }

        .navbar {
            background: linear-gradient(90deg, #a7a7a9, #202024);
            color: white;
            padding: 16px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar h3 {
            margin: 0;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .contenedor {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 25px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }

        .card h1 {
            margin-top: 0;
            margin-bottom: 25px;
        }

        .estado {
            display: inline-block;
            background: #dcfce7;
            color: #166534;
            padding: 7px 12px;
            border-radius: 10px;
            font-weight: bold;
        }

        .volver {
            display: inline-block;
            margin-top: 15px;
            text-decoration: none;
            color: #2563eb;
            font-weight: bold;
        }
    </style>
</head>

<body>

<div class="navbar">
    <h3>Alperit System</h3>

    <div>
        <a href="visitas.php">← Volver a visitas</a>
        &nbsp;&nbsp;&nbsp;
        Usuario: <?php echo htmlspecialchars($_SESSION['nombre']); ?>
    </div>
</div>

<div class="contenedor">

    <div class="card">

        <h1>Detalle de visita</h1>

        <p>
            <strong>Cliente:</strong>
            <?php echo htmlspecialchars($visita['cliente_nombre']); ?>
        </p>

        <p>
            <strong>Vehículo:</strong>
            <?php echo htmlspecialchars($visita['marca'] . ' ' . $visita['modelo']); ?>
        </p>

        <p>
            <strong>Año:</strong>
            <?php echo htmlspecialchars($visita['anio']); ?>
        </p>

        <p>
            <strong>Patente:</strong>
            <?php echo htmlspecialchars($visita['patente']); ?>
        </p>

        <p>
            <strong>Fecha:</strong>
            <?php echo date('d/m/Y', strtotime($visita['fecha'])); ?>
        </p>

        <p>
            <strong>Horario:</strong>
            <?php echo date('H:i', strtotime($visita['hora'])); ?>
        </p>

        <p>
            <strong>Estado:</strong>
            <span class="estado">
                <?php echo htmlspecialchars($visita['estado']); ?>
            </span>
        </p>

        <p>
            <strong>Visita registrada:</strong>
            <?php echo date('d/m/Y H:i', strtotime($visita['fecha_creacion'])); ?>
        </p>

        <a class="volver" href="visitas.php">
            ← Volver a visitas
        </a>

    </div>

</div>

</body>
</html>