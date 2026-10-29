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


// ==========================
// RECIBIR DATOS
// ==========================

$solicitud_id = isset($_POST['solicitud_id'])
    ? (int) $_POST['solicitud_id']
    : 0;

$vehiculo_id = isset($_POST['vehiculo_id'])
    ? (int) $_POST['vehiculo_id']
    : 0;

$peritaje_id = isset($_POST['peritaje_id'])
    ? (int) $_POST['peritaje_id']
    : 0;

$precio_ofrecido = isset($_POST['precio_ofrecido'])
    ? (float) $_POST['precio_ofrecido']
    : null;

$observaciones = $_POST['observaciones'] ?? '';


// ==========================
// VALIDACIÓN BÁSICA
// ==========================

if (
    $solicitud_id <= 0 ||
    $vehiculo_id <= 0 ||
    $peritaje_id <= 0
) {
    die("Faltan datos para generar la propuesta.");
}


// ==========================
// OBTENER CLIENTE DESDE
// LA SOLICITUD
// ==========================

$sqlCliente = "
    SELECT cliente_id
    FROM solicitudes
    WHERE id = ?
";

$stmtCliente = $conn->prepare($sqlCliente);

$stmtCliente->bind_param(
    "i",
    $solicitud_id
);

$stmtCliente->execute();

$resultadoCliente = $stmtCliente->get_result();

$solicitud = $resultadoCliente->fetch_assoc();

if (
    !$solicitud ||
    empty($solicitud['cliente_id'])
) {
    die("No se encontró el cliente asociado a la solicitud.");
}

$cliente_id = (int) $solicitud['cliente_id'];

$stmtCliente->close();


// ==========================
// GUARDAR PROPUESTA
// ==========================

$token_visita = bin2hex(random_bytes(32));

$sql = "
    INSERT INTO propuestas
    (
        cliente_id,
        solicitud_id,
        vehiculo_id,
        peritaje_id,
        precio_ofrecido,
        observaciones,
        token_visita,
        estado
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, 'Borrador')
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "iiiidss",
    $cliente_id,
    $solicitud_id,
    $vehiculo_id,
    $peritaje_id,
    $precio_ofrecido,
    $observaciones,
    $token_visita
);

if ($stmt->execute()) {

    $propuesta_id = $conn->insert_id;

    header(
        "Location: ver_propuesta.php?id=" .
        $propuesta_id
    );

    exit();

} else {

    echo "Error al guardar la propuesta: "
        . $stmt->error;
}

$stmt->close();
$conn->close();

?>