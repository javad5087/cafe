<?php
require __DIR__ . '/inc/bootstrap.php';

$db = db();
$cats = $db->query('SELECT * FROM categories WHERE active=1 ORDER BY sort_order, id')->fetchAll();
$stmt = $db->prepare('SELECT * FROM products WHERE category_id=? AND active=1 ORDER BY sort_order, id');
$menu = [];
foreach ($cats as $c) {
    $stmt->execute([$c['id']]);
    $items = $stmt->fetchAll();
    if ($items) {
        $menu[] = ['cat' => $c, 'items' => $items];
    }
}

$cafe     = setting('cafe_name');
$subtitle = setting('subtitle');
$hero     = setting('hero_image');
$hours    = array_values(array_filter(array_map('trim', explode("\n", setting('working_hours')))));
$address  = trim(setting('address'));
$phones   = array_values(array_filter([trim(setting('phone')), trim(setting('phone2'))]));
$ig       = trim(setting('instagram'));
$features = array_values(array_filter(array_map('trim', explode("\n", setting('features')))));
$qrOn     = setting('qr_enabled', '1') === '1';
$siteUrl  = site_url();
?><!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($subtitle) ?> | <?= e($cafe) ?></title>
<script>try{var t=localStorage.getItem('menu-theme');if(t==='dark'||t==='light')document.documentElement.setAttribute('data-theme',t);}catch(e){}</script>
<link rel="preconnect" href="https://cdn.jsdelivr.net">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vazirmatn@33.0.3/Vazirmatn-font-face.css">
<link rel="stylesheet" href="assets/css/site.css">
</head>
<body>
<div class="sheet">

  <div class="toolbar">
    <button type="button" class="tb-btn" id="share-btn" title="<?= e('اشتراک‌گذاری') ?>" aria-label="<?= e('اشتراک‌گذاری') ?>" data-url="<?= e($siteUrl) ?>" data-title="<?= e($cafe . ' - ' . $subtitle) ?>">
      <?= icon('share', 20) ?>
    </button>
    <?php if ($qrOn): ?>
      <button type="button" class="tb-btn" id="qr-btn" title="<?= e('کد QR منو') ?>" aria-label="<?= e('کد QR منو') ?>">
        <?= icon('qr', 20) ?>
      </button>
    <?php endif; ?>
    <button type="button" class="tb-btn" id="theme-btn" title="<?= e('حالت شب / روز') ?>" aria-label="<?= e('حالت شب و روز') ?>">
      <span class="i-moon"><?= icon('moon', 20) ?></span><span class="i-sun"><?= icon('sun', 20) ?></span>
    </button>
  </div>

  <header class="top">
    <div class="title">
      <div class="brand"><span class="brand-ico"><?= icon('coffee', 26) ?></span><span class="brand-name"><?= e($cafe) ?></span></div>
      <p class="script"><?= e($subtitle) ?></p>
      <h1><?= e('منو') ?></h1>
      <p class="today"><?= e(jdate(time(), 'l j F Y')) ?></p>
    </div>
    <div class="arch">
      <?php if ($hero): ?>
        <img src="<?= e($hero) ?>" alt="<?= e($cafe) ?>">
      <?php else: ?>
        <div class="arch-ph"><?= icon('coffee', 72) ?></div>
      <?php endif; ?>
    </div>
  </header>


  <main class="menu">
    <?php if (!$menu): ?>
      <p class="empty"><?= e('هنوز محصولی در منو ثبت نشده است.') ?></p>
    <?php endif; ?>
    <?php foreach ($menu as $m): $wide = count($m['items']) > 6; ?>
      <section class="cat <?= $wide ? 'wide' : '' ?>">
        <h2><?= icon($m['cat']['icon'], 30) ?><span><?= e($m['cat']['name']) ?></span></h2>
        <ul>
          <?php foreach ($m['items'] as $p): ?>
            <li>
              <div class="row">
                <span class="name"><?= e($p['name']) ?></span>
                <span class="dots"></span>
                <span class="price"><?= money($p['price']) ?></span>
              </div>
              <?php $description = $p['description']; ?>
              <?php if ($description !== ''): ?><div class="desc"><?= e($description) ?></div><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endforeach; ?>
  </main>

  <footer class="contact">
    <?php if ($hours): ?>
      <div class="ci">
        <?= icon('clock', 24) ?>
        <div class="ci-t"><strong><?= e('ساعات کاری') ?></strong>
          <?php foreach ($hours as $h): ?><span><?= e(fa_digits($h)) ?></span><?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
    <?php if ($address): ?>
      <a class="ci" href="https://www.google.com/maps/search/?api=1&query=<?= rawurlencode($address) ?>" target="_blank" rel="noopener">
        <?= icon('pin', 24) ?>
        <div class="ci-t"><strong><?= e('نشانی') ?></strong><span><?= e(fa_digits($address)) ?></span></div>
      </a>
    <?php endif; ?>
    <?php if ($phones): ?>
      <div class="ci">
        <?= icon('phone', 24) ?>
        <div class="ci-t"><strong><?= e('تماس') ?></strong>
          <?php foreach ($phones as $ph): ?>
            <a class="ltr" href="tel:<?= e(preg_replace('~[^\d+]~', '', en_digits($ph))) ?>"><?= e(fa_digits($ph)) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
    <?php if ($ig): ?>
      <a class="ci" href="<?= e(instagram_url($ig)) ?>" target="_blank" rel="noopener">
        <?= icon('instagram', 24) ?>
        <div class="ci-t"><strong><?= e('اینستاگرام') ?></strong><span class="ltr">@<?= e(ltrim(preg_replace('~^https?://(www\.)?instagram\.com/~i', '', $ig), '@/')) ?></span></div>
      </a>
    <?php endif; ?>
  </footer>

  <?php if ($features): ?>
    <ul class="features">
      <?php foreach ($features as $f): ?>
        <li><?= icon('check', 16) ?><?= e($f) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

</div>

<?php if ($qrOn): ?>
<div class="modal" id="qr-modal" hidden>
  <div class="modal-box" role="dialog" aria-modal="true" aria-label="کد QR منو">
    <button type="button" class="modal-x" data-close aria-label="<?= e('بستن') ?>">×</button>
    <strong class="m-title"><?= e($cafe) ?></strong>
    <canvas id="qr-canvas" width="260" height="260"></canvas>
    <?php if (trim(setting('qr_caption')) !== ''): ?><p class="m-cap"><?= e(setting('qr_caption')) ?></p><?php endif; ?>
    <button type="button" class="tb-btn" id="qr-dl"><?= e('دانلود تصویر') ?></button>
  </div>
</div>
<?php endif; ?>

<div class="toast" id="toast" role="status" hidden></div>

<?php if ($qrOn): ?>
<script src="assets/js/qrcode.js"></script>
<script src="assets/js/qr-render.js"></script>
<?php endif; ?>
<script src="assets/js/site.js"></script>
</body>
</html>
