<div class="top-navbar-wrapper">
    <div class="top-navbar">
        <div class="navbar-left">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-home"></i> EQX - EQUINOX GOLD
            </a>
        </div>
        
        <div class="navbar-center">
            <span class="navbar-username">Invitado</span>
        </div>
        
        <div class="navbar-right">
            <!-- Icono de notificaciones -->
            <div class="navbar-item dropdown">
                <button class="navbar-icon" id="notificationsDropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                    <span class="badge-counter">0</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                    <h6 class="dropdown-header">Notificaciones</h6>
                    <a class="dropdown-item" href="#">Sin notificaciones</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#">Ver todas</a>
                </div>
            </div>
            
            <!-- Icono de usuario -->
            <div class="navbar-item dropdown">
                <button class="navbar-icon" id="userDropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user-circle"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Perfil</a>
                    <a class="dropdown-item" href="#"><i class="fas fa-cogs me-2"></i>Configuración</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar sesión</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script para la interacción con servidor vía AJAX -->
<script>
    // Marcar notificaciones como leídas al hacer clic en el desplegable
    document.getElementById('notificationsDropdown').addEventListener('click', function() {
        fetch('marcar_notificaciones.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Notificaciones marcadas como leídas.');
        })
        .catch(error => console.error('Error:', error));
    });

    // Actualización del contador en tiempo real cada 5 segundos
    setInterval(() => {
        fetch('obtener_contador.php')
            .then(response => response.json())
            .then(data => {
                document.querySelector('.badge-counter').textContent = data.total_no_leidas;
            })
            .catch(error => console.error('Error al obtener el contador:', error));
    }, 5000);
</script>