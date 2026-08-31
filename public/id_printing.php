<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$user = require_role(['admin', 'id_staff']);
$page = 'id_printing';
$title = 'ID Printing';

// Handle "mark as printed" for an approved student
if (isset($_GET['print'])) {
    $student_id = (int)$_GET['print'];
    $si = db()->prepare('SELECT * FROM school_ids WHERE student_id = ?');
    $si->execute([$student_id]);
    $card = $si->fetch();

    $student = db()->prepare('SELECT * FROM students WHERE id = ?');
    $student->execute([$student_id]);
    $stu = $student->fetch();

    if ($card && $stu) {
        db()->prepare("INSERT INTO id_requests (student_id, request_type, status, printed_by, printed_date)
                       VALUES (?,?,?,?,NOW())")->execute([$student_id, 'new', 'printed', $user['id']]);
        notify((int)$user['id'], 'ID printed for ' . $stu['full_name'] . ' (' . $card['id_number'] . ')');
        flash('success', 'ID marked as printed.');
    } else {
        flash('error', 'Student has no generated ID card yet.');
    }
    redirect('/id_printing.php');
}

// Select which student card to display (defaults to first approved with an ID)
$sel_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$list = db()->query("SELECT s.id, s.student_no, s.full_name, s.course, s.year_level, s.section, s.photo_path,
                            si.id_number, si.qr_code, si.issue_date, si.expiry_date, si.qr_status
                     FROM school_ids si JOIN students s ON s.id = si.student_id
                     WHERE s.status='approved' ORDER BY s.full_name")->fetchAll();

$selected = null;
foreach ($list as $row) {
    if ($sel_id && $row['id'] === $sel_id) { $selected = $row; break; }
}
if (!$selected && $list) {
    foreach ($list as $row) {
        if ($row['qr_status'] === 'active') { $selected = $row; break; }
    }
}
if (!$selected && $list) { $selected = $list[0]; }

// Pending print requests
$pending = db()->query("SELECT r.id, r.request_type, r.status, r.request_date, s.full_name, s.student_no
                        FROM id_requests r JOIN students s ON s.id = r.student_id
                        WHERE r.status='pending' ORDER BY r.request_date DESC LIMIT 10")->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="grid grid-2">
  <div class="card">
    <div class="section-title">Choose a student ID</div>
    <form method="get" class="mb-1">
      <select name="id" onchange="this.form.submit()" style="width:100%;padding:.7rem;border:1.5px solid var(--line);border-radius:10px;font-family:inherit">
        <?php foreach ($list as $row): ?>
          <option value="<?= $row['id'] ?>" <?= $selected && $selected['id']===$row['id']?'selected':'' ?>>
            <?= e($row['id_number'] . ' — ' . $row['full_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>

    <?php if ($selected): ?>
      <!-- Printable ID card -->
      <div class="id-card-front">
        <div class="id-card-head">
          <div class="mini-logo">SID</div>
          <div class="id-card-sch">School ID System<small>Student Identification Card</small></div>
        </div>
        <div class="id-card-body">
          <div class="id-photo">
            <?php if (!empty($selected['photo_path']) && is_file(__DIR__ . $selected['photo_path'])): ?>
              <img src="<?= e($selected['photo_path']) ?>" alt="photo">
            <?php else: ?>
              NO<br>PHOTO
            <?php endif; ?>
          </div>
          <div class="id-details">
            <div class="nm"><?= e($selected['full_name']) ?></div>
            <div class="course"><?= e($selected['course']) ?><br><?= e($selected['year_level']) ?> • <?= e($selected['section']) ?></div>
            <div class="id-field"><span>ID Number</span><?= e($selected['id_number']) ?></div>
          </div>
        </div>
        <div class="id-card-foot">
          <div class="qr-box"><img src="/qr.php?value=<?= urlencode($selected['qr_code']) ?>" alt="QR" width="72" height="72"></div>
          <div class="id-field-qr">
            Scan this QR at the<br>school gate
          </div>
        </div>
      </div>

      <div class="flex mt-1">
        <a href="?print=<?= $selected['id'] ?>" class="btn btn-primary">▶ Mark as Printed</a>
        <button class="btn btn-outline" onclick="window.print()">🖨 Print Card</button>
        <a href="/qr.php?value=<?= urlencode($selected['qr_code']) ?>" target="_blank" class="btn btn-ghost">QR</a>
      </div>
    <?php else: ?>
      <p class="muted">No approved students with generated ID cards yet. Approve students from Student Management first.</p>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="section-title">Print Requests</div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Student</th><th>Type</th><th>Status</th><th>Requested</th></tr></thead>
        <tbody>
        <?php foreach ($pending as $p): ?>
          <tr>
            <td><?= e($p['full_name']) ?><div class="muted" style="font-size:.72rem"><?= e($p['student_no']) ?></div></td>
            <td><?= e($p['request_type']) ?></td>
            <td><span class="pill pill-amber"><?= e($p['status']) ?></span></td>
            <td><?= e(date('M d, H:i', strtotime($p['request_date']))) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$pending): ?><tr><td colspan="4" class="muted">No pending print requests.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
