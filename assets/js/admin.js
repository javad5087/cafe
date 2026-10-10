/* رفتارهای عمومی پنل: منوی کناری، مودال، تسویه‌ی نسیه */
(function () {
  var root = document.documentElement;

  /* ---------- منوی کناری ---------- */
  var foldBtn = document.getElementById('fold-btn');
  if (foldBtn) {
    foldBtn.addEventListener('click', function () {
      var on = root.classList.toggle('folded');
      try { localStorage.setItem('admin-folded', on ? '1' : '0'); } catch (e) {}
    });
  }
  var toggle = document.getElementById('menu-toggle');
  if (toggle) {
    toggle.addEventListener('click', function (ev) {
      ev.stopPropagation();
      document.body.classList.toggle('menu-open');
    });
    document.addEventListener('click', function (ev) {
      if (document.body.classList.contains('menu-open') && !ev.target.closest('#side')) {
        document.body.classList.remove('menu-open');
      }
    });
  }

  /* ---------- مودال ---------- */
  var modal = document.getElementById('modal');
  var body = document.getElementById('modal-body');
  if (!modal) return;
  var closing = false;

  function open(html) {
    closing = false;
    body.innerHTML = html;
    modal.hidden = false;
    document.body.classList.add('has-modal');
  }

  function hide() {
    modal.hidden = true;
    body.innerHTML = '';
    document.body.classList.remove('has-modal');
  }

  /* با بستن مودال فاکتورِ نسیه، اگر روش پرداخت انتخاب شده باشد خودکار ثبت می‌شود */
  function close() {
    if (closing) return;
    var box = body.querySelector('[data-settle]');
    var sel = box && box.querySelector('select');
    if (sel && sel.value && sel.value !== 'credit') {
      closing = true;
      var fd = new FormData();
      fd.append('_csrf', box.getAttribute('data-csrf'));
      fd.append('id', box.getAttribute('data-id'));
      fd.append('payment', sel.value);
      hide();
      fetch('sale_settle.php', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function () { location.reload(); })
        .catch(function () { location.reload(); });
      return;
    }
    hide();
  }

  document.addEventListener('click', function (ev) {
    var inv = ev.target.closest('[data-invoice]');
    if (inv) {
      ev.preventDefault();
      open('<p style="text-align:center;padding:30px;color:#7b6050">در حال بارگذاری...</p>');
      fetch('sale_view.php?modal=1&id=' + encodeURIComponent(inv.getAttribute('data-invoice')), { credentials: 'same-origin' })
        .then(function (r) { if (!r.ok) throw new Error(); return r.text(); })
        .then(function (html) { body.innerHTML = html; })
        .catch(function () { body.innerHTML = '<p style="text-align:center;padding:30px">بارگذاری فاکتور ناموفق بود.</p>'; });
      return;
    }
    var tpl = ev.target.closest('[data-modal]');
    if (tpl) {
      ev.preventDefault();
      var t = document.getElementById(tpl.getAttribute('data-modal'));
      if (t) open(t.innerHTML);
      return;
    }
    if (ev.target.closest('[data-close]') || ev.target === modal) { close(); return; }
    if (ev.target.closest('[data-print]')) window.print();
  });

  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape' && !modal.hidden) close();
  });

  /* باز کردن خودکار مودال چاپ گزارش از لینک زیرمنو */
  if (/[?&]print=1(&|$)/.test(location.search)) {
    var b = document.querySelector('[data-modal]');
    if (b) b.click();
  }
})();
