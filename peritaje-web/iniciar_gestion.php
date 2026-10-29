<?php

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}


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
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

function normalizarTexto($texto)
{
    $texto = trim((string) $texto);

    // Elimina espacios especiales
    $texto = str_replace("\xc2\xa0", " ", $texto);

    // Pasamos a minúsculas
    $texto = mb_strtolower($texto, 'UTF-8');

    // Quitamos tildes para comparar
    $texto = strtr($texto, [
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'ü' => 'u'
    ]);

    return $texto;
}


// ==============================
// ID DE SOLICITUD
// ==============================

$id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($id <= 0) {
    die("Solicitud inválida.");
}


// ==============================
// BUSCAR SOLICITUD
// ==============================

$stmt = $conn->prepare("
    SELECT *
    FROM solicitudes
    WHERE id = ?
");

$stmt->bind_param("i", $id);

$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    die("La solicitud no existe.");
}

$solicitud = $resultado->fetch_assoc();

$vehiculoSeleccionado = null;

if (!empty($solicitud['vehiculo_seleccionado_id'])) {

    $stmtSeleccionado = $conn->prepare("
        SELECT *
        FROM vehiculos
        WHERE id = ?
    ");

    $stmtSeleccionado->bind_param(
        "i",
        $solicitud['vehiculo_seleccionado_id']
    );

    $stmtSeleccionado->execute();

    $resultadoSeleccionado =
        $stmtSeleccionado->get_result();

    if ($resultadoSeleccionado->num_rows > 0) {
        $vehiculoSeleccionado =
            $resultadoSeleccionado->fetch_assoc();
    }

    $stmtSeleccionado->close();
}

$stmt->close();


// ==============================
// CAMBIAR ESTADO A EN GESTIÓN
// ==============================

if ($solicitud['estado'] === 'Pendiente') {

    $nuevoEstado = "En gestión";

    $stmtEstado = $conn->prepare("
        UPDATE solicitudes
        SET estado = ?
        WHERE id = ?
    ");

    $stmtEstado->bind_param(
        "si",
        $nuevoEstado,
        $id
    );

    $stmtEstado->execute();

    $stmtEstado->close();

    $solicitud['estado'] = $nuevoEstado;
}


// ==============================
// CONTROL DE BÚSQUEDA
// ==============================

$buscarCoincidencias =
    isset($_GET['buscar']) &&
    $_GET['buscar'] === '1';

$buscarAlternativas =
    isset($_GET['alternativas']) &&
    $_GET['alternativas'] === '1';

$vehiculosCompatibles = [];
$vehiculosAlternativos = [];


// ==============================
// EJECUTAR BÚSQUEDA
// ==============================

if ($buscarCoincidencias) {


    // =========================================
    // 1. COINCIDENCIAS EXACTAS
    // =========================================

    $sqlBusqueda = "
        SELECT *
        FROM vehiculos
        WHERE 1 = 1
    ";

    $tipos = "";
    $parametros = [];


    // MARCA
    if (!empty($solicitud['marca'])) {

        $sqlBusqueda .= "
            AND LOWER(TRIM(marca)) = LOWER(TRIM(?))
        ";

        $tipos .= "s";
        $parametros[] = $solicitud['marca'];
    }


    // MODELO
    if (!empty($solicitud['modelo'])) {

        $sqlBusqueda .= "
            AND LOWER(TRIM(modelo)) = LOWER(TRIM(?))
        ";

        $tipos .= "s";
        $parametros[] = $solicitud['modelo'];
    }


    // AÑO DESDE
    if (
        $solicitud['anio_desde'] !== null &&
        $solicitud['anio_desde'] !== ''
    ) {

        $sqlBusqueda .= "
            AND anio >= ?
        ";

        $tipos .= "i";
        $parametros[] =
            (int) $solicitud['anio_desde'];
    }


    // AÑO HASTA
    if (
        $solicitud['anio_hasta'] !== null &&
        $solicitud['anio_hasta'] !== ''
    ) {

        $sqlBusqueda .= "
            AND anio <= ?
        ";

        $tipos .= "i";
        $parametros[] =
            (int) $solicitud['anio_hasta'];
    }


    // TRANSMISIÓN
    if (!empty($solicitud['transmision'])) {

        $sqlBusqueda .= "
            AND LOWER(TRIM(transmision))
            = LOWER(TRIM(?))
        ";

        $tipos .= "s";
        $parametros[] =
            $solicitud['transmision'];
    }


    // KILOMETRAJE
    if (
        $solicitud['kilometraje_maximo'] !== null &&
        $solicitud['kilometraje_maximo'] !== ''
    ) {

        $sqlBusqueda .= "
            AND kilometros <= ?
        ";

        $tipos .= "i";
        $parametros[] =
            (int) $solicitud['kilometraje_maximo'];
    }


    // MONEDA
    if (!empty($solicitud['moneda'])) {

        $sqlBusqueda .= "
            AND UPPER(TRIM(moneda))
            = UPPER(TRIM(?))
        ";

        $tipos .= "s";
        $parametros[] =
            $solicitud['moneda'];
    }


    // PRECIO
    if (
        $solicitud['presupuesto_maximo'] !== null &&
        $solicitud['presupuesto_maximo'] !== ''
    ) {

        $sqlBusqueda .= "
            AND precio <= ?
        ";

        $tipos .= "d";
        $parametros[] =
            (float) $solicitud['presupuesto_maximo'];
    }


    $sqlBusqueda .= "
        ORDER BY precio ASC, anio DESC
    ";


    // ==============================
    // EJECUTAR CONSULTA EXACTA
    // ==============================

    $stmtBusqueda =
        $conn->prepare($sqlBusqueda);

    if (!$stmtBusqueda) {

        die(
            "Error preparando la búsqueda: " .
            $conn->error
        );
    }


    if (!empty($parametros)) {

        $bindParametros = [];

        $bindParametros[] = &$tipos;

        foreach (
            $parametros as $clave => $valor
        ) {

            $bindParametros[] =
                &$parametros[$clave];
        }

        call_user_func_array(
            [$stmtBusqueda, 'bind_param'],
            $bindParametros
        );
    }


    $stmtBusqueda->execute();

    $resultadoBusqueda =
        $stmtBusqueda->get_result();


    while (
        $vehiculo =
        $resultadoBusqueda->fetch_assoc()
    ) {

        $vehiculosCompatibles[] =
            $vehiculo;
    }


    $stmtBusqueda->close();

    if ($buscarAlternativas) {

    // =========================================
    // 2. ALTERNATIVAS CERCANAS
    // =========================================

    /*
        Las alternativas parten de la misma marca.

        Después calculamos cuántos criterios
        no cumple cada vehículo.

        Solo mostramos unidades que fallen
        en 1 o 2 criterios.
    */

    /*
    Para buscar alternativas evaluamos
    todo el stock disponible.

    Más abajo se determinará qué vehículos
    conservan mejor los criterios solicitados
    por el cliente.
*/

$sqlAlternativas = "
    SELECT *
    FROM vehiculos
    WHERE activo = 1
";

$stmtAlternativas =
    $conn->prepare(
        $sqlAlternativas
    );


    if (!$stmtAlternativas) {

        die(
            "Error preparando alternativas: " .
            $conn->error
        );
    }


    $stmtAlternativas->execute();

    $resultadoAlternativas =
        $stmtAlternativas->get_result();


    // IDs de coincidencias exactas
    $idsExactos = [];

    foreach (
        $vehiculosCompatibles
        as $exacto
    ) {

        $idsExactos[] =
            (int) $exacto['id'];
    }



    while (
        $vehiculo =
        $resultadoAlternativas->fetch_assoc()
    ) {


        // ==============================
        // NO REPETIR EXACTOS
        // ==============================

        if (
            in_array(
                (int) $vehiculo['id'],
                $idsExactos,
                true
            )
        ) {

            continue;
        }


        $motivos = [];

        // ==============================
// MARCA
// ==============================

if (
    !empty($solicitud['marca']) &&
    normalizarTexto($vehiculo['marca'])
    !==
    normalizarTexto($solicitud['marca'])
) {

    $motivos[] =
        "Marca diferente";
}


        // ==============================
        // MODELO
        // ==============================

        if (
            !empty($solicitud['modelo']) &&
            strtolower(
                trim(
                    (string)
                    $vehiculo['modelo']
                )
            )
            !==
            strtolower(
                trim(
                    (string)
                    $solicitud['modelo']
                )
            )
        ) {

            $motivos[] =
                "Modelo diferente";
        }


        // ==============================
        // AÑO DESDE
        // ==============================

        if (
            $solicitud['anio_desde'] !== null &&
            $solicitud['anio_desde'] !== ''
        ) {

            if ($vehiculo['anio'] === null) {

                $motivos[] =
                    "Año no informado";

            } elseif (
                (int) $vehiculo['anio']
                <
                (int) $solicitud['anio_desde']
            ) {

                $motivos[] =
                    "Año inferior al solicitado";
            }
        }


        // ==============================
        // AÑO HASTA
        // ==============================

        if (
            $solicitud['anio_hasta'] !== null &&
            $solicitud['anio_hasta'] !== ''
        ) {

            if ($vehiculo['anio'] === null) {

                if (
                    !in_array(
                        "Año no informado",
                        $motivos,
                        true
                    )
                ) {

                    $motivos[] =
                        "Año no informado";
                }

            } elseif (
                (int) $vehiculo['anio']
                >
                (int) $solicitud['anio_hasta']
            ) {

                $motivos[] =
                    "Año superior al máximo";
            }
        }

        // ==============================
// TRANSMISIÓN
// ==============================

if (!empty($solicitud['transmision'])) {

    if (empty($vehiculo['transmision'])) {

        $motivos[] =
            "Transmisión no informada";

    } elseif (
        normalizarTexto($vehiculo['transmision'])
        !==
        normalizarTexto($solicitud['transmision'])
    ) {

        $motivos[] =
            "Transmisión diferente";
    }
}

        // ==============================
        // KILOMETRAJE
        // ==============================

        if (
            $solicitud['kilometraje_maximo'] !== null &&
            $solicitud['kilometraje_maximo'] !== ''
        ) {

            if (
                $vehiculo['kilometros']
                === null
            ) {

                $motivos[] =
                    "Kilometraje no informado";

            } elseif (
                (int) $vehiculo['kilometros']
                >
                (int)
                $solicitud[
                    'kilometraje_maximo'
                ]
            ) {

                $motivos[] =
                    "Supera el kilometraje máximo";
            }
        }


        // ==============================
        // MONEDA
        // ==============================

        if (
            !empty(
                $solicitud['moneda']
            )
        ) {

            if (
                empty(
                    $vehiculo['moneda']
                )
            ) {

                $motivos[] =
                    "Moneda no informada";

            } elseif (
                strtoupper(
                    trim(
                        (string)
                        $vehiculo['moneda']
                    )
                )
                !==
                strtoupper(
                    trim(
                        (string)
                        $solicitud['moneda']
                    )
                )
            ) {

                $motivos[] =
                    "Moneda diferente";
            }
        }


        // ==============================
        // PRECIO
        // ==============================

        if (
            $solicitud[
                'presupuesto_maximo'
            ] !== null &&
            $solicitud[
                'presupuesto_maximo'
            ] !== ''
        ) {

            if (
                $vehiculo['precio']
                === null
            ) {

                $motivos[] =
                    "Precio no informado";

            } elseif (
                (float)
                $vehiculo['precio']
                >
                (float)
                $solicitud[
                    'presupuesto_maximo'
                ]
            ) {

                $motivos[] =
                    "Supera el presupuesto máximo";
            }
        }


        // ==============================
        // SOLO 1 O 2 DIFERENCIAS
        // ==============================

        if (
            count($motivos) >= 1 &&
            count($motivos) <= 2
        ) {

            $vehiculo['motivos'] =
                $motivos;

            $vehiculo['cantidad_fallas'] =
                count($motivos);

            $vehiculosAlternativos[] =
                $vehiculo;
        }

    }


    $stmtAlternativas->close();


    // ==============================
    // ORDENAR ALTERNATIVAS
    // ==============================

    usort(
    $vehiculosAlternativos,

    function ($a, $b) {

        // ==============================
        // PRIORIDAD 1:
        // MISMA MARCA PRIMERO
        // ==============================

        $aMarcaDiferente =
            in_array(
                "Marca diferente",
                $a['motivos'],
                true
            );

        $bMarcaDiferente =
            in_array(
                "Marca diferente",
                $b['motivos'],
                true
            );

        if (
            $aMarcaDiferente
            !==
            $bMarcaDiferente
        ) {

            return
                $aMarcaDiferente
                <=>
                $bMarcaDiferente;
        }


        // ==============================
        // PRIORIDAD 2:
        // MENOS DIFERENCIAS
        // ==============================

        if (
            $a['cantidad_fallas']
            !==
            $b['cantidad_fallas']
        ) {

            return
                $a['cantidad_fallas']
                <=>
                $b['cantidad_fallas'];
        }


        // ==============================
        // PRIORIDAD 3:
        // MENOR PRECIO
        // ==============================

        return
            (float) $a['precio']
            <=>
            (float) $b['precio'];
    }
);

} // cierre de buscarAlternativas

} // cierre de buscarCoincidencias

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
        Gestión de Solicitud - Alperit System
    </title>


    <style>

        * {
            box-sizing: border-box;
        }

        body {

            margin: 0;

            font-family:
                Arial,
                sans-serif;

            background: #f4f6f9;

            color: #111827;
        }


        .navbar {

            background:
                linear-gradient(
                    90deg,
                    #9b9b9d,
                    #202024
                );

            color: white;

            padding: 16px 28px;

            display: flex;

            justify-content:
                space-between;

            align-items: center;
        }


        .navbar a {

            color: white;

            text-decoration: none;

            font-weight: 600;
        }


        .contenedor {

            max-width: 1200px;

            margin: 35px auto;

            padding: 0 20px;
        }


        .titulo {

            margin-bottom: 25px;
        }


        .titulo h1 {

            margin: 0 0 6px 0;
        }


        .titulo p {

            margin: 0;

            color: #6b7280;
        }


        .estado {

            display: inline-block;

            margin-top: 12px;

            background: #eef2ff;

            color: #4f46e5;

            padding: 8px 12px;

            border-radius: 8px;

            font-weight: 700;

            font-size: 13px;
        }


        .grid {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 24px;
        }


        .card {

            background: white;

            border-radius: 14px;

            padding: 24px;

            border:
                1px solid #edf0f4;

            box-shadow:
                0 6px 20px
                rgba(
                    15,
                    23,
                    42,
                    0.07
                );
        }


        .card h2 {

            margin-top: 0;

            margin-bottom: 20px;

            font-size: 20px;
        }


        .dato {

            padding: 12px 0;

            border-bottom:
                1px solid #eef1f5;
        }


        .dato:last-child {

            border-bottom: none;
        }


        .dato span {

            display: block;

            color: #6b7280;

            font-size: 12px;

            margin-bottom: 4px;
        }


        .dato strong {

            font-size: 15px;
        }


        .busqueda {

            margin-top: 24px;
        }


        .busqueda-vacia {

            padding: 30px;

            text-align: center;

            color: #6b7280;

            background: #f8fafc;

            border-radius: 10px;

            border:
                1px dashed #cbd5e1;
        }


        .resultado-titulo {

            display: flex;

            justify-content:
                space-between;

            align-items: center;

            margin-bottom: 18px;
        }


        .cantidad-resultados {

            background: #eef2ff;

            color: #4f46e5;

            padding: 7px 11px;

            border-radius: 8px;

            font-size: 12px;

            font-weight: 700;
        }


        .vehiculo-compatible {

            border:
                1px solid #e5e7eb;

            border-radius: 12px;

            padding: 18px;

            margin-bottom: 15px;

            background: #ffffff;

            display: grid;

            grid-template-columns:
    1.5fr
    1fr
    1fr
    1fr
    auto;

            gap: 20px;

            align-items: center;
        }

        .vehiculo-accion {
    display: flex;
    justify-content: flex-end;
}

.btn-seleccionar {
    display: inline-block;
    padding: 9px 13px;
    background: #111827;
    color: white;
    text-decoration: none;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

.btn-seleccionar:hover {
    background: #374151;
}


        .vehiculo-principal strong {

            display: block;

            font-size: 17px;

            color: #111827;

            margin-bottom: 5px;
        }


        .vehiculo-principal span {

            color: #6b7280;

            font-size: 13px;
        }


        .vehiculo-dato span {

            display: block;

            font-size: 11px;

            color: #9ca3af;

            margin-bottom: 4px;
        }


        .vehiculo-dato strong {

            font-size: 14px;

            color: #374151;
        }


        .precio-compatible {

            font-size: 17px !important;

            color: #4f46e5 !important;
        }


        .badge-coincidencia {

            display: inline-block;

            margin-top: 9px;

            padding: 5px 9px;

            background: #ecfdf3;

            color: #15803d;

            border-radius: 7px;

            font-size: 11px;

            font-weight: 700;
        }


        .seccion-alternativas {

            margin-top: 30px;

            padding-top: 25px;

            border-top:
                1px solid #eef1f5;
        }


        .seccion-alternativas h3 {

            margin:
                0 0 5px 0;

            font-size: 18px;
        }


        .texto-alternativas {

            color: #6b7280;

            font-size: 13px;

            margin:
                0 0 18px 0;
        }


        .badge-alternativa {

            display: inline-block;

            margin-top: 9px;

            padding: 5px 9px;

            background: #fff7ed;

            color: #c2410c;

            border-radius: 7px;

            font-size: 11px;

            font-weight: 700;
        }


        .motivos-alternativa {

            margin-top: -8px;

            margin-bottom: 18px;

            padding: 10px 14px;

            background: #fff7ed;

            border-left:
                4px solid #f59e0b;

            border-radius: 6px;

            font-size: 12px;

            color: #92400e;
        }


        .sin-resultados {

            padding: 30px;

            text-align: center;

            border-radius: 10px;

            background: #f8fafc;

            border:
                1px dashed #d1d5db;

            color: #6b7280;
        }


        .sin-resultados strong {

            display: block;

            color: #374151;

            margin-bottom: 8px;

            font-size: 16px;
        }


        .acciones {

            margin-top: 24px;

            display: flex;

            justify-content:
                space-between;
        }


        .btn {

            text-decoration: none;

            padding: 11px 18px;

            border-radius: 7px;

            font-weight: 700;

            font-size: 14px;
        }


        .btn-volver {

            color: #374151;

            border:
                1px solid #d1d5db;

            background: white;
        }


        .btn-buscar {

            background: #4f46e5;

            color: white;
        }


        .btn-buscar:hover {

            background: #4338ca;
        }


        @media (
            max-width: 800px
        ) {

            .grid {

                grid-template-columns:
                    1fr;
            }


            .vehiculo-compatible {

                grid-template-columns:
                    1fr;
            }

        }

        .vehiculo-seleccionado {
    margin-top: 24px;
    border: 1px solid #d1fae5;
}

.seleccionado-encabezado {
    margin-bottom: 20px;
}

.badge-seleccionado {
    display: inline-block;
    background: #ecfdf3;
    color: #15803d;
    padding: 6px 10px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 10px;
}

.seleccionado-grid {
    display: grid;
    grid-template-columns:
        1fr
        1fr
        1fr
        1fr
        auto;
    gap: 20px;
    align-items: center;
}

.seleccionado-grid span {
    display: block;
    color: #6b7280;
    font-size: 11px;
    margin-bottom: 4px;
}

.seleccionado-grid strong {
    font-size: 14px;
}

.accion-peritaje {
    display: flex;
    justify-content: flex-end;
}

.btn-iniciar-peritaje {
    background: #4f46e5;
    color: white;
    text-decoration: none;
    padding: 10px 14px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

.btn-iniciar-peritaje:hover {
    background: #4338ca;
}

@media (max-width: 800px) {
    .seleccionado-grid {
        grid-template-columns: 1fr;
    }
}

    </style>

</head>


<body>


<div class="navbar">

    <strong>
        Alperit System
    </strong>

    <a href="solicitudes.php">
        ← Volver a solicitudes
    </a>

</div>



<div class="contenedor">


    <div class="titulo">

        <h1>
            Gestión de Solicitud
            #<?php echo $solicitud['id']; ?>
        </h1>


        <p>
            Revisión de criterios y búsqueda
            de vehículos compatibles
        </p>


        <span class="estado">

            <?php
            echo htmlspecialchars(
                $solicitud['estado']
            );
            ?>

        </span>

    </div>



    <div class="grid">


        <!-- DATOS DEL CLIENTE -->

        <div class="card">

            <h2>
                Datos del cliente
            </h2>


            <div class="dato">

                <span>
                    Cliente
                </span>

                <strong>

                    <?php
                    echo htmlspecialchars(
                        $solicitud['nombre']
                    );
                    ?>

                </strong>

            </div>


            <div class="dato">

                <span>
                    Email
                </span>

                <strong>

                    <?php
                    echo htmlspecialchars(
                        $solicitud['email']
                    );
                    ?>

                </strong>

            </div>


            <div class="dato">

                <span>
                    Teléfono
                </span>

                <strong>

                    <?php
                    echo htmlspecialchars(
                        $solicitud['telefono']
                        ?? '-'
                    );
                    ?>

                </strong>

            </div>

        </div>



        <!-- CRITERIOS SOLICITADOS -->

        <div class="card">

            <h2>
                Criterios solicitados
            </h2>


            <div class="dato">

                <span>
                    Vehículo
                </span>

                <strong>

                    <?php

                    echo htmlspecialchars(
                        trim(
                            $solicitud['marca'] .
                            ' ' .
                            (
                                $solicitud['modelo']
                                ?? ''
                            )
                        )
                    );

                    ?>

                </strong>

            </div>



            <div class="dato">

                <span>
                    Rango de año
                </span>

                <strong>

                    <?php

                    if (
                        $solicitud['anio_desde']
                        !== null &&
                        $solicitud['anio_hasta']
                        !== null
                    ) {

                        echo
                            $solicitud['anio_desde']
                            .
                            ' - '
                            .
                            $solicitud['anio_hasta'];

                    } elseif (
                        $solicitud['anio_desde']
                        !== null
                    ) {

                        echo
                            'Desde '
                            .
                            $solicitud['anio_desde'];

                    } elseif (
                        $solicitud['anio_hasta']
                        !== null
                    ) {

                        echo
                            'Hasta '
                            .
                            $solicitud['anio_hasta'];

                    } else {

                        echo '-';
                    }

                    ?>

                </strong>

            </div>



            <div class="dato">

                <span>
                    Transmisión
                </span>

                <strong>

                    <?php
                    echo htmlspecialchars(
                        $solicitud['transmision']
                        ?? '-'
                    );
                    ?>

                </strong>

            </div>



            <div class="dato">

                <span>
                    Kilometraje máximo
                </span>

                <strong>

                    <?php

                    echo
                        $solicitud[
                            'kilometraje_maximo'
                        ] !== null

                        ? number_format(
                            $solicitud[
                                'kilometraje_maximo'
                            ],
                            0,
                            ',',
                            '.'
                        )
                        . ' km'

                        : '-';

                    ?>

                </strong>

            </div>



            <div class="dato">

                <span>
                    Presupuesto máximo
                </span>

                <strong>

                    <?php

                    if (
                        $solicitud[
                            'presupuesto_maximo'
                        ] !== null
                    ) {

                        $moneda =
                            $solicitud['moneda']
                            ?? 'ARS';

                        echo
                            htmlspecialchars(
                                $moneda
                            )
                            .
                            ' '
                            .
                            number_format(
                                $solicitud[
                                    'presupuesto_maximo'
                                ],
                                0,
                                ',',
                                '.'
                            );

                    } else {

                        echo '-';
                    }

                    ?>

                </strong>

            </div>

        </div>

    </div>



    <!-- VEHÍCULOS -->

    <?php if ($vehiculoSeleccionado): ?>

    <div class="card vehiculo-seleccionado">

        <div class="seleccionado-encabezado">

            <div>
                <span class="badge-seleccionado">
                    ✓ Vehículo seleccionado
                </span>

                <h2>
                    <?php
                    echo htmlspecialchars(
                        $vehiculoSeleccionado['marca']
                        . ' '
                        . $vehiculoSeleccionado['modelo']
                    );
                    ?>
                </h2>
            </div>

        </div>


        <div class="seleccionado-grid">

            <div>
                <span>Patente</span>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $vehiculoSeleccionado['patente']
                    );
                    ?>
                </strong>
            </div>


            <div>
                <span>Año</span>

                <strong>
                    <?php
                    echo $vehiculoSeleccionado['anio'];
                    ?>
                </strong>
            </div>


            <div>
                <span>Kilómetros</span>

                <strong>
                    <?php
                    echo number_format(
                        $vehiculoSeleccionado['kilometros'],
                        0,
                        ',',
                        '.'
                    );
                    ?>
                    km
                </strong>
            </div>


            <div>
                <span>Precio</span>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $vehiculoSeleccionado['moneda']
                    );
                    ?>

                    <?php
                    echo number_format(
                        $vehiculoSeleccionado['precio'],
                        0,
                        ',',
                        '.'
                    );
                    ?>
                </strong>
            </div>


            <div class="accion-peritaje">

                <a
                    href="peritajes.php?solicitud=<?php echo $solicitud['id']; ?>&vehiculo=<?php echo $vehiculoSeleccionado['id']; ?>"
                    class="btn-iniciar-peritaje"
                >
                    Iniciar peritaje
                </a>

            </div>

        </div>

    </div>

<?php endif; ?>

    <div class="card busqueda">


        <div class="resultado-titulo">

            <h2>
                Vehículos compatibles
            </h2>


            <?php
            if ($buscarCoincidencias):
            ?>

                <span class="cantidad-resultados">

                    <?php
                    echo count(
                        $vehiculosCompatibles
                    );
                    ?>

                    coincidencia(s)

                </span>

            <?php endif; ?>

        </div>



        <?php
        if (!$buscarCoincidencias):
        ?>


            <div class="busqueda-vacia">

                Todavía no ejecutamos
                la búsqueda automática.

                <br><br>

                El sistema comparará
                los criterios de esta solicitud
                con los vehículos cargados.

            </div>


        <?php else: ?>


            <!-- ==============================
                 COINCIDENCIAS EXACTAS
            =============================== -->


            <?php
            if (
                count(
                    $vehiculosCompatibles
                ) > 0
            ):
            ?>


                <?php
                foreach (
                    $vehiculosCompatibles
                    as $vehiculo
                ):
                ?>


                    <div
                        class="vehiculo-compatible"
                    >


                        <div
                            class="vehiculo-principal"
                        >

                            <strong>

                                <?php

                                echo htmlspecialchars(
                                    $vehiculo['marca']
                                    .
                                    ' '
                                    .
                                    $vehiculo['modelo']
                                );

                                ?>

                            </strong>


                            <span>

                                Patente:

                                <?php
                                echo htmlspecialchars(
                                    $vehiculo['patente']
                                );
                                ?>

                            </span>


                            <div
                                class="badge-coincidencia"
                            >
                                ✓ Coincidencia exacta
                            </div>

                        </div>



                        <div class="vehiculo-dato">

                            <span>
                                Año / Kilómetros
                            </span>

                            <strong>

                                <?php
                                echo
                                    $vehiculo['anio'];
                                ?>

                                ·

                                <?php

                                echo number_format(
                                    $vehiculo['kilometros'],
                                    0,
                                    ',',
                                    '.'
                                );

                                ?>

                                km

                            </strong>

                        </div>



                        <div class="vehiculo-dato">

                            <span>
                                Transmisión
                            </span>

                            <strong>

                                <?php

                                echo htmlspecialchars(
                                    $vehiculo[
                                        'transmision'
                                    ]
                                    ?? '-'
                                );

                                ?>

                            </strong>

                        </div>



                        <div class="vehiculo-dato">

                            <span>
                                Precio
                            </span>

                            <strong
                                class="precio-compatible"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $vehiculo['moneda']
                                    ?? ''
                                );

                                ?>

                                <?php

                                echo number_format(
                                    $vehiculo['precio'],
                                    0,
                                    ',',
                                    '.'
                                );

                                ?>

                            </strong>

                        </div>

                        <div class="vehiculo-accion">

    <a
        href="seleccionar_vehiculo.php?solicitud=<?php echo $solicitud['id']; ?>&vehiculo=<?php echo $vehiculo['id']; ?>"
        class="btn-seleccionar"
    >
        Seleccionar vehículo
    </a>

</div>


                    </div>


                <?php endforeach; ?>


            <?php else: ?>

    <div class="sin-resultados">

        <strong>
            No encontramos una
            coincidencia exacta.
        </strong>

        Ningún vehículo cumple
        actualmente con todos
        los criterios solicitados.

        <br><br>

        <a
            href="iniciar_gestion.php?id=<?php echo $solicitud['id']; ?>&buscar=1&alternativas=1"
            class="btn btn-buscar"
        >
            Buscar alternativas
        </a>

    </div>

<?php endif; ?>



            <!-- ==============================
                 ALTERNATIVAS CERCANAS
            =============================== -->


            <?php
            if (
                count(
                    $vehiculosAlternativos
                ) > 0
            ):
            ?>


                <div
                    class="seccion-alternativas"
                >


                    <h3>
                        Alternativas cercanas
                    </h3>


                    <p
                        class="texto-alternativas"
                    >
                        Estos vehículos no cumplen
                        todos los criterios, pero
                        podrían ser opciones para
                        evaluar.
                    </p>



                    <?php
                    foreach (
                        $vehiculosAlternativos
                        as $vehiculo
                    ):
                    ?>


                        <div
                            class="vehiculo-compatible"
                        >


                            <div
                                class="vehiculo-principal"
                            >

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $vehiculo[
                                            'marca'
                                        ]
                                        .
                                        ' '
                                        .
                                        $vehiculo[
                                            'modelo'
                                        ]
                                    );

                                    ?>

                                </strong>


                                <span>

                                    Patente:

                                    <?php

                                    echo htmlspecialchars(
                                        $vehiculo[
                                            'patente'
                                        ]
                                    );

                                    ?>

                                </span>


                                <div
                                    class="badge-alternativa"
                                >
                                    ⚠ Alternativa cercana
                                </div>

                            </div>



                            <div
                                class="vehiculo-dato"
                            >

                                <span>
                                    Año / Kilómetros
                                </span>

                                <strong>

                                    <?php
                                    echo
                                        $vehiculo[
                                            'anio'
                                        ];
                                    ?>

                                    ·

                                    <?php

                                    echo number_format(
                                        $vehiculo[
                                            'kilometros'
                                        ],
                                        0,
                                        ',',
                                        '.'
                                    );

                                    ?>

                                    km

                                </strong>

                            </div>



                            <div
                                class="vehiculo-dato"
                            >

                                <span>
                                    Transmisión
                                </span>

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $vehiculo[
                                            'transmision'
                                        ]
                                        ?? '-'
                                    );

                                    ?>

                                </strong>

                            </div>



                            <div
                                class="vehiculo-dato"
                            >

                                <span>
                                    Precio
                                </span>

                                <strong
                                    class="precio-compatible"
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $vehiculo[
                                            'moneda'
                                        ]
                                        ?? ''
                                    );

                                    ?>

                                    <?php

                                    echo number_format(
                                        $vehiculo[
                                            'precio'
                                        ],
                                        0,
                                        ',',
                                        '.'
                                    );

                                    ?>

                                </strong>

                            </div>

                            <!-- SELECCIONAR VEHÍCULO -->
<div class="vehiculo-accion">

    <a
        href="seleccionar_vehiculo.php?solicitud=<?php echo $solicitud['id']; ?>&vehiculo=<?php echo $vehiculo['id']; ?>"
        class="btn-seleccionar"
    >
        Seleccionar vehículo
    </a>

</div>


                        </div>



                        <div
                            class="motivos-alternativa"
                        >

                            <strong>
                                No coincide en:
                            </strong>

                            <?php

                            echo htmlspecialchars(
                                implode(
                                    ' · ',
                                    $vehiculo[
                                        'motivos'
                                    ]
                                )
                            );

                            ?>

                        </div>


                    <?php endforeach; ?>


                </div>


            <?php endif; ?>


        <?php endif; ?>


    </div>



    <!-- ACCIONES -->

    <div class="acciones">


        <a
            href="solicitudes.php"
            class="btn btn-volver"
        >
            ← Volver
        </a>


        <a
            href="iniciar_gestion.php?id=<?php
                echo $solicitud['id'];
            ?>&buscar=1"
            class="btn btn-buscar"
        >
            Buscar coincidencias
        </a>


    </div>


</div>


</body>

</html>