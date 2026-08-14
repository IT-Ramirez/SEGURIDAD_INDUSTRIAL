<!-- sidebar.php -->
<style>
  .sidebar-equinox {
    min-height: calc(100vh - 56px);
    background-color: #0a1128 !important;
    width: 220px;
    transition: all 0.3s ease;
    border-right: 1px solid rgba(255, 255, 255, 0.05);
  }

  .sidebar-equinox .nav-item .nav-link {
    color: #cbd5e1;
    padding: 12px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 500;
    transition: all 0.2s ease-in-out;
  }

  .sidebar-equinox .nav-item .nav-link i {
    color: #2dfb31 !important;
    font-size: 1.1rem;
  }

  .sidebar-equinox .nav-item .nav-link:hover,
  .sidebar-equinox .nav-item.active .nav-link {
    color: #ffffff;
    background-color: rgba(45, 251, 49, 0.1);
    border-left: 4px solid #2dfb31;
  }

  .sidebar-equinox .dropdown-menu {
    background-color: #1c2541 !important;
    border: 1px solid rgba(45, 251, 49, 0.2);
    border-radius: 6px;
    margin-left: 10px;
  }

  .sidebar-equinox .dropdown-item {
    color: #e2e8f0;
    padding: 8px 16px;
  }

  .sidebar-equinox .dropdown-item:hover {
    background-color: rgba(45, 251, 49, 0.2);
    color: #2dfb31;
  }
</style>

<!-- Sidebar Nav -->
<ul class="sidebar navbar-nav sidebar-equinox p-2">
  <li class="nav-item active">
    <a class="nav-link" href="index.php">
      <i class="fas fa-tachometer-alt"></i>
      <span>Panel Principal</span>
    </a>
  </li>

  <li class="nav-item">
    <a class="nav-link" href="checklist.php">
      <i class="fas fa-tasks"></i>
      <span>Checklists</span>
    </a>
  </li>

  <li class="nav-item">
    <a class="nav-link" href="#">
      <i class="fas fa-table"></i>
      <span>Registros</span>
    </a>
  </li>

  <li class="nav-item">
    <a class="nav-link" href="staff.php">
      <i class="fas fa-user-circle"></i>
      <span>Usuarios</span>
    </a>
  </li>

  <!-- Menú de Ajustes -->
  <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="ajustesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
      <i class="fas fa-wrench"></i>
      <span>Ajustes</span>
    </a>
    <div class="dropdown-menu shadow" aria-labelledby="ajustesDropdown">
      <a class="dropdown-item" href="#">Personalización</a>
      <a class="dropdown-item" href="areas.php">Áreas</a>
      <a class="dropdown-item" href="#">Reportes</a>
      <a class="dropdown-item" href="#">Backup</a>
    </div>
  </li>
</ul>

<!-- Apertura del Contenedor de Contenido Principal -->
<div id="content-wrapper" class="flex-grow-1 bg-light">
  <div class="container-fluid p-4">
