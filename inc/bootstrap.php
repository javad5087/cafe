<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
date_default_timezone_set('Asia/Tehran');
define('BASE_DIR', dirname(__DIR__));

require_once __DIR__ . '/jalali.php';
require_once __DIR__ . '/icons.php';

/* ------------------------------------------------------------------ */
/* ابزارهای عمومی                                                       */
/* ------------------------------------------------------------------ */

function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash(string $msg, string $type = 'ok'): void
{
    $_SESSION['flash'][] = [$type, $msg];
}

function to_int($v): int
{
    $s = en_digits((string)$v);
    $s = preg_replace('~[^\d]~', '', $s) ?? '';
    return $s === '' ? 0 : (int)$s;
}

function money($n): string
{
    return fa_num($n);
}

function pay_labels(): array
{
    return ['pos' => 'کارتخوان', 'cash' => 'نقدی', 'transfer' => 'کارت به کارت', 'online' => 'آنلاین', 'credit' => 'نسیه'];
}

/** روش‌های پرداخت واقعی (بدون نسیه) — برای تسویه‌ی نسیه */
function pay_methods(): array
{
    $m = pay_labels();
    unset($m['credit']);
    return $m;
}

/** مبلغ با علامت منفی درست در متن راست‌به‌چپ (خروجی HTML امن) */
function money_html($n): string
{
    return '<span dir="ltr">' . ($n < 0 ? '−' : '') . money(abs((float)$n)) . '</span>';
}

function pay_label(string $k): string
{
    return pay_labels()[$k] ?? 'سایر';
}

function expense_categories(): array
{
    return [
        'water'       => 'آب',
        'electricity' => 'برق',
        'phone'       => 'تلفن',
        'gas'         => 'گاز',
        'tax'         => 'مالیات',
        'municipal'   => 'عوارض شهرداری',
        'rent'        => 'اجاره',
        'salary'      => 'حقوق پرسنل',
        'repair'      => 'تعمیرات و سرویس کافه',
        'other'       => 'سایر هزینه‌های متفرقه',
    ];
}

function expense_label(string $k): string
{
    return expense_categories()[$k] ?? 'سایر هزینه‌های متفرقه';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && !hash_equals(csrf_token(), (string)($_POST['_csrf'] ?? ''))) {
        http_response_code(400);
        exit('درخواست نامعتبر است. صفحه را دوباره بارگذاری کنید.');
    }
}

function require_login(): void
{
    if (empty($_SESSION['uid'])) {
        redirect('login.php');
    }
    csrf_check();
}

