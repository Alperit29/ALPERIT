<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'admin') {
    die("Acceso denegado.");
}

$host = "127.0.0.1";
$usuario = getenv('ALPERIT_DB_USER') ?: 'root';
$contrasena = getenv('ALPERIT_DB_PASSWORD') ?: '';
$base_datos = "peritaje_autos";

$conn = new mysqli($host, $usuario, $contrasena, $base_datos);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$sql = "
    SELECT
        v.id,
        v.patente,
        v.marca,
        v.modelo,
        v.anio,
        v.motivo_baja,
        v.fecha_baja,
        u.nombre AS usuario_baja_nombre
    FROM vehiculos v
    LEFT JOIN usuarios u
        ON v.usuario_baja = u.id
    WHERE v.activo = 0
    ORDER BY v.fecha_baja DESC
";

$resultado = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehículos dados de baja | Alperit System</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="mb-1">Vehículos dados de baja</h2>
            <p class="text-muted mb-0">
                Historial de vehículos que ya no se encuentran activos.
            </p>
        </div>

        <a href="index.php" class="btn btn-secondary">
            Volver
        </a>

    </div>


    <div class="card shadow-sm">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>

                        <tr>
                            
                            <th>Patente</th>
                            <th>Vehículo</th>
                            <th>Año</th>
                            <th>Motivo</th>
                            <th>Fecha de baja</th>
                            <th>Usuario</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if ($resultado && $resultado->num_rows > 0): ?>

                        <?php while ($fila = $resultado->fetch_assoc()): ?>

                            <tr>

                                

                                <td>
                                    <?php echo htmlspecialchars($fila['patente']); ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $fila['marca'] . " " . $fila['modelo']
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php echo $fila['anio']; ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($fila['motivo_baja']); ?>
                                </td>

                                <td>
                                    <?php echo $fila['fecha_baja']; ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $fila['usuario_baja_nombre'] ?? 'Sin usuario'
                                    );
                                    ?>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                No hay vehículos dados de baja.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

</body>
</html>

<?php
$conn->close();
?>