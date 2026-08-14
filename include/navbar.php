<?php
include_once(__DIR__ . "/../dbconnection.php");
$email = $_SESSION['username'];
?>

    <nav class="navbar navbar-expand navbar-dark bg-dark static-top">
    <a class="navbar-brand mr-1" href="index.php">
    EQUINOX GOLD
    </a>
    <button class="btn btn-link btn-sm text-white order-1 order-sm-0" id="sidebarToggle">
    <i class="fas fa-bars"></i>
    </button>
    <ul class="navbar-nav ml-auto">

    
        <!-- 👤 Usuario -->
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle user-link" href="#"
               id="userDropdown" role="button" data-toggle="dropdown">
                <i class="fas fa-user-circle fa-fw"></i>
                <span class="nombre-usuario">
                  <?php
$usuario = explode("@", $email)[0];
echo $usuario;
?>
                </span>
            </a>
            <div class="dropdown-menu dropdown-menu-right bg-dark">
                <a class="dropdown-item text-white" href="#">
                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                    Perfil
                </a>
                <a class="dropdown-item text-white" href="#">
                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                    Configuración
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item text-white"
                   href="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/logout.php">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                    Cerrar sesión
                </a>
            </div>
        </li>
    </ul>
</nav>