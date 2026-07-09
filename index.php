<?php
// 1. CONFIGURACIÓN DE CONEXIÓN A LA BASE DE DATOS
include 'config.php';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Error crítico de conexión: " . $e->getMessage());
}

// Variables de control para los Filtros de la Tabla Principal
$filtro_gerencia = isset($_GET['filtro_gerencia']) ? intval($_GET['filtro_gerencia']) : 0;
$filtro_area = isset($_GET['filtro_area']) ? intval($_GET['filtro_area']) : 0;
$areas_filtro_tabla = [];

if ($filtro_gerencia > 0) {
    $stmtFiltroAreas = $pdo->prepare("SELECT * FROM areas WHERE gerencia_id = ? ORDER BY nombre ASC");
    $stmtFiltroAreas->execute([$filtro_gerencia]);
    $areas_filtro_tabla = $stmtFiltroAreas->fetchAll();
}

$stmtInitCol = $pdo->query("SHOW COLUMNS FROM `tareas` LIKE 'nivel_riesgo_inicial'");
$has_nivel_inicial = ($stmtInitCol && $stmtInitCol->fetch() !== false);
$stmtFinalCol = $pdo->query("SHOW COLUMNS FROM `tareas` LIKE 'nivel_riesgo_final'");
$has_nivel_final = ($stmtFinalCol && $stmtFinalCol->fetch() !== false);

// 3. GUARDAR EL REGISTRO
if (isset($_GET['action']) && $_GET['action'] === 'guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $gerencia_id = $_POST['gerencia_id'] ?? 0;
    $area_id = $_POST['area_id'] ?? 0;
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $nivel_riesgo_inicial = trim($_POST['nivel_riesgo_inicial'] ?? '');
    $nivel_riesgo_final = trim($_POST['nivel_riesgo_final'] ?? '');
    $fecha = $_POST['fecha'] ?? '';
    $actividad = trim($_POST['actividad'] ?? '');
    
    $permisos_requeridos = $_POST['permisos_requeridos'] ?? [];
    if (!is_array($permisos_requeridos)) {
        $permisos_requeridos = [$permisos_requeridos];
    }
    $permisos_requeridos_json = json_encode(array_values($permisos_requeridos), JSON_UNESCAPED_UNICODE);

    if ($gerencia_id && $area_id && $ubicacion && $nivel_riesgo_inicial && $nivel_riesgo_final && $fecha && $actividad) {
        if ($has_nivel_inicial && $has_nivel_final) {
            $sql = "INSERT INTO tareas (gerencia_id, area_id, ubicacion, nivel_riesgo_inicial, nivel_riesgo_final, nivel_riesgo, fecha, permisos_requeridos, actividad)
                    VALUES (:gerencia_id, :area_id, :ubicacion, :nivel_riesgo_inicial, :nivel_riesgo_final, :nivel_riesgo, :fecha, :permisos_requeridos, :actividad)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':gerencia_id' => $gerencia_id,
                ':area_id' => $area_id,
                ':ubicacion' => $ubicacion,
                ':nivel_riesgo_inicial' => $nivel_riesgo_inicial,
                ':nivel_riesgo_final' => $nivel_riesgo_final,
                ':nivel_riesgo' => '', 
                ':fecha' => $fecha,
                ':permisos_requeridos' => $permisos_requeridos_json,
                ':actividad' => $actividad
            ]);
        } else {
            $nivel_riesgo = $nivel_riesgo_inicial . ' / ' . $nivel_riesgo_final;
            $sql = "INSERT INTO tareas (gerencia_id, area_id, ubicacion, nivel_riesgo, fecha, permisos_requeridos, actividad) 
                    VALUES (:gerencia_id, :area_id, :ubicacion, :nivel_riesgo, :fecha, :permisos_requeridos, :actividad)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':gerencia_id'  => $gerencia_id,
                ':area_id'      => $area_id,
                ':ubicacion'    => $ubicacion,
                ':nivel_riesgo' => $nivel_riesgo,
                ':fecha'        => $fecha,
                ':permisos_requeridos' => $permisos_requeridos_json,
                ':actividad'    => $actividad
            ]);
        }

        echo "<script>alert('Registro de ATS guardado exitosamente.'); window.location.href='index.php';</script>";
        exit;
    }
}

$gerencias = $pdo->query("SELECT * FROM gerencias ORDER BY nombre ASC")->fetchAll();

// ==========================================
// CONFIGURACIÓN DE PAGINACIÓN LOGIC (CORREGIDO)
// ==========================================
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Construcción de condiciones comunes para conteo y consulta
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

