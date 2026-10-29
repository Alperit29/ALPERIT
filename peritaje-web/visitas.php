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


// =====================================================
// BUSCAR VISITAS
// =====================================================

$sqlVisitas = "
    SELECT
        v.*,
        c.nombre AS cliente_nombre,
        ve.marca,
        ve.modelo,
        ve.anio,
        ve.patente
    FROM visitas v
    INNER JOIN clientes c
        ON c.id = v.cliente_id
    INNER JOIN vehiculos ve
        ON ve.id = v.vehiculo_id
    ORDER BY v.fecha ASC, v.hora ASC
";

$resultadoVisitas = $conn->query($sqlVisitas);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Visitas | Alperit System</title>


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


        /* ==========================================
           NAVBAR
        ========================================== */

        .navbar {
            background: linear-gradient(
                90deg,
                #a7a7a9,
                #202024
            );

            color: white;

            padding: 16px 30px;

            display: flex;

            justify-content: space-between;

            align-items: center;
        }


        .navbar h3 {
            margin: 0;
        }


        .navbar a {
            color: white;

            text-decoration: none;

            font-weight: bold;
        }


        /* ==========================================
           CONTENEDOR
        ========================================== */

        .contenedor {
            padding: 35px;

            max-width: 1400px;

            margin: auto;
        }


        /* ==========================================
           ENCABEZADO
        ========================================== */

        .encabezado {
            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 25px;
        }


        .titulo h1 {
            margin: 0 0 6px;

            font-size: 30px;
        }


        .titulo p {
            margin: 0;

            color: #6b7280;

            font-size: 15px;
        }


        /* ==========================================
           TARJETA PRINCIPAL
        ========================================== */

        .card {
            background: white;

            border-radius: 16px;

            padding: 28px;

            box-shadow:
                0 8px 25px rgba(0,0,0,0.07);
        }


        /* ==========================================
           CABECERA DE AGENDA
        ========================================== */

        .card-header {
            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 20px;
        }


        .card-header h2 {
            margin: 0;

            font-size: 20px;
        }


        .contador {
            background: #eef2ff;

            color: #3730a3;

            padding: 7px 12px;

            border-radius: 8px;

            font-size: 13px;

            font-weight: bold;
        }


        /* ==========================================
           TABLA
        ========================================== */

        .tabla-contenedor {
            overflow-x: auto;
        }


        table {
            width: 100%;

            border-collapse: collapse;
        }


        th {
            text-align: left;

            padding: 14px 12px;

            color: #6b7280;

            font-size: 13px;

            text-transform: uppercase;

            border-bottom: 2px solid #e5e7eb;
        }


        td {
            padding: 16px 12px;

            border-bottom: 1px solid #e5e7eb;

            font-size: 14px;
        }


        tr:last-child td {
            border-bottom: none;
        }


        tr:hover {
            background: #f9fafb;
        }


        /* ==========================================
           CLIENTE
        ========================================== */

        .cliente {
            font-weight: bold;

            color: #111827;
        }


        /* ==========================================
           VEHÍCULO
        ========================================== */

        .vehiculo {
            font-weight: bold;
        }


        .patente {
            display: inline-block;

            background: #f3f4f6;

            border: 1px solid #d1d5db;

            padding: 5px 9px;

            border-radius: 6px;

            font-size: 12px;

            font-weight: bold;

            letter-spacing: 0.5px;
        }


        /* ==========================================
           FECHA / HORA
        ========================================== */

        .fecha {
            font-weight: bold;
        }


        .hora {
            font-weight: bold;

            color: #374151;
        }


        /* ==========================================
           ESTADOS
        ========================================== */

        .estado {
            display: inline-block;

            background: #dcfce7;

            color: #166534;

            padding: 7px 12px;

            border-radius: 8px;

            font-weight: bold;

            font-size: 13px;
        }


        .estado.cancelada {
            background: #fee2e2;

            color: #b91c1c;
        }


        .estado.reprogramada {
            background: #fef3c7;

            color: #92400e;
        }


        /* ==========================================
           ACCIÓN
        ========================================== */

        .btn-ver {
            display: inline-block;

            padding: 7px 13px;

            border: 1px solid #2563eb;

            color: #2563eb;

            background: white;

            border-radius: 7px;

            text-decoration: none;

            font-weight: bold;

            font-size: 13px;
        }


        .btn-ver:hover {
            background: #2563eb;

            color: white;
        }


        /* ==========================================
           SIN VISITAS
        ========================================== */

        .sin-visitas {
            text-align: center;

            padding: 60px 20px;
        }


        .sin-visitas-icono {
            font-size: 48px;

            margin-bottom: 15px;
        }


        .sin-visitas h3 {
            margin: 0 0 8px;

            font-size: 20px;
        }


        .sin-visitas p {
            margin: 0;

            color: #6b7280;

            font-size: 14px;
        }


    </style>

