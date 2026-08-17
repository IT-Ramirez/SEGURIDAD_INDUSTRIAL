<!-- TOP NAVBAR HEADER -->
<header class="top-navbar d-flex justify-content-between align-items-center flex-wrap">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-link btn-sm text-white" id="sidebarToggle" href="#" style="display:none;">
            <i class="fas fa-bars fs-5"></i>
        </button>
        <span class="fs-5 fw-semibold text-white">Checklist Vehículos</span>
    </div>

    <div class="dropdown">
        <button class="btn btn-outline-light dropdown-toggle d-flex align-items-center gap-2 border-0" type="button" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle fs-5 text-white"></i>
            <span class="d-none d-md-inline fw-medium text-white"><?= isset($_SESSION['nombre']) ? htmlspecialchars($_SESSION['nombre']) : 'Usuario' ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i> Perfil</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión</a></li>
        </ul>
    </div>
</header>
