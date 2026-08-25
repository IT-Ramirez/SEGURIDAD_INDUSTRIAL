<?php
include_once("../session_check.php");
checkRole(['admin']);
require_once("../config.php");
 include 'obtener_pdf.php';     
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Inspección - Equinox Gold</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="/image/eqx.jpg">
    <link rel="stylesheet" href="estilos.css">
    <style>
        :root {
            --eqx-gold: #D4AF37;
            --eqx-dark: #24415D;
            --sidebar-width: 260px;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        .wrapper {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        #sidebar {
            position: fixed;
            left: -260px;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background-color: #1a1a1a;
            z-index: 1000;
            overflow-y: auto;
            transition: left 0.3s ease;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        #sidebar.show {
            left: 0;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid #333;
        }

        .sidebar-header h5 {
            margin: 0;
            font-size: 1rem;
        }

        .sidebar-header small {
            display: block;
            font-size: 0.65rem !important;
        }

        .sidebar .nav {
            padding: 0 0.5rem;
        }

        .sidebar .nav-link {
            color: #adb5bd;
            padding: 0.75rem 1rem;
            margin: 0.25rem 0;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
        }

        .sidebar .nav-link:hover {
            color: var(--eqx-gold);
            background-color: rgba(212, 175, 55, 0.1);
        }

        .sidebar .nav-link.active {
            color: var(--eqx-gold);
            background-color: rgba(212, 175, 55, 0.15);
            border-left: 3px solid var(--eqx-gold);
        }

        .sidebar .nav-link i {
            margin-right: 0.75rem;
            width: 1.2rem;
            text-align: center;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            margin-left: 0;
            transition: margin-left 0.3s ease;
        }

        .wrapper.sidebar-open .main-content {
            margin-left: 0;
        }

        .wrapper.sidebar-open::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .top-navbar {
            background-color: #fff;
            border-bottom: 1px solid #dee2e6;
            padding: 1rem 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        #sidebarToggle {
            display: none;
        }

        @media (max-width: 992px) {
            #sidebarToggle {
                display: inline-block;
            }

            .wrapper {
                height: auto;
                min-height: 100vh;
            }

            #sidebar {
                position: fixed;
            }

            .main-content {
                margin-left: 0;
            }
        }

        .btn-eqx-gold {
            background-color: var(--eqx-gold);
            color: var(--eqx-dark);
            border: none;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-eqx-gold:hover {
            background-color: #c19b1a;
            color: var(--eqx-dark);
            transform: translateY(-2px);
        }

        .table-eqx {
            border: 1px solid #dee2e6;
        }

        .table-eqx thead {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        .table-eqx th {
            font-weight: 600;
            color: var(--eqx-dark);
            padding: 0.75rem;
        }

        .table-eqx tbody tr:hover {
            background-color: #f8f9fa;
        }

        .btn-outline-correct {
            color: #28a745;
            border-color: #28a745;
        }

        .btn-outline-correct:hover {
            background-color: #28a745;
            color: white;
        }

        .btn-outline-incorrect {
            color: #dc3545;
            border-color: #dc3545;
        }

        .btn-outline-incorrect:hover {
            background-color: #dc3545;
            color: white;
        }

        .btn-outline-na {
            color: #6c757d;
            border-color: #6c757d;
        }

        .btn-outline-na:hover {
            background-color: #6c757d;
            color: white;
        }

        .status-group {
            display: flex;
            gap: 0.25rem;
            flex-wrap: wrap;
        }

        .btn-check:checked + .btn-outline-correct {
            background-color: #28a745;
            color: white;
        }

        .btn-check:checked + .btn-outline-incorrect {
            background-color: #dc3545;
            color: white;
        }

        .btn-check:checked + .btn-outline-na {
            background-color: #6c757d;
            color: white;
        }

        .btn-action-area {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-action-area .btn {
            flex: 1;
        }

        @media (max-width: 768px) {
            .top-navbar {
                padding: 0.75rem 1rem;
            }

            .btn-action-area {
                flex-direction: column;
            }

            .d-md-flex {
                gap: 0.5rem;
            }
        }

        main {
            flex: 1;
            overflow-y: auto;
        }

        .footer {
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
            padding: 1rem;
            font-size: 0.875rem;
        }
    </style>
</head>

<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <?php $header_title = 'Formulario de Inspección'; $header_actions = '<a href="index.php" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left me-1"></i> Volver</a>'; include 'header.php'; ?>

        <main class="p-4">
            <div class="container-fluid p-0">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <div class="d-flex gap-2">
                        <a href="formulario.php" class="btn btn-eqx-gold btn-sm">
                            <i class="bi bi-plus-circle me-1"></i> Nueva Inspección
                        </a>
                        <a href="index.php" class="btn btn-eqx-gold btn-sm">
                            <i class="bi bi-list-check me-1"></i> Ver Inspecciones
                        </a>
                    </div>
                </div>
            </div>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-4">
                <div class="header-brand d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h3 class="fw-bold m-0" style="color:var(--eqx-dark)">Inspección Equipo Liviano</h3>
                    </div>
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
                            <label class="form-label fw-semibold">Placa:</label>
                            <!-- name="placa" para recibirlo directo en PHP -->
                            <input type="text" name="placa" id="placa" class="form-control" placeholder="Ingrese vehículo / placa" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Código del Vehículo:</label>
                            <input type="text" name="codigo_vehiculo" id="codigo_vehiculo" class="form-control" placeholder="Ejemp: UG-01" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Hora:</label>
                            <input type="text" name="hora" class="form-control" value="<?= htmlspecialchars(date('H:i A'), ENT_QUOTES, 'UTF-8') ?>" readonly>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Odómetro (KM):</label>
                            <input type="number" name="odometro" class="form-control" required placeholder="Ej: 125000" min="0" step="1">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Nombre Conductor:</label>
                             <input type="text" name="nombre" class="form-control" value="<?= isset($_SESSION['nombre_empleado']) ? htmlspecialchars($_SESSION['nombre_empleado'], ENT_QUOTES, 'UTF-8') : '' ?>" required placeholder="Nombre completo" maxlength="150" readonly>
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
                                                <i class="bi bi-check-lg me-1"></i>Cumple
                                            </label>

                                            <input type="radio" class="btn-check" name="evaluacion[<?= $i ?>]" id="i_<?= $i ?>" value="I" required>
                                            <label class="btn btn-outline-incorrect" for="i_<?= $i ?>">
                                                <i class="bi bi-x-lg me-1"></i>No cumple
                                            </label>

                                            <?php if ($item['tipo'] === 'CINA'): ?>
                                                <input type="radio" class="btn-check" name="evaluacion[<?= $i ?>]" id="na_<?= $i ?>" value="NA" required>
                                                <label class="btn btn-outline-na" for="na_<?= $i ?>">N/A</label>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php
                                $camposSiNo = [
                                    'cinta_precaucion' => 'Cinta de precaución amarilla/roja',
                                    'gps_activo' => 'GPS Activo',
                                    'radio_base' => 'Radio Base',
                                    'tarjeta_gps' => 'Tarjeta GPS',
                                    'fatiga' => '* ¿Se siente fatigado?'
                                ];
                                foreach ($camposSiNo as $nombreCampo => $etiquetaCampo):
                                ?>
                                <tr>
                                    <td class="px-3 py-2 fw-medium text-dark"><?= htmlspecialchars($etiquetaCampo, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-center py-2">
                                        <div class="btn-group status-group" role="group">
                                            <?php $esFatiga = $nombreCampo === 'fatiga'; ?>
                                            <input type="radio" class="btn-check" name="<?= $nombreCampo ?>" id="<?= $nombreCampo ?>_<?= $esFatiga ? 'si' : 'c' ?>" value="<?= $esFatiga ? 'SI' : 'C' ?>" required>
                                            <label class="btn btn-outline-correct" for="<?= $nombreCampo ?>_<?= $esFatiga ? 'si' : 'c' ?>"><?= $esFatiga ? 'SI' : '<i class="bi bi-check-lg me-1"></i>Cumple' ?></label>
                                            <input type="radio" class="btn-check" name="<?= $nombreCampo ?>" id="<?= $nombreCampo ?>_<?= $esFatiga ? 'no' : 'i' ?>" value="<?= $esFatiga ? 'NO' : 'I' ?>" required>
                                            <label class="btn btn-outline-incorrect" for="<?= $nombreCampo ?>_<?= $esFatiga ? 'no' : 'i' ?>"><?= $esFatiga ? 'NO' : '<i class="bi bi-x-lg me-1"></i>No cumple' ?></label>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td class="px-3 py-2 fw-medium text-dark">Nivel de Combustible</td>
                                    <td class="text-center py-2">
                                        <select name="nivel_combustible" id="nivel_combustible" class="form-select" required>
                                            <option value="">Seleccione...</option>
                                            <option value="3/4 (Tres cuartos): 75%">3/4 (Tres cuartos): 75%</option>
                                            <option value="1/2 (Medio): 50%">1/2 (Medio): 50%</option>
                                            <option value="1/4 (Un cuarto): 25%">1/4 (Un cuarto): 25%</option>
                                            <option value="Full (Lleno): 100%">Full (Lleno): 100%</option>
                                        </select>
                                        <div id="alerta-combustible" class="alert alert-warning mt-2 mb-0 d-none" role="alert" aria-live="polite">
                                            <i class="bi bi-fuel-pump-fill me-1"></i><strong>Rellenar combustible:</strong> el tanque está en ¼ de su capacidad.
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-2 fw-medium text-dark">Último mantenimiento</td>
                                    <td class="text-center py-2">
                                        <input type="date" name="ultimo_mantenimiento" class="form-control" required>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Observaciones / Notas:</label>
                        <textarea name="observaciones" class="form-control" rows="3" placeholder="Detalle cualquier anomalía o hallazgo relevante..." maxlength="1000"></textarea>
                    </div>
                    <div class="btn-action-area">
                        <button type="submit" class="btn btn-eqx-gold btn-lg shadow-sm fw-bold">
                            <i class="bi bi-file-earmark-pdf me-2"></i>Enviar y Generar PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
        </main>

        <?php include 'footer.php'; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const wrapper = document.querySelector('.wrapper');
    const sidebarToggle = document.getElementById('sidebarToggle');

    if (!sidebar || !wrapper || !sidebarToggle) return;

    const isOpen = !sidebar.classList.contains('show');
    sidebar.classList.toggle('show', isOpen);
    wrapper.classList.toggle('sidebar-open', isOpen);
    sidebarToggle.setAttribute('aria-expanded', String(isOpen));
}

window.addEventListener('resize', function() {
    const sidebar = document.getElementById('sidebar');
    const wrapper = document.querySelector('.wrapper');
    if (!sidebar || !wrapper) return;

    if (window.innerWidth > 992) {
        sidebar.classList.remove('show');
        wrapper.classList.remove('sidebar-open');
    }
});

const sidebarLinks = document.querySelectorAll('#sidebar .nav-link');
sidebarLinks.forEach(function(link) {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 992) {
            document.getElementById('sidebar')?.classList.remove('show');
            document.querySelector('.wrapper')?.classList.remove('sidebar-open');
        }
    });
});

const nivelCombustible = document.getElementById('nivel_combustible');
const alertaCombustible = document.getElementById('alerta-combustible');

nivelCombustible.addEventListener('change', function() {
    alertaCombustible.classList.toggle('d-none', this.value !== '1/4 (Un cuarto): 25%');
});
</script>
</body>
</html>
</html>