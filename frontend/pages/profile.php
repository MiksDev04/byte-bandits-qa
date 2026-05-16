<?php

/**
 * Profile Page – View & Edit Current User Profile
 * frontend/pages/profile.php
 */

session_start();

if (empty($_SESSION['logged_in'])) {
  header('Location: login.php');
  exit;
}

$pageTitle = 'My Profile';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="x-api-key" content="<?= htmlspecialchars(getenv('APP_API_KEY'), ENT_QUOTES, 'UTF-8') ?>">

  <title><?= htmlspecialchars($pageTitle) ?> — QA System</title>
  <link rel="shortcut icon" href="../assets/images/byte-bandits-qa.ico" type="image/x-icon">

  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">

  <style>
    /* ── Profile-specific additions (all tokens from styles.css) ── */

    .profile-hero {
      background: var(--primary, #2d5a3d);
      border-radius: var(--radius, 10px);
      padding: 28px 32px;
      display: flex;
      align-items: center;
      gap: 24px;
      margin-bottom: 24px;
      position: relative;
      overflow: hidden;
    }

    .profile-hero::before {
      content: '';
      position: absolute;
      top: -50px;
      right: -50px;
      width: 200px;
      height: 200px;
      background: rgba(255, 255, 255, .05);
      border-radius: 50%;
      pointer-events: none;
    }

    .profile-hero::after {
      content: '';
      position: absolute;
      bottom: -70px;
      left: 140px;
      width: 260px;
      height: 260px;
      background: rgba(255, 255, 255, .04);
      border-radius: 50%;
      pointer-events: none;
    }

    .profile-avatar {
      flex-shrink: 0;
      width: 68px;
      height: 68px;
      border-radius: 50%;
      background: rgba(255, 255, 255, .18);
      border: 2px solid rgba(255, 255, 255, .28);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      font-weight: 700;
      color: #fff;
      letter-spacing: -.5px;
      position: relative;
      z-index: 1;
    }

    .profile-hero-info {
      position: relative;
      z-index: 1;
      flex: 1;
      min-width: 0;
    }

    .profile-hero-info .hero-name {
      font-size: 1.12rem;
      font-weight: 700;
      color: #fff;
      margin-bottom: 2px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .profile-hero-info .hero-email {
      font-size: .79rem;
      color: rgba(255, 255, 255, .72);
      margin-bottom: 9px;
    }

    .hero-badges {
      display: flex;
      gap: 7px;
      flex-wrap: wrap;
    }

    .badge-hero {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: .68rem;
      font-weight: 600;
      letter-spacing: .04em;
      text-transform: uppercase;
      background: rgba(255, 255, 255, .18);
      color: #fff;
      border: 1px solid rgba(255, 255, 255, .2);
    }

    .hero-stats {
      display: flex;
      gap: 12px;
      position: relative;
      z-index: 1;
      flex-shrink: 0;
    }

    .hero-stat-box {
      background: rgba(255, 255, 255, .12);
      border: 1px solid rgba(255, 255, 255, .15);
      border-radius: 8px;
      padding: 10px 18px;
      text-align: center;
      min-width: 76px;
    }

    .hero-stat-box .stat-n {
      font-size: 1.25rem;
      font-weight: 700;
      color: #fff;
      line-height: 1;
    }

    .hero-stat-box .stat-lbl {
      font-size: .61rem;
      color: rgba(255, 255, 255, .68);
      margin-top: 3px;
      text-transform: uppercase;
      letter-spacing: .05em;
    }

    /* ── Form helpers ─────────────────────────────────────── */
    .form-label-qa {
      font-size: .74rem;
      font-weight: 600;
      color: var(--text-secondary, #6b6860);
      text-transform: uppercase;
      letter-spacing: .06em;
      margin-bottom: 5px;
      display: block;
    }

    .form-control-qa {
      width: 100%;
      padding: 9px 13px;
      border: 1.5px solid var(--border, #e2ddd4);
      border-radius: 7px;
      font-family: var(--font, inherit);
      font-size: .88rem;
      color: var(--text-primary, #1a1a18);
      background: #fff;
      transition: border-color .18s, box-shadow .18s;
      outline: none;
    }

    .form-control-qa:focus {
      border-color: var(--primary, #2d5a3d);
      box-shadow: 0 0 0 3px rgba(45, 90, 61, .1);
    }

    .form-control-qa.is-invalid {
      border-color: #c0392b;
    }

    .form-control-qa:disabled,
    .form-control-qa[readonly] {
      background: var(--bg, #f5f4f0);
      color: var(--text-secondary, #6b6860);
      cursor: default;
    }

    .invalid-feedback-qa {
      font-size: .74rem;
      color: #c0392b;
      margin-top: 3px;
      display: none;
    }

    .invalid-feedback-qa.show {
      display: block;
    }

    /* ── Email hint ────────────────────────────────────────── */
    .email-field-hint {
      font-size: .72rem;
      color: var(--text-secondary, #6b6860);
      margin-top: 4px;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .email-field-hint a {
      color: var(--primary, #2d5a3d);
      text-decoration: underline;
    }

    /* ── Password strength ─────────────────────────────────── */
    .strength-bar {
      height: 4px;
      border-radius: 2px;
      background: var(--border, #e2ddd4);
      overflow: hidden;
      margin-top: 6px;
    }

    .strength-fill {
      height: 100%;
      border-radius: 2px;
      transition: width .25s, background .25s;
      width: 0%;
    }

    .strength-text {
      font-size: .72rem;
      color: var(--text-secondary, #6b6860);
      margin-top: 3px;
    }

    /* ── Account meta list ─────────────────────────────────── */
    .meta-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .meta-list li {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 0;
      border-bottom: 1px solid var(--border-light, #f0ede6);
      font-size: .85rem;
    }

    .meta-list li:last-child {
      border-bottom: none;
    }

    .meta-key {
      color: var(--text-secondary, #6b6860);
    }

    .meta-val {
      font-weight: 600;
      color: var(--text-primary, #1a1a18);
    }

    /* ── Email connection card ─────────────────────────────── */
    .gmail-status-banner {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 16px;
      border-radius: 8px;
      margin-bottom: 18px;
      font-size: .85rem;
      border: 1.5px solid;
    }

    .gmail-status-banner.connected {
      background: #f0faf4;
      border-color: #27ae60;
      color: #1a6e3c;
    }

    .gmail-status-banner.disconnected {
      background: #fff8f0;
      border-color: var(--border, #e2ddd4);
      color: var(--text-secondary, #6b6860);
    }

    .gmail-status-icon {
      font-size: 1.35rem;
      flex-shrink: 0;
    }

    .gmail-status-text {
      flex: 1;
    }

    .gmail-status-text strong {
      display: block;
      font-size: .87rem;
    }

    .gmail-status-text small {
      font-size: .76rem;
      opacity: .8;
    }

    /* Toggle link for the connect form */
    .gmail-toggle-link {
      font-size: .78rem;
      color: var(--primary, #2d5a3d);
      text-decoration: underline;
      cursor: pointer;
      background: none;
      border: none;
      padding: 0;
    }

    @media (max-width: 640px) {
      .profile-hero {
        flex-direction: column;
        text-align: center;
      }

      .hero-stats {
        justify-content: center;
      }

      .hero-badges {
        justify-content: center;
      }
    }

    /* ── OTP shake animation ───────────────────────────────── */
    @keyframes otpShake {

      0%,
      100% {
        transform: translateX(0);
      }

      20% {
        transform: translateX(-5px);
      }

      40% {
        transform: translateX(5px);
      }

      60% {
        transform: translateX(-4px);
      }

      80% {
        transform: translateX(4px);
      }
    }

    #otpBoxRow.otp-shake,
    #emailOtpBoxRow.otp-shake {
      animation: otpShake .45s ease;
    }
  </style>
</head>

<body>

  <div class="qa-wrapper">

    <!-- ── Sidebar ───────────────────────────────────────────── -->
    <?php include '../partials/sidebar.php'; ?>

    <!-- ── Main content ─────────────────────────────────────── -->
    <div class="qa-content">

      <?php include '../partials/header.php'; ?>

      <main class="qa-page">

        <!-- ── Page heading ─────────────────────────────────── -->
        <div class="d-flex align-items-center justify-content-between mb-4">
          <div>
            <h2 class="mb-0" style="font-size:1.25rem;font-weight:700;letter-spacing:-.4px;">
              My Profile
            </h2>
            <p class="text-muted-qa mb-0" style="font-size:.83rem;margin-top:2px;">
              Manage your account details and security settings
            </p>
          </div>
        </div>

        <!-- ── Hero card ─────────────────────────────────────── -->
        <div class="profile-hero">
          <div class="profile-avatar" id="heroAvatar">…</div>
          <div class="profile-hero-info">
            <div class="hero-name" id="heroName">
              <span class="placeholder-wave">
                <span class="placeholder col-5 bg-light rounded"></span>
              </span>
            </div>
            <div class="hero-email" id="heroEmail">
              <span class="placeholder-wave">
                <span class="placeholder col-4 bg-light rounded" style="height:10px;display:inline-block;"></span>
              </span>
            </div>
            <div class="hero-badges" id="heroBadges"></div>
          </div>
          <div class="hero-stats" id="heroStats"></div>
        </div>

        <!-- ── Forms row ─────────────────────────────────────── -->
        <div class="row g-3 mb-3">

          <!-- Personal Information -->
          <div class="col-12 col-lg-6">
            <div class="card h-100">
              <div class="card-header-custom">
                <h3 class="card-title">
                  <span class="me-2"
                    style="width:10px;height:10px;background:var(--primary);border-radius:50%;display:inline-block;"></span>
                  Personal Information
                </h3>
              </div>
              <div class="card-body-custom">
                <form id="infoForm" novalidate>

                  <div class="mb-3">
                    <label class="form-label-qa">Username</label>
                    <input type="text" class="form-control-qa" id="usernameField" disabled>
                  </div>

                  <div class="mb-3">
                    <label class="form-label-qa" for="fullName">Full Name</label>
                    <input type="text" class="form-control-qa" id="fullName"
                      placeholder="Your full name">
                    <div class="invalid-feedback-qa" id="errFullName"></div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label-qa" for="emailField">Email Address</label>
                    <input type="email" class="form-control-qa" id="emailField"
                      placeholder="—" readonly>
                    <div class="email-field-hint" id="emailFieldHint" style="display:none;"></div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label-qa">Role</label>
                    <input type="text" class="form-control-qa" id="roleField" disabled>
                  </div>

                  <!-- Divider -->
                  <hr style="border-color:var(--border-light,#f0ede6);margin:18px 0 16px;">

                  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <button type="button" class="btn-secondary-qa" id="btnOpenChangePwd"
                      style="display:inline-flex;align-items:center;gap:6px;
                                 padding:8px 16px;border-radius:7px;font-size:.85rem;font-weight:600;
                                 border:1.5px solid var(--border,#e2ddd4);background:#fff;
                                 color:var(--text-primary,#1a1a18);cursor:pointer;transition:border-color .18s,background .18s;">
                      <i class="fa-solid fa-lock" style="font-size:.8rem;color:var(--accent-orange,#e67e22);"></i>
                      Change Password
                    </button>
                    <button type="submit" class="btn-primary-qa" id="btnSaveInfo" disabled>
                      <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
                    </button>
                  </div>

                </form>
              </div>
            </div>
          </div>

          <div class="col-12 col-lg-6">
            <div class="row g-3">
              <div class="col-12">
                <div class="card">
                  <div class="card-header-custom">
                    <h3 class="card-title">
                      <span class="me-2"
                        style="width:10px;height:10px;background:var(--accent-blue);border-radius:50%;display:inline-block;"></span>
                      Account Details
                    </h3>
                  </div>
                  <div class="card-body-custom">
                    <ul class="meta-list" id="metaList">
                      <!-- Skeleton placeholder rows -->
                      <?php for ($i = 0; $i < 5; $i++): ?>
                        <li>
                          <span class="meta-key">
                            <span class="placeholder-wave">
                              <span class="placeholder col-3 bg-secondary rounded"></span>
                            </span>
                          </span>
                          <span class="meta-val">
                            <span class="placeholder-wave">
                              <span class="placeholder col-2 bg-secondary rounded"></span>
                            </span>
                          </span>
                        </li>
                      <?php endfor; ?>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- ── Email Address Card ─────────────────────────────── -->
        <div class="row g-3 mb-3" id="gmailCard">
          <div class="col-12">
            <div class="card">
              <div class="card-header-custom d-flex align-items-center justify-content-between">
                <h3 class="card-title mb-0">
                  <span class="me-2"
                    style="width:10px;height:10px;background:#EA4335;border-radius:50%;display:inline-block;"></span>
                  Email Address
                </h3>
              </div>
              <div class="card-body-custom">

                <!-- Status banner (rendered by JS) -->
                <div id="gmailStatusBanner"></div>

                <!-- Connect / Update form -->
                <form id="gmailForm" novalidate style="display:none;">
                  <p style="font-size:.84rem;color:var(--text-secondary,#6b6860);margin:0 0 14px;">
                    <i class="fa-solid fa-circle-info me-1" style="color:var(--primary,#2d5a3d);"></i>
                    Enter a real email address — this is where you'll receive password reset codes
                    and system notifications.
                  </p>

                  <div class="col-12 col-md-6">
                    <label class="form-label-qa" for="gmailUsername">Email Address</label>
                    <input type="email" class="form-control-qa" id="gmailUsername"
                      placeholder="e.g. yourname@gmail.com">
                    <div class="invalid-feedback-qa" id="errGmailUsername"></div>
                  </div>

                  <div class="d-flex align-items-center gap-2 mt-3 flex-wrap">
                    <button type="submit" class="btn-primary-qa" id="btnConnectGmail">
                      <i class="fa-solid fa-paper-plane me-1"></i> Send Verification Code
                    </button>
                    <button type="button" class="gmail-toggle-link" id="btnCancelGmail" style="display:none;">
                      Cancel
                    </button>
                  </div>
                </form>

              </div>
            </div>
          </div>
        </div>

      </main>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════════════════════════
     MODAL 1 — Confirm: send verification code (password change)
═══════════════════════════════════════════════════════════════ -->
  <div class="modal fade" id="modalConfirmCode" tabindex="-1" aria-labelledby="modalConfirmCodeLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:430px;">
      <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 8px 32px rgba(0,0,0,.14);">

        <div class="modal-header" style="border-bottom:1px solid var(--border-light,#f0ede6);padding:20px 24px 16px;">
          <div style="display:flex;align-items:center;gap:10px;">
            <span style="width:34px;height:34px;border-radius:50%;background:rgba(230,126,34,.12);
                       display:flex;align-items:center;justify-content:center;">
              <i class="fa-solid fa-envelope-open-text" style="color:var(--accent-orange,#e67e22);font-size:.9rem;"></i>
            </span>
            <h5 class="modal-title mb-0" id="modalConfirmCodeLabel"
              style="font-size:.97rem;font-weight:700;letter-spacing:-.3px;">
              Change Password
            </h5>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
            style="font-size:.75rem;"></button>
        </div>

        <div class="modal-body" style="padding:22px 24px;">
          <p style="font-size:.88rem;color:var(--text-primary,#1a1a18);margin:0 0 6px;">
            Would you like me to send a verification code to
          </p>
          <p style="font-size:.92rem;font-weight:700;color:var(--primary,#2d5a3d);
                  margin:0 0 16px;word-break:break-all;" id="confirmCodeEmail">—</p>
          <p style="font-size:.8rem;color:var(--text-secondary,#6b6860);margin:0;">
            The code will expire in <strong>10 minutes</strong>. You must enter it to proceed.
          </p>
        </div>

        <div class="modal-footer" style="border-top:1px solid var(--border-light,#f0ede6);
                                       padding:14px 24px;gap:8px;">
          <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
            style="padding:7px 18px;border-radius:7px;font-size:.84rem;font-weight:600;
                       border:1.5px solid var(--border,#e2ddd4);background:#fff;
                       color:var(--text-secondary,#6b6860);">
            <i class="fa-solid fa-xmark me-1"></i> No, cancel
          </button>
          <button type="button" class="btn btn-sm" id="btnYesSendCode"
            style="padding:7px 20px;border-radius:7px;font-size:.84rem;font-weight:600;
                       background:var(--primary,#2d5a3d);color:#fff;border:none;">
            <i class="fa-solid fa-paper-plane me-1"></i> Yes, send code
          </button>
        </div>

      </div>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════════════════════════
     MODAL 2 — Enter verification code (password change)
═══════════════════════════════════════════════════════════════ -->
  <div class="modal fade" id="modalEnterCode" tabindex="-1" aria-labelledby="modalEnterCodeLabel"
    aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
      <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 8px 32px rgba(0,0,0,.14);">

        <div class="modal-header" style="border-bottom:1px solid var(--border-light,#f0ede6);padding:20px 24px 16px;">
          <div style="display:flex;align-items:center;gap:10px;">
            <span style="width:34px;height:34px;border-radius:50%;background:rgba(45,90,61,.1);
                       display:flex;align-items:center;justify-content:center;">
              <i class="fa-solid fa-key" style="color:var(--primary,#2d5a3d);font-size:.9rem;"></i>
            </span>
            <h5 class="modal-title mb-0" id="modalEnterCodeLabel"
              style="font-size:.97rem;font-weight:700;letter-spacing:-.3px;">
              Enter Verification Code
            </h5>
          </div>
        </div>

        <div class="modal-body" style="padding:22px 24px;">
          <p style="font-size:.85rem;color:var(--text-secondary,#6b6860);margin:0 0 18px;">
            A 6-digit code was sent to <strong id="enterCodeEmail" style="color:var(--text-primary,#1a1a18);">—</strong>.
            Enter it below to continue.
          </p>

          <!-- OTP input row -->
          <div style="display:flex;gap:8px;justify-content:center;margin-bottom:10px;" id="otpBoxRow">
            <?php for ($i = 0; $i < 6; $i++): ?>
              <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]"
                class="otp-digit form-control-qa"
                style="width:42px;height:48px;text-align:center;font-size:1.25rem;
                        font-weight:700;padding:0;border-radius:8px;">
            <?php endfor; ?>
          </div>

          <div class="invalid-feedback-qa" id="errOtpCode"
            style="text-align:center;font-size:.77rem;"></div>

          <p style="font-size:.76rem;color:var(--text-secondary,#6b6860);margin:14px 0 0;text-align:center;">
            Didn't receive it?
            <button type="button" id="btnResendCode"
              style="background:none;border:none;padding:0;font-size:.76rem;
                         color:var(--primary,#2d5a3d);text-decoration:underline;cursor:pointer;">
              Resend code
            </button>
            <span id="resendCountdown" style="font-size:.76rem;color:var(--text-secondary,#6b6860);display:none;"></span>
          </p>
        </div>

        <div class="modal-footer" style="border-top:1px solid var(--border-light,#f0ede6);
                                       padding:14px 24px;gap:8px;">
          <button type="button" id="btnCancelCode"
            style="padding:7px 18px;border-radius:7px;font-size:.84rem;font-weight:600;
                       border:1.5px solid var(--border,#e2ddd4);background:#fff;
                       color:var(--text-secondary,#6b6860);cursor:pointer;">
            <i class="fa-solid fa-xmark me-1"></i> Cancel
          </button>
          <button type="button" id="btnVerifyCode"
            style="padding:7px 20px;border-radius:7px;font-size:.84rem;font-weight:600;
                       background:var(--primary,#2d5a3d);color:#fff;border:none;cursor:pointer;">
            <i class="fa-solid fa-shield-check me-1"></i> Verify Code
          </button>
        </div>

      </div>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════════════════════════
     MODAL 3 — Change password form (shown only after OTP verified)
═══════════════════════════════════════════════════════════════ -->
  <div class="modal fade" id="modalChangePassword" tabindex="-1" aria-labelledby="modalChangePwdLabel"
    aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
      <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 8px 32px rgba(0,0,0,.14);">

        <div class="modal-header" style="border-bottom:1px solid var(--border-light,#f0ede6);padding:20px 24px 16px;">
          <div style="display:flex;align-items:center;gap:10px;">
            <span style="width:34px;height:34px;border-radius:50%;background:rgba(39,174,96,.1);
                       display:flex;align-items:center;justify-content:center;">
              <i class="fa-solid fa-lock-open" style="color:#27ae60;font-size:.9rem;"></i>
            </span>
            <h5 class="modal-title mb-0" id="modalChangePwdLabel"
              style="font-size:.97rem;font-weight:700;letter-spacing:-.3px;">
              Change Password
            </h5>
          </div>
          <div style="font-size:.72rem;color:#27ae60;display:flex;align-items:center;gap:4px;
                    background:rgba(39,174,96,.08);padding:3px 9px;border-radius:20px;margin-left:auto;margin-right:8px;">
            <i class="fa-solid fa-circle-check" style="font-size:.7rem;"></i> Identity verified
          </div>
        </div>

        <div class="modal-body" style="padding:22px 24px;">
          <form id="pwdForm" novalidate>

            <div class="mb-3">
              <label class="form-label-qa" for="currentPwd">Current Password</label>
              <div style="position:relative;">
                <input type="password" class="form-control-qa" id="currentPwd"
                  placeholder="Enter current password" style="padding-right:40px;">
                <button type="button" class="pwd-toggle-btn" data-target="currentPwd"
                  style="position:absolute;right:10px;top:50%;transform:translateY(-50%);
                             background:none;border:none;color:var(--text-secondary);cursor:pointer;">
                  <i class="fa-regular fa-eye" data-eye="currentPwd"></i>
                </button>
              </div>
              <div class="invalid-feedback-qa" id="errCurrentPwd"></div>
            </div>

            <div class="mb-3">
              <label class="form-label-qa" for="newPwd">New Password</label>
              <div style="position:relative;">
                <input type="password" class="form-control-qa" id="newPwd"
                  placeholder="Min. 8 characters" style="padding-right:40px;">
                <button type="button" class="pwd-toggle-btn" data-target="newPwd"
                  style="position:absolute;right:10px;top:50%;transform:translateY(-50%);
                             background:none;border:none;color:var(--text-secondary);cursor:pointer;">
                  <i class="fa-regular fa-eye" data-eye="newPwd"></i>
                </button>
              </div>
              <div class="strength-bar">
                <div class="strength-fill" id="strengthFill"></div>
              </div>
              <div class="strength-text" id="strengthText"></div>
              <div class="invalid-feedback-qa" id="errNewPwd"></div>
            </div>

            <div class="mb-3">
              <label class="form-label-qa" for="confirmPwd">Confirm New Password</label>
              <div style="position:relative;">
                <input type="password" class="form-control-qa" id="confirmPwd"
                  placeholder="Repeat new password" style="padding-right:40px;">
                <button type="button" class="pwd-toggle-btn" data-target="confirmPwd"
                  style="position:absolute;right:10px;top:50%;transform:translateY(-50%);
                             background:none;border:none;color:var(--text-secondary);cursor:pointer;">
                  <i class="fa-regular fa-eye" data-eye="confirmPwd"></i>
                </button>
              </div>
              <div class="invalid-feedback-qa" id="errConfirmPwd"></div>
            </div>

          </form>
        </div>

        <div class="modal-footer" style="border-top:1px solid var(--border-light,#f0ede6);
                                       padding:14px 24px;gap:8px;">
          <button type="button" id="btnCancelPwd"
            style="padding:7px 18px;border-radius:7px;font-size:.84rem;font-weight:600;
                       border:1.5px solid var(--border,#e2ddd4);background:#fff;
                       color:var(--text-secondary,#6b6860);cursor:pointer;">
            <i class="fa-solid fa-xmark me-1"></i> Cancel
          </button>
          <button type="submit" form="pwdForm" class="btn-primary-qa" id="btnChangePwd"
            style="padding:7px 20px;">
            <i class="fa-solid fa-lock me-1"></i> Update Password
          </button>
        </div>

      </div>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════════════════════════
     MODAL 4 — Email change OTP (sent to the OLD email address)
═══════════════════════════════════════════════════════════════ -->
  <div class="modal fade" id="modalEmailChangeOtp" tabindex="-1" aria-labelledby="modalEmailChangeOtpLabel"
    aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
      <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 8px 32px rgba(0,0,0,.14);">

        <div class="modal-header" style="border-bottom:1px solid var(--border-light,#f0ede6);padding:20px 24px 16px;">
          <div style="display:flex;align-items:center;gap:10px;">
            <span style="width:34px;height:34px;border-radius:50%;background:rgba(30,136,229,.1);
                       display:flex;align-items:center;justify-content:center;">
              <i class="fa-solid fa-envelope-circle-check" style="color:#1e88e5;font-size:.9rem;"></i>
            </span>
            <h5 class="modal-title mb-0" id="modalEmailChangeOtpLabel"
              style="font-size:.97rem;font-weight:700;letter-spacing:-.3px;">
              Verify Email Change
            </h5>
          </div>
        </div>

        <div class="modal-body" style="padding:22px 24px;">

          <!-- Info notice about where the code went -->
          <div style="background:#e8f5fe;border:1px solid #90caf9;border-radius:8px;
                      padding:12px 14px;margin-bottom:18px;font-size:.83rem;color:#1565c0;">
            <i class="fa-solid fa-circle-info me-1"></i>
            A 6-digit code was sent to your <strong>current</strong> email address
            (<span id="emailOtpSentTo" style="font-weight:700;">—</span>).
            Enter it below to confirm the change.
          </div>

          <!-- New address being set (confirmation) -->
          <p style="font-size:.82rem;color:var(--text-secondary,#6b6860);margin:0 0 14px;">
            New address:
            <strong id="emailOtpNewAddr" style="color:var(--text-primary,#1a1a18);word-break:break-all;">—</strong>
          </p>

          <!-- OTP boxes -->
          <div style="display:flex;gap:8px;justify-content:center;margin-bottom:10px;" id="emailOtpBoxRow">
            <?php for ($i = 0; $i < 6; $i++): ?>
              <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]"
                class="email-otp-digit form-control-qa"
                style="width:42px;height:48px;text-align:center;font-size:1.25rem;
                        font-weight:700;padding:0;border-radius:8px;">
            <?php endfor; ?>
          </div>

          <div class="invalid-feedback-qa" id="errEmailOtpCode"
            style="text-align:center;font-size:.77rem;"></div>

          <p style="font-size:.76rem;color:var(--text-secondary,#6b6860);margin:14px 0 0;text-align:center;">
            Didn't receive it?
            <button type="button" id="btnResendEmailCode"
              style="background:none;border:none;padding:0;font-size:.76rem;
                         color:var(--primary,#2d5a3d);text-decoration:underline;cursor:pointer;">
              Resend code
            </button>
            <span id="emailResendCountdown"
              style="font-size:.76rem;color:var(--text-secondary,#6b6860);display:none;"></span>
          </p>
        </div>

        <div class="modal-footer" style="border-top:1px solid var(--border-light,#f0ede6);
                                       padding:14px 24px;gap:8px;">
          <button type="button" id="btnCancelEmailOtp"
            style="padding:7px 18px;border-radius:7px;font-size:.84rem;font-weight:600;
                       border:1.5px solid var(--border,#e2ddd4);background:#fff;
                       color:var(--text-secondary,#6b6860);cursor:pointer;">
            <i class="fa-solid fa-xmark me-1"></i> Cancel
          </button>
          <button type="button" id="btnVerifyEmailOtp"
            style="padding:7px 20px;border-radius:7px;font-size:.84rem;font-weight:600;
                       background:#1e88e5;color:#fff;border:none;cursor:pointer;">
            <i class="fa-solid fa-shield-check me-1"></i> Confirm Change
          </button>
        </div>

      </div>
    </div>
  </div>

  <!-- Toast container (app.js expects this id) -->
  <div id="toast-container"></div>

  <!-- ── Scripts ─────────────────────────────────────────────── -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/app.js"></script>

  <script>
    $(function() {

      const API = '../../backend/api/profile_api.php';

      /* ── Utilities ──────────────────────────────────────────── */
      function esc(str) {
        return String(str ?? '')
          .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
      }

      function initials(name) {
        return String(name || '?')
          .split(' ').map(w => w[0] || '').join('').slice(0, 2).toUpperCase();
      }

      function formatDate(str) {
        if (!str) return '—';
        return new Date(str).toLocaleDateString('en-PH', {
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        });
      }

      function roleLabel(role) {
        return String(role || '').replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
      }

      function setFieldError(inputId, errId, msg) {
        const $f = $('#' + inputId);
        const $e = $('#' + errId);
        if (msg) {
          $f.addClass('is-invalid');
          $e.text(msg).addClass('show');
        } else {
          $f.removeClass('is-invalid');
          $e.text('').removeClass('show');
        }
      }

      function clearErrors(pairs) {
        pairs.forEach(([f, e]) => setFieldError(f, e, ''));
      }

      /* ── OTP box wiring (shared) ────────────────────────────── */
      function wireOtpBoxes(selector) {
        $(document).on('input', selector, function() {
          this.value = this.value.replace(/\D/, '');
          if (this.value && this.nextElementSibling?.classList.contains(selector.replace('.', ''))) {
            this.nextElementSibling.focus();
          }
        });

        $(document).on('keydown', selector, function(e) {
          if (e.key === 'Backspace' && !this.value &&
            this.previousElementSibling?.classList.contains(selector.replace('.', ''))) {
            this.previousElementSibling.focus();
          }
          if ((e.ctrlKey || e.metaKey) && e.key === 'v') return;
        });

        $(document).on('paste', selector, function(e) {
          e.preventDefault();
          const pasted = (e.originalEvent.clipboardData || window.clipboardData)
            .getData('text').replace(/\D/g, '').slice(0, 6);
          const boxes = document.querySelectorAll(selector);
          pasted.split('').forEach((ch, i) => { if (boxes[i]) boxes[i].value = ch; });
          if (pasted.length >= 6 && boxes[5]) boxes[5].focus();
        });
      }

      wireOtpBoxes('.otp-digit');
      wireOtpBoxes('.email-otp-digit');

      /* ── Load & render profile ──────────────────────────────── */
      function loadProfile() {
        $.ajax({ url: API, type: 'GET', data: { action: 'get' }, dataType: 'json' })
          .done(function(res) {
            if (!res.success) { toast.error(res.message || 'Failed to load profile.'); return; }
            renderProfile(res.data);
          })
          .fail(function() { toast.error('Network error loading profile.'); });
      }

      function renderProfile(u) {
        const act = u.activity || {};

        // ── Hero ──
        $('#heroAvatar').text(initials(u.full_name));
        $('#heroName').text(u.full_name);
        $('#heroEmail').text(u.email);

        $('#heroBadges').html(`
          <span class="badge-hero">
            <i class="fa-solid fa-shield-halved" style="font-size:.62rem;"></i>
            ${esc(roleLabel(u.role))}
          </span>
          <span class="badge-hero">
            <i class="fa-solid fa-circle${u.is_active ? '-check' : '-xmark'}" style="font-size:.62rem;"></i>
            ${u.is_active ? 'Active' : 'Inactive'}
          </span>
        `);

        $('#heroStats').html(`
          <div class="hero-stat-box">
            <div class="stat-n">${esc(act.surveys_created ?? 0)}</div>
            <div class="stat-lbl">Surveys</div>
          </div>
          <div class="hero-stat-box">
            <div class="stat-n">${esc(act.reports_generated ?? 0)}</div>
            <div class="stat-lbl">Reports</div>
          </div>
        `);

        // ── Form fields ──
        $('#usernameField').val(u.username);
        $('#fullName').val(u.full_name);
        $('#roleField').val(roleLabel(u.role));
        $('#btnSaveInfo').prop('disabled', false);

        // ── Email field (read-only, sourced from DB) ──
        const emailVal = u.gmail_connected ? u.gmail_address : (u.email || '');
        const $hint = $('#emailFieldHint');
        $('#emailField').val(emailVal || '');

        if (u.gmail_connected) {
          $hint.html(`
            <i class="fa-solid fa-envelope-circle-check" style="color:#27ae60;"></i>
            Email address on file.
            <a href="#gmailCard" id="changeGmailLink">Change email</a>
          `).show();
          $hint.find('#changeGmailLink').on('click', function(e) {
            e.preventDefault();
            document.getElementById('gmailCard').scrollIntoView({ behavior: 'smooth' });
            $('#btnReconnectGmail').trigger('click');
          });
        } else {
          $hint.html(`
            <i class="fa-solid fa-triangle-exclamation" style="color:#e67e22;"></i>
            No email set. <a href="#gmailCard" id="connectGmailLink">Add an email address</a> to enable password resets.
          `).show();
          $hint.find('#connectGmailLink').on('click', function(e) {
            e.preventDefault();
            document.getElementById('gmailCard').scrollIntoView({ behavior: 'smooth' });
          });
        }

        // ── Account details ──
        const statusBadge = u.is_active
          ? '<span class="badge-qa active"><i class="fa-solid fa-check me-1"></i>Active</span>'
          : '<span class="badge-qa cancelled"><i class="fa-solid fa-xmark me-1"></i>Inactive</span>';

        $('#metaList').html(`
          <li>
            <span class="meta-key"><i class="fa-solid fa-fingerprint me-2" style="color:var(--text-muted,#aaa);"></i>User ID</span>
            <span class="meta-val">#${esc(u.user_id)}</span>
          </li>
          <li>
            <span class="meta-key"><i class="fa-solid fa-circle-check me-2" style="color:var(--text-muted,#aaa);"></i>Account Status</span>
            <span class="meta-val">${statusBadge}</span>
          </li>
          <li>
            <span class="meta-key"><i class="fa-regular fa-calendar me-2" style="color:var(--text-muted,#aaa);"></i>Member Since</span>
            <span class="meta-val">${formatDate(u.created_at)}</span>
          </li>
          <li>
            <span class="meta-key"><i class="fa-solid fa-paper-plane me-2" style="color:var(--text-muted,#aaa);"></i>Surveys Created</span>
            <span class="meta-val">${esc(act.surveys_created ?? 0)}</span>
          </li>
          <li>
            <span class="meta-key"><i class="fa-solid fa-file-lines me-2" style="color:var(--text-muted,#aaa);"></i>Reports Generated</span>
            <span class="meta-val">${esc(act.reports_generated ?? 0)}</span>
          </li>
        `);

        // ── Email status banner ──
        renderGmailStatus(u.gmail_connected, u.gmail_address);
      }

      /* ── Email status banner ────────────────────────────────── */
      function renderGmailStatus(connected, address) {
        if (connected) {
          $('#gmailStatusBanner').html(`
            <div class="gmail-status-banner connected">
              <span class="gmail-status-icon"><i class="fa-solid fa-envelope-circle-check"></i></span>
              <div class="gmail-status-text">
                <strong>Email address set</strong>
                <small>${esc(address)} — password reset codes and notifications will be sent here</small>
              </div>
              <button type="button" class="gmail-toggle-link" id="btnReconnectGmail">
                Change email
              </button>
            </div>
          `);
          $('#gmailForm').hide();
          $('#gmailUsername').val(address);
          bindReconnect();
        } else {
          $('#gmailStatusBanner').html(`
            <div class="gmail-status-banner disconnected">
              <span class="gmail-status-icon"><i class="fa-regular fa-envelope"></i></span>
              <div class="gmail-status-text">
                <strong>No email address set</strong>
                <small>Add a real email address to receive password reset codes and system notifications.</small>
              </div>
            </div>
          `);
          $('#gmailForm').show();
          $('#btnCancelGmail').hide();
        }
      }

      function bindReconnect() {
        $('#btnReconnectGmail').off('click').on('click', function() {
          $('#gmailForm').slideDown(160);
          $('#btnCancelGmail').show();
          $(this).closest('.gmail-status-banner').find('.gmail-toggle-link').hide();
        });
      }

      /* ── Cancel email update ────────────────────────────────── */
      $('#btnCancelGmail').on('click', function() {
        $('#gmailForm').slideUp(160);
        bindReconnect();
        $('#gmailStatusBanner .gmail-toggle-link').show();
        $(this).hide();
      });

      /* ══════════════════════════════════════════════════════════
         EMAIL CHANGE — Step 1: submit form → send OTP to OLD email
      ══════════════════════════════════════════════════════════ */
      let _pendingNewEmail = '';
      let _emailResendTimer = null;

      $('#gmailForm').on('submit', function(e) {
        e.preventDefault();
        clearErrors([['gmailUsername', 'errGmailUsername']]);

        const newEmail = $('#gmailUsername').val().trim();
        const btn = document.getElementById('btnConnectGmail');
        btnLoading(btn, 'Sending…');

        $.ajax({
          url: API,
          type: 'POST',
          contentType: 'application/json',
          dataType: 'json',
          data: JSON.stringify({ action: 'send_email_change_code', new_email: newEmail }),
        })
        .done(function(res) {
          if (res.success) {
            if (res.data?.direct) {
              // First-time setup — no old email, saved directly
              const saved = res.data.gmail_address || newEmail;
              toast.success(res.message || 'Email address saved successfully!', 'Saved');
              renderGmailStatus(true, saved);
              syncEmailField(saved);
            } else {
              // OTP was sent to old email — open OTP modal
              _pendingNewEmail = newEmail;
              const sentTo = res.data?.sent_to || '—';
              openEmailOtpModal(sentTo, newEmail);
            }
          } else {
            const errs = res.data?.errors || {};
            if (errs.gmail_username) {
              setFieldError('gmailUsername', 'errGmailUsername', errs.gmail_username);
            } else if (res.data?.warn) {
              toast.warning ? toast.warning(res.message) : toast.error('⚠️ ' + res.message);
            } else {
              toast.error(res.message || 'Failed to send verification code. Please try again.');
            }
          }
        })
        .fail(function() { toast.error('Network error. Please try again.'); })
        .always(function() { btnReset(btn); });
      });

      /* ── Open email OTP modal ───────────────────────────────── */
      function openEmailOtpModal(sentTo, newAddr) {
        $('#emailOtpBoxRow .email-otp-digit').val('').removeClass('is-invalid');
        $('#errEmailOtpCode').text('').removeClass('show');
        $('#emailOtpSentTo').text(sentTo);
        $('#emailOtpNewAddr').text(newAddr);
        startEmailResendCooldown(30);
        new bootstrap.Modal(document.getElementById('modalEmailChangeOtp')).show();
        document.getElementById('modalEmailChangeOtp').addEventListener('shown.bs.modal', function focusFirst() {
          document.querySelectorAll('.email-otp-digit')[0]?.focus();
          this.removeEventListener('shown.bs.modal', focusFirst);
        });
      }

      /* ── Email OTP resend cooldown ──────────────────────────── */
      function startEmailResendCooldown(seconds) {
        clearInterval(_emailResendTimer);
        $('#btnResendEmailCode').hide();
        $('#emailResendCountdown').text('(' + seconds + 's)').show();
        let left = seconds;
        _emailResendTimer = setInterval(function() {
          left--;
          if (left <= 0) {
            clearInterval(_emailResendTimer);
            $('#emailResendCountdown').hide();
            $('#btnResendEmailCode').show();
          } else {
            $('#emailResendCountdown').text('(' + left + 's)');
          }
        }, 1000);
      }

      /* ── Resend email change code ───────────────────────────── */
      $('#btnResendEmailCode').on('click', function() {
        const btn = this;
        btnLoading(btn, 'Sending…');
        $.ajax({
          url: API,
          type: 'POST',
          contentType: 'application/json',
          dataType: 'json',
          data: JSON.stringify({ action: 'send_email_change_code', new_email: _pendingNewEmail }),
        })
        .done(function(res) {
          if (res.success && !res.data?.direct) {
            toast.success('A new code has been sent.', 'Sent');
            $('#emailOtpBoxRow .email-otp-digit').val('').first().focus();
            $('#errEmailOtpCode').text('').removeClass('show');
            startEmailResendCooldown(30);
          } else {
            toast.error(res.message || 'Could not resend code.');
          }
        })
        .fail(function() { toast.error('Network error.'); })
        .always(function() { btnReset(btn); });
      });

      /* ── Cancel email OTP modal ─────────────────────────────── */
      $('#btnCancelEmailOtp').on('click', function() {
        clearInterval(_emailResendTimer);
        bootstrap.Modal.getInstance(document.getElementById('modalEmailChangeOtp')).hide();
      });

      /* ══════════════════════════════════════════════════════════
         EMAIL CHANGE — Step 2: verify OTP → commit new email
      ══════════════════════════════════════════════════════════ */
      $('#btnVerifyEmailOtp').on('click', function() {
        const code = Array.from(document.querySelectorAll('.email-otp-digit'))
          .map(i => i.value).join('');

        if (code.length < 6) {
          $('#errEmailOtpCode').text('Please enter all 6 digits.').addClass('show');
          document.querySelectorAll('.email-otp-digit')[code.length]?.focus();
          return;
        }

        const btn = this;
        btnLoading(btn, 'Verifying…');
        $('#errEmailOtpCode').text('').removeClass('show');

        $.ajax({
          url: API,
          type: 'POST',
          contentType: 'application/json',
          dataType: 'json',
          data: JSON.stringify({ action: 'verify_email_change_code', code: code }),
        })
        .done(function(res) {
          if (res.success) {
            clearInterval(_emailResendTimer);
            bootstrap.Modal.getInstance(document.getElementById('modalEmailChangeOtp')).hide();
            const saved = res.data?.gmail_address || _pendingNewEmail;
            toast.success(res.message || 'Email address updated successfully!', 'Updated');
            renderGmailStatus(true, saved);
            syncEmailField(saved);
          } else {
            const msg = res.data?.errors?.code || res.message || 'Incorrect code.';
            $('#errEmailOtpCode').text(msg).addClass('show');
            $('#emailOtpBoxRow').addClass('otp-shake');
            setTimeout(() => $('#emailOtpBoxRow').removeClass('otp-shake'), 500);
          }
        })
        .fail(function() { toast.error('Network error.'); })
        .always(function() { btnReset(btn); });
      });

      /* ── Helper: sync the read-only email field after a save ── */
      function syncEmailField(newAddress) {
        $('#emailField').val(newAddress);
        $('#heroEmail').text(newAddress);
        $('#emailFieldHint').html(`
          <i class="fa-solid fa-envelope-circle-check" style="color:#27ae60;"></i>
          Email address on file.
          <a href="#gmailCard" id="changeGmailLink">Change email</a>
        `).show();
        $('#emailFieldHint #changeGmailLink').off('click').on('click', function(e) {
          e.preventDefault();
          document.getElementById('gmailCard').scrollIntoView({ behavior: 'smooth' });
          $('#btnReconnectGmail').trigger('click');
        });
      }

      /* ── Save profile info ──────────────────────────────────── */
      $('#infoForm').on('submit', function(e) {
        e.preventDefault();
        clearErrors([['fullName', 'errFullName']]);

        const btn = document.getElementById('btnSaveInfo');
        btnLoading(btn, 'Saving…');

        $.ajax({
          url: API,
          type: 'POST',
          contentType: 'application/json',
          dataType: 'json',
          data: JSON.stringify({
            action: 'update_info',
            full_name: $('#fullName').val().trim(),
            email: $('#emailField').val().trim(),
          }),
        })
        .done(function(res) {
          if (res.success) {
            toast.success('Profile updated successfully.', 'Saved');
            loadProfile();
          } else {
            const errs = res.data?.errors || {};
            if (errs.full_name) setFieldError('fullName', 'errFullName', errs.full_name);
            if (!errs.full_name) toast.error(res.message || 'Update failed.');
          }
        })
        .fail(function() { toast.error('Network error. Please try again.'); })
        .always(function() { btnReset(btn); });
      });

      /* ── Password strength meter ────────────────────────────── */
      $('#newPwd').on('input', function() {
        const val = this.value;
        let score = 0;
        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const map = [
          { w: '0%',   bg: 'transparent', lbl: '' },
          { w: '25%',  bg: '#c0392b',     lbl: 'Weak' },
          { w: '50%',  bg: '#e67e22',     lbl: 'Fair' },
          { w: '75%',  bg: '#f1c40f',     lbl: 'Good' },
          { w: '100%', bg: '#27ae60',     lbl: 'Strong' },
        ];
        const m = map[score];
        $('#strengthFill').css({ width: m.w, background: m.bg });
        $('#strengthText').text(m.lbl);
      });

      /* ── Password-field eye-toggle (modal 3) ────────────────── */
      $(document).on('click', '.pwd-toggle-btn', function() {
        const targetId = $(this).data('target');
        const $input = $('#' + targetId);
        const $icon = $(this).find('i');
        if ($input.attr('type') === 'password') {
          $input.attr('type', 'text');
          $icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
          $input.attr('type', 'password');
          $icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
      });

      /* ══════════════════════════════════════════════════════════
         PASSWORD CHANGE — Step 1: open confirmation modal
      ══════════════════════════════════════════════════════════ */
      let _verifiedEmail = '';

      $('#btnOpenChangePwd').on('click', function() {
        const email = $('#emailField').val().trim();
        if (!email) {
          toast.error('Please set an email address first before changing your password.');
          return;
        }
        _verifiedEmail = email;
        $('#confirmCodeEmail').text(email);
        new bootstrap.Modal(document.getElementById('modalConfirmCode')).show();
      });

      /* ══════════════════════════════════════════════════════════
         PASSWORD CHANGE — Step 2a: send code
      ══════════════════════════════════════════════════════════ */
      let _resendTimer = null;

      $('#btnYesSendCode').on('click', function() {
        const btn = this;
        btnLoading(btn, 'Sending…');

        $.ajax({
          url: API,
          type: 'POST',
          contentType: 'application/json',
          dataType: 'json',
          data: JSON.stringify({ action: 'send_verification_code' }),
        })
        .done(function(res) {
          if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalConfirmCode')).hide();
            const email = res.data?.email || _verifiedEmail;
            _verifiedEmail = email;
            openEnterCodeModal(email);
          } else {
            toast.error(res.message || 'Failed to send code. Please try again.');
          }
        })
        .fail(function() { toast.error('Network error. Please try again.'); })
        .always(function() { btnReset(btn); });
      });

      function openEnterCodeModal(email) {
        $('#otpBoxRow .otp-digit').val('').removeClass('is-invalid');
        $('#errOtpCode').text('').removeClass('show');
        $('#enterCodeEmail').text(email);
        startResendCooldown(30);
        new bootstrap.Modal(document.getElementById('modalEnterCode')).show();
        document.getElementById('modalEnterCode').addEventListener('shown.bs.modal', function focusFirst() {
          document.querySelectorAll('.otp-digit')[0]?.focus();
          this.removeEventListener('shown.bs.modal', focusFirst);
        });
      }

      /* ── Resend cooldown (password change) ──────────────────── */
      function startResendCooldown(seconds) {
        clearInterval(_resendTimer);
        $('#btnResendCode').hide();
        $('#resendCountdown').text('(' + seconds + 's)').show();
        let left = seconds;
        _resendTimer = setInterval(function() {
          left--;
          if (left <= 0) {
            clearInterval(_resendTimer);
            $('#resendCountdown').hide();
            $('#btnResendCode').show();
          } else {
            $('#resendCountdown').text('(' + left + 's)');
          }
        }, 1000);
      }

      $('#btnResendCode').on('click', function() {
        const btn = this;
        btnLoading(btn, 'Sending…');
        $.ajax({
          url: API,
          type: 'POST',
          contentType: 'application/json',
          dataType: 'json',
          data: JSON.stringify({ action: 'send_verification_code' }),
        })
        .done(function(res) {
          if (res.success) {
            toast.success('A new code has been sent.', 'Sent');
            $('#otpBoxRow .otp-digit').val('').first().focus();
            $('#errOtpCode').text('').removeClass('show');
            startResendCooldown(30);
          } else {
            toast.error(res.message || 'Could not resend code.');
          }
        })
        .fail(function() { toast.error('Network error.'); })
        .always(function() { btnReset(btn); });
      });

      /* ── Cancel enter-code modal ────────────────────────────── */
      $('#btnCancelCode').on('click', function() {
        clearInterval(_resendTimer);
        bootstrap.Modal.getInstance(document.getElementById('modalEnterCode')).hide();
      });

      /* ══════════════════════════════════════════════════════════
         PASSWORD CHANGE — Step 2b: verify OTP
      ══════════════════════════════════════════════════════════ */
      $('#btnVerifyCode').on('click', function() {
        const code = Array.from(document.querySelectorAll('.otp-digit'))
          .map(i => i.value).join('');

        if (code.length < 6) {
          $('#errOtpCode').text('Please enter all 6 digits.').addClass('show');
          document.querySelectorAll('.otp-digit')[code.length]?.focus();
          return;
        }

        const btn = this;
        btnLoading(btn, 'Verifying…');
        $('#errOtpCode').text('').removeClass('show');

        $.ajax({
          url: API,
          type: 'POST',
          contentType: 'application/json',
          dataType: 'json',
          data: JSON.stringify({ action: 'verify_code', code: code }),
        })
        .done(function(res) {
          if (res.success) {
            clearInterval(_resendTimer);
            bootstrap.Modal.getInstance(document.getElementById('modalEnterCode')).hide();
            $('#pwdForm')[0].reset();
            $('#strengthFill').css({ width: '0%' });
            $('#strengthText').text('');
            clearErrors([
              ['currentPwd', 'errCurrentPwd'],
              ['newPwd', 'errNewPwd'],
              ['confirmPwd', 'errConfirmPwd'],
            ]);
            new bootstrap.Modal(document.getElementById('modalChangePassword')).show();
          } else {
            const msg = res.data?.errors?.code || res.message || 'Incorrect code.';
            $('#errOtpCode').text(msg).addClass('show');
            $('#otpBoxRow').addClass('otp-shake');
            setTimeout(() => $('#otpBoxRow').removeClass('otp-shake'), 500);
          }
        })
        .fail(function() { toast.error('Network error.'); })
        .always(function() { btnReset(btn); });
      });

      /* ══════════════════════════════════════════════════════════
         PASSWORD CHANGE — Step 3: submit new password
      ══════════════════════════════════════════════════════════ */
      $('#btnCancelPwd').on('click', function() {
        bootstrap.Modal.getInstance(document.getElementById('modalChangePassword')).hide();
      });

      $('#pwdForm').on('submit', function(e) {
        e.preventDefault();
        clearErrors([
          ['currentPwd', 'errCurrentPwd'],
          ['newPwd', 'errNewPwd'],
          ['confirmPwd', 'errConfirmPwd'],
        ]);

        const btn = document.getElementById('btnChangePwd');
        btnLoading(btn, 'Updating…');

        $.ajax({
          url: API,
          type: 'POST',
          contentType: 'application/json',
          dataType: 'json',
          data: JSON.stringify({
            action: 'change_password',
            current_password: $('#currentPwd').val(),
            new_password: $('#newPwd').val(),
            confirm_password: $('#confirmPwd').val(),
          }),
        })
        .done(function(res) {
          if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalChangePassword')).hide();
            toast.success('Password changed successfully.', 'Updated');
            $('#pwdForm')[0].reset();
            $('#strengthFill').css({ width: '0%' });
            $('#strengthText').text('');
          } else {
            const errs = res.data?.errors || {};
            if (errs.current_password) setFieldError('currentPwd', 'errCurrentPwd', errs.current_password);
            if (errs.new_password)     setFieldError('newPwd',     'errNewPwd',      errs.new_password);
            if (errs.confirm_password) setFieldError('confirmPwd', 'errConfirmPwd',  errs.confirm_password);
            if (!Object.keys(errs).length) toast.error(res.message || 'Password change failed.');
          }
        })
        .fail(function() { toast.error('Network error. Please try again.'); })
        .always(function() { btnReset(btn); });
      });

      /* ── Init ───────────────────────────────────────────────── */
      loadProfile();

    });
  </script>

</body>

</html>