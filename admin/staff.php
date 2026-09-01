<?php 
ob_start(); 
include_once("../session_check.php");
checkRole(['admin']);
include("../functions.php");
require_once('../config.php');
require_once __DIR__ . '/admin_scope.php';

$dsn = "mysql:host=$servername;dbname=$dbname;charset=utf8mb4";
$pdo = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);
$isGlobalAdmin = isGlobalAdmin();
$areaId = $isGlobalAdmin ? null : getAdminAreaId($pdo);
header('Content-Type: text/html; charset=utf-8');

$error = "";
$success = "";

// Capturar mensajes de la URL
if(isset($_GET['msg'])) {
    if($_GET['msg'] == 'added') $success = "✅ Usuario agregado correctamente.";
    if($_GET['msg'] == 'updated') $success = "✅ Usuario actualizado correctamente.";
    if($_GET['msg'] == 'deleted') $success = "🗑️ Usuario eliminado.";
}

if(isset($_POST['addstaff'])){
    $codigo = (int)$_POST['id_empleado'];
    $name = trim($_POST['staffname']);
    $username = strtolower(str_replace(' ', '.', trim($_POST['username']))); 
    $email = trim($_POST['email']); 
    $area = $isGlobalAdmin ? (int)$_POST['area'] : $areaId;
    $role = (int)$_POST['staffrole'];
    $ceco = trim($_POST['ceco']);
    $clasificacion = trim($_POST['clasificacion']);
    $planilla = isset($_POST['planilla']) ? trim($_POST['planilla']) : '';
    $password = password_hash((string)$codigo, PASSWORD_DEFAULT);

    // Verificación de duplicados (userID, Username o Email)
    $check = $sqlconnection->prepare("SELECT COUNT(*) FROM tbl_users WHERE userID=? OR username=? OR email=?");
    $check->bind_param("iss", $codigo, $username, $email);
    $check->execute();
    $check->bind_result($count);
    $check->fetch();
    $check->close();

    if($count > 0){
        $error = "⚠️ El ID de empleado, usuario o email ya se encuentra registrado.";
    } else {
        try {
            $stmt = $sqlconnection->prepare("INSERT INTO tbl_users(userID, nombre_empleado, username, email, id_area, roleID, CECO, password, clasificacion, planilla, status) VALUES(?,?,?,?,?,?,?,?,?,?,'Offline')");
            $stmt->bind_param("isssisssss", $codigo, $name, $username, $email, $area, $role, $ceco, $password, $clasificacion, $planilla);

            if($stmt->execute()){
                header("Location: staff.php?msg=added");
                exit();
            } else {
                $error = "Error al insertar: " . $stmt->error;
            }
        } catch (mysqli_sql_exception $e) {
            $error = "⚠️ Error de base de datos: El código de empleado ya existe o datos no válidos.";
        }
    }
}

if(isset($_POST['updateStaff'])){
    $id = (int)$_POST['staffID'];
    $name = trim($_POST['staffname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']); 
    $area = (int)$_POST['area'];
    $role = (int)$_POST['role'];
    $ceco = trim($_POST['ceco']);
    $password = $_POST['password'];

    if(!empty($password)){
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        if ($isGlobalAdmin) {
            $stmt = $sqlconnection->prepare("UPDATE tbl_users SET nombre_empleado=?, username=?, email=?, id_area=?, roleID=?, CECO=?, password=? WHERE userID=?");
            $stmt->bind_param("sssiissi", $name, $username, $email, $area, $role, $ceco, $hashedPassword, $id);
        } else {
            $stmt = $sqlconnection->prepare("UPDATE tbl_users SET nombre_empleado=?, username=?, email=?, roleID=?, CECO=?, password=? WHERE userID=? AND id_area=?");
            $stmt->bind_param("sssissii", $name, $username, $email, $role, $ceco, $hashedPassword, $id, $areaId);
        }
    } else {
        if ($isGlobalAdmin) {
            $stmt = $sqlconnection->prepare("UPDATE tbl_users SET nombre_empleado=?, username=?, email=?, id_area=?, roleID=?, CECO=? WHERE userID=?");
            $stmt->bind_param("sssiisi", $name, $username, $email, $area, $role, $ceco, $id);
        } else {
            $stmt = $sqlconnection->prepare("UPDATE tbl_users SET nombre_empleado=?, username=?, email=?, roleID=?, CECO=? WHERE userID=? AND id_area=?");
            $stmt->bind_param("sssisii", $name, $username, $email, $role, $ceco, $id, $areaId);
        }
    }

    if($stmt->execute()){
        header("Location: staff.php?msg=updated");
        exit();
    } else {
        $error = "Error al actualizar: " . $stmt->error;
    }
}