</head>


<body>


<!-- ==========================================
     NAVBAR
========================================== -->

<div class="navbar">

    <h3>
        Alperit System
    </h3>


    <div>

        <a href="index.php">
            ← Volver al inicio
        </a>

        &nbsp;&nbsp;&nbsp;

        Usuario:
        <?php
        echo htmlspecialchars(
            $_SESSION['nombre']
        );
        ?>

    </div>

</div>


<!-- ==========================================
     CONTENIDO
========================================== -->

<div class="contenedor">


    <div class="encabezado">

        <div class="titulo">

            <h1>
                Visitas
            </h1>

            <p>
                Agenda de visitas de clientes a vehículos seleccionados
            </p>

        </div>

    </div>


    <div class="card">


        <div class="card-header">

            <h2>
                📅 Agenda de visitas
            </h2>


            <?php

            $totalVisitas = 0;

            if ($resultadoVisitas) {
                $totalVisitas =
                    $resultadoVisitas->num_rows;
            }

            ?>

            <span class="contador">

                <?php echo $totalVisitas; ?>

                <?php
                echo $totalVisitas === 1
                    ? ' visita'
                    : ' visitas';
                ?>

            </span>

        </div>


        <?php if ($totalVisitas > 0): ?>


            <div class="tabla-contenedor">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Cliente
                            </th>

                            <th>
                                Vehículo
                            </th>

                            <th>
                                Año
                            </th>

                            <th>
                                Patente
                            </th>

                            <th>
                                Fecha
                            </th>

                            <th>
                                Hora
                            </th>

                            <th>
                                Estado
                            </th>

                            <th>
                                Acción
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php while (
                        $visita =
                        $resultadoVisitas->fetch_assoc()
                    ): ?>


                        <tr>


                            <td>

                                <span class="cliente">

                                    <?php

                                    echo htmlspecialchars(
                                        $visita['cliente_nombre']
                                    );

                                    ?>

                                </span>

                            </td>


                            <td>

                                <span class="vehiculo">

                                    <?php

                                    echo htmlspecialchars(
                                        $visita['marca']
                                        . ' '
                                        . $visita['modelo']
                                    );

                                    ?>

                                </span>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $visita['anio']
                                );

                                ?>

                            </td>


                            <td>

                                <span class="patente">

                                    <?php

                                    echo htmlspecialchars(
                                        $visita['patente']
                                    );

                                    ?>

                                </span>

                            </td>


                            <td>

                                <span class="fecha">

                                    <?php

                                    echo date(
                                        'd/m/Y',
                                        strtotime(
                                            $visita['fecha']
                                        )
                                    );

                                    ?>

                                </span>

                            </td>


                            <td>

                                <span class="hora">

                                    <?php

                                    echo date(
                                        'H:i',
                                        strtotime(
                                            $visita['hora']
                                        )
                                    );

                                    ?>

                                </span>

                            </td>


                            <td>


                                <?php

                                $claseEstado =
                                    'estado';

                                if (
                                    $visita['estado']
                                    === 'Cancelada'
                                ) {

                                    $claseEstado =
                                        'estado cancelada';

                                } elseif (
                                    $visita['estado']
                                    === 'Reprogramada'
                                ) {

                                    $claseEstado =
                                        'estado reprogramada';
                                }

                                ?>


                                <span
                                    class="<?php
                                    echo $claseEstado;
                                    ?>"
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $visita['estado']
                                    );

                                    ?>

                                </span>


                            </td>


                            <td>

                                <a
                                    class="btn-ver"
                                    href="detalle_visita.php?id=<?php
                                    echo $visita['id'];
                                    ?>"
                                >
                                    Ver
                                </a>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                    </tbody>

                </table>

            </div>


        <?php else: ?>


            <!-- ==================================
                 SIN VISITAS
            =================================== -->

            <div class="sin-visitas">

                <div class="sin-visitas-icono">
                    📅
                </div>


                <h3>
                    No hay visitas agendadas
                </h3>


                <p>
                    Cuando un cliente confirme una visita,
                    aparecerá aquí.
                </p>

            </div>


        <?php endif; ?>


    </div>


</div>


</body>

</html>