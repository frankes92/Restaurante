<?php
session_start();
if (isset($_SESSION['idusuario'])) {
    header('Location: vistas/nuevaorden');
} else {
    header('Location: vistas/login');
}
exit;
?>
