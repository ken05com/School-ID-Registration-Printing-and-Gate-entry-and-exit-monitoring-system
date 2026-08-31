<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$user = require_login();
$page = 'dashboard';
$title = 'Dashboard';

// Stats
$students_total   = (int)db()->query('SELECT COUNT(*) c FROM students')->fetch()['c'];
$students_in_campus = (int)db()->query("SELECT COUNT(DISTINCT student_id) c FROM gate_logs WHERE direction='entry' AND DATE(scanned_at)=CURDATE()")->fetch()['c'];
$ids_issued     = (int)db()->query('SELECT COUNT(*) c FROM school_ids')->fetch()['c'];
$pending_req    = (int)db()->query("SELECT COUNT(*) c FROM id_requests WHERE status='pending'")->fetch()['c'];
$pending_appr   = (int)db()->query("SELECT COUNT(*) c FROM students WHERE status='pending'")->fetch()['c'];
$today_entries  = (int)db()->query("SELECT COUNT(*) c FROM gate_logs WHERE direction='entry' AND DATE(scanned_at)=CURDATE()")->fetch()['c'];
$today_exits    = (int)db()->query("SELECT COUNT(*) c FROM gate_logs WHERE direction='exit' AND DATE(scanned_at)=CURDATE()")->fetch()['c'];

// Recent activity
$recent = db()->query("SELECT gl.id_number, s.full_name, gl.direction, gl.status, gl.scanned_at
                       FROM gate_logs gl JOIN students s ON s.id = gl.student_id
                       ORDER BY gl.scanned_at DESC LIMIT 8")->fetchAll();
$recent_reg = db()->query("SELECT student_no, full_name, course, status, created_at FROM students
                           ORDER BY created_at DESC LIMIT 6")->fetchAll();

$role = $user['role'];
$can_register  = in_array($role, ['admin','registrar','id_staff'], true);
$can_gate      = in_array($role, ['admin','security_guard'], true);
$can_print     = in_array($role, ['admin','id_staff'], true);

include __DIR__ . '/../includes/header.php';
?>

<div class="grid grid-4 mb-1">
  <div class="card stat"><span class="label">Total Students</span><span class="value"><?= $students_total ?></span><span class="sub">registered</span></div>
  <div class="card stat"><span class="label">On Campus Now</span><span class="value" style="color:var(--green)"><?= $students_in_campus ?></span><span class="sub">today</span></div>
  <div class="card stat"><span class="label">IDs Issued</span><span class="value"><?= $ids_issued ?></span><span class="sub">active cards</span></div>
  <div class="card stat"><span class="label">Pending Actions</span><span class="value" style="color:var(--amber)"><?= $pending_appr + $pending_req ?></span><span class="sub">approvals + prints</span></div>
</div>

<div class="grid grid-3">
  <div class="card">
    <div class="section-title">Quick Actions</div>
    <div class="grid grid-2" style="gap:.8rem">
      <?php if ($can_register): ?><a class="btn btn-primary" href="/register.php">＋ Register</a><?php endif; ?>
      <?php if ($can_print): ?><a class="btn btn-outline" href="/id_printing.php">▣ Print ID</a><?php endif; ?>
      <?php if ($can_gate): ?><a class="btn btn-outline" href="/gate.php">◉ Gate</a><?php endif; ?>
      <a class="btn btn-ghost" href="/reports.php">◫ Reports</a>
    </div>
    <div class="mt-2">
      <div class="section-title">Gate Today</div>
      <div class="grid grid-2" style="gap:.8rem">
        <div class="card stat" style="padding:.8rem;box-shadow:none"><span class="label">Entries</span><span class="value" style="font-size:1.5rem"><?= $today_entries ?></span></div>
        <div class="card stat" style="padding:.8rem;box-shadow:none"><span class="label">Exits</span><span class="value" style="font-size:1.5rem"><?= $today_exits ?></span></div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="section-title">Recent Gate Activity</div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>ID</th><th>Student</th><th>Dir</th><th>Status</th><th>Time</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $r): ?>
          <tr>
            <td><?= e($r['id_number']) ?></td>
            <td><?= e($r['full_name']) ?></td>
            <td><span class="pill <?= $r['direction']==='entry'?'pill-green':'pill-gray' ?>"><?= e($r['direction']) ?></span></td>
            <td><span class="pill <?= $r['status']==='valid'?'pill-green':'pill-red' ?>"><?= e($r['status']) ?></span></td>
            <td><?= e(date('M d, H:i', strtotime($r['scanned_at']))) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$recent): ?><tr><td colspan="5" class="muted">No activity yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="section-title">Recent Registrations</div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Student No</th><th>Name</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($recent_reg as $r): ?>
          <tr>
            <td><?= e($r['student_no']) ?></td>
            <td><?= e($r['full_name']) ?></td>
            <td><span class="pill <?= $r['status']==='approved'?'pill-green':($r['status']==='pending'?'pill-amber':'pill-red') ?>"><?= e($r['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
