<?php
require_once 'config.php';
require_once 'funciones.php';
ob_start();
session_start();

include "src/utils/phpqrcode/qrlib.php";
$_SESSION['totalArts'] = 0;
$_SESSION['totalfactura'] = 0;
date_default_timezone_set('America/Argentina/Cordoba');

//Funciones

$username = 'desarrollo';
$password = 'desarrollosoporte975';
$clubTop = conectarDB('192.168.10.204', $username, $password, 'clubtop');
$facturacion = conectarDB('192.168.10.204', $username, $password, 'facturacion');

// Obtener datos de la factura
function obtenerFactura($facturacion, $tipoFact, $pv, $nroFact, $fecha, $suc) {
    
    // Convertir fecha de yyyymmdd a yyyy-mm-dd
    $fechaObj = DateTime::createFromFormat('Ymd', $fecha);
    if (!$fechaObj) {
        throw new Exception("Formato de fecha inválido: $fecha");
    }

    $fechaFormat = $fechaObj->format('Y-m-d');


    $sql = "SELECT pv, nro_comprobante, fecha, sucursal, tipo_factura, 
                nro_documento, cae, vto_cae, neto, exento, iva_105, iva_21, total_iva, total 
                FROM facturas_afip 
                WHERE pv = ? AND nro_comprobante = ? AND fecha = ? AND sucursal = ? AND tipo_factura = ?";
    
    echo "Consultando con los siguientes valores: ";
    echo "pv = $pv, nroFact = $nroFact, fecha = $fechaFormat, suc = $suc, tipoFact = $tipoFact";

    $stmt = $facturacion->prepare($sql);

    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . $facturacion->error);
    }

    // Tipos de datos correctos
    $stmt->bind_param("iisii", $pv, $nroFact, $fechaFormat, $suc, $tipoFact);
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

