<?php
// conexion.php
$pdo = new PDO("mysql:host=localhost;dbname=checklist;charset=utf8", "root", "Tecnologias11-11");

// Lista de parámetros con sus opciones específicas
// 'CI' -> Cumple / Incorrecto | 'CINA' -> Cumple / Incorrecto / No Aplica
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
    ["nombre" => "Alarma Retroceso", "tipo" => "CI"], // Item 13 (Fin de solo Cumple/Incorrecto)
    ["nombre" => "Pértiga, con banderola y luz en extremo superior color ámbar (Aplica en Tajo)", "tipo" => "CINA"], // Item 14
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
    $respuestas = $_POST['evaluacion'];

    $estado_general = in_array('I', $respuestas) ? 'Con Fallas' : 'Aprobado';

    $stmt = $pdo->prepare("INSERT INTO inspecciones (vehiculo_id, nombre_conductor, odometro, observaciones, estado) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$vehiculo_id, $nombre, $odometro, $observaciones, $estado_general]);
    $inspeccion_id = $pdo->lastInsertId();

    $stmtDetalle = $pdo->prepare("INSERT INTO detalles_inspeccion (inspeccion_id, parametro, resultado) VALUES (?, ?, ?)");
    foreach ($parametros as $index => $item) {
        $resultado = $respuestas[$index] ?? 'NA';
        $stmtDetalle->execute([$inspeccion_id, $item['nombre'], $resultado]);
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
    <style>
        :root {
            --eqx-gold: #C59B27;
            --eqx-gold-hover: #A8821D;
            --eqx-dark: #24415D
            --eqx-gray-dark: #24415D
            --eqx-bg-light: #F4F6F8;
            --eqx-border: #E2E8F0;
        }

        body {
            background-color: var(--eqx-bg-light);
            color: var(--eqx-dark);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .header-brand {
            border-bottom: 3px solid var(--eqx-gold);
            padding-bottom: 0.75rem;
        }

        .text-eqx-gold {
            color: var(--eqx-gold) !important;
        }

        .bg-eqx-dark {
            background-color: var(--eqx-dark) !important;
            color: #ffffff;
        }

        /* Botón Principal Dorado */
        .btn-eqx-gold {
            background-color: var(--eqx-gold);
            border-color: var(--eqx-gold);
            color: #ffffff;
            font-weight: 600;
        }

        .btn-eqx-gold:hover, .btn-eqx-gold:focus {
            background-color: var(--eqx-gold-hover);
            border-color: var(--eqx-gold-hover);
            color: #ffffff;
        }

        /* Enfoque de Inputs */
        .form-control:focus, .form-select:focus {
            border-color: var(--eqx-gold);
            box-shadow: 0 0 0 0.25rem rgba(197, 155, 39, 0.25);
        }

        /* Tabla de Parámetros */
        .table-eqx thead {
            background-color: var(--eqx-dark);
            color: #ffffff;
        }

        .table-eqx th {
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        /* Personalización de Botones Radio Seleccionados */
        .btn-check:checked + .btn-outline-success {
            background-color: #2e7d32;
            border-color: #2e7d32;
        }

        .btn-check:checked + .btn-outline-danger {
            background-color: #c62828;
            border-color: #c62828;
        }

        .btn-check:checked + .btn-outline-secondary {
            background-color: #5d6d7e;
            border-color: #5d6d7e;
        }
    </style>
</head>
<body class="p-3">
<div class="container bg-white p-4 p-md-5 rounded shadow-sm" style="max-width: 920px;">

    <!-- Encabezado Corporativo -->
    <div class="d-flex align-items-center justify-content-between header-brand mb-4">
        <div>
            <h3 class="fw-bold m-0" style="color: var(--eqx-dark);">Formulario de Inspección de Vehículos</h3>
            <small class="text-muted fw-bold">EQUINOX GOLD — CONTROL DE VEHÍCULOS</small>
        </div>
        <div class="px-3 py-1 bg-eqx-dark rounded text-center">
            <span class="fw-bold" style="color: var(--eqx-gold); font-size: 0.9rem;">01-FOR-037</span>
        </div>
    </div>

    <?php if (isset($mensaje)): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4" style="background-color: #e8f5e9; color: #1b5e20;">
            <strong>✓</strong> <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Vehículo / Placa:</label>
                <select name="vehiculo_id" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($vehiculos as $v): ?>
                        <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['codigo'] . ' - ' . $v['placa']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Nombre del Conductor:</label>
                <input type="text" name="nombre" class="form-control" required placeholder="Nombre completo">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Odómetro (KM):</label>
                <input type="number" name="odometro" class="form-control" required placeholder="Ej: 125000">
            </div>
        </div>

        <h5 class="fw-bold mb-3" style="color: var(--eqx-dark);">Parámetros a Inspeccionar</h5>
        
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle table-eqx">
                <thead>
                    <tr>
                        <th class="py-2">Parámetro</th>
                        <th style="width: 190px;" class="text-center py-2">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($parametros as $i => $item): ?>
                    <tr>
                        <td class="small fw-medium text-secondary"><?= ($i + 1) . '. ' . htmlspecialchars($item['nombre']) ?></td>
                        <td class="text-center">
                            <div class="btn-group" role="group">
                                <!-- Opción Cumple -->
                                <input type="radio" class="btn-check" name="evaluacion[<?= $i ?>]" id="c_<?= $i ?>" value="C" checked>
                                <label class="btn btn-outline-success btn-sm px-3" for="c_<?= $i ?>">C</label>

                                <!-- Opción Incorrecto / Fallo -->
                                <input type="radio" class="btn-check" name="evaluacion[<?= $i ?>]" id="i_<?= $i ?>" value="I">
                                <label class="btn btn-outline-danger btn-sm px-3" for="i_<?= $i ?>">I</label>

                                <!-- Opción No Aplica (Solo en ítems con tipo CINA) -->
                                <?php if ($item['tipo'] === 'CINA'): ?>
                                    <input type="radio" class="btn-check" name="evaluacion[<?= $i ?>]" id="na_<?= $i ?>" value="NA">
                                    <label class="btn btn-outline-secondary btn-sm px-2" for="na_<?= $i ?>">N/A</label>
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
            <textarea name="observaciones" class="form-control" rows="3" placeholder="Detalle cualquier anomalía o hallazgo..."></textarea>
        </div>

        <button type="submit" class="btn btn-eqx-gold w-100 btn-lg shadow-sm">
            Enviar Inspección
        </button>
    </form>
</div>
</body>
</html>