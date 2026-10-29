<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['rol'] == 'consulta') {
    die("Acceso denegado.");
}

$host = "127.0.0.1";
$usuario = getenv('ALPERIT_DB_USER') ?: 'root';
$contrasena = "";
$base_datos = "peritaje_autos";

$conn = new mysqli($host, $usuario, $contrasena, $base_datos);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$id = $_POST['id'];
$patente = $_POST['patente'];
$marca = $_POST['marca'];
$modelo = $_POST['modelo'];
$anio = $_POST['anio'];
$kilometros = $_POST['kilometros'];

$sql = "UPDATE vehiculos SET 
    patente = '$patente',
    marca = '$marca',
    modelo = '$modelo',
    anio = '$anio',
    kilometros = '$kilometros'
WHERE id = $id";

if ($conn->query($sql) === TRUE) {
    header("Location: index.php");
    exit();
} else {
    echo "Error al actualizar: " . $conn->error;
}

$conn->close();
?>