<?php
// 1. CONFIGURACIÓN DE CONEXIÓN A LA BASE DE DATOS
$host = "localhost";
$user = "root";       
$password = "Tecnologias11-11";       
$database = "equinox_gold";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Error crítico de conexión: " . $e->getMessage());
}

// Variables de control de estado del Modal (Formulario)
$gerencia_seleccionada = isset($_POST['gerencia_id']) ? intval($_POST['gerencia_id']) : 0;
$areas_filtradas = [];
$abrir_modal = false;

// Variables de control para los Filtros de la Tabla Principal
$filtro_gerencia = isset($_GET['filtro_gerencia']) ? intval($_GET['filtro_gerencia']) : 0;
$filtro_area = isset($_GET['filtro_area']) ? intval($_GET['filtro_area']) : 0;
$areas_filtro_tabla = [];

// Cargar áreas para el filtro de la tabla si hay una gerencia seleccionada en el buscador
if ($filtro_gerencia > 0) {
    $stmtFiltroAreas = $pdo->prepare("SELECT * FROM areas WHERE gerencia_id = ? ORDER BY nombre ASC");
    $stmtFiltroAreas->execute([$filtro_gerencia]);
    $areas_filtro_tabla = $stmtFiltroAreas->fetchAll();
}

// 2. CAMBIO DE GERENCIA EN MODAL: Cargar las áreas correspondientes mediante PHP
if ($gerencia_seleccionada > 0 && !isset($_GET['action'])) {
    $stmtAreas = $pdo->prepare("SELECT * FROM areas WHERE gerencia_id = ? ORDER BY nombre ASC");
    $stmtAreas->execute([$gerencia_seleccionada]);
    $areas_filtradas = $stmtAreas->fetchAll();
    $abrir_modal = true; 
}

// 3. GUARDAR EL REGISTRO (MÉTODO POST TRADICIONAL)
if (isset($_GET['action']) && $_GET['action'] === 'guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $gerencia_id = $_POST['gerencia_id'] ?? 0;
    $area_id = $_POST['area_id'] ?? 0;
    $ubicacion = $_POST['ubicacion'] ?? '';
    $nivel_riesgo = $_POST['nivel_riesgo'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $tipo_ats = $_POST['tipo_ats'] ?? '';
    $actividad = $_POST['actividad'] ?? '';

    if ($gerencia_id && $area_id && $ubicacion && $nivel_riesgo && $fecha && $tipo_ats && $actividad) {
        $sql = "INSERT INTO tareas (gerencia_id, area_id, ubicacion, nivel_riesgo, fecha, tipo_ats, actividad) 
                VALUES (:gerencia_id, :area_id, :ubicacion, :nivel_riesgo, :fecha, :tipo_ats, :actividad)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':gerencia_id'  => $gerencia_id,
            ':area_id'      => $area_id,
            ':ubicacion'    => $ubicacion,
            ':nivel_riesgo' => $nivel_riesgo,
            ':fecha'        => $fecha,
            ':tipo_ats'     => $tipo_ats,
            ':actividad'    => $actividad
        ]);
        
        echo "<script>alert('Registro de ATS guardado exitosamente.'); window.location.href='index.php';</script>";
        exit;
    }
}

// 4. DATOS MAESTROS PARA LOS SELECTS
$gerencias = $pdo->query("SELECT * FROM gerencias ORDER BY nombre ASC")->fetchAll();

// 5. CONSTRUCCIÓN DE LA CONSULTA CON FILTROS DINÁMICOS
$sqlTareas = "SELECT t.id, g.nombre AS gerencia, a.nombre AS area, t.actividad, t.nivel_riesgo, t.ubicacion, t.fecha, t.tipo_ats 
              FROM tareas t
              INNER JOIN gerencias g ON t.gerencia_id = g.id
              INNER JOIN areas a ON t.area_id = a.id";

$conditions = [];
$params = [];

if ($filtro_gerencia > 0) {
    $conditions[] = "t.gerencia_id = ?";
    $params[] = $filtro_gerencia;
}
if ($filtro_area > 0) {
    $conditions[] = "t.area_id = ?";
    $params[] = $filtro_area;
}

if (!empty($conditions)) {
    $sqlTareas .= " WHERE " . implode(" AND ", $conditions);
}

