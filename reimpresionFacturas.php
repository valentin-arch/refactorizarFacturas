<?php
// Función para conectar a la base de datos
function conectarDB($host, $username, $password, $dbname) {
    $conn = new mysqli($host, $username, $password, $dbname); //ahora usa $dbname
    $conn->set_charset("utf8"); // Asegurar compatibilidad de caracteres

    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }

    return $conn;
}

// Obtener datos de la factura
function obtenerFactura($facturacion, $tipoFact, $pv, $nroFact, $fecha, $suc) {
    $sql = "SELECT pv, nro_comprobante, fecha, sucursal, tipo_factura, 
                nro_documento, cae, vto_cae, neto, exento, iva_105, iva_21, total_iva, total 
                FROM facturas_afip 
                WHERE pv = ? AND nro_comprobante = ? AND fecha = ? AND sucursal = ? AND tipo_factura = ?";
    
    echo "Consultando con los siguientes valores: ";
    echo "pv = $pv, nroFact = $nroFact, fecha = $fecha, suc = $suc, tipoFact = $tipoFact";

    $stmt = $facturacion->prepare($sql);

    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . $facturacion->error);
    }

    // Tipos de datos correctos
    $stmt->bind_param("iisii", $pv, $nroFact, $fecha, $suc, $tipoFact);
    $stmt->execute();
    
    $result = $stmt->get_result();
    if ($result !== false && $result->num_rows == 1) {
        return $result->fetch_assoc();
    }
    return null;
}

// Obtener ID de la compra desde `purchases`
function obtenerIdCompra($clubTop, $pv, $nroFact) {
    $nro_comprobante = sprintf("%05d%08d", $pv, $nroFact);
    $sql = "SELECT id FROM purchases WHERE nro_ticket = ?";
    $stmt = $clubTop->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . $clubTop->error);
    }

    $stmt->bind_param("i", $nro_comprobante);
    $stmt->execute();
    $stmt->bind_result($id);
    
    return $stmt->fetch() ? $id : null;
}

// Obtener artículos asociados a la compra
function obtenerArticulos($clubTop, $purchaseId) {
    $sql = "SELECT article_cod, amount, price FROM tickets WHERE purchase_id = ?";
    $stmt = $clubTop->prepare($sql);

    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . $clubTop->error);
    }

    $stmt->bind_param("i", $purchaseId);
    $stmt->execute();
    $result = $stmt->get_result();

    $articulos = [];
    while ($row = $result->fetch_assoc()) {
        $articulos[] = [
            "codigo" => $row["article_cod"],
            "cantidad" => $row["amount"],
            "precio" => $row["price"],
        ];
    }

    return $articulos;
} 

// Conectar a las bases de datos
$host = '192.168.10.204';
$username = 'desarrollo';
$password = 'desarrollosoporte975';
$clubTop = conectarDB($host, $username, $password, 'clubtop');
$facturacion = conectarDB($host, $username, $password, 'facturacion');

// Tomar los datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pv = $_POST["pv"];
    $nroFact = $_POST["nroFact"];
    $suc = $_POST["suc"];
    $fecha = $_POST["fecha"];
    $tipoFact = $_POST["tipoFact"];

    // Convertir tipo de factura a numérico
    if ($tipoFact === "A") {
        $tipoFact = 1;
    } else {
        $tipoFact = 6;
    }

    // Obtener la factura
    $factura = obtenerFactura($facturacion, $tipoFact, $pv, $nroFact, $fecha, $suc);
    if (!$factura) {
        throw new Exception("No se encontró la factura.");
    }

    // Obtener el ID de la compra
    $resultForId = obtenerIdCompra($clubTop, $pv, $nroFact);
    if (!$resultForId) {
        throw new Exception("No se encontró un ID para el comprobante con PV: $pv y Número: $nroFact.");
    }

    // Obtener los artículos
    $articulos = obtenerArticulos($clubTop, $resultForId);

    // Cerrar conexiones
    $clubTop->close();
    $facturacion->close();

    // Mostrar resultados
    echo "<h3>Factura Encontrada:</h3>";
    echo "<pre>";
    print_r($factura);
    echo "</pre>";

    echo "<h3>Artículos Asociados:</h3>";
    echo "<pre>";
    print_r($articulos);
    echo "</pre>";
}
?>

<div class="form-container">
    <h3>Búsqueda de Factura</h3>
    <form action="refac2.php" method="POST">
        <label for="pv">Punto de Venta:</label>
        <input type="number" id="pv" name="pv" required><br>

        <label for="nroFact">Número de Factura:</label>
        <input type="number" id="nroFact" name="nroFact" required><br>

        <label for="suc">Sucursal:</label>
        <input type="number" id="suc" name="suc" required><br>

        <label for="fecha">Fecha (YYYY-MM-DD):</label>
        <input type="date" id="fecha" name="fecha" required><br>

        <label for="tipoFact">Tipo de Factura:</label>
        <select id="tipoFact" name="tipoFact" required>
            <option value="A">A</option>
            <option value="B">B</option>
        </select><br>

        <button type="submit">Buscar Factura</button>
    </form>
</div>

</body>
</html>