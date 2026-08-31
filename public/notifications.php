<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$user = require_login();
$page = 'notifications';
$title = 'Notifications';

// Mark all as read
if (($_GET['clear'] ?? '') === '1') {
    db()->prepare('UPDATE notifications SET is_read = 1 WHERE recipient_id = ?')->execute([$user['id']]);
    redirect('/notifications.php');
}

$stmt = db()->prepare('SELECT * FROM notifications WHERE recipient_id = ? OR recipient_id IS NULL ORDER BY created_at DESC LIMIT 30');
$stmt->execute([$user['id']]);
$notes = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="card" style="max-width:720px">
  <div class="flex between mb-1">
    <h2>Notifications</h2>
    <a class="btn btn-ghost btn-sm" href="?clear=1">Mark all read</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Message</th><th>When</th></tr></thead>
      <tbody>
      <?php foreach ($notes as $n): ?>
        <tr style="<?= $n['is_read'] ? 'opacity:.6' : 'font-weight:600' ?>">
          <td><?= e($n['message']) ?></td>
          <td><?= e(date('M d, H:i', strtotime($n['created_at']))) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$notes): ?><tr><td colspan="2" class="muted">No notifications.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
