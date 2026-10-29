<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$host = "127.0.0.1";
$usuario = getenv('ALPERIT_DB_USER') ?: 'root';
$contrasena = getenv('ALPERIT_DB_PASSWORD') ?: '';
$base_datos = "peritaje_autos";

$conn = new mysqli($host, $usuario, $contrasena, $base_datos);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Verificamos que venga un ID
if (!isset($_GET['id'])) {
    die("No se indicó una solicitud.");
}

$id = intval($_GET['id']);

// Buscamos esa solicitud
$stmt = $conn->prepare("SELECT * FROM solicitudes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    die("Solicitud no encontrada.");
}

$solicitud = $resultado->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Solicitud - Alperit System</title>

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

        .navbar a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .contenedor {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }

        .titulo {
            margin-bottom: 30px;
        }

        .titulo h1 {
            margin-bottom: 5px;
        }

        .estado {
            display: inline-block;
            background: #fff3cd;
            color: #856404;
            padding: 7px 12px;
            border-radius: 10px;
            font-weight: bold;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .dato {
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 15px;
        }

        .dato label {
            display: block;
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 6px;
        }

        .dato strong {
            font-size: 17px;
        }

        .observaciones {
            margin-top: 30px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 10px;
        }

        .acciones {
    margin-top: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.btn-volver {
    padding: 11px 20px;
    border: 1px solid #d1d5db;
    border-radius: 7px;
    color: #374151;
    text-decoration: none;
    font-weight: bold;
    background: white;
}

.btn-gestion {
    padding: 12px 22px;
    border-radius: 7px;
    background: #2563eb;
    color: white;
    text-decoration: none;
    font-weight: bold;
}

.btn-gestion:hover {
    background: #1d4ed8;
}

    </style>
</head>

<body>

<div class="navbar">

    <strong>Alperit System</strong>

    <a href="solicitudes.php">
        ← Volver a solicitudes
    </a>

</div>

<div class="contenedor">

    <div class="card">

        <div class="titulo">

            <h1>
                Solicitud #<?php echo $solicitud['id']; ?>
            </h1>

            <span class="estado">
                <?php echo htmlspecialchars($solicitud['estado']); ?>
            </span>

        </div>


        <div class="grid">

            <div class="dato">
                <label>Cliente</label>
                <strong>
                    <?php echo htmlspecialchars($solicitud['nombre']); ?>
                </strong>
            </div>

            <div class="dato">
                <label>Email</label>
                <strong>
                    <?php echo htmlspecialchars($solicitud['email']); ?>
                </strong>
            </div>

            <div class="dato">
                <label>Teléfono</label>
                <strong>
                    <?php echo htmlspecialchars($solicitud['telefono'] ?? '-'); ?>
                </strong>
            </div>

            <div class="dato">
                <label>Vehículo solicitado</label>
                <strong>
                    <?php echo htmlspecialchars(
                        $solicitud['marca'] . " " . $solicitud['modelo']
                    ); ?>
                </strong>
            </div>

            <div class="dato">
    <label>Rango de año</label>
    <strong>
        <?php
        if ($solicitud['anio_desde'] && $solicitud['anio_hasta']) {
            echo $solicitud['anio_desde'] . " - " . $solicitud['anio_hasta'];
        } elseif ($solicitud['anio_desde']) {
            echo "Desde " . $solicitud['anio_desde'];
        } elseif ($solicitud['anio_hasta']) {
            echo "Hasta " . $solicitud['anio_hasta'];
        } else {
            echo "-";
        }
        ?>
    </strong>
</div>

            <div class="dato">
                <label>Transmisión</label>
                <strong>
                    <?php echo htmlspecialchars($solicitud['transmision'] ?? '-'); ?>
                </strong>
            </div>

            <div class="dato">
                <label>Kilometraje máximo</label>
                <strong>
                    <?php
                    echo $solicitud['kilometraje_maximo']
                        ? number_format($solicitud['kilometraje_maximo'], 0, ',', '.') . " km"
                        : '-';
                    ?>
                </strong>
            </div>

            <div class="dato">
    <label>Presupuesto máximo</label>

    <strong>
        <?php
        if ($solicitud['presupuesto_maximo'] !== null) {

            $moneda = $solicitud['moneda'] ?? 'ARS';

            echo htmlspecialchars($moneda) . ' ' .
                number_format(
                    $solicitud['presupuesto_maximo'],
                    0,
                    ',',
                    '.'
                );

        } else {
            echo '-';
        }
        ?>
    </strong>
</div>

            <div class="dato">
                <label>Forma de compra</label>
                <strong>
                    <?php echo htmlspecialchars($solicitud['forma_compra'] ?? '-'); ?>
                </strong>
            </div>

            <div class="dato">
                <label>Fecha de solicitud</label>
                <strong>
                    <?php echo date(
                        "d/m/Y H:i",
                        strtotime($solicitud['fecha_creacion'])
                    ); ?>
                </strong>
            </div>

        </div>


        <div class="observaciones">

            <label><strong>Observaciones del cliente</strong></label>

            <p>
                <?php echo htmlspecialchars(
                    $solicitud['observaciones'] ?? 'Sin observaciones'
                ); ?>
            </p>

        </div>

        <div class="acciones">

    <a href="solicitudes.php" class="btn-volver">
        ← Volver
    </a>

    <a href="iniciar_gestion.php?id=<?php echo $solicitud['id']; ?>"
       class="btn-gestion">
        Iniciar gestión
    </a>

</div>

    </div>

</div>

</body>
</html>