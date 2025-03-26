<?php
require_once 'config.php';
require_once 'funciones.php';
ob_start();
session_start();

include "src/utils/phpqrcode/qrlib.php";
$_SESSION['totalArts']=0;
$_SESSION['totalfactura']=0;
date_default_timezone_set('America/Argentina/Cordoba');

if (file_exists("archivos/temporales/art.txt")) {

	$suc = 1;
	$fecha = "20230801";
	$tipo = 6;
	$ptoVta = 413;
	$nroFact = 120233;

	
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
			imagettftext($plantilla, 15, 0, 910, 1671, $black, $fontBlack,("CAE N°: ". substr($datos, 165, 14)));
			imagettftext($plantilla, 15, 0, 780, 1700, $black, $fontBlack,("Fecha de Vto. de CAE: ". substr($datos, 179, 10)));
		} else {
			imagettftext($plantilla, 15, 0, 900, 1671, $black, $fontBlack,("CAEA N°: ". substr($datos, 165, 14)));
			imagettftext($plantilla, 15, 0, 770, 1700, $black, $fontBlack,("Fecha de Vto. de CAEA: ". substr($datos, 179, 10)));
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

	}else{

		require_once 'templates/header.php';
		require_once 'config.php';
		require_once 'funciones.php';
		ob_start();
		session_start();

		?>
<link href="css/botones.css" rel="stylesheet">
<div class="content">
    <div class="container">
    <div class="col-md-8 col-sm-8 col-xs-12">
    <div class="well text-center">
    <center><h2><font style='font-variant:small-caps;' >Reimpresión de Comprobantes</font></h2></center><hr>

    
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

	}








function facturaA($black, $fontRegular, $fontBlack, $datos)
{
	$path = "src/utils/";
	$plantilla = imagecreatefromjpeg($path . "plantillaARe.jpg");
	imagettftext($plantilla, 40, 0, 602, 150, $black, $fontBlack, "A");
	$neto = substr($datos, 114, 10) + substr($datos, 134, 10);
	imagettftext($plantilla, 14, 0, 1058, 1385, $black, $fontBlack, number_format($neto, 2, ",", ""));
	imagettftext($plantilla, 14, 0, 1058, 1412, $black, $fontBlack, "0,00");
	imagettftext($plantilla, 14, 0, 1058, 1439, $black, $fontBlack, number_format(substr($datos, 104, 10), 2, ",", ""));
	imagettftext($plantilla, 14, 0, 1058, 1466, $black, $fontBlack, number_format(substr($datos, 124, 10), 2, ",", ""));
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
	imagettftext($plantilla, 15, 0, 1058, 1605, $black, $fontBlack, number_format(substr($datos, 84, 10), 2, ",", ""));

	$_SESSION['totalfactura']=substr($datos, 84, 10);
	return $plantilla;

}

function facturaB($black, $fontRegular, $fontBlack, $datos)
{
	$path = "src/utils/";
	$plantilla = imagecreatefromjpeg($path . "plantillaBRe.jpg");
	imagettftext($plantilla, 40, 0, 602, 150, $black, $fontBlack, "B");
	$neto = substr($datos, 114, 10) + substr($datos, 134, 10);
	imagettftext($plantilla, 14, 0, 1050, 1510, $black, $fontBlack, number_format(substr($datos, 84, 10), 2, ",", ""));
	//imagettftext($plantilla, 14, 0, 1050, 1510, $black, $fontBlack, number_format($neto, 2, ",", ""));
	imagettftext($plantilla, 14, 0, 1050, 1550, $black, $fontBlack, "0,00");
	imagettftext($plantilla, 15, 0, 1050, 1590, $black, $fontBlack, number_format(substr($datos, 84, 10), 2, ",", ""));
	
	$_SESSION['totalfactura']=substr($datos, 84, 10);

	return $plantilla;
}

