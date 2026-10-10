<?php
/* تغییر نسیه به یکی از روش‌های پرداخت (با بستن مودال فاکتور فراخوانی می‌شود) */
require __DIR__ . '/../inc/bootstrap.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$pay = (string)($_POST['payment'] ?? '');
if (!$id || !isset(pay_methods()[$pay])) {
    http_response_code(422);
    echo json_encode(['ok' => false]);
    exit;
}

// فقط فاکتورهای نسیه قابل تسویه‌اند
$st = db()->prepare("UPDATE sales SET payment = ?, paid_at = ? WHERE id = ? AND payment = 'credit'");
$st->execute([$pay, time(), $id]);
$ok = $st->rowCount() > 0;
if ($ok) {
    flash('نسیه فاکتور شماره ' . fa_num($id) . ' با روش «' . pay_label($pay) . '» تسویه شد.');
}
echo json_encode(['ok' => $ok]);
