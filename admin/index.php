<?php
// conexion.php
$pdo = new PDO("mysql:host=localhost;dbname=checklist;charset=utf8", "root", "Tecnologias11-11");

// Procesar eliminación si se solicita
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM detalles_inspeccion WHERE inspeccion_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM inspecciones WHERE id = ?")->execute([$id]);
    header("Location: admin/index.php");
    exit;
}

// Obtener inspecciones y datos relacionados
$inspecciones = $pdo->query(
    "SELECT i.id, i.nombre_conductor, i.odometro, i.observaciones, i.estado, i.fecha_registro, v.codigo, v.placa
    FROM inspecciones i
    LEFT JOIN vehiculos v ON v.id = i.vehiculo_id
    ORDER BY i.fecha_registro DESC"
)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-3">
<div class="container bg-white p-4 rounded shadow-sm">
    <h3 class="mb-4 text-danger">Administración de Inspecciones</h3>

    <div class="mb-3">
        <a href="../staff/index.php" class="btn btn-secondary">Ir a Staff</a>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Vehículo</th>
                    <th>Conductor</th>
                    <th>Odómetro</th>
                    <th>Estado</th>
                    <th>Creado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($inspecciones)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No hay inspecciones registradas.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($inspecciones as $insp): ?>
                    <tr>
                        <td><?= $insp['id'] ?></td>
                        <td><?= htmlspecialchars($insp['codigo'] . ' - ' . $insp['placa']) ?></td>
                        <td><?= htmlspecialchars($insp['nombre_conductor']) ?></td>
                        <td><?= htmlspecialchars($insp['odometro']) ?></td>
                        <td><?= htmlspecialchars($insp['estado']) ?></td>
                        <td><?= htmlspecialchars($insp['fecha_registro']) ?></td>
                        <td>
                            <a href="view.php?id=<?= $insp['id'] ?>" class="btn btn-sm btn-primary">Ver</a>
                            <a href="?delete=<?= $insp['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Eliminar esta inspección?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
