<?php
/**
 * Helper bersama untuk filter laporan pada admin/reports.php dan admin/export_reports.php.
 */

function parse_report_filters($source)
{
    $allowedStatus = array('baru', 'diproses', 'selesai');
    $status = isset($source['status']) ? trim((string)$source['status']) : '';
    if (!in_array($status, $allowedStatus, true)) {
        $status = '';
    }

    $type = isset($source['type']) ? trim((string)$source['type']) : '';
    $q = isset($source['q']) ? trim((string)$source['q']) : '';

    $where = array();
    $types = '';
    $params = array();

    if ($status !== '') {
        $where[] = 'status = ?';
        $types .= 's';
        $params[] = $status;
    }

    if ($type !== '') {
        $where[] = 'waste_type = ?';
        $types .= 's';
        $params[] = $type;
    }

    if ($q !== '') {
        $like = '%' . escape_like(str_lower($q), '!') . '%';
        $where[] = "(LOWER(location) LIKE ? ESCAPE '!' OR LOWER(COALESCE(notes, '')) LIKE ? ESCAPE '!')";
        $types .= 'ss';
        $params[] = $like;
        $params[] = $like;
    }

    $whereSql = !empty($where) ? (' WHERE ' . implode(' AND ', $where)) : '';

    $queryParams = array();
    if ($status !== '') {
        $queryParams['status'] = $status;
    }
    if ($type !== '') {
        $queryParams['type'] = $type;
    }
    if ($q !== '') {
        $queryParams['q'] = $q;
    }

    return array(
        'status' => $status,
        'type' => $type,
        'q' => $q,
        'where_sql' => $whereSql,
        'bind_types' => $types,
        'bind_params' => $params,
        'query_string' => http_build_query($queryParams),
    );
}