$sqlTareas .= " ORDER BY t.id DESC";
$stmtTareas = $pdo->prepare($sqlTareas);
$stmtTareas->execute($params);
$tareas = $stmtTareas->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Tareas y ATS - Equinox Gold</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --eqx-gold: #DCA11D; --eqx-dark: #1A1A1A; --eqx-light-dark: #2D2D2D; }
        
        /* Aumento del tamaño general de la fuente */
        body { background-color: #f8f9fa; font-size: 1.05rem; }
        
        .navbar-eqx { background-color: var(--eqx-dark); border-bottom: 4px solid var(--eqx-gold); }
        .navbar-brand { font-size: 1.4rem; }
        
        .btn-gold { background-color: var(--eqx-gold); color: #000; font-weight: 600; font-size: 1.1rem; }
        .btn-gold:hover { background-color: #c48f17; color: #000; }
        
        .card-header-eqx { background-color: var(--eqx-light-dark); color: #fff; border-left: 5px solid var(--eqx-gold); }
        .card-header-eqx h5 { font-size: 1.25rem; }
        
        /* Formato ampliado para la tabla */
        .table th { font-size: 1.1rem; padding: 12px 8px; }
        .table td { font-size: 1.05rem; padding: 12px 8px; }
        
        .badge { font-size: 0.95rem; padding: 6px 10px; }
        .badge-bajo { background-color: #198754; color: #fff; }
        .badge-medio { background-color: #ffc107; color: #000; }
        .badge-alto { background-color: #fd7e14; color: #fff; }
        .badge-critico { background-color: #dc3545; color: #fff; }
        
        .form-label { font-size: 1.1rem; }
        .form-select, .form-control { font-size: 1.05rem; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-eqx mb-4">
    <div class="container-fluid">
        <a class="navbar-brand shadow-sm" href="#">
            <i class="fa-solid fa-helmet-safety text-warning me-2"></i> 
            <strong>EQUINOX GOLD</strong> | Panel de Tareas y ATS
        </a>
    </div>
</nav>

<div class="container-fluid px-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-secondary mb-0"><i class="fa-solid fa-list-check me-2"></i>Monitoreo de Actividades</h3>
        <button class="btn btn-gold shadow-sm px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalAgregarTarea">
            <i class="fa-solid fa-plus me-1"></i> Agregar Registro
        </button>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body bg-light border-start border-secondary border-3">
            <form method="GET" action="index.php" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="filtro_gerencia" class="form-label"><strong>Filtrar por Gerencia:</strong></label>
                    <select class="form-select" id="filtro_gerencia" name="filtro_gerencia" onchange="this.form.submit();">
                        <option value="0">--- Todas las Gerencias ---</option>
                        <?php foreach ($gerencias as $g): ?>
                            <option value="<?php echo $g['id']; ?>" <?php echo ($g['id'] == $filtro_gerencia) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($g['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filtro_area" class="form-label"><strong>Filtrar por Área:</strong></label>
                    <select class="form-select" id="filtro_area" name="filtro_area" onchange="this.form.submit();" <?php echo empty($areas_filtro_tabla) ? 'disabled' : ''; ?>>
                        <option value="0">--- Todas las Áreas ---</option>
                        <?php foreach ($areas_filtro_tabla as $a): ?>
                            <option value="<?php echo $a['id']; ?>" <?php echo ($a['id'] == $filtro_area) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($a['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <?php if ($filtro_gerencia > 0 || $filtro_area > 0): ?>
                        <a href="index.php" class="btn btn-secondary w-100 py-2"><i class="fa-solid fa-filter-circle-xmark me-1"></i> Limpiar Filtros</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header card-header-eqx py-3">
            <h5 class="mb-0"><i class="fa-solid fa-table me-2"></i>Tareas en Sitio</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID Trabajo</th>
                            <th>Gerencia</th>
                            <th>Área</th>
                            <th>Actividad</th>
                            <th>Nivel de Riesgo</th>
                            <th>Ubicación</th>
                            <th>Fecha</th>
                            <th>Tipo de ATS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($tareas) > 0): ?>
                            <?php foreach ($tareas as $tarea): 
                                $badgeClass = 'badge-bajo';
                                if (strtolower($tarea['nivel_riesgo']) === 'medio') $badgeClass = 'badge-medio';
                                if (strtolower($tarea['nivel_riesgo']) === 'alto') $badgeClass = 'badge-alto';
                                if (strtolower($tarea['nivel_riesgo']) === 'crítico' || strtolower($tarea['nivel_riesgo']) === 'critico') $badgeClass = 'badge-critico';
                            ?>
                                <tr>
                                    <td><strong>#<?php echo $tarea['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($tarea['gerencia']); ?></td>
                                    <td><?php echo htmlspecialchars($tarea['area']); ?></td>
                                    <td><?php echo htmlspecialchars($tarea['actividad']); ?></td>
                                    <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $tarea['nivel_riesgo']; ?></span></td>
                                    <td><?php echo htmlspecialchars($tarea['ubicacion']); ?></td>
                                    <td><?php echo $tarea['fecha']; ?></td>
                                    <td><?php echo htmlspecialchars($tarea['tipo_ats']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center text-muted p-4">No se encontraron registros con los criterios seleccionados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAgregarTarea" tabindex="-1" aria-labelledby="modalAgregarTareaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: var(--eqx-dark); border-bottom: 3px solid var(--eqx-gold);">
                <h5 class="modal-title" id="modalAgregarTareaLabel"><i class="fa-solid fa-file-medical me-2"></i>Nuevo Registro de Tarea / ATS</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="window.location.href='index.php'"></button>
            </div>
            
            <form action="index.php?action=guardar" method="POST">
                <div class="modal-body row g-3">
                    
                    <div class="col-md-6">
                        <label for="gerencia_id" class="form-label"><strong>Gerencia</strong></label>
                        <select class="form-select" id="gerencia_id" name="gerencia_id" required onchange="this.form.action='index.php'; this.form.submit();">
                            <option value="" selected disabled>Seleccione Gerencia...</option>
                            <?php foreach ($gerencias as $g): ?>
                                <option value="<?php echo $g['id']; ?>" <?php echo ($g['id'] == $gerencia_seleccionada) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($g['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="area_id" class="form-label"><strong>Área</strong></label>
                        <select class="form-select" id="area_id" name="area_id" required <?php echo empty($areas_filtradas) ? 'disabled' : ''; ?>>
                            <?php if (empty($areas_filtradas)): ?>
                                <option value="" selected disabled>Seleccione primero una gerencia...</option>
                            <?php else: ?>
                                <option value="" selected disabled>Seleccione Área...</option>
                                <?php foreach ($areas_filtradas as $a): ?>
                                    <option value="<?php echo $a['id']; ?>"><?php echo htmlspecialchars($a['nombre']); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="ubicacion" class="form-label"><strong>Ubicación Específica</strong></label>
                        <input type="text" class="form-control" id="ubicacion" name="ubicacion" value="<?php echo htmlspecialchars($_POST['ubicacion'] ?? ''); ?>" placeholder="Ej: Banco 240, Stockpile" required>
                    </div>

                    <div class="col-md-6">
                        <label for="nivel_riesgo" class="form-label"><strong>Nivel de Riesgo</strong></label>
                        <select class="form-select" id="nivel_riesgo" name="nivel_riesgo" required>
                            <option value="" selected disabled>Seleccione una opción...</option>
                            <option value="Bajo" <?php echo (isset($_POST['nivel_riesgo']) && $_POST['nivel_riesgo'] == 'Bajo') ? 'selected' : ''; ?>>Bajo</option>
                            <option value="Medio" <?php echo (isset($_POST['nivel_riesgo']) && $_POST['nivel_riesgo'] == 'Medio') ? 'selected' : ''; ?>>Medio</option>
                            <option value="Alto" <?php echo (isset($_POST['nivel_riesgo']) && $_POST['nivel_riesgo'] == 'Alto') ? 'selected' : ''; ?>>Alto</option>
                            <option value="Crítico" <?php echo (isset($_POST['nivel_riesgo']) && $_POST['nivel_riesgo'] == 'Crítico') ? 'selected' : ''; ?>>Crítico</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="fecha" class="form-label"><strong>Fecha</strong></label>
                        <input type="date" class="form-control" id="fecha" name="fecha" value="<?php echo htmlspecialchars($_POST['fecha'] ?? date('Y-m-d')); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="tipo_ats" class="form-label"><strong>Tipo de ATS</strong></label>
                        <input type="text" class="form-control" id="tipo_ats" name="tipo_ats" value="<?php echo htmlspecialchars($_POST['tipo_ats'] ?? ''); ?>" placeholder="Ej: ATS-01 Trabajo en Caliente" required>
                    </div>

                    <div class="col-md-12">
                        <label for="actividad" class="form-label"><strong>Actividad (Descripción del Trabajo)</strong></label>
                        <textarea class="form-control" id="actividad" name="actividad" rows="3" placeholder="Describa la tarea..." required><?php echo htmlspecialchars($_POST['actividad'] ?? ''); ?></textarea>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary text-lg" data-bs-dismiss="modal" onclick="window.location.href='index.php'">Cancelar</button>
                    <button type="submit" class="btn btn-gold"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar Registro</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php if ($abrir_modal): ?>
<script>
    const modal = new bootstrap.Modal(document.getElementById('modalAgregarTarea'));
    modal.show();
</script>
<?php endif; ?>

</body>
</html>