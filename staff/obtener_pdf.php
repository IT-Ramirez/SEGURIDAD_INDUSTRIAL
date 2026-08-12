<?php
include '../config.php'; 
// Iniciar sesión para manejo de token CSRF y autenticación
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generar Token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    $pdo = new PDO("mysql:host={$servername};dbname={$dbname};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log("Error de conexion: " . $e->getMessage());
    die("Error al conectar con la base de datos. Por favor consulte al administrador.");
}

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
];

$mensaje = null;
$error = null;

// Procesar el formulario al enviar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Validar Token CSRF
    $token_post = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token_post)) {
        die("Error de validación de seguridad (Token CSRF no válido).");
    }

    // 2. Sanitización y Validación estricta de Entradas
    $vehiculo_id = filter_input(INPUT_POST, 'vehiculo_id', FILTER_VALIDATE_INT);
    $odometro = filter_input(INPUT_POST, 'odometro', FILTER_VALIDATE_INT);
    $nombre = trim(filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $hora = trim(filter_input(INPUT_POST, 'hora', FILTER_SANITIZE_SPECIAL_CHARS) ?? date('H:i'));
    $observaciones = trim(filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    
    // Sanitizar y validar array de respuestas
    $raw_respuestas = $_POST['evaluacion'] ?? [];
    $respuestas = [];
    
    if (is_array($raw_respuestas)) {
        foreach ($raw_respuestas as $key => $val) {
            if (is_numeric($key) && in_array($val, ['C', 'I', 'NA'], true)) {
                $respuestas[(int)$key] = $val;
            }
        }
    }

    if (!$vehiculo_id || !$odometro || empty($nombre)) {
        $error = "Por favor ingrese todos los datos obligatorios correctamente.";
    } else {
        try {
            // Evaluamos si las primeras 13 opciones son marcadas como 'C'
            $primeras_13_correctas = true;
            for ($i = 0; $i < 13; $i++) {
                if (($respuestas[$i] ?? '') !== 'C') {
                    $primeras_13_correctas = false;
                    break;
                }
            }

            // Dictamen de aptitud
            $dictamen_aptitud = $primeras_13_correctas ? 'APTO PARA CONDUCIR' : 'NO APTO PARA CONDUCIR';

            $estado_general = in_array('I', $respuestas, true) ? 'Con Fallas' : 'Aprobado';

            // Obtener datos del vehículo
            $stmtVeh = $pdo->prepare("SELECT codigo, placa FROM vehiculos WHERE id = ?");
            $stmtVeh->execute([$vehiculo_id]);
            $vehiculo_data = $stmtVeh->fetch();

            $codigo_vehiculo = $vehiculo_data ? $vehiculo_data['codigo'] : 'N/A';
            $placa = $vehiculo_data ? $vehiculo_data['placa'] : 'N/A';
            $id_empleado = $_SESSION['uid'] ?? null;

            // Generar código de seguridad para la inspección
            $codigo_seguridad = bin2hex(random_bytes(16));

            // Transacción para consistencia de datos
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO inspecciones (vehiculo_id, userID, nombre_conductor, odometro, hora, observaciones, estado, codigo_seguridad) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$vehiculo_id, $id_empleado, $nombre, $odometro, $hora, $observaciones, $estado_general, $codigo_seguridad]);
            $inspeccion_id = $pdo->lastInsertId();

            $stmtDetalle = $pdo->prepare("INSERT INTO detalles_inspeccion (inspeccion_id, parametro, resultado) VALUES (?, ?, ?)");
            foreach ($parametros as $index => $item) {
                $resultado = $respuestas[$index] ?? 'NA';
                $stmtDetalle->execute([$inspeccion_id, $item['nombre'], $resultado]);
            }

            $pdo->commit();

            // GENERAR PDF
            if (isset($_POST['generar_pdf']) && class_exists('FPDF')) {
                class PDF_Formato extends FPDF {
                    function Header() {
                        $x = $this->GetX();
                        $y = $this->GetY();
                        
                        $this->Rect($x, $y, 45, 20);
                        $logoPath = '../image/logo_limon.png';
                        if (file_exists($logoPath)) {
                            $this->Image($logoPath, $x + 2, $y + 6, 41, 0);
                        } else {
                            $this->SetFont('Arial', 'B', 8);
                            $this->SetXY($x, $y + 8);
                            $this->Cell(45, 4, utf8_decode('LOGO EMPRESA'), 0, 0, 'C');
                        }

                        $this->SetXY($x + 45, $y);
                        $this->SetFont('Arial', 'B', 10);
                        $this->Cell(95, 20, utf8_decode('FORMATO DE INSPECCIÓN EQUIPO LIVIANO'), 1, 0, 'C');

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
                
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->SetFillColor(230, 230, 230);
                
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
                $pdf->Cell(15, 6, utf8_decode((string)$odometro), 1, 1, 'C');

                $pdf->SetFont('Arial', 'B', 8);
                $pdf->Cell(25, 6, utf8_decode('Nombre:'), 1, 0, 'L', true);
                $pdf->SetFont('Arial', '', 8);
                $pdf->Cell(70, 6, utf8_decode($nombre), 1, 0, 'L');

                $pdf->SetFont('Arial', 'B', 7);
                $pdf->Cell(95, 6, utf8_decode('Leyenda: [ C ] Correcto   |   [ I ] Incorrecto   |   [ N/A ] No Aplicable'), 1, 1, 'C', true);

                // --- CASILLA DE ESTADO / DICTAMEN DE CONDUCCIÓN ---
                $pdf->Ln(2);
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->Cell(45, 6, utf8_decode('ESTADO DEL VEHÍCULO:'), 1, 0, 'L', true);
                
                if ($primeras_13_correctas) {
                    $pdf->SetFillColor(220, 245, 220); // Fondo verde claro
                    $pdf->SetTextColor(0, 100, 0);      // Texto verde oscuro
                } else {
                    $pdf->SetFillColor(255, 220, 220); // Fondo rojo claro
                    $pdf->SetTextColor(180, 0, 0);      // Texto rojo oscuro
                }
                
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->Cell(145, 6, utf8_decode($dictamen_aptitud), 1, 1, 'C', true);
                
                // Restablecer colores estándar
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFillColor(230, 230, 230);

                $pdf->Ln(2);

                $pdf->SetFont('Arial', 'B', 8);
                $pdf->SetFillColor(28, 32, 36);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->Cell(145, 6, utf8_decode('Parámetros a Inspeccionar'), 1, 0, 'L', true);
                $pdf->Cell(45, 6, utf8_decode('Resultado'), 1, 1, 'C', true);

                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('Arial', '', 7.5);

                foreach ($parametros as $i => $item) {
                    $res = $respuestas[$i] ?? 'NA';
                    
                    if ($res === 'C') {
                        $texto_res = 'Correcto [C]';
                        $pdf->SetFillColor(220, 245, 220);
                    } elseif ($res === 'I') {
                        $texto_res = 'Incorrecto [I]';
                        $pdf->SetFillColor(255, 220, 220);
                    } else {
                        $texto_res = 'N/A';
                        $pdf->SetFillColor(240, 240, 240);
                    }

                    $pdf->Cell(145, 4.8, utf8_decode(($i + 1) . '. ' . $item['nombre']), 1, 0, 'L');
                    $pdf->Cell(45, 4.8, utf8_decode($texto_res), 1, 1, 'C', true);
                }

                $pdf->Ln(2);
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->Cell(0, 4, utf8_decode('Notas / Observaciones:'), 0, 1, 'L');
                $pdf->SetFont('Arial', '', 7.5);
                $obsText = !empty($observaciones) ? $observaciones : 'Sin observaciones registradas.';
                $pdf->MultiCell(0, 4, utf8_decode($obsText), 1, 'L');

                $pdf->Output('D', 'Inspeccion_'.preg_replace('/[^a-zA-Z0-9_\-]/', '', $codigo_vehiculo).'_'.date('Ymd_His').'.pdf');
                exit;
            }

            $mensaje = "Inspección registrada con éxito.";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error al procesar inspeccion: " . $e->getMessage());
            $error = "Ocurrió un error al procesar el registro.";
        }
    }
}

try {
    $vehiculos = $pdo->query("SELECT id, codigo, placa FROM vehiculos")->fetchAll();
} catch (PDOException $e) {
    error_log("Error al obtener vehiculos: " . $e->getMessage());
    $vehiculos = [];
}
?>