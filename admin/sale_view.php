<?php
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/admin_layout.php';
require_login();

$db = db();
$modal = isset($_GET['modal']);
$st = $db->prepare('SELECT * FROM sales WHERE id=?');
$st->execute([(int)($_GET['id'] ?? 0)]);
$s = $st->fetch();
if (!$s) {
    if ($modal) {
        http_response_code(404);
        exit('فاکتور پیدا نشد.');
    }
    flash('فاکتور پیدا نشد.', 'err');
    redirect('sales.php');
}
$it = $db->prepare('SELECT * FROM sale_items WHERE sale_id=? ORDER BY id');
$it->execute([$s['id']]);
$items = $it->fetchAll();
$isCredit = $s['payment'] === 'credit';

$invoice = function () use ($s, $items, $isCredit) { ?>
<div class="card invoice">
  <div style="text-align:center;margin-bottom:12px">
    <strong style="font-size:18px;letter-spacing:.1em"><?= e(setting('cafe_name')) ?></strong><br>
    <span>فاکتور شماره <?= fa_num($s['id']) ?></span><br>
    <small style="color:var(--muted)"><?= e(jdate((int)$s['created_at'], 'l j F Y - H:i')) ?></small>
  </div>
  <table>
    <tr><th>محصول</th><th class="num">تعداد</th><th class="num">قیمت واحد</th><th class="num">مبلغ</th></tr>
    <?php foreach ($items as $i): ?>
      <tr>
        <td><?= e($i['name']) ?></td>
        <td class="num"><?= fa_num($i['qty']) ?></td>
        <td class="num"><?= money($i['price']) ?></td>
        <td class="num"><?= money($i['price'] * $i['qty']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
  <div style="margin-top:12px">
    <div class="sum-row"><span>جمع</span><span><?= money($s['subtotal']) ?></span></div>
    <div class="sum-row"><span>تخفیف</span><span><?= money($s['discount']) ?></span></div>
    <div class="sum-row total"><span>قابل پرداخت</span><span><?= money($s['total']) ?></span></div>
    <div class="sum-row"><span>نوع پرداخت</span><span><?= $isCredit ? 'نسیه (پرداخت‌نشده)' : e(pay_label($s['payment'])) ?></span></div>
    <?php if ($s['customer'] !== ''): ?><div class="sum-row"><span>بدهکار</span><span><?= e($s['customer']) ?></span></div><?php endif; ?>
    <?php if (!empty($s['paid_at'])): ?><div class="sum-row"><span>تاریخ تسویه نسیه</span><span><?= e(jdate((int)$s['paid_at'], 'Y/m/d - H:i')) ?></span></div><?php endif; ?>
    <?php if ($s['note'] !== ''): ?><div class="sum-row"><span>توضیحات</span><span><?= e($s['note']) ?></span></div><?php endif; ?>
  </div>
</div>
<?php };

if ($modal) {
    $invoice();
    // فقط برای نسیه: تغییر به یکی از روش‌های پرداخت؛ با بستن پنجره خودکار ثبت می‌شود
    if ($isCredit): ?>
<div class="settle no-print" data-settle data-id="<?= (int)$s['id'] ?>" data-csrf="<?= e(csrf_token()) ?>">
  <label for="settle-pay">تسویه نسیه — تغییر به روش پرداخت:</label>
  <select id="settle-pay">
    <option value="credit" selected>همچنان نسیه (پرداخت‌نشده)</option>
    <?php foreach (pay_methods() as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?>
  </select>
  <small>روش پرداخت را انتخاب کنید؛ با بستن این پنجره، تغییر به‌طور خودکار ثبت می‌شود.</small>
</div>
<?php endif; ?>
<div class="actions no-print">
  <button type="button" class="btn" data-print>چاپ</button>
  <button type="button" class="btn light" data-close>بستن</button>
</div>
<?php
    exit;
}

admin_header('فاکتور شماره ' . fa_num($s['id']), 'sales');
$invoice();
?>
<div class="actions no-print">
  <button class="btn" onclick="window.print()">چاپ</button>
  <a class="btn light" href="sale_new.php">ثبت فروش جدید</a>
  <a class="btn light" href="sales.php">فهرست فروش‌ها</a>
</div>
<?php admin_footer();
