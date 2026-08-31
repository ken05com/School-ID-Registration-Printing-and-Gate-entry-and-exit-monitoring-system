<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
$user = require_role(['admin', 'security_guard']);
$page = 'gate';
$title = 'Gate Monitoring';

$result = null; // { class, title, detail_name, detail_id, direction }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = trim($_POST['qr'] ?? '');

    if ($input === '') {
        $result = ['class' => 'result-invalid', 'title' => 'No code scanned', 'detail' => 'Please scan or enter the QR code value.'];
    } else {
        // Look up by QR token OR id_number (both printed on the card)
        $stmt = db()->prepare("SELECT s.id AS sid, s.full_name, s.student_no, s.status,
                                      si.id_number, si.qr_code, si.qr_status, si.expiry_date
                               FROM school_ids si
                               JOIN students s ON s.id = si.student_id
                               WHERE si.qr_code = ? OR si.id_number = ? OR s.student_no = ?
                               LIMIT 1");
        $stmt->execute([$input, $input, $input]);
        $card = $stmt->fetch();

        if (!$card) {
            $result = ['class' => 'result-invalid', 'title' => 'Invalid ID', 'detail' => "No record matches '{$input}'. This card is not registered."];
        } elseif ($card['status'] !== 'approved') {
            $result = ['class' => 'result-invalid', 'title' => 'Not Approved', 'detail' => $card['full_name'] . ' is not yet approved.'];
        } elseif ($card['qr_status'] === 'blocked') {
            $result = ['class' => 'result-blocked', 'title' => 'Blocked ID', 'detail' => $card['full_name'] . " 's ID has been blocked."];
        } elseif ($card['qr_status'] === 'expired' || (!empty($card['expiry_date']) && strtotime($card['expiry_date']) < time())) {
            $result = ['class' => 'result-expired', 'title' => 'Expired ID', 'detail' => $card['full_name'] . " 's ID has expired."];
        } else {
            // Determine direction: alternate based on last log for this student
            $last = db()->prepare("SELECT direction FROM gate_logs WHERE student_id=? ORDER BY id DESC LIMIT 1");
            $last->execute([$card['sid']]);
            $lastRow = $last->fetch();
            $direction = ($lastRow && $lastRow['direction'] === 'entry') ? 'exit' : 'entry';

            db()->prepare("INSERT INTO gate_logs (student_id, id_number, direction, guard_id, status)
                           VALUES (?,?,?,?,?)")->execute([$card['sid'], $card['id_number'], $direction, $user['id'], 'valid']);

            $result = [
                'class' => 'result-valid',
                'title' => '✓ ' . strtoupper($direction),
                'detail' => $card['full_name'] . ' • ' . $card['id_number'],
                'direction' => $direction,
            ];
        }
    }
}

// Recent gate activity for the guard
$recent = db()->query("SELECT gl.direction, gl.status, gl.scanned_at, s.full_name, gl.id_number
                       FROM gate_logs gl JOIN students s ON s.id=gl.student_id
                       ORDER BY gl.id DESC LIMIT 12")->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="grid grid-2">
  <div class="card">
    <div class="section-title">Scan Student QR</div>
    <div class="gate-screen">
      <div class="id-field-qr" style="margin-bottom:.5rem">Point the QR card at the camera, or type the code below.</div>
      <video id="scanner" width="100%" playsinline muted style="border-radius:10px;background:#2d2023;max-height:220px"></video>
      <p class="muted" style="font-size:.8rem;margin:.6rem 0">Camera scanning uses the browser's BarcodeDetector (Chrome/Edge).</p>
      <form method="post" class="mt-1">
        <input type="text" name="qr" id="qrinput" class="gate-input" placeholder="Enter or scan QR code…" autofocus>
        <button class="btn btn-primary btn-block mt-1" type="submit">Check ID</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="section-title">Result</div>
    <?php if ($result): ?>
      <div class="result-box <?= $result['class'] ?>">
        <div style="font-size:1.3rem"><?= e($result['title']) ?></div>
        <div style="font-weight:500;margin-top:.4rem;font-size:.95rem"><?= e($result['detail']) ?></div>
      </div>
    <?php else: ?>
      <p class="muted" style="margin-top:.5rem">Waiting for a scan. The result will appear here.</p>
    <?php endif; ?>

    <div class="section-title mt-2">Recent Activity</div>
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
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
