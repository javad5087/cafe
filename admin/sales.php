<?php
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/admin_layout.php';
require_login();

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $db->prepare('DELETE FROM sales WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]);
    flash('فاکتور حذف شد.');
    $back = preg_replace('~[^\w=&%.\-]~', '', (string)($_POST['back'] ?? ''));
    redirect('sales.php' . ($back !== '' ? '?' . $back : ''));
}

// فیلتر روش پرداخت (مثلاً pay=credit برای نسیه‌ها)
$pay = isset($_GET['pay']) && isset(pay_labels()[$_GET['pay']]) ? (string)$_GET['pay'] : null;
if ($pay === 'credit' && !isset($_GET['range']) && !isset($_GET['from_y'])) {
    $_GET['range'] = 'all'; // نسیه‌های قدیمی هم دیده شوند
}

$r = report_range();
$sql = 'SELECT s.*, (SELECT COALESCE(SUM(qty),0) FROM sale_items WHERE sale_id=s.id) items
        FROM sales s WHERE created_at BETWEEN ? AND ?';
$args = [$r['from'], $r['to']];
if ($pay !== null) {
    $sql .= ' AND payment = ?';
    $args[] = $pay;
}
$st = $db->prepare($sql . ' ORDER BY created_at DESC, id DESC LIMIT 500');
$st->execute($args);
$rows = $st->fetchAll();
$sum = sales_summary($r['from'], $r['to'], $pay);

$title = $pay === 'credit' ? 'نسیه‌های تسویه‌نشده' : 'فهرست فروش‌ها';
admin_header($title, $pay === 'credit' ? 'credit' : 'sales');
require __DIR__ . '/_range_filter.php';
?>
<div class="quick no-print">
  <a href="sales.php?range=<?= e($r['range'] === 'custom' ? 'month' : $r['range']) ?>" class="<?= $pay === null ? 'on' : '' ?>">همه پرداخت‌ها</a>
  <a href="sales.php?pay=credit&range=<?= e($r['range'] === 'custom' ? 'all' : $r['range']) ?>" class="<?= $pay === 'credit' ? 'on' : '' ?>">فقط نسیه</a>
</div>

<div class="grid">
  <div class="stat"><small>تعداد فاکتور</small><b><?= fa_num($sum['count']) ?></b></div>
  <div class="stat<?= $pay === 'credit' ? ' warn' : '' ?>"><small><?= $pay === 'credit' ? 'جمع نسیه‌های دریافت‌نشده' : 'جمع فروش' ?></small><b><?= money($sum['total']) ?></b></div>
  <div class="stat"><small>جمع تخفیف‌ها</small><b><?= money($sum['discount']) ?></b></div>
</div>

<div class="table-wrap">
  <table>
    <tr><th>شماره</th><th>تاریخ و ساعت</th><th>تعداد اقلام</th><th>پرداخت</th><th class="num">مبلغ</th><th></th></tr>
    <?php foreach ($rows as $s): ?>
      <tr>
        <td>#<?= fa_num($s['id']) ?></td>
        <td><?= e(jdate((int)$s['created_at'], 'l Y/m/d - H:i')) ?></td>
        <td><?= fa_num($s['items']) ?></td>
        <td>
          <?php if ($s['payment'] === 'credit'): ?>
            <span class="badge credit">نسیه</span>
            <?php if ($s['customer'] !== ''): ?><span class="sub-note"><?= e($s['customer']) ?></span><?php endif; ?>
          <?php else: ?>
            <?= e(pay_label($s['payment'])) ?>
            <?php if (!empty($s['paid_at'])): ?><span class="sub-note">تسویه‌شده از نسیه — <?= e(jdate((int)$s['paid_at'], 'Y/m/d')) ?></span><?php endif; ?>
          <?php endif; ?>
        </td>
        <td class="num"><?= money($s['total']) ?></td>
        <td>
          <div class="actions">
            <button type="button" class="btn sm light" data-invoice="<?= (int)$s['id'] ?>">مشاهده فاکتور</button>
            <form method="post" class="inline" onsubmit="return confirm('این فاکتور حذف شود؟')">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
              <input type="hidden" name="back" value="<?= e($_SERVER['QUERY_STRING'] ?? '') ?>">
              <button class="btn sm danger">حذف</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="6" style="color:var(--muted)">فروشی در این بازه ثبت نشده است.</td></tr><?php endif; ?>
  </table>
</div>
<?php if (count($rows) >= 500): ?><p style="color:var(--muted);font-size:13px">فقط ۵۰۰ فاکتور آخر نمایش داده شده است؛ بازه را کوچک‌تر کنید.</p><?php endif; ?>
<?php admin_footer();
