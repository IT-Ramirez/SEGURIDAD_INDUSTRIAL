<?php include 'obtener_pdf.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checklist Staff - Equinox Gold</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { 
            --eqx-gold: #C59B27; 
            --eqx-gold-hover: #A8821D; 
            --eqx-dark: #1C2024; 
            --eqx-gray-dark: #2A2F35; 
            --eqx-bg-light: #F4F6F8; 
            --sidebar-width: 260px; 
        }
        body { background-color: var(--eqx-bg-light); font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; overflow-x: hidden; }
        .wrapper { display:flex; width:100%; min-height:100vh; }
        #sidebar { width:var(--sidebar-width); background:var(--eqx-dark); color:#fff; display:flex; flex-direction:column }
        #sidebar .sidebar-header { padding:1.25rem 1.5rem; background:rgba(0,0,0,0.12); border-bottom:2px solid var(--eqx-gold); }
        #sidebar .nav-link { color:#c2c7d0; padding:0.8rem 1.5rem; display:flex; gap:12px; }
        #sidebar .nav-link.active, #sidebar .nav-link:hover { color:#fff; background:var(--eqx-gray-dark); border-left:4px solid var(--eqx-gold); }
        .main-content { flex:1; display:flex; flex-direction:column; }
        .top-navbar { background:#fff; border-bottom:1px solid #e3e6f0; padding:0.75rem 1.5rem }
        .btn-eqx-gold { background:var(--eqx-gold); color:#fff; border:none; transition: all 0.2s; }
        .btn-eqx-gold:hover { background:var(--eqx-gold-hover); color:#fff; }
        .table-eqx thead { background:var(--eqx-dark); color:#fff }
        .header-brand { border-bottom:3px solid var(--eqx-gold); padding-bottom:0.75rem }
        
        .status-group .btn-check + .btn {
            font-size: 0.82rem;
            font-weight: 600;
            padding: 0.35rem 0.6rem;
            border-radius: 6px;
        }
        .btn-outline-correct { border-color: #198754; color: #198754; }
        .btn-check:checked + .btn-outline-correct { background-color: #198754; color: #fff; box-shadow: 0 2px 4px rgba(25, 135, 84, 0.3); }
        .btn-outline-incorrect { border-color: #dc3545; color: #dc3545; }
        .btn-check:checked + .btn-outline-incorrect { background-color: #dc3545; color: #fff; box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3); }
        .btn-outline-na { border-color: #6c757d; color: #6c757d; }
        .btn-check:checked + .btn-outline-na { background-color: #6c757d; color: #fff; }

        @media print {
            #sidebar, .top-navbar, .btn-action-area { display: none !important; }
            .main-content { margin: 0; padding: 0; }
            .card { border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>
    <?php include 'navbar.php'; ?>

        <main class="p-4">
            <div class="container-fluid p-0">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <p class="text-muted m-0">Complete el checklist diario para el control vehicular.</p>
                    <a href="index.php" class="btn btn-eqx-gold"><i class="bi bi-plus-circle me-1"></i> Nueva Inspección</a>
                </div>

                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-4">
                        <div class="header-brand d-flex align-items-center justify-content-between mb-4">
                            <div>
                                <h3 class="fw-bold m-0" style="color:var(--eqx-dark)">Formato de Inspección Equipo Liviano</h3>
                                <small class="text-muted fw-bold">CONTROL DE VEHÍCULOS</small>
                            </div>
                            <div class="px-3 py-1 bg-dark rounded text-center"><span class="fw-bold" style="color:var(--eqx-gold); font-size:0.9rem">01-FOR-037</span></div>
                        </div>

                        <?php if ($mensaje): ?>
                            <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4 d-flex justify-content-between align-items-center" style="background-color:#e8f5e9;color:#1b5e20">
                                <div><strong><i class="bi bi-check-circle-fill me-1"></i></strong> <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></div>
                                <button type="button" class="btn btn-sm btn-outline-success" onclick="window.print()"><i class="bi bi-printer me-1"></i> Imprimir Vista</button>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <!-- Field para Token CSRF -->
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="generar_pdf" value="1">

                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Vehículo / Placa:</label>
                                    <select name="vehiculo_id" id="vehiculo_id" class="form-select" required onchange="actualizarCodigo()">
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($vehiculos as $v): ?>
                                            <option value="<?= htmlspecialchars($v['id'], ENT_QUOTES, 'UTF-8') ?>" data-codigo="<?= htmlspecialchars($v['codigo'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($v['codigo'] . ' - ' . $v['placa'], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Código del Vehículo:</label>
                                    <input type="text" id="codigo_vehiculo" class="form-control" placeholder="Autocompletado" readonly>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">Hora:</label>
                                    <input type="txt" name="hora" class="form-control" value="<?= htmlspecialchars(date('H:i A'), ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">Odómetro (KM):</label>
                                    <input type="number" name="odometro" class="form-control" required placeholder="Ej: 125000" min="0" step="1">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">Nombre Conductor:</label>
                                    <input type="text" name="nombre" class="form-control" required placeholder="Nombre completo" maxlength="150">
                                </div>
                            </div>

                            <h5 class="fw-bold mb-3" style="color:var(--eqx-dark)">Parámetros a Inspeccionar</h5>

                            <div class="table-responsive mb-4">
                                <table class="table table-hover align-middle table-eqx border">
                                    <thead>
                                        <tr>
                                            <th class="py-3 px-3">Parámetro</th>
                                            <th style="width:280px" class="text-center py-3">Estado de Evaluación</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($parametros as $i => $item): ?>
                                        <tr>
                                            <td class="px-3 py-2 fw-medium text-dark">
                                                <span class="text-muted me-2"><?= sprintf('%02d', $i + 1) ?>.</span> 
                                                <?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                            <td class="text-center py-2">
                                                <div class="btn-group status-group" role="group">
                                                    <input type="radio" class="btn-check" name="evaluacion[<?= $i ?>]" id="c_<?= $i ?>" value="C" required>
                                                    <label class="btn btn-outline-correct" for="c_<?= $i ?>">
                                                        <i class="bi bi-check-lg me-1"></i>Correcto
                                                    </label>

                                                    <input type="radio" class="btn-check" name="evaluacion[<?= $i ?>]" id="i_<?= $i ?>" value="I" required>
                                                    <label class="btn btn-outline-incorrect" for="i_<?= $i ?>">
                                                        <i class="bi bi-x-lg me-1"></i>Incorrecto
                                                    </label>

                                                    <?php if ($item['tipo'] === 'CINA'): ?>
                                                        <input type="radio" class="btn-check" name="evaluacion[<?= $i ?>]" id="na_<?= $i ?>" value="NA" required>
                                                        <label class="btn btn-outline-na" for="na_<?= $i ?>">N/A</label>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Observaciones / Notas:</label>
                                <textarea name="observaciones" class="form-control" rows="3" placeholder="Detalle cualquier anomalía o hallazgo relevante..." maxlength="1000"></textarea>
                            </div>

                            <div class="btn-action-area">
                                <button type="submit" class="btn btn-eqx-gold w-100 btn-lg shadow-sm fw-bold">
                                    <i class="bi bi-file-earmark-pdf me-2"></i>Enviar y Generar PDF
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function actualizarCodigo() {
    const select = document.getElementById('vehiculo_id');
    const selectedOption = select.options[select.selectedIndex];
    const codigoInput = document.getElementById('codigo_vehiculo');
    
    if (selectedOption && selectedOption.dataset.codigo) {
        codigoInput.value = selectedOption.dataset.codigo;
    } else {
        codigoInput.value = '';
    }
}
</script>
</body>
</html>