if (file_exists("archivos/temporales/art.txt")) {

	$suc = 1;
	$fecha = "20230801";
	$tipo = 6;
	$ptoVta = 413;
	$nroFact = 120233;

        $factura = obtenerFactura($facturacion, $tipo, $ptoVta, $nroFact, $fecha, $suc);
        $cae = $factura['cae'];
        $vtoCae = $factura['vto_cae'];
	
		$archivo_caja = fopen("archivos/temporales/fact.txt", "r");
		$datos = fgets($archivo_caja);
		fclose($archivo_caja);

		$login  = new mysqli("super-imperio.com.ar", "", "", "login");

		$path = "src/utils/";
		$matrixPointSize = 2;
		$errorCorrectionLevel = 'L';
		$fontBlack = $path . "Roboto/Roboto-Black.ttf";
		$fontRegular = $path . "Roboto/Roboto-Regular.ttf";
		$im = imagecreatetruecolor(1, 1);
		$black = imagecolorallocate($im, 0, 0, 0);

		$tipoFac = 1;
		if (substr($datos, 15,1) == 'B') {
			$tipoFac = 6;
		}
		echo "aca1";
		//$tipoFac = 6;

		if ($tipoFac == 1) {
			$plantilla = facturaA($black, $fontRegular, $fontBlack, $datos);
		} else {
			$plantilla = facturaB($black, $fontRegular, $fontBlack, $datos);
		}
		
		//imagettftext($image,  $size, $angle,  $x,  $y,  $color, $fontfile,  $text)
		imagettftext($plantilla, 30, 0, 712, 150, $black, $fontBlack, "FACTURA");
		imagettftext($plantilla, 14, 0, 240, 307, $black, $fontRegular, "Av. Sabattini N° 4450 - Rio Cuarto - Cordoba");
		imagettftext($plantilla, 13, 0, 590, 170, $black, $fontRegular, "Cod. 0" . $tipoFac);
		imagettftext($plantilla, 17, 0, 868, 196, $black, $fontBlack, substr($datos, 2,4));
		imagettftext($plantilla, 17, 0, 1075, 196, $black, $fontBlack, substr($datos, 6,8));
		imagettftext($plantilla, 17, 0, 890, 230, $black, $fontBlack, (substr($datos, 144,2) . "/" . substr($datos, 146,2) . "/" . substr($datos, 148,4)));
		imagettftext($plantilla, 14, 0, 95, 413, $black, $fontRegular, substr($datos, 70,11));
		imagettftext($plantilla, 14, 0, 745, 413, $black, $fontRegular, substr($datos, 22,24));
		imagettftext($plantilla, 14, 0, 745, 448, $black, $fontRegular, substr($datos, 46,24));

		if (substr($datos, 83,1) == 'I') {
			imagettftext($plantilla, 14, 0, 245, 448, $black, $fontRegular, "IVA Responsable Incripto");
		}
		elseif (substr($datos, 83,1) == 'E') {
			imagettftext($plantilla, 14, 0, 245, 448, $black, $fontRegular, "IVA Exento");
		}
		elseif (substr($datos, 83,1) == 'M') {
			imagettftext($plantilla, 14, 0, 245, 448, $black, $fontRegular, "IVA Responsable Monotributo");
		}
		else {
			imagettftext($plantilla, 14, 0, 245, 448, $black, $fontRegular, "CONSUMIDOR FINAL");
		}

		imagettftext($plantilla, 14, 0, 210, 483, $black, $fontRegular, "Cuenta Corriente");

		if (substr($datos, 164, 1) == "0") {
			imagettftext($plantilla, 15, 0, 910, 1671, $black, $fontBlack,("CAE N°: ". $cae));
			imagettftext($plantilla, 15, 0, 780, 1700, $black, $fontBlack,("Fecha de Vto. de CAE: ". $vtoCae));
		} else {
			imagettftext($plantilla, 15, 0, 900, 1671, $black, $fontBlack,("CAEA N°: ". $cae));
			imagettftext($plantilla, 15, 0, 770, 1700, $black, $fontBlack,("Fecha de Vto. de CAEA: ". $vtoCae));
		}


		$mensa_qr = "{'ver':1,'fecha':'" . substr($datos, 148,4) . "-" . substr($datos, 146,2) . "-" . substr($datos, 144,2) . "','cuit':30583747792,'ptoVta':" . substr($datos, 2,4) . ",'tipoCmp':" . $tipoFac . ",'importe':" . substr($datos, 85,10) . ",'moneda':'PES','ctz':1,'tipoDocRec':80,'nroDocRec':" . substr($datos, 70,11) . ",'tipoCodAut':'A','codAut':". substr($datos, 165, 14);
		$texto_qr = "https://www.afip.gob.ar/fe/qr/?p=". base64_encode($mensa_qr); 

		QRcode::png($texto_qr, $path . "qr.png", $errorCorrectionLevel, $matrixPointSize, 2);    
		$qr = imagecreatefrompng($path . "qr.png");
		imagecopymerge($plantilla, $qr, 50, 1623, 0, 0, imagesx($qr), imagesy($qr), 100);

		$suc = substr($datos, 0,2);
		$fecha = substr($datos, 148,4) . "-" . substr($datos, 146,2) . "-" . substr($datos, 144,2);

		$resultado = imagejpeg($plantilla, "factura.jpg");

		if ($tipoFac == 1) {

			$return = descrA($black, $fontRegular, $fontBlack, $suc, $fecha);
		} else {

			$return = descrB($black, $fontRegular, $fontBlack, $suc, $fecha);
		}

		$resultado = imagejpeg($return[0], "factura0$return[1].jpg");

		$merge = "pdfunite ";
		$deleteFiles = "rm factura.jpg ";

		for ($i=1; $i <= $return[1]; $i++) {

			shell_exec("convert factura0$i.jpg factura0$i.pdf");
		//	sleep(10);
			$merge .= " factura0$i.pdf";
			$deleteFiles .= " factura0$i.jpg factura0$i.pdf " . getcwd() ."/archivos/temporales/fact.txt " . getcwd() ."/archivos/temporales/art.txt ";

		}

		$random = rand(0, 99999999);
		$nombre = $random . "ReImpFactura.pdf";
		$merge .= " " . getcwd() . "/archivos/mensajes/facturacion/". $nombre;
		if ($return[1] == 1) {
			$merge = "cp factura01.pdf " . getcwd() . "/archivos/mensajes/facturacion/". $nombre;
		}
		
		shell_exec($merge);
		shell_exec($deleteFiles);

		$id_archivo = NULL;

		$dire = "archivos/mensajes/facturacion/" . $nombre;
		$sql = "INSERT INTO archivos ( id_archivo, nombre_archivo) VALUES ($random, '$dire');";
		$login->query($sql);
		$id_archivo = $random;

		imagedestroy($qr);
		imagedestroy($plantilla);
		
		if (round(floatval($_SESSION['totalArts']),2) == round(floatval($_SESSION['totalfactura']),2) ) {
			//tengo que enviar mensaje interno con el id del arhivo
		
				if (!is_null($id_archivo)) {
					$textoMensaje = "PDF de la reimpresion adjunta al mensaje" . PHP_EOL .
									"Mensaje generado de forma automatica, no lo responda";
					$textoAsunto = "Reimpresion generada correctamente";
				} else {
					$textoMensaje = "No se pudo generar el pdf" . PHP_EOL .
							 "Mensaje generado de forma automatica, no lo responda";
					$textoAsunto = "Reimpresion con probelmas";
				}
				$id_origen  = "132";
				$id_destino = 2;
				$sql = "INSERT INTO mensajesinternos (id_origen, id_destino, id_cc, asunto, mensaje, id_archivo, creacion)
				  VALUES ($id_origen, $id_destino, '', '$textoAsunto', '$textoMensaje', '$id_archivo', CURRENT_TIMESTAMP )";
				$login->query($sql);
				
				$id_destino = 3;
				$sql = "INSERT INTO mensajesinternos (id_origen, id_destino, id_cc, asunto, mensaje, id_archivo, creacion)
				  VALUES ($id_origen, $id_destino, '', '$textoAsunto', '$textoMensaje', '$id_archivo', CURRENT_TIMESTAMP )";
				$login->query($sql);
				$id_destino = 4;
				$sql = "INSERT INTO mensajesinternos (id_origen, id_destino, id_cc, asunto, mensaje, id_archivo, creacion)
				  VALUES ($id_origen, $id_destino, '', '$textoAsunto', '$textoMensaje', '$id_archivo', CURRENT_TIMESTAMP )";
				$login->query($sql);
				$id_destino = 171;
				$sql = "INSERT INTO mensajesinternos (id_origen, id_destino, id_cc, asunto, mensaje, id_archivo, creacion)
				  VALUES ($id_origen, $id_destino, '', '$textoAsunto', '$textoMensaje', '$id_archivo', CURRENT_TIMESTAMP )";
				$login->query($sql);
			
				aviso("Comprobante reimpreso Correctamente");
			}else{

				aviso("Hay diferencia en la suma de Arts con total de Factura");
				aviso("Total Arts", round(floatval($_SESSION['totalArts']),2));
				aviso("Total Fact", round(floatval($_SESSION['totalfactura']),2));
			}
		echo '</script>
				<script type="text/javascript">
				window.location.href ="/reimprime_factura.php";
				</script>';
    } else {
		require_once 'templates/header.php';
		require_once 'config.php';
		require_once 'funciones.php';
		ob_start();
		session_start();
	}