/* ------------------------------------------------------------------ */
/* دیتابیس (SQLite)                                                    */
/* ------------------------------------------------------------------ */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $dir = BASE_DIR . '/data';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    if (!file_exists($dir . '/.htaccess')) {
        file_put_contents($dir . '/.htaccess',
            "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
    }
    $pdo = new PDO('sqlite:' . $dir . '/cafe.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    init_db($pdo);
    return $pdo;
}

function init_db(PDO $db): void
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS users(
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS settings(
            k TEXT PRIMARY KEY,
            v TEXT NOT NULL DEFAULT ''
        );
        CREATE TABLE IF NOT EXISTS categories(
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            icon TEXT NOT NULL DEFAULT 'coffee',
            sort_order INTEGER NOT NULL DEFAULT 0,
            active INTEGER NOT NULL DEFAULT 1
        );
        CREATE TABLE IF NOT EXISTS products(
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
            name TEXT NOT NULL,
            description TEXT NOT NULL DEFAULT '',
            price INTEGER NOT NULL DEFAULT 0,
            sort_order INTEGER NOT NULL DEFAULT 0,
            active INTEGER NOT NULL DEFAULT 1
        );
        CREATE TABLE IF NOT EXISTS sales(
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            created_at INTEGER NOT NULL,
            subtotal INTEGER NOT NULL,
            discount INTEGER NOT NULL DEFAULT 0,
            total INTEGER NOT NULL,
            payment TEXT NOT NULL DEFAULT 'cash',
            note TEXT NOT NULL DEFAULT ''
        );
        CREATE TABLE IF NOT EXISTS sale_items(
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sale_id INTEGER NOT NULL REFERENCES sales(id) ON DELETE CASCADE,
            product_id INTEGER REFERENCES products(id) ON DELETE SET NULL,
            name TEXT NOT NULL,
            price INTEGER NOT NULL,
            qty INTEGER NOT NULL
        );
        CREATE TABLE IF NOT EXISTS expenses(
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            created_at INTEGER NOT NULL,
            category TEXT NOT NULL,
            amount INTEGER NOT NULL,
            note TEXT NOT NULL DEFAULT ''
        );
        CREATE INDEX IF NOT EXISTS idx_exp_created ON expenses(created_at);
        CREATE INDEX IF NOT EXISTS idx_sales_created ON sales(created_at);
        CREATE INDEX IF NOT EXISTS idx_items_sale ON sale_items(sale_id);
    ");

    // ستون‌های نسیه (برای دیتابیس‌های ساخته‌شده با نسخه‌های قبلی)
    $cols = array_column($db->query('PRAGMA table_info(sales)')->fetchAll(), 'name');
    if (!in_array('customer', $cols, true)) {
        $db->exec("ALTER TABLE sales ADD COLUMN customer TEXT NOT NULL DEFAULT ''");
    }
    if (!in_array('paid_at', $cols, true)) {
        $db->exec('ALTER TABLE sales ADD COLUMN paid_at INTEGER');
    }

    // انتقال روش‌های پرداخت قدیمی به عنوان‌های جدید
    $db->exec("UPDATE sales SET payment='pos' WHERE payment='card'");

    // تنظیمات پیش‌فرض (فقط اگر وجود نداشته باشند)
    $defaults = [
        'cafe_name'     => 'BORCELLE',
        'subtitle'      => 'منوی کافه',
        'hero_image'    => '',
        'working_hours' => "شنبه تا پنجشنبه: ۸ صبح تا ۱۱ شب\nجمعه‌ها: ۱۰ صبح تا ۱۲ شب",
        'address'       => 'تهران، خیابان نمونه، پلاک ۱۲',
        'phone'         => '021-12345678',
        'phone2'        => '',
        'instagram'     => 'borcelle.cafe',
        'site_url'      => '',
        'qr_enabled'    => '1',
        'qr_caption'    => 'برای دیدن منو اسکن کنید',
        'features'      => "وای‌فای رایگان\nفضای باز\nقهوه تازه‌دم\nمناسب کار و مطالعه",
    ];
    $ins = $db->prepare('INSERT OR IGNORE INTO settings(k,v) VALUES(?,?)');
    foreach ($defaults as $k => $v) {
        $ins->execute([$k, $v]);
    }

    // کاربر مدیر پیش‌فرض
    if ((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
        $db->prepare('INSERT INTO users(username,password_hash) VALUES(?,?)')
           ->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT)]);
    }

    // داده‌ی نمونه مطابق طرح منو
    if ((int)$db->query('SELECT COUNT(*) FROM categories')->fetchColumn() === 0) {
        $seed = [
            ['قهوه', 'coffee', ['اسپرسو', 'دبل اسپرسو', 'لاته', 'آمریکانو', 'ماکیاتو', 'فلت وایت', 'کاپوچینو']],
            ['چای', 'tea', ['چای لیمو', 'چای انبه', 'چای یاس', 'چای سبز', 'چای نعنا']],
            ['نوشیدنی بدون قهوه', 'cup', ['هات چاکلت', 'میلک‌شیک', 'اسموتی', 'لیموناد', 'میلک‌شیک وانیل']],
            ['دسر', 'dessert', ['وافل توت‌فرنگی', 'رولت دارچین', 'لیمو پای', 'کروسان', 'وافل شکلاتی', 'براونی', 'چیزکیک', 'مافین شکلاتی']],
        ];
        $c = $db->prepare('INSERT INTO categories(name,icon,sort_order) VALUES(?,?,?)');
        $p = $db->prepare('INSERT INTO products(category_id,name,price,sort_order) VALUES(?,?,?,?)');
        foreach ($seed as $i => [$name, $icon, $items]) {
            $c->execute([$name, $icon, $i + 1]);
            $cid = (int)$db->lastInsertId();
            foreach ($items as $j => $item) {
                $p->execute([$cid, $item, 85000, $j + 1]);
            }
        }
    }
}

