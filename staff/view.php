<?php
include '../config.php';
$pdo = new PDO("mysql:host={$servername};dbname={$dbname};charset=utf8", $username, $password);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID de inspección inválido.');
}

$id = $_SESSION['uid'];

$stmt = $pdo->prepare(
    "SELECT i.id, i.nombre_conductor, i.odometro, i.observaciones, i.estado, i.fecha_registro, v.codigo, v.placa
    FROM inspecciones i
    LEFT JOIN vehiculos v ON v.id = i.vehiculo_id
    WHERE i.id = ?"
);
$stmt->execute([$id]);
$inspeccion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inspeccion) {
    die('Inspección no encontrada.');
}

$detalles = $pdo->prepare("SELECT parametro, resultado FROM detalles_inspeccion WHERE inspeccion_id = ? ORDER BY id ASC");
$detalles->execute([$id]);
$detalles = $detalles->fetchAll(PDO::FETCH_ASSOC);

$noAdecuados = array_filter($detalles, function ($detalle) {
    return $detalle['resultado'] !== 'C';
});
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Inspección</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-3">
<div class="container bg-white p-4 rounded shadow-sm">
    <h3 class="mb-4 text-danger">Detalle de la Inspección #<?= $inspeccion['id'] ?></h3>

    <div class="mb-3">
        <a href="index.php" class="btn btn-secondary">Volver al listado</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <strong>Vehículo:</strong>
            <div><?= htmlspecialchars($inspeccion['codigo'] . ' - ' . $inspeccion['placa']) ?></div>
        </div>
        <div class="col-md-4">
            <strong>Conductor:</strong>
            <div><?= htmlspecialchars($inspeccion['nombre_conductor']) ?></div>
        </div>
        <div class="col-md-2">
            <strong>Odómetro:</strong>
            <div><?= htmlspecialchars($inspeccion['odometro']) ?></div>
        </div>
        <div class="col-md-2">
            <strong>Estado:</strong>
            <div><?= htmlspecialchars($inspeccion['estado']) ?></div>
        </div>
    </div>

    <div class="mb-4">
        <strong>Observaciones:</strong>
        <div class="border rounded p-3 bg-light"><?= nl2br(htmlspecialchars($inspeccion['observaciones'])) ?></div>
    </div>

    <div class="mb-4">
        <strong>Registrado en:</strong>
        <div><?= htmlspecialchars($inspeccion['fecha_registro']) ?></div>
    </div>

    <div class="mb-4">
        <h5>Parámetros no adecuados</h5>
        <?php if (empty($noAdecuados)): ?>
            <div class="alert alert-success">Todos los parámetros están en condiciones adecuadas.</div>
        <?php else: ?>
            <div class="alert alert-warning">
                <?= count($noAdecuados) ?> parámetro(s) no cumple(n) con la condición adecuada.
            </div>
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Parámetro</th>
                            <th>Resultado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($noAdecuados as $detalle): ?>
                            <tr>
                                <td><?= htmlspecialchars($detalle['parametro']) ?></td>
                                <td>
                                    <?php if ($detalle['resultado'] === 'I'): ?>
                                        <span class="badge bg-danger">I - Insuficiente / Falla</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">NA - No aplica</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="mb-4">
        <h5>Todos los parámetros</h5>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Parámetro</th>
                        <th>Resultado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalles as $index => $detalle): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($detalle['parametro']) ?></td>
                            <td>
                                <?php if ($detalle['resultado'] === 'C'): ?>
                                    <span class="badge bg-success">C - Conforme</span>
                                <?php elseif ($detalle['resultado'] === 'I'): ?>
                                    <span class="badge bg-danger">I - Insuficiente / Falla</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">NA - No aplica</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
