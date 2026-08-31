<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$user = require_role(['admin', 'registrar', 'id_staff']);
$page = 'register';
$title = 'Student Registration';

$courses = ['BSIT' => 'BS Information Technology', 'BSECE' => 'BS Electronics Engineering', 'BSNUR' => 'BS Nursing', 'BSBA' => 'BS Business Administration', 'BSCS' => 'BS Computer Science', 'BSA' => 'BS Accountancy', 'BSED' => 'BS Education'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $course    = trim($_POST['course'] ?? '');
    $year      = trim($_POST['year_level'] ?? '');
    $section   = trim($_POST['section'] ?? '');
    $address   = trim($_POST['address'] ?? '');

    if ($full_name === '' || $course === '') {
        flash('error', 'Full name and course are required.');
        redirect('/register.php');
    }

    $student_no = next_student_no();
    $stmt = db()->prepare('INSERT INTO students (student_no, full_name, email, phone, course, year_level, section, address, status, registered_by)
                           VALUES (?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$student_no, $full_name, $email, $phone, $course, $year, $section, $address, 'pending', $user['id']]);

    // Notify registrars/admins of the pending registration
    $admins = db()->query("SELECT id FROM users WHERE role IN ('admin','registrar')")->fetchAll();
    foreach ($admins as $a) {
        notify((int)$a['id'], "New student registration pending: {$full_name} ({$student_no})");
    }

    flash('success', "Student registered successfully. Student No: {$student_no}");
    redirect('/register.php');
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="card" style="max-width:760px">
  <h2>Register New Student</h2>
  <p class="muted" style="margin-top:-.5rem">Fill in the student details below. New registrations go to the Registrar for approval.</p>
  <form method="post" class="form-grid mt-2">
    <div class="field" style="grid-column:1/-1"><label>Full Name *</label><input name="full_name" required placeholder="Jane Doe"></div>
    <div class="field"><label>Email</label><input type="email" name="email" placeholder="jane@student.school.edu"></div>
    <div class="field"><label>Mobile No.</label><input name="phone" placeholder="0917-000-0000"></div>
    <div class="field"><label>Course *</label>
      <select name="course" required>
        <option value="">— Select course —</option>
        <?php foreach ($courses as $code => $name): ?>
          <option value="<?= e($code . ' - ' . $name) ?>"><?= e($name) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field"><label>Year Level</label>
      <select name="year_level">
        <option value="">— Select —</option>
        <option>1st Year</option><option>2nd Year</option><option>3rd Year</option><option>4th Year</option><option>5th Year</option>
      </select>
    </div>
    <div class="field"><label>Section</label><input name="section" placeholder="A"></div>
    <div class="field"><label>Address</label><input name="address" placeholder="City, Province"></div>
    <div style="grid-column:1/-1" class="flex between">
      <a href="/students.php" class="btn btn-ghost">Cancel</a>
      <button class="btn btn-primary" type="submit">Submit for Approval</button>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
