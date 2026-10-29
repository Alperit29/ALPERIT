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
    SELECT *
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

    $nuevaFecha = $_POST['fecha'] ?? '';
    $nuevaHora = $_POST['hora'] ?? '';

    if ($nuevaFecha === '' || $nuevaHora === '') {
        die("Fecha u horario no válidos.");
    }

    $sqlActualizar = "
        UPDATE visitas
        SET fecha = ?, hora = ?, estado = 'Reprogramada'
        WHERE id = ?
    ";

    $stmtActualizar = $conn->prepare($sqlActualizar);
    $stmtActualizar->bind_param(
        "ssi",
        $nuevaFecha,
        $nuevaHora,
        $visita['id']
    );

    $stmtActualizar->execute();

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
    <title>Modificar visita - ALPERIT</title>
</head>

<body>

<h1>Modificar visita</h1>

<form method="POST">

    <label>Nueva fecha:</label>
    <input
        type="date"
        name="fecha"
        value="<?php echo htmlspecialchars($visita['fecha']); ?>"
        required
    >

    <br><br>

    <label>Nuevo horario:</label>
   <select name="hora" required>

    <?php
    $horaActual = substr($visita['hora'], 0, 5);
    ?>

    <option value="09:00" <?php echo $horaActual === '09:00' ? 'selected' : ''; ?>>
        09:00
    </option>

    <option value="10:00" <?php echo $horaActual === '10:00' ? 'selected' : ''; ?>>
        10:00
    </option>

    <option value="11:00" <?php echo $horaActual === '11:00' ? 'selected' : ''; ?>>
        11:00
    </option>

    <option value="15:00" <?php echo $horaActual === '15:00' ? 'selected' : ''; ?>>
        15:00
    </option>

    <option value="16:00" <?php echo $horaActual === '16:00' ? 'selected' : ''; ?>>
        16:00
    </option>

    <option value="17:00" <?php echo $horaActual === '17:00' ? 'selected' : ''; ?>>
        17:00
    </option>

</select>

    <br><br>

    <button type="submit">
        Guardar cambios
    </button>

</form>

</body>
</html>