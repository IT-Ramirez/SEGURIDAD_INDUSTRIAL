<?php
include '../config.php';
date_default_timezone_set('America/Managua');


if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

try {
    $pdo = new PDO("mysql:host={$servername};dbname={$dbname};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    die('Error al conectar con la base de datos.');
}

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) require_once $autoload;

function fpdf_txt(string $str): string {
    return mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8');
}

if (class_exists('FPDF') && !class_exists('PDF_Formato')) {
    class PDF_Formato extends FPDF {
        function Header() {
            $x = $this->GetX(); $y = $this->GetY();
            $this->Rect($x, $y, 45, 20);
            $logo = '../image/logo_limon.png';
            if (file_exists($logo)) $this->Image($logo, $x + 2, $y + 6, 41, 0);
            else {
                $this->SetFont('Arial', 'B', 8);
                $this->SetXY($x, $y + 8);
                $this->Cell(45, 4, fpdf_txt('LOGO EMPRESA'), 0, 0, 'C');
            }
            $this->SetXY($x + 45, $y);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(95, 20, fpdf_txt('FORMATO DE INSPECCIÓN EQUIPO LIVIANO'), 1, 0, 'C');
            $this->SetXY($x + 140, $y);
            $this->SetFont('Arial', '', 7);
            $this->Cell(25, 5, fpdf_txt('Código:'), 'LTR', 0, 'L');
            $this->SetFont('Arial', 'B', 7);
            $this->Cell(25, 5, '01-FOR-037', 'TR', 1, 'C');
            $this->SetX($x + 140);
            $this->SetFont('Arial', '', 7);
            $this->Cell(25, 5, fpdf_txt('Revisión:'), 'LR', 0, 'L');
            $this->SetFont('Arial', 'B', 7);
            $this->Cell(25, 5, '0.0', 'R', 1, 'C');
            $this->SetX($x + 140);
            $this->SetFont('Arial', '', 7);
            $this->Cell(25, 5, fpdf_txt('Fecha de emisión:'), 'LR', 0, 'L');
            $this->Cell(25, 5, fpdf_txt('12-oct-18'), 'R', 1, 'C');
            $this->SetX($x + 140);
            $this->Cell(25, 5, fpdf_txt('Página:'), 'LBR', 0, 'L');
            $this->Cell(25, 5, $this->PageNo() . ' de {nb}', 'BR', 1, 'C');
            $this->Ln(3);
        }

        function Footer() {
            $this->SetY(-18);
            $this->SetFont('Arial', 'I', 7);
            $this->SetFillColor(245, 245, 245);
            $this->MultiCell(0, 3.5, fpdf_txt(
                "Importante: Realice una inspección 360° de su equipo móvil antes de ponerlo en marcha.\n" .
                "En caso de relevo de operador/conductor, el que recibe debe validar la inspección realizada."
            ), 1, 'C', true);
        }
    }
}

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
    ["nombre" => "Conos de Seguridad (Mínimo 3 unidades de 36\" para Tajo y Mina UG)", "tipo" => "CI"],
    ["nombre" => "Botiquín de primeros Auxilios", "tipo" => "CI"],
    ["nombre" => "Nivel Fluidos (Aceite de motor, Coolant, Aceite Power Steering, Nivel de combustible)", "tipo" => "CINA"],
    ["nombre" => "Halógenos de Retroceso (Obligatorio en UG)", "tipo" => "CINA"],
    ["nombre" => "Estado físico de carrocería (golpes, rayones)", "tipo" => "CINA"],
    ["nombre" => "Kit para Derrames de Materiales Peligrosos", "tipo" => "CINA"],
    ["nombre" => "Bocina", "tipo" => "CINA"],
    ["nombre" => "Cortador de corriente", "tipo" => "CINA"],
    ["nombre" => "Neumáticos, Llantas, Rines, Espárragos", "tipo" => "CINA"],
    ["nombre" => "Llanta de repuesto", "tipo" => "CINA"],
    ["nombre" => "Extintor, bolso porta extintor", "tipo" => "CI"],
    ["nombre" => "Limpia Parabrisas", "tipo" => "CINA"],
    ["nombre" => "Herramientas (Gata, maneral, extensión para gata, alicate)", "tipo" => "CINA"],
    ["nombre" => "Limpieza Interior", "tipo" => "CINA"],
    ["nombre" => "Espejos, Vidrios, Aire acondicionado", "tipo" => "CINA"],
    ["nombre" => "Vidrios sin fisuras / Sin Polarizado", "tipo" => "CI"],
];

