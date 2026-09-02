<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$user = require_role(['admin', 'registrar', 'id_staff']);
$page = 'students';
$title = 'Student Management';

// Action permissions by role
$role = $user['role'];
$can_photo     = in_array($role, ['admin', 'id_staff'], true);     // file + camera
$can_approve   = in_array($role, ['admin', 'registrar'], true);    // accept
$can_reject    = in_array($role, ['admin', 'registrar'], true);    // reject
$can_edit      = in_array($role, ['admin', 'registrar'], true);    // edit
$can_remove    = in_array($role, ['admin', 'registrar'], true);    // remove/delete
$can_print     = in_array($role, ['admin', 'id_staff'], true);     // print link
$can_register  = in_array($role, ['admin', 'registrar'], true);    // register button

// Handle photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_photo' && $can_photo) {
    $sid = (int)($_POST['student_id'] ?? 0);
    if ($sid > 0 && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['photo']['tmp_name'];
        $size = $_FILES['photo']['size'];
        $type = $_FILES['photo']['type'];

        // Validate: must be image, max 5MB
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($type, $allowed, true)) {
            flash('error', 'Invalid file type. Use JPG, PNG, WebP, or GIF.');
        } elseif ($size > 5 * 1024 * 1024) {
            flash('error', 'File too large. Maximum 5 MB.');
        } else {
            $ext = match($type) { 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', default => 'jpg' };
            $filename = 'student_' . $sid . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = __DIR__ . '/uploads/students/' . $filename;

            if (move_uploaded_file($tmp, $dest)) {
                // Remove old photo if exists
                $old = db()->prepare('SELECT photo_path FROM students WHERE id = ?');
                $old->execute([$sid]);
                $oldRow = $old->fetch();
                if ($oldRow && $oldRow['photo_path'] && is_file(__DIR__ . $oldRow['photo_path'])) {
                    @unlink(__DIR__ . $oldRow['photo_path']);
                }

                $photoPath = '/uploads/students/' . $filename;
                db()->prepare('UPDATE students SET photo_path = ? WHERE id = ?')->execute([$photoPath, $sid]);
                flash('success', 'Photo uploaded successfully.');
            } else {
                flash('error', 'Failed to save file.');
            }
        }
    } else {
        flash('error', 'No file selected or upload error.');
    }
    redirect('/students.php');
}

// Approve a student (accept) — registrar/admin only
if (isset($_GET['approve']) && $can_approve) {
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

// Reject a student — registrar/admin only
if (isset($_GET['reject']) && $can_reject) {
    db()->prepare('UPDATE students SET status = "rejected" WHERE id = ?')->execute([(int)$_GET['reject']]);
    flash('error', 'Student application rejected.');
    redirect('/students.php');
}

// Delete a student (remove) — registrar/admin only
if (isset($_GET['delete']) && $can_remove) {
    db()->prepare('DELETE FROM students WHERE id = ?')->execute([(int)$_GET['delete']]);
    flash('success', 'Student removed.');
    redirect('/students.php');
}

// Edit a student — registrar/admin only
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_student' && $can_edit) {
    $sid = (int)$_POST['id'];
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $year_level = trim($_POST['year_level'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($sid > 0 && $full_name !== '' && $course !== '') {
        db()->prepare('UPDATE students SET full_name=?, email=?, phone=?, course=?, year_level=?, section=?, address=? WHERE id=?')
            ->execute([$full_name, $email, $phone, $course, $year_level, $section, $address, $sid]);
        flash('success', 'Student updated successfully.');
    } else {
        flash('error', 'Full name and course are required.');
    }
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
    <?php if ($can_register): ?>
    <a href="/register.php" class="btn btn-primary btn-sm">＋ Register</a>
    <?php endif; ?>
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
      <thead><tr><th>Photo</th><th>Student No</th><th>Full Name</th><th>Course</th><th>Status</th><th>ID Card</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($students as $s): ?>
        <tr>
          <td>
            <?php if (!empty($s['photo_path']) && is_file(__DIR__ . $s['photo_path'])): ?>
              <img src="<?= e($s['photo_path']) ?>" alt="photo" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:1.5px solid var(--line)">
            <?php else: ?>
              <div style="width:36px;height:36px;border-radius:50%;background:var(--beige);border:1.5px solid var(--line);display:grid;place-items:center;font-size:.6rem;color:var(--muted)">N/A</div>
            <?php endif; ?>
          </td>
          <td><?= e($s['student_no']) ?></td>
          <td><?= e($s['full_name']) ?></td>
          <td><?= e($s['course']) ?></td>
          <td><span class="pill <?= $s['status']==='approved'?'pill-green':($s['status']==='pending'?'pill-amber':'pill-red') ?>"><?= e($s['status']) ?></span></td>
          <td><?= $s['id_status'] ? '<span class="pill pill-maroon">' . e($s['id_status']) . '</span>' : '<span class="pill pill-gray">none</span>' ?></td>
          <td>
            <?php if ($can_edit && $s['status'] === 'approved'): ?>
              <button type="button" class="btn btn-ghost btn-sm" onclick="openEditModal(<?= $s['id'] ?>, '<?= e(addslashes($s['full_name'])) ?>', '<?= e(addslashes($s['email'] ?? '')) ?>', '<?= e(addslashes($s['phone'] ?? '')) ?>', '<?= e(addslashes($s['course'] ?? '')) ?>', '<?= e(addslashes($s['year_level'] ?? '')) ?>', '<?= e(addslashes($s['section'] ?? '')) ?>', '<?= e(addslashes($s['address'] ?? '')) ?>')">Edit</button>
            <?php endif; ?>
            <?php if ($can_photo && $s['status'] === 'approved'): ?>
              <form method="post" enctype="multipart/form-data" style="display:inline" id="photoForm<?= $s['id'] ?>" class="photo-form">
                <input type="hidden" name="action" value="upload_photo">
                <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                <input type="file" name="photo" accept="image/*" style="display:none">
                <label class="btn btn-ghost btn-sm" style="cursor:pointer">📷 File</label>
              </form>
              <button type="button" class="btn btn-ghost btn-sm" onclick="openCameraForStudent(<?= $s['id'] ?>)">📸 Camera</button>
            <?php endif; ?>
            <?php if ($s['status'] === 'pending' && $can_approve): ?>
              <a class="btn btn-green btn-sm" href="?approve=<?= $s['id'] ?>">Accept</a>
            <?php endif; ?>
            <?php if ($s['status'] === 'pending' && $can_reject): ?>
              <a class="btn btn-danger btn-sm" href="?reject=<?= $s['id'] ?>" onclick="return confirm('Reject this application?')">Reject</a>
            <?php endif; ?>
            <?php if ($s['id_status'] === 'active' && $can_print): ?>
              <a class="btn btn-outline btn-sm" href="/id_printing.php?id=<?= $s['id'] ?>">Print</a>
            <?php endif; ?>
            <?php if ($can_remove): ?>
              <a class="btn btn-ghost btn-sm" href="?delete=<?= $s['id'] ?>" onclick="return confirm('Remove this student?')">Remove</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$students): ?><tr><td colspan="6" class="muted">No students found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
