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

$sql = "
    SELECT
        c.id,
        c.nombre,
        c.dni,
        c.email,
        c.telefono,
        c.fecha_alta,
        MAX(s.fecha_creacion) AS ultima_consulta,
        COUNT(s.id) AS total_solicitudes
    FROM clientes c
    LEFT JOIN solicitudes s
        ON s.cliente_id = c.id
    GROUP BY
        c.id,
        c.nombre,
        c.dni,
        c.email,
        c.telefono,
        c.fecha_alta
    ORDER BY ultima_consulta DESC
";

$resultado = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Clientes - ALPERIT</title>

    <style>

        body {
            margin: 0;
            background: #f4f6f9;
            font-family: Arial, sans-serif;
            color: #111827;
        }

        .barra-superior {
            background: linear-gradient(90deg, #b7babe, #262a30);
            color: white;
            padding: 10px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
        }

        .barra-superior strong {
            font-size: 16px;
        }

        .barra-superior a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            margin-right: 20px;
        }

        .contenedor {
            padding: 35px 18px;
        }

        h1 {
            margin-bottom: 5px;
        }

        .subtitulo {
            color: #6b7280;
            margin-top: 0;
            margin-bottom: 25px;
        }

        .card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 8px 22px rgba(0,0,0,0.06);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        th {
            font-weight: 700;
        }

        .badge {
            display: inline-block;
            background: #eaf2ff;
            color: #1d4ed8;
            padding: 5px 9px;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 700;
        }

        .btn-ver {
            display: inline-block;
            padding: 6px 11px;
            border: 1px solid #2563eb;
            color: #2563eb;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        .btn-ver:hover {
            background: #2563eb;
            color: white;
        }

    </style>

</head>

<body>

<div class="barra-superior">

    <strong>Alperit System</strong>

    <div>
        <a href="index.php">← Volver al inicio</a>

        Usuario:
        <?php echo htmlspecialchars($_SESSION['nombre']); ?>
    </div>

</div>

<div class="contenedor">

    <h1>Clientes</h1>

    <p class="subtitulo">
        Registro e historial de clientes de ALPERIT
    </p>

    <div class="card">

        <table>

            <thead>

                <tr>
                    <th>Última consulta</th>
                    <th>Cliente</th>
                    <th>DNI</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Solicitudes</th>
                    <th>Acción</th>
                </tr>

            </thead>

            <tbody>

                <?php
                while ($cliente = $resultado->fetch_assoc()):
                ?>

                    <tr>
                        
                    <td>
    <?php
    echo $cliente['ultima_consulta']
        ? date("d/m/Y H:i", strtotime($cliente['ultima_consulta']))
        : '-';
    ?>
</td>
                        

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $cliente['nombre']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $cliente['dni']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $cliente['email']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $cliente['telefono'] ?? '-'
                            );
                            ?>
                        </td>

                        <td>

                            <span class="badge">

                                <?php
                                echo $cliente[
                                    'total_solicitudes'
                                ];
                                ?>

                                solicitudes

                            </span>

                        </td>

                        <td>

                            <a
                                href="detalle_cliente.php?id=<?php echo $cliente['id']; ?>"
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

$conn->close();

?>