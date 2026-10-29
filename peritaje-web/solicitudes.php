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

$sql = "SELECT * FROM solicitudes ORDER BY fecha_creacion DESC";
$resultadoSolicitudes = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes - Alperit System</title>

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
            padding: 30px;
        }

        .titulo {
            margin-bottom: 25px;
        }

        .titulo h1 {
            margin-bottom: 5px;
        }

        .titulo p {
            color: #6b7280;
            margin-top: 0;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 14px;
            border-bottom: 2px solid #e5e7eb;
        }

        td {
            padding: 14px;
            border-bottom: 1px solid #e5e7eb;
        }

        .estado {
            display: inline-block;
            background: #fff3cd;
            color: #856404;
            padding: 7px 12px;
            border-radius: 10px;
            font-weight: bold;
        }

        .btn-ver {
    border: 1px solid #2563eb;
    color: #2563eb;
    background: white;
    padding: 7px 14px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    text-decoration: none;
    display: inline-block;
}

.btn-anular {
    border: 1px solid #dc2626;
    color: #dc2626;
    background: white;
    padding: 7px 14px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    text-decoration: none;
    display: inline-block;
    margin-left: 6px;
}

.btn-anular:hover {
    background: #dc2626;
    color: white;
}
    </style>
</head>

<body>

<div class="navbar">
    <h3>Alperit System</h3>

    <div>
        <a href="index.php">← Volver al inicio</a>
        &nbsp;&nbsp;&nbsp;
        Usuario: <?php echo htmlspecialchars($_SESSION['nombre']); ?>
    </div>
</div>

<div class="contenedor">

    <div class="titulo">
        <h1>Solicitudes de clientes</h1>
        <p>Vehículos solicitados desde el Portal Cliente ALPERIT</p>
    </div>

    <div class="card">

        <table>

            <thead>
                <tr>
                    
                    <th>Cliente</th>
                    <th>Vehículo</th>
                    <th>Rango de año</th>
                    <th>Km máx.</th>
                    <th>Presupuesto</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Acción</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($resultadoSolicitudes && $resultadoSolicitudes->num_rows > 0): ?>

                <?php while ($solicitud = $resultadoSolicitudes->fetch_assoc()): ?>

                    <tr>

                        

                        <td>
                            <?php echo htmlspecialchars($solicitud['nombre']); ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $solicitud['marca'] . " " . $solicitud['modelo']
                            );
                            ?>
                        </td>

                        <td>
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
</td>

                        <td>
                            <?php
                            echo $solicitud['kilometraje_maximo']
                                ? number_format($solicitud['kilometraje_maximo'], 0, ',', '.')
                                : '-';
                            ?>
                        </td>

                        <td>
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
</td>

                        <td>
                            <span class="estado">
                                <?php echo htmlspecialchars($solicitud['estado']); ?>
                            </span>
                        </td>

                        <td>
                            <?php
                            echo date(
                                "d/m/Y H:i",
                                strtotime($solicitud['fecha_creacion'])
                            );
                            ?>
                        </td>

                        <td>
    <a class="btn-ver"
       href="detalle_solicitud.php?id=<?php echo $solicitud['id']; ?>">
        Ver
    </a>

    <?php if ($_SESSION['rol'] === 'admin' && $solicitud['estado'] === 'Pendiente'): ?>

    <a class="btn-anular"
       href="eliminar_solicitud.php?id=<?php echo $solicitud['id']; ?>">
        Eliminar
    </a>

<?php endif; ?>
</td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="9">
                        No hay solicitudes cargadas.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>