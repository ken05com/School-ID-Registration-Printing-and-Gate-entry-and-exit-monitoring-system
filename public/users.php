<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$user = require_role(['admin']);
$page = 'users';
$title = 'User Management';

// Create user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create') {
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'student';
        $pw = $_POST['password'] ?? '';

        if ($name && $email && $pw) {
            try {
                $hash = password_hash($pw, PASSWORD_BCRYPT);
                db()->prepare('INSERT INTO users (full_name, email, password, role) VALUES (?,?,?,?)')
                    ->execute([$name, $email, $hash, $role]);
                flash('success', 'User created.');
            } catch (Throwable $ex) {
                flash('error', 'Could not create user (email may already exist).');
            }
        } else {
            flash('error', 'Fill in all fields.');
        }
    } elseif ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id !== (int)$user['id']) { // don't deactivate yourself
            $u = db()->prepare('SELECT status FROM users WHERE id=?'); $u->execute([$id]);
            $cur = $u->fetch();
            if ($cur) {
                $new = $cur['status'] === 'active' ? 'inactive' : 'active';
                db()->prepare('UPDATE users SET status=? WHERE id=?')->execute([$new, $id]);
                flash('success', 'User status updated.');
            }
        } else {
            flash('error', 'You cannot deactivate your own account.');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id !== (int)$user['id']) {
            db()->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
            flash('success', 'User deleted.');
        } else {
            flash('error', 'You cannot delete your own account.');
        }
    }
    redirect('/users.php');
}

$users = db()->query('SELECT * FROM users ORDER BY role, full_name')->fetchAll();
$roles = ['admin', 'registrar', 'id_staff', 'security_guard', 'student'];
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="grid grid-2">
  <div class="card">
    <div class="section-title">Add New User</div>
    <form method="post" class="form-grid">
      <input type="hidden" name="action" value="create">
      <div class="field" style="grid-column:1/-1"><label>Full Name</label><input name="full_name" required></div>
      <div class="field" style="grid-column:1/-1"><label>Email</label><input type="email" name="email" required></div>
      <div class="field"><label>Role</label>
        <select name="role">
          <?php foreach ($roles as $r): ?><option value="<?= $r ?>"><?= e(role_label($r)) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Password</label><input type="password" name="password" required></div>
      <div style="grid-column:1/-1"><button class="btn btn-primary btn-block" type="submit">Create User</button></div>
    </form>
  </div>

  <div class="card">
    <div class="section-title">System Users</div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= e($u['full_name']) ?><div class="muted" style="font-size:.72rem"><?= e($u['email']) ?></div></td>
            <td><span class="pill pill-maroon"><?= e(role_label($u['role'])) ?></span></td>
            <td><span class="pill <?= $u['status']==='active'?'pill-green':'pill-gray' ?>"><?= e($u['status']) ?></span></td>
            <td class="flex" style="gap:.4rem">
              <form method="post" style="display:inline">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <button class="btn btn-ghost btn-sm" type="submit"><?= $u['status']==='active'?'Disable':'Enable' ?></button>
              </form>
              <form method="post" style="display:inline" onsubmit="return confirm('Delete user?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <button class="btn btn-danger btn-sm" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
