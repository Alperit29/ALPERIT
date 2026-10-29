<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
?>

<?php
$host = "127.0.0.1";
$usuario = getenv('ALPERIT_DB_USER') ?: 'root';
$contrasena = getenv('ALPERIT_DB_PASSWORD') ?: '';
$base_datos = "peritaje_autos";

$conn = new mysqli($host, $usuario, $contrasena, $base_datos);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$tipoFiltro = $_GET['tipo'] ?? 'Todos';

if ($tipoFiltro === 'Auto' || $tipoFiltro === 'Camioneta') {
    $sqlVehiculos = "SELECT * FROM vehiculos 
                     WHERE activo = 1 
                     AND tipo_rodado = '$tipoFiltro'
                     ORDER BY id DESC";
} else {
    $sqlVehiculos = "SELECT * FROM vehiculos 
                     WHERE activo = 1 
                     ORDER BY id DESC";
}

$resultadoVehiculos = $conn->query($sqlVehiculos);

$sqlUltimosPeritajes = "SELECT p.id AS id_peritaje, v.patente, v.marca, v.modelo, p.fecha_peritaje, p.estado_general, p.precio_estimado, u.nombre AS perito 
FROM peritajes p 
INNER JOIN vehiculos v ON p.vehiculo_id = v.id 
INNER JOIN usuarios u ON p.usuario_id = u.id 
ORDER BY p.id DESC 
LIMIT 3";

$resultadoPeritajes = $conn->query($sqlUltimosPeritajes);

$totalVehiculos = $conn->query(
    "SELECT COUNT(*) as total FROM vehiculos WHERE activo = 1"
)->fetch_assoc();

$totalPeritajes = $conn->query(
    "SELECT COUNT(*) as total FROM peritajes"
)->fetch_assoc();

$totalSolicitudes = $conn->query(
    "SELECT COUNT(*) as total FROM solicitudes WHERE estado = 'Pendiente'"
)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sistema de Peritaje</title>

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
            background: linear-gradient(90deg, #b6b6b8, #151518);
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
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }

        .titulo-seccion {
            color: #02060e;
            font-weight: 700;
        }

        .btn-principal {
            background: #615f5f;
            border: none;
            color: #ffffff;
        }

        .btn-principal:hover {
            color: #696666;
        }

        .btn-acceso {
            display: block;
            text-align: center;
            text-decoration: none;
            background: linear-gradient(90deg, #b8b9bb, #252c33);
            color: #fff;
            padding: 22px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 22px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            transition: 0.2s ease-in-out;
        }

        .btn-acceso small {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-top: 6px;
            opacity: 0.9;
        }

        .btn-acceso:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.2);
            color: #fff;
        }

        .tabla thead {
            background: #e9eef6;
        }

        .tabla-scroll {
            max-height: 360px;
            overflow-y: auto;
        }

        .scroll-box {
    flex: 1 1 0;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
    border-radius: 10px;
}

        .scroll-box table {
            margin-bottom: 0;
        }

        .scroll-box thead th {
    position: sticky;
    top: 0;
    background: #ffffff;
    z-index: 2;
}

        .badge-soft {
            background: #eaf2ff;
            color: #4b4c4d;
            padding: 8px 12px;
            border-radius: 10px;
            font-weight: 600;
        }

        .estado-bueno {
            color: #198754;
            font-weight: 600;
        }

        .estado-regular {
            color: #fd7e14;
            font-weight: 600;
        }

        .estado-deficiente {
            color: #dc3545;
            font-weight: 600;
        }

        .estado-muy-bueno,
        .estado-excelente {
            color: #0d6efd;
            font-weight: 600;
        }


        /* ==========================
           TARJETAS DEL DASHBOARD
        ========================== */

        .dashboard-card {
            min-height: 145px;
        }

        .icono-card {
            width: 55px;
            height: 55px;
            border-radius: 12px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 27px;
        }

        .icono-vehiculos {
            background: #eaf2ff;
        }

        .icono-peritajes {
            background: #e8f8ef;
        }

        .icono-solicitudes {
            background: #fff4cc;
        }

        .icono-sistema {
            background: #e8f8ef;
        }

        .card-solicitudes {
            border: 2px solid #f4c430 !important;
            background: #fffdf5;
        }

        .numero-solicitudes {
            color: #dc3545;
            font-weight: 700;
        }

        .estado-sistema {
            color: #198754;
            font-weight: 700;
        }

    </style>

</head>


<body>


<!-- ==========================
     BARRA SUPERIOR
========================== -->

