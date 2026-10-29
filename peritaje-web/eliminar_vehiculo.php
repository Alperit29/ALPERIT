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


// ================================
// RECIBIR ID DEL VEHÍCULO
// ================================

$id_vehiculo = isset($_GET['id'])
    ? (int) $_GET['id']
    : (isset($_POST['id_vehiculo']) ? (int) $_POST['id_vehiculo'] : 0);

if ($id_vehiculo <= 0) {
    die("ID de vehículo no válido.");
}


// ================================
// PROCESAR BAJA
// ================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $motivo_baja = trim($_POST['motivo_baja'] ?? '');
    $usuario_baja = (int) $_SESSION['usuario_id'];

    if ($motivo_baja === '') {
        die("Debe seleccionar un motivo de baja.");
    }

    $sql = "
        UPDATE vehiculos
        SET
            activo = 0,
            motivo_baja = ?,
            fecha_baja = NOW(),
            usuario_baja = ?
        WHERE id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sii",
        $motivo_baja,
        $usuario_baja,
        $id_vehiculo
    );

    if ($stmt->execute()) {

        header("Location: index.php");
        exit();

    } else {

        die("Error al dar de baja el vehículo: " . $conn->error);
    }
}


// ================================
// BUSCAR VEHÍCULO
// ================================

$sqlVehiculo = "
    SELECT id, patente, marca, modelo
    FROM vehiculos
    WHERE id = ?
";

$stmt = $conn->prepare($sqlVehiculo);
$stmt->bind_param("i", $id_vehiculo);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    die("Vehículo no encontrado.");
}

$vehiculo = $resultado->fetch_assoc();

?>

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<title>Dar de baja vehículo</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

</head>

<body class="bg-light">


<div class="container mt-5">

    <div class="card shadow-sm mx-auto" style="max-width: 600px;">

        <div class="card-body p-4">

            <h3 class="mb-3">
                Dar de baja vehículo
            </h3>

            <p class="text-muted">
                El vehículo no será eliminado de la base de datos.
                Se conservará su historial.
            </p>


            <div class="alert alert-secondary">

                <strong>
                    <?php echo htmlspecialchars($vehiculo['marca']); ?>
                    <?php echo htmlspecialchars($vehiculo['modelo']); ?>
                </strong>

                <br>

                Patente:
                <?php echo htmlspecialchars($vehiculo['patente']); ?>

            </div>


            <form method="POST">

                <input
                    type="hidden"
                    name="id_vehiculo"
                    value="<?php echo $vehiculo['id']; ?>"
                >


                <div class="mb-4">

                    <label class="form-label">
                        Motivo de baja
                    </label>

                    <select
    name="motivo_baja"
    class="form-select"
    required
>

    <option value="">
        Seleccionar motivo
    </option>

    <option value="Vendido">
        Vendido
    </option>

    <option value="Reservado">
        Reservado
    </option>

    <option value="Incendio o siniestro">
        Incendio o siniestro
    </option>

    <option value="Error de carga">
        Error de carga
    </option>

</select>

                </div>


                <div class="d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-danger"
                    >
                        Confirmar baja
                    </button>

                    <a
                        href="index.php"
                        class="btn btn-secondary"
                    >
                        Cancelar
                    </a>

                </div>

            </form>

        </div>

    </div>

</div>


</body>

</html>