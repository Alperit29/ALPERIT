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

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Solicitud inválida.");
}

$id = (int) $_GET['id'];

/*
|--------------------------------------------------------------------------
| Buscar solicitud
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id, nombre, marca, modelo, estado
    FROM solicitudes
    WHERE id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();
$solicitud = $resultado->fetch_assoc();

if (!$solicitud) {
    die("La solicitud no existe.");
}

/*
|--------------------------------------------------------------------------
| Solo se pueden eliminar solicitudes Pendientes
|--------------------------------------------------------------------------
*/

if ($solicitud['estado'] !== 'Pendiente') {
    die("No se puede eliminar una solicitud que ya se encuentra en gestión.");
}

/*
|--------------------------------------------------------------------------
| Eliminación definitiva
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    | Volvemos a validar el estado dentro del DELETE.
    | Esto evita eliminarla si cambió de estado mientras
    | estaba abierta la pantalla de confirmación.
    */

    $stmtEliminar = $conn->prepare("
        DELETE FROM solicitudes
        WHERE id = ?
          AND estado = 'Pendiente'
    ");

    $stmtEliminar->bind_param("i", $id);

    if (!$stmtEliminar->execute()) {
        die("Error al eliminar la solicitud.");
    }

    /*
    | Si no se eliminó ninguna fila, significa que
    | la solicitud ya no estaba Pendiente.
    */

    if ($stmtEliminar->affected_rows !== 1) {
        $stmtEliminar->close();
        die("La solicitud no pudo eliminarse porque su estado cambió.");
    }

    $stmtEliminar->close();

    header("Location: solicitudes.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Eliminar solicitud | Alperit System</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #111827;
        }

        .contenedor {
            max-width: 650px;
            margin: 60px auto;
            padding: 20px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }

        h1 {
            margin-top: 0;
        }

        .advertencia {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .datos {
            background: #f9fafb;
            padding: 18px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .acciones {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .btn {
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-volver {
            background: #e5e7eb;
            color: #111827;
        }

        .btn-eliminar {
            background: #dc2626;
            color: white;
            border: none;
        }

        .btn-eliminar:hover {
            background: #b91c1c;
        }

    </style>

</head>

<body>

<div class="contenedor">

    <div class="card">

        <h1>Eliminar solicitud</h1>

        <div class="advertencia">

            <strong>Atención:</strong>

            esta acción eliminará definitivamente la solicitud
            y no podrá deshacerse.

        </div>

        <div class="datos">

            <strong>Cliente:</strong>

            <?php echo htmlspecialchars($solicitud['nombre']); ?>

            <br><br>

            <strong>Vehículo solicitado:</strong>

            <?php
            echo htmlspecialchars(
                trim(
                    $solicitud['marca'] . " " .
                    $solicitud['modelo']
                )
            );
            ?>

            <br><br>

            <strong>Estado:</strong>

            <?php echo htmlspecialchars($solicitud['estado']); ?>

        </div>

        <form method="POST">

            <p>
                ¿Confirmás que querés eliminar esta solicitud?
            </p>

            <div class="acciones">

                <a href="solicitudes.php"
                   class="btn btn-volver">
                    Volver
                </a>

                <button type="submit"
                        class="btn btn-eliminar">
                    Eliminar definitivamente
                </button>

            </div>

        </form>

    </div>

</div>

</body>

</html>

<?php
$stmt->close();
$conn->close();
?>