function descrA($black, $fontRegular, $fontBlack, $suc, $fecha)
{
	$ventas = new mysqli("super-imperio.com.ar", , , "ventas");
	$plantilla = imagecreatefromjpeg("factura.jpg");
	

	$commFile = fopen("archivos/temporales/art.txt", "r");
	$ticketArray = array();
	while (($reg = fgets($commFile)) !== false) {
		$article = substr($reg, 6,6);
		$amount = substr($reg, 25,5);
		$price = substr($reg, 30,10);
		echo ">>>>>>>>>>> " . $amount . "*******" . $article;
		$espromo = strpos($article, "P");

		if ($espromo === FALSE) {

			
			
			if ($article < 10000) {
				$sql = "SELECT `descripcion`, `codigo` FROM `articulos` WHERE `codigo_corto` = $article ORDER BY `codigo` ASC";
			}
			else {
				$sql = "SELECT `descripcion` FROM `articulos` WHERE `codigo` = $article ORDER BY `codigo` ASC";
			}

			$query = $ventas->query($sql);
			$query = $query->fetch_assoc();
			$des = $query['descripcion'];
			if (isset($query['codigo'])) {
				$article = $query['codigo'];
			}
		}else{
			
			$des = "DESCUENTO x PROMOCION";
		}
		
		if ($espromo === FALSE) {
			
		
			$sql = "SELECT `porkg`, `iva`, `precio` FROM `historicos` WHERE `sucursal` = $suc AND `codigo` = $article AND `fecha` = '$fecha' ORDER BY `id` DESC";
			$query = $ventas->query($sql);
			$query = $query->fetch_assoc();
			$med = $query['porkg'];
			$priceUnit = $query['precio'];
			if ($med == 1) {
				$amount = $price /  $priceUnit;
			}
			$alic = $query['iva'];
			$alic = $alic / 100;
		}else{

		}
		
		
		if (empty($ticketArray[$article])) {
			$ticketArray[$article] = array($amount, $price, $priceUnit, $med, $alic, $des);
		} else {
			$ticketArray[$article][0] += $amount;
			$ticketArray[$article][1] += floatval($price);
		}
	}
	$pages = ceil(count($ticketArray) / 38);
	$renglon = 587;
	$page = 1;
	imagettftext($plantilla, 13, 0, 600, 1700, $black, $fontBlack, "Pag. $page/$pages");
    foreach ($ticketArray as $key => $datos) {
    	if ($renglon > (585 + 31*24)) {
    		imagejpeg($plantilla, "factura0$page.jpg");
    		$plantilla = imagecreatefromjpeg("factura.jpg");
    		$renglon = 587;
    		$page++;
    		imagettftext($plantilla, 13, 0, 600, 1700, $black, $fontBlack, "Pag. $page/$pages");
    	}
    	imagettftext($plantilla, 11, 0, 45, $renglon, $black, $fontRegular, $key);
		imagettftext($plantilla, 11, 0, 122, $renglon, $black, $fontRegular, $datos[5]);
		imagettftext($plantilla, 12, 0, 530, $renglon, $black, $fontRegular, number_format($datos[0], 2, ".", ""));
		if ($datos[3] == 0) {
			imagettftext($plantilla, 12, 0, 600, $renglon, $black, $fontRegular, "unidades");
		}
		else {
			imagettftext($plantilla, 12, 0, 600, $renglon, $black, $fontRegular, "Kgr.");	
		}
		imagettftext($plantilla, 12, 0, 720, $renglon, $black, $fontRegular, number_format($datos[2], 2, ",", ""));
		imagettftext($plantilla, 12, 0, 820, $renglon, $black, $fontRegular, "0,00");
		//calculo subtotal sin iva
		$iva = "1." . str_replace(".", "", $datos[4]);
		$subtotal = $datos[1] / $iva;
		imagettftext($plantilla, 12, 0, 920, $renglon, $black, $fontRegular, number_format($subtotal, 2, ",", ""));
		imagettftext($plantilla, 12, 0, 1020, $renglon, $black, $fontRegular, number_format($datos[4], 2, ",", ""));
		imagettftext($plantilla, 12, 0, 1100, $renglon, $black, $fontRegular, number_format($datos[1], 2, ",", ""));
		$_SESSION['totalArts']+=$datos[1];

		$renglon += 20;
    }

	return array($plantilla, $page);
}

