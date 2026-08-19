<?php
include_once('../session_check.php');
checkRole(['admin']);
require_once('../config.php');
require_once __DIR__ . '/admin_scope.php';

$dsn = "mysql:host=$servername;dbname=$dbname;charset=utf8mb4";
$pdo = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
]);
$isGlobalAdmin = isGlobalAdmin();
$areaId = $isGlobalAdmin ? null : getAdminAreaId($pdo);
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';
$fechaValida = static function (string $fecha): bool {
    $date = DateTime::createFromFormat('!Y-m-d', $fecha);
    return $date !== false && $date->format('Y-m-d') === $fecha;
};
$where = $isGlobalAdmin ? [] : ['u.id_area = :area_id'];
$params = $isGlobalAdmin ? [] : [':area_id' => $areaId];
if ($fechaValida($desde)) {
    $where[] = 'i.fecha_registro >= :desde';
    $params[':desde'] = $desde . ' 00:00:00';
}
if ($fechaValida($hasta)) {
    $where[] = 'i.fecha_registro < DATE_ADD(:hasta, INTERVAL 1 DAY)';
    $params[':hasta'] = $hasta . ' 00:00:00';
}

$stmt = $pdo->prepare(
    "SELECT i.id, i.placa, COALESCE(v.codigo, '') AS codigo_vehiculo,
             i.nombre_conductor, COALESCE(a.nombre_area, '') AS area,
             COALESCE(u.clasificacion, '') AS clasificacion,
             i.odometro, i.hora, i.estado,
            i.fecha_registro, i.observaciones, d.parametro, d.resultado
     FROM inspecciones i
     LEFT JOIN vehiculos v ON v.id = i.codigo_vehiculo
         LEFT JOIN tbl_users u ON u.userID = i.userID
         LEFT JOIN tbl_area a ON a.id_area = u.id_area
     LEFT JOIN detalles_inspeccion d ON d.inspeccion_id = i.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY i.fecha_registro DESC, i.id DESC, d.id ASC"
);
$stmt->execute($params);

$inspecciones = [];
$parametros = [];

while ($row = $stmt->fetch()) {
    $id = (int)$row['id'];

    if (!isset($inspecciones[$id])) {
        $inspecciones[$id] = [
            'id' => $row['id'],
            'placa' => $row['placa'],
            'codigo_vehiculo' => $row['codigo_vehiculo'],
            'nombre_conductor' => $row['nombre_conductor'],
            'area' => $row['area'],
            'clasificacion' => $row['clasificacion'],
            'odometro' => $row['odometro'],
            'hora' => $row['hora'],
            'estado' => $row['estado'],
            'fecha_registro' => $row['fecha_registro'],
            'observaciones' => $row['observaciones'],
            'resultados' => []
        ];
    }

    if ($row['parametro'] !== null && $row['parametro'] !== '') {
        $parametros[$row['parametro']] = true;
        $inspecciones[$id]['resultados'][$row['parametro']] = $row['resultado'];
    }
}

$filename = 'inspecciones_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, [
    'ID inspección', 'Placa', 'Código vehículo', 'Conductor', 'Área', 'Clasificación', 'Odómetro',
    'Hora', 'Estado', 'Fecha de registro', 'Observaciones', ...array_keys($parametros)
], ';');

foreach ($inspecciones as $inspeccion) {
    $values = [
        $inspeccion['id'],
        $inspeccion['placa'],
        $inspeccion['codigo_vehiculo'],
        $inspeccion['nombre_conductor'],
        $inspeccion['area'],
        $inspeccion['clasificacion'],
        $inspeccion['odometro'],
        $inspeccion['hora'],
        $inspeccion['estado'],
        $inspeccion['fecha_registro'],
        $inspeccion['observaciones']
    ];

    foreach (array_keys($parametros) as $parametro) {
        $values[] = $inspeccion['resultados'][$parametro] ?? '';
    }

    foreach ($values as &$value) {
        if (is_string($value) && preg_match('/^[=+\-@]/', $value)) {
            $value = "'" . $value;
        }
    }
    unset($value);

    fputcsv($output, $values, ';');
}

fclose($output);
exit;