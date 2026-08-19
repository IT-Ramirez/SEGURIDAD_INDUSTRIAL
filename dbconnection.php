<?php
	require_once __DIR__ . '/config.php';

	// Crear conexion a la BBDD
	$sqlconnection = new mysqli($servername, $username, $password, $dbname);
	$connection = mysqli_connect($servername, $username, $password, $dbname);

	if ($sqlconnection->connect_error) {
    	die("Conexion fallida: " . $sqlconnection->connect_error);
	}

	// Garantizar uso de UTF-8 en la conexión para evitar problemas de acentos
	if (! $sqlconnection->set_charset('utf8mb4')) {
	    // fallback: intentar establecer utf8
	    $sqlconnection->set_charset('utf8');
	}
	mysqli_set_charset($connection, 'utf8mb4');
?>