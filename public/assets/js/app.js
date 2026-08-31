// School ID System — front-end interactions
(function () {
  'use strict';

  // Auto-submit helper for selects that act as navigators
  document.querySelectorAll('[data-submit-on-change]').forEach(function (sel) {
    sel.addEventListener('change', function () { sel.form.submit(); });
  });

  // ---- Gate camera QR scanning ----
  var scanner = document.getElementById('scanner');
  var qrInput = document.getElementById('qrinput');

  if (scanner && qrInput && ('BarcodeDetector' in window)) {
    var detector = new BarcodeDetector({ formats: ['qr_code'] });
    var stream = null;

    function startCamera() {
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return;
      navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment' }
      }).then(function (s) {
        stream = s;
        scanner.srcObject = s;
        scanLoop();
      }).catch(function () { /* camera permission denied, manual entry still works */ });
    }

    function scanLoop() {
      if (!scanner.videoWidth) { setTimeout(scanLoop, 400); return; }
      detector.detect(scanner).then(function (codes) {
        if (codes.length) {
          qrInput.value = codes[0].rawValue;
          qrInput.form.submit();
          return;
        }
        setTimeout(scanLoop, 350);
      }).catch(function () { setTimeout(scanLoop, 500); });
    }

    startCamera();
    window.addEventListener('pagehide', function () {
      if (stream) { stream.getTracks().forEach(function (t) { t.stop(); }); }
    });
  } else if (scanner) {
    // Fallback hint when BarcodeDetector is unsupported
    scanner.style.display = 'none';
  }

  // ---- Confirmation for print (optional nicety) ----
  document.querySelectorAll('[data-print-confirm]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      if (!window.confirm('Mark this ID as printed?')) e.preventDefault();
    });
  });
})();
