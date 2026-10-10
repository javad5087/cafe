<?php
/* فیلتر بازه‌ی زمانی با لیست‌های کشویی شمسی؛ نیازمند متغیر $r از report_range() */
$ranges = ['today' => 'امروز', 'week' => 'این هفته', 'month' => 'این ماه', 'all' => 'همه'];
$self = basename($_SERVER['SCRIPT_NAME']);
$payKeep = isset($_GET['pay']) && isset(pay_labels()[$_GET['pay']]) ? (string)$_GET['pay'] : '';
?>
<div class="quick no-print">
  <?php foreach ($ranges as $k => $l): ?>
    <a href="<?= e($self) ?>?range=<?= e($k) ?><?= $payKeep ? '&pay=' . e($payKeep) : '' ?>" class="<?= $r['range'] === $k ? 'on' : '' ?>"><?= e($l) ?></a>
  <?php endforeach; ?>
</div>
<form method="get" class="filters no-print">
  <?php if ($payKeep): ?><input type="hidden" name="pay" value="<?= e($payKeep) ?>"><?php endif; ?>
  <div class="f"><label>از تاریخ</label><?= jalali_select('from', $r['from_show']) ?></div>
  <div class="f"><label>تا تاریخ</label><?= jalali_select('to', $r['to_show']) ?></div>
  <button class="btn">اعمال</button>
</form>
