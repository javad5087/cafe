<?php
/* تبدیل تاریخ میلادی ↔ شمسی و ابزارهای ارقام فارسی */

function fa_digits($s): string
{
    return strtr((string)$s, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']);
}

function en_digits($s): string
{
    return strtr((string)$s, [
        '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
        '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
    ]);
}

/** عدد با جداکننده هزارگان و ارقام فارسی */
function fa_num($n): string
{
    return fa_digits(str_replace(',', '٬', number_format((float)$n, 0, '.', ',')));
}

function g2j(int $gy, int $gm, int $gd): array
{
    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) + $gd + $g_d_m[$gm - 1];
    $jy = -1595 + (33 * intdiv($days, 12053));
    $days %= 12053;
    $jy += 4 * intdiv($days, 1461);
    $days %= 1461;
    if ($days > 365) {
        $jy += intdiv($days - 1, 365);
        $days = ($days - 1) % 365;
    }
    if ($days < 186) {
        $jm = 1 + intdiv($days, 31);
        $jd = 1 + ($days % 31);
    } else {
        $jm = 7 + intdiv($days - 186, 30);
        $jd = 1 + (($days - 186) % 30);
    }
    return [$jy, $jm, $jd];
}

function j2g(int $jy, int $jm, int $jd): array
{
    $jy += 1595;
    $days = -355668 + (365 * $jy) + (intdiv($jy, 33) * 8) + intdiv(($jy % 33) + 3, 4) + $jd
        + (($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);
    $gy = 400 * intdiv($days, 146097);
    $days %= 146097;
    if ($days > 36524) {
        $days--;
        $gy += 100 * intdiv($days, 36524);
        $days %= 36524;
        if ($days >= 365) {
            $days++;
        }
    }
    $gy += 4 * intdiv($days, 1461);
    $days %= 1461;
    if ($days > 365) {
        $gy += intdiv($days - 1, 365);
        $days = ($days - 1) % 365;
    }
    $gd = $days + 1;
    $leap = (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0)) ? 29 : 28;
    $sal_a = [0, 31, $leap, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    for ($gm = 0; $gm < 13 && $gd > $sal_a[$gm]; $gm++) {
        $gd -= $sal_a[$gm];
    }
    return [$gy, $gm, $gd];
}

function jalali_months(): array
{
    return ['', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
}

function jalali_weekdays(): array
{
    // شاخص = date('w')  (0 = یکشنبه)
    return ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];
}

/**
 * قالب‌بندی تاریخ شمسی با ارقام فارسی
 * Y سال | y سال دو رقمی | m ماه دو رقمی | n ماه | d روز دو رقمی | j روز | F نام ماه | l نام روز هفته | H ساعت | i دقیقه
 */
function jdate(int $ts, string $fmt = 'Y/m/d'): string
{
    [$jy, $jm, $jd] = g2j((int)date('Y', $ts), (int)date('n', $ts), (int)date('j', $ts));
    $months = jalali_months();
    $days = jalali_weekdays();
    $out = '';
    $len = strlen($fmt);
    for ($i = 0; $i < $len; $i++) {
        $c = $fmt[$i];
        switch ($c) {
            case 'Y': $out .= $jy; break;
            case 'y': $out .= substr((string)$jy, -2); break;
            case 'm': $out .= sprintf('%02d', $jm); break;
            case 'n': $out .= $jm; break;
            case 'd': $out .= sprintf('%02d', $jd); break;
            case 'j': $out .= $jd; break;
            case 'F': $out .= $months[$jm]; break;
            case 'l': $out .= $days[(int)date('w', $ts)]; break;
            case 'H': $out .= date('H', $ts); break;
            case 'i': $out .= date('i', $ts); break;
            default:  $out .= $c;
        }
    }
    return fa_digits($out);
}

/** تبدیل رشته‌ی شمسی (۱۴۰۵/۰۷/۱۸) به timestamp؛ در صورت نامعتبر بودن null */
function jalali_ts(string $s, bool $endOfDay = false): ?int
{
    $s = en_digits(trim($s));
    if (!preg_match('~^(\d{4})[/\-.](\d{1,2})[/\-.](\d{1,2})$~', $s, $m)) {
        return null;
    }
    [$jy, $jm, $jd] = [(int)$m[1], (int)$m[2], (int)$m[3]];
    if ($jm < 1 || $jm > 12 || $jd < 1 || $jd > 31) {
        return null;
    }
    [$gy, $gm, $gd] = j2g($jy, $jm, $jd);
    return $endOfDay ? mktime(23, 59, 59, $gm, $gd, $gy) : mktime(0, 0, 0, $gm, $gd, $gy);
}

/** timestamp شروع ماه شمسی جاری */
function jalali_month_start(): int
{
    [$jy, $jm] = g2j((int)date('Y'), (int)date('n'), (int)date('j'));
    [$gy, $gm, $gd] = j2g($jy, $jm, 1);
    return mktime(0, 0, 0, $gm, $gd, $gy);
}