/* ------------------------------------------------------------------ */
/* تنظیمات کافه                                                        */
/* ------------------------------------------------------------------ */

function settings(): array
{
    static $s = null;
    if ($s === null) {
        $s = [];
        foreach (db()->query('SELECT k,v FROM settings') as $r) {
            $s[$r['k']] = $r['v'];
        }
    }
    return $s;
}

function setting(string $k, string $d = ''): string
{
    return settings()[$k] ?? $d;
}

function save_setting(string $k, string $v): void
{
    db()->prepare('INSERT OR REPLACE INTO settings(k,v) VALUES(?,?)')->execute([$k, $v]);
}

function instagram_url(string $v): string
{
    $v = trim($v);
    if ($v === '') {
        return '';
    }
    if (preg_match('~^https?://~i', $v)) {
        return $v;
    }
    return 'https://instagram.com/' . rawurlencode(ltrim($v, '@/'));
}

/* ------------------------------------------------------------------ */
/* آپلود تصویر                                                         */
/* ------------------------------------------------------------------ */

function save_image(array $f): ?string
{
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 5 * 1024 * 1024) {
        flash('بارگذاری تصویر ناموفق بود یا حجم آن بیش از ۵ مگابایت است.', 'err');
        return null;
    }
    $info = @getimagesize($f['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    $ext = $types[$info[2] ?? 0] ?? null;
    if (!$ext) {
        flash('فقط تصاویر JPG، PNG یا WebP مجاز است.', 'err');
        return null;
    }
    $dir = BASE_DIR . '/uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = 'hero_' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
        flash('ذخیره‌ی تصویر ناموفق بود.', 'err');
        return null;
    }
    return 'uploads/' . $name;
}

/* ------------------------------------------------------------------ */
/* گزارش‌ها                                                            */
/* ------------------------------------------------------------------ */

/** تعداد روزهای ماه شمسی (اسفند حداکثر ۳۰ در نظر گرفته می‌شود) */
function jalali_days_in_month(int $jm): int
{
    return $jm <= 6 ? 31 : ($jm <= 11 ? 30 : 30);
}

/** timestamp از اجزای شمسی؛ روز اضافه به آخر ماه محدود می‌شود */
function jalali_parts_ts(int $jy, int $jm, int $jd, bool $end = false): ?int
{
    if ($jy < 1300 || $jy > 1700 || $jm < 1 || $jm > 12 || $jd < 1) {
        return null;
    }
    $jd = min($jd, jalali_days_in_month($jm));
    [$gy, $gm, $gd] = j2g($jy, $jm, $jd);
    return $end ? mktime(23, 59, 59, $gm, $gd, $gy) : mktime(0, 0, 0, $gm, $gd, $gy);
}

function jalali_year_now(): int
{
    return g2j((int)date('Y'), (int)date('n'), (int)date('j'))[0];
}

/** سه لیست کشویی (سال / ماه / روز) شمسی؛ مقدار پیش‌فرض از timestamp */
function jalali_select(string $prefix, int $ts): string
{
    [$sy, $sm, $sd] = g2j((int)date('Y', $ts), (int)date('n', $ts), (int)date('j', $ts));
    $nowY = jalali_year_now();
    $y0 = min($nowY - 5, $sy);
    $y1 = max($nowY + 1, $sy);
    $months = jalali_months();

    $h = '<span class="jdate">';
    $h .= '<select name="' . e($prefix) . '_d" aria-label="روز">';
    for ($d = 1; $d <= 31; $d++) {
        $h .= '<option value="' . $d . '"' . ($d === $sd ? ' selected' : '') . '>' . fa_digits($d) . '</option>';
    }
    $h .= '</select><select name="' . e($prefix) . '_m" aria-label="ماه">';
    for ($m = 1; $m <= 12; $m++) {
        $h .= '<option value="' . $m . '"' . ($m === $sm ? ' selected' : '') . '>' . e($months[$m]) . '</option>';
    }
    $h .= '</select><select name="' . e($prefix) . '_y" aria-label="سال">';
    for ($y = $y1; $y >= $y0; $y--) {
        $h .= '<option value="' . $y . '"' . ($y === $sy ? ' selected' : '') . '>' . fa_digits($y) . '</option>';
    }
    $h .= '</select></span>';
    return $h;
}

