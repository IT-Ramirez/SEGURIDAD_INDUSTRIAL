<?php
session_start();
include_once '../session_check.php';
require_once '../config.php';

// 2. Pass the variables DIRECTLY without quotes around them
$dsn = "mysql:host=$servername;dbname=$dbname;charset=utf8mb4";
$pdo = new PDO($dsn, $username, $password);

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
    LEFT JOIN vehiculos v ON v.id = i.codigo_vehiculo WHERE i.userID = {$_SESSION['uid']}
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
            background-color: #24415D;
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
            background-color: #24415D;
            border-top: 1px solid #e3e6f0;
            padding: 1rem 1.5rem;
            margin-top: auto;
            text-align: center;
            color: white;
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

        #sidebarToggle { display: none; }

        @media (max-width: 992px) {
            #sidebar { 
                position: fixed; 
                left: -260px; 
                height: 100vh; 
                top: 0; 
                z-index: 1050; 
                box-shadow: 2px 0 5px rgba(0,0,0,0.1);
                transition: left 0.3s ease;
            }
            #sidebar.show { left: 0; }
            .wrapper::before { 
                content: ''; 
                position: fixed; 
                top: 0; 
                left: 0; 
                width: 100%; 
                height: 100%; 
                background: rgba(0,0,0,0.5); 
                display: none; 
                z-index: 1040; 
            }
            .wrapper.sidebar-open::before { display: block; }
            #sidebarToggle { display: block !important; }
        }

        @media (max-width: 768px) {
            .top-navbar { 
                padding: 0.5rem 1rem; 
                flex-wrap: wrap;
                gap: 1rem;
            }
            main { padding: 1rem !important; }
            .table-responsive { font-size: 0.9rem; }
        }

        @media (max-width: 576px) {
            .top-navbar { 
                padding: 0.4rem 0.75rem; 
                gap: 0.5rem;
            }
            main { padding: 0.75rem !important; }
            .card { border-radius: 0.5rem !important; }
            .table-responsive { font-size: 0.8rem; }
        }
    </style>
</head>
<body>

<div class="wrapper">

    <!-- MAIN CONTENT AREA -->
    <div class="main-content">
        
        <!-- NAVBAR SUPERIOR -->
        <?php include 'header.php'; ?>

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
                                                <?php if ($insp['estado'] === 'APTO PARA CONDUCIR'): ?>
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

        <?php include 'footer.php'; ?>

    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Toggle Sidebar
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const wrapper = document.querySelector('.wrapper');

if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function(e) {
        e.preventDefault();
        sidebar.classList.toggle('show');
        wrapper.classList.toggle('sidebar-open');
    });
}

// Cerrar sidebar cuando se hace click en un link
const sidebarLinks = sidebar ? sidebar.querySelectorAll('.nav-link') : [];
sidebarLinks.forEach(link => {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 992) {
            sidebar.classList.remove('show');
            wrapper.classList.remove('sidebar-open');
        }
    });
});

// Cerrar sidebar al hacer click fuera
document.addEventListener('click', function(e) {
    if (window.innerWidth <= 992 && sidebar && sidebar.classList.contains('show')) {
        if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
            sidebar.classList.remove('show');
            wrapper.classList.remove('sidebar-open');
        }
    }
});

// Ajustar sidebar al cambiar el tamaño de la ventana
window.addEventListener('resize', function() {
    if (window.innerWidth > 992) {
        sidebar.classList.remove('show');
        wrapper.classList.remove('sidebar-open');
    }
});
</script>
</body>
</html>