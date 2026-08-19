        <header class="top-navbar d-flex justify-content-between align-items-center flex-wrap" style="background-color: #24415D; color: white;">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-link btn-sm text-white" id="sidebarToggle" aria-expanded="false" onclick="toggleSidebar()">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <span class="fs-5 fw-semibold text-white"><?= isset($header_title) ? htmlspecialchars($header_title, ENT_QUOTES, 'UTF-8') : 'Panel Admin' ?></span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <?php if (isset($header_actions)): ?>
                    <?= $header_actions ?>
                <?php else: ?>
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2 border-0" type="button" data-bs-toggle="dropdown" data-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle fs-5"></i>
                             <span class="d-none d-md-inline fw-medium "><?= isset($_SESSION['nombre_empleado']) ? htmlspecialchars($_SESSION['nombre_empleado'], ENT_QUOTES, 'UTF-8') : 'Usuario' ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i> Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión</a></li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </header>
