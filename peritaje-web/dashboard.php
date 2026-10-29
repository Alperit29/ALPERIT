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

$conn = new mysqli($host, $usuario, $contrasena, $base_datos);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");


// =====================================================
// FILTRO POR RANGO DE FECHAS
// =====================================================

$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');


// Validación básica de fechas
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
    $desde = date('Y-m-01');
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
    $hasta = date('Y-m-d');
}


// Si accidentalmente Desde es mayor que Hasta,
// intercambiamos las fechas.
if ($desde > $hasta) {
    $temporal = $desde;
    $desde = $hasta;
    $hasta = $temporal;
}


// =====================================================
// SOLICITUDES TOTALES DEL PERÍODO
// =====================================================

$totalSolicitudes = $conn->query("
    SELECT COUNT(*) AS total
    FROM solicitudes
    WHERE DATE(fecha_creacion) BETWEEN '$desde' AND '$hasta'
")->fetch_assoc();


// =====================================================
// SOLICITUDES PENDIENTES DEL PERÍODO
// =====================================================

$totalPendientes = $conn->query("
    SELECT COUNT(*) AS total
    FROM solicitudes
    WHERE estado = 'Pendiente'
    AND DATE(fecha_creacion) BETWEEN '$desde' AND '$hasta'
")->fetch_assoc();


// =====================================================
// ESTADOS DE SOLICITUDES DEL PERÍODO
// =====================================================

$estadosSolicitudes = $conn->query("
    SELECT estado, COUNT(*) AS cantidad
    FROM solicitudes
    WHERE DATE(fecha_creacion) BETWEEN '$desde' AND '$hasta'
    GROUP BY estado
    ORDER BY estado
");

$nombresEstados = [];
$cantidadEstados = [];

while ($estado = $estadosSolicitudes->fetch_assoc()) {

    $nombresEstados[] = $estado['estado'];
    $cantidadEstados[] = (int) $estado['cantidad'];

}


// =====================================================
// PERITAJES REALIZADOS EN EL PERÍODO
// =====================================================

$totalPeritajes = $conn->query("
    SELECT COUNT(*) AS total
    FROM peritajes
    WHERE DATE(fecha_peritaje) BETWEEN '$desde' AND '$hasta'
")->fetch_assoc();


// =====================================================
// VEHÍCULOS CARGADOS
// Se mantiene como inventario TOTAL.
// No depende del período.
// =====================================================


$totalPropuestas = $conn->query("
    SELECT COUNT(*) AS total
    FROM propuestas
    WHERE DATE(fecha_creacion) BETWEEN '$desde' AND '$hasta'
")->fetch_assoc();

$solicitudesConPropuesta = $conn->query("
    SELECT COUNT(DISTINCT s.id) AS total
    FROM solicitudes s
    INNER JOIN propuestas p
        ON p.solicitud_id = s.id
    WHERE DATE(s.fecha_creacion) BETWEEN '$desde' AND '$hasta'
")->fetch_assoc();

$tasaConversion = 0;

if ((int)$totalSolicitudes['total'] > 0) {
    $tasaConversion = round(
        (
            (int)$solicitudesConPropuesta['total']
            /
            (int)$totalSolicitudes['total']
        ) * 100,
        1
    );
}

$solicitudesConPeritaje = $conn->query("
    SELECT COUNT(DISTINCT s.id) AS total
    FROM solicitudes s
    INNER JOIN propuestas p
        ON p.solicitud_id = s.id
    WHERE p.peritaje_id IS NOT NULL
    AND DATE(s.fecha_creacion) BETWEEN '$desde' AND '$hasta'
")->fetch_assoc();

$totalVehiculos = $conn->query("
    SELECT COUNT(*) AS total
    FROM vehiculos
    WHERE activo = 1
")->fetch_assoc();

// ========================================
// DISTRIBUCIÓN DEL STOCK ACTIVO
// ========================================

$sqlStockRodados = "
    SELECT
        tipo_rodado,
        COUNT(*) AS total
    FROM vehiculos
    WHERE activo = 1
      AND tipo_rodado IN ('Auto', 'Camioneta')
    GROUP BY tipo_rodado
";

$resultadoStockRodados = $conn->query($sqlStockRodados);

$totalAutos = 0;
$totalCamionetas = 0;

while ($rodado = $resultadoStockRodados->fetch_assoc()) {

    if ($rodado['tipo_rodado'] === 'Auto') {
        $totalAutos = (int)$rodado['total'];
    }

    if ($rodado['tipo_rodado'] === 'Camioneta') {
        $totalCamionetas = (int)$rodado['total'];
    }
}

$totalStockActivo = $totalAutos + $totalCamionetas;

$porcentajeAutos = $totalStockActivo > 0
    ? round(($totalAutos / $totalStockActivo) * 100, 1)
    : 0;

$porcentajeCamionetas = $totalStockActivo > 0
    ? round(($totalCamionetas / $totalStockActivo) * 100, 1)
    : 0;



$sqlMotivosBaja = "
    SELECT motivo_baja, COUNT(*) AS total
    FROM vehiculos
    WHERE activo = 0
      AND motivo_baja IS NOT NULL
      AND motivo_baja <> ''
    GROUP BY motivo_baja
    ORDER BY total DESC
";

$resultadoMotivosBaja = $conn->query($sqlMotivosBaja);

// ========================================
// MARCAS MÁS SOLICITADAS
// ========================================

$sqlMarcasSolicitadas = "
    SELECT
        marca,
        COUNT(*) AS total
    FROM solicitudes
    WHERE marca IS NOT NULL
      AND marca <> ''
      AND DATE(fecha_creacion) BETWEEN '$desde' AND '$hasta'
    GROUP BY marca
    ORDER BY total DESC, marca ASC
    LIMIT 4
";

$resultadoMarcasSolicitadas = $conn->query($sqlMarcasSolicitadas);

// =====================================================
// ACTIVIDAD DEL SISTEMA
// Cruce: SOLICITUDES + PERITAJES POR FECHA
// =====================================================
$sqlActividad = "

    SELECT
        fecha,
        SUM(solicitudes) AS solicitudes,
        SUM(peritajes) AS peritajes,
        SUM(propuestas) AS propuestas

    FROM (

        SELECT
            DATE(fecha_creacion) AS fecha,
            COUNT(*) AS solicitudes,
            0 AS peritajes,
            0 AS propuestas

        FROM solicitudes

        WHERE DATE(fecha_creacion)
        BETWEEN '$desde' AND '$hasta'

        GROUP BY DATE(fecha_creacion)


        UNION ALL


        SELECT
            DATE(fecha_peritaje) AS fecha,
            0 AS solicitudes,
            COUNT(*) AS peritajes,
            0 AS propuestas

        FROM peritajes

        WHERE DATE(fecha_peritaje)
        BETWEEN '$desde' AND '$hasta'

        GROUP BY DATE(fecha_peritaje)


        UNION ALL


        SELECT
            DATE(fecha_creacion) AS fecha,
            0 AS solicitudes,
            0 AS peritajes,
            COUNT(*) AS propuestas

        FROM propuestas

        WHERE DATE(fecha_creacion)
        BETWEEN '$desde' AND '$hasta'

        GROUP BY DATE(fecha_creacion)

    ) actividad

    GROUP BY fecha

    ORDER BY fecha ASC

";

$resultadoActividad = $conn->query($sqlActividad);


$fechas = [];
$datosSolicitudes = [];
$datosPeritajes = [];
$datosPropuestas = [];


if ($resultadoActividad) {

    while ($fila = $resultadoActividad->fetch_assoc()) {

        $fechas[] = $fila['fecha'];

        $datosSolicitudes[] =
            (int) $fila['solicitudes'];

        $datosPeritajes[] =
            (int) $fila['peritajes'];

        $datosPropuestas[] =
            (int) $fila['propuestas'];
    }
}

$sqlUltimaActividad = "

    SELECT

        'Solicitud' AS tipo,
        id,
        fecha_creacion AS fecha,
        estado AS detalle

    FROM solicitudes

    WHERE DATE(fecha_creacion)
    BETWEEN '$desde' AND '$hasta'


    UNION ALL


    SELECT

        'Peritaje' AS tipo,
        id,
        fecha_peritaje AS fecha,
        estado_general AS detalle

    FROM peritajes

    WHERE DATE(fecha_peritaje)
    BETWEEN '$desde' AND '$hasta'


    UNION ALL


    SELECT

        'Propuesta' AS tipo,
        id,
        fecha_creacion AS fecha,
        estado AS detalle

    FROM propuestas

    WHERE DATE(fecha_creacion)
    BETWEEN '$desde' AND '$hasta'


    ORDER BY fecha DESC

    LIMIT 6

";

$resultadoUltimaActividad =
    $conn->query($sqlUltimaActividad);

?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Alperit System</title>

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

    /* =========================
       NAVBAR
    ========================= */

    .navbar {
        background: linear-gradient(90deg, #9b9b9d, #202024);
        color: white;
        padding: 16px 28px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .navbar a {
        color: white;
        text-decoration: none;
        font-weight: 600;
        margin-left: 20px;
    }

    .contenedor {
    padding: 15px 30px 30px;
}

    /* =========================
       TÍTULO
    ========================= */

    .titulo {
        margin-bottom: 12px;
    }

    .titulo h1 {
        margin: 0 0 6px 0;
    }

    .titulo p {
        margin: 0;
        color: #6b7280;
    }

    /* =========================
       FILTROS
    ========================= */

    .filtros-dashboard {
        background: #ffffff;
        border: 1px solid #edf0f4;
        border-radius: 14px;
        padding: 12px 20px;
        margin-bottom: 16px;

        display: flex;
        align-items: flex-end;
        gap: 14px;
        flex-wrap: wrap;

        box-shadow: 0 6px 20px rgba(15, 23, 42, 0.07);
    }

    .campo-filtro {
        display: flex;
        flex-direction: column;
        gap: 7px;
    }

    .campo-filtro label {
        font-size: 13px;
        font-weight: 600;
        color: #374151;
    }

    .campo-filtro input {
        min-width: 165px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 9px 12px;
        background: #ffffff;
        color: #111827;
        font-size: 14px;
        outline: none;
    }

    .campo-filtro input:focus {
        border-color: #4f46e5;
    }

    .btn-filtrar {
        border: none;
        border-radius: 8px;
        padding: 10px 18px;
        background: #4f46e5;
        color: #ffffff;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-filtrar:hover {
        background: #4338ca;
    }

    .btn-limpiar {
        border-radius: 8px;
        padding: 10px 18px;
        background: #eef2ff;
        color: #4f46e5;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
    }

    .btn-limpiar:hover {
        background: #e0e7ff;
    }

    /* =========================
       MÉTRICAS
    ========================= */

    .metricas {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 18px;
    }

    .card-metrica {
        background: white;
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 6px 20px rgba(15, 23, 42, 0.07);
        border: 1px solid #edf0f4;
        min-height: 145px;
    }

    .card-superior {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .card-metrica span {
        color: #6b7280;
        font-size: 14px;
    }

    .card-metrica strong {
        display: block;
        font-size: 32px;
        margin-top: 8px;
        color: #111827;
    }

    .descripcion-metrica {
        margin-top: 18px;
        font-size: 12px;
        color: #9ca3af;
    }

    .icono-metrica {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 23px;
    }

    .icono-solicitudes {
        background: #eef2ff;
    }

    .icono-pendientes {
        background: #fff7df;
    }

    .icono-peritajes {
        background: #eaf8f1;
    }

    .icono-vehiculos {
        background: #f0edff;
    }

    .icono-propuestas {
        background: #f3e8ff;
    }

    .icono-conversion {
        background: #fff4e5;
    }

    .valor-solicitudes {
        color: #4f46e5 !important;
    }

    .valor-pendientes {
        color: #d89b00 !important;
    }

    .valor-peritajes {
        color: #159a55 !important;
    }

    .valor-vehiculos {
        color: #5b45d6 !important;
    }

    .valor-propuestas {
        color: #7c3aed !important;
    }

    .valor-conversion {
        color: #ea580c !important;
    }

    /* =========================
       ENCABEZADOS DE GRÁFICOS
    ========================= */

    .encabezado-grafico {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .encabezado-grafico h2,
    .encabezado-grafico h3 {
        margin: 0;
        font-size: 20px;
        color: #1f2937;
    }

    .encabezado-grafico p {
        margin: 5px 0 0 0;
        color: #8b95a7;
        font-size: 13px;
    }

    /* =========================
       FILA DE 3 BLOQUES
    ========================= */

    .fila-inferior {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
        margin-top: 18px;
    }

    .bloque-estados,
    .bloque-actividad-reciente,
    .bloque-efectividad {
        background: white;
        border-radius: 14px;
        padding: 16px 18px;
        border: 1px solid #edf0f4;
        box-shadow: 0 6px 20px rgba(15, 23, 42, 0.07);
        min-width: 0;
        overflow: hidden;
    }

    /* =========================
       ESTADO DE SOLICITUDES
    ========================= */

    .contenido-estados {
        display: flex;
        align-items: center;
        gap: 18px;
    }

    .grafico-estados {
        width: 150px;
        height: 150px;
        flex-shrink: 0;
    }

    .resumen-estados {
        flex: 1;
    }

    .item-estado {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #eef1f5;
        font-size: 14px;
    }

    .estado-nombre {
        color: #374151;
        font-weight: 600;
    }

    .estado-cantidad {
        color: #4f46e5;
        font-weight: 700;
    }
    
    /* =========================
   MOTIVOS DE BAJA
========================= */

.resumen-bajas {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-top: 16px;
    padding: 13px 15px;
    background: #fff7f7;
    border: 1px solid #fee2e2;
    border-radius: 12px;
}

.resumen-bajas-numero {
    min-width: 52px;
    height: 52px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fee2e2;
    border-radius: 12px;
    color: #dc2626;
    font-size: 24px;
    font-weight: 800;
}

.resumen-bajas-texto {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.resumen-bajas-texto strong {
    font-size: 14px;
    font-weight: 700;
    color: #1f2937;
}

.resumen-bajas-texto span {
    margin-top: 3px;
    font-size: 11px;
    color: #8b95a7;
}


/* DETALLE DE MOTIVOS */

.grid-motivos-baja {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 9px;
    margin-top: 11px;
}

.motivo-resumen {
    padding: 10px 11px;
    background: #f8fafc;
    border: 1px solid #eef1f5;
    border-radius: 10px;
}

.motivo-resumen-titulo {
    display: flex;
    align-items: center;
    gap: 7px;
    min-width: 0;
}

.motivo-resumen-titulo strong {
    font-size: 12px;
    font-weight: 600;
    color: #374151;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.motivo-punto {
    width: 7px;
    height: 7px;
    flex-shrink: 0;
    border-radius: 50%;
    background: #6366f1;
}

.motivo-resumen-datos {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    margin-top: 7px;
}

.motivo-resumen-datos strong {
    font-size: 18px;
    font-weight: 800;
    color: #1f2937;
}

.motivo-resumen-datos span {
    font-size: 11px;
    font-weight: 600;
    color: #8b95a7;
}


/* COLORES POR MOTIVO */

.motivo-resumen:nth-child(1) .motivo-punto {
    background: #ef4444;
}

.motivo-resumen:nth-child(2) .motivo-punto {
    background: #f59e0b;
}

.motivo-resumen:nth-child(3) .motivo-punto {
    background: #10b981;
}

.motivo-resumen:nth-child(4) .motivo-punto {
    background: #6366f1;
}

    /* =========================
       EFECTIVIDAD DEL PROCESO
    ========================= */

    .efectividad-item {
        margin-top: 16px;
    }

    .efectividad-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 7px;
    }

    .efectividad-info strong {
        font-size: 14px;
        font-weight: 600;
        color: #1f2937;
    }

    .efectividad-info span {
        font-size: 16px;
        font-weight: 700;
        color: #4f46e5;
    }

    .barra-efectividad {
        width: 100%;
        height: 9px;
        background: #eef1f5;
        border-radius: 10px;
        overflow: hidden;
    }

    .barra-efectividad-progreso {
        height: 100%;
        background: #4f46e5;
        border-radius: 10px;
        min-width: 8px;
    }

    .efectividad-item small {
        display: block;
        margin-top: 6px;
        color: #9ca3af;
        font-size: 12px;
    }

    /* =========================
       EMBUDO DEL PROCESO
    ========================= */

    .bloque-proceso {
        margin-top: 24px;
        background: white;
        border-radius: 14px;
        padding: 22px;
        border: 1px solid #edf0f4;
        box-shadow: 0 6px 20px rgba(15, 23, 42, 0.07);
    }

    .proceso-grid {
        display: grid;
        grid-template-columns: 1fr auto 1fr auto 1fr;
        align-items: center;
        gap: 18px;
    }

    .proceso-etapa {
        background: #f8fafc;
        border-radius: 12px;
        padding: 22px;
        text-align: center;
    }

    .proceso-etapa span {
        display: block;
        color: #6b7280;
        font-size: 13px;
    }

    .proceso-etapa strong {
        display: block;
        margin-top: 8px;
        font-size: 30px;
        color: #4f46e5;
    }

    .proceso-flecha {
        font-size: 26px;
        color: #94a3b8;
        font-weight: bold;
    }

    /* =========================
       ACTIVIDAD DEL SISTEMA
    ========================= */

    .bloque-grafico {
        margin-top: 24px;
        background: white;
        border-radius: 14px;
        padding: 22px;
        border: 1px solid #edf0f4;
        box-shadow: 0 6px 20px rgba(15, 23, 42, 0.07);
    }

    .badge-periodo {
        background: #eef2ff;
        color: #4f46e5;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
    }

    .grafico-contenedor {
        height: 240px;
    }

    /* =========================
       ESTADOS VACÍOS
    ========================= */

    .actividad-vacia {
        color: #9ca3af;
        padding: 20px 0;
    }

    /* ========================================
   MARCAS MÁS SOLICITADAS
======================================== */

.bloque-tercero {
    background: #ffffff;
    border-radius: 14px;
    padding: 16px 18px;
    border: 1px solid #edf0f4;
    box-shadow: 0 6px 20px rgba(15, 23, 42, 0.07);
    min-width: 0;
    overflow: hidden;
}

.ranking-marcas {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.marca-ranking-item {
    display: grid;
    grid-template-columns: 46px 1fr auto;
    align-items: center;
    gap: 12px;

    padding: 9px 11px;

    background: #f8fafc;
    border: 1px solid #eef1f5;
    border-radius: 10px;

    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.marca-ranking-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
}

/* POSICIÓN 01 / 02 / 03 / 04 */

.marca-posicion {
    width: 40px;
    height: 40px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 10px;

    background: #eef2ff;
    color: #4f46e5;

    font-size: 16px;
    font-weight: 800;
}

/* MARCA + CANTIDAD */

.marca-datos {
    min-width: 0;
}

.marca-datos strong {
    display: block;

    color: #1f2937;
    font-size: 14px;
    font-weight: 700;

    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.marca-datos span {
    display: block;

    margin-top: 3px;

    color: #8b95a7;
    font-size: 12px;
}

/* PORCENTAJE */

.marca-porcentaje {
    min-width: 58px;

    padding: 6px 9px;

    background: #eef2ff;
    color: #4f46e5;

    border-radius: 8px;

    text-align: center;

    font-size: 13px;
    font-weight: 700;
}

/* DIFERENCIAMOS VISUALMENTE EL TOP 3 */

.marca-ranking-item:nth-child(1) .marca-posicion {
    background: #ede9fe;
    color: #6d28d9;
}

.marca-ranking-item:nth-child(2) .marca-posicion {
    background: #e0f2fe;
    color: #0284c7;
}

.marca-ranking-item:nth-child(3) .marca-posicion {
    background: #fff7ed;
    color: #ea580c;
}

.marca-ranking-item:nth-child(4) .marca-posicion {
    background: #f1f5f9;
    color: #64748b;
}

/* ========================================
   STOCK DISPONIBLE - CARD MÉTRICA
======================================== */

.card-stock {
    padding: 16px 18px;
    display: flex;
    flex-direction: column;
}

/* Encabezado */

.stock-encabezado {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.stock-encabezado > div:first-child {
    display: flex;
    align-items: baseline;
    gap: 7px;
    flex-wrap: wrap;
}

.stock-encabezado > div:first-child > span {
    width: 100%;
    font-size: 14px;
    color: #6b7280;
}

.stock-total {
    font-size: 28px !important;
    margin: 4px 0 0 0 !important;
    color: #4f46e5 !important;
    line-height: 1;
}

.stock-encabezado small {
    font-size: 11px;
    color: #9ca3af;
}


/* AUTOS / CAMIONETAS */

.stock-detalle {
    display: grid;
    grid-template-columns: 1fr 1px 1fr;
    align-items: center;
    margin-top: 12px;
}

.stock-tipo {
    display: grid;
    grid-template-columns: auto 1fr;
    align-items: center;
    column-gap: 6px;
}

.stock-tipo strong {
    font-size: 18px !important;
    margin: 0 !important;
    line-height: 1;
    color: #1f2937 !important;
}

.stock-tipo span {
    font-size: 12px;
    color: #374151;
}

.stock-tipo small {
    grid-column: 1 / -1;
    margin-top: 3px;
    font-size: 10px;
    color: #8b95a7;
}

.stock-separador {
    width: 1px;
    height: 28px;
    background: #e5e7eb;
    margin: 0 10px;
}


/* BARRA DE DISTRIBUCIÓN */

.stock-barra {
    display: flex;
    width: 100%;
    height: 6px;
    margin-top: 10px;

    background: #eef1f5;

    border-radius: 20px;
    overflow: hidden;
}

.stock-barra-autos {
    height: 100%;
    background: #3b82f6;
}

.stock-barra-camionetas {
    height: 100%;
    background: #8b5cf6;
}




    /* =========================
       RESPONSIVE
    ========================= */

    @media (max-width: 900px) {

        .metricas {
            grid-template-columns: repeat(2, 1fr);
        }

        .fila-inferior {
            grid-template-columns: 1fr;
        }

        .proceso-grid {
            grid-template-columns: 1fr;
        }

        .proceso-flecha {
            transform: rotate(90deg);
            text-align: center;
        }
    }

    @media (max-width: 600px) {

        .metricas {
            grid-template-columns: 1fr;
        }

        .filtros-dashboard {
            align-items: stretch;
        }

        .campo-filtro,
        .campo-filtro input,
        .btn-filtrar,
        .btn-limpiar {
            width: 100%;
        }

        .btn-limpiar {
            text-align: center;
        }

        .contenido-estados {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>


</head>

<body>

<div class="navbar">
    <strong>Alperit System</strong>

    <div>
        <a href="index.php">Inicio</a>
        <a href="solicitudes.php">Solicitudes</a>
        <a href="dashboard.php">Dashboard</a>
    </div>
</div>

<div class="contenedor">

    <div class="titulo">
        <h1>Dashboard General</h1>
        <p>Resumen general de la actividad del sistema</p>
    </div>

    <form method="GET" class="filtros-dashboard">

        <div class="campo-filtro">
            <label for="desde">Desde</label>

            <input
                type="date"
                id="desde"
                name="desde"
                value="<?php echo htmlspecialchars($desde); ?>"
            >
        </div>

        <div class="campo-filtro">
            <label for="hasta">Hasta</label>

            <input
                type="date"
                id="hasta"
                name="hasta"
                value="<?php echo htmlspecialchars($hasta); ?>"
            >
        </div>

        <button
            type="submit"
            class="btn-filtrar"
        >
            Aplicar Busqueda
        </button>

        <a
            href="dashboard.php"
            class="btn-limpiar"
        >
            Limpiar
        </a>

    </form>


    <!-- ========================= -->
    <!-- MÉTRICAS PRINCIPALES -->
    <!-- ========================= -->

    <div class="metricas">

        <div class="card-metrica">
            <div class="card-superior">
                <div>
                    <span>Solicitudes totales</span>

                    <strong class="valor-solicitudes">
                        <?php echo $totalSolicitudes['total']; ?>
                    </strong>
                </div>

                <div class="icono-metrica icono-solicitudes">
                    📄
                </div>
            </div>

            <div class="descripcion-metrica">
                Solicitudes registradas en el sistema
            </div>
        </div>


        <div class="card-metrica">

            <div class="card-superior">

                <div>
                    <span>Propuestas generadas</span>

                    <strong class="valor-propuestas">
                        <?php echo $totalPropuestas['total']; ?>
                    </strong>
                </div>

                <div class="icono-metrica icono-propuestas">
                    💰
                </div>

            </div>

            <div class="descripcion-metrica">
                Propuestas generadas en el período
            </div>

        </div>


        <div class="card-metrica">

            <div class="card-superior">

                <div>
                    <span>Peritajes realizados</span>

                    <strong class="valor-peritajes">
                        <?php echo $totalPeritajes['total']; ?>
                    </strong>
                </div>

                <div class="icono-metrica icono-peritajes">
                    📋
                </div>

            </div>

            <div class="descripcion-metrica">
                Peritajes registrados hasta el momento
            </div>

        </div>

         <div class="card-metrica card-stock">

    <div class="stock-encabezado">

        <div>
            <span>Stock disponible</span>

            <strong class="stock-total">
                <?php echo $totalVehiculos['total']; ?>
            </strong>

            <small>vehículos activos</small>
        </div>

        <div class="icono-metrica icono-vehiculos">
            🚗
        </div>

    </div>


    <div class="stock-detalle">

        <div class="stock-tipo">
            <strong>
                <?php echo $totalAutos; ?>
            </strong>

            <span>Autos</span>

            <small>
                <?php echo $porcentajeAutos; ?>%
            </small>
        </div>


        <div class="stock-separador"></div>


        <div class="stock-tipo">
            <strong>
                <?php echo $totalCamionetas; ?>
            </strong>

            <span>Camionetas</span>

            <small>
                <?php echo $porcentajeCamionetas; ?>%
            </small>
        </div>

    </div>


    <div class="stock-barra">

        <div
            class="stock-barra-autos"
            style="width: <?php echo $porcentajeAutos; ?>%;">
        </div>

        <div
            class="stock-barra-camionetas"
            style="width: <?php echo $porcentajeCamionetas; ?>%;">
        </div>

    </div>

</div>


        <div class="card-metrica">

            <div class="card-superior">

                <div>
                    <span>Conversión a propuesta</span>

                    <strong class="valor-conversion">
                        <?php echo $tasaConversion; ?>%
                    </strong>
                </div>

                <div class="icono-metrica icono-conversion">
                    📈
                </div>

            </div>

            <div class="descripcion-metrica">
                <?php
                echo $solicitudesConPropuesta['total']
                    . " de "
                    . $totalSolicitudes['total']
                    . " solicitudes llegaron a propuesta";
                ?>
            </div>

        </div>

    </div>


    <!-- ================================= -->
<!-- ESTADOS + MOTIVOS + EFECTIVIDAD -->
<!-- ================================= -->

<div class="fila-inferior">

    <!-- ESTADO DE SOLICITUDES -->

    <div class="bloque-estados">

        <div class="encabezado-grafico">
            <div>
                <h3>Estado de solicitudes</h3>
                <p>Distribución de solicitudes en el período seleccionado</p>
            </div>
        </div>

        <div class="contenido-estados">

            <div class="grafico-estados">
                <canvas id="graficoEstados"></canvas>
            </div>

            <div
                class="resumen-estados"
                id="resumenEstados">
            </div>

        </div>

    </div>

 <!-- MOTIVOS DE BAJA -->

<div class="bloque-actividad-reciente">

    <div class="encabezado-grafico">
        <div>
            <h2>Motivos de baja</h2>
            <p>Vehículos dados de baja del sistema</p>
        </div>
    </div>

    <?php

    $motivos = [];
    $totalBajas = 0;

    if ($resultadoMotivosBaja) {

        while ($baja = $resultadoMotivosBaja->fetch_assoc()) {

            $baja['total'] = (int)$baja['total'];

            $motivos[] = $baja;

            $totalBajas += $baja['total'];
        }
    }

    ?>

    <?php if ($totalBajas > 0): ?>

        <!-- RESUMEN TOTAL -->

        <div class="resumen-bajas">

            <div class="resumen-bajas-numero">
                <?php echo $totalBajas; ?>
            </div>

            <div class="resumen-bajas-texto">
                <strong>Vehículos dados de baja</strong>
                <span>Total registrado</span>
            </div>

        </div>


        <!-- DETALLE POR MOTIVO -->

        <div class="grid-motivos-baja">

            <?php foreach ($motivos as $baja): ?>

                <?php

                $porcentajeBaja = $totalBajas > 0
                    ? round(($baja['total'] / $totalBajas) * 100, 1)
                    : 0;

                ?>

                <div class="motivo-resumen">

                    <div class="motivo-resumen-titulo">

                        <span class="motivo-punto"></span>

                        <strong>
                            <?php echo htmlspecialchars($baja['motivo_baja']); ?>
                        </strong>

                    </div>

                    <div class="motivo-resumen-datos">

                        <strong>
                            <?php echo $baja['total']; ?>
                        </strong>

                        <span>
                            <?php echo $porcentajeBaja; ?>%
                        </span>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="actividad-vacia">
            No hay vehículos dados de baja.
        </div>

    <?php endif; ?>

</div>

<!-- MARCAS MÁS SOLICITADAS -->

<div class="bloque-tercero">

    <div class="encabezado-grafico">
        <div>
            <h2>Marcas más solicitadas</h2>
            <p>Preferencias de los clientes en el período</p>
        </div>
    </div>

    <div class="ranking-marcas">

        <?php if ($resultadoMarcasSolicitadas && $resultadoMarcasSolicitadas->num_rows > 0): ?>

            <?php
            $posicion = 1;
            $totalSolicitudesPeriodo = (int)$totalSolicitudes['total'];
            ?>

            <?php while ($marca = $resultadoMarcasSolicitadas->fetch_assoc()): ?>

                <?php
                $porcentajeMarca = $totalSolicitudesPeriodo > 0
                    ? round(($marca['total'] / $totalSolicitudesPeriodo) * 100, 1)
                    : 0;
                ?>

                <div class="marca-ranking-item">

                    <div class="marca-posicion">
                        <?php echo str_pad($posicion, 2, '0', STR_PAD_LEFT); ?>
                    </div>

                    <div class="marca-datos">

                        <strong>
                            <?php echo htmlspecialchars($marca['marca']); ?>
                        </strong>

                        <span>
                            <?php echo $marca['total']; ?>
                            <?php echo $marca['total'] == 1 ? 'solicitud' : 'solicitudes'; ?>
                        </span>

                    </div>

                    <div class="marca-porcentaje">
                        <?php echo $porcentajeMarca; ?>%
                    </div>

                </div>

                <?php $posicion++; ?>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="actividad-vacia">
                No hay solicitudes en el período seleccionado.
            </div>

        <?php endif; ?>

    </div>

</div>

</div>

    <!-- ========================= -->
    <!-- EMBUDO DEL PROCESO -->
    <!-- ========================= -->

    <div class="bloque-proceso">

        <div class="encabezado-grafico">
            <div>
                <h2>Embudo del proceso</h2>
                <p>Seguimiento de solicitudes dentro del período seleccionado</p>
            </div>
        </div>

        <div class="proceso-grid">

            <div class="proceso-etapa">

                <span>Solicitudes recibidas</span>

                <strong>
                    <?php echo $totalSolicitudes['total']; ?>
                </strong>

            </div>

            <div class="proceso-flecha">
                →
            </div>

            <div class="proceso-etapa">

                <span>Solicitudes con peritaje</span>

                <strong>
                    <?php echo $solicitudesConPeritaje['total']; ?>
                </strong>

            </div>

            <div class="proceso-flecha">
                →
            </div>

            <div class="proceso-etapa">

                <span>Solicitudes con propuesta</span>

                <strong>
                    <?php echo $solicitudesConPropuesta['total']; ?>
                </strong>

            </div>

        </div>

    </div>


    <!-- ========================= -->
    <!-- ACTIVIDAD DEL SISTEMA -->
    <!-- ========================= -->

    <div class="bloque-grafico">

        <div class="encabezado-grafico">

            <div>
                <h2>Actividad del sistema</h2>
                <p>Solicitudes, peritajes y propuestas registradas por fecha</p>
            </div>

            <span class="badge-periodo">
                <?php echo date('d/m/Y', strtotime($desde)); ?>
                —
                <?php echo date('d/m/Y', strtotime($hasta)); ?>
            </span>

        </div>

        <div class="grafico-contenedor">
            <canvas id="graficoActividad"></canvas>
        </div>

    </div>

</div>
   

</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const ctx = document.getElementById('graficoActividad');

const fechas = <?php echo json_encode($fechas); ?>;
const solicitudes = <?php echo json_encode($datosSolicitudes); ?>;
const peritajes = <?php echo json_encode($datosPeritajes); ?>;
const propuestas = <?php echo json_encode($datosPropuestas); ?>;

new Chart(ctx, {
    type: 'line',

    data: {
        labels: fechas,

     datasets: [
    {
        label: 'Solicitudes',
        data: solicitudes,
        borderColor: '#4f46e5',
        backgroundColor: 'rgba(79, 70, 229, 0.10)',
        borderWidth: 3,
        tension: 0.4,
        fill: true,
        pointRadius: 4,
        pointBackgroundColor: '#4f46e5'
    },
    {
        label: 'Peritajes',
        data: peritajes,
        borderColor: '#14b8a6',
        backgroundColor: 'rgba(20, 184, 166, 0.08)',
        borderWidth: 3,
        tension: 0.4,
        fill: true,
        pointRadius: 4,
        pointBackgroundColor: '#14b8a6'
    },
    {
        label: 'Propuestas',
        data: propuestas,
        borderColor: '#7c3aed',
        backgroundColor: 'rgba(124, 58, 237, 0.08)',
        borderWidth: 3,
        tension: 0.4,
        fill: true,
        pointRadius: 4,
        pointBackgroundColor: '#7c3aed'
    }
]   
    },

    options: {
        responsive: true,
        maintainAspectRatio: false,

        plugins: {
            legend: {
                position: 'top',
                align: 'end'
            }
        },

        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                },
                grid: {
                    color: '#eef1f5'
                }
            },

            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

const estados = <?php echo json_encode($nombresEstados); ?>;
const cantidadesEstados = <?php echo json_encode($cantidadEstados); ?>;

const ctxEstados = document.getElementById('graficoEstados');

new Chart(ctxEstados, {
    type: 'doughnut',

    data: {
        labels: estados,

        datasets: [{
            data: cantidadesEstados,

            backgroundColor: [
                '#4f46e5',
                '#818cf8',
                '#14b8a6',
                '#cbd5e1'
            ],

            borderWidth: 0
        }]
    },

    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',

        plugins: {
            legend: {
                display: false
            }
        }
    }
});

const resumen = document.getElementById('resumenEstados');

estados.forEach((estado, index) => {

    const fila = document.createElement('div');
    fila.className = 'item-estado';

    fila.innerHTML = `
        <span class="estado-nombre">${estado}</span>
        <span class="estado-cantidad">${cantidadesEstados[index]}</span>
    `;

    resumen.appendChild(fila);
});
</script>



</body>
</html>