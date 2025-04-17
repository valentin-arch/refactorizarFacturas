<?php
$resultado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Datos del formulario
    $datosIngresados = [
        'sucursal'   => $_POST['sucursal'] ?? null,
        'caja'       => $_POST['caja'] ?? null,
        'fecha'      => $_POST['fecha'] ?? null,
        'horaDesde'  => $_POST['horaDesde'] ?? null,
        'horaHasta'  => $_POST['horaHasta'] ?? null
    ];

    // Procesamiento (ejemplo básico)
    function estaDentroDe90Dias(string $fecha): array {
        $fechaInput = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$fechaInput) return ['valida' => false, 'dias_diferencia' => null];

        $ahora = new DateTime();
        $limite = (clone $ahora)->modify('-90 days');
        $enRango = $fechaInput >= $limite && $fechaInput <= $ahora;
        $diferencia = $fechaInput->diff($ahora)->days;

        return ['valida' => $enRango, 'dias_diferencia' => $diferencia];
    }

    $checkFecha = estaDentroDe90Dias($datosIngresados['fecha']);

    $resultado = [
        'ok' => $checkFecha['valida'],
        'mensaje' => $checkFecha['valida']
            ? 'Fecha válida, dentro de los últimos 90 días.'
            : 'Fecha fuera del rango de 90 días.',
        'dias_diferencia' => $checkFecha['dias_diferencia']
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificar Fecha y Hora</title>
    <style>
        body { font-family: sans-serif; max-width: 500px; margin: 2em auto; }
        label { display: block; margin-top: 1em; }
        input { width: 100%; padding: 0.5em; }
        button { margin-top: 1em; padding: 0.6em 1em; }
        pre { background: #f0f0f0; padding: 1em; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h2>Verificar Fecha y Hora</h2>
    <form method="post">
        <label>Sucursal:
            <input type="number" name="sucursal" required>
        </label>
        <label>Número de caja:
            <input type="number" name="caja" required>
        </label>
        <label>Fecha:
            <input type="date" name="fecha" required>
        </label>
        <label>Hora Desde:
            <input type="time" name="horaDesde" required>
        </label>
        <label>Hora Hasta:
            <input type="time" name="horaHasta" required>
        </label>
        <button type="submit">Verificar</button>
    </form>

    <?php if ($resultado): ?>
        <h3>Datos ingresados:</h3>
        <pre><?= json_encode($datosIngresados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>

        <h3>Resultado del servidor:</h3>
        <pre><?= json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>
    <?php endif; ?>
</body>
</html>
