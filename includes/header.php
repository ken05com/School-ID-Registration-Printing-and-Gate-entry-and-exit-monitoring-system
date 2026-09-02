<?php
/**
 * Shared page header with sidebar navigation (maroon/beige minimalist).
 * Requires: auth.php, functions.php included before.
 */

$__user = $__user ?? ($user ?? null);
$__page = $__page ?? '';
$__title = $__title ?? '';
$__flash = take_flashes();
$__unread = unread_notifications($__user['id'] ?? null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($__title) ?> • <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="app">
  <aside class="sidebar">
    <div class="brand">
      <div class="brand-logo">SID</div>
      <div>
        <div class="brand-name">School ID</div>
        <div class="brand-sub">System</div>
      </div>
    </div>
    <?php
      $__role = $__user['role'] ?? '';
      $__can_manage_students = in_array($__role, ['admin','registrar','id_staff'], true);
      $__can_register       = in_array($__role, ['admin','registrar'], true);
      $__can_print          = in_array($__role, ['admin','id_staff'], true);
      $__can_gate           = in_array($__role, ['admin','security_guard'], true);
      $__can_users          = $__role === 'admin';
    ?>
    <nav class="nav">
      <a href="/dashboard.php" class="nav-link<?= $__page==='dashboard'?' active':'' ?>">
        <span class="ico">◈</span> Dashboard</a>
      <?php if ($__can_manage_students): ?>
      <a href="/students.php" class="nav-link<?= $__page==='students'?' active':'' ?>">
        <span class="ico">▤</span> Student Management</a>
      <?php endif; ?>
      <?php if ($__can_register): ?>
      <a href="/register.php" class="nav-link<?= $__page==='register'?' active':'' ?>">
        <span class="ico">＋</span> Student Registration</a>
      <?php endif; ?>
      <?php if ($__can_print): ?>
      <a href="/id_printing.php" class="nav-link<?= $__page==='id_printing'?' active':'' ?>">
        <span class="ico">▣</span> ID Printing</a>
      <?php endif; ?>
      <?php if ($__can_gate): ?>
      <a href="/gate.php" class="nav-link<?= $__page==='gate'?' active':'' ?>">
        <span class="ico">◉</span> Gate Monitoring</a>
      <?php endif; ?>
      <a href="/reports.php" class="nav-link<?= $__page==='reports'?' active':'' ?>">
        <span class="ico">◫</span> Reports</a>
      <?php if ($__can_users): ?>
      <a href="/users.php" class="nav-link<?= $__page==='users'?' active':'' ?>">
        <span class="ico">☰</span> User Management</a>
      <?php endif; ?>
    </nav>
    <div class="sidebar-foot">
      <div class="side-user">
        <div class="avatar"><?= e(strtoupper(substr($__user['full_name'] ?? 'U',0,1))) ?></div>
        <div>
          <div class="side-name"><?= e($__user['full_name'] ?? '') ?></div>
          <div class="side-role"><?= e(role_label($__user['role'] ?? '')) ?></div>
        </div>
      </div>
      <a href="/logout.php" class="logout">Log out</a>
    </div>
  </aside>

  <main class="main">
    <header class="topbar">
      <div class="page-title"><?= e($__title) ?></div>
      <div class="topbar-right">
        <a href="/notifications.php" class="bell" title="Notifications">
          <span>🔔</span>
          <?php if ($__unread > 0): ?><span class="badge"><?= $__unread ?></span><?php endif; ?>
        </a>
        <div class="top-role"><?= e(role_label($__user['role'] ?? '')) ?></div>
      </div>
    </header>

    <?php foreach ($__flash as $f): ?>
      <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
    <?php endforeach; ?>

    <div class="content">
