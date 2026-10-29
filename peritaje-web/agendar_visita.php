<?php
// =====================================================
// CONEXIÓN A LA BASE DE DATOS
// =====================================================

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
// RECIBIR TOKEN
// =====================================================

$token = $_GET['token'] ?? '';

if (empty($token)) {
    die("Enlace de visita inválido.");
}


// =====================================================
// BUSCAR PROPUESTA
// =====================================================

$sql = "
    SELECT
        p.id AS propuesta_id,
        p.estado AS propuesta_estado,
        p.precio_ofrecido,

        c.id AS cliente_id,
        c.nombre AS cliente_nombre,

        v.id AS vehiculo_id,
        v.marca,
        v.modelo,
        v.anio

    FROM propuestas p

    INNER JOIN clientes c
        ON c.id = p.cliente_id

    INNER JOIN vehiculos v
        ON v.id = p.vehiculo_id

    WHERE p.token_visita = ?

    LIMIT 1
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "s",
    $token
);

$stmt->execute();

$resultado = $stmt->get_result();

$propuesta = $resultado->fetch_assoc();

$stmt->close();


// =====================================================
// VALIDAR PROPUESTA
// =====================================================

if (!$propuesta) {
    die("El enlace de visita no es válido.");
}


// =====================================================
// COMPROBAR VISITA EXISTENTE
// =====================================================

$sqlVisitaExistente = "
    SELECT
        id,
        fecha,
        hora,
        estado
    FROM visitas
    WHERE propuesta_id = ?
    LIMIT 1
";

$stmtVisitaExistente = $conn->prepare(
    $sqlVisitaExistente
);

$stmtVisitaExistente->bind_param(
    "i",
    $propuesta['propuesta_id']
);

$stmtVisitaExistente->execute();

$resultadoVisitaExistente =
    $stmtVisitaExistente->get_result();

$visitaExistente =
    $resultadoVisitaExistente->fetch_assoc();

$stmtVisitaExistente->close();


// =====================================================
// GUARDAR VISITA
// =====================================================

$visitaGuardada = false;

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !$visitaExistente
) {

    $fecha = $_POST['fecha'] ?? '';
    $hora = $_POST['hora'] ?? '';

    if (empty($fecha) || empty($hora)) {
        die("Debés seleccionar una fecha y un horario.");
    }


    // =================================================
    // VALIDAR FECHA Y HORARIO
    // =================================================

    $horariosPermitidos = [
        '09:00',
        '10:00',
        '11:00',
        '15:00',
        '16:00',
        '17:00'
    ];


    // No permitir fechas anteriores

    if ($fecha < date('Y-m-d')) {
        die("La fecha seleccionada no es válida.");
    }


    // No permitir horarios fuera de los disponibles

    if (!in_array($hora, $horariosPermitidos, true)) {
        die("El horario seleccionado no es válido.");
    }


    // =================================================
    // COMPROBAR QUE EL TURNO SIGA DISPONIBLE
    // =================================================

    $sqlTurno = "
        SELECT id
        FROM visitas
        WHERE fecha = ?
          AND hora = ?
          AND estado IN ('Agendada', 'Reprogramada')
        LIMIT 1
    ";

    $stmtTurno = $conn->prepare($sqlTurno);

    $stmtTurno->bind_param(
        "ss",
        $fecha,
        $hora
    );

    $stmtTurno->execute();

    $resultadoTurno = $stmtTurno->get_result();

    if ($resultadoTurno->fetch_assoc()) {

        $stmtTurno->close();

        die(
            "Ese horario ya no está disponible. "
            . "Elegí otro turno."
        );
    }

    $stmtTurno->close();


    // =================================================
    // DATOS DE LA VISITA
    // =================================================

    $propuesta_id = (int) $propuesta['propuesta_id'];
    $cliente_id = (int) $propuesta['cliente_id'];
    $vehiculo_id = (int) $propuesta['vehiculo_id'];


    // =================================================
    // INSERTAR VISITA
    // =================================================

    $sqlVisita = "
        INSERT INTO visitas
        (
            propuesta_id,
            cliente_id,
            vehiculo_id,
            fecha,
            hora,
            estado
        )
        VALUES (?, ?, ?, ?, ?, 'Agendada')
    ";

    $stmtVisita = $conn->prepare($sqlVisita);

    $stmtVisita->bind_param(
        "iiiss",
        $propuesta_id,
        $cliente_id,
        $vehiculo_id,
        $fecha,
        $hora
    );


    if ($stmtVisita->execute()) {

        $stmtVisita->close();

        header(
            "Location: agendar_visita.php?token=" .
            urlencode($token)
        );

        exit();

    } else {

        $error = $stmtVisita->error;

        $stmtVisita->close();

        die(
            "Error al registrar la visita: " .
            $error
        );
    }
}

