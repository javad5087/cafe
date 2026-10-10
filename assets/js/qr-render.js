/* رسم QR روی canvas (نیازمند qrcode.js) */
(function (global) {
  function build(text) {
    var L = global.QRLib;
    var qr = new L.QRCode(-1, L.ErrorCorrectLevel.M);
    qr.addData(encodeURI(text));
    qr.make();
    return qr;
  }

  /** رسم QR در canvas؛ size = اندازه‌ی نهایی بر حسب پیکسل */
  function draw(canvas, text, size) {
    size = size || 520;
    var qr = build(text), n = qr.getModuleCount(), quiet = 4;
    var cell = Math.max(1, Math.floor(size / (n + quiet * 2)));
    var px = cell * (n + quiet * 2);
    canvas.width = px;
    canvas.height = px;
    var ctx = canvas.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, px, px);
    ctx.fillStyle = '#000000';
    for (var r = 0; r < n; r++) {
      for (var c = 0; c < n; c++) {
        if (qr.isDark(r, c)) ctx.fillRect((c + quiet) * cell, (r + quiet) * cell, cell, cell);
      }
    }
    return canvas;
  }

  function download(canvas, name) {
    var a = document.createElement('a');
    a.href = canvas.toDataURL('image/png');
    a.download = name || 'menu-qr.png';
    document.body.appendChild(a);
    a.click();
    a.remove();
  }

  global.QRRender = { draw: draw, download: download };
})(window);
