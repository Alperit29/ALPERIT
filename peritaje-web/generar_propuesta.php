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

$peritaje_id = isset($_GET['peritaje_id'])
    ? (int) $_GET['peritaje_id']
    : 0;

$solicitud_id = isset($_GET['solicitud_id'])
    ? (int) $_GET['solicitud_id']
    : 0;

$sql = "
    SELECT
        s.id AS solicitud_id,
        s.cliente_id,
        s.nombre,
        s.dni,
        s.email,
        s.telefono,

        v.id AS vehiculo_id,
        v.patente,
        v.marca,
        v.modelo,
        v.anio,
        v.kilometros,
        v.transmision,
        v.moneda,

        p.id AS peritaje_id,
        p.estado_general,
        p.observaciones,
        p.precio_estimado

    FROM solicitudes s

    INNER JOIN vehiculos v
        ON v.id = s.vehiculo_seleccionado_id

    INNER JOIN peritajes p
        ON p.id = ?

    WHERE s.id = ?
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $peritaje_id,
    $solicitud_id
);

$stmt->execute();

$resultado = $stmt->get_result();

$datos = $resultado->fetch_assoc();

if (!$datos) {
    die("No se pudieron obtener los datos de la propuesta.");
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Generar propuesta - ALPERIT
    </title>

</head>

<body>

    <h1>Generar propuesta</h1>

    <h2>Cliente</h2>

    <p>
        <strong>Nombre:</strong>
        <?php echo htmlspecialchars($datos['nombre']); ?>
    </p>

    <p>
        <strong>DNI:</strong>
        <?php echo htmlspecialchars($datos['dni']); ?>
    </p>

    <p>
        <strong>Email:</strong>
        <?php echo htmlspecialchars($datos['email']); ?>
    </p>

    <p>
        <strong>Teléfono:</strong>
        <?php echo htmlspecialchars($datos['telefono']); ?>
    </p>


    <h2>Vehículo</h2>

    <p>
        <strong>Vehículo:</strong>

        <?php
        echo htmlspecialchars(
            $datos['marca'] .
            ' ' .
            $datos['modelo']
        );
        ?>
    </p>

    <p>
        <strong>Patente:</strong>
        <?php echo htmlspecialchars($datos['patente']); ?>
    </p>

    <p>
        <strong>Año:</strong>
        <?php echo $datos['anio']; ?>
    </p>

    <p>
        <strong>Kilómetros:</strong>
        <?php
        echo number_format(
            $datos['kilometros'],
            0,
            ',',
            '.'
        );
        ?>
    </p>


    <h2>Resultado del peritaje</h2>

    <p>
        <strong>Estado:</strong>
        <?php
        echo htmlspecialchars(
            $datos['estado_general']
        );
        ?>
    </p>

    <p>
        <strong>Observaciones:</strong>
        <?php
        echo htmlspecialchars(
            $datos['observaciones']
        );
        ?>
    </p>


    <h2>Propuesta económica</h2>

    <form
        action="guardar_propuesta.php"
        method="POST"
    >

        <input
            type="hidden"
            name="cliente_id"
            value="<?php echo $datos['cliente_id']; ?>"
        >

        <input
            type="hidden"
            name="solicitud_id"
            value="<?php echo $datos['solicitud_id']; ?>"
        >

        <input
            type="hidden"
            name="vehiculo_id"
            value="<?php echo $datos['vehiculo_id']; ?>"
        >

        <input
            type="hidden"
            name="peritaje_id"
            value="<?php echo $datos['peritaje_id']; ?>"
        >

        <label>
            Precio ofrecido
        </label>

        <br>

        <input
            type="number"
            step="0.01"
            name="precio_ofrecido"
            value="<?php echo $datos['precio_estimado']; ?>"
        >

        <br><br>

        <label>
            Observaciones de la propuesta
        </label>

        <br>

        <textarea
            name="observaciones"
            rows="5"
            cols="50"
        ></textarea>

        <br><br>

        <button type="submit">
            Guardar propuesta
        </button>

    </form>

</body>

</html>

<?php

$stmt->close();
$conn->close();

?>