?>


<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Agendar visita | ALPERIT</title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f5f7;
            color: #2b2b2b;
        }


        .navbar {
            background: #252525;
            color: white;
            padding: 18px 40px;
            font-size: 21px;
            font-weight: bold;
        }


        .contenedor {
            max-width: 850px;
            margin: 45px auto;
            padding: 0 20px;
        }


        .titulo {
            margin-bottom: 25px;
        }


        .titulo h1 {
            margin: 0 0 8px;
            font-size: 30px;
        }


        .titulo p {
            margin: 0;
            color: #6c6c6c;
            font-size: 16px;
        }


        .card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.07);
        }


        .vehiculo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }


        .vehiculo h2 {
            margin: 0 0 8px;
            font-size: 23px;
        }


        .vehiculo p {
            margin: 0;
            color: #777;
        }


        .precio {
            font-size: 22px;
            font-weight: bold;
        }


        .campo {
            margin-top: 18px;
        }


        .campo label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }


        .campo input {
            width: 100%;
            padding: 13px;
            border: 1px solid #d6d6d6;
            border-radius: 9px;
            font-size: 16px;
        }


        .campo select {
            width: 100%;
            padding: 13px;
            border: 1px solid #d6d6d6;
            border-radius: 9px;
            font-size: 16px;
            background: white;
        }


        .btn {
            margin-top: 22px;
            background: #5b4fe9;
            color: white;
            border: none;
            border-radius: 9px;
            padding: 13px 22px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }


        .acciones-visita {
            margin: 20px 0;
        }


        .acciones-visita a {
            display: inline-block;
            margin-right: 12px;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
        }


        .btn-modificar {
            background: #eef2ff;
            color: #4338ca;
        }


        .btn-cancelar {
            background: #fee2e2;
            color: #b91c1c;
        }


        .estado-cancelada {
            color: #b91c1c;
            font-weight: bold;
        }


        .estado-activa {
            color: #15803d;
            font-weight: bold;
        }

    </style>

</head>


