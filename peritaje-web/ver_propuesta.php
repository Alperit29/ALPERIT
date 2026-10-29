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

$propuesta_id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($propuesta_id <= 0) {
    die("Propuesta no válida.");
}

$sql = "
    SELECT
    pr.id AS propuesta_id,
    pr.token_visita,
    pr.precio_ofrecido,
    pr.observaciones AS observaciones_propuesta,
        pr.estado AS estado_propuesta,
        pr.fecha_creacion,

        c.nombre,
        c.dni,
        c.email,
        c.telefono,

        v.patente,
        v.marca,
        v.modelo,
        v.anio,
        v.kilometros,
        v.transmision,
        v.moneda,

        p.estado_general,
        p.observaciones AS observaciones_peritaje

    FROM propuestas pr

    INNER JOIN clientes c
        ON c.id = pr.cliente_id

    INNER JOIN vehiculos v
        ON v.id = pr.vehiculo_id

    INNER JOIN peritajes p
        ON p.id = pr.peritaje_id

    WHERE pr.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $propuesta_id);
$stmt->execute();

$resultado = $stmt->get_result();
$propuesta = $resultado->fetch_assoc();

if (!$propuesta) {
    die("No se encontró la propuesta.");
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

<title>
    Propuesta #<?php echo $propuesta['propuesta_id']; ?>
    - ALPERIT
</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #eef1f5;
    font-family: Arial, Helvetica, sans-serif;
    color: #0b172a;
}

.pagina {
    max-width: 1050px;
    margin: 35px auto;
    padding: 0 20px 50px;
}


/* ==========================
   DOCUMENTO
========================== */

.documento {
    background: white;
    padding: 45px;
    border-radius: 14px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}


/* ==========================
   ENCABEZADO
========================== */

.encabezado {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    align-items: center;
    margin-bottom: 35px;
}

.marca {
    padding-right: 30px;
}

.logo {
    font-size: 46px;
    font-weight: 800;
    letter-spacing: 3px;
    color: #0b2748;
    margin: 0;
}

.slogan {
    font-size: 12px;
    letter-spacing: 4px;
    color: #475569;
    margin-top: 6px;
}

.datos-propuesta {
    border-left: 2px solid #1d4ed8;
    padding-left: 35px;
}

.datos-propuesta h1 {
    margin: 0 0 8px;
    font-size: 30px;
}

.numero {
    font-size: 21px;
    font-weight: 700;
    margin-bottom: 15px;
}

.meta {
    margin: 8px 0;
    font-size: 14px;
}

.estado {
    display: inline-block;
    background: #fff0bd;
    color: #7c5700;
    padding: 5px 12px;
    border-radius: 7px;
    font-weight: 700;
}


/* ==========================
   BLOQUES
========================== */

.grid-datos {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 15px;
}

.bloque {
    background: #f5f7fa;
    border-radius: 12px;
    padding: 24px;
}

.bloque h2 {
    margin: 0 0 22px;
    font-size: 19px;
    color: #0b2748;
}

.fila {
    display: grid;
    grid-template-columns: 145px 1fr;
    margin-bottom: 12px;
    font-size: 14px;
}

.fila strong {
    color: #0b172a;
}


/* ==========================
   PERITAJE
========================== */

.peritaje {
    margin-bottom: 15px;
}

.peritaje-contenido {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
}

.estado-peritaje {
    font-size: 20px;
    font-weight: 700;
    color: #15803d;
}


/* ==========================
   PROPUESTA ECONÓMICA
========================== */

.economica {
    margin-top: 15px;
    overflow: hidden;
    border-radius: 12px;
    border: 1px solid #dbe4ee;
}

.economica-titulo {
    background: #0b2748;
    color: white;
    padding: 17px 24px;
    font-size: 19px;
    font-weight: 700;
}

