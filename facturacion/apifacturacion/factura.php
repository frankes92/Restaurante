<?php
require_once("funciones.php");
require_once("cantidad_en_letras.php");
$funciones = new Funciones();
//01->FACTURA -> F 
//03->BOLETA  -> B
//07->NOTA DE CREDITO -> F , B
//08->NOTA DE DEBITO  -> F , B
//09->GUIA DE REMISION -> T
//RUC-TIPO DOC-SERIE-CORRELATIVO.XML

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


$cliente = array(
			'tipodoc'		=> '6',//6->ruc, 1-> dni 
			'ruc'			=> '20480631286', 
			'razon_social'  => 'ASOCIACION CENTRO DE ENTRENAMIENTO EN TECNOLOGIAS DE INFORMACION - CETI', 
			'direccion'		=> 'Cal. Francisco Cuneo-Pataz Nro. 270(Frente al Circulo Departamental de Emple)',
			'pais'			=> 'PE', 
			'departamento'  => '140000',//LAMBAYEQUE 
			'provincia'		=> '140100',//CHICLAYO 
			'distrito'		=> '140101' //CHICLAYO
			);			



$comprobante =	array(
			'tipodoc'		=> '01',
			'serie'			=> 'F002',
			'correlativo'	=> '777',
			'fecha_emision' => '2020-08-11',
			'moneda'		=> 'PEN', //PEN->SOLES; USD->DOLARES
			'op_gravadas'		=> 0, //OP. GRAVADAS
			'igv'			=> 0,
			'op_exoneradas'	=> 0,
			'op_inafectas'	=> 0,
			'total'			=> 0,
			'total_texto'	=> 'CIENTO DIECIOCHO CON 00/00 SOLES'
		);

$detalle = 
			array(
				array(
					'item' 				=> 1,
					'codigo'			=> '11',
					'descripcion'		=> 'ACEITE CAPRI',
					'cantidad'			=> 1,
					'valor_unitario'	=> 50,
					'precio_unitario'	=> 59,
					'tipo_precio'		=> "01", //ya incluye igv
					'igv'				=> 9,
					'porcentaje_igv'	=> 18,
					'valor_total'		=> 50,
					'importe_total'		=> 59,
					'unidad'			=> 'NIU',//unidad,
					'codigo_afectacion_alt'	=> '10',
					'codigo_afectacion'	=> 1000,
					'nombre_afectacion'	=>'IGV',
					'tipo_afectacion'	=> 'VAT' //GRAVADAS				 
				),
				array(
					'item' 				=> 2,
					'codigo'			=> '22',
					'descripcion'		=> 'AYUDIN',
					'cantidad'			=> 1,
					'valor_unitario'	=> 50,
					'precio_unitario'	=> 59,
					'tipo_precio'		=> "01", //ya incluye igv
					'igv'				=> 9,
					'porcentaje_igv'	=> 18,
					'valor_total'		=> 50,
					'importe_total'		=> 59,
					'unidad'			=> 'NIU',//unidad,
					'codigo_afectacion_alt'	=> '10',
					'codigo_afectacion'	=> 1000,
					'nombre_afectacion'	=>	'IGV',
					'tipo_afectacion'	=> 'VAT' //GRAVADAS			 
				)				
			);

$op_gravadas = 0;
$op_exoneradas = 0;
$op_inafectas = 0;
$igv = 0;
$total = 0; //op_gravadas

foreach ($detalle as $k => $v) {
	if($v['codigo_afectacion_alt']==10){
		$op_gravadas = $op_gravadas + $v['valor_total'];
	}
	if($v['codigo_afectacion_alt']==20){
		$op_exoneradas = $op_exoneradas + $v['valor_total'];
	}
	if($v['codigo_afectacion_alt']==30){
		$op_inafectas = $op_inafectas + $v['valor_total'];
	}

	$igv = $igv + $v['igv'];
	$total = $total + $v['importe_total'];
}

$comprobante['op_gravadas'] = $op_gravadas;
$comprobante['op_exoneradas'] = $op_exoneradas;
$comprobante['op_inafectas'] = $op_inafectas;
$comprobante['igv'] = $igv;
$comprobante['total'] = $total;
$comprobante['total_texto'] = CantidadEnLetra($total);

$nombre = $emisor['ruc'].'-'.$comprobante['tipodoc'].'-'.$comprobante['serie'].'-'.$comprobante['correlativo'];



if($comprobante['tipodoc']=='01' || $comprobante['tipodoc']=='03'){
	$funciones->CrearXMLFactura($nombre, $emisor, $cliente, $comprobante, $detalle);
}else if($comprobante['tipodoc']=='07'){ //nota de credito
	$funciones->CrearXMLNotaCredito($nombre, $emisor, $cliente, $comprobante, $detalle);
}



require_once("ApiFacturacion.php");

$api = new ApiFacturacion();

$api->EnviarComprobanteElectronico($emisor,$nombre);

?>