<body>


    <div class="navbar">
        ALPERIT
    </div>


    <main class="contenedor">


        <div class="titulo">

            <h1>
                Agendá tu visita
            </h1>

            <p>
                Elegí cuándo querés acercarte a conocer el vehículo.
            </p>

        </div>


        <!-- ==========================================
             VEHÍCULO
        =========================================== -->

        <div class="card vehiculo">

            <div>

                <h2>

                    <?php

                    echo htmlspecialchars(
                        $propuesta['marca'] . ' ' .
                        $propuesta['modelo']
                    );

                    ?>

                </h2>


                <p>

                    Año

                    <?php

                    echo htmlspecialchars(
                        $propuesta['anio']
                    );

                    ?>

                </p>

            </div>


            <div class="precio">

                USD

                <?php

                echo number_format(
                    $propuesta['precio_ofrecido'],
                    0,
                    ',',
                    '.'
                );

                ?>

            </div>

        </div>


        <!-- ==========================================
             VISITA EXISTENTE
        =========================================== -->

        <?php if ($visitaExistente): ?>

            <div class="card">


                <?php if ($visitaExistente['estado'] === 'Cancelada'): ?>


                    <!-- ==================================
                         VISITA CANCELADA
                    =================================== -->

                    <h2>
                        ✕ Visita cancelada
                    </h2>


                    <p>
                        Esta visita fue cancelada.
                    </p>


                    <p>

                        <strong>
                            Fecha:
                        </strong>

                        <?php

                        echo date(
                            'd/m/Y',
                            strtotime(
                                $visitaExistente['fecha']
                            )
                        );

                        ?>

                    </p>


                    <p>

                        <strong>
                            Horario:
                        </strong>

                        <?php

                        echo date(
                            'H:i',
                            strtotime(
                                $visitaExistente['hora']
                            )
                        );

                        ?>

                    </p>


                    <p>

                        <strong>
                            Estado:
                        </strong>

                        <span class="estado-cancelada">

                            <?php

                            echo htmlspecialchars(
                                $visitaExistente['estado']
                            );

                            ?>

                        </span>

                    </p>


                <?php else: ?>


                    <!-- ==================================
                         VISITA ACTIVA
                    =================================== -->

                    <h2>
                        ✓ Visita agendada
                    </h2>


                    <p>

                        Tu visita para conocer el

                        <strong>

                            <?php

                            echo htmlspecialchars(
                                $propuesta['marca'] . ' ' .
                                $propuesta['modelo']
                            );

                            ?>

                        </strong>

                        quedó programada correctamente.

                    </p>


                    <div class="acciones-visita">

                        <a
                            class="btn-modificar"
                            href="modificar_visita.php?token=<?php echo urlencode($token); ?>"
                        >
                            Modificar visita
                        </a>


                        <a
                            class="btn-cancelar"
                            href="cancelar_visita.php?token=<?php echo urlencode($token); ?>"
                        >
                            Cancelar visita
                        </a>

                    </div>


                    <p>

                        <strong>
                            Fecha:
                        </strong>

                        <?php

                        echo date(
                            'd/m/Y',
                            strtotime(
                                $visitaExistente['fecha']
                            )
                        );

                        ?>

                    </p>


                    <p>

                        <strong>
                            Horario:
                        </strong>

                        <?php

                        echo date(
                            'H:i',
                            strtotime(
                                $visitaExistente['hora']
                            )
                        );

                        ?>

                    </p>


                    <p>

                        <strong>
                            Estado:
                        </strong>

                        <span class="estado-activa">

                            <?php

                            echo htmlspecialchars(
                                $visitaExistente['estado']
                            );

                            ?>

                        </span>

                    </p>


                <?php endif; ?>


            </div>


        <?php else: ?>


            <!-- ==========================================
                 NUEVA VISITA
            =========================================== -->

            <div class="card">

                <h2>
                    Elegí el día de tu visita
                </h2>


                <form method="POST">


                    <div class="campo">

                        <label for="fecha">
                            Fecha
                        </label>


                        <input
                            type="date"
                            id="fecha"
                            name="fecha"
                            min="<?php echo date('Y-m-d'); ?>"
                            required
                        >

                    </div>


                    <div class="campo">

                        <label for="hora">
                            Horario
                        </label>


                        <select
                            id="hora"
                            name="hora"
                            required
                        >

                            <option value="">
                                Seleccionar horario
                            </option>

                            <option value="09:00">
                                09:00
                            </option>

                            <option value="10:00">
                                10:00
                            </option>

                            <option value="11:00">
                                11:00
                            </option>

                            <option value="15:00">
                                15:00
                            </option>

                            <option value="16:00">
                                16:00
                            </option>

                            <option value="17:00">
                                17:00
                            </option>

                        </select>

                    </div>


                    <button
                        type="submit"
                        class="btn"
                    >
                        Confirmar visita
                    </button>


                </form>

            </div>


        <?php endif; ?>


    </main>


    <script>

        const fechaInput =
            document.getElementById('fecha');

        const horaSelect =
            document.getElementById('hora');


        if (fechaInput && horaSelect) {

            fechaInput.addEventListener(
                'change',
                async function () {

                    const fecha = this.value;


                    // Reiniciar todos los horarios

                    Array.from(
                        horaSelect.options
                    ).forEach(function (opcion) {

                        if (opcion.value !== '') {

                            opcion.disabled = false;

                            opcion.textContent =
                                opcion.value;

                        }

                    });


                    horaSelect.value = '';


                    if (!fecha) {
                        return;
                    }


                    try {

                        const respuesta =
                            await fetch(
                                'horarios_disponibles.php?fecha=' +
                                encodeURIComponent(fecha)
                            );


                        const horariosOcupados =
                            await respuesta.json();


                        Array.from(
                            horaSelect.options
                        ).forEach(function (opcion) {

                            if (
                                horariosOcupados.includes(
                                    opcion.value
                                )
                            ) {

                                opcion.disabled = true;

                                opcion.textContent =
                                    opcion.value +
                                    ' - Ocupado';

                            }

                        });


                    } catch (error) {

                        console.error(
                            'Error al consultar horarios:',
                            error
                        );

                    }

                }
            );

        }

    </script>


</body>

</html>