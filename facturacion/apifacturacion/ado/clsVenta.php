<?php
require_once("conexion.php");

class clsVenta{

	function insertarDetalle($idventa,$detalle){
		$sql = "INSERT INTO detalle(id,idventa, item, idproducto, cantidad, valor_unitario, precio_unitario, igv, porcentaje_igv, valor_total, importe_total)
			VALUES (NULL, :idventa, :item, :idproducto, :cantidad, :valor_unitario, :precio_unitario, :igv, :porcentaje_igv, :valor_total, :importe_total)";
	
			global $cnx;
			$pre = $cnx->prepare($sql);

			foreach($detalle as $k=>$v){
				$parametros = array(
					':idventa'		=>$idventa,
					':item'			=>$v['item'],
					':idproducto'	=>$v['codigo'],
					':cantidad'		=>$v['cantidad'],
					':valor_unitario'=>$v['valor_unitario'],
					':precio_unitario'=>$v['precio_unitario'],
					':igv'			=>$v['igv'],
					':porcentaje_igv'=>$v['porcentaje_igv'],
					':valor_total'	=> $v['valor_total'],
					':importe_total'=> $v['importe_total']
					);
				$pre->execute($parametros);
			}
	}

	function insertarVenta($idemisor, $venta){
		$sql = "INSERT INTO venta(id, idemisor, tipocomp, idserie, serie, correlativo, fecha_emision, codmoneda, op_gravadas, op_exoneradas, op_inafectas, igv, total, codcliente)
				VALUES (NULL, :idemisor, :tipocomp, :idserie, :serie, :correlativo, :fecha_emision, :codmoneda, :op_gravadas, :op_exoneradas, :op_inafectas, :igv, :total, :codcliente)";
		$parametros = array(
					':idemisor'=>$idemisor,
					':tipocomp'=>$venta['tipodoc'],
					':idserie' =>$venta['idserie'],
					':serie'   =>$venta['serie'],
					':correlativo' =>$venta['correlativo'],
					':fecha_emision'=>$venta['fecha_emision'],
					':codmoneda'  => $venta['moneda'],
					':op_gravadas'=>$venta['op_gravadas'],
					':op_exoneradas'=>$venta['op_exoneradas'],
					':op_inafectas' =>$venta['op_inafectas'],
					':igv'			=>$venta['igv'],
					':total'		=>$venta['total'],
					':codcliente'	=>$venta['codcliente']					
				);

			global $cnx;
			$pre = $cnx->prepare($sql);
			$pre->execute($parametros);
			return $pre;
	}

	function actualizarDatosFE($idventa, $estado, $codigoerror, $mensajesunat){
		$sql = "UPDATE venta SET feestado=:feestado, fecodigoerror=:fecodigoerror, femensajesunat=:femensajesunat WHERE id=:idventa";
		global $cnx;
		$parametros = array(
						':feestado'=>$feestado, 
						':fecodigoerror'=>$fecodigoerror, 
						':femensajesunat'=>$femensajesunat, 
						':idventa'=>$idventa
					);
		$pre = $cnx->prepare($sql);
		$pre->execute($parametros);
		return $pre;
	}

	function listarComprobante(){
		$sql = "SELECT * FROM venta";
		global $cnx;
		return $cnx->query($sql);		
	}

	function obtenerUltimoComprobanteId(){
		$sql = "SELECT * FROM venta ORDER BY id DESC LIMIT 1";
		global $cnx;
		return $cnx->query($sql);		
	}

	function obtenerComprobanteId($id){
		$sql = "SELECT * FROM venta WHERE id=?";
		global $cnx;
		$pre = $cnx->prepare($sql);
		$pre->execute(array($id));
		return $pre;		
		
	}
    
    function obtenerDetalleId($id){
		$sql = "SELECT * FROM detalle WHERE idventa=?";
		global $cnx;
		$pre = $cnx->prepare($sql);
		$pre->execute(array($id));
		return $pre;		
		
	}
    
     function obtenerDetalleVenta($id){
		$sql = "SELECT
                detalle.id,
                detalle.idventa,
                detalle.item,
                producto.nombre,
                detalle.cantidad,
                detalle.valor_unitario,
                detalle.precio_unitario,
                detalle.igv,
                detalle.porcentaje_igv,
                detalle.valor_total,
                detalle.importe_total
                FROM
                detalle
                LEFT JOIN producto ON detalle.idproducto = producto.codigo
                WHERE
                detalle.idventa=?";
		global $cnx;
		$pre = $cnx->prepare($sql);
		$pre->execute(array($id));
		return $pre;		
		
	}


}

?>