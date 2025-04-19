<?php

//header('Content-Type: application/json');
date_default_timezone_set('America/Argentina/Cordoba');

//Verifica si una fecha y hora están dentro de los últimos 90 días.

function estaDentroDe90Dias(string $fecha) {
    $fechaInput = DateTime::createFromFormat('d-m-Y', $fecha);
    if (!$fechaInput) {
        return [
            'valida' => false,
            'dias_diferencia' => null
        ];
    }

    $ahora = new DateTime();
    $limiteInferior = new DateTime();
    $limiteInferior->modify('-90 days'); 

    $enRango = $fechaInput >= $limiteInferior && $fechaInput <= $ahora;

    $diferencia = $fechaInput->diff($ahora)->days;

    return [
        'valida' => $enRango,
        'dias_diferencia' => $diferencia
    ];
}   

//Valida que el rango horario tenga formato correcto y que desde <= hasta.

function validarRangoHorario( $horaDesde, $horaHasta) {
    var_dump($horaDesde);
    $desde = DateTime::createFromFormat('H:i', $horaDesde);
    $hasta = DateTime::createFromFormat('H:i', $horaHasta);

    if ($desde !== false || $hasta !== false) return false;

    if ($desde < '7:00' || $hasta > '23:00'){
        return false;
    }

    return $desde <= $hasta;
}

//Procesa la entrada y devuelve un array con el resultado y un mensaje.

function procesarSolicitud(array $datos) {
    var_dump($datos['horaDesde']);

    if (!validarRangoHorario($datos['horaDesde'], $datos['horaHasta'])) {
        return [ 'ok' => false, 'mensaje' => 'Rango horario inválido (formato incorrecto o desde > hasta).' ];
    }


    if (!estaDentroDe90Dias($datos['fecha'])) {
        return [ 'ok' => false, 'mensaje' => 'El rango de fecha y hora no está dentro de los últimos 90 días.' ];
    }

    return [ 'ok' => true, 'mensaje' => 'Rango de fecha y hora válido.' ];
}


if(isset($_POST) and count($_POST)> 0){
    echo json_encode(procesarSolicitud($_POST));
}
//echo json_encode(procesarSolicitud($_POST));

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