.economica-contenido {
    background: #eef5ff;
    padding: 28px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.concepto {
    font-size: 14px;
    font-weight: 700;
    color: #334155;
}

.precio {
    font-size: 38px;
    font-weight: 800;
    color: #174ea6;
}


/* ==========================
   OBSERVACIONES
========================== */

.observaciones {
    margin-top: 15px;
    background: #f5f7fa;
    padding: 24px;
    border-radius: 12px;
}

.observaciones h2 {
    margin: 0 0 15px;
    font-size: 18px;
}

.observaciones p {
    margin: 0;
    line-height: 1.6;
}


/* ==========================
   ACCIONES
========================== */

.acciones {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.btn {
    border: none;
    border-radius: 7px;
    padding: 11px 18px;
    font-weight: 700;
    font-size: 14px;
    text-decoration: none;
    cursor: pointer;
}

.btn-volver {
    background: #dfe4ea;
    color: #111827;
}

.btn-enviar {
    background: #2563eb;
    color: white;
}

.btn-enviar:hover {
    background: #1d4ed8;
}


/* ==========================
   RESPONSIVE
========================== */

@media (max-width: 750px) {

    .encabezado,
    .grid-datos,
    .peritaje-contenido {
        grid-template-columns: 1fr;
    }

    .datos-propuesta {
        border-left: none;
        border-top: 2px solid #1d4ed8;
        padding-left: 0;
        padding-top: 20px;
    }

    .economica-contenido {
        display: block;
    }

    .precio {
        margin-top: 15px;
        font-size: 30px;
    }

}

</style>

</head>

<body>

<div class="pagina">

<div class="documento">


    <!-- ==========================
         ENCABEZADO
    ========================== -->

    <div class="encabezado">

        <div class="marca">

            <div class="logo">
                ALPERIT
            </div>

            <div class="slogan">
                ENCONTRAMOS EL AUTO QUE BUSCÁS
            </div>

        </div>


        <div class="datos-propuesta">

            <h1>
                PROPUESTA COMERCIAL
            </h1>

            <div class="numero">
                N° <?php
                echo str_pad(
                    $propuesta['propuesta_id'],
                    4,
                    '0',
                    STR_PAD_LEFT
                );
                ?>
            </div>

            <div class="meta">

                <strong>Fecha de emisión:</strong>

                <?php
                echo date(
                    'd/m/Y',
                    strtotime($propuesta['fecha_creacion'])
                );
                ?>

            </div>

            <div class="meta">

                <strong>Estado:</strong>

                <span class="estado">
                    <?php
                    echo htmlspecialchars(
                        $propuesta['estado_propuesta']
                    );
                    ?>
                </span>

            </div>

        </div>

    </div>


    <!-- ==========================
         CLIENTE + VEHÍCULO
    ========================== -->

    <div class="grid-datos">


        <div class="bloque">

            <h2>👤 DATOS DEL CLIENTE</h2>

            <div class="fila">
                <strong>Nombre:</strong>
                <span>
                    <?php
                    echo htmlspecialchars(
                        $propuesta['nombre']
                    );
                    ?>
                </span>
            </div>

            <div class="fila">
                <strong>DNI:</strong>
                <span>
                    <?php
                    echo htmlspecialchars(
                        $propuesta['dni']
                    );
                    ?>
                </span>
            </div>

            <div class="fila">
                <strong>Email:</strong>
                <span>
                    <?php
                    echo htmlspecialchars(
                        $propuesta['email']
                    );
                    ?>
                </span>
            </div>

            <div class="fila">
                <strong>Teléfono:</strong>
                <span>
                    <?php
                    echo htmlspecialchars(
                        $propuesta['telefono']
                    );
                    ?>
                </span>
            </div>

        </div>


        <div class="bloque">

            <h2>🚙 DATOS DEL VEHÍCULO</h2>

            <div class="fila">

                <strong>Marca / Modelo:</strong>

                <span>
                    <?php
                    echo htmlspecialchars(
                        $propuesta['marca'] .
                        ' ' .
                        $propuesta['modelo']
                    );
                    ?>
                </span>

            </div>

            <div class="fila">

                <strong>Patente:</strong>

                <span>
                    <?php
                    echo htmlspecialchars(
                        $propuesta['patente']
                    );
                    ?>
                </span>

            </div>

            <div class="fila">

                <strong>Año:</strong>

                <span>
                    <?php echo $propuesta['anio']; ?>
                </span>

            </div>

            <div class="fila">

                <strong>Kilómetros:</strong>

                <span>
                    <?php
                    echo number_format(
                        $propuesta['kilometros'],
                        0,
                        ',',
                        '.'
                    );
                    ?> km
                </span>

            </div>

            <div class="fila">

                <strong>Transmisión:</strong>

                <span>
                    <?php
                    echo htmlspecialchars(
                        $propuesta['transmision']
                    );
                    ?>
                </span>

            </div>

        </div>

    </div>


    <!-- ==========================
         PERITAJE
    ========================== -->

    <div class="bloque peritaje">

        <h2>☑ RESULTADO DEL PERITAJE</h2>

        <div class="peritaje-contenido">

            <div>

                <div class="fila">

                    <strong>Estado general:</strong>

                    <span class="estado-peritaje">

                        <?php
                        echo htmlspecialchars(
                            $propuesta['estado_general']
                        );
                        ?>

                    </span>

                </div>

            </div>


            <div>

                <strong>Observaciones:</strong>

                <p>
                    <?php
                    echo htmlspecialchars(
                        $propuesta['observaciones_peritaje']
                    );
                    ?>
                </p>

            </div>

        </div>

    </div>


    <!-- ==========================
         PROPUESTA ECONÓMICA
    ========================== -->

    <div class="economica">

        <div class="economica-titulo">
            ▣ PROPUESTA ECONÓMICA
        </div>

        <div class="economica-contenido">

            <div class="concepto">
                PRECIO OFRECIDO POR EL VEHÍCULO
            </div>

            <div class="precio">

                <?php
                echo htmlspecialchars(
                    $propuesta['moneda']
                );
                ?>

                <?php
                echo number_format(
                    $propuesta['precio_ofrecido'],
                    2,
                    ',',
                    '.'
                );
                ?>

            </div>

        </div>

    </div>


    <!-- ==========================
         OBSERVACIONES
    ========================== -->

    <div class="observaciones">

        <h2>
            💬 OBSERVACIONES COMERCIALES
        </h2>

        <p>

            <?php

            if (
                !empty(
                    $propuesta['observaciones_propuesta']
                )
            ) {

                echo htmlspecialchars(
                    $propuesta['observaciones_propuesta']
                );

            } else {

                echo "Sin observaciones.";

            }

            ?>

        </p>

    </div>


</div>


<!-- BOTONES FUERA DEL DOCUMENTO -->

<div class="acciones">

    <a
        href="index.php"
        class="btn btn-volver"
    >
        ← Volver
    </a>

    <button
    type="button"
    class="btn btn-enviar"
    onclick="enviarPropuesta()"
>
    Enviar propuesta
</button>

<script>

async function enviarPropuesta() {

    const datos = {
    propuesta_id: <?php echo (int) $propuesta['propuesta_id']; ?>,

    // CLIENTE
    nombre: <?php echo json_encode($propuesta['nombre']); ?>,
    dni: <?php echo json_encode($propuesta['dni']); ?>,
    email: <?php echo json_encode($propuesta['email']); ?>,
    telefono: <?php echo json_encode($propuesta['telefono']); ?>,

    // VEHÍCULO
    vehiculo: <?php echo json_encode(
        $propuesta['marca'] . ' ' . $propuesta['modelo']
    ); ?>,
    patente: <?php echo json_encode($propuesta['patente']); ?>,
    anio: <?php echo json_encode($propuesta['anio']); ?>,
    kilometros: <?php echo json_encode($propuesta['kilometros']); ?>,
    transmision: <?php echo json_encode($propuesta['transmision']); ?>,

    // PERITAJE
    estado_peritaje: <?php echo json_encode($propuesta['estado_general']); ?>,
    observaciones_peritaje: <?php echo json_encode($propuesta['observaciones_peritaje']); ?>,

    // PROPUESTA
precio: <?php echo json_encode($propuesta['precio_ofrecido']); ?>,
moneda: <?php echo json_encode($propuesta['moneda']); ?>,
observaciones_propuesta: <?php echo json_encode($propuesta['observaciones_propuesta']); ?>,

url_agendar_visita: <?php
    echo json_encode(
        "http://localhost/peritaje-web/agendar_visita.php?token=" .
        $propuesta['token_visita']
    );
?>
};
    try {

        const respuesta = await fetch(
             ""URL_WEBHOOK_N8N_CONFIGURAR",",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(datos)
            }
        );

        if (!respuesta.ok) {
            throw new Error("Error al enviar la propuesta");
        }

        alert("Propuesta enviada correctamente a n8n");

    } catch (error) {

        console.error(error);

        alert("No se pudo enviar la propuesta");

    }

}

</script>

</div>

</div>

</body>

</html>

<?php

$stmt->close();
$conn->close();

?>