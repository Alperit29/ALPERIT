<?php

header('Content-Type: application/json; charset=utf-8');


// ==============================
// CONEXIÓN
// ==============================

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
    echo json_encode([
        'error' => 'Error de conexión'
    ]);
    exit();
}

$conn->set_charset("utf8mb4");


// ==============================
// RECIBIR FECHA
// ==============================

$fecha = $_GET['fecha'] ?? '';

if (empty($fecha)) {
    echo json_encode([]);
    exit();
}


// ==============================
// BUSCAR HORARIOS OCUPADOS
// ==============================

$sql = "
    SELECT hora
    FROM visitas
    WHERE fecha = ?
      AND estado = 'Agendada'
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "s",
    $fecha
);

$stmt->execute();

$resultado = $stmt->get_result();

$horariosOcupados = [];

while ($fila = $resultado->fetch_assoc()) {

    $horariosOcupados[] =
        substr($fila['hora'], 0, 5);
}

$stmt->close();
$conn->close();


// ==============================
// RESPUESTA
// ==============================

echo json_encode($horariosOcupados);