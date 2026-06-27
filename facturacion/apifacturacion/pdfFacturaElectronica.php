<?php 
define('FPDF_FONTPATH','font/'); 
require_once('fpdf/fpdf.php');
require_once("phpqrcode/qrlib.php");
require_once("ado/clsEmisor.php");
require_once("ado/clsCliente.php");
require_once("ado/clsVenta.php");
require_once("ado/clsCompartido.php");


$objVentas= new clsVenta();
$objEmisor = new clsEmisor();
$objCliente = new clsCliente();
$objCompartido = new clsCompartido();

isset($_GET["id"])? $id=$_GET["id"] : $id="";

$venta=$objVentas->obtenerComprobanteId($id);
$venta = $venta->fetch(PDO::FETCH_NAMED);

$emisor = $objEmisor->obtenerEmisor($venta["idemisor"]);
$emisor = $emisor->fetch(PDO::FETCH_NAMED);

$cliente=$objCliente->obtenerClienteId($venta["codcliente"]);
$cliente = $cliente->fetch(PDO::FETCH_NAMED);

$detalle=$objVentas->obtenerDetalleVenta($id);
 

//$producto=$objCompartido->obtenerProducto($detalle["idproducto"]);
//$producto = $producto->fetchAll(PDO::FETCH_NAMED);

//var_dump($producto);

$pdf = new FPDF();
$pdf->AddPage('P','A4');
//$pdf->AddPage('P',array(80,200));
$pdf->SetFont('Arial','',12);

$pdf->SetFont('Arial','B',12);

$pdf->Image("logo_empresa.jpg",40,2,25,25);

$pdf->Cell(100);
$pdf->Cell(80,6,$emisor["ruc"],'LRT',1,'C',0);

$pdf->Cell(100);

if($venta["tipocomp"]=="01"){
    
  $pdf->Cell(80,6,"FACTURA ELECTRONICA",'LR',1,'C',0);  
    
}else if($venta["tipocomp"]=="03"){
    
      $pdf->Cell(80,6,"BOLETA ELECTRONICA",'LR',1,'C',0);
    
}else if($venta["tipocomp"]=="07"){
    
      $pdf->Cell(80,6,"NOTA DE CRÉDITO ELECTRONICA",'LR',1,'C',0);  
    
}else if($venta["tipocomp"]=="08"){
    
      $pdf->Cell(80,6,"NOTA DE DÉBITO ELECTRONICA",'LR',1,'C',0);  
}



$pdf->Cell(100);
$pdf->Cell(80,6,$venta["serie"]."-".$venta["correlativo"],'BLR',0,'C',0);

$pdf->SetAutoPageBreak('auto',2);

$pdf->SetDisplayMode(75);

$pdf->Ln();

$pdf->SetFont('Arial','B',8);
$pdf->Cell(30,6,"RUC:",0,0,'L',0);
$pdf->SetFont('Arial','',8);
$pdf->Cell(30,6,$cliente["nrodoc"],0,1,'L',0);

$pdf->SetFont('Arial','B',8);
$pdf->Cell(30,6,"CLIENTE:",0,0,'L',0);
$pdf->SetFont('Arial','',8);
$pdf->Cell(30,6,$cliente["razon_social"],0,1,'L',0);

$pdf->SetFont('Arial','B',8);
$pdf->Cell(30,6,"DIRECCION:",0,0,'L',0);
$pdf->SetFont('Arial','',8);
$pdf->Cell(30,6,$cliente["direccion"],0,1,'L',0);

$pdf->Ln(3);

$pdf->SetFont('Arial','B',8);
$pdf->Cell(10,6,"ITEM",1,0,'C',0);
$pdf->Cell(20,6,"CANTIDAD",1,0,'C',0);
$pdf->Cell(100,6,"PRODUCTO",1,0,'C',0);
$pdf->Cell(20,6,"V.U.",1,0,'C',0);
$pdf->Cell(25,6,"SUBTOTAL",1,1,'C',0);

$pdf->SetFont('Arial','',8);

$i=0;

while($fila = $detalle->fetch(PDO::FETCH_ASSOC)){
   //var_dump($fila);
    $i++;
    
    $pdf->Cell(10,6,$i,1,0,'C',0);
	$pdf->Cell(20,6,$fila["cantidad"],1,0,'C',0);
	$pdf->Cell(100,6,$fila["nombre"],1,0,'L',0);
	$pdf->Cell(20,6,$fila["valor_unitario"],1,0,'C',0);
	$pdf->Cell(25,6,$fila["valor_total"],1,1,'C',0);    

}

	


$pdf->Cell(150,6,"IMPORTE TOTAL",'T',0,'R',0);
$pdf->Cell(25,6,$venta["total"],1,1,'C',0);

$pdf->Cell(150,6,"OP. GRAVADAS",'',0,'R',0);
$pdf->Cell(25,6,$venta["op_gravadas"],1,1,'C',0);
$pdf->Cell(150,6,"OP. INAFECTAS",'',0,'R',0);
$pdf->Cell(25,6,$venta["op_inafectas"],1,1,'C',0);
$pdf->Cell(150,6,"OP. EXONERADAS",'',0,'R',0);
$pdf->Cell(25,6,$venta["op_exoneradas"],1,1,'C',0);
$pdf->Cell(150,6,"IGV",'',0,'R',0);
$pdf->Cell(25,6,$venta["igv"],1,1,'C',0);


//codigo qr
		/*RUC | TIPO DE DOCUMENTO | SERIE | NUMERO | MTO TOTAL IGV | MTO TOTAL DEL COMPROBANTE | FECHA DE EMISION |TIPO DE DOCUMENTO ADQUIRENTE | NUMERO DE DOCUMENTO ADQUIRENTE |*/

$ruc = $emisor["ruc"];
$tipo_documento = $venta["tipocomp"]; //factura
$serie = $venta["serie"];
$correlativo = $venta["correlativo"];
$igv = $venta["igv"];
$total = $venta["total"];
$fecha = $venta["fecha_emision"];
$tipodoccliente =$cliente["tipodoc"];
$nro_doc_cliente = $cliente["nrodoc"];

$nombrexml = $ruc."-".$tipo_documento."-".$serie."-".$correlativo;     
//"20102020201-01-F001-23323";

$text_qr = $ruc." | ".$tipo_documento." | ".$serie." | ".$correlativo." | ".$igv." | ".$total." | ".$fecha." | ".$tipodoccliente." | ".$nro_doc_cliente;
$ruta_qr = $nombrexml.'.png';

QRcode::png($text_qr, $ruta_qr, 'Q',15, 0);

$pdf->Image($ruta_qr, 80 , $pdf->GetY(),25,25);

$pdf->Ln(30);
$pdf->Cell(160,6,utf8_decode("Representación impresa de la Factura Electrónica"),0,0,'C',0);


$pdf->Output('I',$nombrexml.'.pdf');
//$pdf->Output('D',$nombrexml.'.pdf');
?>