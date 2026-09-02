// School ID System — front-end interactions
(function () {
  'use strict';

  // Auto-submit helper for selects that act as navigators
  document.querySelectorAll('[data-submit-on-change]').forEach(function (sel) {
    sel.addEventListener('change', function () { sel.form.submit(); });
  });

  // ---- Gate camera QR + barcode scanning ----
  var scanner = document.getElementById('scanner');
  var qrInput = document.getElementById('qrinput');
  var scanWrap = document.getElementById('scannerWrap');
  var scanStatus = document.getElementById('scannerStatus');

  if (scanner && qrInput && scanWrap) {
    var stream = null;
    var scanning = false;
    var lastScanTime = 0;
    var SCAN_COOLDOWN = 2500;

    // Create audio beep for scan feedback
    var audioCtx = null;
    function beep(freq, duration) {
      try {
        if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        var osc = audioCtx.createOscillator();
        var gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.frequency.value = freq || 880;
        gain.gain.value = 0.3;
        osc.start(audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + (duration || 0.15));
        osc.stop(audioCtx.currentTime + (duration || 0.15));
      } catch (e) { /* audio not available */ }
    }

    function setStatus(text) {
      if (scanStatus) scanStatus.textContent = text;
    }

    function flashResult(success) {
      if (!scanWrap) return;
      scanWrap.classList.add(success ? 'scan-ok' : 'scan-fail');
      setTimeout(function () {
        scanWrap.classList.remove('scan-ok', 'scan-fail');
      }, 1800);
    }

    // Supported formats — both QR codes and linear barcodes
    var BARCODE_FORMATS = ['qr_code', 'code_128', 'code_39', 'code_93', 'codabar', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'itf'];
    var hasBarcodeDetector = 'BarcodeDetector' in window;
    var detector = null;
    var zxingReader = null;

    if (hasBarcodeDetector) {
      try {
        detector = new BarcodeDetector({ formats: BARCODE_FORMATS });
      } catch (e) {
        detector = null;
        hasBarcodeDetector = false;
      }
    }
    // ZXing fallback (handles QR + barcodes) — loaded from zxing.js
    if (!hasBarcodeDetector && typeof ZXing !== 'undefined') {
      try {
        zxingReader = new ZXing.MultiFormatReader();
        var zxingHints = new Map();
        zxingHints.set(ZXing.DecodeHintType.TRY_HARDER, true);
        zxingHints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [
          ZXing.BarcodeFormat.QR_CODE,
          ZXing.BarcodeFormat.CODE_128,
          ZXing.BarcodeFormat.CODE_39,
          ZXing.BarcodeFormat.CODE_93,
          ZXing.BarcodeFormat.CODABAR,
          ZXing.BarcodeFormat.EAN_13,
          ZXing.BarcodeFormat.EAN_8,
          ZXing.BarcodeFormat.UPC_A,
          ZXing.BarcodeFormat.UPC_E,
          ZXing.BarcodeFormat.ITF
        ]);
        zxingReader.setHints(zxingHints);
      } catch (e) {
        zxingReader = null;
      }
    }

    var canvas = document.createElement('canvas');
    var ctx = canvas.getContext('2d');

    function decodeFrame() {
      if (!scanner.videoWidth) return null;
      canvas.width = scanner.videoWidth;
      canvas.height = scanner.videoHeight;
      ctx.drawImage(scanner, 0, 0, canvas.width, canvas.height);
      var imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);

      if (detector) {
        // BarcodeDetector needs ImageBitmap or ImageData
        return detector.detect(imageData).then(function (codes) {
          return (codes.length > 0) ? codes[0].rawValue : null;
        });
      } else if (zxingReader) {
        try {
          // Convert RGBA ImageData into a packed Uint32Array for ZXing's luminance source
          var imgData = imageData.data;
          var px = new Uint32Array(imgData.length / 4);
          for (var pi = 0, o = 0; pi < px.length; pi++, o += 4) {
            px[pi] = (imgData[o] << 16) | (imgData[o + 1] << 8) | imgData[o + 2];
          }
          var luminance = new ZXing.RGBLuminanceSource(px, canvas.width, canvas.height);
          var binarizer = new ZXing.HybridBinarizer(luminance);
          var bitmap = new ZXing.BinaryBitmap(binarizer);
          var result = zxingReader.decodeWithState(bitmap);
          return Promise.resolve(result ? result.getText() : null);
        } catch (e) {
          return Promise.resolve(null); // no code found in this frame
        }
      }
      return Promise.resolve(null);
    }

    function onScanSuccess(value) {
      var now = Date.now();
      if (now - lastScanTime < SCAN_COOLDOWN) return;
      lastScanTime = now;

      beep(880, 0.12);
      setTimeout(function () { beep(1320, 0.1); }, 120);

      qrInput.value = value;
      flashResult(true);
      setStatus('Scanned! Submitting…');

      // Auto-submit after a brief visual confirmation
      setTimeout(function () {
        qrInput.form.submit();
      }, 600);
    }

    function scanLoop() {
      if (!scanning) return;
      if (!scanner.videoWidth) {
        requestAnimationFrame(scanLoop);
        return;
      }
      decodeFrame().then(function (value) {
        if (value) {
          onScanSuccess(value);
          return; // stop loop — form will submit
        }
        requestAnimationFrame(scanLoop);
      })['catch'](function () {
        setTimeout(scanLoop, 500);
      });
    }

    function startCamera() {
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        setStatus('Camera not supported');
        return;
      }
      navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } }
      }).then(function (s) {
        stream = s;
        scanner.srcObject = s;
        scanner.onloadedmetadata = function () {
          scanning = true;
          setStatus('Camera active — scan code');
          scanLoop();
        };
        // Fallback so the preview shows even if onloadedmetadata never fires
        scanner.play()['catch'](function () { });
      })['catch'](function () {
        setStatus('Camera denied — use manual entry');
      });
    }

    startCamera();

    window.addEventListener('pagehide', function () {
      scanning = false;
      if (stream) { stream.getTracks().forEach(function (t) { t.stop(); }); }
    });
  } else if (scanner) {
    scanner.style.display = 'none';
  }

  // ---- Confirmation for print (optional nicety) ----
  document.querySelectorAll('[data-print-confirm]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      if (!window.confirm('Mark this ID as printed?')) e.preventDefault();
    });
  });

  // ---- Photo capture (used on student management for ID staff) ----
  var uploadInput = document.getElementById('photoUpload');
  var cameraModal = document.getElementById('cameraModal');
  var cameraVideo = document.getElementById('cameraVideo');
  var captureCanvas = document.getElementById('captureCanvas');
  var activePhotoForm = null;

  function openCameraForStudent(studentId) {
    var form = document.getElementById('photoForm' + studentId);
    if (!form) return;
    activePhotoForm = form;
    openCamera();
  }
  window.openCameraForStudent = openCameraForStudent;

  if (cameraModal) {
    var camStream = null;

    function openCamera() {
      cameraModal.style.display = 'flex';
      if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({
          video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } }
        }).then(function (s) {
          camStream = s;
          if (cameraVideo) cameraVideo.srcObject = s;
        })['catch'](function () {
          alert('Camera access denied.');
          cameraModal.style.display = 'none';
        });
      } else {
        alert('Camera not supported in this browser.');
        cameraModal.style.display = 'none';
      }
    }

    function closeCamera() {
      cameraModal.style.display = 'none';
      if (camStream) { camStream.getTracks().forEach(function (t) { t.stop(); }); camStream = null; }
    }
    window.openCamera = openCamera;
    window.closeCamera = closeCamera;

    var captureConfirm = document.getElementById('captureConfirm');
    var captureCancel = document.getElementById('captureCancel');
    if (captureConfirm) {
      captureConfirm.addEventListener('click', function () {
        if (!cameraVideo || !cameraVideo.videoWidth) return;
        captureCanvas.width = cameraVideo.videoWidth;
        captureCanvas.height = cameraVideo.videoHeight;
        var cctx = captureCanvas.getContext('2d');
        cctx.drawImage(cameraVideo, 0, 0);
        captureCanvas.toBlob(function (blob) {
          if (!blob) return;
          closeCamera();
          if (!activePhotoForm) return;
          var file = new File([blob], 'photo.jpg', { type: 'image/jpeg' });
          // Build a new input holding the captured file, then submit
          var input = document.createElement('input');
          input.type = 'file';
          input.name = 'photo';
          input.accept = 'image/*';
          var dt = new DataTransfer();
          dt.items.add(file);
          input.files = dt.files;
          activePhotoForm.appendChild(input);
          activePhotoForm.submit();
        }, 'image/jpeg', 0.85);
      });
    }
    if (captureCancel) {
      captureCancel.addEventListener('click', closeCamera);
    }
  }

  // ---- Student photo file upload (auto-submit on file select) ----
  document.querySelectorAll('.photo-form input[type="file"]').forEach(function (fileInput) {
    fileInput.addEventListener('change', function () {
      if (fileInput.files && fileInput.files[0]) {
        fileInput.form.submit();
      }
    });
  });

  // ---- Edit student modal ----
  var editModal = document.getElementById('editModal');
  var editForm = document.getElementById('editStudentForm');
  if (editModal && editForm) {
    window.openEditModal = function (id, name, email, phone, course, year, section, address) {
      editForm.elements['id'].value = id;
      editForm.elements['full_name'].value = name || '';
      editForm.elements['email'].value = email || '';
      editForm.elements['phone'].value = phone || '';
      editForm.elements['course'].value = course || '';
      editForm.elements['year_level'].value = year || '';
      editForm.elements['section'].value = section || '';
      editForm.elements['address'].value = address || '';
      editModal.style.display = 'flex';
    };
    editModal.addEventListener('click', function (e) {
      if (e.target === editModal) editModal.style.display = 'none';
    });
    var editCancel = document.getElementById('editCancel');
    if (editCancel) editCancel.addEventListener('click', function () { editModal.style.display = 'none'; });
  }
})();