$whereSQL = !empty($conditions) ? " WHERE " . implode(" AND ", $conditions) : "";

// 1. Contar el total de registros bajo los filtros actuales
$sqlCount = "SELECT COUNT(*) FROM tareas t" . $whereSQL;
$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute($params);
$total_registros = $stmtCount->fetchColumn();
$total_paginas = ceil($total_registros / $registros_por_pagina);

// 2. Obtener las columnas y registros limitados para la página actual
$selectCols = ['t.id', 'g.nombre AS gerencia', 'a.nombre AS area', 't.actividad', 't.ubicacion', 't.fecha', 't.permisos_requeridos', 't.nivel_riesgo'];
if ($has_nivel_inicial && $has_nivel_final) {
    $selectCols[] = 't.nivel_riesgo_inicial';
    $selectCols[] = 't.nivel_riesgo_final';
}

$sqlTareas = "SELECT " . implode(', ', $selectCols) . " FROM tareas t
              INNER JOIN gerencias g ON t.gerencia_id = g.id
              INNER JOIN areas a ON t.area_id = a.id" 
              . $whereSQL . 
              " ORDER BY t.id DESC LIMIT ? OFFSET ?";

$stmtTareas = $pdo->prepare($sqlTareas);

// Vinculamos primero los parámetros de los filtros dinámicos (Gerencia / Área)
$idx = 1;
foreach ($params as $param) {
    $stmtTareas->bindValue($idx++, $param);
}

// Vinculamos estrictamente como enteros LIMIT y OFFSET para evitar que se ignoren
$stmtTareas->bindValue($idx++, $registros_por_pagina, PDO::PARAM_INT);
$stmtTareas->bindValue($idx, $offset, PDO::PARAM_INT);

$stmtTareas->execute();
$tareas = $stmtTareas->fetchAll();