?>

<link href="css/botones.css" rel="stylesheet">
<div class="content">
    <div class="container">
    <div class="col-md-8 col-sm-8 col-xs-12">
    <div class="well text-center">
    <center><h2><font style='font-variant:small-caps;' >Reimpresión de Comprobantes</font></h2></center><hr>
</div>

<?php
if (!isset($_SESSION['logged_in'])) {
	  header('Location: index.php');
	}

		
	echo '<center><div class="okay_bbc"><b>Debe subir dos archivos:</b> <hr>
      <b>fact.txt</b> que debe contener solo la linea correspondiente al comprobante.<br>
      <b>art.txt</b> que debe contener articulos relacionados al comprobante. 
        </div>
        <form enctype="multipart/form-data" action="upload.php" method="POST">
    	<input id="archivo[]" name="archivo[]" multiple="" type="file" />
   			<input type="submit" value="Subir Archivos" />
				</form></center>
					</div>
					</div>
					<div class="cajachica"><br><br> <iframe width="400" height="400" scrolling="no" frameborder="0"  src="/calendario.php" ></iframe> </div>
					</div>
					</div>';
		require_once 'templates/footer.php';

function facturaA($black, $fontRegular, $fontBlack, $datos, $factura) {
	$path = "src/utils/";
	$plantilla = imagecreatefromjpeg($path . "plantillaARe.jpg");
	imagettftext($plantilla, 40, 0, 602, 150, $black, $fontBlack, "A");
	$neto = $factura['neto'];
	imagettftext($plantilla, 14, 0, 1058, 1385, $black, $fontBlack, number_format($neto, 2, ",", ""));
	imagettftext($plantilla, 14, 0, 1058, 1412, $black, $fontBlack, "0,00");
	imagettftext($plantilla, 14, 0, 1058, 1439, $black, $fontBlack, number_format($factura['iva_21'], 2, ",", ""));
	imagettftext($plantilla, 14, 0, 1058, 1466, $black, $fontBlack, number_format($factura['iva_105'], 2, ",", ""));
	imagettftext($plantilla, 14, 0, 1058, 1493, $black, $fontBlack, "0,00");
	imagettftext($plantilla, 14, 0, 1058, 1520, $black, $fontBlack, "0,00");
	imagettftext($plantilla, 14, 0, 1058, 1547, $black, $fontBlack, "0,00");
	imagettftext($plantilla, 13, 0, 680, 1427, $black, $fontRegular, "0,00");
	imagettftext($plantilla, 13, 0, 680, 1449, $black, $fontRegular, "0,00");
	imagettftext($plantilla, 13, 0, 680, 1471, $black, $fontRegular, "0,00");
	imagettftext($plantilla, 13, 0, 680, 1493, $black, $fontRegular, "0,00");
	imagettftext($plantilla, 13, 0, 680, 1515, $black, $fontRegular, "0,00");
	imagettftext($plantilla, 15, 0, 650, 1547, $black, $fontRegular, "0,00");
	imagettftext($plantilla, 14, 0, 1058, 1575, $black, $fontBlack, "0,00");
	imagettftext($plantilla, 15, 0, 1058, 1605, $black, $fontBlack, number_format($factura['total'], 2, ",", ""));

	$_SESSION['totalfactura'] = substr($datos, 84, 10);
	return $plantilla;

}

