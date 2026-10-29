<?php
$host = "127.0.0.1";
$usuario = getenv('ALPERIT_DB_USER') ?: 'root';
$contrasena = getenv('ALPERIT_DB_PASSWORD') ?: '';
$base_datos = "peritaje_autos";

$conn = new mysqli($host, $usuario, $contrasena, $base_datos);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$vehiculo_id = $_POST['vehiculo_id'];
$solicitud_id = $_POST['solicitud_id'] ?? null;
$estado_general = $_POST['estado_general'];
$observaciones = $_POST['observaciones'];
$precio_estimado = $_POST['precio_estimado'];

// 1. Guardar peritaje
$sql = "INSERT INTO peritajes 
(vehiculo_id, fecha_peritaje, estado_general, observaciones, precio_estimado, usuario_id)
VALUES 
('$vehiculo_id', CURDATE(), '$estado_general', '$observaciones', '$precio_estimado', 1)";

if ($conn->query($sql) === TRUE) {

    $peritaje_id = $conn->insert_id;

    // 2. Procesar fotos
    if (!empty($_FILES['fotos']['name'][0])) {

        $rutaBase = "uploads/peritajes/";

        foreach ($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {

            $nombreArchivo = time() . "_" . $_FILES['fotos']['name'][$key];
            $rutaCompleta = $rutaBase . $nombreArchivo;

            if (move_uploaded_file($tmp_name, $rutaCompleta)) {

                $sqlFoto = "INSERT INTO fotos_peritaje (peritaje_id, ruta_foto, tipo_foto, fecha_carga)
                            VALUES ('$peritaje_id', '$rutaCompleta', 'general', NOW())";

                $conn->query($sqlFoto);
            }
        }
    }

    header(
    "Location: generar_propuesta.php?peritaje_id=" .
    $peritaje_id . 
    "&solicitud_id=" .
    $solicitud_id
);
exit();
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>