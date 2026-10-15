<?php
// 1. CONFIGURACIÓN DE CONEXIÓN A LA BASE DE DATOS
include 'config.php';

$pdo = null;
$gerencias = [];
$areas_filtro_tabla = [];
$tareas = [];
$total_registros = 0;
$total_paginas = 1;
$allowed_page_sizes = [10, 20, 50, 100];
$registros_por_pagina = isset($_GET['limite']) ? intval($_GET['limite']) : 10;
if (!in_array($registros_por_pagina, $allowed_page_sizes, true)) {
    $registros_por_pagina = 10;
}
$pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;
$has_nivel_inicial = false;
$has_nivel_final = false;
$has_estado = false;
$has_edit_token = false;
$edit_mode = false;
$edit_tarea = null;
$edit_token = trim($_GET['token'] ?? '');
$edit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    $pdo = null;
}

// Variables de control para los Filtros de la Tabla Principal
$filtro_gerencia = isset($_GET['filtro_gerencia']) ? intval($_GET['filtro_gerencia']) : 0;
$filtro_area = isset($_GET['filtro_area']) ? intval($_GET['filtro_area']) : 0;
$filtro_estado = isset($_GET['filtro_estado']) ? trim($_GET['filtro_estado']) : '';

if ($pdo) {
    if ($filtro_gerencia > 0) {
        $stmtFiltroAreas = $pdo->prepare("SELECT * FROM areas WHERE gerencia_id = ? ORDER BY nombre ASC");
        $stmtFiltroAreas->execute([$filtro_gerencia]);
        $areas_filtro_tabla = $stmtFiltroAreas->fetchAll();
    }

    $missingTokensStmt = $pdo->query("SELECT id FROM tareas WHERE edit_token IS NULL OR edit_token = ''");
    while ($rowMissingToken = $missingTokensStmt->fetch()) {
        $newToken = bin2hex(random_bytes(16));
        $tokenUpdateStmt = $pdo->prepare("UPDATE tareas SET edit_token = ? WHERE id = ?");
        $tokenUpdateStmt->execute([$newToken, $rowMissingToken['id']]);
    }

    $stmtInitCol = $pdo->query("SHOW COLUMNS FROM `tareas` LIKE 'nivel_riesgo_inicial'");
    $has_nivel_inicial = ($stmtInitCol && $stmtInitCol->fetch() !== false);
    $stmtFinalCol = $pdo->query("SHOW COLUMNS FROM `tareas` LIKE 'nivel_riesgo_final'");
    $has_nivel_final = ($stmtFinalCol && $stmtFinalCol->fetch() !== false);
    $stmtEstadoCol = $pdo->query("SHOW COLUMNS FROM `tareas` LIKE 'estado'");
    $has_estado = ($stmtEstadoCol && $stmtEstadoCol->fetch() !== false);
    $stmtEditTokenCol = $pdo->query("SHOW COLUMNS FROM `tareas` LIKE 'edit_token'");
    $has_edit_token = ($stmtEditTokenCol && $stmtEditTokenCol->fetch() !== false);

    if (!$has_edit_token) {
        try {
            $pdo->exec("ALTER TABLE `tareas` ADD COLUMN `edit_token` VARCHAR(255) NULL UNIQUE");
            $has_edit_token = true;
        } catch (PDOException $e) {
            $has_edit_token = false;
        }
    }

    if ($has_edit_token && $edit_id > 0 && $edit_token !== '') {
        $stmtEditTask = $pdo->prepare("SELECT * FROM tareas WHERE id = ? AND edit_token = ? LIMIT 1");
        $stmtEditTask->execute([$edit_id, $edit_token]);
        $edit_tarea = $stmtEditTask->fetch();
        if ($edit_tarea) {
            $edit_mode = true;
        }
    }
}

function buildEditUrl($id, $token) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = dirname($_SERVER['PHP_SELF'] ?? '/index.php');
    $basePath = rtrim($basePath, '/');
    if ($basePath === '' || $basePath === '.') {
        $basePath = '';
    }
    return $protocol . '://' . $host . $basePath . '/index.php?action=editar&id=' . (int)$id . '&token=' . urlencode($token);
}

