<?php
// conexion.php
$pdo = new PDO("mysql:host=localhost;dbname=checklist;charset=utf8", "root", "Tecnologias11-11");

// Cargar FPDF si está instalada la librería (asegúrate de tener fpdf.php en tu directorio)
if (file_exists('fpdf/fpdf.php')) {
    require_once('fpdf/fpdf.php');
}

// Lista de parámetros con sus opciones específicas
$parametros = [
    ["nombre" => "Documentos vigentes (Seguro/Licencia/Matrícula/Rodamiento/Inspección Mecánica y de gases)", "tipo" => "CI"],
    ["nombre" => "Carnet de Manejo Interno VIGENTE", "tipo" => "CI"],
    ["nombre" => "Buen estado de la batería y asegurada", "tipo" => "CI"],
    ["nombre" => "Luces intermitentes, luces direccionales", "tipo" => "CI"],
    ["nombre" => "Doble tracción (para Subterráneo y Tajos)", "tipo" => "CI"],
    ["nombre" => "Luz estroboscópica color ámbar (centella)", "tipo" => "CI"],
    ["nombre" => "Frenos y Dirección en buen estado", "tipo" => "CI"],
    ["nombre" => "Frenos de Emergencia", "tipo" => "CI"],
    ["nombre" => "Cinturón de Seguridad", "tipo" => "CI"],
    ["nombre" => "10 cintas refractivas (2 frente, 6 costados, 2 atrás)", "tipo" => "CI"],
    ["nombre" => "Cuña de seguridad", "tipo" => "CI"],
    ["nombre" => "Trabas para espárragos / Revisión de tuerca de espárragos", "tipo" => "CI"],
    ["nombre" => "Alarma Retroceso", "tipo" => "CI"],
    ["nombre" => "Pértiga, con banderola y luz en extremo superior color ámbar (Aplica en Tajo)", "tipo" => "CINA"],
    ["nombre" => "Conos de Seguridad (Mínimo 3 unidades de 36\" para Tajo y Mina UG)", "tipo" => "CINA"],
    ["nombre" => "Botiquín de primeros Auxilios", "tipo" => "CINA"],
    ["nombre" => "Nivel Fluidos (Aceite de motor, Coolant, Aceite Power Steering, Nivel de combustible)", "tipo" => "CINA"],
    ["nombre" => "Halógenos de Retroceso (Obligatorio en UG)", "tipo" => "CINA"],
    ["nombre" => "Estado físico de carrocería (golpes, rayones)", "tipo" => "CINA"],
    ["nombre" => "Kit para Derrames de Materiales Peligrosos", "tipo" => "CINA"],
    ["nombre" => "Bocina", "tipo" => "CINA"],
    ["nombre" => "Cortador de corriente", "tipo" => "CINA"],
    ["nombre" => "Neumáticos, Llantas, Rines, Espárragos", "tipo" => "CINA"],
    ["nombre" => "Llanta de repuesto", "tipo" => "CINA"],
    ["nombre" => "Extintor, bolso porta extintor", "tipo" => "CINA"],
    ["nombre" => "Limpia Parabrisas", "tipo" => "CINA"],
    ["nombre" => "Herramientas (Gata, maneral, extensión para gata, alicate)", "tipo" => "CINA"],
    ["nombre" => "Limpieza Interior", "tipo" => "CINA"],
    ["nombre" => "Espejos, Vidrios, Aire acondicionado", "tipo" => "CINA"],
    ["nombre" => "Vidrios sin fisuras / Sin Polarizado", "tipo" => "CINA"],
    ["nombre" => "Cinta de precaucion", "tipo" => "CINA"],
    ["nombre" => "GPS", "tipo" => "CINA"]
];

