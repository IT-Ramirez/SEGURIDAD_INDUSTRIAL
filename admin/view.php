<?php
require_once '../config.php'; 

// Conexión a la base de datos utilizando las variables de config.php
$dsn = "mysql:host=$servername;dbname=$dbname;charset=utf8mb4";
$pdo = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// Validar ID de inspección
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];

// Obtener datos generales de la inspección
$stmt = $pdo->prepare(
    "SELECT i.id, i.nombre_conductor, i.odometro, i.observaciones, i.estado, i.fecha_registro, v.codigo, v.placa
     FROM inspecciones i
     LEFT JOIN vehiculos v ON v.id = i.codigo_vehiculo
     WHERE i.id = ?"
);
$stmt->execute([$id]);
$inspeccion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inspeccion) {
    die('Inspección no encontrada.');
}

// Obtener los detalles del checklist
$detallesStmt = $pdo->prepare("SELECT parametro, resultado FROM detalles_inspeccion WHERE inspeccion_id = ? ORDER BY id ASC");
$detallesStmt->execute([$id]);
$detalles = $detallesStmt->fetchAll(PDO::FETCH_ASSOC);

// Filtrar únicamente los parámetros no conformes o con fallas
$noAdecuados = array_filter($detalles, function ($detalle) {
    return $detalle['resultado'] !== 'C';
});
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Inspección #<?= htmlspecialchars($inspeccion['id']) ?> - Equinox Gold</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --eqx-gold: #C59B27;
            --eqx-gold-hover: #A8821D;
            --eqx-dark: #1C2024;
            --eqx-bg-light: #F4F6F8;
        }

        body {
            background-color: var(--eqx-bg-light);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .card-header-custom {
            background-color: var(--eqx-dark);
            color: #ffffff;
            border-bottom: 3px solid var(--eqx-gold);
        }

        .btn-eqx-gold {
            background-color: var(--eqx-gold);
            color: #ffffff;
            border: none;
        }

        .btn-eqx-gold:hover {
            background-color: var(--eqx-gold-hover);
            color: #ffffff;
        }
    </style>
</head>
<body class="py-4">

<div class="container my-3">
    <!-- Acciones superiores -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver al listado
        </a>
        <button onclick="window.print();" class="btn btn-outline-dark">
            <i class="bi bi-printer me-1"></i> Imprimir Reporte
        </button>
    </div>

    <!-- Tarjeta Principal de Inspección -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header card-header-custom p-3 d-flex justify-content-between align-items-center">
            <h4 class="m-0 fw-bold fs-5">
                <i class="bi bi-clipboard-check me-2"></i> Detalle de Inspección #<?= htmlspecialchars($inspeccion['id']) ?>
            </h4>
            <span class="badge fs-6 <?= $inspeccion['estado'] === 'Aprobado' ? 'bg-success' : 'bg-danger' ?>">
                <?= htmlspecialchars($inspeccion['estado']) ?>
            </span>
        </div>
        
        <div class="card-body p-4">
            
            <!-- Información General -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="text-muted small d-block">Vehículo / Placa</label>
                    <span class="fw-semibold text-dark fs-6">
                        <?= htmlspecialchars(($inspeccion['codigo'] ?? 'N/A') . ' (' . ($inspeccion['placa'] ?? 'Sin placa') . ')') ?>
                    </span>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small d-block">Conductor</label>
                    <span class="fw-semibold text-dark fs-6"><?= htmlspecialchars($inspeccion['nombre_conductor']) ?></span>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small d-block">Odómetro</label>
                    <span class="fw-semibold text-dark fs-6"><?= number_format((float)$inspeccion['odometro']) ?> KM</span>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small d-block">Fecha de Registro</label>
                    <span class="fw-semibold text-dark fs-6"><?= date('d/m/Y H:i', strtotime($inspeccion['fecha_registro'])) ?></span>
                </div>
            </div>

            <!-- Observaciones -->
            <?php if (!empty($inspeccion['observaciones'])): ?>
                <div class="mb-4">
                    <label class="text-muted small d-block mb-1">Observaciones / Anotaciones</label>
                    <div class="border rounded p-3 bg-light text-dark">
                        <?= nl2br(htmlspecialchars($inspeccion['observaciones'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Parámetros no adecuados -->
            <div class="mb-4">
                <h5 class="fw-bold fs-6 text-danger mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Parámetros No Adecuados / Fallas
                </h5>
                <?php if (empty($noAdecuados)): ?>
                    <div class="alert alert-success d-flex align-items-center m-0" role="alert">
                        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                        <div>Todos los parámetros evaluados cumplen con las condiciones adecuadas.</div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mb-3">
                        Se registraron <strong><?= count($noAdecuados) ?></strong> parámetro(s) con hallazgos o fallas.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Parámetro Evaluado</th>
                                    <th style="width: 220px;">Resultado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($noAdecuados as $detalle): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($detalle['parametro']) ?></td>
                                        <td>
                                            <?php if ($detalle['resultado'] === 'I'): ?>
                                                <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> Insuficiente / Falla</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><i class="bi bi-dash-circle me-1"></i> No Aplica</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Todos los parámetros -->
            <div>
                <h5 class="fw-bold fs-6 text-dark mb-3">
                    <i class="bi bi-list-check me-1"></i> Todos los Parámetros Evaluados
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 60px;" class="text-center">#</th>
                                <th>Parámetro</th>
                                <th style="width: 220px;">Resultado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalles as $index => $detalle): ?>
                                <tr>
                                    <td class="text-center text-muted fw-bold"><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($detalle['parametro']) ?></td>
                                    <td>
                                        <?php if ($detalle['resultado'] === 'C'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                <i class="bi bi-check-circle me-1"></i> Conforme
                                            </span>
                                        <?php elseif ($detalle['resultado'] === 'I'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                                <i class="bi bi-x-circle me-1"></i> Insuficiente / Falla
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                                                <i class="bi bi-dash-circle me-1"></i> No Aplica
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>