function facturaB($black, $fontRegular, $fontBlack, $datos, $factura) {
	$path = "src/utils/";
	$plantilla = imagecreatefromjpeg($path . "plantillaBRe.jpg");
	imagettftext($plantilla, 40, 0, 602, 150, $black, $fontBlack, "B");
	$neto = $factura['neto'];
	imagettftext($plantilla, 14, 0, 1050, 1510, $black, $fontBlack, number_format($factura['neto'], 2, ",", ""));
	//imagettftext($plantilla, 14, 0, 1050, 1510, $black, $fontBlack, number_format($neto, 2, ",", ""));
	imagettftext($plantilla, 14, 0, 1050, 1550, $black, $fontBlack, "0,00");
	imagettftext($plantilla, 15, 0, 1050, 1590, $black, $fontBlack, number_format($factura['total'], 2, ",", ""));
	
	$_SESSION['totalfactura']=substr($datos, 84, 10);

	return $plantilla;
}

function descrA($black, $fontRegular, $fontBlack, $suc, $fecha, $pv, $nroFact, $tipoFact, $facturacion, $clubTop) {
    // Obtener los datos de la factura desde la base de datos
    $factura = obtenerFactura($facturacion, $tipoFact, $pv, $nroFact, $fecha, $suc);
    if (!$factura) {
        die("<p style='color: red;'> No se encontró la factura.</p>");
    }

    // Obtener el ID de la compra
    $purchaseId = obtenerIdCompra($clubTop, $pv, $nroFact);
    if (!$purchaseId) {
        die("<p style='color: red;'> No se encontró la compra asociada.</p>");
    }

    // Obtener los artículos asociados
    $articulos = obtenerArticulos($clubTop, $purchaseId);
    $ticketArray = [];

    foreach ($articulos as $articulo) {
        $codigo = $articulo['codigo'];
        $cantidad = floatval($articulo['cantidad']);
        $precio = floatval($articulo['precio']);
        
        $espromo = strpos($codigo, "P") !== false;
        
        if ($espromo) {
            $descripcion = "DESCUENTO x PROMOCION";
            $porkg = 0;
            $iva = 0.21;
            $precioUnit = $precio / max($cantidad, 1);
        } else {
            $sql = ($codigo < 10000) ?
                "SELECT descripcion, codigo FROM articulos WHERE codigo_corto = ? ORDER BY codigo ASC" :
                "SELECT descripcion FROM articulos WHERE codigo = ? ORDER BY codigo ASC";

            $stmt = $facturacion->prepare($sql);
            $stmt->bind_param("i", $codigo);
            $stmt->execute();
            $resultado = $stmt->get_result()->fetch_assoc();
            $descripcion = $resultado['descripcion'] ?? "SIN DESCRIPCIÓN";
            $codigo = $resultado['codigo'] ?? $codigo;

            $sqlHist = "SELECT porkg, iva, precio FROM historicos WHERE sucursal = ? AND codigo = ? AND fecha = ? ORDER BY id DESC";
            $stmt = $facturacion->prepare($sqlHist);
            $stmt->bind_param("iis", $suc, $codigo, $fecha);
            $stmt->execute();
            $resultadoHist = $stmt->get_result()->fetch_assoc();

            $porkg = $resultadoHist['porkg'] ?? 0;
            $precioUnit = $resultadoHist['precio'] ?? $precio;
            $iva = ($resultadoHist['iva'] ?? 21) / 100;

            if ($porkg) {
                $cantidad = $precio / $precioUnit;
            }
        }

        if (!isset($ticketArray[$codigo])) {
            $ticketArray[$codigo] = [$cantidad, $precio, $precioUnit, $porkg, $iva, $descripcion];
        } else {
            $ticketArray[$codigo][0] += $cantidad;
            $ticketArray[$codigo][1] += $precio;
        }
    }

    // php Paginación e impresión
    $pages = ceil(count($ticketArray) / 38);
    $renglon = 587;
    $page = 1;
    $plantilla = imagecreatefromjpeg("factura.jpg");
    imagettftext($plantilla, 13, 0, 600, 1700, $black, $fontBlack, "Pag. $page/$pages");

    foreach ($ticketArray as $codigo => $datos) {
        if ($renglon > (585 + 31 * 24)) {
            imagejpeg($plantilla, "factura0$page.jpg");
            $plantilla = imagecreatefromjpeg("factura.jpg");
            $renglon = 587;
            $page++;
            imagettftext($plantilla, 13, 0, 600, 1700, $black, $fontBlack, "Pag. $page/$pages");
        }

        imagettftext($plantilla, 11, 0, 45, $renglon, $black, $fontRegular, $codigo);
        imagettftext($plantilla, 11, 0, 122, $renglon, $black, $fontRegular, $datos[5]);
        imagettftext($plantilla, 12, 0, 530, $renglon, $black, $fontRegular, number_format($datos[0], 2, ".", ""));
        imagettftext($plantilla, 12, 0, 600, $renglon, $black, $fontRegular, $datos[3] ? "Kgr." : "unidades");
        imagettftext($plantilla, 12, 0, 720, $renglon, $black, $fontRegular, number_format($datos[2], 2, ",", ""));
        imagettftext($plantilla, 12, 0, 820, $renglon, $black, $fontRegular, "0,00");
        
        $subtotal = $datos[1] / (1 + $datos[4]);
        imagettftext($plantilla, 12, 0, 920, $renglon, $black, $fontRegular, number_format($subtotal, 2, ",", ""));
        imagettftext($plantilla, 12, 0, 1020, $renglon, $black, $fontRegular, number_format($datos[4] * 100, 2, ",", ""));
        imagettftext($plantilla, 12, 0, 1100, $renglon, $black, $fontRegular, number_format($datos[1], 2, ",", ""));

        $_SESSION['totalArts'] += $datos[1];
        $renglon += 20;
    }

    return [$plantilla, $page];
}

