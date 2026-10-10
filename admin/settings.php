<?php
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/admin_layout.php';
require_login();

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['action'] ?? '';

    if ($a === 'info') {
        foreach (['cafe_name', 'subtitle', 'address', 'phone', 'phone2', 'instagram'] as $k) {
            save_setting($k, trim((string)($_POST[$k] ?? '')));
        }
        foreach (['working_hours', 'features'] as $k) {
            $lines = array_filter(array_map('trim', explode("\n", str_replace("\r", '', (string)($_POST[$k] ?? '')))));
            save_setting($k, implode("\n", $lines));
        }
        $old = setting('hero_image');
        if (!empty($_POST['remove_hero']) && $old) {
            if (strpos($old, 'uploads/') === 0) {
                @unlink(BASE_DIR . '/' . $old);
            }
            save_setting('hero_image', '');
        }
        $new = save_image($_FILES['hero'] ?? []);
        if ($new) {
            if ($old && strpos($old, 'uploads/') === 0) {
                @unlink(BASE_DIR . '/' . $old);
            }
            save_setting('hero_image', $new);
        }
        flash('اطلاعات کافه ذخیره شد.');
    } elseif ($a === 'password') {
        $cur = (string)($_POST['current'] ?? '');
        $new = (string)($_POST['new'] ?? '');
        $rep = (string)($_POST['repeat'] ?? '');
        $st = $db->prepare('SELECT * FROM users WHERE id=?');
        $st->execute([$_SESSION['uid']]);
        $u = $st->fetch();
        if (!$u || !password_verify($cur, $u['password_hash'])) {
            flash('رمز فعلی اشتباه است.', 'err');
        } elseif (strlen($new) < 6) {
            flash('رمز جدید باید حداقل ۶ کاراکتر باشد.', 'err');
        } elseif ($new !== $rep) {
            flash('تکرار رمز جدید مطابقت ندارد.', 'err');
        } else {
            $db->prepare('UPDATE users SET password_hash=? WHERE id=?')
               ->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]);
            flash('رمز عبور تغییر کرد.');
        }
    }
    redirect('settings.php');
}

admin_header('اطلاعات و امکانات کافه', 'settings');
$hero = setting('hero_image');
?>
<form method="post" enctype="multipart/form-data" class="settings-dashboard">
  <section class="card settings-card settings-card-main">
    <div class="settings-card-head"><span class="settings-icon">☕</span><div><h2>مشخصات منو</h2><p>نام و عنوانی که در منوی کافه نمایش داده می‌شود.</p></div></div>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="info">
  <div class="fields">
    <div><label>نام کافه</label><input type="text" name="cafe_name" value="<?= e(setting('cafe_name')) ?>"></div>
    <div><label>عنوان بالای «منو»</label><input type="text" name="subtitle" value="<?= e(setting('subtitle')) ?>"></div>
    <div class="full">
      <label>تصویر بالای منو (JPG / PNG / WebP، حداکثر ۵ مگابایت)</label>
      <?php if ($hero): ?>
        <p><img src="../<?= e($hero) ?>" alt="" style="height:110px;border-radius:55px 55px 0 0"></p>
        <label class="check"><input type="checkbox" name="remove_hero"> حذف تصویر فعلی</label>
      <?php endif; ?>
      <input type="file" name="hero" accept="image/jpeg,image/png,image/webp">
    </div>
  </div>
  </section>

  <section class="card settings-card">
    <div class="settings-card-head"><span class="settings-icon">☎</span><div><h2>اطلاعات تماس</h2><p>اطلاعاتی که در انتهای منو نمایش داده می‌شود.</p></div></div>
  <div class="fields">
    <div class="full"><label>ساعات کاری (هر خط یک مورد)</label><textarea name="working_hours"><?= e(setting('working_hours')) ?></textarea></div>
    <div class="full"><label>نشانی و آدرس کافه</label><input type="text" name="address" value="<?= e(setting('address')) ?>"></div>
    <div><label>شماره تماس اول</label><input type="text" name="phone" class="ltr" value="<?= e(setting('phone')) ?>" placeholder="021-12345678"></div>
    <div><label>شماره تماس دوم (اختیاری)</label><input type="text" name="phone2" class="ltr" value="<?= e(setting('phone2')) ?>" placeholder="0912-0000000"></div>
    <div class="full"><label>اینستاگرام (نام کاربری یا لینک کامل)</label><input type="text" name="instagram" class="ltr" value="<?= e(setting('instagram')) ?>" placeholder="borcelle.cafe"></div>
  </div>
  </section>

  <section class="card settings-card">
    <div class="settings-card-head"><span class="settings-icon">✦</span><div><h2>امکانات کافه</h2><p>امکانات را هرکدام در یک خط وارد کنید.</p></div></div>
  <div class="fields">
    <div class="full"><label>هر خط یک امکان (مثلاً وای‌فای رایگان)</label><textarea name="features"><?= e(setting('features')) ?></textarea></div>
  </div>
  </section>
  <div class="settings-save"><button class="btn">ذخیره اطلاعات کافه</button></div>
</form>

<form method="post" class="card settings-password">
  <div class="settings-card-head"><span class="settings-icon">🔒</span><div><h2>تغییر رمز عبور مدیر</h2><p>برای امنیت حساب مدیریت، رمز خود را به‌روزرسانی کنید.</p></div></div>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="password">
  <div class="fields">
    <div class="full"><label>رمز فعلی</label><input type="password" name="current" class="ltr" required></div>
    <div><label>رمز جدید</label><input type="password" name="new" class="ltr" required></div>
    <div><label>تکرار رمز جدید</label><input type="password" name="repeat" class="ltr" required></div>
  </div>
  <button class="btn">تغییر رمز</button>
</form>
<?php admin_footer();
