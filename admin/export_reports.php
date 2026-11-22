<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/_reports_filter.php';

require_admin();

$filter = parse_report_filters($_GET);

$sql = 'SELECT id, waste_type, location, notes, status, created_at, updated_at
        FROM reports' . $filter['where_sql'] . '
        ORDER BY created_at DESC, id DESC';

$stmt = mysqli_prepare($koneksi, $sql);
stmt_bind_params($stmt, $filter['bind_types'], $filter['bind_params']);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$filename = 'laporan-pilahin-' . date('Ymd-His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, array('ID', 'Jenis Sampah', 'Lokasi', 'Keterangan', 'Status', 'Dibuat Pada', 'Diperbarui Pada'));

while ($row = mysqli_fetch_assoc($res)) {
    fputcsv($out, array(
        (int)$row['id'],
        csv_safe($row['waste_type']),
        csv_safe($row['location']),
        csv_safe($row['notes']),
        csv_safe($row['status']),
        csv_safe($row['created_at']),
        csv_safe($row['updated_at']),
    ));
}

fclose($out);
mysqli_stmt_close($stmt);
exit;