function buildPaginationSequence($current, $total, $window = 2) {
    $pages = [];
    if ($total <= 1) {
        return [1];
    }

    $start = max(1, $current - $window);
    $end = min($total, $current + $window);

    if ($start > 1) {
        $pages[] = 1;
        if ($start > 2) {
            $pages[] = null;
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $pages[] = $i;
    }

    if ($end < $total) {
        if ($end < $total - 1) {
            $pages[] = null;
        }
        $pages[] = $total;
    }

    return $pages;
}

// 3. GUARDAR EL REGISTRO
if ($pdo && isset($_GET['action']) && $_GET['action'] === 'guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $gerencia_id = $_POST['gerencia_id'] ?? 0;
    $area_id = $_POST['area_id'] ?? 0;
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $responsable = trim($_POST['responsable'] ?? '');
    $nivel_riesgo_inicial = trim($_POST['nivel_riesgo_inicial'] ?? '');
    $nivel_riesgo_final = trim($_POST['nivel_riesgo_final'] ?? '');
    $fecha = $_POST['fecha'] ?? '';
    $actividad = trim($_POST['actividad'] ?? '');

    $permisos_requeridos = $_POST['permisos_requeridos'] ?? [];
    if (!is_array($permisos_requeridos)) {
        $permisos_requeridos = [$permisos_requeridos];
    }
    $permisos_requeridos_json = json_encode(array_values($permisos_requeridos), JSON_UNESCAPED_UNICODE);

    if ($gerencia_id && $area_id && $ubicacion && $responsable && $nivel_riesgo_inicial && $nivel_riesgo_final && $fecha && $actividad) {
        $estado_inicial = 'En desarrollo';

        try {
            if ($has_nivel_inicial && $has_nivel_final) {
                $sql = "INSERT INTO tareas (gerencia_id, area_id, ubicacion, responsable, nivel_riesgo_inicial, nivel_riesgo_final, nivel_riesgo, fecha, permisos_requeridos, actividad";
                $params = [
                    ':gerencia_id' => $gerencia_id,
                    ':area_id' => $area_id,
                    ':ubicacion' => $ubicacion,
                    ':responsable' => $responsable,
                    ':nivel_riesgo_inicial' => $nivel_riesgo_inicial,
                    ':nivel_riesgo_final' => $nivel_riesgo_final,
                    ':nivel_riesgo' => '',
                    ':fecha' => $fecha,
                    ':permisos_requeridos' => $permisos_requeridos_json,
                    ':actividad' => $actividad
                ];

                if ($has_estado) {
                    $sql .= ", estado) VALUES (:gerencia_id, :area_id, :ubicacion, :responsable, :nivel_riesgo_inicial, :nivel_riesgo_final, :nivel_riesgo, :fecha, :permisos_requeridos, :actividad, :estado)";
                    $params[':estado'] = $estado_inicial;
                } else {
                    $sql .= ") VALUES (:gerencia_id, :area_id, :ubicacion, :responsable, :nivel_riesgo_inicial, :nivel_riesgo_final, :nivel_riesgo, :fecha, :permisos_requeridos, :actividad)";
                }
            } else {
                $nivel_riesgo = $nivel_riesgo_inicial . ' / ' . $nivel_riesgo_final;
                $sql = "INSERT INTO tareas (gerencia_id, area_id, ubicacion, responsable, nivel_riesgo, fecha, permisos_requeridos, actividad";
                $params = [
                    ':gerencia_id' => $gerencia_id,
                    ':area_id' => $area_id,
                    ':ubicacion' => $ubicacion,
                    ':responsable' => $responsable,
                    ':nivel_riesgo' => $nivel_riesgo,
                    ':fecha' => $fecha,
                    ':permisos_requeridos' => $permisos_requeridos_json,
                    ':actividad' => $actividad
                ];

                if ($has_estado) {
                    $sql .= ", estado) VALUES (:gerencia_id, :area_id, :ubicacion, :responsable, :nivel_riesgo, :fecha, :permisos_requeridos, :actividad, :estado)";
                    $params[':estado'] = $estado_inicial;
                } else {
                    $sql .= ") VALUES (:gerencia_id, :area_id, :ubicacion, :responsable, :nivel_riesgo, :fecha, :permisos_requeridos, :actividad)";
                }
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $insertedId = (int)$pdo->lastInsertId();
            $editUrl = '';

          $editToken = '';
            if ($has_edit_token) {
                // Genera un número aleatorio entre 0 y 9999 y asegura que tenga 4 dígitos (ej: 0523)
                $editToken = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
                
                $stmtToken = $pdo->prepare("UPDATE tareas SET edit_token = ? WHERE id = ?");
                $stmtToken->execute([$editToken, $insertedId]);
                $editUrl = buildEditUrl($insertedId, $editToken);
            }

            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Registro de ATS guardado exitosamente.' . ($editToken !== '' ? ' Token de autorización: ' . $editToken : ''),
                    'edit_url' => $editUrl,
                    'edit_token' => $editToken
                ]);
                exit;
            }

            $message = 'Registro de ATS guardado exitosamente.' . ($editToken !== '' ? ' Token de autorización: ' . $editToken : '');
            echo "<script>alert('" . addslashes($message) . "'); window.location.href='index.php';</script>";
            exit;
        } catch (PDOException $e) {
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
                exit;
            }

            echo "<script>alert('Error al guardar: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
            exit;
        }
    }
}

