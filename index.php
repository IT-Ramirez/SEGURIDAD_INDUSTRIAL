<?php
include 'controller_ats.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="robots" content="noindex, nofollow">
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
        .badge-en-desarrollo { background-color: var(--eqx-accent); color: #000; }
        .badge-finalizado { background-color: #198754; color: #fff; }
        .estado-select { max-width: 160px; font-size: 0.95rem; }
        
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
                <div class="col-md-3">
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
                <div class="col-md-3">
                    <label for="filtro_estado" class="form-label"><strong>Filtrar por Estado:</strong></label>
                    <select class="form-select" id="filtro_estado" name="filtro_estado" onchange="this.form.submit();">
                        <option value="">--- Todos los Estados ---</option>
                        <option value="En desarrollo" <?php echo ($filtro_estado === 'En desarrollo') ? 'selected' : ''; ?>>En desarrollo</option>
                        <option value="Finalizado" <?php echo ($filtro_estado === 'Finalizado') ? 'selected' : ''; ?>>Finalizado</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <?php if ($filtro_gerencia > 0 || $filtro_area > 0 || $filtro_estado !== ''): ?>
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
                            <th>Ubicación</th>
                            <th>Responsable</th>
                            <th>Fecha</th>
                            <th>Riesgo Inicial</th>
                            <th>Riesgo Final</th>
                            <th>Permisos Requeridos</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($tareas) > 0): ?>
                            <?php 
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
                                    <td><?php echo htmlspecialchars($tarea['ubicacion']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($tarea['responsable'] ?? 'No asignado'); ?></strong></td>
                                    <td><?php echo $tarea['fecha']; ?></td>

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
                                    <td>
                                        <?php
                                        $estadoActual = trim($tarea['estado'] ?? '');
                                        if ($estadoActual === '') {
                                            $estadoActual = 'En desarrollo';
                                        }
                                        $estadoLabel = ($estadoActual === 'finalizado' || $estadoActual === 'Finalizado') ? 'Finalizado' : 'En desarrollo';
                                        $estadoClass = ($estadoLabel === 'Finalizado') ? 'badge-finalizado' : 'badge-en-desarrollo';
                                        ?>
                                        <div class="d-flex flex-column gap-2">
                                            <span class="badge <?php echo $estadoClass; ?> align-self-start"><?php echo htmlspecialchars($estadoLabel); ?></span>
                                            <select class="form-select form-select-sm estado-select" data-id="<?php echo (int)$tarea['id']; ?>">
                                                <option value="En desarrollo" <?php echo ($estadoLabel === 'En desarrollo') ? 'selected' : ''; ?>>En desarrollo</option>
                                                <option value="Finalizado" <?php echo ($estadoLabel === 'Finalizado') ? 'selected' : ''; ?>>Finalizado</option>
                                            </select>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="11" class="text-center text-muted p-4">No se encontraron registros con los criterios seleccionados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php if ($total_paginas > 1): ?>
            <div class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-center bg-white py-3 border-top gap-3">
                <div class="text-muted small">
                    Mostrando página <strong><?php echo $pagina_actual; ?></strong> de <strong><?php echo $total_paginas; ?></strong> (Total de <?php echo $total_registros; ?> registros)
                </div>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="d-flex align-items-center gap-2">
                        <label class="mb-0 text-muted">Ver</label>
                        <select id="pageSizeSelect" class="form-select form-select-sm" style="width: auto; min-width: 90px;">
                            <?php foreach ([10, 20, 50, 100] as $size): ?>
                                <option value="<?php echo $size; ?>" <?php echo ($registros_por_pagina === $size) ? 'selected' : ''; ?>><?php echo $size; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="text-muted">registros</span>
                    </div>
                    <nav aria-label="Navegación de tareas">
                        <ul class="pagination pagination-sm mb-0 shadow-sm">
                            <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo buildQueryString(max(1, $pagina_actual - 1), $filtro_gerencia, $filtro_area, $filtro_estado, $registros_por_pagina); ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo; Ant</span>
                                </a>
                            </li>

                            <?php foreach (buildPaginationSequence($pagina_actual, $total_paginas, 2) as $page): ?>
                                <?php if ($page === null): ?>
                                    <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                                <?php else: ?>
                                    <li class="page-item <?php echo ($pagina_actual === $page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo buildQueryString($page, $filtro_gerencia, $filtro_area, $filtro_estado, $registros_por_pagina); ?>"><?php echo $page; ?></a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo buildQueryString(min($total_paginas, $pagina_actual + 1), $filtro_gerencia, $filtro_area, $filtro_estado, $registros_por_pagina); ?>" aria-label="Next">
                                    <span aria-hidden="true">Sig &raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL AGREGAR TAREA -->
<div class="modal fade" id="modalAgregarTarea" tabindex="-1" aria-labelledby="modalAgregarTareaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: var(--eqx-primary); border-bottom: 3px solid var(--eqx-accent);">
                <h5 class="modal-title" id="modalAgregarTareaLabel"><i class="fa-solid fa-file-medical me-2"></i><?php echo $edit_mode ? 'Editar Registro de Tarea / ATS' : 'Nuevo Registro de Tarea / ATS'; ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="formAgregarTarea" action="<?php echo $edit_mode ? 'index.php?action=actualizar' : 'index.php?action=guardar'; ?>" method="POST" data-action="<?php echo $edit_mode ? 'actualizar' : 'guardar'; ?>" data-edit-mode="<?php echo $edit_mode ? '1' : '0'; ?>">
                <?php if ($edit_mode && $edit_tarea): ?>
                    <input type="hidden" name="id" value="<?php echo (int)$edit_tarea['id']; ?>">
                    <input type="hidden" name="edit_token" value="<?php echo htmlspecialchars($edit_tarea['edit_token'] ?? ''); ?>">
                <?php endif; ?>
                <div class="modal-body row g-3">
                    <div class="col-md-6">
                        <label for="gerencia_id" class="form-label"><strong>Gerencia</strong></label>
                        <select class="form-select" id="gerencia_id" name="gerencia_id" required <?php echo $edit_mode ? 'disabled' : ''; ?>>
                            <option value="" selected disabled>Seleccione Gerencia...</option>
                            <?php foreach ($gerencias as $g): ?>
                                <option value="<?php echo $g['id']; ?>" <?php echo ($edit_mode && $edit_tarea && (int)$edit_tarea['gerencia_id'] === (int)$g['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($g['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="area_id" class="form-label"><strong>Área</strong></label>
                        <select class="form-select" id="area_id" name="area_id" required data-selected-area-id="<?php echo $edit_mode && $edit_tarea ? (int)$edit_tarea['area_id'] : 0; ?>" <?php echo $edit_mode ? '' : 'disabled'; ?>>
                            <option value="" selected disabled>Seleccione primero una gerencia...</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="nivel_riesgo_inicial" class="form-label"><strong>Nivel de Riesgo Inicial</strong></label>
                        <select class="form-select" id="nivel_riesgo_inicial" name="nivel_riesgo_inicial" required>
                            <option value="" selected disabled>Seleccione una opción...</option>
                            <option value="Bajo" <?php echo ($edit_mode && $edit_tarea && ($edit_tarea['nivel_riesgo_inicial'] ?? '') === 'Bajo') ? 'selected' : ''; ?>>Bajo 1-5</option>
                            <option value="Moderado" <?php echo ($edit_mode && $edit_tarea && ($edit_tarea['nivel_riesgo_inicial'] ?? '') === 'Moderado') ? 'selected' : ''; ?>>Moderado 6-10</option>
                            <option value="Alto" <?php echo ($edit_mode && $edit_tarea && ($edit_tarea['nivel_riesgo_inicial'] ?? '') === 'Alto') ? 'selected' : ''; ?>>Alto 11-17</option>
                            <option value="Extremo" <?php echo ($edit_mode && $edit_tarea && ($edit_tarea['nivel_riesgo_inicial'] ?? '') === 'Extremo') ? 'selected' : ''; ?>>Extremo 18-25</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="nivel_riesgo_final" class="form-label"><strong>Nivel de Riesgo Final</strong></label>
                        <select class="form-select" id="nivel_riesgo_final" name="nivel_riesgo_final" required <?php echo $edit_mode ? 'disabled' : ''; ?>>
                            <option value="" selected disabled>Seleccione una opción...</option>
                            <option value="Bajo" <?php echo ($edit_mode && $edit_tarea && ($edit_tarea['nivel_riesgo_final'] ?? '') === 'Bajo') ? 'selected' : ''; ?>>Bajo 1-5</option>
                            <option value="Moderado" <?php echo ($edit_mode && $edit_tarea && ($edit_tarea['nivel_riesgo_final'] ?? '') === 'Moderado') ? 'selected' : ''; ?>>Moderado 6-10</option>
                            <option value="Alto" <?php echo ($edit_mode && $edit_tarea && ($edit_tarea['nivel_riesgo_final'] ?? '') === 'Alto') ? 'selected' : ''; ?>>Alto 11-17</option>
                            <option value="Extremo" <?php echo ($edit_mode && $edit_tarea && ($edit_tarea['nivel_riesgo_final'] ?? '') === 'Extremo') ? 'selected' : ''; ?>>Extremo 18-25</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="ubicacion" class="form-label"><strong>Ubicación Específica</strong></label>
                        <input type="text" class="form-control" id="ubicacion" name="ubicacion" placeholder="Ej: Banco 240, Veta nueva" value="<?php echo htmlspecialchars($edit_mode && $edit_tarea ? ($edit_tarea['ubicacion'] ?? '') : ''); ?>" required <?php echo $edit_mode ? 'readonly' : ''; ?>>
                    </div>

                    <div class="col-md-6">
                        <label for="responsable" class="form-label"><strong>Responsable de Actividad</strong></label>
                        <input type="text" class="form-control" id="responsable" name="responsable" placeholder="Nombre completo del responsable" value="<?php echo htmlspecialchars($edit_mode && $edit_tarea ? ($edit_tarea['responsable'] ?? '') : ''); ?>" required <?php echo $edit_mode ? 'readonly' : ''; ?>>
                    </div>

                   <div class="col-md-12">
                        <label for="fecha" class="form-label"><strong>Fecha</strong></label>
                        <input type="date" class="form-control" id="fecha" name="fecha" value="<?php echo htmlspecialchars($edit_mode && $edit_tarea ? ($edit_tarea['fecha'] ?? date('Y-m-d')) : date('Y-m-d')); ?>" readonly style="pointer-events: none;" required>
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
                                $permisosSeleccionados = [];
                                if ($edit_mode && $edit_tarea && !empty($edit_tarea['permisos_requeridos'])) {
                                    $decodedPermisos = json_decode($edit_tarea['permisos_requeridos'], true);
                                    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedPermisos)) {
                                        $permisosSeleccionados = array_map('strval', $decodedPermisos);
                                    } else {
                                        $permisosSeleccionados = array_map('trim', explode(',', $edit_tarea['permisos_requeridos']));
                                    }
                                }
                                foreach ($opcionesPermisos as $permiso) {
                            ?>
                                <div class="form-check form-check-inline me-3 mb-2">
                                    <input class="form-check-input permiso-checkbox" type="checkbox" name="permisos_requeridos[]" id="permiso_<?php echo md5($permiso); ?>" value="<?php echo htmlspecialchars($permiso); ?>" <?php echo in_array($permiso, $permisosSeleccionados, true) ? 'checked' : ''; ?> <?php echo $edit_mode ? 'disabled' : ''; ?>>
                                    <label class="form-check-label" for="permiso_<?php echo md5($permiso); ?>"><?php echo htmlspecialchars($permiso); ?></label>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label for="actividad" class="form-label"><strong>Actividad (Descripción del Trabajo)</strong></label>
                        <textarea class="form-control" id="actividad" name="actividad" rows="3" placeholder="Describa la tarea..." required <?php echo $edit_mode ? 'readonly' : ''; ?>><?php echo htmlspecialchars($edit_mode && $edit_tarea ? ($edit_tarea['actividad'] ?? '') : ''); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-gold"><i class="fa-solid fa-floppy-disk me-1"></i> <?php echo $edit_mode ? 'Actualizar Estado' : 'Guardar Registro'; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUrlEdicion" tabindex="-1" aria-labelledby="modalUrlEdicionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: var(--eqx-primary); border-bottom: 3px solid var(--eqx-accent);">
                <h5 class="modal-title" id="modalUrlEdicionLabel"><i class="fa-solid fa-link me-2"></i>URL de edición</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label for="editUrlInput" class="form-label"><strong>Copie este token de autorización para el propietario del registro:</strong></label>
                <div class="input-group">
                    <input type="text" class="form-control" id="editUrlInput" readonly>
                    <button type="button" class="btn btn-gold" id="btnCopiarUrl">Copiar</button>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Inicialización de la configuración asegurando un valor por defecto si PHP entrega vacío
    const autoUpdateConfig = {
        pagina: parseInt("<?php echo !empty($pagina_actual) ? $pagina_actual : 1; ?>") || 1,
        limite: parseInt("<?php echo !empty($registros_por_pagina) ? $registros_por_pagina : 10; ?>") || 10,
        filtro_gerencia: parseInt("<?php echo !empty($filtro_gerencia) ? $filtro_gerencia : 0; ?>") || 0,
        filtro_area: parseInt("<?php echo !empty($filtro_area) ? $filtro_area : 0; ?>") || 0,
        filtro_estado: "<?php echo addslashes($filtro_estado ?? ''); ?>"
    };

    const formularioAgregarTarea = document.getElementById('formAgregarTarea');
    const modalAgregarTareaElement = document.getElementById('modalAgregarTarea');
    const modalUrlEdicionElement = document.getElementById('modalUrlEdicion');
    const modalUrlEdicion = modalUrlEdicionElement ? bootstrap.Modal.getOrCreateInstance(modalUrlEdicionElement) : null;
    const tableBody = document.querySelector('.table-responsive tbody');

    // Inicialización del Modal usando Bootstrap 5 de manera limpia
    const modalAgregarTarea = modalAgregarTareaElement ? bootstrap.Modal.getOrCreateInstance(modalAgregarTareaElement) : null;

    if (formularioAgregarTarea && formularioAgregarTarea.dataset.editMode === '1') {
        const gerenciaId = document.getElementById('gerencia_id')?.value || 0;
        if (gerenciaId > 0 && modalAgregarTarea) {
            modalAgregarTarea.show();
        }
    }

    if (formularioAgregarTarea) {
        formularioAgregarTarea.addEventListener('submit', function (event) {
            event.preventDefault();

            const formData = new FormData(formularioAgregarTarea);
            const submitButton = formularioAgregarTarea.querySelector('button[type="submit"]');
            const action = formularioAgregarTarea.dataset.action || 'guardar';
            const isEditMode = formularioAgregarTarea.dataset.editMode === '1';
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> ' + (isEditMode ? 'Actualizando...' : 'Guardando...');
            }

            fetch(`index.php?action=${action}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(async response => {
                const text = await response.text();
                let payload = null;
                try {
                    payload = JSON.parse(text);
                } catch (error) {
                    payload = { status: 'error', message: 'Respuesta inválida del servidor: ' + text };
                }

                if (payload.status === 'success') {
                    if (!isEditMode) {
                        formularioAgregarTarea.reset();
                        const areaSelect = document.getElementById('area_id');
                        if (areaSelect) {
                            areaSelect.innerHTML = '<option value="" selected disabled>Seleccione primero una gerencia...</option>';
                            areaSelect.disabled = true;
                        }
                    }

                    if (modalAgregarTarea) {
                        modalAgregarTarea.hide();
                    }

                    if (payload.edit_token) {
                        const editUrlInput = document.getElementById('editUrlInput');
                        if (editUrlInput) {
                            editUrlInput.value = payload.edit_token;
                        }
                        if (modalUrlEdicion) {
                            modalUrlEdicion.show();
                        }
                    } else {
                        alert(payload.message || (isEditMode ? 'Registro actualizado correctamente.' : 'Registro guardado correctamente.'));
                    }

                    if (!payload.edit_token) {
                        window.location.href = 'index.php';
                    }
                } else {
                    alert(payload.message || (isEditMode ? 'No se pudo actualizar el registro.' : 'No se pudo guardar el registro.'));
                }
            })
            .catch((error) => {
                console.error(error);
                alert('Ocurrió un error de red al ' + (isEditMode ? 'actualizar' : 'guardar') + ' el registro.');
            })
            .finally(() => {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> ' + (isEditMode ? 'Actualizar Registro' : 'Guardar Registro');
                }
            });
        });
    }

    const filtroGerenciaSelect = document.getElementById('filtro_gerencia');
    const filtroAreaSelect = document.getElementById('filtro_area');
    const gerenciaSelect = document.getElementById('gerencia_id');
    const areaSelect = document.getElementById('area_id');

    if (filtroGerenciaSelect && filtroAreaSelect) {
        filtroGerenciaSelect.addEventListener('change', function () {
            if (Number(this.value) !== 0) {
                filtroAreaSelect.value = '0';
            }
        });
    }

    function cargarAreasPorGerencia(gerenciaId, selectedAreaId = 0) {
        if (!areaSelect) return;
        areaSelect.innerHTML = '<option value="" selected disabled>Cargando áreas...</option>';
        areaSelect.disabled = true;

        if (gerenciaId > 0) {
            fetch(`procesar.php?action=get_areas_por_gerencia&gerencia_id=${gerenciaId}`)
                .then(response => response.json())
                .then(res => {
                    if (res.status === 'success') {
                        areaSelect.innerHTML = '<option value="" selected disabled>Seleccione Área...</option>';
                        res.data.forEach(area => {
                            const selected = Number(selectedAreaId) === Number(area.id) ? 'selected' : '';
                            areaSelect.innerHTML += `<option value="${area.id}" ${selected}>${area.nombre}</option>`;
                        });
                        areaSelect.disabled = false;
                    } else {
                        areaSelect.innerHTML = '<option value="" selected disabled>Error al cargar áreas</option>';
                    }
                })
                .catch(() => {
                    areaSelect.innerHTML = '<option value="" selected disabled>Error de red</option>';
                });
        }
    }

    if (gerenciaSelect) {
        gerenciaSelect.addEventListener('change', function() {
            cargarAreasPorGerencia(this.value, 0);
        });

        if (gerenciaSelect.value > 0) {
            const selectedAreaId = areaSelect?.dataset.selectedAreaId || 0;
            cargarAreasPorGerencia(gerenciaSelect.value, selectedAreaId);
        }
    }

    const copyUrlButton = document.getElementById('btnCopiarUrl');
    if (copyUrlButton) {
        copyUrlButton.addEventListener('click', async () => {
            const editUrlInput = document.getElementById('editUrlInput');
            if (!editUrlInput) return;

            try {
                await navigator.clipboard.writeText(editUrlInput.value);
                copyUrlButton.textContent = 'Copiado';
                copyUrlButton.classList.add('btn-success');
                setTimeout(() => {
                    copyUrlButton.textContent = 'Copiar';
                    copyUrlButton.classList.remove('btn-success');
                }, 1200);
            } catch (error) {
                editUrlInput.focus();
                editUrlInput.select();
                document.execCommand('copy');
                copyUrlButton.textContent = 'Copiado';
                setTimeout(() => {
                    copyUrlButton.textContent = 'Copiar';
                }, 1200);
            }
        });
    }

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

    function escaparHtml(valor) {
        return String(valor ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function obtenerBadgeClase(riesgo) {
        const lower = String(riesgo ?? '').toLowerCase();
        if (lower.includes('extremo')) return 'badge-critico';
        if (lower.includes('alto')) return 'badge-alto';
        if (lower.includes('moderado') || lower.includes('medio')) return 'badge-medio';
        if (lower.includes('bajo')) return 'badge-bajo';
        return 'bg-secondary text-white';
    }

    function obtenerPermisosHtml(permisosRequeridos) {
        let permisos = [];

        if (permisosRequeridos) {
            try {
                const decoded = JSON.parse(permisosRequeridos);
                if (Array.isArray(decoded)) {
                    permisos = decoded;
                }
            } catch (error) {
                permisos = String(permisosRequeridos)
                    .split(',')
                    .map(item => item.trim())
                    .filter(Boolean);
            }
        }

        if (!permisos.length) {
            return '<span class="text-muted small">Sin permisos</span>';
        }

        return permisos.map(permiso => {
            const bgStyle = (permiso === 'No se requiere permiso') ? 'bg-light text-dark border border-secondary-subtle' : 'bg-secondary';
            return `<span class="badge ${bgStyle} me-1 mb-1">${escaparHtml(permiso)}</span>`;
        }).join('');
    }

    function crearFilaTarea(tarea) {
        const riesgoInicial = String(tarea.nivel_riesgo_inicial ?? '').trim();
        const riesgoFinal = String(tarea.nivel_riesgo_final ?? '').trim();
        const riesgoTexto = String(tarea.nivel_riesgo ?? '').trim();
        const riesgoInicialFinal = riesgoInicial !== '' ? riesgoInicial : (riesgoTexto.split('/')[0] || '').trim();
        const riesgoFinalFinal = riesgoFinal !== '' ? riesgoFinal : (riesgoTexto.split('/')[1] || '').trim();
        const estadoActual = String(tarea.estado ?? 'En desarrollo').trim();
        const estadoLabel = (estadoActual === 'finalizado' || estadoActual === 'Finalizado') ? 'Finalizado' : 'En desarrollo';
        const estadoClass = (estadoLabel === 'Finalizado') ? 'badge-finalizado' : 'badge-en-desarrollo';

        const riesgoInicialHtml = riesgoInicialFinal !== ''
            ? `<span class="badge ${obtenerBadgeClase(riesgoInicialFinal)}">${escaparHtml(riesgoInicialFinal)}</span>`
            : '<span class="text-muted small">-</span>';

        const riesgoFinalHtml = riesgoFinalFinal !== ''
            ? `<span class="badge ${obtenerBadgeClase(riesgoFinalFinal)}">${escaparHtml(riesgoFinalFinal)}</span>`
            : '<span class="text-muted small">-</span>';

        return `
            <tr>
                <td><strong>#${escaparHtml(tarea.id)}</strong></td>
                <td>${escaparHtml(tarea.gerencia ?? '')}</td>
                <td>${escaparHtml(tarea.area ?? '')}</td>
                <td>${escaparHtml(tarea.actividad ?? '')}</td>
                <td>${escaparHtml(tarea.ubicacion ?? '')}</td>
                <td><strong>${escaparHtml(tarea.responsable ?? 'No asignado')}</strong></td>
                <td>${escaparHtml(tarea.fecha ?? '')}</td>
                <td>${riesgoInicialHtml}</td>
                <td>${riesgoFinalHtml}</td>
                <td>${obtenerPermisosHtml(tarea.permisos_requeridos)}</td>
                <td>
                    <div class="d-flex flex-column gap-2">
                        <span class="badge ${estadoClass} align-self-start">${escaparHtml(estadoLabel)}</span>
                        <select class="form-select form-select-sm estado-select" data-id="${escaparHtml(tarea.id)}">
                            <option value="En desarrollo" ${estadoLabel === 'En desarrollo' ? 'selected' : ''}>En desarrollo</option>
                            <option value="Finalizado" ${estadoLabel === 'Finalizado' ? 'selected' : ''}>Finalizado</option>
                        </select>
                    </div>
                </td>
            </tr>
        `;
    }

    function renderTabla(rows) {
        if (!tableBody) return;

        if (!Array.isArray(rows) || rows.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="11" class="text-center text-muted p-4">No se encontraron registros con los criterios seleccionados.</td></tr>';
            return;
        }

        tableBody.innerHTML = rows.map(crearFilaTarea).join('');
        bindEstadoSelects();
    }

    function bindEstadoSelects() {
        document.querySelectorAll('.estado-select').forEach(select => {
            select.dataset.currentValue = select.value;

            select.onchange = function () {
                const id = this.dataset.id;
                const estado = this.value;
                const previousValue = this.dataset.currentValue;
                const token = window.prompt('Ingrese el token de autorización para cambiar el estado de esta tarea:');

                if (token === null) {
                    this.value = previousValue;
                    return;
                }

                const tokenTrimmed = token.trim();
                if (tokenTrimmed === '') {
                    this.value = previousValue;
                    alert('Debe ingresar el token de autorización para modificar el estado.');
                    return;
                }

                fetch('procesar.php?action=actualizar_estado', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                    },
                    body: `id=${encodeURIComponent(id)}&estado=${encodeURIComponent(estado)}&edit_token=${encodeURIComponent(tokenTrimmed)}`
                })
                .then(response => response.json())
                .then(res => {
                    if (res.status !== 'success') {
                        this.value = previousValue;
                        alert(res.message || 'No se pudo actualizar el estado.');
                    } else {
                        const estadoNormalizado = (estado === 'finalizado' || estado === 'Finalizado') ? 'Finalizado' : 'En desarrollo';
                        actualizarEstadoUI(this, estadoNormalizado);
                        this.dataset.currentValue = estadoNormalizado;
                    }
                })
                .catch(() => {
                    this.value = previousValue;
                    alert('No se pudo actualizar el estado.');
                });
            };
        });
    }

    function actualizarEstadoUI(select, estadoNormalizado) {
        select.value = estadoNormalizado;
        select.dataset.currentValue = estadoNormalizado;

        const badge = select.closest('td').querySelector('.badge');
        if (badge) {
            badge.textContent = estadoNormalizado;
            badge.classList.toggle('badge-finalizado', estadoNormalizado === 'Finalizado');
            badge.classList.toggle('badge-en-desarrollo', estadoNormalizado !== 'Finalizado');
        }
    }

    function fetchLatestTable() {
        const query = new URLSearchParams({
            action: 'listar',
            pagina: autoUpdateConfig.pagina,
            limite: autoUpdateConfig.limite,
            filtro_gerencia: String(autoUpdateConfig.filtro_gerencia || 0),
            filtro_area: String(autoUpdateConfig.filtro_area || 0),
            filtro_estado: autoUpdateConfig.filtro_estado || ''
        });

        fetch(`procesar.php?${query.toString()}`)
            .then(response => response.json())
            .then(res => {
                if (res.status === 'success' && Array.isArray(res.data)) {
                    renderTabla(res.data);
                }
            })
            .catch(() => {
                // Ignorar errores de red temporales
            });
    }

    const pageSizeSelect = document.getElementById('pageSizeSelect');
    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', function () {
            const newLimit = parseInt(this.value, 10) || 10;
            const query = new URLSearchParams({
                pagina: 1,
                limite: newLimit,
                filtro_gerencia: String(autoUpdateConfig.filtro_gerencia || 0),
                filtro_area: String(autoUpdateConfig.filtro_area || 0),
                filtro_estado: autoUpdateConfig.filtro_estado || ''
            });
            window.location.href = `index.php?${query.toString()}`;
        });
    }

    bindEstadoSelects();
    fetchLatestTable();
    setInterval(fetchLatestTable, 5000);
</script>
</body>
</html>