function descrB($black, $fontRegular, $fontBlack, $suc, $fecha)
{

	$ventas = new mysqli("super-imperio.com.ar", "", "", "ventas");
	$plantilla = imagecreatefromjpeg("factura.jpg");
	
	$commFile = fopen("archivos/temporales/art.txt", "r");
	$ticketArray = array();
	while (($reg = fgets($commFile)) !== false) {
		echo $reg;echo "<BR>";
		$article = substr($reg, 6,6);
		$amount = substr($reg, 25,5);
		$price = substr($reg, 30,10);


		$espromo = strpos($article, "P");

		if ($espromo === FALSE) {

			
			
			if ($article < 10000) {
				$sql = "SELECT `descripcion`, `codigo` FROM `articulos` WHERE `codigo_corto` = $article ORDER BY `codigo` ASC";
			}
			else {
				$sql = "SELECT `descripcion` FROM `articulos` WHERE `codigo` = $article ORDER BY `codigo` ASC";
			}

			$query = $ventas->query($sql);
			$query = $query->fetch_assoc();
			$des = $query['descripcion'];
			if (isset($query['codigo'])) {
				$article = $query['codigo'];
			}
		}else{
			
			$des = "DESCUENTO x PROMOCION";
		}
		
		if ($espromo === FALSE) {
			
		
			$sql = "SELECT `porkg`, `iva`, `precio` FROM `historicos` WHERE `sucursal` = $suc AND `codigo` = $article AND `fecha` = '$fecha' ORDER BY `id` DESC";
			$query = $ventas->query($sql);
			$query = $query->fetch_assoc();
			$med = $query['porkg'];
			$priceUnit = $query['precio'];
			if ($med == 1) {
				$amount = $price /  $priceUnit;
			}
			$alic = $query['iva'];
			$alic = $alic / 100;
		}else{
				$priceUnit = "-";

		}
		


		if (empty($ticketArray[$article])) {
		
			$ticketArray[$article] = array($amount, $price, $priceUnit, $med, $alic, $des);
		} else {
		
			$ticketArray[$article][0] += $amount;
			$ticketArray[$article][1] += floatval($price);
		}

	}

	$pages = ceil(count($ticketArray) / 45);
	$renglon = 587;
	$page = 1;
	imagettftext($plantilla, 13, 0, 600, 1700, $black, $fontBlack, "Pag. $page/$pages");
    foreach ($ticketArray as $key => $datos) {
    	if ($renglon > (585 + 37*24)) {
    		imagejpeg($plantilla, "factura0$page.jpg");
    		$plantilla = imagecreatefromjpeg("factura.jpg");
    		$renglon = 587;
    		$page++;
    		imagettftext($plantilla, 13, 0, 600, 1700, $black, $fontBlack, "Pag. $page/$pages");
    	}
    	imagettftext($plantilla, 11, 0, 45, $renglon, $black, $fontRegular, $key);
		imagettftext($plantilla, 11, 0, 122, $renglon, $black, $fontRegular, $datos[5]);
		imagettftext($plantilla, 12, 0, 530, $renglon, $black, $fontRegular, number_format($datos[0], 2, ".", ""));
		if ($datos[3] == 0) {
			imagettftext($plantilla, 12, 0, 600, $renglon, $black, $fontRegular, "unidades");
		}
		else {
			imagettftext($plantilla, 12, 0, 600, $renglon, $black, $fontRegular, "Kgr.");	
		}
		imagettftext($plantilla, 12, 0, 720, $renglon, $black, $fontRegular, number_format($datos[2], 2, ",", ""));
		imagettftext($plantilla, 12, 0, 820, $renglon, $black, $fontRegular, "0,00");
		imagettftext($plantilla, 12, 0, 920, $renglon, $black, $fontRegular, "0,00");
		//imagettftext($plantilla, 12, 0, 920, $renglon, $black, $fontRegular, number_format($datos[2], 2, ",", ""));
		imagettftext($plantilla, 12, 0, 1100, $renglon, $black, $fontRegular, number_format($datos[1], 2, ",", ""));
		
		$_SESSION['totalArts']+=$datos[1];
		

		$renglon += 20;
    }

	return array($plantilla, $page);
}

?>