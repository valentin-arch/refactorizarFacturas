<?php
// Conexión con la base de datos
$servername = "srv1074.hstgr.io";
$username = "u300699575_supertop";
$password = "SuperTop123";
$dbname = "u300699575_supertop";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Función para formatear fechas (YYYYMMDD -> dd/mm/yyyy)
function formatFecha($fecha) {
    if (strlen($fecha) == 8) {
        return substr($fecha, 6, 2) . "/" . substr($fecha, 4, 2) . "/" . substr($fecha, 0, 4);
    }
    return "formato de fecha invalida";
}

// Función para obtener registros por DNI
function getRecordsByDNI($conn, $dni, $noCobrados) {
    $sql = "SELECT `IdAutorizacion`, `Apellido`, `Nombre`, `FechaDesde`, `FechaHasta`, `FechaCompra`
            FROM PadronAutorizacion
            WHERE DNI = ?";

    if ($noCobrados) {
        $sql .= " AND (FechaCompra IS NULL OR FechaCompra = '')";
    }

    $sql .= " ORDER BY FechaDesde DESC";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $dni);
        $stmt->execute();
        $stmt->bind_result($IdAutorizacion, $Apellido, $Nombre, $FechaHasta, $FechaDesde, $FechaCompras);

        $records = [];
        while ($stmt->fetch()) {
            $records[] = [
                'IdAutorizacion' => $IdAutorizacion,
                'Apellido' => $Apellido,
                'Nombre' => $Nombre,
                'FechaDesde' => $FechaDesde,
                'FechaHasta' => $FechaHasta,
                'FechaCompras' => !empty($FechaCompras) ? $FechaCompras : "No Cobrado"
            ];
        }
        $stmt->close();
        return $records;
    }
    return [];
}

// Función para obtener registros por Mes/Año
function getRecordsByPeriod($conn, $periodoCompra, $noCobrados) {
    $partes = explode("/", $periodoCompra);
    if (count($partes) != 2) {
        return []; // Retorna vacío si el formato es incorrecto
    }

    list($mes, $año) = $partes;
    $mes = str_pad($mes, 2, "0", STR_PAD_LEFT); // Asegurar que el mes tenga dos dígitos
    $fechaInicio = "01/$mes/$año"; // Primer día del mes
    $fechaFin = "31/$mes/$año"; // Día 25 del mes

    // Consulta SQL para filtrar entre el 1 y el 31 del mes/año ingresado
    $sql = "SELECT `IdAutorizacion`, `Apellido`, `Nombre`,`FechaDesde`,`FechaHasta`, `FechaCompra`
            FROM PadronAutorizacion
            WHERE STR_TO_DATE(FechaHasta, '%d/%m/%Y')
                  BETWEEN STR_TO_DATE(?, '%d/%m/%Y')
                  AND STR_TO_DATE(?, '%d/%m/%Y')";

    if ($noCobrados) {
        $sql .= " AND (FechaCompra IS NULL OR FechaCompra = '')";
    }

    $sql .= " ORDER BY STR_TO_DATE(FechaDesde, '%d/%m/%Y') DESC";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ss", $fechaInicio, $fechaFin);
        $stmt->execute();
        $stmt->bind_result($IdAutorizacion, $Apellido, $Nombre, $FechaDesde, $FechaHasta, $FechaCompras);

        $records = [];
        while ($stmt->fetch()) {
            $records[] = [
                'IdAutorizacion' => $IdAutorizacion,
                'Apellido' => $Apellido,
                'Nombre' => $Nombre,
                'FechaDesde' => $FechaDesde,
                'FechaHasta' => $FechaHasta,
                'FechaCompras' => !empty($FechaCompras) ? $FechaCompras : "No Cobrado"
            ];
        }
        $stmt->close();
        return $records;
    }
    return [];
}

// Variables iniciales
$dni = "";
$periodo = "";
$noCobrados = false;
$records = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dni = !empty($_POST["dni"]) ? trim($_POST["dni"]) : "";
    $periodo = !empty($_POST["periodo"]) ? trim($_POST["periodo"]) : "";
    $noCobrados = isset($_POST["noCobrados"]);

    if (!empty($dni)) {
        $records = getRecordsByDNI($conn, $dni, $noCobrados);
    } elseif (!empty($periodo)) {
        $records = getRecordsByPeriod($conn, $periodo, $noCobrados);
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Autorizaciones</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
        form { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); width: 350px; }
        label { font-weight: bold; display: block; margin-top: 10px; }
        input, select { width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #007BFF; color: white; padding: 10px; border: none; border-radius: 4px; cursor: pointer; width: 100%; margin-top: 10px; }
        button:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #007BFF; color: white; }
    </style>
</head>
<body>
    <h2>Consultar Autorizaciones</h2>
    <form method="POST">
        <label for="dni">Buscar por DNI:</label>
        <input type="text" id="dni" name="dni" placeholder="Ejemplo: 30123456">
        <label for="periodo">Buscar por período habilitado (mm/yyyy):</label>
        <input type="text" id="periodo" name="periodo" placeholder="Ejemplo: 03/2025">
        <label><input type="checkbox" name="noCobrados"> Solo mostrar registros NO cobrados</label>
        <button type="submit">Buscar</button>
    </form>
    <?php if (!empty($records)): ?>
        <h2>Resultados</h2>
        <table>
            <tr>
                <th>ID Autorización</th>
                <th>Apellido</th>
                <th>Nombre</th>
                <th>Fecha Desde</th>
                <th>Fecha Hasta</th>
                <th>Fecha de Compras</th>
            </tr>
            <?php foreach ($records as $record): ?>
                <tr>
                    <td><?= $record['IdAutorizacion'] ?></td>
                    <td><?= $record['Apellido'] ?></td>
                    <td><?= $record['Nombre'] ?></td>
                    <td><?= $record['FechaDesde'] ?></td>
                    <td><?= $record['FechaHasta'] ?></td>
                    <td><?= $record['FechaCompras'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php elseif ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
        <p>No se encontraron registros.</p>
    <?php endif; ?>
</body>
</html>
