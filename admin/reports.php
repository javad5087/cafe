<?php
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/admin_layout.php';
require_login();

$r = report_range();
$d = report_data($r['from'], $r['to'], 10);
$sum = $d['sum'];
$byPay = $d['byPay'];
$byDay = array_reverse($d['byDay'], true);
$maxDay = $byDay ? max(array_column($byDay, 'total')) : 0;
$extraPay = array_diff_key($byPay, pay_labels());

admin_header('گزارش و حسابداری فروش', isset($_GET['print']) ? 'reports_print' : 'reports');
?>
<div class="no-print" style="margin-bottom:14px">
  <button type="button" class="btn" data-modal="tpl-print">چاپ گزارش دوره‌ای</button>
</div>

<?php require __DIR__ . '/_range_filter.php'; ?>

<div class="grid">
  <div class="stat"><small>فروش خالص</small><b><?= money($sum['total']) ?></b></div>
  <div class="stat"><small>تعداد فاکتور</small><b><?= fa_num($sum['count']) ?></b></div>
  <div class="stat"><small>میانگین هر فاکتور</small><b><?= money($sum['avg']) ?></b></div>
  <div class="stat"><small>جمع تخفیف‌ها</small><b><?= money($sum['discount']) ?></b></div>
  <div class="stat"><small>فروش ناخالص (قبل از تخفیف)</small><b><?= money($sum['subtotal']) ?></b></div>
  <div class="stat warn"><small>نسیه‌ی دریافت‌نشده</small><b><?= money($d['credit']) ?></b></div>
  <div class="stat bad"><small>جمع هزینه‌ها</small><b><?= money($d['exp']['total']) ?></b></div>
  <div class="stat <?= $d['balance'] < 0 ? 'bad' : 'good' ?>"><small>مانده (فروش خالص − هزینه‌ها)</small><b><?= money_html($d['balance']) ?></b></div>
</div>

<div class="grid" style="grid-template-columns:1fr 1fr">
  <div>
    <h2 style="font-size:17px">تفکیک نوع پرداخت</h2>
    <div class="table-wrap"><table>
      <?php foreach (pay_labels() as $k => $l): ?>
        <tr><td><?= e($l) ?><?= $k === 'credit' ? ' <span class="sub-note">دریافت‌نشده</span>' : '' ?></td><td class="num"><?= money($byPay[$k] ?? 0) ?></td></tr>
      <?php endforeach; ?>
      <?php foreach ($extraPay as $k => $v): ?>
        <tr><td><?= e(pay_label((string)$k)) ?></td><td class="num"><?= money($v) ?></td></tr>
      <?php endforeach; ?>
    </table></div>
  </div>
  <div>
    <h2 style="font-size:17px">هزینه‌ها به تفکیک نوع</h2>
    <div class="table-wrap"><table>
      <?php foreach ($d['exp']['byCat'] as $k => $v): ?>
        <tr><td><?= e(expense_label((string)$k)) ?></td><td class="num"><?= money($v) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$d['exp']['byCat']): ?><tr><td style="color:var(--muted)">هزینه‌ای در این بازه ثبت نشده است.</td></tr><?php endif; ?>
    </table></div>
  </div>
</div>

<h2 style="font-size:17px">۱۰ محصول پرفروش</h2>
<div class="table-wrap"><table>
  <tr><th>محصول</th><th class="num">تعداد</th><th class="num">مبلغ</th></tr>
  <?php foreach ($d['top'] as $t): ?>
    <tr><td><?= e($t['name']) ?></td><td class="num"><?= fa_num($t['q']) ?></td><td class="num"><?= money($t['amount']) ?></td></tr>
  <?php endforeach; ?>
  <?php if (!$d['top']): ?><tr><td colspan="3" style="color:var(--muted)">داده‌ای نیست.</td></tr><?php endif; ?>
</table></div>

<h2 style="font-size:17px">فروش روزانه</h2>
<div class="table-wrap">
  <table>
    <tr><th>تاریخ</th><th>فاکتور</th><th class="num">فروش</th><th style="width:30%"></th></tr>
    <?php foreach ($byDay as $row): ?>
      <tr>
        <td><?= e(jdate($row['ts'], 'l Y/m/d')) ?></td>
        <td><?= fa_num($row['count']) ?></td>
        <td class="num"><?= money($row['total']) ?></td>
        <td><div class="bar"><i style="width:<?= $maxDay ? round($row['total'] / $maxDay * 100) : 0 ?>%"></i></div></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$byDay): ?><tr><td colspan="4" style="color:var(--muted)">در این بازه فروشی ثبت نشده است.</td></tr><?php endif; ?>
  </table>
</div>

<!-- پنجره‌ی چاپ گزارش دوره‌ای (مودال) -->
<template id="tpl-print">
  <form method="get" action="report_print.php" target="_blank" class="print-modal">
    <h2>چاپ گزارش دوره‌ای</h2>
    <label>تاریخ مرجع (دوره‌ای که شامل این روز است)</label>
    <?= jalali_select('ref', time()) ?>
    <div class="btns">
      <button class="btn" name="type" value="daily">گزارش روزانه</button>
      <button class="btn" name="type" value="weekly">گزارش هفتگی</button>
      <button class="btn" name="type" value="monthly">گزارش ماهانه</button>
      <button class="btn" name="type" value="yearly">گزارش سالانه</button>
    </div>
  </form>
</template>
<?php admin_footer();