if ($pdo && isset($_GET['action']) && $_GET['action'] === 'actualizar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $autorizacion = trim($_POST['autorizacion'] ?? $_POST['edit_token'] ?? '');
    $nuevo_estado = trim($_POST['estado'] ?? '');
    $pin_maestro_valido = isset($pin_maestro) && $pin_maestro !== '' && hash_equals($pin_maestro, $autorizacion);

    if ($id > 0 && $autorizacion !== '' && $has_estado && in_array($nuevo_estado, ['En desarrollo', 'Finalizado'], true)) {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

        try {
            if ($pin_maestro_valido) {
                $stmtOwner = $pdo->prepare("SELECT id FROM tareas WHERE id = ? LIMIT 1");
                $stmtOwner->execute([$id]);
            } elseif ($has_edit_token) {
                $stmtOwner = $pdo->prepare("SELECT id FROM tareas WHERE id = ? AND edit_token = ? LIMIT 1");
                $stmtOwner->execute([$id, $autorizacion]);
            } else {
                $stmtOwner = false;
            }

            if ($stmtOwner === false || $stmtOwner->fetch() === false) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'error', 'message' => 'No tiene permisos para editar este registro.']);
                } else {
                    echo "<script>alert('No tiene permisos para editar este registro.'); window.history.back();</script>";
                }
                exit;
            }

            if ($pin_maestro_valido) {
                $stmt = $pdo->prepare("UPDATE tareas SET estado = ? WHERE id = ?");
                $stmt->execute([$nuevo_estado, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE tareas SET estado = ? WHERE id = ? AND edit_token = ?");
                $stmt->execute([$nuevo_estado, $id, $autorizacion]);
            }

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'message' => 'Estado actualizado correctamente.']);
                exit;
            }

            echo "<script>alert('Estado actualizado correctamente.'); window.location.href='index.php';</script>";
            exit;
        } catch (PDOException $e) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Error al actualizar: ' . $e->getMessage()]);
                exit;
            }

            echo "<script>alert('Error al actualizar: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
            exit;
        }
    }
}

if ($pdo) {
    $gerencias = $pdo->query("SELECT * FROM gerencias ORDER BY nombre ASC")->fetchAll();

    // Construcción de condiciones comunes para conteo y consulta
    $conditions = [];
    $params = [];

    if ($filtro_gerencia > 0) {
        $conditions[] = "t.gerencia_id = ?";
        $params[] = $filtro_gerencia;
    }
    if ($filtro_area > 0) {
        $conditions[] = "t.area_id = ?";
        $params[] = $filtro_area;
    }
    if ($has_estado && $filtro_estado !== '') {
        $allowedEstados = ['En desarrollo', 'Finalizado'];
        if (in_array($filtro_estado, $allowedEstados, true)) {
            $conditions[] = "t.estado = ?";
            $params[] = $filtro_estado;
        }
    }

    $whereSQL = !empty($conditions) ? " WHERE " . implode(" AND ", $conditions) : "";

    // 1. Contar el total de registros bajo los filtros actuales
    $sqlCount = "SELECT COUNT(*) FROM tareas t" . $whereSQL;
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($params);
    $total_registros = $stmtCount->fetchColumn();
    $total_paginas = max(1, ceil($total_registros / $registros_por_pagina));
    if ($pagina_actual > $total_paginas) {
        $pagina_actual = $total_paginas;
        $offset = ($pagina_actual - 1) * $registros_por_pagina;
    }

    // 2. Obtener las columnas y registros limitados para la página actual
    $selectCols = ['t.id', 'g.nombre AS gerencia', 'a.nombre AS area', 't.actividad', 't.ubicacion', 't.responsable', 't.fecha', 't.permisos_requeridos', 't.nivel_riesgo', 't.edit_token'];
    if ($has_estado) {
        $selectCols[] = 't.estado';
    }
    if ($has_nivel_inicial && $has_nivel_final) {
        $selectCols[] = 't.nivel_riesgo_inicial';
        $selectCols[] = 't.nivel_riesgo_final';
    }

    $sqlTareas = "SELECT " . implode(', ', $selectCols) . " FROM tareas t
                  INNER JOIN gerencias g ON t.gerencia_id = g.id
                  INNER JOIN areas a ON t.area_id = a.id" 
                  . $whereSQL . 
                  " ORDER BY t.id DESC LIMIT ? OFFSET ?";

    $stmtTareas = $pdo->prepare($sqlTareas);

    // Vinculamos primero los parámetros de los filtros dinámicos (Gerencia / Área)
    $idx = 1;
    foreach ($params as $param) {
        $stmtTareas->bindValue($idx++, $param);
    }

    // Vinculamos estrictamente como enteros LIMIT y OFFSET para evitar que se ignoren
    $stmtTareas->bindValue($idx++, $registros_por_pagina, PDO::PARAM_INT);
    $stmtTareas->bindValue($idx, $offset, PDO::PARAM_INT);

    $stmtTareas->execute();
    $tareas = $stmtTareas->fetchAll();
}

// Función auxiliar para mantener parámetros de filtros al cambiar de página
function buildQueryString($page, $f_gerencia = 0, $f_area = 0, $f_estado = '', $limite = 10) {
    $query = ['pagina' => $page];
    if ($f_gerencia > 0) $query['filtro_gerencia'] = $f_gerencia;
    if ($f_area > 0) $query['filtro_area'] = $f_area;
    if ($f_estado !== '') $query['filtro_estado'] = $f_estado;
    if (in_array($limite, [10, 20, 50, 100], true)) {
        $query['limite'] = $limite;
    }
    return 'index.php?' . http_build_query($query);
}
?>