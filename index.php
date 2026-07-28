<?php
// conexion.php
$pdo = new PDO("mysql:host=localhost;dbname=checklist;charset=utf8", "root", "Tecnologias11-11");

// Lista de los 29 parámetros extraídos del Excel 01-FOR-037
$parametros = [
    "Documentos vigentes (Seguro/Licencia/Matrícula/Rodamiento/Inspección Mecánica y de gases)",
    "Carnet de Manejo Interno VIGENTE",
    "Buen estado de la batería y asegurada",
    "Luces intermitentes, luces direccionales",
    "Doble tracción (para Subterráneo y Tajos)",
    "Luz estroboscópica color ámbar (centella)",
    "Frenos y Dirección en buen estado",
    "Frenos de Emergencia",
    "Cinturón de Seguridad",
    "10 cintas refractivas (2 frente, 6 costados, 2 atrás)",
    "Cuña de seguridad",
    "Trabas para espárragos / Revisión de tuerca de espárragos",
    "Alarma Retroceso",
    "Pértiga, con banderola y luz en extremo superior color ámbar (Aplica en Tajo)",
    "Conos de Seguridad (Mínimo 3 unidades de 36\" para Tajo y Mina UG)",
    "Botiquín de primeros Auxilios",
    "Nivel Fluidos (Aceite de motor, Coolant, Aceite Power Steering, Nivel de combustible)",
    "Halógenos de Retroceso (Obligatorio en UG)",
    "Estado físico de carrocería (golpes, rayones)",
    "Kit para Derrames de Materiales Peligrosos",
    "Bocina",
    "Cortador de corriente",
    "Neumáticos, Llantas, Rines, Espárragos",
    "Llanta de repuesto",
    "Extintor, bolso porta extintor",
    "Limpia Parabrisas",
    "Herramientas (Gata, maneral, extensión para gata, alicate)",
    "Limpieza Interior",
    "Espejos, Vidrios, Aire acondicionado / Vidrios sin fisuras / Sin Polarizado"
];

// Procesar el formulario al enviar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehiculo_id = $_POST['vehiculo_id'];
    $nombre = $_POST['nombre'];
    $odometro = $_POST['odometro'];
    $observaciones = $_POST['observaciones'];
    $respuestas = $_POST['evaluacion']; // Arreglo con las respuestas C, I, NA

    // Verificar si hay algún fallo registrado
    $estado_general = in_array('I', $respuestas) ? 'Con Fallas' : 'Aprobado';

    // 1. Insertar Cabecera
    $stmt = $pdo->prepare("INSERT INTO inspecciones (vehiculo_id, nombre_conductor, odometro, observaciones, estado) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$vehiculo_id, $nombre, $odometro, $observaciones, $estado_general]);
    $inspeccion_id = $pdo->lastInsertId();

    // 2. Insertar Detalles
    $stmtDetalle = $pdo->prepare("INSERT INTO detalles_inspeccion (inspeccion_id, parametro, resultado) VALUES (?, ?, ?)");
    foreach ($parametros as $index => $param) {
        $resultado = $respuestas[$index] ?? 'NA';
        $stmtDetalle->execute([$inspeccion_id, $param, $resultado]);
    }

    $mensaje = "Inspección registrada con éxito.";
}

// Obtener vehículos
$vehiculos = $pdo->query("SELECT * FROM vehiculos")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checklist Vehículo Liviano</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-3">
<div class="container bg-white p-4 rounded shadow-sm" style="max-width: 800px;">
    <h3 class="mb-3 text-primary">Inspección de Vehículo Liviano (01-FOR-037)</h3>
    
    <?php if (isset($mensaje)): ?>
        <div class="alert alert-success"><?= $mensaje ?></div>
    <?php endif; ?>

    <form method="POST">
        <!-- Cabecera -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label font-weight-bold">Vehículo / Placa:</label>
                <select name="vehiculo_id" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($vehiculos as $v): ?>
                        <option value="<?= $v['id'] ?>"><?= $v['codigo'] ?> - <?= $v['placa'] ?></option>
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

        <!-- Tabla de Evaluación -->
        <h5 class="text-secondary">Parámetros a Inspeccionar</h5>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Parámetro</th>
                        <th style="width: 180px;" class="text-center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($parametros as $i => $param): ?>
                    <tr>
                        <td class="small"><?= ($i + 1) . ". " . $param ?></td>
                        <td class="text-center">
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="evaluacion[<?= $i ?>]" id="c_<?= $i ?>" value="C" checked>
                                <label class="btn btn-outline-success btn-sm" for="c_<?= $i ?>">C</label>

                                <input type="radio" class="btn-check" name="evaluacion[<?= $i ?>]" id="i_<?= $i ?>" value="I">
                                <label class="btn btn-outline-danger btn-sm" for="i_<?= $i ?>">I</label>

                                <input type="radio" class="btn-check" name="evaluacion[<?= $i ?>]" id="na_<?= $i ?>" value="NA">
                                <label class="btn btn-outline-secondary btn-sm" for="na_<?= $i ?>">N/A</label>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mb-3">
            <label class="form-label">Observaciones / Notas:</label>
            <textarea name="observaciones" class="form-control" rows="2"></textarea>
        </div>

        <button type="submit" class="btn btn-primary w-100 btn-lg">Enviar Inspección</button>
    </form>
</div>
</body>
</html>