// Procesar el formulario al enviar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehiculo_id = $_POST['vehiculo_id'];
    $nombre = $_POST['nombre'];
    $odometro = $_POST['odometro'];
    $observaciones = $_POST['observaciones'];
    $respuestas = $_POST['evaluacion'] ?? [];

    $estado_general = in_array('I', $respuestas) ? 'Con Fallas' : 'Aprobado';

    // Obtener datos del vehículo para el reporte/guardado
    $stmtVeh = $pdo->prepare("SELECT * FROM vehiculos WHERE id = ?");
    $stmtVeh->execute([$vehiculo_id]);
    $vehiculo_data = $stmtVeh->fetch(PDO::FETCH_ASSOC);
    $placa = $vehiculo_data ? ($vehiculo_data['codigo'] . ' - ' . $vehiculo_data['placa']) : 'N/A';

    $stmt = $pdo->prepare("INSERT INTO inspecciones (vehiculo_id, nombre_conductor, odometro, observaciones, estado) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$vehiculo_id, $nombre, $odometro, $observaciones, $estado_general]);
    $inspeccion_id = $pdo->lastInsertId();

    $stmtDetalle = $pdo->prepare("INSERT INTO detalles_inspeccion (inspeccion_id, parametro, resultado) VALUES (?, ?, ?)");
    foreach ($parametros as $index => $item) {
        $resultado = $respuestas[$index] ?? 'NA';
        $stmtDetalle->execute([$inspeccion_id, $item['nombre'], $resultado]);
    }

    // SI SE SOLICITA GENERAR PDF (mediante botón o script de impresión)
    if (isset($_POST['generar_pdf']) && class_exists('FPDF')) {
        class PDF extends FPDF {
            function Header() {
                $this->SetFont('Arial', 'B', 14);
                $this->Cell(0, 8, utf8_decode('EQUINOX GOLD - INSPECCIÓN DE VEHÍCULOS'), 0, 1, 'C');
                $this->SetFont('Arial', '', 9);
                $this->Cell(0, 5, utf8_decode('Formulario Activo: 01-FOR-037'), 0, 1, 'C');
                $this->Ln(5);
            }
            function Footer() {
                $this->SetY(-15);
                $this->SetFont('Arial', 'I', 8);
                $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
            }
        }

        $pdf = new PDF();
        $pdf->AliasNbPages();
        $pdf->AddPage();
        
        // Datos generales
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(0, 7, utf8_decode('DATOS GENERALES'), 1, 1, 'L', true);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(45, 6, utf8_decode('Vehículo / Placa:'), 1, 0);
        $pdf->Cell(50, 6, utf8_decode($placa), 1, 0);
        $pdf->Cell(45, 6, utf8_decode('Conductor:'), 1, 0);
        $pdf->Cell(50, 6, utf8_decode($nombre), 1, 1);
        
        $pdf->Cell(45, 6, utf8_decode('Odómetro (KM):'), 1, 0);
        $pdf->Cell(50, 6, utf8_decode($odometro), 1, 0);
        $pdf->Cell(45, 6, utf8_decode('Estado General:'), 1, 0);
        $pdf->Cell(50, 6, utf8_decode($estado_general), 1, 1);
        $pdf->Ln(5);

        // Tabla de Parámetros
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 7, utf8_decode('EVALUACIÓN DE PARÁMETROS'), 1, 1, 'L', true);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(140, 6, utf8_decode('Parámetro'), 1, 0);
        $pdf->Cell(50, 6, utf8_decode('Resultado'), 1, 1, 'C');

        $pdf->SetFont('Arial', '', 8);
        foreach ($parametros as $i => $item) {
            $res = $respuestas[$i] ?? 'NA';
            $texto_res = ($res === 'C') ? 'Correcto' : (($res === 'I') ? 'Incorrecto' : 'N/A');
            
            $pdf->Cell(140, 5, utf8_decode(($i+1) . '. ' . $item['nombre']), 1, 0);
            $pdf->Cell(50, 5, utf8_decode($texto_res), 1, 1, 'C');
        }

        if(!empty($observaciones)) {
            $pdf->Ln(4);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(0, 5, utf8_decode('Observaciones:'), 0, 1);
            $pdf->SetFont('Arial', '', 8);
            $pdf->MultiCell(0, 4, utf8_decode($observaciones), 1);
        }

        $pdf->Output('D', 'Inspeccion_'.$placa.'_'.date('Ymd_His').'.pdf');
        exit;
    }

    $mensaje = "Inspección registrada con éxito.";
}