/** خواندن تاریخ از لیست‌های کشویی ارسال‌شده (prefix_y / prefix_m / prefix_d) */
function jalali_from_request(array $src, string $prefix, bool $end = false): ?int
{
    if (!isset($src[$prefix . '_y'], $src[$prefix . '_m'], $src[$prefix . '_d'])) {
        return null;
    }
    return jalali_parts_ts((int)$src[$prefix . '_y'], (int)$src[$prefix . '_m'], (int)$src[$prefix . '_d'], $end);
}

/** بازه‌ی زمانی از پارامترهای GET: range=today|week|month|all یا from_* / to_* (لیست‌های کشویی شمسی) */
function report_range(): array
{
    $today = mktime(0, 0, 0);
    $range = (string)($_GET['range'] ?? '');
    $from = $to = null;

    if ($range === 'today') {
        $from = $today;
        $to = $today + 86399;
    } elseif ($range === 'week') {
        $off = ((int)date('w', $today) + 1) % 7; // شروع هفته: شنبه
        $from = strtotime("-{$off} days", $today);
        $to = $today + 86399;
    } elseif ($range === 'all') {
        $from = 0;
        $to = PHP_INT_MAX;
    } elseif (isset($_GET['from_y'])) {
        $from = jalali_from_request($_GET, 'from');
        $to = jalali_from_request($_GET, 'to', true);
        $range = 'custom';
    }
    if ($from === null || $to === null) {
        $range = 'month';
        $from = jalali_month_start();
        $to = $today + 86399;
    }
    if ($from > $to) {
        [$from, $to] = [mktime(0, 0, 0, (int)date('n', $to), (int)date('j', $to), (int)date('Y', $to)),
                        mktime(23, 59, 59, (int)date('n', $from), (int)date('j', $from), (int)date('Y', $from))];
    }
    return [
        'from' => $from,
        'to' => $to,
        // برای نمایش در لیست‌های کشویی (در حالت «همه» از ابتدای ماه جاری تا امروز)
        'from_show' => $from > 0 ? $from : jalali_month_start(),
        'to_show' => $to < PHP_INT_MAX ? $to : $today,
        'range' => $range,
    ];
}

function sales_summary(int $from, int $to, ?string $pay = null): array
{
    $sql = 'SELECT COUNT(*) c, COALESCE(SUM(total),0) t, COALESCE(SUM(discount),0) d
            FROM sales WHERE created_at BETWEEN ? AND ?';
    $args = [$from, $to];
    if ($pay !== null) {
        $sql .= ' AND payment = ?';
        $args[] = $pay;
    }
    $st = db()->prepare($sql);
    $st->execute($args);
    $r = $st->fetch();
    return ['count' => (int)$r['c'], 'total' => (int)$r['t'], 'discount' => (int)$r['d']];
}

/** مجموع نسیه‌های تسویه‌نشده (کل زمان‌ها) */
function credit_outstanding(): array
{
    $r = db()->query("SELECT COUNT(*) c, COALESCE(SUM(total),0) t FROM sales WHERE payment='credit'")->fetch();
    return ['count' => (int)$r['c'], 'total' => (int)$r['t']];
}

