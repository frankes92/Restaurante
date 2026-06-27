<?php 
require_once("funciones.php");


$emisor = 	array(
			'tipodoc'		=> '6',
			'ruc' 			=> '20602814425', 
			'razon_social'	=> 'TAQINI TECHNOLOGY SAC', 
			'nombre_comercial'	=> 'TAQINI TECHNOLOGY SAC', 
			'direccion'		=> '8 DE OCTUBRE N 123 - CHICLAYO - CHICLAYO - LAMBAYEQUE', 
			'pais'			=> 'PE', 
			'departamento'  => 'LAMBAYEQUE',//LAMBAYEQUE 
			'provincia'		=> 'CHICLAYO',//CHICLAYO 
			'distrito'		=> 'CHICLAYO', //CHICLAYO
			'ubigeo'		=> '140101', //CHICLAYO
			'usuario_sol'	=> 'MODDATOS', //USUARIO SECUNDARIO EMISOR ELECTRONICO
			'clave_sol'		=> 'MODDATOS' //CLAVE DE USUARIO SECUNDARIO EMISOR ELECTRONICO
			);


$cabecera = array(
			"tipodoc"		=>"RC",
			"serie"			=>date('Ymd'),
			"correlativo"	=>"1",
			"fecha_emision" =>date('Y-m-d'),			
			"fecha_envio"	=>date('Y-m-d')	
	);


$items = array();

$cant=500;

for($i=1;$i<=$cant;$i++){

	$item_total = rand(10,100);
	$item_valor = $item_total/1.18;
	$item_valor = (float) number_format($item_valor,2,'.','');

	$item_igv = $item_total - $item_valor;

	$items[] = array(
			"item"				=> $i,
			"tipodoc"			=> "03",
			"serie"				=> "B00".rand(1,9),
			"correlativo"		=> rand(1,50000),
			"condicion"			=> rand(1,3), //1->Registro, 2->Actuali, 3->Bajas
			"moneda"			=> "PEN",			
			"importe_total"		=> $item_total,
			"valor_total"		=> $item_valor,
			"igv_total"			=> $item_igv,
			"codigo_afectacion"	=>"1000",
			"nombre_afectacion"	=>"IGV",
			"tipo_afectacion"	=>"VAT"
		);
}

$objFun = new Funciones();


$nombrexml = $emisor['ruc'].'-'.$cabecera['tipodoc'].'-'.$cabecera['serie'].'-'.$cabecera['correlativo'];

$objFun->generar_xml_resumen_boletas($emisor, $cabecera, $items, $nombrexml);

require_once("ApiFacturacion.php");
$api = new ApiFacturacion();

$ticket = $api->EnviarResumenComprobantes($emisor,$nombrexml);

$api->ConsultarTicket($emisor, $cabecera, $ticket);

?>