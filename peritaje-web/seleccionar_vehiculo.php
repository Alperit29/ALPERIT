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

$solicitudId = isset($_GET['solicitud'])
    ? (int) $_GET['solicitud']
    : 0;

$vehiculoId = isset($_GET['vehiculo'])
    ? (int) $_GET['vehiculo']
    : 0;

if (
    $solicitudId <= 0 ||
    $vehiculoId <= 0
) {
    die("Datos inválidos.");
}


// ==============================
// VALIDAR SOLICITUD
// ==============================

$stmtSolicitud = $conn->prepare("
    SELECT id
    FROM solicitudes
    WHERE id = ?
");

$stmtSolicitud->bind_param(
    "i",
    $solicitudId
);

$stmtSolicitud->execute();

$resultadoSolicitud =
    $stmtSolicitud->get_result();

if ($resultadoSolicitud->num_rows === 0) {
    die("La solicitud no existe.");
}

$stmtSolicitud->close();


// ==============================
// VALIDAR VEHÍCULO
// ==============================

$stmtVehiculo = $conn->prepare("
    SELECT id
    FROM vehiculos
    WHERE id = ?
");

$stmtVehiculo->bind_param(
    "i",
    $vehiculoId
);

$stmtVehiculo->execute();

$resultadoVehiculo =
    $stmtVehiculo->get_result();

if ($resultadoVehiculo->num_rows === 0) {
    die("El vehículo no existe.");
}

$stmtVehiculo->close();


// ==============================
// GUARDAR SELECCIÓN
// ==============================

$stmtActualizar = $conn->prepare("
    UPDATE solicitudes
    SET vehiculo_seleccionado_id = ?
    WHERE id = ?
");

$stmtActualizar->bind_param(
    "ii",
    $vehiculoId,
    $solicitudId
);

$stmtActualizar->execute();

$stmtActualizar->close();

$conn->close();


// ==============================
// VOLVER A LA GESTIÓN
// ==============================

header(
    "Location: iniciar_gestion.php?id="
    . $solicitudId
    . "&buscar=1"
);

exit();

?>