$vehiculos = $pdo->query("SELECT * FROM vehiculos")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checklist Staff - Equinox Gold</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { 
            --eqx-gold: #C59B27; 
            --eqx-gold-hover: #A8821D; 
            --eqx-dark: #1C2024; 
            --eqx-gray-dark: #2A2F35; 
            --eqx-bg-light: #F4F6F8; 
            --sidebar-width: 260px; 
        }
        body { background-color: var(--eqx-bg-light); font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; overflow-x: hidden; }
        .wrapper { display:flex; width:100%; min-height:100vh; }
        #sidebar { width:var(--sidebar-width); background:var(--eqx-dark); color:#fff; display:flex; flex-direction:column }
        #sidebar .sidebar-header { padding:1.25rem 1.5rem; background:rgba(0,0,0,0.12); border-bottom:2px solid var(--eqx-gold); }
        #sidebar .nav-link { color:#c2c7d0; padding:0.8rem 1.5rem; display:flex; gap:12px; }
        #sidebar .nav-link.active, #sidebar .nav-link:hover { color:#fff; background:var(--eqx-gray-dark); border-left:4px solid var(--eqx-gold); }
        .main-content { flex:1; display:flex; flex-direction:column; }
        .top-navbar { background:#fff; border-bottom:1px solid #e3e6f0; padding:0.75rem 1.5rem }
        .btn-eqx-gold { background:var(--eqx-gold); color:#fff; border:none; transition: all 0.2s; }
        .btn-eqx-gold:hover { background:var(--eqx-gold-hover); color:#fff; }
        .table-eqx thead { background:var(--eqx-dark); color:#fff }
        .header-brand { border-bottom:3px solid var(--eqx-gold); padding-bottom:0.75rem }
        
        /* Estilos intuitivos para el estado de evaluación */
        .status-group .btn-check + .btn {
            font-size: 0.82rem;
            font-weight: 600;
            padding: 0.35rem 0.6rem;
            border-radius: 6px;
        }
        .btn-outline-correct {
            border-color: #198754;
            color: #198754;
        }
        .btn-check:checked + .btn-outline-correct {
            background-color: #198754;
            color: #fff;
            box-shadow: 0 2px 4px rgba(25, 135, 84, 0.3);
        }
        .btn-outline-incorrect {
            border-color: #dc3545;
            color: #dc3545;
        }
        .btn-check:checked + .btn-outline-incorrect {
            background-color: #dc3545;
            color: #fff;
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
        }
        .btn-outline-na {
            border-color: #6c757d;
            color: #6c757d;
        }
        .btn-check:checked + .btn-outline-na {
            background-color: #6c757d;
            color: #fff;
        }

        /* Ocultar interfaz para impresión nativa */
        @media print {
            #sidebar, .top-navbar, .btn-action-area { display: none !important; }
            .main-content { margin: 0; padding: 0; }
            .card { border: none !important; shadow: none !important; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <nav id="sidebar">
        <div class="sidebar-header">
            <h5 class="fw-bold mb-0 text-white"><i class="bi bi-person-badge text-warning"></i> EQUINOX GOLD</h5>
            <small class="text-muted text-uppercase" style="font-size:0.7rem; letter-spacing:1px">Perfil Staff</small>
        </div>
        <ul class="nav flex-column my-3">
            <li class="nav-item"><a href="index.php" class="nav-link active"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li class="nav-item"><a href="index.php" class="nav-link"><i class="bi bi-clipboard-data"></i> Inspección</a></li>
            <li class="nav-item"><a href="index.php" class="nav-link"><i class="bi bi-file-earmark-plus"></i> Formulario Staff</a></li>
            <li class="nav-item mt-4"><span class="px-3 text-uppercase text-muted small fw-bold">Accesos</span></li>
            <li class="nav-item"><a href="../admin/index.php" class="nav-link"><i class="bi bi-eye"></i> Ver inspecciones</a></li>
            <li class="nav-item"><a href="#" class="nav-link"><i class="bi bi-truck"></i> Vehículos</a></li>
        </ul>
        <div class="mt-auto p-3 bg-dark text-center border-top border-secondary">
            <small class="text-muted d-block mb-1">Formulario Activo</small>
            <span class="badge bg-warning text-dark">01-FOR-037</span>
        </div>
    </nav>

    <div class="main-content">
        <header class="top-navbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2"><span class="fs-5 fw-semibold text-dark">Checklist Staff</span></div>
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2 border-0" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle fs-5 text-secondary"></i>
                    <span class="d-none d-md-inline fw-medium">Staff</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i> Perfil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#"><i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión</a></li>
                </ul>
            </div>
        </header>

        <main class="p-4">
            <div class="container-fluid p-0">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <p class="text-muted m-0">Complete el checklist diario para el control vehicular.</p>
                    <a href="index.php" class="btn btn-eqx-gold"><i class="bi bi-plus-circle me-1"></i> Nueva Inspección</a>
                </div>

                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-4">
                        <div class="header-brand d-flex align-items-center justify-content-between mb-4">
                            <div>
                                <h3 class="fw-bold m-0" style="color:var(--eqx-dark)">Formulario de Inspección de Vehículos</h3>
                                <small class="text-muted fw-bold">EQUINOX GOLD — CONTROL DE VEHÍCULOS</small>
                            </div>
                            <div class="px-3 py-1 bg-dark rounded text-center"><span class="fw-bold" style="color:var(--eqx-gold); font-size:0.9rem">01-FOR-037</span></div>
                        </div>

                        <?php if (isset($mensaje)): ?>
                            <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4 d-flex justify-content-between align-items-center" style="background-color:#e8f5e9;color:#1b5e20">
                                <div><strong><i class="bi bi-check-circle-fill me-1"></i></strong> <?= htmlspecialchars($mensaje) ?></div>
                                <button type="button" class="btn btn-sm btn-outline-success" onclick="window.print()"><i class="bi bi-printer me-1"></i> Imprimir Vista</button>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <input type="hidden" name="generar_pdf" value="1">

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Vehículo / Placa:</label>
                                    <select name="vehiculo_id" class="form-select" required>
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($vehiculos as $v): ?>
                                            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['codigo'] . ' - ' . $v['placa']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Nombre del Conductor:</label>
                                    <input type="text" name="nombre" class="form-control" required placeholder="Nombre completo">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Odómetro (KM):</label>
                                    <input type="number" name="odometro" class="form-control" required placeholder="Ej: 125000">
                                </div>
                            </div>

                            <h5 class="fw-bold mb-3" style="color:var(--eqx-dark)">Parámetros a Inspeccionar</h5>

                            <div class="table-responsive mb-4">
                                <table class="table table-hover align-middle table-eqx border">
                                    <thead>
                                        <tr>
                                            <th class="py-3 px-3">Parámetro</th>
                                            <th style="width:280px" class="text-center py-3">Estado de Evaluación</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($parametros as $i => $item): ?>
                                        <tr>
                                            <td class="px-3 py-2 fw-medium text-dark">
                                                <span class="text-muted me-2"><?= sprintf('%02d', $i + 1) ?>.</span> 
                                                <?= htmlspecialchars($item['nombre']) ?>
                                            </td>
                                            <td class="text-center py-2">
                                                <div class="btn-group status-group" role="group">
                                                    <!-- Correcto -->
                                                    <input type="radio" class="btn-check" name="evaluacion[<?= $i ?>]" id="c_<?= $i ?>" value="C" required>
                                                    <label class="btn btn-outline-correct" for="c_<?= $i ?>">
                                                        <i class="bi bi-check-lg me-1"></i>Correcto
                                                    </label>

                                                    <!-- Incorrecto -->
                                                    <input type="radio" class="btn-check" name="evaluacion[<?= $i ?>]" id="i_<?= $i ?>" value="I" required>
                                                    <label class="btn btn-outline-incorrect" for="i_<?= $i ?>">
                                                        <i class="bi bi-x-lg me-1"></i>Incorrecto
                                                    </label>

                                                    <!-- No Aplica -->
                                                    <?php if ($item['tipo'] === 'CINA'): ?>
                                                        <input type="radio" class="btn-check" name="evaluacion[<?= $i ?>]" id="na_<?= $i ?>" value="NA" required>
                                                        <label class="btn btn-outline-na" for="na_<?= $i ?>">N/A</label>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Observaciones / Notas:</label>
                                <textarea name="observaciones" class="form-control" rows="3" placeholder="Detalle cualquier anomalía o hallazgo relevante..."></textarea>
                            </div>

                            <!-- Botón Principal Enviar y Generar PDF -->
                            <div class="btn-action-area">
                                <button type="submit" class="btn btn-eqx-gold w-100 btn-lg shadow-sm fw-bold">
                                    <i class="bi bi-file-earmark-pdf me-2"></i>Enviar y Generar PDF
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>