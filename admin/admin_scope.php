<?php

function isGlobalAdmin(): bool
{
    $globalAdmins = ['itadmin', 'maria.soriano', 'sixto.galvez', 'joshoa.meza',
                    'eliezer.aragon','erick.lara','tomasa.ortiz', 'grisbell.velasquez'
    ];
    return in_array(strtolower((string)($_SESSION['username'] ?? '')), $globalAdmins, true);
}

function getAdminAreaId(PDO $pdo): int
{
    if (isset($_SESSION['admin_area_id']) && (int)$_SESSION['admin_area_id'] > 0) {
        return (int)$_SESSION['admin_area_id'];
    }

    $stmt = $pdo->prepare('SELECT id_area FROM tbl_users WHERE userID = ? LIMIT 1');
    $stmt->execute([(int)($_SESSION['uid'] ?? 0)]);
    $areaId = (int)$stmt->fetchColumn();

    if ($areaId <= 0) {
        throw new RuntimeException('El administrador no tiene un área asignada.');
    }

    $_SESSION['admin_area_id'] = $areaId;
    return $areaId;
}
