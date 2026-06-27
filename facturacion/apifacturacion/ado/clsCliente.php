<?php
require_once("conexion.php");

class clsCliente{

	function insertarCliente($cliente){

		$sql = "INSERT INTO cliente(id, tipodoc, nrodoc, razon_social, direccion)
				VALUES (NULL, :tipodoc, :nrodoc, :razon_social, :direccion)";

		$parametros = array(
						':tipodoc'		=>$cliente['tipodoc'],
						':nrodoc' 		=>$cliente['ruc'],
						':razon_social'	=>$cliente['razon_social'],
						':direccion'	=>$cliente['direccion']
						);
		global $cnx;
		$pre = $cnx->prepare($sql);
		$pre->execute($parametros);
		return $pre;
	}

	function consultarCliente($nrodoc){
		$sql = "SELECT * FROM cliente WHERE nrodoc=:nrodoc";

		$parametros = array(':nrodoc'=>$nrodoc);

		global $cnx;
		$pre = $cnx->prepare($sql);
		$pre->execute($parametros);
		return $pre;	
	}

    function obtenerClienteId($id){
		$sql = "SELECT * FROM cliente WHERE id=?";
		global $cnx;
		$pre = $cnx->prepare($sql);
		$pre->execute(array($id));
		return $pre;		
		
	}
}

?>