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
    ["nombre" => "Pértiga, con banderola y luz en extremo superior color ámbar (Aplica en Tajo)", "tipo" => "CINA"], // Item 14 (Inicio de 3 opciones)
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
    <title>Checklist Staff</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-3">
<div class="container bg-white p-4 rounded shadow-sm" style="max-width: 900px;">
    <h3 class="mb-3 text-primary">Formulario de Inspección Staff</h3>

    <?php if (isset($mensaje)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label font-weight-bold">Vehículo / Placa:</label>
                <select name="vehiculo_id" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($vehiculos as $v): ?>
                        <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['codigo'] . ' - ' . $v['placa']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Nombre del Conductor:</label>
                <input type="text" name="nombre" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Odómetro (KM):</label>
                <input type="number" name="odometro" class="form-control" required>
            </div>
        </div>

        <h5 class="text-secondary">Parámetros a Inspeccionar</h5>
        <div class="table-responsive mb-3">
            <table class="table table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Parámetro</th>
                        <th style="width: 180px;" class="text-center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($parametros as $i => $item): ?>
                    <tr>
                        <td class="small"><?= ($i + 1) . '. ' . htmlspecialchars($item['nombre']) ?></td>
                        <td class="text-center">
                            <div class="btn-group" role="group">
                                <!-- Opción Cumple -->
                                <input type="radio" class="btn-check" name="evaluacion[<?= $i ?>]" id="c_<?= $i ?>" value="C" checked>
                                <label class="btn btn-outline-success btn-sm" for="c_<?= $i ?>">C</label>

                                <!-- Opción Incorrecto / Fallo -->
                                <input type="radio" class="btn-check" name="evaluacion[<?= $i ?>]" id="i_<?= $i ?>" value="I">
                                <label class="btn btn-outline-danger btn-sm" for="i_<?= $i ?>">I</label>

                                <!-- Opción No Aplica (Solo si el tipo es CINA) -->
                                <?php if ($item['tipo'] === 'CINA'): ?>
                                    <input type="radio" class="btn-check" name="evaluacion[<?= $i ?>]" id="na_<?= $i ?>" value="NA">
                                    <label class="btn btn-outline-secondary btn-sm" for="na_<?= $i ?>">N/A</label>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mb-3">
            <label class="form-label">Observaciones / Notas:</label>
            <textarea name="observaciones" class="form-control" rows="3"></textarea>
        </div>

        <button type="submit" class="btn btn-primary w-100 btn-lg">Enviar Inspección</button>
    </form>
</div>
</body>
</html>