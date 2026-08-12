<?php
	require_once __DIR__ . '/config.php';

	// Crear conexion a la BBDD
	$sqlconnection = new mysqli($servername, $username, $password, $dbname);
	$connection = mysqli_connect($servername, $username, $password, $dbname);

	if ($sqlconnection->connect_error) {
    	die("Conexion fallida: " . $sqlconnection->connect_error);
	}
?>