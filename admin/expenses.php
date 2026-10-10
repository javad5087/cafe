<?php
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/admin_layout.php';
require_login();

$db = db();
$cats = expense_categories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($a === 'save') {
        $cat = (string)($_POST['category'] ?? '');
        $amount = to_int($_POST['amount'] ?? 0);
        $note = trim((string)($_POST['note'] ?? ''));
        $day = jalali_from_request($_POST, 'date');
        $ts = ($day ?? mktime(0, 0, 0)) + 43200; // ظهر همان روز
        if (!isset($cats[$cat])) {
            flash('دسته‌ی هزینه را انتخاب کنید.', 'err');
        } elseif ($amount <= 0) {
            flash('مبلغ هزینه را وارد کنید.', 'err');
        } elseif ($id) {
            $db->prepare('UPDATE expenses SET created_at=?, category=?, amount=?, note=? WHERE id=?')
               ->execute([$ts, $cat, $amount, $note, $id]);
            flash('هزینه ویرایش شد.');
        } else {
            $db->prepare('INSERT INTO expenses(created_at,category,amount,note) VALUES(?,?,?,?)')
               ->execute([$ts, $cat, $amount, $note]);
            flash('هزینه ثبت شد.');
        }
    } elseif ($a === 'delete' && $id) {
        $db->prepare('DELETE FROM expenses WHERE id=?')->execute([$id]);
        flash('هزینه حذف شد.');
    }
    $back = preg_replace('~[^\w=&%.\-]~', '', (string)($_POST['back'] ?? ''));
    redirect('expenses.php' . ($back !== '' ? '?' . $back : ''));
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $db->prepare('SELECT * FROM expenses WHERE id=?');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
}

$r = report_range();
$st = $db->prepare('SELECT * FROM expenses WHERE created_at BETWEEN ? AND ? ORDER BY created_at DESC, id DESC LIMIT 500');
$st->execute([$r['from'], $r['to']]);
$rows = $st->fetchAll();

$total = 0;
$byCat = [];
foreach ($rows as $x) {
    $total += (int)$x['amount'];
    $byCat[$x['category']] = ($byCat[$x['category']] ?? 0) + (int)$x['amount'];
}
arsort($byCat);

admin_header('ثبت هزینه‌ها', 'expenses');
?>
<div class="card">
  <h2><?= $edit ? 'ویرایش هزینه' : 'ثبت هزینه‌ی جدید' ?></h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <input type="hidden" name="back" value="<?= e($_SERVER['QUERY_STRING'] ?? '') ?>">
    <div class="fields">
      <div>
        <label>نوع هزینه</label>
        <select name="category" required>
          <?php foreach ($cats as $k => $l): ?>
            <option value="<?= e($k) ?>" <?= ($edit['category'] ?? '') === $k ? 'selected' : '' ?>><?= e($l) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div><label>مبلغ</label><input type="text" inputmode="numeric" name="amount" value="<?= e(fa_digits($edit['amount'] ?? '')) ?>" required></div>
      <div><label>تاریخ پرداخت</label><?= jalali_select('date', (int)($edit['created_at'] ?? time())) ?></div>
      <div class="full"><label>توضیحات (اختیاری — مثلاً شماره قبض یا نام پرسنل)</label><input type="text" name="note" value="<?= e($edit['note'] ?? '') ?>"></div>
    </div>
    <button class="btn"><?= $edit ? 'ذخیره تغییرات' : 'ثبت هزینه' ?></button>
    <?php if ($edit): ?><a class="btn light" href="expenses.php">انصراف</a><?php endif; ?>
  </form>
</div>

<?php require __DIR__ . '/_range_filter.php'; ?>

<div class="grid" style="grid-template-columns:1fr 2fr">
  <div class="stat bad"><small>جمع هزینه‌ها در بازه‌ی انتخابی</small><b><?= money($total) ?></b></div>
  <div class="table-wrap" style="margin:0">
    <table>
      <tr><th>نوع هزینه</th><th class="num">جمع</th></tr>
      <?php foreach ($byCat as $k => $v): ?>
        <tr><td><?= e(expense_label((string)$k)) ?></td><td class="num"><?= money($v) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$byCat): ?><tr><td colspan="2" style="color:var(--muted)">هزینه‌ای ثبت نشده است.</td></tr><?php endif; ?>
    </table>
  </div>
</div>

<div class="table-wrap">
  <table>
    <tr><th>تاریخ</th><th>نوع هزینه</th><th>توضیحات</th><th class="num">مبلغ</th><th></th></tr>
    <?php foreach ($rows as $x): ?>
      <tr>
        <td><?= e(jdate((int)$x['created_at'], 'Y/m/d')) ?></td>
        <td><?= e(expense_label($x['category'])) ?></td>
        <td><?= e($x['note']) ?></td>
        <td class="num"><?= money($x['amount']) ?></td>
        <td>
          <div class="actions">
            <a class="btn sm light" href="expenses.php?edit=<?= (int)$x['id'] ?>">ویرایش</a>
            <form method="post" class="inline" onsubmit="return confirm('این هزینه حذف شود؟')">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$x['id'] ?>">
              <input type="hidden" name="back" value="<?= e($_SERVER['QUERY_STRING'] ?? '') ?>">
              <button class="btn sm danger">حذف</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="5" style="color:var(--muted)">در این بازه هزینه‌ای ثبت نشده است.</td></tr><?php endif; ?>
  </table>
</div>
<?php admin_footer();
