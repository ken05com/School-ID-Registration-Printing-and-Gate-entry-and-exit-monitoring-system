<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    redirect('/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] === 'inactive') {
            $error = 'This account has been deactivated. Contact the administrator.';
        } else {
            login_user((int)$user['id']);
            redirect('/dashboard.php');
        }
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login • <?= e(APP_NAME) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="login-wrap">
  <div class="card login-card">
    <div class="login-head">
      <div class="login-logo">SID</div>
      <h1>Welcome back</h1>
      <div class="sub">Sign in to the School ID System</div>
    </div>
    <?php if ($error): ?><div class="alert alert-error mb-1"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form-grid" action="">
      <div class="field" style="grid-column:1/-1">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required autofocus placeholder="you@school.edu">
      </div>
      <div class="field" style="grid-column:1/-1">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required placeholder="••••••••">
      </div>
      <div style="grid-column:1/-1">
        <button class="btn btn-primary btn-block" type="submit">Login</button>
      </div>
    </form>
    <div class="login-hint">
      Demo accounts (password: <strong>password123</strong>)<br>
      admin@school.edu • registrar@school.edu • idstaff@school.edu • guard@school.edu
    </div>
  </div>
</div>
</body>
</html>
