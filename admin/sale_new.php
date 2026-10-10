<?php
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/admin_layout.php';
require_login();

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $items = $_POST['items'] ?? [];
    $lines = [];
    $sub = 0;
    $find = $db->prepare('SELECT * FROM products WHERE id=? AND active=1');
    if (is_array($items)) {
        foreach ($items as $pid => $qty) {
            $qty = (int)$qty;
            if ($qty <= 0 || $qty > 999) {
                continue;
            }
            $find->execute([(int)$pid]);
            $p = $find->fetch();
            if (!$p) {
                continue;
            }
            $lines[] = [$p, $qty];
            $sub += (int)$p['price'] * $qty;
        }
    }
    if (!$lines) {
        flash('هیچ محصولی انتخاب نشده است.', 'err');
        redirect('sale_new.php');
    }
    $disc = min(to_int($_POST['discount'] ?? 0), $sub);
    $pay = (string)($_POST['payment'] ?? 'cash');
    if (!isset(pay_labels()[$pay])) {
        $pay = 'cash';
    }
    $note = trim((string)($_POST['note'] ?? ''));
    $customer = $pay === 'credit' ? trim((string)($_POST['customer'] ?? '')) : '';

    $db->beginTransaction();
    try {
        $db->prepare('INSERT INTO sales(created_at,subtotal,discount,total,payment,note,customer) VALUES(?,?,?,?,?,?,?)')
           ->execute([time(), $sub, $disc, $sub - $disc, $pay, $note, $customer]);
        $sid = (int)$db->lastInsertId();
        $ins = $db->prepare('INSERT INTO sale_items(sale_id,product_id,name,price,qty) VALUES(?,?,?,?,?)');
        foreach ($lines as [$p, $qty]) {
            $ins->execute([$sid, $p['id'], $p['name'], $p['price'], $qty]);
        }
        $db->commit();
    } catch (Throwable $ex) {
        $db->rollBack();
        flash('ثبت فروش ناموفق بود.', 'err');
        redirect('sale_new.php');
    }
    flash('فروش با موفقیت ثبت شد.');
    redirect('sale_view.php?id=' . $sid);
}

$cats = $db->query('SELECT * FROM categories WHERE active=1 ORDER BY sort_order, id')->fetchAll();
$st = $db->prepare('SELECT * FROM products WHERE category_id=? AND active=1 ORDER BY sort_order, id');

admin_header('ثبت فروش', 'sale_new');
?>
<form method="post" id="pos-form">
  <?= csrf_field() ?>
  <div class="pos">
    <div>
      <div class="card" style="padding:10px 14px">
        <input type="text" id="pos-search" placeholder="جستجوی محصول...">
      </div>
      <?php foreach ($cats as $c):
          $st->execute([$c['id']]);
          $ps = $st->fetchAll();
          if (!$ps) continue; ?>
        <div class="pos-cat">
          <h3><?= icon($c['icon'], 18) ?> <?= e($c['name']) ?></h3>
          <div class="pos-list">
            <?php foreach ($ps as $p): ?>
              <div class="pos-item" data-price="<?= (int)$p['price'] ?>" data-name="<?= e($p['name']) ?>">
                <div class="n"><span><?= e($p['name']) ?></span><small><?= money($p['price']) ?></small></div>
                <div class="qty-box">
                  <button type="button" class="minus">−</button>
                  <span class="q">۰</span>
                  <button type="button" class="plus">+</button>
                  <input type="hidden" name="items[<?= (int)$p['id'] ?>]" value="0">
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$cats): ?><p style="color:var(--muted)">ابتدا محصولات منو را تعریف کنید.</p><?php endif; ?>
    </div>

    <div class="card cart">
      <h2>صورت‌حساب</h2>
      <ul id="cart-list"><li style="color:var(--muted)">هنوز محصولی انتخاب نشده است.</li></ul>
      <div class="fields">
        <div><label>تخفیف</label><input type="text" inputmode="numeric" name="discount" id="discount" value="۰"></div>
        <div>
          <label>نوع پرداخت</label>
          <select name="payment" id="payment">
            <?php foreach (pay_labels() as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="full" id="cust-wrap" hidden><label>نام بدهکار (برای نسیه)</label><input type="text" name="customer" id="customer" placeholder="مثلاً آقای احمدی"></div>
        <div class="full"><label>توضیحات (اختیاری)</label><input type="text" name="note"></div>
      </div>
      <div class="sum-row"><span>جمع</span><span><span id="sub">۰</span></span></div>
      <div class="sum-row"><span>تخفیف</span><span><span id="disc-show">۰</span></span></div>
      <div class="sum-row total"><span>قابل پرداخت</span><span><span id="total">۰</span></span></div>
      <button class="btn" style="width:100%;margin-top:14px">ثبت فروش</button>
    </div>
  </div>
</form>
<script src="../assets/js/pos.js"></script>
<?php admin_footer();
