(function () {
  var root = document.documentElement;
  var toast = document.getElementById('toast');
  var toastTimer;

  function showToast(msg) {
    toast.textContent = msg;
    toast.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toast.hidden = true; }, 2200);
  }

  /* ---------- حالت شب / روز ---------- */
  document.getElementById('theme-btn').addEventListener('click', function () {
    var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    try { localStorage.setItem('menu-theme', next); } catch (e) {}
  });

  /* ---------- اشتراک‌گذاری ---------- */
  function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }
    return new Promise(function (resolve, reject) {
      var ta = document.createElement('textarea');
      ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
      document.body.appendChild(ta); ta.select();
      try { document.execCommand('copy') ? resolve() : reject(); } catch (e) { reject(e); }
      ta.remove();
    });
  }

  var shareBtn = document.getElementById('share-btn');
  shareBtn.addEventListener('click', function () {
    var url = shareBtn.getAttribute('data-url') || location.href;
    var title = shareBtn.getAttribute('data-title') || document.title;
    if (navigator.share) {
      navigator.share({ title: title, url: url }).catch(function (err) {
        if (err && err.name === 'AbortError') return;
        copyText(url).then(function () { showToast('لینک منو کپی شد'); });
      });
      return;
    }
    copyText(url).then(
      function () { showToast('لینک منو کپی شد'); },
      function () { showToast(url); }
    );
  });

  /* ---------- کد QR ---------- */
  var qrBtn = document.getElementById('qr-btn');
  var modal = document.getElementById('qr-modal');
  if (qrBtn && modal) {
    var canvas = document.getElementById('qr-canvas');
    var drawn = false;
    function closeModal() { modal.hidden = true; }
    qrBtn.addEventListener('click', function () {
      if (!drawn) {
        try { QRRender.draw(canvas, shareBtn.getAttribute('data-url') || location.href, 780); drawn = true; }
        catch (e) { showToast('ساخت QR ناموفق بود'); return; }
      }
      modal.hidden = false;
    });
    modal.addEventListener('click', function (ev) {
      if (ev.target === modal || ev.target.closest('[data-close]')) closeModal();
    });
    document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') closeModal(); });
    document.getElementById('qr-dl').addEventListener('click', function () { QRRender.download(canvas, 'menu-qr.png'); });
  }
})();
