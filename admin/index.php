<?php
// conexion.php
$pdo = new PDO("mysql:host=localhost;dbname=checklist;charset=utf8", "root", "Tecnologias11-11");

// Procesar eliminación si se solicita
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM detalles_inspeccion WHERE inspeccion_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM inspecciones WHERE id = ?")->execute([$id]);
    header("Location: index.php");
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
    <title>Panel Admin - Equinox Gold</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
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

        body {
            background-color: var(--eqx-bg-light);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            overflow-x: hidden;
        }

        /* Layout Main Layout */
        .wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        #sidebar {
            width: var(--sidebar-width);
            min-width: var(--sidebar-width);
            background-color: var(--eqx-dark);
            color: #ffffff;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            z-index: 1000;
        }

        #sidebar .sidebar-header {
            padding: 1.25rem 1.5rem;
            background-color: rgba(0, 0, 0, 0.2);
            border-bottom: 2px solid var(--eqx-gold);
        }

        #sidebar .nav-link {
            color: #c2c7d0;
            padding: 0.8rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            transition: all 0.2s;
        }

        #sidebar .nav-link:hover, #sidebar .nav-link.active {
            color: #ffffff;
            background-color: var(--eqx-gray-dark);
            border-left: 4px solid var(--eqx-gold);
        }

        /* Main Content Container */
        .main-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        /* Top Navbar */
        .top-navbar {
            background-color: #ffffff;
            border-bottom: 1px solid #e3e6f0;
            padding: 0.75rem 1.5rem;
        }

        /* Table Customizations */
        .table-eqx thead {
            background-color: var(--eqx-dark);
            color: #ffffff;
        }

        /* Footer */
        .footer {
            background-color: #ffffff;
            border-top: 1px solid #e3e6f0;
            padding: 1rem 1.5rem;
            margin-top: auto;
        }

        .btn-eqx-gold {
            background-color: var(--eqx-gold);
            color: #ffffff;
            border: none;
            font-weight: 500;
        }

        .btn-eqx-gold:hover {
            background-color: var(--eqx-gold-hover);
            color: #ffffff;
        }
    </style>
</head>
<body>

<div class="wrapper">
    <!-- 1. SIDEBAR -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <h5 class="fw-bold mb-0 text-white"><i class="bi bi-shield-check text-warning"></i> EQUINOX GOLD</h5>
            <small class="text-muted text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">Control Vehicular</small>
        </div>

        <ul class="nav flex-column my-3">
            <li class="nav-item">
                <a href="#" class="nav-link active">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="bi bi-clipboard-data"></i> Inspecciones
                </a>
            </li>
            <li class="nav-item">
                <a href="../staff/index.php" class="nav-link">
                    <i class="bi bi-file-earmark-plus"></i> Formulario Staff
                </a>
            </li>
            <li class="nav-item mt-4">
                <span class="px-3 text-uppercase text-muted small fw-bold">Configuración</span>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="bi bi-truck"></i> Vehículos
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="bi bi-people"></i> Usuarios
                </a>
            </li>
        </ul>

        <div class="mt-auto p-3 bg-dark text-center border-top border-secondary">
            <small class="text-muted d-block mb-1">Formulario Activo</small>
            <span class="badge bg-warning text-dark">01-FOR-037</span>
        </div>
    </nav>

    <!-- MAIN CONTENT AREA -->
    <div class="main-content">
        
        <!-- 2. NAVBAR SUPERIOR -->
        <header class="top-navbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <span class="fs-5 fw-semibold text-dark">Administración de Inspecciones</span>
            </div>

            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2 border-0" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle fs-5 text-secondary"></i>
                    <span class="d-none d-md-inline fw-medium">Administrador</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i> Perfil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#"><i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión</a></li>
                </ul>
            </div>
        </header>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="p-4">
            <div class="container-fluid p-0">
                
                <!-- Acciones Rápidas / Filtros -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <p class="text-muted m-0">Registro y estado del checklist diario de la flota.</p>
                    <a href="../staff/index.php" class="btn btn-eqx-gold">
                        <i class="bi bi-plus-circle me-1"></i> Nueva Inspección
                    </a>
                </div>

                <!-- Tarjeta Principal con Tabla -->
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 table-eqx">
                                <thead>
                                    <tr>
                                        <th class="ps-3 py-3">ID</th>
                                        <th>Vehículo / Placa</th>
                                        <th>Conductor</th>
                                        <th>Odómetro</th>
                                        <th>Estado</th>
                                        <th>Fecha Registro</th>
                                        <th class="text-center pe-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($inspecciones)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">No hay inspecciones registradas.</td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php foreach ($inspecciones as $insp): ?>
                                        <tr>
                                            <td class="ps-3 fw-bold">#<?= $insp['id'] ?></td>
                                            <td>
                                                <span class="fw-semibold text-dark"><?= htmlspecialchars($insp['codigo'] ?? 'N/A') ?></span>
                                                <small class="text-muted d-block"><?= htmlspecialchars($insp['placa'] ?? '') ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($insp['nombre_conductor']) ?></td>
                                            <td><?= number_format($insp['odometro']) ?> KM</td>
                                            <td>
                                                <?php if ($insp['estado'] === 'Aprobado'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Aprobado</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Con Fallas</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($insp['fecha_registro'])) ?></small>
                                            </td>
                                            <td class="text-center pe-3">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="view.php?id=<?= $insp['id'] ?>" class="btn btn-outline-primary" title="Ver detalles">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="?delete=<?= $insp['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('¿Eliminar esta inspección?');" title="Eliminar">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <!-- 3. FOOTER -->
        <footer class="footer d-flex justify-content-between align-items-center">
            <small class="text-muted">© <?= date('Y') ?> <strong>Equinox Gold</strong>. Todos los derechos reservados.</small>
            <small class="text-muted">Sistema Checklist v1.0</small>
        </footer>

    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>