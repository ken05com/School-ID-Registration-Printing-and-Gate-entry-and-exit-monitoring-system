<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$user = require_login();
$page = 'reports';
$title = 'Reports';

$from = $_GET['from'] ?? date('Y-m-d', strtotime('-7 days'));
$to   = $_GET['to'] ?? date('Y-m-d');

// Gate activity in range
$stmt = db()->prepare("SELECT gl.direction, gl.status, gl.scanned_at, s.full_name, gl.id_number
                       FROM gate_logs gl JOIN students s ON s.id=gl.student_id
                       WHERE DATE(gl.scanned_at) BETWEEN ? AND ?
                       ORDER BY gl.scanned_at DESC");
$stmt->execute([$from, $to]);
$logs = $stmt->fetchAll();

$entries = array_filter($logs, fn($l) => $l['direction'] === 'entry');
$exits   = array_filter($logs, fn($l) => $l['direction'] === 'exit');
$valid   = array_filter($logs, fn($l) => $l['status'] === 'valid');

// Summmary counts
$reg_in_range = (int)db()->query("SELECT COUNT(*) c FROM students WHERE DATE(created_at) BETWEEN '$from' AND '$to'")->fetch()['c'];
$printed_in_range = (int)db()->query("SELECT COUNT(*) c FROM id_requests WHERE printed_date IS NOT NULL AND DATE(printed_date) BETWEEN '$from' AND '$to'")->fetch()['c'];
$active_ids = (int)db()->query("SELECT COUNT(*) c FROM school_ids WHERE qr_status='active'")->fetch()['c'];
$students_total = (int)db()->query('SELECT COUNT(*) c FROM students WHERE status="approved"')->fetch()['c'];

// Export CSV
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="gate_report_' . $from . '_to_' . $to . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID Number', 'Student', 'Direction', 'Status', 'Timestamp']);
    foreach ($logs as $l) {
        fputcsv($out, [$l['id_number'], $l['full_name'], $l['direction'], $l['status'], $l['scanned_at']]);
    }
    fclose($out);
    exit;
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="grid grid-4 mb-1">
  <div class="card stat"><span class="label">Entries (range)</span><span class="value"><?= count($entries) ?></span></div>
  <div class="card stat"><span class="label">Exits (range)</span><span class="value"><?= count($exits) ?></span></div>
  <div class="card stat"><span class="label">Valid Scans</span><span class="value" style="color:var(--green)"><?= count($valid) ?></span></div>
  <div class="card stat"><span class="label">Active ID Cards</span><span class="value"><?= $active_ids ?></span></div>
</div>

<div class="grid grid-3 mb-1">
  <div class="card stat"><span class="label">Registrations (range)</span><span class="value"><?= $reg_in_range ?></span></div>
  <div class="card stat"><span class="label">IDs Printed (range)</span><span class="value"><?= $printed_in_range ?></span></div>
  <div class="card stat"><span class="label">Approved Students</span><span class="value"><?= $students_total ?></span></div>
</div>

<div class="card">
  <div class="flex between mb-1">
    <h2>Gate Activity Report</h2>
    <a class="btn btn-outline btn-sm" href="?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&export=csv">⬇ Export CSV</a>
  </div>
  <form class="flex mb-1" method="get">
    <div class="field"><label>From</label><input type="date" name="from" value="<?= e($from) ?>"></div>
    <div class="field"><label>To</label><input type="date" name="to" value="<?= e($to) ?>"></div>
    <button class="btn btn-primary" type="submit" style="align-self:end">Apply</button>
  </form>
  <div class="table-wrap">
    <table>
      <thead><tr><th>ID Number</th><th>Student</th><th>Direction</th><th>Status</th><th>Timestamp</th></tr></thead>
      <tbody>
      <?php foreach ($logs as $l): ?>
        <tr>
          <td><?= e($l['id_number']) ?></td>
          <td><?= e($l['full_name']) ?></td>
          <td><span class="pill <?= $l['direction']==='entry'?'pill-green':'pill-gray' ?>"><?= e($l['direction']) ?></span></td>
          <td><span class="pill <?= $l['status']==='valid'?'pill-green':'pill-red' ?>"><?= e($l['status']) ?></span></td>
          <td><?= e(date('Y-m-d H:i', strtotime($l['scanned_at']))) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$logs): ?><tr><td colspan="5" class="muted">No activity in the selected range.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
