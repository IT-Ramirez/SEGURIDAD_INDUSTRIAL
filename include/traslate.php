<?php
// Capturar el contenido generado
$contenido = ob_get_clean();

// Array de traducciones

$traducciones = [
    'Panel de administración' => 'Dahni kum duki',
    'Vista General del Sistema' => 'Tanka yamka dahni',
    'Lista de Pedidos Actuales' => 'Nakiskisa kum piawan',
    'Número de Orden' => 'Nini takamna',
    'Menú' => 'Waî',
    'Cantidad' => 'Taksa',
    'Estado' => 'Italniska',
    'esperando' => 'baya',
    'preparando' => 'yaptaya',
    'cancelado' => 'salwaia',
    'Disponibilidad del Personal' => 'Nini wihta kum yamka',
    'Activo' => 'Awti',
    'Inactivo' => 'Ullisa',
    'Nombre de Ítem' => 'Yamni wina kum'
    
];


// Función para traducir el contenido
function traducir($contenido, $traducciones) {
    foreach ($traducciones as $palabra_ingles => $palabra_miskito) {
        // Reemplazar las palabras en inglés por miskito (case-insensitive)
        $contenido = preg_replace("/\b$palabra_ingles\b/i", $palabra_miskito, $contenido);
    }
    return $contenido;
}

// Traducir el contenido
$contenido_traducido = traducir($contenido, $traducciones);

// Mostrar el contenido traducido
echo $contenido_traducido;
?>