$mensaje = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $csrf = $_POST['csrf_token'] ?? '';
        if (empty($_SESSION['csrf_token']) || empty($csrf) || !hash_equals($_SESSION['csrf_token'], $csrf))
            throw new Exception('Token de seguridad inválido.');

        if (!isset($_SESSION['uid']) || !is_numeric($_SESSION['uid']))
            throw new Exception('Sesión de usuario inválida.');

        // Lectura limpia de $_POST['placa']
        $placa = strtoupper(trim((string)($_POST['placa'] ?? '')));
        $codigo = strtoupper(trim((string)($_POST['codigo_vehiculo'] ?? '')));
        $odometro = filter_input(INPUT_POST, 'odometro', FILTER_VALIDATE_INT);
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $hora = trim((string)($_POST['hora'] ?? date('h:i A')));
        $observaciones = trim((string)($_POST['observaciones'] ?? ''));
        $camposNuevos = [
            'Cinta de precaución amarilla/roja' => $_POST['cinta_precaucion'] ?? '',
            'GPS Activo' => $_POST['gps_activo'] ?? '',
            'Radio Base' => $_POST['radio_base'] ?? '',
            'Tarjeta GPS' => $_POST['tarjeta_gps'] ?? '',
            '* ¿Se siente fatigado?' => $_POST['fatiga'] ?? '',
            'Nivel de Combustible' => $_POST['nivel_combustible'] ?? '',
            'Último mantenimiento' => trim((string)($_POST['ultimo_mantenimiento'] ?? ''))
        ];

        foreach ($camposNuevos as $nombreCampo => $valorCampo) {
            if ($valorCampo === '')
                throw new Exception('Debe completar el campo: ' . $nombreCampo);
        }

        
        if ($odometro === false || $odometro < 0) throw new Exception('El odómetro no es válido.');

        $raw = $_POST['evaluacion'] ?? [];
        $respuestas = [];

        if (is_array($raw)) {
            foreach ($raw as $key => $value) {
                if (is_numeric($key) && in_array($value, ['C', 'I', 'NA'], true))
                    $respuestas[(int)$key] = $value;
            }
        }

        foreach ($parametros as $i => $item) {
            if (!isset($respuestas[$i]))
                throw new Exception('Debe completar todos los parámetros de inspección.');
            if ($item['tipo'] === 'CI' && $respuestas[$i] === 'NA')
                throw new Exception('Los parámetros obligatorios no pueden marcarse como N/A.');
        }

        $obligatoriosCorrectos = true;
        foreach ($parametros as $i => $item) {
            if ($item['tipo'] === 'CI' && ($respuestas[$i] ?? '') !== 'C') {
                $obligatoriosCorrectos = false;
                break;
            }
        }

        $dictamen = $obligatoriosCorrectos
            && $camposNuevos['GPS Activo'] === 'C'
            && $camposNuevos['* ¿Se siente fatigado?'] === 'NO'
            ? 'APTO PARA CONDUCIR'
            : 'NO APTO PARA CONDUCIR';
        $estado = $dictamen;
        $userID = (int)$_SESSION['uid'];
        $codigo_seguridad = bin2hex(random_bytes(16));

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO inspecciones
            (placa,codigo_vehiculo,userID,nombre_conductor,odometro,hora,observaciones,estado,codigo_seguridad)
            VALUES (?,?,?,?,?,?,?,?,?)
        ");

        $stmt->execute([
            $placa,
            $codigo,
            $userID,
            $nombre,
            $odometro,
            $hora,
            $observaciones,
            $dictamen,
            $codigo_seguridad
        ]);

        $inspeccion_id = (int)$pdo->lastInsertId();

        $stmtDetalle = $pdo->prepare("
            INSERT INTO detalles_inspeccion
            (inspeccion_id,parametro,resultado)
            VALUES (?,?,?)
        ");
        $resultadoTexto = [
            'C' => 'cumple',
            'I' => 'incumple',
            'NA' => 'no aplica'
        ];

        foreach ($parametros as $i => $item) {
            $stmtDetalle->execute([
                $inspeccion_id,
                $item['nombre'],
                $resultadoTexto[$respuestas[$i]]
            ]);
        }

        foreach ($camposNuevos as $nombreCampo => $valorCampo) {
            $valorGuardado = $nombreCampo === '* ¿Se siente fatigado?'
                ? ($valorCampo === 'SI' ? 'I' : 'C')
                : $valorCampo;
            if (isset($resultadoTexto[$valorGuardado])) {
                $valorGuardado = $resultadoTexto[$valorGuardado];
            }
            if (mb_strlen($valorGuardado) > 255)
                throw new Exception('El valor del campo "' . $nombreCampo . '" es demasiado largo.');
            $stmtDetalle->execute([$inspeccion_id, $nombreCampo, $valorGuardado]);
        }

        $pdo->commit();

        if (isset($_POST['generar_pdf']) && $_POST['generar_pdf'] === '1' && class_exists('PDF_Formato')) {
            $pdf = new PDF_Formato('P', 'mm', 'A4');
            $pdf->AliasNbPages();
            $pdf->SetMargins(10, 10, 10);
            $pdf->SetAutoPageBreak(true, 22);
            $pdf->AddPage();

            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(25, 6, fpdf_txt('Placa:'), 1, 0, 'L', true);
            $pdf->SetFont('Arial', '', 8);
            $pdf->Cell(35, 6, fpdf_txt($placa), 1, 0, 'C');
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(35, 6, fpdf_txt('Código del Vehículo:'), 1, 0, 'L', true);
            $pdf->SetFont('Arial', '', 8);
            $pdf->Cell(30, 6, fpdf_txt($codigo), 1, 0, 'C');
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(15, 6, fpdf_txt('Hora:'), 1, 0, 'L', true);
            $pdf->SetFont('Arial', '', 8);
            $pdf->Cell(18, 6, fpdf_txt($hora), 1, 0, 'C');
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(17, 6, fpdf_txt('Odómetro:'), 1, 0, 'L', true);
            $pdf->SetFont('Arial', '', 8);
            $pdf->Cell(15, 6, fpdf_txt((string)$odometro), 1, 1, 'C');

            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(25, 6, fpdf_txt('Nombre:'), 1, 0, 'L', true);
            $pdf->SetFont('Arial', '', 8);
            $pdf->Cell(70, 6, fpdf_txt($nombre), 1, 0, 'L');
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->Cell(95, 6, fpdf_txt('Leyenda: [ C ] Correcto | [ I ] Incorrecto | [ N/A ] No Aplicable'), 1, 1, 'C', true);

            $pdf->Ln(2);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(45, 6, fpdf_txt('ESTADO DEL VEHÍCULO:'), 1, 0, 'L', true);

            if ($dictamen === 'APTO PARA CONDUCIR') {
                $pdf->SetFillColor(220, 245, 220);
                $pdf->SetTextColor(0, 100, 0);
            } else {
                $pdf->SetFillColor(255, 220, 220);
                $pdf->SetTextColor(180, 0, 0);
            }

            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(145, 6, fpdf_txt($dictamen), 1, 1, 'C', true);
            $pdf->SetTextColor(0, 0, 0);

            $pdf->Ln(2);
            $pdf->SetFillColor(28, 32, 36);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(145, 6, fpdf_txt('Parámetros a Inspeccionar'), 1, 0, 'L', true);
            $pdf->Cell(45, 6, fpdf_txt('Resultado'), 1, 1, 'C', true);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('Arial', '', 7.5);

            foreach ($parametros as $i => $item) {
                $res = $respuestas[$i];

                if ($res === 'C') {
                    $texto = 'Correcto [C]';
                    $pdf->SetFillColor(220, 245, 220);
                } elseif ($res === 'I') {
                    $texto = 'Incorrecto [I]';
                    $pdf->SetFillColor(255, 220, 220);
                } else {
                    $texto = 'N/A';
                    $pdf->SetFillColor(240, 240, 240);
                }

                $pdf->Cell(145, 4.8, fpdf_txt(($i + 1) . '. ' . $item['nombre']), 1, 0, 'L');
                $pdf->Cell(45, 4.8, fpdf_txt($texto), 1, 1, 'C', true);
            }

            foreach ($camposNuevos as $nombreCampo => $valorCampo) {
                $pdf->SetFillColor(240, 240, 240);
                $pdf->Cell(145, 4.8, fpdf_txt($nombreCampo), 1, 0, 'L');
                $pdf->Cell(45, 4.8, fpdf_txt($valorCampo), 1, 1, 'C', true);
            }

            $pdf->Ln(2);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(0, 4, fpdf_txt('Notas / Observaciones:'), 0, 1);
            $pdf->SetFont('Arial', '', 7.5);
            $obs = $observaciones !== '' ? $observaciones : 'Sin observaciones registradas.';
            $pdf->MultiCell(0, 4, fpdf_txt($obs), 1, 'L');

            $archivo = preg_replace('/[^a-zA-Z0-9_\-]/', '', $codigo);
            $filename = 'Inspeccion_' . $archivo . '_' . date('Ymd_His') . '.pdf';

            $pdf->Output('D', $filename);
            exit;
        }

        $mensaje = 'Inspección registrada con éxito.';
        header('Location: listado.php');
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Error al procesar inspección: ' . $e->getMessage());
        $error = $e->getMessage();
    }
}
?>