<nav class="navbar navbar-expand-lg navbar-custom">

    <div class="container">

        <a class="navbar-brand" href="index.php">
            Alperit System
        </a>

        <button
            class="navbar-toggler bg-light"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#menu"
        >
            <span class="navbar-toggler-icon"></span>
        </button>


        <div class="collapse navbar-collapse" id="menu">

            <ul class="navbar-nav ms-auto me-3">

                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        Inicio
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="peritajes.php">
                        Peritajes
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="buscar.php">
                        Búsqueda
                    </a>
                </li>

                

                <li class="nav-item">

                    <a class="nav-link" href="solicitudes.php">

                        🔔 Solicitudes
                        (<?php echo $totalSolicitudes['total']; ?>)

                    </a>

                </li>

                <li class="nav-item">
  <a class="nav-link" href="dashboard.php">
    📊 Dashboard
  </a>
</li>
 <li class="nav-item">
    <a class="nav-link" href="clientes.php">
        👤 Clientes
    </a>
</li>

<li class="nav-item">
    <a class="nav-link" href="visitas.php">
        📅 Visitas
    </a>
</li>


            </ul>


            <span class="usuario-navbar">

                Usuario:
                <?php echo $_SESSION['nombre']; ?>

            </span>

        </div>

    </div>

</nav>



<!-- ==========================
     CONTENIDO PRINCIPAL
========================== -->

