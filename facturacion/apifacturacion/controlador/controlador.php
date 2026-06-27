<?php
require_once("../ado/clsCompartido.php");
require_once("../ado/clsEmisor.php");
require_once("../ado/clsVenta.php");
require_once("../ado/clsCliente.php");
require_once("../funciones.php");
require_once("../cantidad_en_letras.php");
require_once("../ApiFacturacion.php");

$accion = $_POST['accion'];

controlador($accion);

function controlador($accion){

	$objCompartido = new clsCompartido();
	$objEmisor = new clsEmisor();
	$funciones = new Funciones();
	$api = new ApiFacturacion();
	$objVenta = new clsVenta();
	$objCliente = new clsCliente();

	switch ($accion) {

		case 'LISTAR_SERIES':
			
			$series = $objCompartido->listarSerie($_POST['tipocomp']);
			$series = $series->fetchAll(PDO::FETCH_NAMED);
			$series = array("series"=>$series);
			echo json_encode($series);			
			break;
		
		case 'OBTENER_CORRELATIVO':
			$serie = $objCompartido->obtenerSerie($_POST['idserie']);
			$serie = $serie->fetch(PDO::FETCH_NAMED);
			$correlativo = $serie['correlativo']+1;
			echo $correlativo;
			break;

		case 'BUSCAR_PRODUCTO':
			$productos = $objCompartido->listarProducto($_POST['filtro']);
			$productos = $productos->fetchAll(PDO::FETCH_NAMED);
			$productos = array("productos"=>$productos);
			echo json_encode($productos);			
			break;			

		case 'ADD_PRODUCTO':

			// ----- INICIO LOGICA DE CARRITO ----- //

			$producto = $objCompartido->obtenerProducto($_POST['codigo']);
			$producto = $producto->fetch(PDO::FETCH_NAMED);

			session_start();

			if(!isset($_SESSION['carrito'])){
				$_SESSION['carrito'] = array();
			}

			$carrito = $_SESSION['carrito'];

			$item = count($carrito)+1;
			$cantidad = 1;
			$existe = false;
			foreach ($carrito as $k => $v) {
				if($v['codigo']==$_POST['codigo']){
					$item = $k;
					$existe = true;
					break;
				}
			}

			if(!$existe){
				$carrito[$item] = array(
						'codigo'=>$producto['codigo'],
						'nombre'=>$producto['nombre'],
						'precio'=>$producto['precio'],
						'unidad'=>$producto['unidad'],
						'codigoafectacion'=>$producto['codigoafectacion'],
						'cantidad'=>$cantidad
						);

			}else{
				$carrito[$item]['cantidad']++;
			}

			$_SESSION['carrito'] = $carrito;

			//------------------ FIN LOGICA DE CARRITO ---------- //

			$op_gravadas=0.00;
			$op_exoneradas=0.00;
			$op_inafectas=0.00;
			$igv;
			$igv_porcentaje=0.18;

			foreach ($carrito as $K => $v) {
				if($v['codigoafectacion']=='10'){
					$op_gravadas = $op_gravadas+$v['precio']*$v['cantidad'];
				}

				if($v['codigoafectacion']=='20'){
					$op_exoneradas = $op_exoneradas+$v['precio']*$v['cantidad'];
				}

				if($v['codigoafectacion']=='30'){
					$op_inafectas = $op_inafectas+$v['precio']*$v['cantidad'];
				}												
			}

			$igv = $op_gravadas*$igv_porcentaje;

			$total = $op_gravadas + $op_exoneradas + $op_inafectas + $igv;


			echo "<table class='table table-bordered table-hover'>";
			echo "<tr>";
			echo "<th>ITEM</th><th>CANT</th><th>UND</th><th>PRODUCTO</th><th>VU</th><th>SUBT</th>";
			echo "</tr>";
			foreach($carrito as $k=>$v){
				echo "<tr>";
				echo "<td>".$k."</td><td>".$v['cantidad']."</td><td>".$v['unidad']."</td><td>".$v['nombre']."</td><td>".$v['precio']."</td><td>".($v['precio']*$v['cantidad'])."</td>";
				echo "</tr>";
			}

			echo "<tr><td colspan='5' align='right'>OP. GRAVADAS</td><td>".$op_gravadas."</td></tr>";
			echo "<tr><td colspan='5' align='right'>IGV(18%)</td><td>".$igv."</td></tr>";			
			echo "<tr><td colspan='5' align='right'>OP. EXONERADAS</td><td>".$op_exoneradas."</td></tr>";
			echo "<tr><td colspan='5' align='right'>OP. INAFECTAS</td><td>".$op_inafectas."</td></tr>";						
			echo "<tr><td colspan='5' align='right'><b>TOTAL</b></td><td><b>".$total."</b></td></tr>";		
			echo "</table>";

			break;	


		case 'CANCELAR_CARRITO':
			session_start();
			session_destroy();
			break;


		case 'GUARDAR_VENTA':
			session_start();

			//logica de ventas
			//--------------------------
			//fin logica de ventas



			//INICIO PROCESO FACTURACION

			$funciones = new Funciones();

			//obtenemos los datos del emisor de la BD
			$idemisor = $_POST['idemisor'];
			$emisor = $objEmisor->obtenerEmisor($idemisor);
			$emisor = $emisor->fetch(PDO::FETCH_NAMED);


			$cliente = array(
				'tipodoc'		=> $_POST['tipodoc'],//6->ruc, 1-> dni 
				'ruc'			=> $_POST['nrodoc'], 
				'razon_social'  => $_POST['razon_social'], 
				'direccion'		=> $_POST['direccion'],
				'pais'			=> 'PE'
				);	

			$cliente_existe = $objCliente->consultarCliente($_POST['nrodoc']);

			if($cliente_existe->rowCount()>0){
				$cliente_existe = $cliente_existe->fetch(PDO::FETCH_NAMED);
			}else{
				$objCliente->insertarCliente($cliente);
				$cliente_existe = $objCliente->consultarCliente($_POST['nrodoc']);
				$cliente_existe = $cliente_existe->fetch(PDO::FETCH_NAMED);
			}
			$idcliente = $cliente_existe['id'];

			$carrito = $_SESSION['carrito'];
			$detalle = array();
			$igv_porcentaje = 0.18;



			$op_gravadas=0.00;
			$op_exoneradas=0.00;
			$op_inafectas=0.00;
			$igv = 0;

			foreach ($carrito as $k => $v) {

				$producto = $objCompartido->obtenerProducto($v['codigo']);
				$producto = $producto->fetch(PDO::FETCH_NAMED);

				$afectacion = $objCompartido->obtenerRegistroAfectacion($producto['codigoafectacion']);
				$afectacion = $afectacion->fetch(PDO::FETCH_NAMED);

				$igv_detalle =0;
				$factor_porcentaje = 1;
				if($producto['codigoafectacion']==10){
					$igv_detalle = $v['precio']*$v['cantidad']*$igv_porcentaje;
					$factor_porcentaje = 1+ $igv_porcentaje;
				}

				$itemx = array(
					'item' 				=> $k,
					'codigo'			=> $v['codigo'],
					'descripcion'		=> $v['nombre'],
					'cantidad'			=> $v['cantidad'],
					'valor_unitario'	=> $v['precio'],
					'precio_unitario'	=> $v['precio']*$factor_porcentaje,
					'tipo_precio'		=> $producto['tipo_precio'], //ya incluye igv
					'igv'				=> $igv_detalle,
					'porcentaje_igv'	=> $igv_porcentaje*100,
					'valor_total'		=> $v['precio']*$v['cantidad'],
					'importe_total'		=> $v['precio']*$v['cantidad']*$factor_porcentaje,
					'unidad'			=> $v['unidad'],//unidad,
					'codigo_afectacion_alt'	=> $producto['codigoafectacion'],
					'codigo_afectacion'	=> $afectacion['codigo_afectacion'],
					'nombre_afectacion'	=> $afectacion['nombre_afectacion'],
					'tipo_afectacion'	=> $afectacion['tipo_afectacion']			 
				);

				$itemx;

				$detalle[] = $itemx;

			

				if($itemx['codigo_afectacion_alt']==10){
					$op_gravadas = $op_gravadas + $itemx['valor_total'];
				}

				if($itemx['codigo_afectacion_alt']==20){
					$op_exoneradas = $op_exoneradas + $itemx['valor_total'];
				}				

				if($itemx['codigo_afectacion_alt']==30){
					$op_inafectas = $op_inafectas + $itemx['valor_total'];
				}

				$igv = $igv + $igv_detalle;				
			}


			$total = $op_gravadas + $op_exoneradas + $op_inafectas + $igv;

			$idserie = $_POST['idserie'];

			$seriex = $objCompartido->obtenerSerie($idserie);
			$seriex = $seriex->fetch(PDO::FETCH_NAMED);

			$comprobante =	array(
					'tipodoc'		=> $_POST['tipocomp'],
					'idserie'		=> $idserie,
					'serie'			=> $seriex['serie'],
					'correlativo'	=> $seriex['correlativo']+1,
					'fecha_emision' => $_POST['fecha_emision'],
					'moneda'		=> $_POST['moneda'], //PEN->SOLES; USD->DOLARES
					'op_gravadas'	=> $op_gravadas,
					'igv'			=> $igv,
					'op_exoneradas'	=> $op_exoneradas,
					'op_inafectas'	=> $op_inafectas,
					'total'			=> $total,
					'total_texto'	=> CantidadEnLetra($total),
					'codcliente'	=> $idcliente
				);			

			$objCompartido->actualizarSerie($idserie, $comprobante['correlativo']);

			$nombre = $emisor['ruc'].'-'.$comprobante['tipodoc'].'-'.$comprobante['serie'].'-'.$comprobante['correlativo'];

			if($comprobante['tipodoc']=='01' || $comprobante['tipodoc']=='03'){
				$funciones->CrearXMLFactura($nombre, $emisor, $cliente, $comprobante, $detalle);
			}else if($comprobante['tipodoc']=='07'){ //nota de credito
				$funciones->CrearXMLNotaCredito($nombre, $emisor, $cliente, $comprobante, $detalle);
			}
			
			$api->EnviarComprobanteElectronico($emisor,$nombre,"../");
			//FIN FACTURACION ELECTRONICA


			//REGISTRO EN BASE DE DATOS

			$objVenta->insertarVenta($idemisor, $comprobante);
			$venta = $objVenta->obtenerUltimoComprobanteId();
			$venta = $venta->fetch(PDO::FETCH_NAMED);

			$objVenta->insertarDetalle($venta['id'],$detalle);

			//FIN DE REGISTRO EN BASE DE DATOS
			echo "VENTA CORRECTA";
			echo "<script>window.open('./apifacturacion/pdfFacturaElectronica.php?id=".$venta['id']."','_blank')</script>";
			session_destroy();

		default:
			# code...
			break;
	}

}

?>