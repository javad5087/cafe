<?php
require __DIR__ . '/../inc/bootstrap.php';

if (!empty($_SESSION['uid'])) {
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $st = db()->prepare('SELECT * FROM users WHERE username = ?');
    $st->execute([trim((string)($_POST['username'] ?? ''))]);
    $u = $st->fetch();
    if ($u && password_verify((string)($_POST['password'] ?? ''), $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$u['id'];
        redirect('index.php');
    }
    sleep(1);
    $error = 'نام کاربری یا رمز عبور اشتباه است.';
}
?><!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ورود به پنل مدیریت</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vazirmatn@33.0.3/Vazirmatn-font-face.css">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="login-wrap">
  <form method="post" class="card login">
    <div class="logo"><?= icon('coffee', 30) ?><span><?= e(setting('cafe_name')) ?></span></div>
    <?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>
    <?= csrf_field() ?>
    <div class="fields">
      <div class="full"><label>نام کاربری</label><input type="text" name="username" class="ltr" autofocus required></div>
      <div class="full"><label>رمز عبور</label><input type="password" name="password" class="ltr" required></div>
    </div>
    <button class="btn" style="width:100%">ورود</button>
  </form>
</div>
</body>
</html>
