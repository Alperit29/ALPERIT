<?php

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli(
    "127.0.0.1",
    "root",
    "",
    "peritaje_autos"
);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$clienteId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


// ==========================
// CLIENTE
// ==========================

$stmt = $conn->prepare("
    SELECT *
    FROM clientes
    WHERE id = ?
");

$stmt->bind_param("i", $clienteId);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    die("Cliente no encontrado.");
}

$cliente = $resultado->fetch_assoc();

$stmt->close();


// ==========================
// HISTORIAL DE SOLICITUDES
// ==========================

$stmtSolicitudes = $conn->prepare("
    SELECT *
    FROM solicitudes
    WHERE cliente_id = ?
    ORDER BY fecha_creacion DESC
");

$stmtSolicitudes->bind_param("i", $clienteId);
$stmtSolicitudes->execute();

$solicitudes = $stmtSolicitudes->get_result();

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
        Cliente - ALPERIT
    </title>

    <style>

        body {
            margin: 0;
            background: #f4f6f9;
            font-family: Arial, sans-serif;
            color: #111827;
        }

        .barra-superior {
            background: linear-gradient(
                90deg,
                #b7babe,
                #262a30
            );

            color: white;
            padding: 10px 18px;

            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .barra-superior a {
            color: white;
            text-decoration: none;
            font-weight: 600;
        }

        .contenedor {
            padding: 35px 18px;
        }

        .volver {
            display: inline-block;
            margin-bottom: 20px;
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        .card {
            background: white;
            border-radius: 14px;
            padding: 22px;
            margin-bottom: 20px;

            box-shadow:
                0 8px 22px
                rgba(0,0,0,0.06);
        }

        h1,
        h2 {
            margin-top: 0;
        }

        .datos-cliente {
            display: grid;
            grid-template-columns:
                repeat(4, 1fr);
            gap: 25px;
        }

        .dato span {
            display: block;
            color: #6b7280;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .dato strong {
            font-size: 14px;
        }

        .contador {
            display: inline-block;
            margin-top: 18px;
            padding: 7px 11px;
            border-radius: 7px;

            background: #eaf2ff;
            color: #1d4ed8;

            font-size: 12px;
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 13px 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }

        .estado {
            display: inline-block;
            background: #fff3cd;
            color: #856404;
            padding: 5px 8px;
            border-radius: 7px;
            font-weight: 700;
            font-size: 11px;
        }

        .btn-ver {
            display: inline-block;
            padding: 6px 10px;
            border: 1px solid #2563eb;
            color: #2563eb;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
        }

        @media (max-width: 800px) {

            .datos-cliente {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<div class="barra-superior">

    <strong>
        Alperit System
    </strong>

    <div>

        <a href="index.php">
            ← Volver al inicio
        </a>

        &nbsp;&nbsp;

        Usuario:
        <?php
        echo htmlspecialchars(
            $_SESSION['nombre']
        );
        ?>

    </div>

</div>


<div class="contenedor">

    <a
        href="clientes.php"
        class="volver"
    >
        ← Volver a clientes
    </a>


    <!-- FICHA DEL CLIENTE -->

    <div class="card">

        <h1>
            <?php
            echo htmlspecialchars(
                $cliente['nombre']
            );
            ?>
        </h1>

        


        <div class="datos-cliente">

            <div class="dato">

                <span>DNI</span>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $cliente['dni']
                    );
                    ?>
                </strong>

            </div>


            <div class="dato">

                <span>Email</span>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $cliente['email']
                    );
                    ?>
                </strong>

            </div>


            <div class="dato">

                <span>Teléfono</span>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $cliente['telefono'] ?? '-'
                    );
                    ?>
                </strong>

            </div>


            <div class="dato">

                <span>Cliente desde</span>

                <strong>

                    <?php
                    echo date(
                        "d/m/Y",
                        strtotime(
                            $cliente['fecha_alta']
                        )
                    );
                    ?>

                </strong>

            </div>

        </div>


        <span class="contador">

            <?php
            echo $solicitudes->num_rows;
            ?>

            solicitudes realizadas

        </span>

    </div>


    <!-- HISTORIAL -->

    <div class="card">

        <h2>
            Historial de solicitudes
        </h2>


        <table>

            <thead>

                <tr>
                    
                    <th>Vehículo</th>
                    <th>Rango de año</th>
                    <th>Km máx.</th>
                    <th>Presupuesto</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Acción</th>
                </tr>

            </thead>


            <tbody>

            <?php
            while (
                $solicitud =
                $solicitudes->fetch_assoc()
            ):
            ?>

                <tr>

                    


                    <td>

                        <?php

                        echo htmlspecialchars(
                            $solicitud['marca']
                            .
                            " "
                            .
                            ($solicitud['modelo'] ?? '')
                        );

                        ?>

                    </td>


                    <td>

                        <?php

                        echo
                            ($solicitud['anio_desde'] ?? '-')
                            .
                            " - "
                            .
                            ($solicitud['anio_hasta'] ?? '-');

                        ?>

                    </td>


                    <td>

                        <?php

                        echo $solicitud[
                            'kilometraje_maximo'
                        ]
                            ? number_format(
                                $solicitud[
                                    'kilometraje_maximo'
                                ],
                                0,
                                ',',
                                '.'
                            )
                            : '-';

                        ?>

                    </td>


                    <td>

                        <?php

                        if (
                            $solicitud[
                                'presupuesto_maximo'
                            ]
                        ) {

                            echo htmlspecialchars(
                                $solicitud['moneda']
                            )
                            .
                            " "
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

                    </td>


                    <td>

                        <span class="estado">

                            <?php
                            echo htmlspecialchars(
                                $solicitud['estado']
                            );
                            ?>

                        </span>

                    </td>


                    <td>

                        <?php

                        echo date(
                            "d/m/Y H:i",
                            strtotime(
                                $solicitud[
                                    'fecha_creacion'
                                ]
                            )
                        );

                        ?>

                    </td>


                    <td>

                        <a
                            href="detalle_solicitud.php?id=<?php echo $solicitud['id']; ?>"
                            class="btn-ver"
                        >
                            Ver
                        </a>

                    </td>

                </tr>

            <?php endwhile; ?>

            </tbody>

        </table>

    </div>

</div>


</body>
</html>

<?php

$stmtSolicitudes->close();
$conn->close();

?>