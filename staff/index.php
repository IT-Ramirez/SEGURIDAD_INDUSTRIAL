<?php
// conexion.php
$pdo = new PDO("mysql:host=localhost;dbname=checklist;charset=utf8", "root", "Tecnologias11-11");

// Cargar dependencias de Composer, incluida la librería FPDF
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
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
    ["nombre" => "Cinta de precaución amarilla/roja", "tipo" => "CINA"],
    ["nombre" => "Otros", "tipo" => "CINA"]
];

// Procesar el formulario al enviar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehiculo_id = $_POST['vehiculo_id'];
    $nombre = $_POST['nombre'];
    $odometro = $_POST['odometro'];
    $hora = $_POST['hora'] ?? date('H:i');
    $observaciones = $_POST['observaciones'];
    $respuestas = $_POST['evaluacion'] ?? [];

    $estado_general = in_array('I', $respuestas) ? 'Con Fallas' : 'Aprobado';

    // Obtener datos del vehículo
    $stmtVeh = $pdo->prepare("SELECT * FROM vehiculos WHERE id = ?");
    $stmtVeh->execute([$vehiculo_id]);
    $vehiculo_data = $stmtVeh->fetch(PDO::FETCH_ASSOC);

    $codigo_vehiculo = $vehiculo_data ? $vehiculo_data['codigo'] : 'N/A';
    $placa = $vehiculo_data ? $vehiculo_data['placa'] : 'N/A';

    // Guardar en la base de datos
    $stmt = $pdo->prepare("INSERT INTO inspecciones (vehiculo_id, nombre_conductor, odometro, hora, observaciones, estado) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$vehiculo_id, $nombre, $odometro, $hora, $observaciones, $estado_general]);
    $inspeccion_id = $pdo->lastInsertId();

    $stmtDetalle = $pdo->prepare("INSERT INTO detalles_inspeccion (inspeccion_id, parametro, resultado) VALUES (?, ?, ?)");
    foreach ($parametros as $index => $item) {
        $resultado = $respuestas[$index] ?? 'NA';
        $stmtDetalle->execute([$inspeccion_id, $item['nombre'], $resultado]);
    }

    // GENERAR PDF
    if (isset($_POST['generar_pdf']) && class_exists('FPDF')) {
        class PDF_Formato extends FPDF {
            function Header() {
                $x = $this->GetX();
                $y = $this->GetY();
                
                // 1. Logo (Izquierda)
                $this->Rect($x, $y, 45, 20);
                $logoPath = '../images/logo_limon.png'; // Cambia esta ruta si tu logo está en otra carpeta
                if (file_exists($logoPath)) {
                    $this->Image($logoPath, $x + 2, $y + 2, 41, 16);
                } else {
                    $this->SetFont('Arial', 'B', 8);
                    $this->SetXY($x, $y + 8);
                    $this->Cell(45, 4, utf8_decode('LOGO EMPRESA'), 0, 0, 'C');
                }

                // 2. Título (Centro)
                $this->SetXY($x + 45, $y);
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(95, 20, utf8_decode('FORMATO DE INSPECCIÓN EQUIPO LIVIANO'), 1, 0, 'C');

                // 3. Cuadro Control de Documento (Derecha)
                $this->SetXY($x + 140, $y);
                $this->SetFont('Arial', '', 7);
                
                $this->Cell(25, 5, utf8_decode('Código:'), 'LTR', 0, 'L');
                $this->SetFont('Arial', 'B', 7);
                $this->Cell(25, 5, utf8_decode('01-FOR-037'), 'TR', 1, 'C');
                
                $this->SetX($x + 140);
                $this->SetFont('Arial', '', 7);
                $this->Cell(25, 5, utf8_decode('Revisión:'), 'LR', 0, 'L');
                $this->SetFont('Arial', 'B', 7);
                $this->Cell(25, 5, utf8_decode('0.0'), 'R', 1, 'C');

                $this->SetX($x + 140);
                $this->SetFont('Arial', '', 7);
                $this->Cell(25, 5, utf8_decode('Fecha de emisión:'), 'LR', 0, 'L');
                $this->Cell(25, 5, utf8_decode('12-oct-18'), 'R', 1, 'C');

                $this->SetX($x + 140);
                $this->Cell(25, 5, utf8_decode('Página:'), 'LBR', 0, 'L');
                $this->Cell(25, 5, $this->PageNo() . ' de {nb}', 'BR', 1, 'C');

                $this->Ln(3);
            }

            function Footer() {
                $this->SetY(-18);
                $this->SetFont('Arial', 'I', 7);
                $this->SetFillColor(245, 245, 245);
                $this->MultiCell(0, 3.5, utf8_decode("Importante: Realice una inspección 360° de su equipo móvil antes de ponerlo en marcha.\nEn caso de relevo de operador/conductor, el que recibe debe validar la inspección realizada."), 1, 'C', true);
            }
        }

        $pdf = new PDF_Formato('P', 'mm', 'A4');
        $pdf->AliasNbPages();
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();
        
        // --- SECCIÓN DE DATOS GENERALES ---
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(230, 230, 230);
        
        // Fila 1: Fecha e Identificación
        $pdf->Cell(25, 6, utf8_decode('Placa:'), 1, 0, 'L', true);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(35, 6, utf8_decode($placa), 1, 0, 'C');
        
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(35, 6, utf8_decode('Código del Vehículo:'), 1, 0, 'L', true);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(30, 6, utf8_decode($codigo_vehiculo), 1, 0, 'C');

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(15, 6, utf8_decode('Hora:'), 1, 0, 'L', true);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(18, 6, utf8_decode($hora), 1, 0, 'C');

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(17, 6, utf8_decode('Odómetro:'), 1, 0, 'L', true);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(15, 6, utf8_decode($odometro), 1, 1, 'C');

        // Fila 2: Conductor y Leyenda
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(25, 6, utf8_decode('Nombre:'), 1, 0, 'L', true);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(70, 6, utf8_decode($nombre), 1, 0, 'L');

        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(95, 6, utf8_decode('Leyenda: [ C ] Correcto   |   [ I ] Incorrecto   |   [ N/A ] No Aplicable'), 1, 1, 'C', true);

        $pdf->Ln(2);

        // --- TABLA DE PARÁMETROS ---
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(28, 32, 36);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(145, 6, utf8_decode('Parámetros a Inspeccionar'), 1, 0, 'L', true);
        $pdf->Cell(45, 6, utf8_decode('Resultado'), 1, 1, 'C', true);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', '', 7.5);

        foreach ($parametros as $i => $item) {
            $res = $respuestas[$i] ?? 'NA';
            
            // Formatear texto e ícono
            if ($res === 'C') {
                $texto_res = 'Correcto [C]';
                $pdf->SetFillColor(220, 245, 220); // Verde claro
            } elseif ($res === 'I') {
                $texto_res = 'Incorrecto [I]';
                $pdf->SetFillColor(255, 220, 220); // Rojo claro
            } else {
                $texto_res = 'N/A';
                $pdf->SetFillColor(240, 240, 240); // Gris claro
            }

            // Alternar color ligero en filas
            $pdf->Cell(145, 4.8, utf8_decode(($i + 1) . '. ' . $item['nombre']), 1, 0, 'L');
            $pdf->Cell(45, 4.8, utf8_decode($texto_res), 1, 1, 'C', true);
        }

        // --- SECCIÓN NOTAS / OBSERVACIONES ---
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(0, 4, utf8_decode('Notas / Observaciones:'), 0, 1, 'L');
        $pdf->SetFont('Arial', '', 7.5);
        $obsText = !empty($observaciones) ? $observaciones : 'Sin observaciones registradas.';
        $pdf->MultiCell(0, 4, utf8_decode($obsText), 1, 'L');

        $pdf->Output('D', 'Inspeccion_'.$codigo_vehiculo.'_'.date('Ymd_His').'.pdf');
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
    <title>Checklist Staff - Equinox Gold / Calibre</title>
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
        
        .status-group .btn-check + .btn {
            font-size: 0.82rem;
            font-weight: 600;
            padding: 0.35rem 0.6rem;
            border-radius: 6px;
        }
        .btn-outline-correct { border-color: #198754; color: #198754; }
        .btn-check:checked + .btn-outline-correct { background-color: #198754; color: #fff; box-shadow: 0 2px 4px rgba(25, 135, 84, 0.3); }
        .btn-outline-incorrect { border-color: #dc3545; color: #dc3545; }
        .btn-check:checked + .btn-outline-incorrect { background-color: #dc3545; color: #fff; box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3); }
        .btn-outline-na { border-color: #6c757d; color: #6c757d; }
        .btn-check:checked + .btn-outline-na { background-color: #6c757d; color: #fff; }

        @media print {
            #sidebar, .top-navbar, .btn-action-area { display: none !important; }
            .main-content { margin: 0; padding: 0; }
            .card { border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>

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
                                <h3 class="fw-bold m-0" style="color:var(--eqx-dark)">Formato de Inspección Equipo Liviano</h3>
                                <small class="text-muted fw-bold">CONTROL DE VEHÍCULOS</small>
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
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Vehículo / Placa:</label>
                                    <select name="vehiculo_id" id="vehiculo_id" class="form-select" required onchange="actualizarCodigo()">
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($vehiculos as $v): ?>
                                            <option value="<?= $v['id'] ?>" data-codigo="<?= htmlspecialchars($v['codigo']) ?>"><?= htmlspecialchars($v['codigo'] . ' - ' . $v['placa']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Código del Vehículo:</label>
                                    <input type="text" id="codigo_vehiculo" class="form-control" placeholder="Autocompletado" readonly>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">Hora:</label>
                                    <input type="time" name="hora" class="form-control" value="<?= date('H:i') ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">Odómetro (KM):</label>
                                    <input type="number" name="odometro" class="form-control" required placeholder="Ej: 125000">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">Nombre Conductor:</label>
                                    <input type="text" name="nombre" class="form-control" required placeholder="Nombre completo">
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
                                                    <input type="radio" class="btn-check" name="evaluacion[<?= $i ?>]" id="c_<?= $i ?>" value="C" required>
                                                    <label class="btn btn-outline-correct" for="c_<?= $i ?>">
                                                        <i class="bi bi-check-lg me-1"></i>Correcto
                                                    </label>

                                                    <input type="radio" class="btn-check" name="evaluacion[<?= $i ?>]" id="i_<?= $i ?>" value="I" required>
                                                    <label class="btn btn-outline-incorrect" for="i_<?= $i ?>">
                                                        <i class="bi bi-x-lg me-1"></i>Incorrecto
                                                    </label>

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
<script>
function actualizarCodigo() {
    const select = document.getElementById('vehiculo_id');
    const selectedOption = select.options[select.selectedIndex];
    const codigoInput = document.getElementById('codigo_vehiculo');
    
    if (selectedOption && selectedOption.dataset.codigo) {
        codigoInput.value = selectedOption.dataset.codigo;
    } else {
        codigoInput.value = '';
    }
}
</script>
</body>
</html>