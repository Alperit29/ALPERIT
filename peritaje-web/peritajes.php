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
$contrasena = getenv('ALPERIT_DB_PASSWORD') ?: '';
$base_datos = "peritaje_autos";

$conn = new mysqli(
    $host,
    $usuario,
    $contrasena,
    $base_datos
);

if ($conn->connect_error) {
    die(
        "Error de conexión: "
        . $conn->connect_error
    );
}

$conn->set_charset("utf8mb4");


// ==============================
// DATOS RECIBIDOS DESDE GESTIÓN
// ==============================

$solicitudId = isset($_GET['solicitud'])
    ? (int) $_GET['solicitud']
    : 0;

$vehiculoSeleccionadoId =
    isset($_GET['vehiculo'])
        ? (int) $_GET['vehiculo']
        : 0;


// ==============================
// VALIDAR VEHÍCULO SELECCIONADO
// ==============================

$vehiculoPreseleccionado = null;

if ($vehiculoSeleccionadoId > 0) {

    $stmtVehiculo = $conn->prepare("
        SELECT
            id,
            patente,
            marca,
            modelo,
            anio,
            kilometros
        FROM vehiculos
        WHERE id = ?
    ");

    $stmtVehiculo->bind_param(
        "i",
        $vehiculoSeleccionadoId
    );

    $stmtVehiculo->execute();

    $resultadoVehiculo =
        $stmtVehiculo->get_result();

    if (
        $resultadoVehiculo->num_rows > 0
    ) {

        $vehiculoPreseleccionado =
            $resultadoVehiculo->fetch_assoc();

    } else {

        $vehiculoSeleccionadoId = 0;
    }

    $stmtVehiculo->close();
}


// ==============================
// LISTADO DE VEHÍCULOS
// ==============================

$vehiculos = $conn->query("
    SELECT
        id,
        patente,
        marca,
        modelo
    FROM vehiculos
    ORDER BY id DESC
");

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Nuevo Peritaje
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <style>

        body {
            background: #f4f6f9;
            font-family: Arial, sans-serif;
        }

        .navbar-custom {
            background:
                linear-gradient(
                    90deg,
                    #b7babe,
                    #262a30
                );
        }

        .navbar-brand,
        .nav-link,
        .usuario-navbar {
            color: #fff !important;
            font-weight: 600;
        }

        .card-custom {
            border: none;
            border-radius: 16px;
            box-shadow:
                0 10px 25px
                rgba(0,0,0,0.08);
        }

        .titulo-seccion {
            color: #04060e;
            font-weight: 700;
        }

        .btn-principal {
            background: #484949;
            border: none;
            color: #fff;
        }

        .btn-principal:hover {
            background: #757677;
            color: #fff;
        }


        /* ==========================
           VEHÍCULO PRESELECCIONADO
        ========================== */

        .alerta-seleccion {
            background: #ecfdf3;
            border: 1px solid #d1fae5;
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 22px;
        }

        .alerta-seleccion span {
            display: block;
            color: #15803d;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .alerta-seleccion strong {
            color: #111827;
            font-size: 15px;
        }

    </style>

</head>


<body>


<nav
    class="navbar navbar-expand-lg navbar-custom"
>

    <div class="container">

        <a
            class="navbar-brand"
            href="index.php"
        >
            Sistema de Peritaje de Vehículos
        </a>


        <div class="collapse navbar-collapse">

            <ul
                class="navbar-nav ms-auto me-3"
            >

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="index.php"
                    >
                        Inicio
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link active"
                        href="peritajes.php"
                    >
                        Peritajes
                    </a>

                </li>

            </ul>


            <span class="usuario-navbar">

                Usuario:

                <?php
                echo htmlspecialchars(
                    $_SESSION['nombre']
                );
                ?>

            </span>

        </div>

    </div>

</nav>



<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-8">

            <div
                class="card card-custom p-4"
            >

                <h2
                    class="titulo-seccion mb-4"
                >
                    Nuevo Peritaje
                </h2>


                <!-- ==========================
                     ORIGEN DE LA SOLICITUD
                ========================== -->

                <?php
                if (
                    $solicitudId > 0 &&
                    $vehiculoPreseleccionado
                ):
                ?>

                    <div class="alerta-seleccion">

                        <span>
                            ✓ Vehículo seleccionado
                            desde Solicitud
                            #<?php echo $solicitudId; ?>
                        </span>

                        <strong>

                            <?php

                            echo htmlspecialchars(
                                $vehiculoPreseleccionado[
                                    'patente'
                                ]
                                .
                                ' - '
                                .
                                $vehiculoPreseleccionado[
                                    'marca'
                                ]
                                .
                                ' '
                                .
                                $vehiculoPreseleccionado[
                                    'modelo'
                                ]
                            );

                            ?>

                        </strong>

                    </div>

                <?php endif; ?>



                <form
                    action="guardar_peritaje.php"
                    method="POST"
                    enctype="multipart/form-data"
                >


                    <!-- ==========================
                         SOLICITUD DE ORIGEN
                    ========================== -->

                    <?php
                    if ($solicitudId > 0):
                    ?>

                        <input
                            type="hidden"
                            name="solicitud_id"
                            value="<?php
                                echo $solicitudId;
                            ?>"
                        >

                    <?php endif; ?>



                    <!-- ==========================
                         VEHÍCULO
                    ========================== -->

                    <div class="mb-3">

                        <label
                            for="vehiculo_id"
                            class="form-label"
                        >
                            Vehículo
                        </label>


                        <select
                            class="form-select"
                            id="vehiculo_id"
                            name="vehiculo_id"
                            required
                        >

                            <option value="">
                                Seleccionar vehículo
                            </option>


                            <?php
                            while (
                                $vehiculo =
                                $vehiculos->fetch_assoc()
                            ):
                            ?>

                                <option

                                    value="<?php
                                        echo $vehiculo['id'];
                                    ?>"

                                    <?php
                                    if (
                                        $vehiculoSeleccionadoId
                                        ===
                                        (int) $vehiculo['id']
                                    ) {
                                        echo 'selected';
                                    }
                                    ?>

                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $vehiculo['patente']
                                        .
                                        " - "
                                        .
                                        $vehiculo['marca']
                                        .
                                        " "
                                        .
                                        $vehiculo['modelo']
                                    );

                                    ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>



                    <!-- ==========================
                         ESTADO GENERAL
                    ========================== -->

                    <div class="mb-3">

                        <label
                            for="estado_general"
                            class="form-label"
                        >
                            Estado general
                        </label>


                        <select
                            class="form-select"
                            id="estado_general"
                            name="estado_general"
                            required
                        >

                            <option value="">
                                Seleccionar estado
                            </option>

                            <option value="Excelente">
                                Excelente
                            </option>

                            <option value="Muy Bueno">
                                Muy Bueno
                            </option>

                            <option value="Bueno">
                                Bueno
                            </option>

                            <option value="Regular">
                                Regular
                            </option>

                            <option value="Deficiente">
                                Deficiente
                            </option>

                        </select>

                    </div>



                    <!-- ==========================
                         OBSERVACIONES
                    ========================== -->

                    <div class="mb-3">

                        <label
                            for="observaciones"
                            class="form-label"
                        >
                            Observaciones
                        </label>


                        <textarea
                            class="form-control"
                            id="observaciones"
                            name="observaciones"
                            rows="4"
                            required
                        ></textarea>

                    </div>



                    <!-- ==========================
                         PRECIO ESTIMADO
                    ========================== -->

                    <div class="mb-3">

                        <label
                            for="precio_estimado"
                            class="form-label"
                        >
                            Precio estimado
                        </label>


                        <input
                            type="number"
                            step="0.01"
                            class="form-control"
                            id="precio_estimado"
                            name="precio_estimado"
                            required
                        >

                    </div>



                    <!-- ==========================
                         FOTOS
                    ========================== -->

                    <div class="mb-3">

                        <label
                            for="fotos"
                            class="form-label"
                        >
                            Fotos del vehículo
                        </label>


                        <input
                            type="file"
                            class="form-control"
                            id="fotos"
                            name="fotos[]"
                            multiple
                        >

                    </div>



                    <button
                        type="submit"
                        class="btn btn-principal"
                    >
                        Guardar Peritaje
                    </button>


                    <a
                        href="index.php"
                        class="btn btn-outline-secondary"
                    >
                        Volver
                    </a>


                </form>

            </div>

        </div>

    </div>

</div>


</body>

</html>

<?php

$conn->close();

?>