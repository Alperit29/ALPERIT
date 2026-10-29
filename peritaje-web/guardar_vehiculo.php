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


// ==============================
// DATOS DEL FORMULARIO
// ==============================
$patente = trim($_POST['patente'] ?? '');
$marca = trim($_POST['marca'] ?? '');
$modelo = trim($_POST['modelo'] ?? '');

$anio = isset($_POST['anio'])
    ? (int) $_POST['anio']
    : 0;

$kilometros = isset($_POST['kilometros'])
    ? (int) $_POST['kilometros']
    : 0;

$transmision = trim($_POST['transmision'] ?? '');
$tipo_rodado = trim($_POST['tipo_rodado'] ?? '');
$moneda = trim($_POST['moneda'] ?? '');

$precio = isset($_POST['precio'])
    ? (float) $_POST['precio']
    : 0;


// ==============================
// VALIDACIONES
// ==============================

if (
    $patente === '' ||
    $marca === '' ||
    $modelo === '' ||
    $anio <= 0 ||
    $kilometros < 0 ||
    $transmision === '' ||
    $tipo_rodado === '' ||
    $moneda === '' ||
    $precio < 0
) {
    die("Faltan datos obligatorios o existen valores inválidos.");
}


// VALIDAR TIPO DE RODADO

if (
    $tipo_rodado !== 'Auto' &&
    $tipo_rodado !== 'Camioneta'
) {
    die("El tipo de rodado seleccionado no es válido.");
}


// VALIDAR MONEDA

if (
    $moneda !== 'ARS' &&
    $moneda !== 'USD'
) {
    die("La moneda seleccionada no es válida.");
}

// ==============================
// INSERTAR VEHÍCULO
// ==============================
$stmt = $conn->prepare("
    INSERT INTO vehiculos
    (
        patente,
        marca,
        modelo,
        anio,
        kilometros,
        transmision,
        tipo_rodado,
        moneda,
        precio
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "sssiisssd",
    $patente,
    $marca,
    $modelo,
    $anio,
    $kilometros,
    $transmision,
    $tipo_rodado,
    $moneda,
    $precio
);

if ($stmt->execute()) {

    header("Location: index.php");
    exit();

} else {

    echo "Error al guardar el vehículo: " . $stmt->error;
}


$stmt->close();
$conn->close();

?>