if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    if ($isGlobalAdmin) {
        $stmt = $sqlconnection->prepare("DELETE FROM tbl_users WHERE userID=?");
        $stmt->bind_param("i", $id);
    } else {
        $stmt = $sqlconnection->prepare("DELETE FROM tbl_users WHERE userID=? AND id_area=?");
        $stmt->bind_param("ii", $id, $areaId);
    }
    if($stmt->execute()){
        header("Location: staff.php?msg=deleted");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Usuarios - EQX</title>
    <link rel="icon" type="image/png" href="/image/eqx.jpg">
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/sb-admin.css" rel="stylesheet">    
    <link href="../css/stylesmac.css" rel="stylesheet">
    <style>
        :root {
            --eqx-gold: #C59B27;
            --eqx-gold-hover: #A8821D;
            --eqx-dark: #24415D;
            --eqx-gray-dark: #2A2F35;
            --eqx-bg-light: #F4F6F8;
            --sidebar-width: 260px;
        }

        body {
            background-color: var(--eqx-bg-light);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            overflow-x: hidden;
        }

        .wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

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

        .main-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .top-navbar {
            background-color: #ffffff;
            border-bottom: 1px solid #e3e6f0;
            padding: 0.75rem 1.5rem;
        }

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

        .staff-modal .modal-content {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 45px rgba(36, 65, 93, 0.18);
        }

        .staff-modal .modal-header {
            background: linear-gradient(135deg, var(--eqx-dark) 0%, #1c2d3f 100%);
            color: #fff;
            border-bottom: 0;
            padding: 1.25rem 1.5rem;
        }

        .staff-modal .modal-title-wrap {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .staff-modal .modal-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(197, 155, 39, 0.16);
            color: var(--eqx-gold);
            font-size: 1.1rem;
        }

        .staff-modal .modal-header h5 {
            margin: 0;
            font-weight: 700;
        }

        .staff-modal .modal-header p {
            margin: 0.2rem 0 0;
            font-size: 0.82rem;
            color: rgba(255, 255, 255, 0.8);
        }

        .staff-modal .modal-body {
            background: #f8fafc;
            padding: 1.4rem 1.5rem 1rem;
        }

        .staff-modal .form-section {
            background: #fff;
            border: 1px solid #e9edf3;
            border-radius: 14px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .staff-modal .form-section-title {
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--eqx-dark);
            margin-bottom: 0.9rem;
        }

        .staff-modal .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .staff-modal .form-group {
            margin-bottom: 0;
        }

        .staff-modal .form-label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 0.4rem;
        }

        .staff-modal .form-control,
        .staff-modal .custom-select {
            border: 1px solid #dfe7f1;
            border-radius: 10px;
            background: #fff;
            padding: 0.7rem 0.8rem;
            font-size: 0.92rem;
            color: #17212d;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .staff-modal .form-control:focus,
        .staff-modal .custom-select:focus {
            border-color: rgba(197, 155, 39, 0.7);
            box-shadow: 0 0 0 0.2rem rgba(197, 155, 39, 0.12);
        }

        .staff-modal .modal-footer {
            background: #fff;
            border-top: 1px solid #edf2f7;
            padding: 1rem 1.5rem 1.25rem;
            justify-content: space-between;
        }

        .staff-modal .btn-cancel {
            background: #eef2f7;
            color: #334155;
            border: none;
            border-radius: 10px;
            padding: 0.7rem 1rem;
            font-weight: 600;
        }

        .staff-modal .btn-save {
            background: linear-gradient(135deg, var(--eqx-gold) 0%, #a9831d 100%);
            border: none;
            border-radius: 10px;
            padding: 0.7rem 1.4rem;
            font-weight: 700;
            color: #fff;
            box-shadow: 0 8px 18px rgba(197, 155, 39, 0.35);
        }

        .staff-modal .btn-save:hover {
            background: linear-gradient(135deg, #b88b20 0%, #8d6816 100%);
            color: #fff;
        }

        @media (max-width: 768px) {
            .staff-modal .form-grid {
                grid-template-columns: 1fr;
            }
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
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <?php $header_title = 'Configuración de Usuarios'; include 'header.php'; ?>

            <main class="p-4">
                <div class="container-fluid p-0">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <p class="text-muted m-0">Administración y mantenimiento de usuarios del sistema.</p>
                        <button class="btn btn-eqx-gold" data-toggle="modal" data-target="#addStaffModal">
                            <i class="fa fa-plus me-1"></i> Agregar Usuario
                        </button>
                    </div>

                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                    <?php if($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>

                    <div class="card mb-4 shadow-sm border-0 rounded-3">
                        <div class="card-header"><i class="fas fa-user-circle"></i> Lista de Usuarios</div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="staffTable" class="table table-striped table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th># ID</th>
                                            <th>Nombre</th>
                                            <th>Email</th>
                                            <th>Área</th>
                                            <th>CECO</th>
                                            <th>Clasificación</th>
                                            <th>Opciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $usersQuery = "SELECT u.userID, u.nombre_empleado, u.username, u.email, u.id_area, u.roleID, u.clasificacion, u.CECO, a.nombre_area, r.role 
                                                                     FROM tbl_users u 
                                                                     LEFT JOIN tbl_area a ON u.id_area = a.id_area 
                                                                     LEFT JOIN tbl_role r ON u.roleID = r.roleID 
                                                                     WHERE u.username != 'itadmin'" . ($isGlobalAdmin ? "" : " AND u.id_area = ?");
                                        $stmtUsers = $sqlconnection->prepare($usersQuery);
                                        if (!$isGlobalAdmin) $stmtUsers->bind_param("i", $areaId);
                                        $stmtUsers->execute();
                                        $result = $stmtUsers->get_result();
                                        while($row = $result->fetch_assoc()):
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['userID'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($row['nombre_empleado'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($row['nombre_area'] ?? 'Sin Área', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($row['CECO'] ?? 'Sin CECO', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($row['clasificacion'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary edit-btn" 
                                                    data-id="<?php echo $row['userID']; ?>"
                                                    data-nombre="<?php echo htmlspecialchars($row['nombre_empleado'], ENT_QUOTES); ?>"
                                                    data-user="<?php echo htmlspecialchars($row['username'], ENT_QUOTES); ?>"
                                                    data-email="<?php echo htmlspecialchars($row['email'], ENT_QUOTES); ?>"
                                                    data-area="<?php echo $row['id_area']; ?>"
                                                    data-role="<?php echo $row['roleID']; ?>"
                                                    data-ceco="<?php echo htmlspecialchars($row['CECO'] ?? '', ENT_QUOTES); ?>"
                                                    data-toggle="modal" data-target="#editStaffModal">Editar</button>
                                                <a href="?delete=<?php echo $row['userID']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar?')">Eliminar</a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
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

    <div class="modal fade staff-modal" id="addStaffModal" tabindex="-1" aria-labelledby="addStaffModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <div class="modal-title-wrap">
                            <div class="modal-icon"><i class="fa fa-user-plus"></i></div>
                            <div>
                                <h5 id="addStaffModalLabel">Nuevo Usuario</h5>
                                <p>Completa los datos para registrar una nueva cuenta.</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-body">
                        <div class="form-section">
                            <div class="form-section-title">Datos personales</div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label" for="add_id_empleado">ID Empleado</label>
                                    <input type="number" id="add_id_empleado" name="id_empleado" class="form-control" placeholder="Ej. 4567" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="add_staffname">Nombre completo</label>
                                    <input type="text" id="add_staffname" name="staffname" class="form-control" placeholder="Nombre y apellido" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="add_username">Usuario</label>
                                    <input type="text" id="add_username" name="username" class="form-control" placeholder="usuario.nombre" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="add_email">Correo electrónico</label>
                                    <input type="email" id="add_email" name="email" class="form-control" placeholder="correo@empresa.com" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title">Acceso y asignación</div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label" for="add_area">Área</label>
                                    <select id="add_area" name="area" class="form-control" required>
                                        <option value="">Seleccione un área</option>
                                        <?php 
                                        $areas = $isGlobalAdmin
                                            ? $sqlconnection->query("SELECT * FROM tbl_area")
                                            : $sqlconnection->query("SELECT * FROM tbl_area WHERE id_area = {$areaId}");
                                        while($a = $areas->fetch_assoc()) echo "<option value='{$a['id_area']}'>{$a['nombre_area']}</option>"; 
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="add_staffrole">Rol</label>
                                    <select id="add_staffrole" name="staffrole" class="form-control" required>
                                        <option value="">Seleccione un rol</option>
                                        <?php 
                                        $roles = $sqlconnection->query("SELECT * FROM tbl_role");
                                        while($r = $roles->fetch_assoc()) echo "<option value='{$r['roleID']}'>{$r['role']}</option>"; 
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="add_ceco">CECO</label>
                                    <input type="text" id="add_ceco" name="ceco" class="form-control" placeholder="CECO (opcional)">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="add_clasificacion">Clasificación</label>
                                    <select id="add_clasificacion" name="clasificacion" class="form-control">
                                        <option value="Operacion">OPERACIÓN</option>
                                        <option value="Visita">VISITA</option>
                                        <option value="ANXOR INGENIERIA">ANXOR INGENIERIA</option>
                                        <option value="AQUATEC">AQUATEC</option>
                                        <option value="AVIMOR">AVIMOR</option>
                                        <option value="CENTRAL HIDRAULICA">CENTRAL HIDRAULICA</option>
                                        <option value="CONEQUISA">CONEQUISA</option>
                                        <option value="Constructora KPD">Constructora KPD</option>
                                        <option value="CONSTRUMARKET DE NICARAGUA">CONSTRUMARKET DE NICARAGUA</option>
                                        <option value="EL DORADO, S. A.">EL DORADO, S. A.</option>
                                        <option value="GUNNER CRUZ">GUNNER CRUZ</option>
                                        <option value="HG TRANSPORTE">HG TRANSPORTE</option>
                                        <option value="HVASCO S.A.">HVASCO S.A.</option>
                                        <option value="JOHN MAY">JOHN MAY</option>
                                        <option value="KLUANE NICARAGUA">KLUANE NICARAGUA</option>
                                        <option value="LA CASA DEL PERNO, S.A.">LA CASA DEL PERNO, S.A.</option>
                                        <option value="MINPRO">MINPRO</option>
                                        <option value="MULTISERVICIOS METAL MECANICA CIVILES Y ELECTRICOS">MULTISERVICIOS METAL MECANICA CIVILES Y ELECTRICOS</option>
                                        <option value="NICARAGUA INGENIEROS">NICARAGUA INGENIEROS</option>
                                        <option value="ROLSA">ROLSA</option>
                                        <option value="SEMANIC">SEMANIC</option>
                                        <option value="Servicios Mineros de Nicaragua">Servicios Mineros de Nicaragua</option>
                                        <option value="SERVICIOS VARIOS CASTELLON">SERVICIOS VARIOS CASTELLON</option>
                                        <option value="SINSA">SINSA</option>
                                        <option value="SKAVA">SKAVA</option>
                                        <option value="SST">SST</option>
                                        <option value="WILFREDO SORIANO ROSTRAN">WILFREDO SORIANO ROSTRAN</option>
                                        <option value="CORPORACIÓN DE SEGURIDAD INTERNACIONAL, S.A">CORPORACIÓN DE SEGURIDAD INTERNACIONAL, S.A</option>
                                        <option value="GEMCO">GEMCO</option>
                                        <option value="GAUBYSA">GAUBYSA</option>
                                        <option value="MULTISERVICIOS QUINTANILLA">MULTISERVICIOS QUINTANILLA</option>
                                        <option value="Metal Mecanica">Metal Mecanica</option>
                                        <option value="DME">DME</option>
                                        <option value="IMPELCO">IMPELCO</option>
                                        <option value="Valerio Construcciones">Valerio Construcciones</option>
                                        <option value="EXPLOTEC">EXPLOTEC</option>
                                        <option value="NIMAC">NIMAC</option>
                                        <option value="DIMELCO">DIMELCO</option>
                                        <option value="ABELARDO CRUZ BLANCO">ABELARDO CRUZ BLANCO</option>
                                        <option value="Transporte Castro">Transporte Castro</option>
                                        <option value="Bienes y Servicios Martinez">Bienes y Servicios Martinez</option>
                                        <option value="Juan Zapata">Juan Zapata</option>
                                        <option value="Transporte Valdez">Transporte Valdez</option>
                                        <option value="Santa Bárbara">Santa Bárbara</option>
                                        <option value="Transporte Rivera">Transporte Rivera</option>
                                        <option value="VITCAS">VITCAS</option>
                                        <option value="POL FRIO">POL FRIO</option>
                                        <option value="Transporte la Bendición">Transporte la Bendición</option>
                                        <option value="Angel Rodriguez">Angel Rodriguez</option>
                                        <option value="INVERZA">INVERZA</option>
                                        <option value="MECO">MECO</option>
                                        <option value="CRUZ AMPARO MEDINA">CRUZ AMPARO MEDINA</option>
                                        <option value="MASTER DRILLING NICARAGUA">MASTER DRILLING NICARAGUA</option>
                                        <option value="TRITECH NICARAGUA">TRITECH NICARAGUA</option>
                                        <option value="TGI">TGI</option>
                                        <option value="Servicios Varios Gonzalez">Servicios Varios Gonzalez</option>
                                        <option value="ENYELD EMILIO GONZALES PAIZ">ENYELD EMILIO GONZALES PAIZ</option>
                                        <option value="STEELMAX">STEELMAX</option>
                                        <option value="MULTI SERVICIOS INTOCO">MULTI SERVICIOS INTOCO</option>
                                        <option value="INGSERSA">INGSERSA</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-cancel" data-dismiss="modal">Cancelar</button>
                        <button type="submit" name="addstaff" class="btn btn-save">Guardar Usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editStaffModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header"><h5>Editar Usuario</h5></div>
                    <div class="modal-body">
                        <input type="hidden" name="staffID" id="edit_id">
                        <label>Nombre</label>
                        <input type="text" name="staffname" id="edit_nombre" class="form-control mb-2" required>
                        <label>Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control mb-2" required>
                        <label>Usuario</label>
                        <input type="text" name="username" id="edit_user" class="form-control mb-2" required>
                        <label>Área</label>
                        <select name="area" id="edit_area" class="form-control mb-2">
                            <?php 
                            $areas = $isGlobalAdmin
                                ? $sqlconnection->query("SELECT * FROM tbl_area")
                                : $sqlconnection->query("SELECT * FROM tbl_area WHERE id_area = {$areaId}");
                            while($a = $areas->fetch_assoc()) echo "<option value='{$a['id_area']}'>{$a['nombre_area']}</option>"; 
                            ?>
                        </select>
                        <label>Rol</label>
                        <select name="role" id="edit_role" class="form-control mb-2">
                            <option value="">Seleccione un rol</option>
                            <?php 
                            $roles = $sqlconnection->query("SELECT * FROM tbl_role");
                            while($r = $roles->fetch_assoc()) echo "<option value='{$r['roleID']}'>{$r['role']}</option>"; 
                            ?>
                        </select>
                        <label>CECO (Opcional)</label>
                        <input type="text" name="ceco" id="edit_ceco" class="form-control mb-2">
                      <label>Contraseña (opcional)</label>
                      <input type="password" name="password" class="form-control" autocomplete="new-password">
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="updateStaff" class="btn btn-primary">Actualizar</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
    
    <script>
    $(document).ready(function(){
        $('#staffTable').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' }
        });

        $('.edit-btn').on('click', function(){
            $('#edit_id').val($(this).data('id'));
            $('#edit_nombre').val($(this).data('nombre'));
            $('#edit_user').val($(this).data('user'));
            $('#edit_email').val($(this).data('email')); 
            $('#edit_area').val($(this).data('area'));
            $('#edit_role').val($(this).data('role'));
            $('#edit_ceco').val($(this).data('ceco'));
        });
    });

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
    </script>
</body>
</html>
<?php 
ob_end_flush(); 
?>