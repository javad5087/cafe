(function () {
  var FA = '۰۱۲۳۴۵۶۷۸۹';
  function fa(n) {
    return String(Math.round(n))
      .replace(/\B(?=(\d{3})+(?!\d))/g, '٬')
      .replace(/\d/g, function (d) { return FA[d]; });
  }
  function toInt(s) {
    s = String(s).replace(/[۰-۹]/g, function (d) { return FA.indexOf(d); });
    s = s.replace(/[٠-٩]/g, function (d) { return d.charCodeAt(0) - 1632; });
    return parseInt(s.replace(/\D/g, ''), 10) || 0;
  }

  var rows = Array.prototype.slice.call(document.querySelectorAll('.pos-item'));
  var list = document.getElementById('cart-list');
  var discInput = document.getElementById('discount');
  var subEl = document.getElementById('sub');
  var discEl = document.getElementById('disc-show');
  var totalEl = document.getElementById('total');

  function calc() {
    var sub = 0;
    rows.forEach(function (r) {
      var inp = r.querySelector('input[type=hidden]');
      var q = parseInt(inp.value, 10) || 0;
      r.querySelector('.q').textContent = fa(q);
      r.classList.toggle('sel', q > 0);
      if (q > 0) {
        var line = q * parseInt(r.dataset.price, 10);
        sub += line;
      }
    });
    list.innerHTML = '';
    var any = false;
    rows.forEach(function (r) {
      var q = parseInt(r.querySelector('input[type=hidden]').value, 10) || 0;
      if (q <= 0) return;
      any = true;
      var li = document.createElement('li');
      var s = document.createElement('span');
      s.textContent = r.dataset.name + ' × ' + fa(q);
      var b = document.createElement('b');
      b.textContent = fa(q * parseInt(r.dataset.price, 10));
      li.appendChild(s); li.appendChild(b); list.appendChild(li);
    });
    if (!any) {
      var e = document.createElement('li');
      e.style.color = 'var(--muted)';
      e.textContent = 'هنوز محصولی انتخاب نشده است.';
      list.appendChild(e);
    }
    var d = Math.min(toInt(discInput.value), sub);
    subEl.textContent = fa(sub);
    discEl.textContent = fa(d);
    totalEl.textContent = fa(sub - d);
  }

  rows.forEach(function (r) {
    var inp = r.querySelector('input[type=hidden]');
    r.querySelector('.plus').addEventListener('click', function () {
      inp.value = Math.min(999, (parseInt(inp.value, 10) || 0) + 1); calc();
    });
    r.querySelector('.minus').addEventListener('click', function () {
      inp.value = Math.max(0, (parseInt(inp.value, 10) || 0) - 1); calc();
    });
  });
  discInput.addEventListener('input', calc);

  var search = document.getElementById('pos-search');
  if (search) {
    search.addEventListener('input', function () {
      var q = search.value.trim();
      rows.forEach(function (r) {
        r.style.display = (!q || r.dataset.name.indexOf(q) !== -1) ? '' : 'none';
      });
    });
  }

  document.getElementById('pos-form').addEventListener('submit', function (ev) {
    var any = rows.some(function (r) { return (parseInt(r.querySelector('input[type=hidden]').value, 10) || 0) > 0; });
    if (!any) { ev.preventDefault(); alert('حداقل یک محصول انتخاب کنید.'); }
  });

  var pay = document.getElementById('payment');
  var custWrap = document.getElementById('cust-wrap');
  if (pay && custWrap) {
    var syncPay = function () { custWrap.hidden = pay.value !== 'credit'; };
    pay.addEventListener('change', syncPay);
    syncPay();
  }

  calc();
})();
