<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$user = require_role(['admin', 'registrar', 'id_staff']);
$page = 'students';
$title = 'Student Management';

// Approve a student (creates/activates a School ID)
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $s = db()->prepare('SELECT * FROM students WHERE id = ?');
    $s->execute([$id]);
    $stu = $s->fetch();
    if ($stu) {
        db()->prepare('UPDATE students SET status = "approved" WHERE id = ?')->execute([$id]);
        // Create the school ID record with QR token if it doesn't exist yet
        $exists = db()->prepare('SELECT id FROM school_ids WHERE student_id = ?');
        $exists->execute([$id]);
        if (!$exists->fetch()) {
            $qr = 'SID-' . $stu['student_no'] . '-' . uid(6);
            $idn = $stu['student_no'];
            $issue = date('Y-m-d');
            $expiry = date('Y-m-d', strtotime('+3 years'));
            db()->prepare('INSERT INTO school_ids (student_id, id_number, qr_code, issue_date, expiry_date, qr_status)
                           VALUES (?,?,?,?,?,?)')->execute([$id, $idn, $qr, $issue, $expiry, 'active']);
            notify((int)($user['id']), 'Student ID generated for ' . $stu['full_name']);
        }
        flash('success', 'Student approved and ID generated.');
    }
    redirect('/students.php');
}

// Reject a student
if (isset($_GET['reject'])) {
    db()->prepare('UPDATE students SET status = "rejected" WHERE id = ?')->execute([(int)$_GET['reject']]);
    flash('error', 'Student application rejected.');
    redirect('/students.php');
}

// Delete a student
if (isset($_GET['delete'])) {
    db()->prepare('DELETE FROM students WHERE id = ?')->execute([(int)$_GET['delete']]);
    flash('success', 'Student removed.');
    redirect('/students.php');
}

$q = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? 'all';
$where = "WHERE 1=1";
$params = [];
if ($q !== '') { $where .= " AND (s.full_name LIKE ? OR s.student_no LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($filter !== 'all') { $where .= " AND s.status = ?"; $params[] = $filter; }

$stmt = db()->prepare("SELECT s.*, si.qr_status AS id_status,
                       (SELECT COUNT(*) FROM gate_logs gl WHERE gl.student_id = s.id) AS log_count
                       FROM students s LEFT JOIN school_ids si ON si.student_id = s.id
                       $where ORDER BY s.created_at DESC");
$stmt->execute($params);
$students = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="card">
  <div class="flex between mb-1">
    <h2>Students</h2>
    <a href="/register.php" class="btn btn-primary btn-sm">＋ Register</a>
  </div>
  <form method="get" class="flex mb-1">
    <input type="text" name="q" placeholder="Search by name or student no." value="<?= e($q) ?>" style="flex:1;padding:.6rem .85rem;border:1.5px solid var(--line);border-radius:10px;font-family:inherit">
    <select name="filter" style="padding:.6rem .85rem;border:1.5px solid var(--line);border-radius:10px;font-family:inherit">
      <option value="all" <?= $filter==='all'?'selected':'' ?>>All statuses</option>
      <option value="pending" <?= $filter==='pending'?'selected':'' ?>>Pending</option>
      <option value="approved" <?= $filter==='approved'?'selected':'' ?>>Approved</option>
      <option value="rejected" <?= $filter==='rejected'?'selected':'' ?>>Rejected</option>
    </select>
    <button class="btn btn-outline" type="submit">Filter</button>
  </form>

  <div class="table-wrap">
    <table>
      <thead><tr><th>Student No</th><th>Full Name</th><th>Course</th><th>Status</th><th>ID Card</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($students as $s): ?>
        <tr>
          <td><?= e($s['student_no']) ?></td>
          <td><?= e($s['full_name']) ?></td>
          <td><?= e($s['course']) ?></td>
          <td><span class="pill <?= $s['status']==='approved'?'pill-green':($s['status']==='pending'?'pill-amber':'pill-red') ?>"><?= e($s['status']) ?></span></td>
          <td><?= $s['id_status'] ? '<span class="pill pill-maroon">' . e($s['id_status']) . '</span>' : '<span class="pill pill-gray">none</span>' ?></td>
          <td>
            <?php if ($s['status'] === 'pending'): ?>
              <a class="btn btn-green btn-sm" href="?approve=<?= $s['id'] ?>">Approve</a>
              <a class="btn btn-danger btn-sm" href="?reject=<?= $s['id'] ?>" onclick="return confirm('Reject this application?')">Reject</a>
            <?php endif; ?>
            <?php if ($s['id_status'] === 'active'): ?>
              <a class="btn btn-outline btn-sm" href="/id_printing.php?id=<?= $s['id'] ?>">Print</a>
            <?php endif; ?>
            <a class="btn btn-ghost btn-sm" href="?delete=<?= $s['id'] ?>" onclick="return confirm('Delete this student (permanent)?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$students): ?><tr><td colspan="6" class="muted">No students found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
