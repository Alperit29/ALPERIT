<?php

$host = "127.0.0.1";
$usuario = getenv('ALPERIT_DB_USER') ?: 'root';
$contrasena = getenv('ALPERIT_DB_PASSWORD') ?: '';
$base_datos = "peritaje_autos";

$conn = new mysqli(
    $host,
    $usuario,
    $contrasena,
    $base_datos
);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$token = $_GET['token'] ?? '';

if ($token === '') {
    die("Enlace de visita no válido.");
}

$sqlPropuesta = "
    SELECT id
    FROM propuestas
    WHERE token_visita = ?
    LIMIT 1
";

$stmt = $conn->prepare($sqlPropuesta);
$stmt->bind_param("s", $token);
$stmt->execute();

$resultado = $stmt->get_result();
$propuesta = $resultado->fetch_assoc();

if (!$propuesta) {
    die("Enlace de visita no válido.");
}

$sqlVisita = "
    SELECT id, fecha, hora, estado
    FROM visitas
    WHERE propuesta_id = ?
    LIMIT 1
";

$stmtVisita = $conn->prepare($sqlVisita);
$stmtVisita->bind_param("i", $propuesta['id']);
$stmtVisita->execute();

$resultadoVisita = $stmtVisita->get_result();
$visita = $resultadoVisita->fetch_assoc();

if (!$visita) {
    die("No se encontró una visita agendada.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $sqlCancelar = "
        UPDATE visitas
        SET estado = 'Cancelada'
        WHERE id = ?
    ";

    $stmtCancelar = $conn->prepare($sqlCancelar);
    $stmtCancelar->bind_param("i", $visita['id']);
    $stmtCancelar->execute();

    header(
        "Location: agendar_visita.php?token=" .
        urlencode($token)
    );

    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancelar visita - ALPERIT</title>
</head>

<body>

    <h1>Cancelar visita</h1>

    <p>
        ¿Estás seguro de que querés cancelar la visita?
    </p>

    <p>
        <strong>Fecha:</strong>
        <?php echo date('d/m/Y', strtotime($visita['fecha'])); ?>
    </p>

    <p>
        <strong>Horario:</strong>
        <?php echo date('H:i', strtotime($visita['hora'])); ?>
    </p>

    <form method="POST">
        <button type="submit">
            Sí, cancelar visita
        </button>

        <a href="agendar_visita.php?token=<?php echo urlencode($token); ?>">
            Volver
        </a>
    </form>

</body>
</html>