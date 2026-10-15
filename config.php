<?php
$host = "localhost";
$user =  "usreqxApp";
$password = "Fhj09y6mqDF#z7v";
$database = "equinox_gold";

$pin_maestro = getenv('ATS_MASTER_PIN') ?: '281432';
$configuracionLocal = __DIR__ . '/config.local.php';
if (is_file($configuracionLocal)) {
    require $configuracionLocal;
}
?>
