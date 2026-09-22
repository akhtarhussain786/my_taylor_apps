/**
 * MY TAYLOR - Client Application Logic
 * Interactive Geolocation, 24H SLA Countdown Timer, Signature Pad & Slot Picker
 */

document.addEventListener('DOMContentLoaded', () => {
  initSlotPicker();
  initCountdownTimer();
  initGeolocationHelper();
  initPincodeChecker();
  initSignaturePad();
});

/* 1. Time Slot Picker */
function initSlotPicker() {
  const slotItems = document.querySelectorAll('.slot-item');
  const slotInput = document.getElementById('selected-time-slot');

  slotItems.forEach(item => {
    item.addEventListener('click', () => {
      slotItems.forEach(i => i.classList.remove('selected'));
      item.classList.add('selected');
      if (slotInput) {
        slotInput.value = item.getAttribute('data-slot');
      }
    });
  });
}

/* 2. Real-Time 24-Hour SLA Countdown Timer */
function initCountdownTimer() {
  const timerElement = document.getElementById('sla-countdown');
  if (!timerElement) return;

  const deadlineStr = timerElement.getAttribute('data-deadline');
  if (!deadlineStr) return;

  const deadline = new Date(deadlineStr).getTime();

  function updateTimer() {
    const now = new Date().getTime();
    const distance = deadline - now;

    if (distance < 0) {
      timerElement.innerHTML = "<span class='text-gold'>SLA Met / On Final Route</span>";
      return;
    }

    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

    const pad = (n) => String(n).padStart(2, '0');
    timerElement.innerHTML = `${pad(hours)}h ${pad(minutes)}m ${pad(seconds)}s <small style="font-size:12px;opacity:0.8;">Remaining</small>`;
  }

  updateTimer();
  setInterval(updateTimer, 1000);
}

/* 3. Geolocation Helper */
function initGeolocationHelper() {
  const geoBtn = document.getElementById('btn-detect-location');
  const latInput = document.getElementById('input-lat');
  const lngInput = document.getElementById('input-lng');
  const geoStatus = document.getElementById('geo-status');

  if (geoBtn) {
    geoBtn.addEventListener('click', () => {
      if (!navigator.geolocation) {
        alert("Geolocation is not supported by your browser. Please enter your address manually.");
        return;
      }

      if (geoStatus) geoStatus.innerText = "Detecting high-precision GPS coordinates...";

      navigator.geolocation.getCurrentPosition(
        (pos) => {
          const lat = pos.coords.latitude;
          const lng = pos.coords.longitude;
          if (latInput) latInput.value = lat.toFixed(6);
          if (lngInput) lngInput.value = lng.toFixed(6);
          if (geoStatus) {
            geoStatus.innerHTML = `<span style="color:var(--accent-emerald);">✓ GPS Locked: ${lat.toFixed(4)}, ${lng.toFixed(4)}</span>`;
          }
        },
        (err) => {
          if (geoStatus) {
            geoStatus.innerText = "Could not detect GPS automatically. Please confirm address below.";
          }
        },
        { enableHighAccuracy: true, timeout: 10000 }
      );
    });
  }
}

/* 4. Pincode Serviceability AJAX Checker */
function initPincodeChecker() {
  const checkBtn = document.getElementById('btn-check-pincode');
  const pincodeInput = document.getElementById('input-pincode');
  const resultDiv = document.getElementById('pincode-result');

  if (checkBtn && pincodeInput && resultDiv) {
    checkBtn.addEventListener('click', () => {
      const pin = pincodeInput.value.trim();
      if (pin.length < 6) {
        resultDiv.innerHTML = `<span style="color:var(--accent-rose);">Please enter a valid 6-digit pincode.</span>`;
        return;
      }

      resultDiv.innerHTML = `<span style="color:var(--gold-primary);">Checking serviceability & 24H capacity...</span>`;

      fetch(`api/serviceability.php?pincode=${encodeURIComponent(pin)}`)
        .then(res => res.json())
        .then(data => {
          if (data.status === 'success') {
            if (data.is_24h_available) {
              resultDiv.innerHTML = `
                <div class="card" style="background:#F0FDF4;border-color:#86EFAC;padding:12px 16px;margin-top:10px;">
                  <strong style="color:#166534;">⚡ 24-Hour Express Guaranteed for ${data.area_name} (${data.city})</strong>
                  <p style="font-size:13px;color:#15803D;margin:4px 0 0;">Doorstep measurement slot available today. Delivery charge: ₹0.00</p>
                </div>
              `;
            } else {
              resultDiv.innerHTML = `
                <div class="card" style="background:#FEFCE8;border-color:#FDE047;padding:12px 16px;margin-top:10px;">
                  <strong style="color:#854D0E;">✓ Standard Tailoring Available for ${data.area_name}</strong>
                  <p style="font-size:13px;color:#A16207;margin:4px 0 0;">Standard delivery available (36-48 Hours). Express slot is currently full.</p>
                </div>
              `;
            }
          } else {
            resultDiv.innerHTML = `
              <div class="card" style="background:#FEF2F2;border-color:#FECACA;padding:12px 16px;margin-top:10px;">
                <strong style="color:#991B1B;">✕ Service Currently Expanding</strong>
                <p style="font-size:13px;color:#B91C1C;margin:4px 0 0;">We are currently not delivering in ${pin}. We are launching here soon!</p>
              </div>
            `;
          }
        })
        .catch(err => {
          resultDiv.innerHTML = `<span style="color:var(--accent-rose);">Could not verify pincode. Please try again.</span>`;
        });
    });
  }
}

/* 5. Signature Pad for Delivery Confirmation */
function initSignaturePad() {
  const canvas = document.getElementById('sig-canvas');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  let isDrawing = false;
  let lastX = 0;
  let lastY = 0;

  // Resize canvas for display pixel ratio
  function resizeCanvas() {
    const rect = canvas.getBoundingClientRect();
    canvas.width = rect.width;
    canvas.height = rect.height;
    ctx.lineWidth = 2.5;
    ctx.lineCap = 'round';
    ctx.strokeStyle = '#0F172A';
  }

  resizeCanvas();
  window.addEventListener('resize', resizeCanvas);

  function startDrawing(e) {
    isDrawing = true;
    const pos = getPos(e);
    lastX = pos.x;
    lastY = pos.y;
  }

  function draw(e) {
    if (!isDrawing) return;
    e.preventDefault();
    const pos = getPos(e);
    ctx.beginPath();
    ctx.moveTo(lastX, lastY);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
    lastX = pos.x;
    lastY = pos.y;

    // Save to hidden input
    const sigInput = document.getElementById('sig-data-input');
    if (sigInput) {
      sigInput.value = canvas.toDataURL();
    }
  }

  function stopDrawing() {
    isDrawing = false;
  }

  function getPos(e) {
    const rect = canvas.getBoundingClientRect();
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    return {
      x: clientX - rect.left,
      y: clientY - rect.top
    };
  }

  canvas.addEventListener('mousedown', startDrawing);
  canvas.addEventListener('mousemove', draw);
  canvas.addEventListener('mouseup', stopDrawing);
  canvas.addEventListener('mouseleave', stopDrawing);

  canvas.addEventListener('touchstart', startDrawing, { passive: false });
  canvas.addEventListener('touchmove', draw, { passive: false });
  canvas.addEventListener('touchend', stopDrawing);

  const clearBtn = document.getElementById('btn-clear-sig');
  if (clearBtn) {
    clearBtn.addEventListener('click', () => {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      const sigInput = document.getElementById('sig-data-input');
      if (sigInput) sigInput.value = '';
    });
  }
}
