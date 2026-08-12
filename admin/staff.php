<?php 
ob_start(); 
include_once("../session_check.php");
checkRole(['admin']);
include("../functions.php");

$error = "";
$success = "";

// Capturar mensajes de la URL
if(isset($_GET['msg'])) {
    if($_GET['msg'] == 'added') $success = "✅ Usuario agregado correctamente.";
    if($_GET['msg'] == 'updated') $success = "✅ Usuario actualizado correctamente.";
    if($_GET['msg'] == 'deleted') $success = "🗑️ Usuario eliminado.";
}

if(isset($_POST['addstaff'])){
    $codigo = $_POST['id_empleado'];
    $name = $_POST['staffname'];
    $username = strtolower(str_replace(' ', '.', $_POST['username'])); 
    $email = $_POST['email']; 
    $area = (int)$_POST['area'];
    $role = (int)$_POST['staffrole'];
    $ceco = $_POST['ceco'];
    $clasificacion = $_POST['clasificacion'];
    $planilla = $_POST['planilla'];
    $password = password_hash((string)$codigo, PASSWORD_DEFAULT);

    // Verificación duplicados (Username o Email)
    $check = $sqlconnection->prepare("SELECT COUNT(*) FROM tbl_users WHERE username=? OR email=?");
    $check->bind_param("ss", $username, $email);
    $check->execute();
    $check->bind_result($count);
    $check->fetch();
    $check->close();

    if($count > 0){
        $error = "⚠️ El usuario o el email ya existen.";
    } else {
        $stmt = $sqlconnection->prepare("INSERT INTO tbl_users(userID, nombre_empleado, username, email, id_area, roleID, CECO, password, clasificacion, planilla, status) VALUES(?,?,?,?,?,?,?,?,?,?,'Offline')");
        $stmt->bind_param("isssisssss", $codigo, $name, $username, $email, $area, $role, $ceco, $password, $clasificacion, $planilla);

        if($stmt->execute()){
            header("Location: staff.php?msg=added");
            exit();
        } else {
            $error = "Error al insertar: " . $stmt->error;
        }
    }
}

if(isset($_POST['updateStaff'])){
    $id = (int)$_POST['staffID'];
    $name = $_POST['staffname'];
    $username = $_POST['username'];
    $email = $_POST['email']; 
    $area = (int)$_POST['area'];
    $role = (int)$_POST['role'];
    $ceco = $_POST['ceco'];
    $password = $_POST['password'];

    if(!empty($password)){
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $sqlconnection->prepare("UPDATE tbl_users SET nombre_empleado=?, username=?, email=?, id_area=?, roleID=?, CECO=?, password=? WHERE userID=?");
        $stmt->bind_param("sssiissi", $name, $username, $email, $area, $role, $ceco, $hashedPassword, $id);
    } else {
        $stmt = $sqlconnection->prepare("UPDATE tbl_users SET nombre_empleado=?, username=?, email=?, id_area=?, roleID=?, CECO=? WHERE userID=?");
        $stmt->bind_param("sssiisi", $name, $username, $email, $area, $role, $ceco, $id);
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
    $stmt = $sqlconnection->prepare("DELETE FROM tbl_users WHERE userID=?");
    $stmt->bind_param("i", $id);
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
    <link href="css/sb-admin.css" rel="stylesheet">    
    <link href="../css/stylesmac.css" rel="stylesheet">
</head>
<body id="page-top">
    <?php include_once('../include/navbar.php'); ?>
    <div id="wrapper">
        <?php include_once('../include/sidebar.php'); ?>
        <div id="content-wrapper">
            <div class="container-fluid">
                <h1 class="mb-4">Configuración de Usuarios</h1>

                <button class="btn btn-success mb-3" data-toggle="modal" data-target="#addStaffModal">
                    <i class="fa fa-plus"></i> Agregar Usuario
                </button>

                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="card mb-4 shadow-sm">
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
                                    $result = $sqlconnection->query("SELECT u.userID, u.nombre_empleado, u.username, u.email, u.id_area, u.roleID, u.clasificacion, a.nombre_area, a.CECO, r.role 
                                                                     FROM tbl_users u 
                                                                     LEFT JOIN tbl_area a ON u.id_area = a.id_area 
                                                                     LEFT JOIN tbl_role r ON u.roleID = r.roleID 
                                                                     WHERE u.username != 'itadmin'");
                                    while($row = $result->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td><?php echo $row['userID']; ?></td>
                                        <td><?php echo htmlspecialchars($row['nombre_empleado']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nombre_area'] ?? 'Sin Área'); ?></td>
                                        <td><?php echo htmlspecialchars($row['CECO'] ?? 'Sin CECO'); ?></td>
                                        <td><?php echo htmlspecialchars($row['clasificacion'] ?? ''); ?></td>
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
                        </div> </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addStaffModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header"><h5>Nuevo Usuario</h5></div>
                    <div class="modal-body">
                        <input type="number" name="id_empleado" class="form-control mb-2" placeholder="ID Empleado" required>
                        <input type="text" name="staffname" class="form-control mb-2" placeholder="Nombre Completo" required>
                        <input type="text" name="username" class="form-control mb-2" placeholder="Username" required>
                        <input type="email" name="email" class="form-control mb-2" placeholder="Email" required>
                        <select name="area" class="form-control mb-2" required>
                            <?php 
                            $areas = $sqlconnection->query("SELECT * FROM tbl_area");
                            while($a = $areas->fetch_assoc()) echo "<option value='{$a['id_area']}'>{$a['nombre_area']}</option>"; 
                            ?>
                        </select>
                        <select name="staffrole" class="form-control mb-2" required>
                            <?php 
                            $roles = $sqlconnection->query("SELECT * FROM tbl_role");
                            while($r = $roles->fetch_assoc()) echo "<option value='{$r['roleID']}'>{$r['role']}</option>"; 
                            ?>
                        </select>
                        <input type="text" name="ceco" class="form-control mb-2" placeholder="CECO" required>
                        <select name="clasificacion" class="form-control mb-2">
                            <option value="Operacion">OPERACIÓN</option>
                            <option value="Visita">VISITA</option>
                        </select>
                        <input type="text" name="planilla" class="form-control" placeholder="Planilla" required>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="addstaff" class="btn btn-success">Guardar</button>
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
                            $areas = $sqlconnection->query("SELECT * FROM tbl_area");
                            while($a = $areas->fetch_assoc()) echo "<option value='{$a['id_area']}'>{$a['nombre_area']}</option>"; 
                            ?>
                        </select>
                        <label>Rol</label>
                        <select name="role" id="edit_role" class="form-control mb-2">
                            <?php 
                            $roles = $sqlconnection->query("SELECT * FROM tbl_role");
                            while($r = $roles->fetch_assoc()) echo "<option value='{$r['roleID']}'>{$r['role']}</option>"; 
                            ?>
                        </select>
                        <label>CECO</label>
                        <input type="text" name="ceco" id="edit_ceco" class="form-control mb-2">
                        <label>Contraseña (opcional)</label>
                        <input type="password" name="password" class="form-control">
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
    </script>
</body>
</html>
<?php 
ob_end_flush(); 
?>