/** آمار کامل فروش یک بازه: جمع‌ها، تفکیک پرداخت، فروش روزانه و پرفروش‌ها */
function report_data(int $from, int $to, int $topLimit = 10): array
{
    $db = db();
    $st = $db->prepare('SELECT created_at, subtotal, discount, total, payment FROM sales WHERE created_at BETWEEN ? AND ?');
    $st->execute([$from, $to]);

    $sum = ['count' => 0, 'subtotal' => 0, 'discount' => 0, 'total' => 0, 'avg' => 0];
    $byPay = [];
    $byDay = [];
    foreach ($st as $s) {
        $sum['count']++;
        $sum['subtotal'] += (int)$s['subtotal'];
        $sum['discount'] += (int)$s['discount'];
        $sum['total'] += (int)$s['total'];
        $byPay[$s['payment']] = ($byPay[$s['payment']] ?? 0) + (int)$s['total'];
        $day = date('Y-m-d', (int)$s['created_at']);
        $byDay[$day]['ts'] = (int)$s['created_at'];
        $byDay[$day]['count'] = ($byDay[$day]['count'] ?? 0) + 1;
        $byDay[$day]['total'] = ($byDay[$day]['total'] ?? 0) + (int)$s['total'];
    }
    ksort($byDay);
    $sum['avg'] = $sum['count'] ? intdiv($sum['total'], $sum['count']) : 0;

    $tp = $db->prepare('SELECT si.name, SUM(si.qty) q, SUM(si.qty*si.price) amount
                        FROM sale_items si JOIN sales s ON s.id=si.sale_id
                        WHERE s.created_at BETWEEN ? AND ? GROUP BY si.name ORDER BY q DESC, amount DESC LIMIT ' . (int)$topLimit);
    $tp->execute([$from, $to]);

    $ex = $db->prepare('SELECT category, SUM(amount) t FROM expenses WHERE created_at BETWEEN ? AND ? GROUP BY category ORDER BY t DESC');
    $ex->execute([$from, $to]);
    $expByCat = [];
    $expTotal = 0;
    foreach ($ex as $row) {
        $expByCat[$row['category']] = (int)$row['t'];
        $expTotal += (int)$row['t'];
    }

    return [
        'sum' => $sum,
        'byPay' => $byPay,
        'byDay' => $byDay,
        'top' => $tp->fetchAll(),
        'credit' => $byPay['credit'] ?? 0,
        'exp' => ['total' => $expTotal, 'byCat' => $expByCat],
        'balance' => $sum['total'] - $expTotal,
    ];
}

/** بازه‌ی گزارش دوره‌ای (روزانه/هفتگی/ماهانه/سالانه) شامل تاریخ مرجع */
function period_bounds(string $type, int $refTs): array
{
    $day = mktime(0, 0, 0, (int)date('n', $refTs), (int)date('j', $refTs), (int)date('Y', $refTs));
    [$jy, $jm] = g2j((int)date('Y', $day), (int)date('n', $day), (int)date('j', $day));

    switch ($type) {
        case 'weekly':
            $off = ((int)date('w', $day) + 1) % 7;
            $from = strtotime("-{$off} days", $day);
            $to = strtotime('+6 days', $from) + 86399;
            return [$from, $to, 'گزارش فروش هفتگی'];
        case 'monthly':
            $from = jalali_parts_ts($jy, $jm, 1);
            $to = ($jm === 12 ? jalali_parts_ts($jy + 1, 1, 1) : jalali_parts_ts($jy, $jm + 1, 1)) - 1;
            return [$from, $to, 'گزارش فروش ماهانه'];
        case 'yearly':
            $from = jalali_parts_ts($jy, 1, 1);
            $to = jalali_parts_ts($jy + 1, 1, 1) - 1;
            return [$from, $to, 'گزارش فروش سالانه'];
        default:
            return [$day, $day + 86399, 'گزارش فروش روزانه'];
    }
}

/* ------------------------------------------------------------------ */
/* آدرس سایت (برای QR و اشتراک‌گذاری)                                   */
/* ------------------------------------------------------------------ */

function site_url(): string
{
    $saved = trim(setting('site_url'));
    if ($saved !== '') {
        return $saved;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    $host = preg_replace('~[^A-Za-z0-9.\-:\[\]]~', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    if (substr($dir, -6) === '/admin') {
        $dir = substr($dir, 0, -6);
    }
    return ($https ? 'https' : 'http') . '://' . $host . $dir . '/';
}
