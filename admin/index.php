<?php
require_once '../config.php';
include_once("../session_check.php");
checkRole(['admin']);
include('../functions.php');

// Validar inicio de sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dsn = "mysql:host=$servername;dbname=$dbname;charset=utf8mb4";
$pdo = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// Procesar eliminación de manera segura con transacciones
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    try {
        $pdo->beginTransaction();

        $stmtDetalles = $pdo->prepare("DELETE FROM detalles_inspeccion WHERE inspeccion_id = ?");
        $stmtDetalles->execute([$id]);

        $stmtInspeccion = $pdo->prepare("DELETE FROM inspecciones WHERE id = ?");
        $stmtInspeccion->execute([$id]);

        $pdo->commit();
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error al eliminar la inspección: " . $e->getMessage());
    }
}

// Obtener TODAS las inspecciones (vista general para el Administrador)
$stmt = $pdo->prepare(
    "SELECT i.id, i.nombre_conductor, i.odometro, i.observaciones, i.estado, i.fecha_registro, v.codigo, i.placa
     FROM inspecciones i
     LEFT JOIN vehiculos v ON v.id = i.codigo_vehiculo
     ORDER BY i.fecha_registro DESC"
);

$stmt->execute();
$inspecciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            --eqx-dark: #24415D;
            --eqx-gray-dark: #24415D;
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
    <!-- 1. SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT AREA -->
    <div class="main-content">
        
        <!-- 2. NAVBAR SUPERIOR -->
        <?php $header_title = 'Administración de Inspecciones'; include 'header.php'; ?>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="p-4">
            <div class="container-fluid p-0">
                
                <!-- Acciones Rápidas / Filtros -->
                <div class="d-flex justify-content-between align-items-center gap-3 mb-4 flex-wrap">
                    <p class="text-muted m-0">Registro y estado del checklist diario de toda la flota.</p>
                    <div class="d-flex align-items-center gap-2 flex-grow-1 justify-content-end" style="max-width: 520px; min-width: 240px;">
                        <input type="text" id="adminSearch" class="form-control form-control-sm" placeholder="Buscar por placa o conductor" aria-label="Buscar por placa o conductor" style="max-width: 260px;">
                        <a href="exportar_excel.php" class="btn btn-outline-success btn-sm" title="Exportar inspecciones a Excel">
                            <i class="bi bi-file-earmark-excel me-1"></i> Exportar Excel
                        </a>
                        <a href="formulario.php" class="btn btn-eqx-gold btn-sm">
                            <i class="bi bi-plus-circle me-1"></i> Nueva Inspección
                        </a>
                    </div>
                </div>

                <!-- Tarjeta Principal con Tabla -->
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="inspeccionesTable" class="table table-hover align-middle mb-0 table-eqx">
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
                                            <td class="ps-3 fw-bold">#<?= htmlspecialchars($insp['id']) ?></td>
                                            <td>
                                                <span class="fw-semibold text-dark"><?= htmlspecialchars($insp['codigo'] ?? 'N/A') ?></span>
                                                <small class="text-muted d-block"><?= htmlspecialchars($insp['placa'] ?? '') ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($insp['nombre_conductor']) ?></td>
                                            <td><?= number_format((float)$insp['odometro']) ?> KM</td>
                                            <td>
                                                <?php if ($insp['estado'] === 'APTO PARA CONDUCIR'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">APTO PARA CONDUCIR</span>
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
                            <div id="paginationContainer" class="d-flex justify-content-between align-items-center mt-3 px-3 pb-3 gap-2 flex-wrap"></div>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <!-- 3. FOOTER -->
        <?php include 'footer.php'; ?>

    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
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

const adminSearch = document.getElementById('adminSearch');
const tableRows = () => [...document.querySelectorAll('#inspeccionesTable tbody tr')];
const tbody = document.querySelector('#inspeccionesTable tbody');
const paginationContainer = document.getElementById('paginationContainer');
const rowsPerPage = 8;
let currentPage = 1;

function renderPagination(filteredRows) {
    if (!paginationContainer) return;

    const totalPages = Math.max(1, Math.ceil(filteredRows.length / rowsPerPage));
    if (totalPages <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }

    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    const pageRows = filteredRows.slice(start, end);

    const prevDisabled = currentPage === 1 ? 'disabled' : '';
    const nextDisabled = currentPage === totalPages ? 'disabled' : '';

    paginationContainer.innerHTML = `
        <div class="text-muted small">Mostrando ${pageRows.length ? start + 1 : 0}-${Math.min(start + pageRows.length, filteredRows.length)} de ${filteredRows.length}</div>
        <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-outline-secondary ${prevDisabled}" data-page="prev">Anterior</button>
            <button type="button" class="btn btn-outline-secondary ${nextDisabled}" data-page="next">Siguiente</button>
        </div>
    `;

    paginationContainer.querySelector('[data-page="prev"]').addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            applyTableFilter();
        }
    });

    paginationContainer.querySelector('[data-page="next"]').addEventListener('click', () => {
        if (currentPage < totalPages) {
            currentPage++;
            applyTableFilter();
        }
    });
}

function applyTableFilter() {
    const query = adminSearch ? adminSearch.value.trim().toLowerCase() : '';
    const rows = tableRows();
    const filtered = rows.filter(row => {
        if (row.querySelector('td[colspan]')) return true;
        const rowText = row.textContent.toLowerCase();
        return !query || rowText.includes(query);
    });

    rows.forEach(row => {
        row.style.display = 'none';
    });

    const emptyState = document.querySelector('.empty-search-row');
    if (emptyState) emptyState.remove();

    if (filtered.length === 0) {
        const emptyRow = document.createElement('tr');
        emptyRow.className = 'empty-search-row';
        emptyRow.innerHTML = '<td colspan="7" class="text-center py-4 text-muted">No se encontraron resultados.</td>';
        tbody.appendChild(emptyRow);
        paginationContainer.innerHTML = '';
        return;
    }

    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    filtered.slice(start, end).forEach(row => {
        row.style.display = '';
    });

    renderPagination(filtered);
}

if (adminSearch) {
    adminSearch.addEventListener('input', function() {
        currentPage = 1;
        applyTableFilter();
    });
}

applyTableFilter();
</script>

</body>
</html>