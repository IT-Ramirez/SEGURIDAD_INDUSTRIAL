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
            <a href="index.php" class="nav-link">
                <i class="bi bi-clipboard-data"></i> Inspecciones
            </a>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link">
                <i class="bi bi-file-earmark-plus"></i> Formulario
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
            <a href="staff.php" class="nav-link">
                <i class="bi bi-people"></i> Usuarios
            </a>
        </li>
    </ul>

    <div class="mt-auto p-3 bg-dark text-center border-top border-secondary">
        <small class="text-muted d-block mb-1">Formulario Activo</small>
        <span class="badge bg-warning text-dark">01-FOR-037</span>
    </div>
</nav>
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
    if (window.innerWidth > 992 && sidebar) {
        sidebar.classList.remove('show');
        wrapper.classList.remove('sidebar-open');
    }
});
</script>