function descrB($black, $fontRegular, $fontBlack, $suc, $fecha, $pv, $nroFact, $tipoFact, $facturacion, $clubTop) {
    // Obtener los datos de la factura desde la base de datos
    $factura = obtenerFactura($facturacion, $tipoFact, $pv, $nroFact, $fecha, $suc);
    if (!$factura) {
        die("<p style='color: red;'> No se encontró la factura.</p>");
    }

    // Obtener el ID de la compra
    $purchaseId = obtenerIdCompra($clubTop, $pv, $nroFact);
    if (!$purchaseId) {
        die("<p style='color: red;'> No se encontró la compra asociada.</p>");
    }

    // Obtener los artículos asociados
    $articulos = obtenerArticulos($clubTop, $purchaseId);
    $ticketArray = [];

    foreach ($articulos as $articulo) {
        $codigo = $articulo['codigo'];
        $cantidad = floatval($articulo['cantidad']);
        $precio = floatval($articulo['precio']);
        
        $espromo = strpos($codigo, "P") !== false;
        
        if ($espromo) {
            $descripcion = "DESCUENTO x PROMOCION";
            $porkg = 0;
            $iva = 0.21;
            $precioUnit = $precio / max($cantidad, 1);
        } else {
            $sql = ($codigo < 10000) ?
                "SELECT descripcion, codigo FROM articulos WHERE codigo_corto = ? ORDER BY codigo ASC" :
                "SELECT descripcion FROM articulos WHERE codigo = ? ORDER BY codigo ASC";

            $stmt = $facturacion->prepare($sql);
            $stmt->bind_param("i", $codigo);
            $stmt->execute();
            $resultado = $stmt->get_result()->fetch_assoc();
            $descripcion = $resultado['descripcion'] ?? "SIN DESCRIPCIÓN";
            $codigo = $resultado['codigo'] ?? $codigo;

            $sqlHist = "SELECT porkg, iva, precio FROM historicos WHERE sucursal = ? AND codigo = ? AND fecha = ? ORDER BY id DESC";
            $stmt = $facturacion->prepare($sqlHist);
            $stmt->bind_param("iis", $suc, $codigo, $fecha);
            $stmt->execute();
            $resultadoHist = $stmt->get_result()->fetch_assoc();

            $porkg = $resultadoHist['porkg'] ?? 0;
            $precioUnit = $resultadoHist['precio'] ?? $precio;
            $iva = ($resultadoHist['iva'] ?? 21) / 100;

            if ($porkg) {
                $cantidad = $precio / $precioUnit;
            }
        }

        if (!isset($ticketArray[$codigo])) {
            $ticketArray[$codigo] = [$cantidad, $precio, $precioUnit, $porkg, $iva, $descripcion];
        } else {
            $ticketArray[$codigo][0] += $cantidad;
            $ticketArray[$codigo][1] += $precio;
        }
    }

    // Paginación e impresión
    $pages = ceil(count($ticketArray) / 38);
    $renglon = 587;
    $page = 1;
    $plantilla = imagecreatefromjpeg("factura.jpg");
    imagettftext($plantilla, 13, 0, 600, 1700, $black, $fontBlack, "Pag. $page/$pages");

    foreach ($ticketArray as $codigo => $datos) {
        if ($renglon > (585 + 31 * 24)) {
            imagejpeg($plantilla, "factura0$page.jpg");
            $plantilla = imagecreatefromjpeg("factura.jpg");
            $renglon = 587;
            $page++;
            imagettftext($plantilla, 13, 0, 600, 1700, $black, $fontBlack, "Pag. $page/$pages");
        }

        imagettftext($plantilla, 11, 0, 45, $renglon, $black, $fontRegular, $codigo);
        imagettftext($plantilla, 11, 0, 122, $renglon, $black, $fontRegular, $datos[5]);
        imagettftext($plantilla, 12, 0, 530, $renglon, $black, $fontRegular, number_format($datos[0], 2, ".", ""));
        imagettftext($plantilla, 12, 0, 600, $renglon, $black, $fontRegular, $datos[3] ? "Kgr." : "unidades");
        imagettftext($plantilla, 12, 0, 720, $renglon, $black, $fontRegular, number_format($datos[2], 2, ",", ""));
        imagettftext($plantilla, 12, 0, 820, $renglon, $black, $fontRegular, "0,00");
        
        $subtotal = $datos[1] / (1 + $datos[4]);
        imagettftext($plantilla, 12, 0, 920, $renglon, $black, $fontRegular, number_format($subtotal, 2, ",", ""));
        imagettftext($plantilla, 12, 0, 1020, $renglon, $black, $fontRegular, number_format($datos[4] * 100, 2, ",", ""));
        imagettftext($plantilla, 12, 0, 1100, $renglon, $black, $fontRegular, number_format($datos[1], 2, ",", ""));

        $_SESSION['totalArts'] += $datos[1];
        $renglon += 20;
	}

}

?>