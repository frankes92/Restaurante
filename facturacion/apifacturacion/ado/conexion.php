<?php 
try{

	$manejador = "mysql";
	$servidor = "localhost";
	$usuario = "root"; // usuario con acceso a la base de datos, generalmente root
	$pass = "";// aquí coloca la clave de la base de datos del servidor o hosting
	$base = "facturacion"; //nombre de la base de datos
	$cadena = "$manejador:host=$servidor;dbname=$base";
	$cnx = new PDO($cadena, $usuario, $pass, array(PDO::ATTR_PERSISTENT => "true", PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
}catch(Exception $ex){
	echo "Error de acceso, informelo a la brevedad.";
	exit;
}

?>