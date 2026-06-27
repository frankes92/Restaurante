<?php
require_once("clsEmisor.php");

$objEmisor = new clsEmisor();

$emisores = $objEmisor->consultarListaEmisores();

while($fila = $emisores->fetch(PDO::FETCH_NAMED)){
	var_dump($fila);
}

?>