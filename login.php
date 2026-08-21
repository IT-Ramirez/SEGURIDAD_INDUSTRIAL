<?php 
include("functions.php");

$message = ""; 
// --- PROCESAMIENTO DEL REGISTRO (MODAL) ---
if(isset($_POST['addstaff'])){
    $codigo = $_POST['id_empleado'];
    $name = $_POST['staffname'];
    $email_full = $_POST['email']; 
    $area = (int)$_POST['area'];
    $ceco = $_POST['ceco'] ?? '';
    $clasificacion = $_POST['clasificacion'];
    $planilla = $_POST['planilla'] ?? '';
    
    $parts = explode('@', $email_full);
    $username = $parts[0]; 

    $password = password_hash((string)$codigo, PASSWORD_DEFAULT);
    
    // --- NUEVA VALIDACIÓN: Verificar si el código de empleado ya existe ---
    $check_code = $sqlconnection->prepare("SELECT COUNT(*) FROM tbl_users WHERE userID=?");
    $check_code->bind_param("i", $codigo);
    $check_code->execute();
    $check_code->bind_result($count_code);
    $check_code->fetch();
    $check_code->close();

    if($count_code > 0){
        $message = "<div class='alert alert-danger border-0 shadow-sm animate__animated animate__shakeX'>
                        <i class='fas fa-exclamation-triangle'></i> El ID de empleado <strong>$codigo</strong> ya existe.
                    </div>";
    } else {
        // --- VALIDACIÓN ORIGINAL DEL CORREO ---
        $check = $sqlconnection->prepare("SELECT COUNT(*) FROM tbl_users WHERE email=?");
        $check->bind_param("s", $email_full);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->close();

        if($count > 0){
            $message = "<div class='alert alert-danger border-0 shadow-sm animate__animated animate__shakeX'>
                            <i class='fas fa-exclamation-triangle'></i> El correo <strong>$email_full</strong> ya existe.
                        </div>";
        } else {
            $stmt = $sqlconnection->prepare("INSERT INTO tbl_users(userID, nombre_empleado, username, email, id_area, roleID, CECO, password, clasificacion, planilla, status) VALUES(?,?,?,?,?, 3,?,?,?,?,'Offline')");
            $stmt->bind_param("isssissss", $codigo, $name, $username, $email_full, $area, $ceco, $password, $clasificacion, $planilla);

            if($stmt->execute()){
                header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
                exit();
            } else {
                $message = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>SERVIAPP | Equinox Gold</title>
  <!-- 2. Meta Descripción (Aparece en gris en Google) -->
  <meta name="description" content="Plataforma oficial de SERVIAPP para Equinox Gold. Accede al sistema de gestión de servicios, control de comedor y Reservas de Habitaciones">
  <meta name="keywords" content="Serviapp, Equinox Gold, Comedor, Gestión de Servicios">
  <!-- 4. Meta etiquetas Open Graph (Para cuando compartes el enlace por WhatsApp/Redes) -->
  <meta property="og:title" content="SERVIAPP - Equinox Gold">
  <meta property="og:description" content="Plataforma de gestión de servicios y registros de campamento para Equinox Gold.">
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://eqxserviapp.com/">



    <title>EQX | LOGIN</title>
    <link rel="icon" type="image/png" href="/image/eqx.jpg">
    <link href="./admin/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="./admin/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    
    <style>
        :root {
            --primary-blue: #21415D;
            --accent-gold: #d4a30e;
            --glass-bg: rgba(255, 255, 255, 0.9);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body { 
            background: linear-gradient(rgba(33, 65, 93, 0.7), rgba(33, 65, 93, 0.7)), url(./image/Equinox.jpg); 
            background-size: cover; 
            background-position: center; 
            background-attachment: fixed;
            height: 100vh; 
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        /* Glassmorphism Card */
        .card-login { 
            background: var(--glass-bg); 
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 24px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 420px;
            padding: 2rem;
            animation: fadeInUp 0.8s ease-out;
        }

        .brand-logo { width: 140px; margin-bottom: 1rem; }

        .brand-text {
            font-size: 1.5rem;
            font-weight: 600;
            letter-spacing: 1px;
            margin-bottom: 2rem;
        }

        /* Form Styling */
        .form-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--primary-blue);
            margin-left: 5px;
        }

        .input-group {
            background: white;
            border-radius: 12px;
            border: 1.5px solid #e1e5eb;
            transition: var(--transition);
            overflow: hidden;
        }

        .input-group:focus-within {
            border-color: var(--accent-gold);
            box-shadow: 0 0 0 4px rgba(212, 163, 14, 0.15);
        }

        .input-group-text {
            background: transparent;
            border: none;
            color: #adb5bd;
            padding-left: 15px;
        }

        .form-control {
            border: none;
            height: 50px;
            font-size: 0.95rem;
        }

        .form-control:focus {
            box-shadow: none;
            background: transparent;
        }

        /* Password Toggle */
        .toggle-password {
            cursor: pointer;
            padding: 15px;
            color: #6c757d;
            transition: var(--transition);
        }
        .toggle-password:hover { color: var(--primary-blue); }

        /* Buttons */
        .btn-primary-custom { 
            background: var(--primary-blue); 
            color: #fff; 
            border: none; 
            height: 55px;
            border-radius: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: var(--transition);
        }

        .btn-primary-custom:hover:not(:disabled) {
            background: #1a344a;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(33, 65, 93, 0.3);
        }

        .btn-primary-custom:disabled { opacity: 0.8; cursor: not-allowed; }

        .btn-register-link {
            color: var(--primary-blue);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
        }

        .btn-register-link:hover { color: var(--accent-gold); text-decoration: none; }

        /* Modals */
        .modal-content { border-radius: 20px; border: none; }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="card-login shadow">
        <div class="text-center">
          <img src="./image/equinox.png" class="brand-logo" alt="Equinox Gold" style="width: 80px; height: auto;">
            <div class="brand-text">
                <span style="color: var(--primary-blue);">SERVI</span><span style="color: var(--accent-gold);">APP</span>
            </div>
        </div>
        <?php 
            if($message) echo $message; 
            if(isset($_GET['success'])) echo "<div class='alert alert-success border-0 shadow-sm animate__animated animate__fadeIn'>✅ ¡Registro exitoso! Ya puedes ingresar.</div>";
        ?>
        <form id="loginform">
            <div class="form-group mb-4">
                <label>Correo Electrónico</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    </div>
                    <input type="email" id="inputemail" class="form-control" placeholder="usuario@equinoxgold.com" required autofocus>
                </div>
            </div>

            <div class="form-group mb-4">
                <label>Contraseña / ID Empleado</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    </div>
                    <input type="password" id="inputPassword" class="form-control" placeholder="Ingrese su ID" required>
                    <div class="input-group-append">
                        <span class="toggle-password" id="eyeIcon">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
            </div>

            <div id="warningbox" style="display:none;" class="mb-3"></div>

            <button type="submit" id="btnLogin" class="btn btn-block btn-primary-custom mb-4">
                Ingresar al Sistema
            </button>

            <div class="text-center small" hidden>
                <span class="text-muted">¿Eres nuevo en la unidad?</span><br>
                <a href="#" class="btn-register-link" data-toggle="modal" data-target="#addStaffModal">
                    Registrarme ahora
                </a>
            </div>
        </form>
    </div>

    <div class="modal fade" id="addStaffModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title font-weight-bold" style="color: var(--primary-blue)">Registro de Usuario</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-group">
                            <label class="text-muted small">ID EMPLEADO (Será su contraseña)</label>
                            <input type="number" name="id_empleado" class="form-control bg-light" placeholder="Ej: 12345" required style="border: 1px solid #ddd">
                        </div>
                        <div class="form-group">
                            <label class="text-muted small">NOMBRE COMPLETO</label>
                            <input type="text" name="staffname" class="form-control bg-light" placeholder="Ingrese su nombre completo" required style="border: 1px solid #ddd">
                        </div>
                        <div class="form-group">
                            <label class="text-muted small">CORREO CORPORATIVO</label>
                            <input type="email" name="email" class="form-control bg-light" placeholder="Ej: usuario@equinoxgold.com" required style="border: 1px solid #ddd">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="text-muted small">ÁREA</label>
                                    <select name="area" class="form-control bg-light" required style="border: 1px solid #ddd">
                                        <option value="">Seleccione...</option>
                                        <?php 
                                        $areas = $sqlconnection->query("SELECT * FROM tbl_area");
                                        while($a = $areas->fetch_assoc()) echo "<option value='{$a['id_area']}'>{$a['nombre_area']}</option>";
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="text-muted small">CLASIFICACIÓN</label>
                                    <select name="clasificacion" class="form-control bg-light" required style="border: 1px solid #ddd">
                                        <option value="Operacion">OPERACIÓN</option>
                                        <option value="Visita">VISITA</option>
                                        <option value="ANXOR INGENIERIA">ANXOR INGENIERIA</option>
                                        <option value="AQUATEC">AQUATEC</option>
                                        <option value="AVIMOR">AVIMOR</option>
                                        <option value="CENTRAL HIDRAULICA">CENTRAL HIDRAULICA</option>
                                        <option value="CONSTRUMARKET DE NICARAGUA">CONSTRUMARKET DE NICARAGUA</option>
                                        <option value="EL DORADO, S. A.">EL DORADO, S. A.</option>
                                        <option value="GUNNER CRUZ">GUNNER CRUZ</option>
                                        <option value="HG TRANSPORTE">HG TRANSPORTE</option>
                                        <option value="HVASCO S.A.">HVASCO S.A.</option>
                                        <option value="JOHN MAY">JOHN MAY</option>
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
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-dismiss="modal">Cerrar</button>
                        <button type="submit" name="addstaff" class="btn btn-primary-custom rounded-pill px-4" style="height: 45px">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="./admin/vendor/jquery/jquery.min.js"></script>
    <script src="./admin/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Toggle de contraseña
            $('#eyeIcon').on('click', function() {
                const passwordInput = $('#inputPassword');
                const icon = $(this).find('i');
                
                if (passwordInput.attr('type') === 'password') {
                    passwordInput.attr('type', 'text');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    passwordInput.attr('type', 'password');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });

            // Login con AJAX y Loading State
            $('#loginform').on('submit', function(e) {
                e.preventDefault();
                const btn = $('#btnLogin');
                const warning = $('#warningbox');
                const originalText = btn.html();

                // Estado de carga
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Verificando...');
                warning.fadeOut();

                $.ajax({
                    type: "POST",
                    url: "process_login.php",
                    data: { 
                        email: $("#inputemail").val(), 
                        password: $("#inputPassword").val() 
                    },
                    success: function(data) {
                        data = data.trim();
                        if (["admin", "cocinero", "empleado"].includes(data)) {
                            btn.removeClass('btn-primary-custom').addClass('btn-success').html('<i class="fas fa-check"></i> Redireccionando...');
                            setTimeout(() => {
                                if (data === "admin") window.location.href = "./admin/index.php";
                                else if (data === "moderador") window.location.href = "./staff/index.php";
                                else window.location.href = "./staff/index.php";
                            }, 700);
                        } else {
                            btn.prop('disabled', false).html(originalText);
                            warning.html("<div class='alert alert-danger py-2 animate__animated animate__shakeX'><small><i class='fas fa-times-circle'></i> Credenciales incorrectas</small></div>").fadeIn();
                        }
                    },
                    error: function() {
                        btn.prop('disabled', false).html(originalText);
                        warning.html("<div class='alert alert-danger py-2'>Error de conexión</div>").fadeIn();
                    }
                });
            });
        });
    </script>

</body>
</html>