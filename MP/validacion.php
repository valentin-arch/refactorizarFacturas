<?php

header('Content-Type: application/json');
date_default_timezone_set('America/Argentina/Cordoba');

//Verifica si una fecha y hora están dentro de los últimos 90 días.

function estaDentroDe90Dias(string $fecha): array {
    $fechaInput = DateTime::createFromFormat('d-m-Y', $fecha);
    if (!$fechaInput) {
        return [
            'valida' => false,
            'dias_diferencia' => null
        ];
    }

    $ahora = new DateTime();
    $limiteInferior = (clone $ahora)->modify('-90 days');
    $enRango = $fechaInput >= $limiteInferior && $fechaInput <= $ahora;

    $diferencia = $fechaInput->diff($ahora)->days;

    return [
        'valida' => $enRango,
        'dias_diferencia' => $diferencia
    ];
}   

//Valida que el rango horario tenga formato correcto y que desde <= hasta.

function validarRangoHorario(string $horaDesde, string $horaHasta): bool {
    $desde = DateTime::createFromFormat('H:i', $horaDesde);
    $hasta = DateTime::createFromFormat('H:i', $horaHasta);

    if (!$desde || !$hasta) return false;

    return $desde <= $hasta;
}

//Procesa la entrada y devuelve un array con el resultado y un mensaje.

function procesarSolicitud(array $datos): array {
    $sucursal = $datos['sucursal'] ?? null;
    $numCaja = $datos['caja'] ?? null;
    $fecha = $datos['fecha'] ?? null;
    $horaDesde = $datos['horaDesde'] ?? null;
    $horaHasta = $datos['horaHasta'] ?? null;

    if (!$sucursal || !$numCaja || !$fecha || !$horaDesde || !$horaHasta) {
        return [ 'ok' => false, 'mensaje' => 'Faltan datos requeridos.' ];
    }

    if (!is_numeric($sucursal) || strlen((string)$sucursal) !== 2) {
        return [ 'ok' => false, 'mensaje' => 'La sucursal debe tener exactamente 2 dígitos.' ];
    } 

    if (!validarRangoHorario($horaDesde, $horaHasta)) {
        return [ 'ok' => false, 'mensaje' => 'Rango horario inválido (formato incorrecto o desde > hasta).' ];
    }

    $fechaHoraDesde = "$fecha $horaDesde";
    $fechaHoraHasta = "$fecha $horaHasta";

    if (!estaDentroDe90Dias($fechaHoraDesde) || !estaDentroDe90Dias($fechaHoraHasta)) {
        return [ 'ok' => false, 'mensaje' => 'El rango de fecha y hora no está dentro de los últimos 90 días.' ];
    }

    return [ 'ok' => true, 'mensaje' => 'Rango de fecha y hora válido.' ];
}

echo json_encode(procesarSolicitud($_POST));

?>