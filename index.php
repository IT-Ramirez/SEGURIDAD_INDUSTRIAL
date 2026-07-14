<?php
// 1. CONFIGURACIÓN E INCLUSIONES
include 'config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// 2. VERIFICACIÓN DE ESTRUCTURA DE TABLA
$stmtInitCol = $pdo->query("SHOW COLUMNS FROM `tareas` LIKE 'nivel_riesgo_inicial'");
$has_nivel_inicial = ($stmtInitCol && $stmtInitCol->fetch() !== false);
$stmtFinalCol = $pdo->query("SHOW COLUMNS FROM `tareas` LIKE 'nivel_riesgo_final'");
$has_nivel_final = ($stmtFinalCol && $stmtFinalCol->fetch() !== false);

// 3. OBTENCIÓN DE DATOS PARA LA TABLA
$pagina = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limite = 10;
$offset = ($pagina - 1) * $limite;

$cols = ['t.id', 'g.nombre AS gerencia', 'a.nombre AS area', 't.actividad', 't.ubicacion', 't.fecha', 't.permisos_requeridos', 't.estado'];
if ($has_nivel_inicial && $has_nivel_final) {
    $cols[] = 't.nivel_riesgo_inicial';
    $cols[] = 't.nivel_riesgo_final';
} else {
    $cols[] = 't.nivel_riesgo';
}

$sql = "SELECT " . implode(', ', $cols) . " FROM tareas t
        INNER JOIN gerencias g ON t.gerencia_id = g.id
        INNER JOIN areas a ON t.area_id = a.id
        ORDER BY t.id DESC LIMIT $limite OFFSET $offset";

$tareas = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Tareas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container-fluid py-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Listado de Tareas</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Gerencia</th>
                        <th>Área</th>
                        <th>Actividad</th>
                        <th>Riesgo Inicial</th>
                        <th>Riesgo Final</th>
                        <th>Ubicación</th>
                        <th>Fecha</th>
                        <th>Permisos</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tareas as $tarea): 
                        $estado_actual = $tarea['estado'] ?? 'En desarrollo';
                        $select_color_class = ($estado_actual === 'finalizado') ? 'bg-success text-white' : 'bg-warning text-dark';
                    ?>
                    <tr>
                        <td>#<?php echo $tarea['id']; ?></td>
                        <td><?php echo htmlspecialchars($tarea['gerencia']); ?></td>
                        <td><?php echo htmlspecialchars($tarea['area']); ?></td>
                        <td><?php echo htmlspecialchars($tarea['actividad']); ?></td>
                        <td><?php echo htmlspecialchars($tarea['nivel_riesgo_inicial'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($tarea['nivel_riesgo_final'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($tarea['ubicacion']); ?></td>
                        <td><?php echo $tarea['fecha']; ?></td>
                        <td><?php echo htmlspecialchars($tarea['permisos_requeridos']); ?></td>
                        <td>
                            <select class="form-select form-select-sm estado-selector <?php echo $select_color_class; ?>" 
                                    data-id="<?php echo $tarea['id']; ?>" style="width: 140px; cursor: pointer; font-weight: 600;">
                                <option value="En desarrollo" <?php echo ($estado_actual === 'En desarrollo') ? 'selected' : ''; ?>>En desarrollo</option>
                                <option value="finalizado" <?php echo ($estado_actual === 'finalizado') ? 'selected' : ''; ?>>Finalizado</option>
                            </select>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.estado-selector').forEach(select => {
    select.addEventListener('change', function() {
        const tareaId = this.getAttribute('data-id');
        const nuevoEstado = this.value;
        const selector = this;

        const formData = new FormData();
        formData.append('id', tareaId);
        formData.append('estado', nuevoEstado);

        fetch('procesar.php?action=actualizar_estado', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                selector.className = 'form-select form-select-sm estado-selector ' + 
                    (nuevoEstado === 'finalizado' ? 'bg-success text-white' : 'bg-warning text-dark');
            } else {
                alert('Error: ' + data.message);
            }
        });
    });
});
</script>
</body>
</html>