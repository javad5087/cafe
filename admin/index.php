<?php
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/admin_layout.php';
require_login();

$db = db();
$todayFrom = mktime(0, 0, 0);
$todayTo = $todayFrom + 86399;
$today = sales_summary($todayFrom, $todayTo);
$month = sales_summary(jalali_month_start(), $todayTo);
$credit = credit_outstanding();
$monthExp = report_data(jalali_month_start(), $todayTo, 1)['exp']['total'];
$productCount = (int)$db->query('SELECT COUNT(*) FROM products WHERE active=1')->fetchColumn();

$top = $db->prepare('SELECT si.name, SUM(si.qty) q FROM sale_items si JOIN sales s ON s.id=si.sale_id
                     WHERE s.created_at BETWEEN ? AND ? GROUP BY si.name ORDER BY q DESC LIMIT 5');
$top->execute([$todayFrom, $todayTo]);
$topRows = $top->fetchAll();

$last = $db->query('SELECT * FROM sales ORDER BY id DESC LIMIT 8')->fetchAll();

$hash = (string)$db->query("SELECT password_hash FROM users WHERE username='admin'")->fetchColumn();

admin_header('داشبورد', 'index');
if ($hash !== '' && password_verify('admin123', $hash)): ?>
  <div class="flash warn">رمز عبور پیش‌فرض (admin123) هنوز تغییر نکرده است. لطفاً از بخش «اطلاعات کافه» آن را عوض کنید.</div>
<?php endif; ?>

<div class="grid">
  <div class="stat"><small>فروش امروز</small><b><?= money($today['total']) ?></b></div>
  <div class="stat"><small>تعداد فاکتور امروز</small><b><?= fa_num($today['count']) ?></b></div>
  <div class="stat"><small>فروش این ماه (<?= e(jdate(time(), 'F')) ?>)</small><b><?= money($month['total']) ?></b></div>
  <div class="stat"><small>محصولات فعال منو</small><b><?= fa_num($productCount) ?></b></div>
  <div class="stat warn"><small>نسیه‌های تسویه‌نشده (<?= fa_num($credit['count']) ?> فاکتور)</small><b><?= money($credit['total']) ?></b></div>
  <div class="stat bad"><small>هزینه‌های این ماه</small><b><?= money($monthExp) ?></b></div>
</div>

<div style="display:flex;gap:10px;margin-bottom:20px" class="no-print">
  <a class="btn" href="sale_new.php">ثبت فروش جدید</a>
  <a class="btn light" href="reports.php">گزارش حسابداری</a>
  <a class="btn light" href="expenses.php">ثبت هزینه</a>
</div>

<div class="grid" style="grid-template-columns:2fr 1fr">
  <div>
    <h2 style="font-size:17px">آخرین فروش‌ها</h2>
    <div class="table-wrap">
      <table>
        <tr><th>شماره</th><th>تاریخ</th><th>پرداخت</th><th class="num">مبلغ</th></tr>
        <?php foreach ($last as $s): ?>
          <tr>
            <td><a href="sale_view.php?id=<?= (int)$s['id'] ?>">#<?= fa_num($s['id']) ?></a></td>
            <td><?= e(jdate((int)$s['created_at'], 'Y/m/d - H:i')) ?></td>
            <td><?= $s['payment'] === 'credit' ? '<span class="badge credit">نسیه</span>' : e(pay_label($s['payment'])) ?></td>
            <td class="num"><?= money($s['total']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$last): ?><tr><td colspan="4" style="color:var(--muted)">هنوز فروشی ثبت نشده است.</td></tr><?php endif; ?>
      </table>
    </div>
  </div>
  <div>
    <h2 style="font-size:17px">پرفروش‌های امروز</h2>
    <div class="table-wrap">
      <table>
        <?php foreach ($topRows as $r): ?>
          <tr><td><?= e($r['name']) ?></td><td class="num"><?= fa_num($r['q']) ?> عدد</td></tr>
        <?php endforeach; ?>
        <?php if (!$topRows): ?><tr><td style="color:var(--muted)">داده‌ای وجود ندارد.</td></tr><?php endif; ?>
      </table>
    </div>
  </div>
</div>
<?php admin_footer();