<div class="container py-4">


    <!-- ==========================
         TARJETAS SUPERIORES
    ========================== -->

    <div class="row g-3 mb-4">


        <!-- VEHÍCULOS -->

        <div class="col-lg-3 col-md-6">

            <div class="card card-custom dashboard-card p-3 h-100">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <div class="text-muted">
                            Vehículos cargados
                        </div>

                        <h2 class="mb-0">

                            <?php echo $totalVehiculos['total']; ?>

                        </h2>

                    </div>


                    <div class="icono-card icono-vehiculos">

                        🚗

                    </div>

                </div>


                <div class="mt-3">

                    <span class="badge-soft">
                        Registro
                    </span>

                </div>

            </div>

        </div>



        <!-- PERITAJES -->

        <div class="col-lg-3 col-md-6">

            <div class="card card-custom dashboard-card p-3 h-100">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <div class="text-muted">
                            Peritajes realizados
                        </div>

                        <h2 class="mb-0">

                            <?php echo $totalPeritajes['total']; ?>

                        </h2>

                    </div>


                    <div class="icono-card icono-peritajes">

                        📋

                    </div>

                </div>


                <div class="mt-3">

                    <span class="badge-soft">
                        Control
                    </span>

                </div>

            </div>

        </div>



        <!-- SOLICITUDES -->

        <div class="col-lg-3 col-md-6">

            <div class="card card-custom card-solicitudes dashboard-card p-3 h-100">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <div class="text-muted">
                            Solicitudes pendientes
                        </div>

                        <h2 class="mb-0 numero-solicitudes">

                            <?php echo $totalSolicitudes['total']; ?>

                        </h2>

                    </div>


                    <div class="icono-card icono-solicitudes">

                        🔔

                    </div>

                </div>


                <div class="mt-3">

                    <a
                        href="solicitudes.php"
                        class="btn btn-warning btn-sm fw-bold"
                    >
                        Ver solicitudes
                    </a>

                </div>

            </div>

        </div>



        <!-- SISTEMA -->

        <div class="col-lg-3 col-md-6">

            <div class="card card-custom dashboard-card p-3 h-100">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <div class="text-muted">
                            Sistema
                        </div>

                        <h2 class="mb-0 estado-sistema">
                            Activo
                        </h2>

                    </div>


                    <div class="icono-card icono-sistema">

                        📶

                    </div>

                </div>


                <div class="mt-3">

                    <span class="badge-soft">
                        Online
                    </span>

                </div>

            </div>

        </div>


    </div>



    <!-- ==========================
         ÚLTIMOS PERITAJES
    ========================== -->

    <div class="card card-custom p-3 mb-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <h3 class="titulo-seccion mb-0">
                Últimos Peritajes
            </h3>

            <span class="badge-soft">
                Actividad reciente
            </span>

        </div>


        <div class="table-responsive tabla-scroll">

            <table class="table tabla table-hover align-middle">

                <thead>

                    <tr>

                        
                        <th>Patente</th>
                        <th>Vehículo</th>
                        <th>Estado</th>
                        <th>Precio Estimado</th>
                        <th>Fecha</th>
                        <th>Perito</th>
                        <th>Acción</th>

                    </tr>

                </thead>


                <tbody>

                    <?php if ($resultadoPeritajes && $resultadoPeritajes->num_rows > 0): ?>

                        <?php while($fila = $resultadoPeritajes->fetch_assoc()): ?>

                            <?php

                            $claseEstado = '';

                            switch (strtolower($fila['estado_general'])) {

                                case 'bueno':

                                    $claseEstado = 'estado-bueno';

                                    break;

                                case 'regular':

                                    $claseEstado = 'estado-regular';

                                    break;

                                case 'deficiente':

                                    $claseEstado = 'estado-deficiente';

                                    break;

                                case 'muy bueno':

                                    $claseEstado = 'estado-muy-bueno';

                                    break;

                                case 'excelente':

                                    $claseEstado = 'estado-excelente';

                                    break;

                            }

                            ?>


                            <tr
                                style="cursor:pointer;"
                                onclick="window.location='detalle_peritaje.php?id=<?php echo $fila['id_peritaje']; ?>'"
                            >

                                

                                <td>
                                    <?php echo $fila['patente']; ?>
                                </td>

                                <td>

                                    <?php
                                    echo $fila['marca'] . " " . $fila['modelo'];
                                    ?>

                                </td>


                                <td>

                                    <?php

                                    $estado = strtolower($fila['estado_general']);

                                    $badge = 'bg-secondary';

                                    if ($estado == 'excelente')
                                        $badge = 'bg-primary';

                                    if ($estado == 'muy bueno')
                                        $badge = 'bg-success';

                                    if ($estado == 'bueno')
                                        $badge = 'bg-success';

                                    if ($estado == 'regular')
                                        $badge = 'bg-warning text-dark';

                                    if ($estado == 'deficiente')
                                        $badge = 'bg-danger';

                                    ?>

                                    <span class="badge <?php echo $badge; ?>">

                                        <?php echo $fila['estado_general']; ?>

                                    </span>

                                </td>


                                <td>

                                    $<?php
                                    echo number_format(
                                        $fila['precio_estimado'],
                                        0,
                                        ',',
                                        '.'
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo date(
                                        "d/m/Y",
                                        strtotime($fila['fecha_peritaje'])
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php echo $fila['perito']; ?>

                                </td>


                                <td>

                                    <a
                                        href="detalle_peritaje.php?id=<?php echo $fila['id_peritaje']; ?>"
                                        class="btn btn-sm btn-outline-dark"
                                    >
                                        Ver
                                    </a>

                                </td>

                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="8"
                                class="text-center text-muted"
                            >
                                No hay peritajes cargados todavía.
                            </td>

                        </tr>


                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>



    <!-- ==========================
         ACCESOS RÁPIDOS
    ========================== -->

    <div class="row g-4 mb-4">

        <div class="col-md-6">

            <a
                href="peritajes.php"
                class="btn-acceso"
            >

                + Registrar Peritaje

                <small>
                    Iniciar carga de tasación
                </small>

            </a>

        </div>


        <div class="col-md-6">

            <a
                href="buscar.php"
                class="btn-acceso"
            >

                Buscar Vehículo

                <small>
                    Consultar por patente e historial
                </small>

            </a>

        </div>

    </div>



    <!-- ==========================
         VEHÍCULOS
    ========================== -->

    <div class="row g-4">


        <!-- ALTA DE VEHÍCULO -->

        <div class="col-lg-4">

            <div class="card card-custom p-4">

                <h3 class="titulo-seccion mb-3">
                    Alta de Vehículo
                </h3>


                <form
    action="guardar_vehiculo.php"
    method="POST"
>

    <div class="mb-3">
        <label for="patente" class="form-label">
            Patente
        </label>

        <input
            type="text"
            class="form-control"
            id="patente"
            name="patente"
            maxlength="15"
            required
        >
    </div>


    <div class="mb-3">
        <label for="marca" class="form-label">
            Marca
        </label>

        <input
            type="text"
            class="form-control"
            id="marca"
            name="marca"
            maxlength="50"
            required
        >
    </div>


    <div class="mb-3">
        <label for="modelo" class="form-label">
            Modelo
        </label>

        <input
            type="text"
            class="form-control"
            id="modelo"
            name="modelo"
            maxlength="50"
            required
        >
    </div>


    <div class="row">

        <div class="col-md-6 mb-3">
            <label for="anio" class="form-label">
                Año
            </label>

            <input
                type="number"
                class="form-control"
                id="anio"
                name="anio"
                min="1900"
                max="2100"
                required
            >
        </div>


        <div class="col-md-6 mb-3">
            <label for="kilometros" class="form-label">
                Kilómetros
            </label>

            <input
                type="number"
                class="form-control"
                id="kilometros"
                name="kilometros"
                min="0"
                required
            >
        </div>

    </div>


    <div class="mb-3">
        <label for="transmision" class="form-label">
            Transmisión
        </label>

        <select
            class="form-select"
            id="transmision"
            name="transmision"
            required
        >
            <option value="">Seleccioná una opción</option>
            <option value="Manual">Manual</option>
            <option value="Automática">Automática</option>
        </select>
    </div>

    <div class="mb-3">
    <label for="tipo_rodado" class="form-label">
        Tipo de rodado
    </label>

    <select
        class="form-select"
        id="tipo_rodado"
        name="tipo_rodado"
        required
    >
        <option value="">Seleccioná una opción</option>
        <option value="Auto">Auto</option>
        <option value="Camioneta">Camioneta</option>
    </select>
</div>


    <div class="row">

        <div class="col-md-4 mb-3">
            <label for="moneda" class="form-label">
                Moneda
            </label>

            <select
                class="form-select"
                id="moneda"
                name="moneda"
                required
            >
                <option value="">Seleccioná</option>
                <option value="ARS">ARS</option>
                <option value="USD">USD</option>
            </select>
        </div>


        <div class="col-md-8 mb-3">
            <label for="precio" class="form-label">
                Precio
            </label>

            <input
                type="number"
                class="form-control"
                id="precio"
                name="precio"
                min="0"
                step="0.01"
                placeholder="Ej: 30000"
                required
            >
        </div>

    </div>


    <div class="d-flex gap-2">

        <button
            type="submit"
            class="btn btn-principal"
        >
            Guardar vehículo
        </button>

        <button
            type="reset"
            class="btn btn-outline-secondary"
        >
            Limpiar
        </button>

    </div>

</form>

            </div>

        </div>



        <!-- VEHÍCULOS CARGADOS -->

        <div class="col-lg-8">

            <div class="card card-custom p-4 h-100 d-flex flex-column">

                <div class="d-flex justify-content-between align-items-center mb-3">

    <h3 class="titulo-seccion mb-0">
        Vehículos Cargados
    </h3>

    <div class="d-flex align-items-center gap-2">

        <?php if ($_SESSION['rol'] == 'admin'): ?>
            <a
                href="vehiculos_baja.php"
                class="btn btn-sm btn-outline-secondary"
            >
                Ver bajas
            </a>
        <?php endif; ?>

        <form method="GET" class="d-flex align-items-center gap-2 mb-0">

    <label for="tipo" class="mb-0">
        Tipo:
    </label>

    <select
        name="tipo"
        id="tipo"
        class="form-select form-select-sm"
        onchange="this.form.submit()"
    >
        <option value="Todos" <?php echo $tipoFiltro === 'Todos' ? 'selected' : ''; ?>>
            Todos
        </option>

        <option value="Auto" <?php echo $tipoFiltro === 'Auto' ? 'selected' : ''; ?>>
            Auto
        </option>

        <option value="Camioneta" <?php echo $tipoFiltro === 'Camioneta' ? 'selected' : ''; ?>>
            Camioneta
        </option>
    </select>

</form>

        <span class="badge-soft">
            Últimos registros
        </span>

    </div>

</div>


                <div class="scroll-box">

                    <table class="table tabla table-hover align-middle">

                        <thead>

                            <tr>

                                <th>Marca</th>
                                <th>Modelo</th>
                                <th>Patente</th>
                                <th>Tipo</th>
                                <th>Año</th>
                                <th>Kilómetros</th>
                                <th>Acción</th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php if ($resultadoVehiculos && $resultadoVehiculos->num_rows > 0): ?>


                                <?php while($fila = $resultadoVehiculos->fetch_assoc()): ?>


                                    <tr>
                                        <td>
                                            <?php echo $fila['marca']; ?>
                                        </td>

                                        <td>
                                            <?php echo $fila['modelo']; ?>
                                        </td>
                                        

                                        <td>
                                            <?php echo $fila['patente']; ?>
                                        </td>

                                        
                                        <td>
                                            <?php echo $fila['tipo_rodado']; ?>
                                        </td>

                                        <td>
                                            <?php echo $fila['anio']; ?>
                                        </td>

                                        <td>

                                            <?php
                                            echo number_format(
                                                $fila['kilometros'],
                                                0,
                                                ',',
                                                '.'
                                            );
                                            ?>

                                        </td>


                                        <td class="d-flex gap-1">


                                            <?php if ($_SESSION['rol'] != 'consulta'): ?>

                                                <a
                                                    href="editar_vehiculo.php?id=<?php echo $fila['id']; ?>"
                                                    class="btn btn-sm btn-outline-primary px-2 py-1"
                                                >
                                                    Editar
                                                </a>

                                            <?php endif; ?>


                                            <?php if ($_SESSION['rol'] == 'admin'): ?>

                                                <a
                                                    href="eliminar_vehiculo.php?id=<?php echo $fila['id']; ?>"
                                                    class="btn btn-sm btn-outline-danger px-2 py-1"
                                                   
                                                >
                                                    Eliminar
                                                </a>

                                            <?php endif; ?>


                                        </td>

                                    </tr>


                                <?php endwhile; ?>


                            <?php else: ?>


                                <tr>

                                    <td
                                        colspan="7"
                                        class="text-center text-muted"
                                    >
                                        No hay vehículos cargados todavía.
                                    </td>

                                </tr>


                            <?php endif; ?>


                        </tbody>

                    </table>

                </div>

            </div>

        </div>


    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>