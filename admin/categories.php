<?php
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/admin_layout.php';
require_login();

$db = db();
$icons = icon_names();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($a === 'save') {
        $name = trim((string)($_POST['name'] ?? ''));
        $icon = (string)($_POST['icon'] ?? 'coffee');
        if (!isset($icons[$icon])) {
            $icon = 'coffee';
        }
        $sort = to_int($_POST['sort_order'] ?? 0);
        $active = isset($_POST['active']) ? 1 : 0;
        if ($name === '') {
            flash('نام دسته‌بندی را وارد کنید.', 'err');
        } elseif ($id) {
            $db->prepare('UPDATE categories SET name=?, icon=?, sort_order=?, active=? WHERE id=?')
               ->execute([$name, $icon, $sort, $active, $id]);
            flash('دسته‌بندی ذخیره شد.');
        } else {
            $db->prepare('INSERT INTO categories(name,icon,sort_order,active) VALUES(?,?,?,?)')
               ->execute([$name, $icon, $sort, $active]);
            flash('دسته‌بندی جدید افزوده شد.');
        }
    } elseif ($a === 'toggle' && $id) {
        $db->prepare('UPDATE categories SET active = 1 - active WHERE id=?')->execute([$id]);
    } elseif ($a === 'delete' && $id) {
        $st = $db->prepare('SELECT COUNT(*) FROM products WHERE category_id=?');
        $st->execute([$id]);
        if ((int)$st->fetchColumn() > 0) {
            flash('این دسته محصول دارد؛ ابتدا محصولات را حذف یا منتقل کنید (یا دسته را غیرفعال کنید).', 'err');
        } else {
            $db->prepare('DELETE FROM categories WHERE id=?')->execute([$id]);
            flash('دسته‌بندی حذف شد.');
        }
    }
    redirect('categories.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $db->prepare('SELECT * FROM categories WHERE id=?');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
}
$cats = $db->query('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id=c.id) cnt
                    FROM categories c ORDER BY sort_order, id')->fetchAll();

admin_header('دسته‌بندی‌ها', 'categories');
?>
<div class="card">
  <h2><?= $edit ? 'ویرایش دسته‌بندی' : 'افزودن دسته‌بندی' ?></h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <div class="fields">
      <div><label>نام دسته (فارسی)</label><input type="text" name="name" value="<?= e($edit['name'] ?? '') ?>" required></div>
      <div><label>ترتیب نمایش (عدد کوچک‌تر بالاتر)</label><input type="text" inputmode="numeric" name="sort_order" value="<?= e(fa_digits($edit['sort_order'] ?? count($cats) + 1)) ?>"></div>
      <div class="full">
        <label>آیکون</label>
        <div class="icon-pick">
          <?php foreach ($icons as $key => $label): ?>
            <label>
              <input type="radio" name="icon" value="<?= e($key) ?>" <?= ($edit['icon'] ?? 'coffee') === $key ? 'checked' : '' ?>>
              <span><?= icon($key, 28) ?><?= e($label) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="full"><label class="check"><input type="checkbox" name="active" <?= ($edit['active'] ?? 1) ? 'checked' : '' ?>> نمایش در منو</label></div>
    </div>
    <button class="btn"><?= $edit ? 'ذخیره تغییرات' : 'افزودن' ?></button>
    <?php if ($edit): ?><a class="btn light" href="categories.php">انصراف</a><?php endif; ?>
  </form>
</div>

<div class="table-wrap">
  <table>
    <tr><th>ترتیب</th><th>آیکون</th><th>نام</th><th>تعداد محصول</th><th>وضعیت</th><th></th></tr>
    <?php foreach ($cats as $c): ?>
      <tr>
        <td><?= fa_num($c['sort_order']) ?></td>
        <td><?= icon($c['icon'], 24) ?></td>
        <td><?= e($c['name']) ?></td>
        <td><?= fa_num($c['cnt']) ?></td>
        <td><span class="badge <?= $c['active'] ? '' : 'off' ?>"><?= $c['active'] ? 'فعال' : 'مخفی' ?></span></td>
        <td>
          <div class="actions">
            <a class="btn sm light" href="categories.php?edit=<?= (int)$c['id'] ?>">ویرایش</a>
            <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn sm light"><?= $c['active'] ? 'مخفی‌کردن' : 'فعال‌کردن' ?></button></form>
            <form method="post" class="inline" onsubmit="return confirm('این دسته‌بندی حذف شود؟')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn sm danger">حذف</button></form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$cats): ?><tr><td colspan="6" style="color:var(--muted)">دسته‌ای ثبت نشده است.</td></tr><?php endif; ?>
  </table>
</div>
<?php admin_footer();
