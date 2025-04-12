<?php

/**
 * Carga el diccionario de equivalencias desde un archivo CSV.
 *
 * @param string $archivo Ruta del archivo CSV.
 * @return array Diccionario en formato:
 * [
 *   [rubro][subrubro] => [
 *      "abrev" => [abreviaciones permitidas],
 *      "full"  => [nombres completos correspondientes]
 *   ]
 * ]
 */

function cargarDiccionarioDesdeCSV($archivo) {
    $diccionario = [];

    if (!file_exists($archivo)) {
        die("Error: El archivo CSV no existe.");
    }

    $handle = fopen($archivo, "r");
    if ($handle) {
        while (($line = fgetcsv($handle, 0)) !== false) {

            $rubro = trim($line[0]);
            $subrubro = trim($line[1]);
            $abreviacion = trim($line[2]);
            $nombreCompleto = trim($line[3]);

            if ($abreviacion === "") {
                continue; // Si no hay abreviación, ignorar toda la categoría
            }

            if (!isset($diccionario[$rubro][$subrubro])) {
                $diccionario[$rubro][$subrubro] = [
                    "abrev" => [],
                    "full"  => []
                ];
            }

            $diccionario[$rubro][$subrubro]["abrev"][] = $abreviacion;
            $diccionario[$rubro][$subrubro]["full"][] = $nombreCompleto;
        }
        fclose($handle);
    } else {
        die("Error: No se pudo abrir el archivo CSV.");
    }

    return $diccionario;
}

/**
 * Reemplaza abreviaciones en la descripción de productos.
 * Solo reemplaza las abreviaciones si están dentro de las dos primeras palabras.
 *
 * @param string $descripcion Descripción original del producto.
 * @param array $abreviaciones Lista de abreviaciones permitidas.
 * @param array $nombresCompletos Lista de nombres completos.
 * @return string Descripción modificada.
 */

//function reemplazarAbreviaciones($descripcion, $abreviaciones, $nombresCompletos) {
    // Separar palabras manteniendo puntos y slashes en los tokens
//    preg_match_all('/[^ .\/]+[.\/]?/', $descripcion, $matches);
//    $tokens = $matches[0];

//    $palabraIndex = 0;
//    for ($i = 0; $i < count($tokens); $i++) {
//        if ($palabraIndex < 3) { // Solo modificar las dos primeras palabras reales
//            foreach ($abreviaciones as $index => $abrev) {
//                similar_text($tokens[$i], $abrev, $porcentaje);
//                echo $tokens[$i] . " - " . $abrev . " - " . $porcentaje . "\n";
//                if ($porcentaje >= 75) { //originalmente estaba en 75%
//                    $tokens[$i] = $nombresCompletos[$index];
//                    break;
//                }
//            }
//            $palabraIndex++;
//        }
//    }

//    return implode(" ", $tokens); // Reconstruir la cadena con espacios entre palabras
//}

function reemplazarAbreviaciones($descripcion, $abreviaciones, $nombresCompletos) {
    preg_match_all('/[^ .\/]+[.\/]?/', $descripcion, $matches);
    $tokens = $matches[0];

    $palabraIndex = 0;
    for ($i = 0; $i < count($tokens); $i++) {
        if ($palabraIndex < 3) {
            foreach ($abreviaciones as $index => $abrev) {
                $longToken = strlen($tokens[$i]);

                // Umbral dinámico: si la palabra es muy corta, bajar el porcentaje
                $umbral = ($longToken <= 3) ? 60 : 75;

                similar_text(strtoupper($tokens[$i]), strtoupper($abrev), $porcentaje);
                if ($porcentaje >= $umbral) {
                    $tokens[$i] = $nombresCompletos[$index];
                    break;
                }
            }
            $palabraIndex++;
        }
    }

    return implode(" ", $tokens);
}

function tokenizarDescripcion($descripcion) {
    return preg_split('/(?=[\.\/ ])/', $descripcion, -1, PREG_SPLIT_NO_EMPTY);
}

function similitud($a, $b) {
    similar_text(strtolower($a), strtolower($b), $percent);
    return $percent;
}

function reemplazarMarca($descripcion, $marcas, $codigo) {
    if (!isset($marcas[$codigo])) {
        return $descripcion;
    }

    $marca_correcta = $marcas[$codigo];

    $tokens = tokenizarDescripcion($descripcion);
    $mejor_match = "";
    $mejor_similitud = 0;

    if (strpos($descripcion, $marca_correcta) !== false) {
        return $descripcion;
    } else {
        for ($i = 0; $i < count($tokens); $i++) {
            for ($j = $i; $j < count($tokens); $j++) {
                $subcadena = implode("", array_slice($tokens, $i, $j - $i + 1));
                $similitud = similitud($subcadena, $marca_correcta);
                if ($similitud > $mejor_similitud) {
                    $mejor_similitud = $similitud;
                    $mejor_match = $subcadena;
                }
            }
        }

        $descripcion = str_replace($mejor_match, $marca_correcta, $descripcion);
        return $descripcion;
    }
}

// Ruta del CSV
$archivoCSV = "diccionarioDescr.csv";

// Cargar el diccionario
$diccionario = cargarDiccionarioDesdeCSV($archivoCSV);

$baseTop = new mysqli('192.168.10.204', 'desarrollo', 'desarrollosoporte975', 'ventas');
$sql = "SELECT codigo, descripcion, rubro, sub_rubro FROM articulos WHERE habilitado = '1' and rubro = '1' and sub_rubro = '43'" ;
$result = $baseTop->query($sql);
$productos = array();
while($resultado = $result->fetch_assoc()){
    $productos[$resultado['codigo']] = array('rubro' => $resultado['rubro'], 'subrubro' => $resultado['sub_rubro'], 'descripcion' => $resultado['descripcion']);
}

foreach ($productos as &$producto) {
    $rubro = $producto["rubro"];
    $subrubro = $producto["subrubro"];

    // Verificar si hay abreviaciones para esta categoría
    if (isset($diccionario[$rubro][$subrubro])) {
        $abreviaciones = $diccionario[$rubro][$subrubro]["abrev"];
        $nombresCompletos = $diccionario[$rubro][$subrubro]["full"];
        $producto["descripcion"] = reemplazarAbreviaciones($producto["descripcion"], $abreviaciones, $nombresCompletos);
    }
}

// Mostrar los productos actualizados
print_r($productos);

//guardar en archivo .txt
$archivoDeSalida = "descripciones extendidas.txt";
$handleSalida = fopen($archivoDeSalida, "w");

if ($handleSalida) {

    foreach ($productos as $codigo => $datos) {
        $linea = "Codigo: $codigo | Rubro: {$datos['rubro']} | Subrubro: {$datos['subrubro']} | Descripcion: {$datos['descripcion']}\n";
        fwrite($handleSalida, $linea);
    }
    fclose($handleSalida);
    echo "los productos se guardaron en el '$archivoDeSalida'\n";

} else {
    
    echo "no se pudo acceder al archivo de texto \n";
}

?>