<?php
/* آیکون‌های SVG خطی (inline) */

function icon_paths(): array
{
    return [
        'coffee'  => '<path d="M4 11h13v4a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5v-4z"/><path d="M17 12h1.5a2.5 2.5 0 0 1 0 5H17"/><path d="M3 21h16"/><path d="M8 3c-1 1.2 1 2 0 3.5M12 3c-1 1.2 1 2 0 3.5"/>',
        'tea'     => '<path d="M5 11h12v3a5 5 0 0 1-5 5h-2a5 5 0 0 1-5-5v-3z"/><path d="M17 12h1.5a2.5 2.5 0 0 1 0 5H17"/><path d="M12 11V7"/><rect x="10.5" y="4" width="3" height="3" rx=".5"/><path d="M4 21h14"/>',
        'cup'     => '<path d="M3 10h14v3a6 6 0 0 1-6 6H9a6 6 0 0 1-6-6v-3z"/><path d="M17 11h1.5a2.5 2.5 0 0 1 0 5H16.5"/><path d="M2 21h18"/><path d="M9 6c0-1.5 2-1.5 2-3"/>',
        'dessert' => '<path d="M5.5 13h13L17 21H7z"/><path d="M6 13c0-3 2.5-5 6-5s6 2 6 5"/><circle cx="12" cy="4.5" r="1.5"/><path d="M9.5 13l.5 8M14.5 13l-.5 8M12 13v8"/>',
        'juice'   => '<path d="M6 7h12l-1.5 14h-9z"/><path d="M13 7l2-5h3"/><path d="M7 12h10"/>',
        'food'    => '<path d="M7 3v8M5 3v5a2 2 0 0 0 4 0V3M7 11v10"/><path d="M17 21V3c-2.5 1.5-3 5-3 8h3"/>',
        'phone'   => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.7" r=".8" fill="currentColor"/>',
        'clock'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'pin'     => '<path d="M12 21s7-6.2 7-11a7 7 0 0 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'wifi'    => '<path d="M2 9a15 15 0 0 1 20 0M5 12.5a10 10 0 0 1 14 0M8.5 16a5 5 0 0 1 7 0"/><circle cx="12" cy="19.5" r="1" fill="currentColor"/>',
        'check'   => '<path d="M5 12.5l4.5 4.5L19 7"/>',
        'share'   => '<circle cx="18" cy="5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="19" r="2.5"/><path d="M8.2 10.8l7.6-4.4M8.2 13.2l7.6 4.4"/>',
        'qr'      => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM20 14v1M14 20h3M20 18v3"/>',
        'home'    => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h5v-6h4v6h5V10"/>',
        'cart'    => '<path d="M3 4h2.5l2 11h10l2-8H7"/><circle cx="9" cy="19.5" r="1.3"/><circle cx="17" cy="19.5" r="1.3"/>',
        'chart'   => '<path d="M4 20V4"/><path d="M4 20h16"/><rect x="7.5" y="11" width="3" height="6" rx=".5"/><rect x="13" y="7" width="3" height="10" rx=".5"/>',
        'gear'    => '<circle cx="12" cy="12" r="3"/><path d="M12 2.5v3M12 18.5v3M2.5 12h3M18.5 12h3M5.3 5.3l2.1 2.1M16.6 16.6l2.1 2.1M5.3 18.7l2.1-2.1M16.6 7.4l2.1-2.1"/>',
        'wallet'  => '<path d="M4 7a2 2 0 0 1 2-2h11v3"/><rect x="4" y="7" width="16" height="12" rx="2"/><path d="M16 13h4"/><circle cx="16.3" cy="13" r=".6" fill="currentColor"/>',
        'bars'    => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'logout'  => '<path d="M9 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h3"/><path d="M16 8l4 4-4 4M20 12H9"/>',
        'external'=> '<path d="M14 4h6v6M20 4l-9 9"/><path d="M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4"/>',
        'fold'    => '<circle cx="12" cy="12" r="9"/><path d="M13 8l-4 4 4 4"/>',
        'user'    => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/>',
        'moon'    => '<path d="M20 14.5A8.5 8.5 0 1 1 9.5 4 6.5 6.5 0 0 0 20 14.5z"/>',
        'sun'     => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
    ];
}

/** آیکون‌های قابل انتخاب برای دسته‌بندی‌ها */
function icon_names(): array
{
    return [
        'coffee'  => 'قهوه',
        'tea'     => 'چای',
        'cup'     => 'نوشیدنی',
        'dessert' => 'دسر',
        'juice'   => 'آبمیوه',
        'food'    => 'غذا',
    ];
}

function icon(string $name, int $size = 24, string $class = ''): string
{
    $paths = icon_paths();
    $p = $paths[$name] ?? $paths['coffee'];
    return '<svg class="ico ' . htmlspecialchars($class, ENT_QUOTES) . '" width="' . $size . '" height="' . $size
        . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . $p . '</svg>';
}
