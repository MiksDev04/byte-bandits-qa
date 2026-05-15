<?php
session_start();

if (!empty($_SESSION['logged_in'])) {
  header('Location: dashboard.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="x-api-key" content="<?= htmlspecialchars(getenv('APP_API_KEY'), ENT_QUOTES, 'UTF-8') ?>">

  <title>Forgot Password — QA Management System</title>
  <link rel="shortcut icon" href="../assets/images/byte-bandits-qa.ico" type="image/x-icon">

  <!-- Bootstrap 5 -->
  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <!-- hCaptcha -->
  <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
  <!-- Custom -->
  <link rel="stylesheet" href="../assets/css/styles.css">

  <style>
    .login-bg {
      position: fixed;
      inset: 0;
      background: linear-gradient(135deg, #f5f3ff 0%, #ede9fd 40%, #e0e7ff 100%);
      z-index: 0;
    }

    .login-bg::before {
      content: '';
      position: absolute;
      top: -120px;
      right: -80px;
      width: 450px;
      height: 450px;
      background: radial-gradient(circle, rgba(108, 92, 231, .12) 0%, transparent 70%);
      border-radius: 50%;
    }

    .login-bg::after {
      content: '';
      position: absolute;
      bottom: -100px;
      left: -60px;
      width: 380px;
      height: 380px;
      background: radial-gradient(circle, rgba(9, 132, 227, .08) 0%, transparent 70%);
      border-radius: 50%;
    }

    .login-page {
      position: relative;
      z-index: 1;
    }

    .login-card {
      animation: fadeUp .4s ease both;
    }

    @keyframes fadeUp {
      from {
        opacity: 0;
        transform: translateY(18px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .input-group-qa {
      position: relative;
    }

    .input-group-qa .input-icon {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
      font-size: .82rem;
      z-index: 2;
      pointer-events: none;
    }

    .input-group-qa .form-control-qa {
      padding-left: 36px;
    }

    .input-group-qa .toggle-pw {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: var(--text-muted);
      cursor: pointer;
      font-size: .82rem;
      z-index: 2;
      padding: 4px;
    }

    .input-group-qa .toggle-pw:hover {
      color: var(--text-primary);
    }

    .login-footer {
      text-align: center;
      font-size: .75rem;
      color: var(--text-muted);
      margin-top: 24px;
    }

    .alert-login {
      border-radius: var(--radius-sm);
      font-size: .83rem;
      padding: 10px 14px;
      margin-bottom: 16px;
      display: none;
      animation: fadeUp .2s ease;
    }

    /* ── Step indicator ──────────────────────────────── */
    .step-indicator {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0;
      margin-bottom: 24px;
    }

    .step-dot {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: .7rem;
      font-weight: 700;
      transition: all .3s ease;
      position: relative;
      z-index: 1;
      flex-shrink: 0;
    }

    .step-dot.active {
      background: var(--primary, #6c5ce7);
      color: #fff;
      box-shadow: 0 0 0 4px rgba(108, 92, 231, .18);
    }

    .step-dot.done {
      background: #22c55e;
      color: #fff;
    }

    .step-dot.pending {
      background: #e5e7eb;
      color: #9ca3af;
    }

    .step-line {
      flex: 1;
      height: 2px;
      max-width: 44px;
      background: #e5e7eb;
      transition: background .4s ease;
    }

    .step-line.done {
      background: #22c55e;
    }

    /* ── OTP input row ───────────────────────────────── */
    .otp-row {
      display: flex;
      gap: 8px;
      justify-content: center;
      margin: 6px 0 4px;
    }

    .otp-box {
      width: 44px;
      height: 52px;
      text-align: center;
      font-size: 1.25rem;
      font-weight: 700;
      border: 2px solid #d1d5db;
      border-radius: 10px;
      outline: none;
      transition: border-color .2s, box-shadow .2s;
      background: #fff;
      color: var(--text-primary, #1e1b4b);
      caret-color: var(--primary, #6c5ce7);
    }

    .otp-box:focus {
      border-color: var(--primary, #6c5ce7);
      box-shadow: 0 0 0 3px rgba(108, 92, 231, .15);
    }

    .otp-box.filled {
      border-color: var(--primary, #6c5ce7);
      background: rgba(108, 92, 231, .04);
    }

    /* ── Resend timer ────────────────────────────────── */
    .resend-row {
      text-align: center;
      font-size: .78rem;
      color: var(--text-muted);
      margin-top: 10px;
    }

    .resend-row a {
      color: var(--primary, #6c5ce7);
      cursor: pointer;
      text-decoration: none;
      font-weight: 600;
    }

    .resend-row a:hover {
      text-decoration: underline;
    }

    .resend-row a.disabled {
      pointer-events: none;
      color: var(--text-muted);
      font-weight: 400;
    }

    /* ── Password strength bar ───────────────────────── */
    .pw-strength-bar {
      height: 4px;
      border-radius: 2px;
      background: #e5e7eb;
      margin-top: 8px;
      overflow: hidden;
    }

    .pw-strength-fill {
      height: 100%;
      width: 0%;
      border-radius: 2px;
      transition: width .3s ease, background .3s ease;
    }

    .pw-strength-label {
      font-size: .72rem;
      margin-top: 4px;
      min-height: 16px;
    }

    /* ── hCaptcha centering ──────────────────────────── */
    .hcaptcha-wrap {
      display: flex;
      justify-content: center;
      margin: 12px 0 4px;
    }

    /* ── Back link ───────────────────────────────────── */
    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: .78rem;
      color: var(--text-muted);
      text-decoration: none;
      margin-bottom: 16px;
      transition: color .2s;
    }

    .back-link:hover {
      color: var(--primary, #6c5ce7);
    }

    /* ── Success state ───────────────────────────────── */
    .success-icon {
      width: 64px;
      height: 64px;
      background: linear-gradient(135deg, #22c55e, #16a34a);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 16px;
      font-size: 1.6rem;
      color: #fff;
      animation: popIn .4s cubic-bezier(.34, 1.56, .64, 1) both;
    }

    @keyframes popIn {
      from {
        transform: scale(0);
        opacity: 0;
      }

      to {
        transform: scale(1);
        opacity: 1;
      }
    }

    /* Step panels */
    .step-panel {
      display: none;
    }

    .step-panel.active {
      display: block;
      animation: fadeUp .3s ease both;
    }
  </style>
</head>

<body>

  <div class="login-bg"></div>

  <main class="login-page">
    <div class="login-card">

      <!-- Back to login -->
      <a href="login.php" class="back-link">
        <i class="fa-solid fa-arrow-left"></i> Back to Sign In
      </a>

      <!-- Logo -->
      <div class="login-logo">
        <i class="fa-solid fa-key"></i>
      </div>

      <!-- Step indicator -->
      <div class="step-indicator" id="step-indicator">
        <div class="step-dot active" id="dot-1">1</div>
        <div class="step-line" id="line-1"></div>
        <div class="step-dot pending" id="dot-2">2</div>
        <div class="step-line" id="line-2"></div>
        <div class="step-dot pending" id="dot-3">3</div>
      </div>

      <!-- Alert banner (shared) -->
      <div id="fp-alert" class="alert alert-danger alert-login" role="alert">
        <i class="fa-solid fa-circle-xmark me-1"></i>
        <span id="fp-alert-msg"></span>
      </div>
      <div id="fp-success" class="alert alert-success alert-login" role="alert">
        <i class="fa-solid fa-circle-check me-1"></i>
        <span id="fp-success-msg"></span>
      </div>

      <!-- ══ STEP 1 — Email + hCaptcha ════════════════════════ -->
      <div class="step-panel active" id="step-1">
        <h1 class="login-title">Forgot Password?</h1>
        <p class="login-sub">Enter your email address and complete the verification below.</p>

        <div class="mb-3">
          <label class="form-label-qa" for="email">Email Address</label>
          <div class="input-group-qa">
            <span class="input-icon"><i class="fa-regular fa-envelope"></i></span>
            <input type="email"
              id="email"
              name="email"
              class="form-control-qa"
              placeholder="Enter your account email"
              maxlength="100"
              autocomplete="email"
              required>
          </div>
          <div class="form-error-msg" id="err-email"></div>
        </div>

        <!-- hCaptcha widget -->
        <div class="hcaptcha-wrap">
          <div class="h-captcha"
            data-sitekey="fa7a609a-d1f0-41a2-82d2-e148a45875ba"
            id="hcaptcha-widget"></div>
        </div>
        <div class="form-error-msg mb-3" id="err-captcha"></div>

        <button type="button" class="btn-login" id="btn-send-code">
          <i class="fa-solid fa-paper-plane"></i>
          Send Verification Code
        </button>
      </div>

      <!-- ══ STEP 2 — Enter OTP ════════════════════════════════ -->
      <div class="step-panel" id="step-2">
        <h1 class="login-title">Check Your Email</h1>
        <p class="login-sub" id="otp-sub-text">
          We sent a 6-digit code to <strong id="email-display"></strong>.<br>
          The code expires in <strong>15 minutes</strong>.
        </p>

        <div class="mb-4">
          <label class="form-label-qa d-block text-center mb-2">Verification Code</label>
          <div class="otp-row" id="otp-row">
            <input class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" data-idx="0">
            <input class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" data-idx="1">
            <input class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" data-idx="2">
            <input class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" data-idx="3">
            <input class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" data-idx="4">
            <input class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" data-idx="5">
          </div>
          <div class="form-error-msg text-center" id="err-otp"></div>
        </div>

        <button type="button" class="btn-login" id="btn-verify-code">
          <i class="fa-solid fa-shield-check"></i>
          Verify Code
        </button>

        <div class="resend-row mt-3">
          Didn't receive it? <a id="resend-link" class="disabled">Resend in <span id="resend-timer">60</span>s</a>
        </div>
      </div>

      <!-- ══ STEP 3 — New Password ═════════════════════════════ -->
      <div class="step-panel" id="step-3">
        <h1 class="login-title">Reset Password</h1>
        <p class="login-sub">Choose a new strong password for your account.</p>

        <div class="mb-3">
          <label class="form-label-qa" for="new-password">New Password</label>
          <div class="input-group-qa">
            <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
            <input type="password"
              id="new-password"
              name="new_password"
              class="form-control-qa"
              placeholder="Enter new password"
              maxlength="128"
              autocomplete="new-password"
              required>
            <button type="button" class="toggle-pw" id="toggle-np" aria-label="Show/hide password" tabindex="-1">
              <i class="fa-regular fa-eye" id="np-eye"></i>
            </button>
          </div>
          <div class="pw-strength-bar">
            <div class="pw-strength-fill" id="pw-fill"></div>
          </div>
          <div class="pw-strength-label" id="pw-label"></div>
          <div class="form-error-msg" id="err-new-password"></div>
        </div>

        <div class="mb-4">
          <label class="form-label-qa" for="confirm-password">Confirm Password</label>
          <div class="input-group-qa">
            <span class="input-icon"><i class="fa-solid fa-lock-keyhole"></i></span>
            <input type="password"
              id="confirm-password"
              name="confirm_password"
              class="form-control-qa"
              placeholder="Confirm new password"
              maxlength="128"
              autocomplete="new-password"
              required>
            <button type="button" class="toggle-pw" id="toggle-cp" aria-label="Show/hide password" tabindex="-1">
              <i class="fa-regular fa-eye" id="cp-eye"></i>
            </button>
          </div>
          <div class="form-error-msg" id="err-confirm-password"></div>
        </div>

        <button type="button" class="btn-login" id="btn-reset-pw">
          <i class="fa-solid fa-rotate-right"></i>
          Reset Password
        </button>
      </div>

      <!-- ══ STEP 4 — Success ══════════════════════════════════ -->
      <div class="step-panel" id="step-4">
        <div class="success-icon">
          <i class="fa-solid fa-check"></i>
        </div>
        <h1 class="login-title">Password Reset!</h1>
        <p class="login-sub">Your password has been changed successfully.<br>You can now sign in with your new password.</p>
        <a href="login.php" class="btn-login d-block text-center text-decoration-none">
          <i class="fa-solid fa-right-to-bracket"></i>
          Go to Sign In
        </a>
      </div>

      <div class="login-footer">
        &copy; <?= date('Y') ?> Quality Assurance Management System
        &bull; All rights reserved
      </div>

    </div>
  </main>

  <div id="toast-container"></div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/app.js"></script>
  <script>
    $(function() {

      const API = '../../backend/api/auth/forgot_password_api.php';
      let resetToken = ''; // token returned after OTP verified

      /* ═══════════════════════════════════════════════════════════
         STEP NAVIGATION
      ═══════════════════════════════════════════════════════════ */
      function goToStep(n) {
        $('.step-panel').removeClass('active');
        $('#step-' + n).addClass('active');
        clearAlerts();

        // Update dots
        for (let i = 1; i <= 3; i++) {
          const dot = $('#dot-' + i);
          const line = $('#line-' + i);
          dot.removeClass('active done pending');
          if (i < n) {
            dot.addClass('done');
            dot.html('<i class="fa-solid fa-check" style="font-size:.65rem"></i>');
          } else if (i === n) {
            dot.addClass('active');
            dot.text(i);
          } else {
            dot.addClass('pending');
            dot.text(i);
          }
          if (line.length) line.toggleClass('done', i < n);
        }

        // Step 4 hides the indicator
        if (n === 4) $('#step-indicator').hide();
      }

      /* ═══════════════════════════════════════════════════════════
         ALERTS
      ═══════════════════════════════════════════════════════════ */
      function showError(msg) {
        $('#fp-success').hide();
        $('#fp-alert-msg').text(msg);
        $('#fp-alert').fadeIn(200);
      }

      function showSuccess(msg) {
        $('#fp-alert').hide();
        $('#fp-success-msg').text(msg);
        $('#fp-success').fadeIn(200);
      }

      function clearAlerts() {
        $('#fp-alert, #fp-success').hide();
      }

      /* ═══════════════════════════════════════════════════════════
         STEP 1 — Send code
      ═══════════════════════════════════════════════════════════ */
      $('#btn-send-code').on('click', function() {
        clearAlerts();
        const email = $('#email').val().trim();
        let valid = true;

        // Basic email validation
        if (!email) {
          $('#err-email').text('Email is required.').addClass('show');
          valid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
          $('#err-email').text('Please enter a valid email address.').addClass('show');
          valid = false;
        } else {
          $('#err-email').text('').removeClass('show');
        }

        // hCaptcha token
        const captchaResp = hcaptcha.getResponse();
        if (!captchaResp) {
          $('#err-captcha').text('Please complete the "I am human" verification.').addClass('show');
          valid = false;
        } else {
          $('#err-captcha').text('').removeClass('show');
        }

        if (!valid) return;

        const btn = document.getElementById('btn-send-code');
        btnLoading(btn, 'Sending…');

        $.ajax({
          url: API,
          type: 'POST',
          data: JSON.stringify({
            action: 'send_code',
            email,
            captcha: captchaResp
          }),
          contentType: 'application/json',
          dataType: 'json',
          success(data) {
            if (data.success) {
              $('#email-display').text(email);
              goToStep(2);
              startResendTimer(60);
              setTimeout(() => $('#otp-row .otp-box').first().focus(), 200);
            } else {
              showError(data.message || 'Failed to send code.');
              hcaptcha.reset();
              btnReset(btn);
            }
          },
          error(xhr) {
            let msg = 'A server error occurred. Please try again.';
            try {
              msg = JSON.parse(xhr.responseText).message || msg;
            } catch (e) {}
            showError(msg);
            hcaptcha.reset();
            btnReset(btn);
          }
        });
      });

      /* ═══════════════════════════════════════════════════════════
         STEP 2 — OTP boxes
      ═══════════════════════════════════════════════════════════ */
      // Auto-advance & backspace navigation
      $('#otp-row').on('input', '.otp-box', function() {
        const val = $(this).val().replace(/\D/g, '');
        $(this).val(val);
        $(this).toggleClass('filled', val.length > 0);
        if (val.length === 1) {
          const idx = parseInt($(this).data('idx'));
          if (idx < 5) {
            $('#otp-row .otp-box').eq(idx + 1).focus();
          }
        }
        clearOtpError();
      });

      $('#otp-row').on('keydown', '.otp-box', function(e) {
        if (e.key === 'Backspace' && !$(this).val()) {
          const idx = parseInt($(this).data('idx'));
          if (idx > 0) {
            const prev = $('#otp-row .otp-box').eq(idx - 1);
            prev.val('').removeClass('filled').focus();
          }
        }
        // Allow paste
      });

      // Paste handler — distribute digits across boxes
      $('#otp-row').on('paste', '.otp-box', function(e) {
        e.preventDefault();
        const pasted = (e.originalEvent.clipboardData || window.clipboardData)
          .getData('text').replace(/\D/g, '').slice(0, 6);
        $('#otp-row .otp-box').each(function(i) {
          const ch = pasted[i] || '';
          $(this).val(ch).toggleClass('filled', ch !== '');
        });
        // Focus last filled or last box
        const focusIdx = Math.min(pasted.length, 5);
        $('#otp-row .otp-box').eq(focusIdx).focus();
      });

      function getOtpValue() {
        return $('#otp-row .otp-box').map(function() {
          return $(this).val();
        }).get().join('');
      }

      function clearOtpError() {
        $('#err-otp').text('').removeClass('show');
      }

      $('#btn-verify-code').on('click', function() {
        clearAlerts();
        const code = getOtpValue();
        const email = $('#email-display').text();

        if (code.length < 6) {
          $('#err-otp').text('Please enter all 6 digits of the verification code.').addClass('show');
          return;
        }

        const btn = document.getElementById('btn-verify-code');
        btnLoading(btn, 'Verifying…');

        $.ajax({
          url: API,
          type: 'POST',
          data: JSON.stringify({
            action: 'verify_code',
            email,
            code
          }),
          contentType: 'application/json',
          dataType: 'json',
          success(data) {
            if (data.success) {
              resetToken = data.token || '';
              goToStep(3);
              setTimeout(() => $('#new-password').focus(), 200);
            } else {
              showError(data.message || 'Invalid or expired code.');
              $('#otp-row .otp-box').addClass('is-invalid');
              btnReset(btn);
            }
          },
          error(xhr) {
            let msg = 'A server error occurred. Please try again.';
            try {
              msg = JSON.parse(xhr.responseText).message || msg;
            } catch (e) {}
            showError(msg);
            btnReset(btn);
          }
        });
      });

      /* ── Resend timer ──────────────────────────────────────── */
      let resendInterval;

      function startResendTimer(seconds) {
        clearInterval(resendInterval);
        let remaining = seconds;
        $('#resend-timer').text(remaining);
        $('#resend-link').addClass('disabled').html('Resend in <span id="resend-timer">' + remaining + '</span>s');

        resendInterval = setInterval(function() {
          remaining--;
          $('#resend-timer').text(remaining);
          if (remaining <= 0) {
            clearInterval(resendInterval);
            $('#resend-link').removeClass('disabled').text('Resend Code');
          }
        }, 1000);
      }

      // Resend action
      $(document).on('click', '#resend-link:not(.disabled)', function() {
        const email = $('#email-display').text();
        $.ajax({
          url: API,
          type: 'POST',
          data: JSON.stringify({
            action: 'send_code',
            email,
            resend: true
          }),
          contentType: 'application/json',
          dataType: 'json',
          success(data) {
            if (data.success) {
              showSuccess('A new code has been sent to your email.');
              startResendTimer(60);
              $('#otp-row .otp-box').val('').removeClass('filled is-invalid');
              setTimeout(() => clearAlerts(), 4000);
            } else {
              showError(data.message || 'Could not resend code.');
            }
          },
          error() {
            showError('Failed to resend code. Please try again.');
          }
        });
      });

      /* ═══════════════════════════════════════════════════════════
         STEP 3 — Password reset
      ═══════════════════════════════════════════════════════════ */
      // Password strength meter
      $('#new-password').on('input', function() {
        const pw = $(this).val();
        let score = 0;
        if (pw.length >= 8) score++;
        if (/[A-Z]/.test(pw)) score++;
        if (/[0-9]/.test(pw)) score++;
        if (/[^A-Za-z0-9]/.test(pw)) score++;

        const fills = [0, 25, 50, 75, 100];
        const colors = ['', '#ef4444', '#f97316', '#eab308', '#22c55e'];
        const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];

        $('#pw-fill').css({
          width: fills[score] + '%',
          background: colors[score]
        });
        $('#pw-label').text(labels[score]).css('color', colors[score]);
      });

      // Toggle password visibility
      function togglePw(inputId, eyeId) {
        const inp = $('#' + inputId);
        const eye = $('#' + eyeId);
        if (inp.attr('type') === 'password') {
          inp.attr('type', 'text');
          eye.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
          inp.attr('type', 'password');
          eye.removeClass('fa-eye-slash').addClass('fa-eye');
        }
      }
      $('#toggle-np').on('click', () => togglePw('new-password', 'np-eye'));
      $('#toggle-cp').on('click', () => togglePw('confirm-password', 'cp-eye'));

      $('#btn-reset-pw').on('click', function() {
        clearAlerts();
        const pw = $('#new-password').val();
        const cpw = $('#confirm-password').val();
        let valid = true;

        if (!pw || pw.length < 8) {
          $('#err-new-password').text('Password must be at least 8 characters.').addClass('show');
          valid = false;
        } else {
          $('#err-new-password').text('').removeClass('show');
        }

        if (pw !== cpw) {
          $('#err-confirm-password').text('Passwords do not match.').addClass('show');
          valid = false;
        } else {
          $('#err-confirm-password').text('').removeClass('show');
        }

        if (!valid) return;

        const btn = document.getElementById('btn-reset-pw');
        btnLoading(btn, 'Resetting…');

        $.ajax({
          url: API,
          type: 'POST',
          data: JSON.stringify({
            action: 'reset_password',
            token: resetToken,
            password: pw
          }),
          contentType: 'application/json',
          dataType: 'json',
          success(data) {
            if (data.success) {
              goToStep(4);
            } else {
              showError(data.message || 'Failed to reset password.');
              btnReset(btn);
            }
          },
          error(xhr) {
            let msg = 'A server error occurred. Please try again.';
            try {
              msg = JSON.parse(xhr.responseText).message || msg;
            } catch (e) {}
            showError(msg);
            btnReset(btn);
          }
        });
      });

      /* ── Clear field errors on input ──────────────────────── */
      $('#email').on('input', function() {
        $('#err-email').text('').removeClass('show');
        clearAlerts();
      });

      $('#new-password, #confirm-password').on('input', function() {
        $(this).closest('.mb-3, .mb-4').find('.form-error-msg').text('').removeClass('show');
      });

    });
  </script>
</body>

</html>