// Función auxiliar para mantener parámetros de filtros al cambiar de página
function buildQueryString($page, $f_gerencia, $f_area) {
    $query = ['pagina' => $page];
    if ($f_gerencia > 0) $query['filtro_gerencia'] = $f_gerencia;
    if ($f_area > 0) $query['filtro_area'] = $f_area;
    return 'index.php?' . http_build_query($query);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ATS - Equinox Gold</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --eqx-primary: #24415D;
            --eqx-accent: #DCA11D;
            --eqx-dark: #1A1A1A;
            --eqx-light-dark: #2D2D2D;
        }
        body { background: linear-gradient(135deg, #f5f7fb 0%, #eef3f8 100%); font-size: 1.05rem; }
        .navbar-eqx { background: linear-gradient(90deg, var(--eqx-primary) 0%, #2f5873 100%); border-bottom: 4px solid var(--eqx-accent); }
        .navbar-brand { font-size: 1.35rem; }
        .navbar-logo { height: 38px; width: auto; }
        .btn-gold { background-color: var(--eqx-accent); color: #000; font-weight: 600; font-size: 1.05rem; border: none; }
        .btn-gold:hover { background-color: #c48f17; color: #000; }
        .card-header-eqx { background: linear-gradient(90deg, var(--eqx-primary) 0%, #2f5873 100%); color: #fff; border-left: 5px solid var(--eqx-accent); }
        .card-header-eqx h5 { font-size: 1.25rem; }
        .table th, .table td { font-size: 1.05rem; padding: 12px 8px; }
        .badge { font-size: 0.95rem; padding: 6px 10px; }
        .badge-bajo { background-color: #198754; color: #fff; }
        .badge-medio { background-color: #ffc107; color: #000; }
        .badge-alto { background-color: #fd7e14; color: #fff; }
        .badge-critico { background-color: #dc3545; color: #fff; }
        
        /* Ajustes de paginación */
        .pagination .page-link { color: var(--eqx-primary); }
        .pagination .page-item.active .page-link { background-color: var(--eqx-primary); border-color: var(--eqx-primary); color: #fff; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-eqx mb-4">
    <div class="container-fluid">
        <a class="navbar-brand shadow-sm d-flex align-items-center" href="#">
            <img src="image/eqx.jpg" alt="Equinox Gold" class="navbar-logo me-2">
            <span><strong>EQUINOX GOLD</strong> | Panel de ATS</span>
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

    <div class="card shadow-sm mb-4">
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
                            <th>Riesgo Inicial</th>
                            <th>Riesgo Final</th>
                            <th>Ubicación</th>
                            <th>Fecha</th>
                            <th>Permisos Requeridos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($tareas) > 0): ?>
                            <?php 
                            // Función auxiliar para modularizar y reutilizar las clases de color de los niveles de riesgo
                            $getBadgeClass = function($riesgo) {
                                $lower = mb_strtolower($riesgo, 'UTF-8');
                                if (strpos($lower, 'extremo') !== false) return 'badge-critico';
                                if (strpos($lower, 'alto') !== false) return 'badge-alto';
                                if (strpos($lower, 'moderado') !== false || strpos($lower, 'medio') !== false) return 'badge-medio';
                                if (strpos($lower, 'bajo') !== false) return 'badge-bajo';
                                return 'bg-secondary text-white';
                            };

                            foreach ($tareas as $tarea): 
                                $riesgoInicial = trim($tarea['nivel_riesgo_inicial'] ?? '');
                                $riesgoFinal = trim($tarea['nivel_riesgo_final'] ?? '');

                                // Si la base no cuenta con los campos independientes, procesamos el campo mixto 'nivel_riesgo'
                                if ($riesgoInicial === '' && $riesgoFinal === '') {
                                    $partes = explode('/', $tarea['nivel_riesgo'] ?? '');
                                    $riesgoInicial = trim($partes[0] ?? '');
                                    $riesgoFinal = trim($partes[1] ?? '');
                                }
                            ?>
                                <tr>
                                    <td><strong>#<?php echo $tarea['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($tarea['gerencia']); ?></td>
                                    <td><?php echo htmlspecialchars($tarea['area']); ?></td>
                                    <td><?php echo htmlspecialchars($tarea['actividad']); ?></td>
                                    
                                    <td>
                                        <?php if ($riesgoInicial !== ''): ?>
                                            <span class="badge <?php echo $getBadgeClass($riesgoInicial); ?>"><?php echo htmlspecialchars($riesgoInicial); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($riesgoFinal !== ''): ?>
                                            <span class="badge <?php echo $getBadgeClass($riesgoFinal); ?>"><?php echo htmlspecialchars($riesgoFinal); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td><?php echo htmlspecialchars($tarea['ubicacion']); ?></td>
                                    <td><?php echo $tarea['fecha']; ?></td>
                                    <td>
                                        <?php
                                        $permisos = [];
                                        if (!empty($tarea['permisos_requeridos'])) {
                                            $decoded = json_decode($tarea['permisos_requeridos'], true);
                                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                $permisos = $decoded;
                                            } else {
                                                $permisos = array_filter(array_map('trim', explode(',', $tarea['permisos_requeridos'])));
                                            }
                                        }
                                        if (count($permisos) > 0) {
                                            foreach ($permisos as $perm) {
                                                $bgStyle = ($perm === 'No se requiere permiso') ? 'bg-light text-dark border border-secondary-subtle' : 'bg-secondary';
                                                echo '<span class="badge ' . $bgStyle . ' me-1 mb-1">' . htmlspecialchars($perm) . '</span>';
                                            }
                                        } else {
                                            echo '<span class="text-muted small">Sin permisos</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center text-muted p-4">No se encontraron registros con los criterios seleccionados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php if ($total_paginas > 1): ?>
            <div class="card-footer d-flex justify-content-between align-items-center bg-white py-3 border-top">
                <div class="text-muted small">
                    Mostrando página <strong><?php echo $pagina_actual; ?></strong> de <strong><?php echo $total_paginas; ?></strong> (Total de <?php echo $total_registros; ?> registros)
                </div>
                <nav aria-label="Navegación de tareas">
                    <ul class="pagination pagination-sm mb-0 shadow-sm">
                        <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo buildQueryString($pagina_actual - 1, $filtro_gerencia, $filtro_area); ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo; Ant</span>
                            </a>
                        </li>

                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <li class="page-item <?php echo ($pagina_actual === $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo buildQueryString($i, $filtro_gerencia, $filtro_area); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo buildQueryString($pagina_actual + 1, $filtro_gerencia, $filtro_area); ?>" aria-label="Next">
                                <span aria-hidden="true">Sig &raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalAgregarTarea" tabindex="-1" aria-labelledby="modalAgregarTareaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: var(--eqx-primary); border-bottom: 3px solid var(--eqx-accent);">
                <h5 class="modal-title" id="modalAgregarTareaLabel"><i class="fa-solid fa-file-medical me-2"></i>Nuevo Registro de Tarea / ATS</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form action="index.php?action=guardar" method="POST">
                <div class="modal-body row g-3">
                    <div class="col-md-6">
                        <label for="gerencia_id" class="form-label"><strong>Gerencia</strong></label>
                        <select class="form-select" id="gerencia_id" name="gerencia_id" required>
                            <option value="" selected disabled>Seleccione Gerencia...</option>
                            <?php foreach ($gerencias as $g): ?>
                                <option value="<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="area_id" class="form-label"><strong>Área</strong></label>
                        <select class="form-select" id="area_id" name="area_id" required disabled>
                            <option value="" selected disabled>Seleccione primero una gerencia...</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="nivel_riesgo_inicial" class="form-label"><strong>Nivel de Riesgo Inicial</strong></label>
                        <select class="form-select" id="nivel_riesgo_inicial" name="nivel_riesgo_inicial" required>
                            <option value="" selected disabled>Seleccione una opción...</option>
                            <option value="Bajo">Bajo 1-5</option>
                            <option value="Moderado">Moderado 6-10</option>
                            <option value="Alto">Alto 11-17</option>
                            <option value="Extremo">Extremo 18-25</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="nivel_riesgo_final" class="form-label"><strong>Nivel de Riesgo Final</strong></label>
                        <select class="form-select" id="nivel_riesgo_final" name="nivel_riesgo_final" required>
                            <option value="" selected disabled>Seleccione una opción...</option>
                            <option value="Bajo">Bajo 1-5</option>
                            <option value="Moderado">Moderado 6-10</option>
                            <option value="Alto">Alto 11-17</option>
                            <option value="Extremo">Extremo 18-25</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="ubicacion" class="form-label"><strong>Ubicación Específica</strong></label>
                        <input type="text" class="form-control" id="ubicacion" name="ubicacion" placeholder="Ej: Banco 240, Veta nueva" required>
                    </div>

                    <div class="col-md-6">
                        <label for="fecha" class="form-label"><strong>Fecha</strong></label>
                        <input type="date" class="form-control" id="fecha" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label"><strong>Permisos Requeridos</strong></label>
                        <div class="p-3 rounded border bg-white">
                            <?php
                                $opcionesPermisos = [
                                    'No se requiere permiso',
                                    'Espacio Confinado',
                                    'Trabajo en Caliente',
                                    'Trabajo en Altura',
                                    'Excavación',
                                    'Aislamiento en grupo',
                                    'Izaje de carga'
                                ];
                                foreach ($opcionesPermisos as $permiso) {
                            ?>
                                <div class="form-check form-check-inline me-3 mb-2">
                                    <input class="form-check-input permiso-checkbox" type="checkbox" name="permisos_requeridos[]" id="permiso_<?php echo md5($permiso); ?>" value="<?php echo htmlspecialchars($permiso); ?>">
                                    <label class="form-check-label" for="permiso_<?php echo md5($permiso); ?>"><?php echo htmlspecialchars($permiso); ?></label>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label for="actividad" class="form-label"><strong>Actividad (Descripción del Trabajo)</strong></label>
                        <textarea class="form-control" id="actividad" name="actividad" rows="3" placeholder="Describa la tarea..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-gold"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar Registro</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('gerencia_id').addEventListener('change', function() {
        const gerenciaId = this.value;
        const selectArea = document.getElementById('area_id');
        
        selectArea.innerHTML = '<option value="" selected disabled>Cargando áreas...</option>';
        selectArea.disabled = true;

        if(gerenciaId > 0) {
            fetch(`procesar.php?action=get_areas_por_gerencia&gerencia_id=${gerenciaId}`)
                .then(response => response.json())
                .then(res => {
                    if(res.status === 'success') {
                        selectArea.innerHTML = '<option value="" selected disabled>Seleccione Área...</option>';
                        res.data.forEach(area => {
                            selectArea.innerHTML += `<option value="${area.id}">${area.nombre}</option>`;
                        });
                        selectArea.disabled = false;
                    } else {
                        selectArea.innerHTML = '<option value="" selected disabled>Error al cargar áreas</option>';
                    }
                })
                .catch(() => {
                    selectArea.innerHTML = '<option value="" selected disabled>Error de red</option>';
                });
        }
    });

    const permisoCheckboxes = document.querySelectorAll('.permiso-checkbox');
    const hashNoPermiso = "<?php echo md5('No se requiere permiso'); ?>";
    
    permisoCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            const noneCheckbox = document.querySelector(`#permiso_${hashNoPermiso}`);
            if (checkbox.value === 'No se requiere permiso' && checkbox.checked) {
                permisoCheckboxes.forEach(cb => {
                    if (cb !== checkbox) cb.checked = false;
                });
            } else if (checkbox.value !== 'No se requiere permiso' && checkbox.checked) {
                if (noneCheckbox) noneCheckbox.checked = false;
            }
        });
    });
</script>
</body>
</html>