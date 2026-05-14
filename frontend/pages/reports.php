<?php
/**
 * Reports Dashboard Page
 * Quality Assurance Management System
 * frontend/pages/reports.php
 */

session_start();

if (empty($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Reports';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="x-api-key" content="<?= htmlspecialchars(getenv('APP_API_KEY'), ENT_QUOTES, 'UTF-8') ?>">

  <title><?= htmlspecialchars($pageTitle) ?> — QA System</title>

  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>

<div class="qa-wrapper">

  <!-- ── Sidebar ────────────────────────────────────────────── -->
  <?php include '../partials/sidebar.php'; ?>

  <!-- ── Main content ──────────────────────────────────────── -->
  <div class="qa-content">

    <?php include '../partials/header.php'; ?>

    <main class="qa-page">

      <!-- ── Page header ─────────────────────────────────────── -->
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h2 class="mb-0" style="font-size:1.25rem;font-weight:700;letter-spacing:-.4px;">
            Reports Dashboard
          </h2>
          <p class="text-muted-qa mb-0" style="font-size:.83rem;margin-top:2px;">
            Aggregated view of all quality assurance activities
          </p>
        </div>
        <div class="d-flex gap-2">
          <button class="btn-outline-qa" id="refresh-btn">
            <i class="fa-solid fa-rotate-right"></i> Refresh
          </button>
          <button class="btn-primary-qa" id="open-export-modal-btn">
            <i class="fa-solid fa-file-export"></i> Export
          </button>
        </div>
      </div>

      <!-- ── Section tabs ─────────────────────────────────────── -->
      <div class="mb-4" style="border-bottom:1px solid var(--border);">
        <nav class="d-flex gap-3" style="padding-bottom:0;" id="report-tabs">
          <?php
          $tabs = [
            ['key' => 'summary',   'label' => 'Overview'],
            ['key' => 'audits',    'label' => 'Audits'],
            ['key' => 'tasks',     'label' => 'Tasks'],
            ['key' => 'kpis',      'label' => 'KPIs'],
            ['key' => 'surveys',   'label' => 'Surveys'],
            ['key' => 'plans',     'label' => 'Action Plans'],
            ['key' => 'standards', 'label' => 'Standards'],
          ];
          foreach ($tabs as $i => $tab):
          ?>
          <button class="report-tab-btn<?= $i === 0 ? ' active' : '' ?>"
                  data-tab="<?= $tab['key'] ?>"
                  style="background:none;border:none;padding:8px 0;margin-bottom:-1px;cursor:pointer;
                         font-family:var(--font);font-size:.88rem;
                         <?= $i === 0
                             ? 'font-weight:600;color:var(--primary);border-bottom:2px solid var(--primary);'
                             : 'font-weight:500;color:var(--text-secondary);border-bottom:2px solid transparent;' ?>">
            <?= $tab['label'] ?>
          </button>
          <?php endforeach; ?>
        </nav>
      </div>


      <!-- ══════════════════════════════════════════════════════
           PAGE-LEVEL DATE FILTER BAR
      ══════════════════════════════════════════════════════ -->
      <div id="page-filter-bar"
           style="background:#f8f9fa;border:1px solid var(--border);border-radius:10px;
                  padding:12px 16px;margin-bottom:18px;">

        <div style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:12px;">

          <!-- From -->
          <div style="display:flex;flex-direction:column;gap:4px;">
            <label for="filter-date-from"
                   style="font-size:.72rem;font-weight:600;color:var(--text-secondary);
                          text-transform:uppercase;letter-spacing:.4px;">From</label>
            <input type="date" id="filter-date-from"
                   style="padding:7px 10px;border:1px solid var(--border);border-radius:7px;
                          font-family:var(--font);font-size:.84rem;color:var(--text-primary);
                          background:#fff;outline:none;min-width:148px;">
          </div>

          <!-- Arrow -->
          <div style="padding-bottom:8px;color:var(--text-muted);font-size:.9rem;">&#8594;</div>

          <!-- To -->
          <div style="display:flex;flex-direction:column;gap:4px;">
            <label for="filter-date-to"
                   style="font-size:.72rem;font-weight:600;color:var(--text-secondary);
                          text-transform:uppercase;letter-spacing:.4px;">To</label>
            <input type="date" id="filter-date-to"
                   style="padding:7px 10px;border:1px solid var(--border);border-radius:7px;
                          font-family:var(--font);font-size:.84rem;color:var(--text-primary);
                          background:#fff;outline:none;min-width:148px;">
          </div>

          <!-- Quick presets -->
          <div style="display:flex;flex-direction:column;gap:4px;">
            <label style="font-size:.72rem;font-weight:600;color:var(--text-secondary);
                          text-transform:uppercase;letter-spacing:.4px;">Quick Range</label>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
              <button class="filter-preset-btn" data-preset="this_month"
                      style="padding:6px 11px;border:1px solid var(--border);border-radius:6px;
                             background:#fff;font-family:var(--font);font-size:.76rem;
                             font-weight:500;color:var(--text-secondary);cursor:pointer;white-space:nowrap;">
                This Month
              </button>
              <button class="filter-preset-btn" data-preset="last_month"
                      style="padding:6px 11px;border:1px solid var(--border);border-radius:6px;
                             background:#fff;font-family:var(--font);font-size:.76rem;
                             font-weight:500;color:var(--text-secondary);cursor:pointer;white-space:nowrap;">
                Last Month
              </button>
              <button class="filter-preset-btn" data-preset="this_year"
                      style="padding:6px 11px;border:1px solid var(--border);border-radius:6px;
                             background:#fff;font-family:var(--font);font-size:.76rem;
                             font-weight:500;color:var(--text-secondary);cursor:pointer;white-space:nowrap;">
                This Year
              </button>
              <button class="filter-preset-btn" data-preset="last_year"
                      style="padding:6px 11px;border:1px solid var(--border);border-radius:6px;
                             background:#fff;font-family:var(--font);font-size:.76rem;
                             font-weight:500;color:var(--text-secondary);cursor:pointer;white-space:nowrap;">
                Last Year
              </button>
            </div>
          </div>

          <!-- Action buttons -->
          <div style="display:flex;gap:8px;align-items:flex-end;margin-left:auto;">
            <button id="filter-apply-btn" class="btn-primary-qa"
                    style="padding:7px 18px;font-size:.84rem;">
              <i class="fa-solid fa-filter me-1"></i> Apply Filter
            </button>
            <button id="filter-clear-btn" class="btn-outline-qa"
                    style="padding:7px 14px;font-size:.84rem;">
              <i class="fa-solid fa-xmark me-1"></i> Clear
            </button>
          </div>

        </div>

        <!-- Active filter pill (shown after apply) -->
        <div id="filter-active-pill"
             style="display:none;margin-top:10px;padding:8px 13px;
                    background:rgba(45,90,61,.08);border:1px solid rgba(45,90,61,.22);
                    border-radius:7px;font-size:.78rem;color:var(--primary);font-weight:500;">
          <div style="display:flex;align-items:center;gap:8px;">
            <i class="fa-solid fa-circle-check" style="font-size:.85rem;"></i>
            <span id="filter-active-text"></span>
            <button id="filter-pill-clear"
                    style="background:none;border:1px solid rgba(45,90,61,.3);cursor:pointer;
                           color:var(--primary);font-size:.73rem;margin-left:auto;
                           padding:3px 9px;border-radius:5px;font-family:var(--font);font-weight:600;">
              <i class="fa-solid fa-xmark me-1"></i>Remove filter
            </button>
          </div>
        </div>

      </div><!-- /#page-filter-bar -->

      <!-- ══════════════════════════════════════════════════════
           OVERVIEW SECTION
      ══════════════════════════════════════════════════════ -->
      <div id="section-summary" class="report-section">

        <!-- Stat cards — same pattern as dashboard.php -->
        <div class="row g-3 mb-4" id="summary-stats">
          <?php
          $statCards = [
            ['id'=>'stat-audits',    'icon'=>'fa-magnifying-glass',       'color'=>'blue',   'label'=>'Total Audits',     'sub'=>'Across all types'],
            ['id'=>'stat-tasks',     'icon'=>'fa-list-check',             'color'=>'purple', 'label'=>'Audit Tasks',      'sub'=>'Accreditation tasks'],
            ['id'=>'stat-kpi',       'icon'=>'fa-chart-line',             'color'=>'green',  'label'=>'KPI Avg Value',    'sub'=>'Latest records'],
            ['id'=>'stat-plans',     'icon'=>'fa-triangle-exclamation',   'color'=>'orange', 'label'=>'Open Action Plans','sub'=>'Pending resolution'],
            ['id'=>'stat-surveys',   'icon'=>'fa-paper-plane',            'color'=>'blue',   'label'=>'Surveys',          'sub'=>'Total created'],
            ['id'=>'stat-responses', 'icon'=>'fa-comments',               'color'=>'green',  'label'=>'Responses',        'sub'=>'Survey submissions'],
            ['id'=>'stat-standards', 'icon'=>'fa-clipboard-check',        'color'=>'purple', 'label'=>'Active Standards', 'sub'=>'CHED, ISO & more'],
            ['id'=>'stat-policies',  'icon'=>'fa-file-shield',            'color'=>'orange', 'label'=>'Active Policies',  'sub'=>'Linked to standards'],
          ];
          foreach ($statCards as $c):
          ?>
          <div class="col-6 col-md-3">
            <div class="stat-card">
              <div class="stat-icon <?= $c['color'] ?>"><i class="fa-solid <?= $c['icon'] ?>"></i></div>
              <div class="stat-label"><?= $c['label'] ?></div>
              <div class="stat-value" id="<?= $c['id'] ?>">
                <span class="placeholder-wave"><span class="placeholder col-4 bg-secondary rounded"></span></span>
              </div>
              <div class="stat-sub"><?= $c['sub'] ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Charts row -->
        <div class="row g-3 mb-4">
          <div class="col-12 col-lg-4">
            <div class="card h-100">
              <div class="card-header-custom">
                <h3 class="card-title">
                  <span class="me-2" style="width:10px;height:10px;background:var(--accent-blue);border-radius:50%;display:inline-block;"></span>
                  Audit Status
                </h3>
              </div>
              <div class="card-body-custom d-flex align-items-center justify-content-center" style="min-height:200px;">
                <canvas id="chart-audit-status" style="max-height:180px;"></canvas>
              </div>
            </div>
          </div>
          <div class="col-12 col-lg-4">
            <div class="card h-100">
              <div class="card-header-custom">
                <h3 class="card-title">
                  <span class="me-2" style="width:10px;height:10px;background:var(--accent-orange);border-radius:50%;display:inline-block;"></span>
                  Task Status
                </h3>
              </div>
              <div class="card-body-custom d-flex align-items-center justify-content-center" style="min-height:200px;">
                <canvas id="chart-task-status" style="max-height:180px;"></canvas>
              </div>
            </div>
          </div>
          <div class="col-12 col-lg-4">
            <div class="card h-100">
              <div class="card-header-custom">
                <h3 class="card-title">
                  <span class="me-2" style="width:10px;height:10px;background:var(--primary);border-radius:50%;display:inline-block;"></span>
                  Survey Status
                </h3>
              </div>
              <div class="card-body-custom d-flex align-items-center justify-content-center" style="min-height:200px;">
                <canvas id="chart-survey-status" style="max-height:180px;"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Audits & Recent Surveys side by side -->
        <div class="row g-3">
          <div class="col-12 col-lg-6">
            <div class="card">
              <div class="card-header-custom">
                <h3 class="card-title">
                  <span class="me-2" style="width:10px;height:10px;background:var(--accent-blue);border-radius:50%;display:inline-block;"></span>
                  Recent Audits
                </h3>
                <a href="audits.php" class="btn-outline-qa" style="font-size:.76rem;padding:5px 10px;">
                  View all <i class="fa-solid fa-chevron-right ms-1" style="font-size:.65rem;"></i>
                </a>
              </div>
              <div class="card-body-custom p-0" id="summary-recent-audits">
                <?php for ($i = 0; $i < 3; $i++): ?>
                <div class="p-3" style="border-bottom:1px solid var(--border-light);">
                  <div class="d-flex justify-content-between mb-1">
                    <span class="placeholder-wave" style="width:60%;"><span class="placeholder col-12 bg-secondary rounded" style="height:12px;display:block;"></span></span>
                    <span class="placeholder-wave" style="width:18%;"><span class="placeholder col-12 bg-secondary rounded" style="height:12px;display:block;"></span></span>
                  </div>
                  <div class="progress-bar-wrap mt-2"><div class="progress-bar-fill blue" style="width:45%;"></div></div>
                </div>
                <?php endfor; ?>
              </div>
            </div>
          </div>
          <div class="col-12 col-lg-6">
            <div class="card">
              <div class="card-header-custom">
                <h3 class="card-title">
                  <span class="me-2" style="width:10px;height:10px;background:var(--primary);border-radius:50%;display:inline-block;"></span>
                  Recent Surveys
                </h3>
                <a href="surveys.php" class="btn-outline-qa" style="font-size:.76rem;padding:5px 10px;">
                  View all <i class="fa-solid fa-chevron-right ms-1" style="font-size:.65rem;"></i>
                </a>
              </div>
              <div class="card-body-custom p-0" id="summary-recent-surveys">
                <?php for ($i = 0; $i < 3; $i++): ?>
                <div class="p-3" style="border-bottom:1px solid var(--border-light);">
                  <div class="d-flex justify-content-between mb-1">
                    <span class="placeholder-wave" style="width:55%;"><span class="placeholder col-12 bg-secondary rounded" style="height:12px;display:block;"></span></span>
                    <span class="placeholder-wave" style="width:20%;"><span class="placeholder col-12 bg-secondary rounded" style="height:12px;display:block;"></span></span>
                  </div>
                  <span class="placeholder-wave" style="width:30%;display:block;margin-top:6px;"><span class="placeholder col-12 bg-secondary rounded" style="height:8px;display:block;"></span></span>
                </div>
                <?php endfor; ?>
              </div>
            </div>
          </div>
        </div>

      </div><!-- /#section-summary -->

      <!-- ══════════════════════════════════════════════════════
           AUDITS SECTION
      ══════════════════════════════════════════════════════ -->
      <div id="section-audits" class="report-section d-none">
        <div class="card">
          <div class="card-header-custom">
            <h3 class="card-title">
              <span class="me-2" style="width:10px;height:10px;background:var(--accent-blue);border-radius:50%;display:inline-block;"></span>
              Audits Report
            </h3>
            <span class="text-muted-qa" style="font-size:.78rem;" id="audits-count"></span>
          </div>
          <div class="table-responsive">
            <table class="table-qa" id="tbl-audits">
              <thead>
                <tr>
                  <th>#</th><th>Title</th><th>Type</th><th>Scheduled</th>
                  <th>Completion</th><th>Status</th><th>Tasks</th><th>Progress</th><th>Notes</th>
                </tr>
              </thead>
              <tbody id="audits-tbody">
                <?php for ($i = 0; $i < 5; $i++): ?>
                <tr><?php for ($j = 0; $j < 9; $j++): ?>
                  <td><span class="placeholder-wave"><span class="placeholder col-8 bg-secondary rounded"></span></span></td>
                <?php endfor; ?></tr>
                <?php endfor; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════════
           TASKS SECTION
      ══════════════════════════════════════════════════════ -->
      <div id="section-tasks" class="report-section d-none">
        <div class="card">
          <div class="card-header-custom">
            <h3 class="card-title">
              <span class="me-2" style="width:10px;height:10px;background:var(--accent-orange);border-radius:50%;display:inline-block;"></span>
              Accreditation Tasks
            </h3>
            <span class="text-muted-qa" style="font-size:.78rem;" id="tasks-count"></span>
          </div>
          <div class="table-responsive">
            <table class="table-qa" id="tbl-tasks">
              <thead>
                <tr>
                  <th>#</th><th>Title</th><th>Audit</th><th>Standard</th>
                  <th>Due Date</th><th>Status</th><th>Remarks</th>
                </tr>
              </thead>
              <tbody id="tasks-tbody">
                <?php for ($i = 0; $i < 5; $i++): ?>
                <tr><?php for ($j = 0; $j < 7; $j++): ?>
                  <td><span class="placeholder-wave"><span class="placeholder col-8 bg-secondary rounded"></span></span></td>
                <?php endfor; ?></tr>
                <?php endfor; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════════
           KPIs SECTION
      ══════════════════════════════════════════════════════ -->
      <div id="section-kpis" class="report-section d-none">
        <div class="row g-3 mb-3">
          <div class="col-12">
            <div class="card">
              <div class="card-header-custom">
                <h3 class="card-title">
                  <span class="me-2" style="width:10px;height:10px;background:var(--accent-green);border-radius:50%;display:inline-block;"></span>
                  KPI — Actual vs. Target
                </h3>
              </div>
              <div class="card-body-custom" style="min-height:220px;">
                <canvas id="chart-kpi" style="max-height:220px;"></canvas>
              </div>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="card-header-custom">
            <h3 class="card-title">KPI Indicators</h3>
            <span class="text-muted-qa" style="font-size:.78rem;" id="kpis-count"></span>
          </div>
          <div class="table-responsive">
            <table class="table-qa" id="tbl-kpis">
              <thead>
                <tr>
                  <th>#</th><th>Indicator</th><th>Category</th><th>Unit</th>
                  <th>Target</th><th>Latest Value</th><th>Period</th><th>Meets Target</th>
                </tr>
              </thead>
              <tbody id="kpis-tbody">
                <?php for ($i = 0; $i < 4; $i++): ?>
                <tr><?php for ($j = 0; $j < 8; $j++): ?>
                  <td><span class="placeholder-wave"><span class="placeholder col-8 bg-secondary rounded"></span></span></td>
                <?php endfor; ?></tr>
                <?php endfor; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════════
           SURVEYS SECTION
      ══════════════════════════════════════════════════════ -->
      <div id="section-surveys" class="report-section d-none">
        <div class="card">
          <div class="card-header-custom">
            <h3 class="card-title">
              <span class="me-2" style="width:10px;height:10px;background:var(--primary);border-radius:50%;display:inline-block;"></span>
              Surveys Report
            </h3>
            <span class="text-muted-qa" style="font-size:.78rem;" id="surveys-count"></span>
          </div>
          <div class="table-responsive">
            <table class="table-qa" id="tbl-surveys">
              <thead>
                <tr>
                  <th>#</th><th>Title</th><th>Target Group</th><th>Start</th><th>End</th>
                  <th>Status</th><th>Questions</th><th>Responses</th><th>Created By</th>
                </tr>
              </thead>
              <tbody id="surveys-tbody">
                <?php for ($i = 0; $i < 4; $i++): ?>
                <tr><?php for ($j = 0; $j < 9; $j++): ?>
                  <td><span class="placeholder-wave"><span class="placeholder col-8 bg-secondary rounded"></span></span></td>
                <?php endfor; ?></tr>
                <?php endfor; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════════
           ACTION PLANS SECTION
      ══════════════════════════════════════════════════════ -->
      <div id="section-plans" class="report-section d-none">
        <div class="card">
          <div class="card-header-custom">
            <h3 class="card-title">
              <span class="me-2" style="width:10px;height:10px;background:var(--accent-orange);border-radius:50%;display:inline-block;"></span>
              Action Plans
            </h3>
            <span class="text-muted-qa" style="font-size:.78rem;" id="plans-count"></span>
          </div>
          <div class="table-responsive">
            <table class="table-qa" id="tbl-plans">
              <thead>
                <tr>
                  <th>#</th><th>Title</th><th>Related Audit</th><th>Root Cause</th>
                  <th>Target Date</th><th>Status</th><th>Resolution</th>
                </tr>
              </thead>
              <tbody id="plans-tbody">
                <?php for ($i = 0; $i < 4; $i++): ?>
                <tr><?php for ($j = 0; $j < 7; $j++): ?>
                  <td><span class="placeholder-wave"><span class="placeholder col-8 bg-secondary rounded"></span></span></td>
                <?php endfor; ?></tr>
                <?php endfor; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════════
           STANDARDS SECTION
      ══════════════════════════════════════════════════════ -->
      <div id="section-standards" class="report-section d-none">
        <div class="card">
          <div class="card-header-custom">
            <h3 class="card-title">
              <span class="me-2" style="width:10px;height:10px;background:var(--accent-green);border-radius:50%;display:inline-block;"></span>
              Standards &amp; Policies
            </h3>
            <span class="text-muted-qa" style="font-size:.78rem;" id="standards-count"></span>
          </div>
          <div class="table-responsive">
            <table class="table-qa" id="tbl-standards">
              <thead>
                <tr>
                  <th>#</th><th>Title</th><th>Body</th><th>Version</th><th>Effective</th>
                  <th>Status</th><th>Active Policies</th><th>Linked Tasks</th>
                </tr>
              </thead>
              <tbody id="standards-tbody">
                <?php for ($i = 0; $i < 4; $i++): ?>
                <tr><?php for ($j = 0; $j < 8; $j++): ?>
                  <td><span class="placeholder-wave"><span class="placeholder col-8 bg-secondary rounded"></span></span></td>
                <?php endfor; ?></tr>
                <?php endfor; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </main>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════
     EXPORT FILTER MODAL
══════════════════════════════════════════════════════ -->
<div id="export-modal-backdrop"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1040;
            align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:12px;width:min(560px,95vw);max-height:90vh;
              overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.25);font-family:var(--font);">

    <!-- Modal header -->
    <div style="padding:18px 22px 14px;border-bottom:1px solid var(--border);
                display:flex;align-items:center;justify-content:space-between;">
      <div>
        <h5 style="margin:0;font-size:1rem;font-weight:700;color:var(--text-primary);">
          <i class="fa-solid fa-file-export me-2" style="color:var(--primary);"></i>Export Reports
        </h5>
        <p style="margin:3px 0 0;font-size:.78rem;color:var(--text-muted);">
          Choose format, sections, and optional date range
        </p>
      </div>
      <button id="close-export-modal" style="background:none;border:none;cursor:pointer;
              font-size:1.1rem;color:var(--text-muted);padding:4px 6px;border-radius:6px;
              line-height:1;" title="Close">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <!-- Modal body -->
    <div style="padding:20px 22px;">

      <!-- Export format -->
      <div style="margin-bottom:18px;">
        <label style="font-size:.82rem;font-weight:600;color:var(--text-secondary);
                      text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:8px;">
          Export Format
        </label>
        <div style="display:flex;gap:10px;">
          <label id="fmt-pdf-label" style="flex:1;border:2px solid var(--primary);border-radius:8px;
                 padding:10px 14px;cursor:pointer;display:flex;align-items:center;gap:10px;
                 background:rgba(45,90,61,.05);">
            <input type="radio" name="export-format" value="pdf" checked style="accent-color:var(--primary);">
            <i class="fa-solid fa-file-pdf" style="color:#c0392b;font-size:1.1rem;"></i>
            <span style="font-size:.88rem;font-weight:600;color:var(--text-primary);">PDF</span>
          </label>
          <label id="fmt-excel-label" style="flex:1;border:2px solid var(--border);border-radius:8px;
                 padding:10px 14px;cursor:pointer;display:flex;align-items:center;gap:10px;">
            <input type="radio" name="export-format" value="excel" style="accent-color:var(--primary);">
            <i class="fa-solid fa-file-excel" style="color:#1e7145;font-size:1.1rem;"></i>
            <span style="font-size:.88rem;font-weight:600;color:var(--text-primary);">Excel</span>
          </label>
        </div>
      </div>

      <!-- Date Range -->
      <div style="margin-bottom:18px;">
        <label style="font-size:.82rem;font-weight:600;color:var(--text-secondary);
                      text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:8px;">
          Date Range
          <span style="font-size:.72rem;font-weight:400;color:var(--text-muted);
                       text-transform:none;letter-spacing:0;margin-left:6px;">
            (optional — filters records by their primary date field)
          </span>
        </label>
        <div style="display:flex;gap:10px;align-items:flex-end;">
          <div style="flex:1;">
            <label style="font-size:.75rem;color:var(--text-muted);display:block;margin-bottom:4px;">From</label>
            <input type="date" id="export-date-from"
                   style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:7px;
                          font-family:var(--font);font-size:.85rem;color:var(--text-primary);
                          background:#fff;outline:none;box-sizing:border-box;">
          </div>
          <div style="padding-bottom:9px;color:var(--text-muted);font-size:.85rem;">&#8594;</div>
          <div style="flex:1;">
            <label style="font-size:.75rem;color:var(--text-muted);display:block;margin-bottom:4px;">To</label>
            <input type="date" id="export-date-to"
                   style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:7px;
                          font-family:var(--font);font-size:.85rem;color:var(--text-primary);
                          background:#fff;outline:none;box-sizing:border-box;">
          </div>
          <div style="padding-bottom:2px;">
            <button id="clear-date-range" title="Clear dates"
                    style="background:none;border:1px solid var(--border);border-radius:7px;
                           padding:8px 10px;cursor:pointer;color:var(--text-muted);font-size:.8rem;
                           line-height:1;">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
        </div>
        <div id="date-range-hint" style="font-size:.74rem;color:var(--text-muted);margin-top:6px;display:none;">
          <i class="fa-solid fa-circle-info me-1"></i>
          <span id="date-range-hint-text"></span>
        </div>
      </div>

      <!-- Sections to include -->
      <div style="margin-bottom:6px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
          <label style="font-size:.82rem;font-weight:600;color:var(--text-secondary);
                        text-transform:uppercase;letter-spacing:.5px;">
            Sections to Include
          </label>
          <div style="display:flex;gap:8px;">
            <button id="export-check-all" style="background:none;border:none;cursor:pointer;
                    font-size:.75rem;color:var(--primary);font-weight:600;padding:2px 6px;">
              Select All
            </button>
            <span style="color:var(--border);">|</span>
            <button id="export-uncheck-all" style="background:none;border:none;cursor:pointer;
                    font-size:.75rem;color:var(--text-muted);font-weight:600;padding:2px 6px;">
              Clear All
            </button>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;" id="export-section-checks">
          <?php
          $exportSections = [
            ['key'=>'summary',      'icon'=>'fa-chart-pie',            'label'=>'Overview / Summary'],
            ['key'=>'audits',       'icon'=>'fa-magnifying-glass',     'label'=>'Audits'],
            ['key'=>'tasks',        'icon'=>'fa-list-check',           'label'=>'Accreditation Tasks'],
            ['key'=>'kpis',         'icon'=>'fa-chart-line',           'label'=>'KPI Indicators'],
            ['key'=>'surveys',      'icon'=>'fa-paper-plane',          'label'=>'Surveys'],
            ['key'=>'action_plans', 'icon'=>'fa-triangle-exclamation', 'label'=>'Action Plans'],
            ['key'=>'standards',    'icon'=>'fa-clipboard-check',      'label'=>'Standards &amp; Policies'],
          ];
          foreach ($exportSections as $s):
          ?>
          <label class="export-section-check-label"
                 style="display:flex;align-items:center;gap:9px;padding:9px 12px;
                        border:1px solid var(--border);border-radius:8px;cursor:pointer;
                        font-size:.84rem;font-weight:500;color:var(--text-primary);
                        background:#fafafa;transition:border-color .15s,background .15s;">
            <input type="checkbox" name="export-section" value="<?= $s['key'] ?>" checked
                   style="accent-color:var(--primary);width:15px;height:15px;flex-shrink:0;">
            <i class="fa-solid <?= $s['icon'] ?>" style="color:var(--primary);width:14px;text-align:center;font-size:.85rem;"></i>
            <?= $s['label'] ?>
          </label>
          <?php endforeach; ?>
        </div>
        <p id="export-no-section-warn" style="display:none;color:#c0392b;font-size:.78rem;
           margin-top:8px;"><i class="fa-solid fa-triangle-exclamation me-1"></i>Select at least one section.</p>
      </div>

    </div><!-- /modal body -->

    <!-- Modal footer -->
    <div style="padding:14px 22px 18px;border-top:1px solid var(--border);
                display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
      <div id="export-active-filter-badge" style="font-size:.76rem;color:var(--text-muted);display:none;">
        <i class="fa-solid fa-filter me-1" style="color:var(--primary);"></i>
        <span id="export-filter-summary"></span>
      </div>
      <div style="margin-left:auto;display:flex;gap:10px;">
        <button id="cancel-export-btn" class="btn-outline-qa" style="min-width:90px;">
          Cancel
        </button>
        <button id="confirm-export-btn" class="btn-primary-qa" style="min-width:130px;">
          <i class="fa-solid fa-download me-1"></i> Export
        </button>
      </div>
    </div>

  </div>
</div>

<!-- Toast container -->
<div id="toast-container"></div>

<!-- ── Scripts ──────────────────────────────────────────────── -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="../assets/js/app.js"></script>

<script>
$(function () {

  /* ──────────────────────────────────────────────────────────
     CONFIG
  ────────────────────────────────────────────────────────── */
  const API = '../../backend/api/reports_api.php';
  const cache = {};           // store fetched data per action
  const chartInst = {};       // Chart.js instances
  let activeSection = 'summary';

  // ── Page-level date filter state ──────────────────────────
  const pageFilter = { from: '', to: '' };   // '' means no filter

  /* ──────────────────────────────────────────────────────────
     HELPERS  (mirror dashboard.php helpers)
  ────────────────────────────────────────────────────────── */
  function esc(str) {
    return String(str ?? '')
      .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
  }

  function badgeClass(status) {
    const map = {
      'scheduled'  : 'pending',
      'in progress': 'in-progress',
      'completed'  : 'completed',
      'cancelled'  : 'cancelled',
      'pending'    : 'pending',
      'active'     : 'active',
      'open'       : 'pending',
      'resolved'   : 'completed',
      'closed'     : 'completed',
      'draft'      : 'pending',
      'archived'   : 'cancelled',
    };
    return map[(status ?? '').toLowerCase()] || 'pending';
  }

  function badge(status) {
    return `<span class="badge-qa ${badgeClass(status)}">${esc(status)}</span>`;
  }

  function emptyRow(cols, msg) {
    return `<tr><td colspan="${cols}" class="text-center py-4 text-muted-qa"
                style="font-size:.82rem;">
              <i class="fa-regular fa-folder-open mb-2" style="font-size:1.5rem;display:block;opacity:.3;"></i>
              ${esc(msg)}
            </td></tr>`;
  }

  function progressCell(pct) {
    return `<div class="d-flex align-items-center gap-2">
              <div class="progress-bar-wrap flex-fill">
                <div class="progress-bar-fill blue" style="width:${pct}%;"></div>
              </div>
              <span style="font-size:.74rem;font-weight:700;color:var(--text-primary);min-width:32px;">${pct}%</span>
            </div>`;
  }

  function meetsTarget(val) {
    if (val === null) return '<span style="color:var(--text-muted);font-size:.78rem;">N/A</span>';
    return val
      ? '<span class="badge-qa active"><i class="fa-solid fa-check me-1"></i>Yes</span>'
      : '<span class="badge-qa cancelled"><i class="fa-solid fa-xmark me-1"></i>No</span>';
  }

  function animateCount(selector, target, suffix = '') {
    const el = $(selector);
    let cur = 0;
    const step = Math.ceil(Math.max(target, 1) / 30);
    const t = setInterval(() => {
      cur = Math.min(cur + step, target);
      el.text(cur + suffix);
      if (cur >= target) clearInterval(t);
    }, 30);
  }

  /* ──────────────────────────────────────────────────────────
     PAGE DATE FILTER — helpers
  ────────────────────────────────────────────────────────── */
  // Primary date field used for page-level filtering per module
  const PG_DATE = {
    audits       : 'scheduled_date',
    tasks        : 'due_date',
    surveys      : 'start_date',
    action_plans : 'target_date',
    standards    : 'effective_date',
  };

  function filterRows(rows, dateField) {
    const f = pageFilter.from, t = pageFilter.to;
    if (!f && !t) return rows;
    if (!dateField)  return rows;
    return rows.filter(r => {
      const d = r[dateField];
      if (!d) return false;
      if (f && d < f) return false;
      if (t && d > t) return false;
      return true;
    });
  }

  // Format a date string nicely (YYYY-MM-DD → May 9, 2026)
  function fmtDate(s) {
    if (!s) return '';
    const [y, m, d] = s.split('-');
    return new Date(+y, +m - 1, +d).toLocaleDateString('en-PH', { year:'numeric', month:'short', day:'numeric' });
  }

  function updateFilterPill() {
    const f = pageFilter.from, t = pageFilter.to;
    if (!f && !t) {
      $('#filter-active-pill').hide();
      return;
    }
    let txt = 'Showing records ';
    if (f && t)   txt += fmtDate(f) + ' → ' + fmtDate(t);
    else if (f)   txt += 'from ' + fmtDate(f);
    else          txt += 'up to '  + fmtDate(t);
    $('#filter-active-text').text(txt);
    $('#filter-active-pill').css('display', 'block');
  }

  /* ──────────────────────────────────────────────────────────
     FETCH WRAPPER  (caches per action key)
  ────────────────────────────────────────────────────────── */
  function fetchReport(action) {
    if (cache[action]) return $.Deferred().resolve(cache[action]).promise();
    return $.ajax({ url: API, type: 'GET', data: { action }, dataType: 'json' })
      .then(function (res) {
        if (!res.success) throw res.message;
        // API responses include a top-level `data` key containing the payload.
        // Some endpoints may wrap lists inside another `data` key; support both.
        cache[action] = (res.data && res.data.data) ? res.data.data : res.data;
        return cache[action];
      });
  }

  /* ──────────────────────────────────────────────────────────
     TAB SWITCHING
  ────────────────────────────────────────────────────────── */
  $('#report-tabs').on('click', '.report-tab-btn', function () {
    const tab = $(this).data('tab');
    if (tab === activeSection) return;
    activeSection = tab;

    // Update tab styles (mirror dashboard pattern)
    $('.report-tab-btn').css({
      'color'        : 'var(--text-secondary)',
      'border-bottom': '2px solid transparent',
      'font-weight'  : '500',
    }).removeClass('active');
    $(this).css({
      'color'        : 'var(--primary)',
      'border-bottom': '2px solid var(--primary)',
      'font-weight'  : '600',
    }).addClass('active');

    // Show / hide sections
    $('.report-section').addClass('d-none');
    $(`#section-${tab}`).removeClass('d-none');

    loadSection(tab);
  });

  function loadSection(tab) {
    switch (tab) {
      case 'summary':   loadSummary();   break;
      case 'audits':    loadAudits();    break;
      case 'tasks':     loadTasks();     break;
      case 'kpis':      loadKpis();      break;
      case 'surveys':   loadSurveys();   break;
      case 'plans':     loadPlans();     break;
      case 'standards': loadStandards(); break;
    }
  }

  /* ──────────────────────────────────────────────────────────
     DONUT CHART HELPER
  ────────────────────────────────────────────────────────── */
  function donutChart(id, labels, values, colors) {
    const ctx = document.getElementById(id);
    if (!ctx) return;
    if (chartInst[id]) chartInst[id].destroy();
    chartInst[id] = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels,
        datasets: [{ data: values, backgroundColor: colors, borderWidth: 2, borderColor: '#fff' }]
      },
      options: {
        plugins: {
          legend: {
            position: 'bottom',
            labels: { font: { family: 'var(--font)', size: 11 }, boxWidth: 12, padding: 10 }
          }
        },
        cutout: '62%',
        animation: { animateScale: true }
      }
    });
  }

  /* ──────────────────────────────────────────────────────────
     OVERVIEW / SUMMARY
  ────────────────────────────────────────────────────────── */
  function loadSummary() {
    const hasFilter = pageFilter.from || pageFilter.to;

    if (hasFilter) {
      // When a date filter is active, fetch raw data from individual endpoints
      // so we can apply filterRows() and re-derive aggregated numbers.
      $.when(
        fetchReport('audits'),
        fetchReport('tasks'),
        fetchReport('surveys'),
        fetchReport('action_plans'),
        fetchReport('standards'),
        fetchReport('kpis'),
      ).then(function (audits, tasks, surveys, plans, standards, kpis) {
        const fAudits    = filterRows(audits,    PG_DATE.audits);
        const fTasks     = filterRows(tasks,     PG_DATE.tasks);
        const fSurveys   = filterRows(surveys,   PG_DATE.surveys);
        const fPlans     = filterRows(plans,     PG_DATE.action_plans);
        const fStandards = filterRows(standards, PG_DATE.standards);

        // ── Stat cards ──────────────────────────────────────
        animateCount('#stat-audits',    fAudits.length);
        animateCount('#stat-tasks',     fTasks.length);

        // KPI avg from latest_value across all indicators (not date-filtered since KPIs use period_year)
        const kpiVals = kpis.map(k => parseFloat(k.latest_value)).filter(v => !isNaN(v));
        const kpiAvg  = kpiVals.length ? (kpiVals.reduce((a,b) => a+b, 0) / kpiVals.length).toFixed(2) : '—';
        $('#stat-kpi').text(kpiAvg);

        const openPlans = fPlans.filter(p => (p.status || '').toLowerCase() === 'open').length;
        animateCount('#stat-plans',     openPlans);
        animateCount('#stat-surveys',   fSurveys.length);

        const totalResponses = fSurveys.reduce((sum, s) => sum + (parseInt(s.responses_count) || 0), 0);
        animateCount('#stat-responses', totalResponses);

        const activeStds = fStandards.filter(s => (s.status || '').toLowerCase() === 'active').length;
        animateCount('#stat-standards', activeStds);

        const activePolicies = fStandards.reduce((sum, s) => sum + (parseInt(s.active_policies) || 0), 0);
        animateCount('#stat-policies',  activePolicies);

        // ── Donut charts (derived from filtered rows) ───────
        const auditColors = { 'Scheduled':'#2980b9','In Progress':'#e67e22','Completed':'#27ae60','Cancelled':'#c0392b' };
        const auditByStatus = fAudits.reduce((acc, r) => {
          const s = r.status || 'Unknown';
          acc[s] = (acc[s] || 0) + 1;
          return acc;
        }, {});
        const aLabels = Object.keys(auditByStatus);
        donutChart('chart-audit-status', aLabels, Object.values(auditByStatus), aLabels.map(l => auditColors[l] || '#999'));

        const taskColors = { 'Pending':'#6b6860','In Progress':'#e67e22','Completed':'#27ae60' };
        const taskByStatus = fTasks.reduce((acc, r) => {
          const s = r.status || 'Unknown';
          acc[s] = (acc[s] || 0) + 1;
          return acc;
        }, {});
        const tLabels = Object.keys(taskByStatus);
        donutChart('chart-task-status', tLabels, Object.values(taskByStatus), tLabels.map(l => taskColors[l] || '#999'));

        const survColors = { 'Draft':'#aaa','Active':'#27ae60','Closed':'#c0392b' };
        const survByStatus = fSurveys.reduce((acc, r) => {
          const s = r.status || 'Unknown';
          acc[s] = (acc[s] || 0) + 1;
          return acc;
        }, {});
        const sLabels = Object.keys(survByStatus);
        donutChart('chart-survey-status', sLabels, Object.values(survByStatus), sLabels.map(l => survColors[l] || '#999'));

        // ── Recent Audits (filtered, sorted by scheduled_date desc) ──
        const $auditList = $('#summary-recent-audits').empty();
        const recentAudits = [...fAudits]
          .sort((a, b) => (b.scheduled_date || '').localeCompare(a.scheduled_date || ''))
          .slice(0, 5);
        if (!recentAudits.length) {
          $auditList.html('<div class="p-3 text-muted-qa" style="font-size:.82rem;">No audits in this date range.</div>');
        } else {
          recentAudits.forEach(a => {
            const pct = a.progress_pct ?? 0;
            $auditList.append(`
              <div class="p-3" style="border-bottom:1px solid var(--border-light);">
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <span style="font-size:.83rem;font-weight:600;color:var(--text-primary);">${esc(a.title)}</span>
                  ${badge(a.status)}
                </div>
                <div style="font-size:.74rem;color:var(--text-muted);margin-bottom:6px;">
                  <i class="fa-regular fa-calendar me-1"></i>${esc(a.scheduled_date || '—')}
                  &nbsp;·&nbsp; ${esc(a.audit_type)}
                </div>
                <div class="d-flex align-items-center gap-2">
                  <div class="progress-bar-wrap flex-fill">
                    <div class="progress-bar-fill blue" style="width:${pct}%;"></div>
                  </div>
                  <span style="font-size:.74rem;font-weight:700;color:var(--text-primary);min-width:32px;">${pct}%</span>
                </div>
              </div>`);
          });
        }

        // ── Recent Surveys (filtered, sorted by start_date desc) ──
        const $survList = $('#summary-recent-surveys').empty();
        const recentSurveys = [...fSurveys]
          .sort((a, b) => (b.start_date || '').localeCompare(a.start_date || ''))
          .slice(0, 5);
        if (!recentSurveys.length) {
          $survList.html('<div class="p-3 text-muted-qa" style="font-size:.82rem;">No surveys in this date range.</div>');
        } else {
          recentSurveys.forEach(s => {
            $survList.append(`
              <div class="p-3" style="border-bottom:1px solid var(--border-light);">
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <span style="font-size:.83rem;font-weight:600;color:var(--text-primary);">${esc(s.title)}</span>
                  ${badge(s.status)}
                </div>
                <div style="font-size:.74rem;color:var(--text-muted);">
                  <i class="fa-solid fa-users me-1"></i>${esc(s.target_group)}
                  &nbsp;·&nbsp;
                  <i class="fa-solid fa-comments me-1"></i>${s.responses_count} response${s.responses_count !== 1 ? 's' : ''}
                </div>
              </div>`);
          });
        }

      }).fail(function (err) {
        toast.error('Could not load filtered overview: ' + err);
      });

    } else {
      // No filter: use the fast pre-aggregated summary endpoint
      fetchReport('summary').then(function (d) {

        // Stat cards
        animateCount('#stat-audits',    d.audits.total);
        animateCount('#stat-tasks',     d.tasks.total);
        $('#stat-kpi').text(d.kpis.avg);
        animateCount('#stat-plans',     d.plans.by_status['Open'] ?? 0);
        animateCount('#stat-surveys',   d.surveys.total);
        animateCount('#stat-responses', d.surveys.total_responses);
        animateCount('#stat-standards', d.standards.active);
        animateCount('#stat-policies',  d.standards.policies);

        // Audit status donut
        const auditColors = { 'Scheduled':'#2980b9','In Progress':'#e67e22','Completed':'#27ae60','Cancelled':'#c0392b' };
        const aLabels = Object.keys(d.audits.by_status);
        donutChart('chart-audit-status', aLabels, Object.values(d.audits.by_status), aLabels.map(l => auditColors[l] || '#999'));

        // Task status donut
        const taskColors = { 'Pending':'#6b6860','In Progress':'#e67e22','Completed':'#27ae60' };
        const tLabels = Object.keys(d.tasks.by_status);
        donutChart('chart-task-status', tLabels, Object.values(d.tasks.by_status), tLabels.map(l => taskColors[l] || '#999'));

        // Survey status donut
        const survColors = { 'Draft':'#aaa','Active':'#27ae60','Closed':'#c0392b' };
        const sLabels = Object.keys(d.surveys.by_status);
        donutChart('chart-survey-status', sLabels, Object.values(d.surveys.by_status), sLabels.map(l => survColors[l] || '#999'));

        // Recent audits list
        const $auditList = $('#summary-recent-audits').empty();
        if (!d.recent_audits?.length) {
          $auditList.html('<div class="p-3 text-muted-qa" style="font-size:.82rem;">No audits found.</div>');
        } else {
          d.recent_audits.forEach(a => {
            const pct = a.progress_pct ?? 0;
            $auditList.append(`
              <div class="p-3" style="border-bottom:1px solid var(--border-light);">
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <span style="font-size:.83rem;font-weight:600;color:var(--text-primary);">${esc(a.title)}</span>
                  ${badge(a.status)}
                </div>
                <div style="font-size:.74rem;color:var(--text-muted);margin-bottom:6px;">
                  <i class="fa-regular fa-calendar me-1"></i>${esc(a.scheduled_date || '—')}
                  &nbsp;·&nbsp; ${esc(a.audit_type)}
                </div>
                <div class="d-flex align-items-center gap-2">
                  <div class="progress-bar-wrap flex-fill">
                    <div class="progress-bar-fill blue" style="width:${pct}%;"></div>
                  </div>
                  <span style="font-size:.74rem;font-weight:700;color:var(--text-primary);min-width:32px;">${pct}%</span>
                </div>
              </div>`);
          });
        }

        // Recent surveys list
        const $survList = $('#summary-recent-surveys').empty();
        if (!d.recent_surveys?.length) {
          $survList.html('<div class="p-3 text-muted-qa" style="font-size:.82rem;">No surveys found.</div>');
        } else {
          d.recent_surveys.forEach(s => {
            $survList.append(`
              <div class="p-3" style="border-bottom:1px solid var(--border-light);">
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <span style="font-size:.83rem;font-weight:600;color:var(--text-primary);">${esc(s.title)}</span>
                  ${badge(s.status)}
                </div>
                <div style="font-size:.74rem;color:var(--text-muted);">
                  <i class="fa-solid fa-users me-1"></i>${esc(s.target_group)}
                  &nbsp;·&nbsp;
                  <i class="fa-solid fa-comments me-1"></i>${s.responses_count} response${s.responses_count !== 1 ? 's' : ''}
                </div>
              </div>`);
          });
        }

      }).fail(function (err) {
        toast.error('Could not load overview: ' + err);
      });
    }
  }

  /* ──────────────────────────────────────────────────────────
     AUDITS TABLE
  ────────────────────────────────────────────────────────── */
  function loadAudits() {
    if (cache['audits']) { renderAudits(cache['audits']); return; }
    fetchReport('audits').then(renderAudits).fail(() => toast.error('Failed to load audits.'));
  }

  function renderAudits(rows) {
    rows = filterRows(rows, PG_DATE.audits);
    $('#audits-count').text(rows.length + ' record' + (rows.length !== 1 ? 's' : ''));
    const tbody = $('#audits-tbody').empty();
    if (!rows.length) { tbody.html(emptyRow(9, 'No audits found')); return; }
    rows.forEach(r => {
      tbody.append(`<tr>
        <td>${esc(r.audit_id)}</td>
        <td style="font-weight:600;">${esc(r.title)}</td>
        <td>${esc(r.audit_type)}</td>
        <td>${esc(r.scheduled_date  || '—')}</td>
        <td>${esc(r.completion_date || '—')}</td>
        <td>${badge(r.status)}</td>
        <td><span style="font-size:.8rem;">${esc(r.completed_tasks)}/${esc(r.total_tasks)}</span></td>
        <td style="min-width:130px;">${progressCell(r.progress_pct)}</td>
        <td style="max-width:160px;word-break:break-word;font-size:.78rem;color:var(--text-secondary);">${esc(r.notes || '—')}</td>
      </tr>`);
    });
  }

  /* ──────────────────────────────────────────────────────────
     TASKS TABLE
  ────────────────────────────────────────────────────────── */
  function loadTasks() {
    if (cache['tasks']) { renderTasks(cache['tasks']); return; }
    fetchReport('tasks').then(renderTasks).fail(() => toast.error('Failed to load tasks.'));
  }

  function renderTasks(rows) {
    rows = filterRows(rows, PG_DATE.tasks);
    $('#tasks-count').text(rows.length + ' record' + (rows.length !== 1 ? 's' : ''));
    const tbody = $('#tasks-tbody').empty();
    if (!rows.length) { tbody.html(emptyRow(7, 'No tasks found')); return; }
    rows.forEach(r => {
      tbody.append(`<tr>
        <td>${esc(r.task_id)}</td>
        <td style="font-weight:600;">${esc(r.title)}</td>
        <td>${esc(r.audit_title  || '—')}</td>
        <td>${r.standard_title
              ? `<span style="font-size:.72rem;color:var(--text-muted);">[${esc(r.standard_body)}]</span> ${esc(r.standard_title)}`
              : '—'}</td>
        <td>${esc(r.due_date || '—')}</td>
        <td>${badge(r.status)}</td>
        <td style="max-width:180px;word-break:break-word;font-size:.78rem;color:var(--text-secondary);">${esc(r.remarks || '—')}</td>
      </tr>`);
    });
  }

  /* ──────────────────────────────────────────────────────────
     KPIs
  ────────────────────────────────────────────────────────── */
  function loadKpis() {
    if (cache['kpis']) { renderKpis(cache['kpis']); return; }
    fetchReport('kpis').then(renderKpis).fail(() => toast.error('Failed to load KPIs.'));
  }

  function renderKpis(rows) {
    $('#kpis-count').text(rows.length + ' indicator' + (rows.length !== 1 ? 's' : ''));
    const tbody = $('#kpis-tbody').empty();
    if (!rows.length) { tbody.html(emptyRow(8, 'No KPI indicators found')); return; }
    rows.forEach(r => {
      tbody.append(`<tr>
        <td>${esc(r.indicator_id)}</td>
        <td style="font-weight:600;">${esc(r.name)}</td>
        <td>${esc(r.category || '—')}</td>
        <td>${esc(r.unit    || '—')}</td>
        <td>${r.target_value != null ? Number(r.target_value).toLocaleString('en-PH') : '—'}</td>
        <td style="font-weight:700;">${r.latest_value != null ? Number(r.latest_value).toLocaleString('en-PH') : '—'}</td>
        <td style="font-size:.78rem;color:var(--text-muted);">${esc(r.latest_period || '—')}</td>
        <td>${meetsTarget(r.meets_target)}</td>
      </tr>`);
    });

    // Bar chart: actual vs target
    const ctx = document.getElementById('chart-kpi');
    if (ctx && rows.length) {
      if (chartInst['chart-kpi']) chartInst['chart-kpi'].destroy();
      chartInst['chart-kpi'] = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: rows.map(r => r.name),
          datasets: [
            {
              label: 'Target',
              data: rows.map(r => r.target_value),
              backgroundColor: 'rgba(45,90,61,.18)',
              borderColor: '#2d5a3d',
              borderWidth: 2,
            },
            {
              label: 'Actual (Latest)',
              data: rows.map(r => r.latest_value),
              backgroundColor: 'rgba(41,128,185,.65)',
              borderColor: '#2980b9',
              borderWidth: 2,
            },
          ]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { labels: { font: { family: 'var(--font)', size: 11 }, boxWidth: 12 } }
          },
          scales: { y: { beginAtZero: true } }
        }
      });
    }
  }

  /* ──────────────────────────────────────────────────────────
     SURVEYS TABLE
  ────────────────────────────────────────────────────────── */
  function loadSurveys() {
    if (cache['surveys']) { renderSurveys(cache['surveys']); return; }
    fetchReport('surveys').then(renderSurveys).fail(() => toast.error('Failed to load surveys.'));
  }

  function renderSurveys(rows) {
    rows = filterRows(rows, PG_DATE.surveys);
    $('#surveys-count').text(rows.length + ' record' + (rows.length !== 1 ? 's' : ''));
    const tbody = $('#surveys-tbody').empty();
    if (!rows.length) { tbody.html(emptyRow(9, 'No surveys found')); return; }
    rows.forEach(r => {
      tbody.append(`<tr>
        <td>${esc(r.survey_id)}</td>
        <td style="font-weight:600;">${esc(r.title)}</td>
        <td>${esc(r.target_group)}</td>
        <td>${esc(r.start_date || '—')}</td>
        <td>${esc(r.end_date   || '—')}</td>
        <td>${badge(r.status)}</td>
        <td>${esc(r.questions_count)}</td>
        <td>${esc(r.responses_count)}</td>
        <td style="font-size:.8rem;color:var(--text-muted);">${esc(r.creator_name || '—')}</td>
      </tr>`);
    });
  }

  /* ──────────────────────────────────────────────────────────
     ACTION PLANS TABLE
  ────────────────────────────────────────────────────────── */
  function loadPlans() {
    if (cache['action_plans']) { renderPlans(cache['action_plans']); return; }
    fetchReport('action_plans').then(renderPlans).fail(() => toast.error('Failed to load action plans.'));
  }

  function renderPlans(rows) {
    rows = filterRows(rows, PG_DATE.action_plans);
    $('#plans-count').text(rows.length + ' record' + (rows.length !== 1 ? 's' : ''));
    const tbody = $('#plans-tbody').empty();
    if (!rows.length) { tbody.html(emptyRow(7, 'No action plans found')); return; }
    rows.forEach(r => {
      tbody.append(`<tr>
        <td>${esc(r.plan_id)}</td>
        <td style="font-weight:600;">${esc(r.title)}</td>
        <td>${esc(r.audit_title || '—')}</td>
        <td style="max-width:160px;word-break:break-word;font-size:.78rem;color:var(--text-secondary);">${esc(r.root_cause || '—')}</td>
        <td>${esc(r.target_date || '—')}</td>
        <td>${badge(r.status)}</td>
        <td style="max-width:160px;word-break:break-word;font-size:.78rem;color:var(--text-secondary);">${esc(r.resolution || '—')}</td>
      </tr>`);
    });
  }

  /* ──────────────────────────────────────────────────────────
     STANDARDS TABLE
  ────────────────────────────────────────────────────────── */
  function loadStandards() {
    if (cache['standards']) { renderStandards(cache['standards']); return; }
    fetchReport('standards').then(renderStandards).fail(() => toast.error('Failed to load standards.'));
  }

  function renderStandards(rows) {
    rows = filterRows(rows, PG_DATE.standards);
    $('#standards-count').text(rows.length + ' record' + (rows.length !== 1 ? 's' : ''));
    const tbody = $('#standards-tbody').empty();
    if (!rows.length) { tbody.html(emptyRow(8, 'No standards found')); return; }
    rows.forEach(r => {
      tbody.append(`<tr>
        <td>${esc(r.standard_id)}</td>
        <td style="font-weight:600;">${esc(r.title)}</td>
        <td><span class="badge-qa pending">${esc(r.body)}</span></td>
        <td>${esc(r.version || '—')}</td>
        <td>${esc(r.effective_date || '—')}</td>
        <td>${badge(r.status)}</td>
        <td>${esc(r.active_policies)} / ${esc(r.total_policies)}</td>
        <td>${esc(r.linked_tasks)}</td>
      </tr>`);
    });
  }

  /* ──────────────────────────────────────────────────────────
     REFRESH BUTTON
  ────────────────────────────────────────────────────────── */
  /* ──────────────────────────────────────────────────────────
     PAGE FILTER — bar event handlers
  ────────────────────────────────────────────────────────── */

  // Preset date ranges
  $('.filter-preset-btn').on('click', function () {
    const preset = $(this).data('preset');
    const now = new Date();
    let from, to;
    if (preset === 'this_month') {
      from = new Date(now.getFullYear(), now.getMonth(), 1);
      to   = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    } else if (preset === 'last_month') {
      from = new Date(now.getFullYear(), now.getMonth() - 1, 1);
      to   = new Date(now.getFullYear(), now.getMonth(), 0);
    } else if (preset === 'this_year') {
      from = new Date(now.getFullYear(), 0, 1);
      to   = new Date(now.getFullYear(), 11, 31);
    } else if (preset === 'last_year') {
      from = new Date(now.getFullYear() - 1, 0, 1);
      to   = new Date(now.getFullYear() - 1, 11, 31);
    }
    const fmt = d => d.toISOString().slice(0, 10);
    $('#filter-date-from').val(fmt(from));
    $('#filter-date-to').val(fmt(to));

    // Highlight active preset
    $('.filter-preset-btn').css({ background:'#fff', color:'var(--text-secondary)', borderColor:'var(--border)' });
    $(this).css({ background:'var(--primary)', color:'#fff', borderColor:'var(--primary)' });
  });

  // Clear preset highlight when dates are manually changed
  $('#filter-date-from, #filter-date-to').on('change', function () {
    $('.filter-preset-btn').css({ background:'#fff', color:'var(--text-secondary)', borderColor:'var(--border)' });
  });

  // Apply filter
  $('#filter-apply-btn').on('click', function () {
    pageFilter.from = $('#filter-date-from').val();
    pageFilter.to   = $('#filter-date-to').val();

    if (!pageFilter.from && !pageFilter.to) {
      if (typeof toast !== 'undefined') toast.warning('Please enter at least one date to filter.', 'Filter');
      return;
    }

    if (pageFilter.from && pageFilter.to && pageFilter.from > pageFilter.to) {
      if (typeof toast !== 'undefined') toast.error('"From" date cannot be after "To" date.', 'Invalid Range');
      return;
    }

    updateFilterPill();
    rerenderActiveSection();

    if (typeof toast !== 'undefined') toast.success('Filter applied.', 'Date Filter');
  });

  // Clear filter — from the bar buttons or the pill
  function clearPageFilter() {
    pageFilter.from = '';
    pageFilter.to   = '';
    $('#filter-date-from, #filter-date-to').val('');
    $('.filter-preset-btn').css({ background:'#fff', color:'var(--text-secondary)', borderColor:'var(--border)' });
    $('#filter-active-pill').hide();
    rerenderActiveSection();
    if (typeof toast !== 'undefined') toast.success('Filter cleared.', 'Date Filter');
  }

  $('#filter-clear-btn, #filter-pill-clear').on('click', clearPageFilter);

  // Re-render the currently visible section using already-cached data
  function rerenderActiveSection() {
    switch (activeSection) {
      case 'summary':
        // Always re-render: filtered path uses raw-endpoint caches; unfiltered path uses summary cache.
        // loadSummary() itself decides which path to take based on pageFilter state.
        loadSummary();
        break;
      case 'audits':
        if (cache['audits'])       renderAudits(cache['audits']);
        else                       loadAudits();
        break;
      case 'tasks':
        if (cache['tasks'])        renderTasks(cache['tasks']);
        else                       loadTasks();
        break;
      case 'kpis':
        if (cache['kpis'])         renderKpis(cache['kpis']);
        else                       loadKpis();
        break;
      case 'surveys':
        if (cache['surveys'])      renderSurveys(cache['surveys']);
        else                       loadSurveys();
        break;
      case 'plans':
        if (cache['action_plans']) renderPlans(cache['action_plans']);
        else                       loadPlans();
        break;
      case 'standards':
        if (cache['standards'])    renderStandards(cache['standards']);
        else                       loadStandards();
        break;
    }
  }

  $('#refresh-btn').on('click', function () {
    const btn = this;
    $(btn).find('i').addClass('fa-spin');
    btn.disabled = true;

    // Bust cache for current section only
    // map UI tab keys to API action keys (e.g. 'plans' -> 'action_plans')
    const actionKey = (tab) => tab === 'plans' ? 'action_plans' : tab === 'summary' ? 'summary' : tab;
    delete cache[actionKey(activeSection)];
    // Also bust summary sub-data
    if (activeSection === 'summary') {
      Object.keys(cache).forEach(k => delete cache[k]);
      // Reset chart instances
      Object.values(chartInst).forEach(c => c?.destroy());
      Object.keys(chartInst).forEach(k => delete chartInst[k]);
    }
    loadSection(activeSection);

    setTimeout(() => {
      $(btn).find('i').removeClass('fa-spin');
      btn.disabled = false;
      if (typeof toast !== 'undefined') toast.success('Report refreshed.', 'Updated');
    }, 900);
  });

  /* ──────────────────────────────────────────────────────────
     EXPORT MODAL — open / close / format toggle
  ────────────────────────────────────────────────────────── */
  function openExportModal() {
    $('#export-modal-backdrop').css('display', 'flex');
    $('body').css('overflow', 'hidden');
    updateFilterBadge();
  }
  function closeExportModal() {
    $('#export-modal-backdrop').css('display', 'none');
    $('body').css('overflow', '');
  }

  $('#open-export-modal-btn').on('click', openExportModal);
  $('#close-export-modal, #cancel-export-btn').on('click', closeExportModal);

  // Close on backdrop click
  $('#export-modal-backdrop').on('click', function (e) {
    if (e.target === this) closeExportModal();
  });

  // Format radio — highlight selected card
  $('input[name="export-format"]').on('change', function () {
    const val = $(this).val();
    $('#fmt-pdf-label').css({
      'border-color': val === 'pdf' ? 'var(--primary)' : 'var(--border)',
      'background'  : val === 'pdf' ? 'rgba(45,90,61,.05)' : '#fff',
    });
    $('#fmt-excel-label').css({
      'border-color': val === 'excel' ? 'var(--primary)' : 'var(--border)',
      'background'  : val === 'excel' ? 'rgba(45,90,61,.05)' : '#fff',
    });
  });

  // Section checkboxes — toggle label style
  $(document).on('change', 'input[name="export-section"]', function () {
    const lbl = $(this).closest('label');
    if ($(this).is(':checked')) {
      lbl.css({ 'border-color': 'var(--primary)', 'background': 'rgba(45,90,61,.04)' });
    } else {
      lbl.css({ 'border-color': 'var(--border)', 'background': '#fafafa' });
    }
    $('#export-no-section-warn').hide();
    updateFilterBadge();
  });

  $('#export-check-all').on('click', function () {
    $('input[name="export-section"]').prop('checked', true).trigger('change');
  });
  $('#export-uncheck-all').on('click', function () {
    $('input[name="export-section"]').prop('checked', false).trigger('change');
  });

  // Date range hint + badge
  $('#export-date-from, #export-date-to').on('change', updateFilterBadge);
  $('#clear-date-range').on('click', function () {
    $('#export-date-from, #export-date-to').val('');
    updateFilterBadge();
  });

  function updateFilterBadge() {
    const from  = $('#export-date-from').val();
    const to    = $('#export-date-to').val();
    const sects = $('input[name="export-section"]:checked').length;
    const total = $('input[name="export-section"]').length;

    if (from || to) {
      let hint = 'Filtering records: ';
      if (from && to)  hint += from + ' → ' + to;
      else if (from)   hint += 'from ' + from;
      else             hint += 'up to ' + to;
      hint += ' · ' + sects + ' of ' + total + ' sections selected';
      $('#date-range-hint-text').text(hint);
      $('#date-range-hint').show();
      $('#export-active-filter-badge').show();
      $('#export-filter-summary').text(hint);
    } else {
      $('#date-range-hint').hide();
      $('#export-active-filter-badge').hide();
    }
  }

  /* ──────────────────────────────────────────────────────────
     DATE FILTER HELPER
     Each module has a primary date field used for filtering.
  ────────────────────────────────────────────────────────── */
  function applyDateFilter(rows, dateField, from, to) {
    if (!from && !to) return rows;
    return rows.filter(r => {
      const d = r[dateField];
      if (!d) return false;   // exclude rows with no date when filter active
      if (from && d < from) return false;
      if (to   && d > to)   return false;
      return true;
    });
  }

  // Primary date field per module (used for date-range filtering)
  const DATE_FIELD = {
    audits       : 'scheduled_date',
    tasks        : 'due_date',
    kpis         : null,           // KPIs use period_year; skip row-level filter
    surveys      : 'start_date',
    action_plans : 'target_date',
    standards    : 'effective_date',
  };

  /* ──────────────────────────────────────────────────────────
     CONFIRM EXPORT — dispatch to PDF or Excel
  ────────────────────────────────────────────────────────── */
  $('#confirm-export-btn').on('click', async function () {
    // Validate at least one section
    const selectedSections = $('input[name="export-section"]:checked').map(function () {
      return $(this).val();
    }).get();

    if (!selectedSections.length) {
      $('#export-no-section-warn').show();
      return;
    }

    const format  = $('input[name="export-format"]:checked').val();
    const dateFrom = $('#export-date-from').val();   // 'YYYY-MM-DD' or ''
    const dateTo   = $('#export-date-to').val();

    const btn = this;
    btnLoading(btn, 'Generating…');
    closeExportModal();

    try {
      if (format === 'pdf') {
        await runPdfExport(selectedSections, dateFrom, dateTo);
      } else {
        await runExcelExport(selectedSections, dateFrom, dateTo);
      }
    } catch (e) {
      if (typeof toast !== 'undefined') toast.error('Export failed: ' + e);
    }

    btnReset(btn);
  });

  /* ──────────────────────────────────────────────────────────
     PDF EXPORT
  ────────────────────────────────────────────────────────── */
  async function runPdfExport(selectedSections, dateFrom, dateTo) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
    const now = new Date().toLocaleDateString('en-PH', { year:'numeric', month:'long', day:'numeric' });
    const accentFill = [45, 90, 61];

    let pageNum = 1;
    let firstPage = true;

    const dateLabel = (dateFrom || dateTo)
      ? '  |  Date range: ' + (dateFrom || '—') + ' → ' + (dateTo || '—')
      : '';

    function pageHeader(title) {
      doc.setFillColor(...accentFill);
      doc.rect(0, 0, 297, 18, 'F');
      doc.setTextColor(255, 255, 255);
      doc.setFontSize(10); doc.setFont('helvetica', 'bold');
      doc.text('QA Management System — Reports Dashboard', 10, 8);
      doc.setFontSize(8); doc.setFont('helvetica', 'normal');
      doc.text('Generated: ' + now + dateLabel, 10, 13.5);
      doc.setTextColor(0, 0, 0);
      doc.setFontSize(13); doc.setFont('helvetica', 'bold');
      doc.text(title, 10, 26);
      doc.setFont('helvetica', 'normal');
    }

    function pageFooter() {
      doc.setFontSize(7); doc.setTextColor(160);
      doc.text('Page ' + pageNum, 287, 205, { align: 'right' });
      doc.setTextColor(0);
    }

    function addPage(title) {
      if (!firstPage) { doc.addPage(); pageNum++; }
      firstPage = false;
      pageHeader(title);
    }

    // ── Summary ──
    if (selectedSections.includes('summary')) {
      const sum = cache['summary'] || await fetchReport('summary');
      addPage('Executive Summary');
      doc.autoTable({
        head: [['Module', 'Total', 'Key Metric', 'Value']],
        body: [
          ['Audits',          sum.audits.total,    'Completed',       sum.audits.by_status['Completed']    ?? 0],
          ['Audit Tasks',     sum.tasks.total,     'Pending',         sum.tasks.by_status['Pending']       ?? 0],
          ['Action Plans',    sum.plans.total,     'Open',            sum.plans.by_status['Open']          ?? 0],
          ['KPI Indicators',  sum.kpis.total,      'Meeting Target',  sum.kpis.meeting_target],
          ['KPI Avg Value',   sum.kpis.avg ?? '—', '—',              '—'],
          ['Surveys',         sum.surveys.total,   'Responses',       sum.surveys.total_responses],
          ['Active Standards',sum.standards.active,'Active Policies', sum.standards.policies],
        ],
        startY: 30, styles: { fontSize: 9 }, headStyles: { fillColor: accentFill },
      });
      pageFooter();
    }

    // ── Section definitions ──
    const sectionDefs = [
      { action:'audits',       title:'Audits Report',
        head:['ID','Title','Type','Scheduled','Completion','Status','Done/Total','%'],
        dateField: DATE_FIELD.audits,
        rows: d => d.map(r => [r.audit_id, r.title, r.audit_type, r.scheduled_date||'—', r.completion_date||'—', r.status, `${r.completed_tasks}/${r.total_tasks}`, r.progress_pct+'%']) },
      { action:'tasks',        title:'Accreditation Tasks',
        head:['ID','Title','Audit','Standard','Due Date','Status','Remarks'],
        dateField: DATE_FIELD.tasks,
        rows: d => d.map(r => [r.task_id, r.title, r.audit_title||'—', r.standard_title||'—', r.due_date||'—', r.status, r.remarks||'—']) },
      { action:'kpis',         title:'KPI Indicators',
        head:['ID','Indicator','Category','Unit','Target','Actual','Period','Meets Target'],
        dateField: null,
        rows: d => d.map(r => [r.indicator_id, r.name, r.category||'—', r.unit||'—', r.target_value??'—', r.latest_value??'—', r.latest_period||'—', r.meets_target===null?'N/A':r.meets_target?'Yes':'No']) },
      { action:'surveys',      title:'Surveys Report',
        head:['ID','Title','Target Group','Start','End','Status','Questions','Responses'],
        dateField: DATE_FIELD.surveys,
        rows: d => d.map(r => [r.survey_id, r.title, r.target_group, r.start_date||'—', r.end_date||'—', r.status, r.questions_count, r.responses_count]) },
      { action:'action_plans', title:'Action Plans',
        head:['ID','Title','Audit','Root Cause','Target Date','Status','Resolution'],
        dateField: DATE_FIELD.action_plans,
        rows: d => d.map(r => [r.plan_id, r.title, r.audit_title||'—', r.root_cause||'—', r.target_date||'—', r.status, r.resolution||'—']) },
      { action:'standards',    title:'Standards & Policies',
        head:['ID','Title','Body','Version','Effective','Status','Active Policies','Linked Tasks'],
        dateField: DATE_FIELD.standards,
        rows: d => d.map(r => [r.standard_id, r.title, r.body, r.version||'—', r.effective_date||'—', r.status, `${r.active_policies}/${r.total_policies}`, r.linked_tasks]) },
    ];

    for (const s of sectionDefs) {
      if (!selectedSections.includes(s.action)) continue;
      let data = cache[s.action] || await fetchReport(s.action);
      if (s.dateField) data = applyDateFilter(data, s.dateField, dateFrom, dateTo);
      addPage(s.title + (s.dateField && (dateFrom||dateTo) ? ` (${data.length} record${data.length!==1?'s':''} in range)` : ''));
      if (data.length) {
        doc.autoTable({ head: [s.head], body: s.rows(data), startY: 30, styles: { fontSize: 8 }, headStyles: { fillColor: accentFill } });
      } else {
        doc.setFontSize(9); doc.setTextColor(120);
        doc.text('No records match the selected date range.', 10, 36);
        doc.setTextColor(0);
      }
      pageFooter();
    }

    doc.save('QA_Reports_' + new Date().toISOString().slice(0,10) + '.pdf');
    if (typeof toast !== 'undefined') toast.success('PDF exported successfully.', 'Export');
  }

  /* ──────────────────────────────────────────────────────────
     EXCEL EXPORT
  ────────────────────────────────────────────────────────── */
  async function runExcelExport(selectedSections, dateFrom, dateTo) {
    const wb   = XLSX.utils.book_new();
    const now  = new Date().toLocaleDateString('en-PH', { year:'numeric', month:'long', day:'numeric' });

    function addSheet(name, headers, rows) {
      const ws = XLSX.utils.aoa_to_sheet([headers, ...rows]);
      // Auto-width: roughly base column width on longest cell
      const colWidths = headers.map((h, i) => {
        const maxLen = Math.max(h.length, ...rows.map(r => String(r[i] ?? '').length));
        return { wch: Math.min(Math.max(maxLen + 2, 10), 50) };
      });
      ws['!cols'] = colWidths;
      XLSX.utils.book_append_sheet(wb, ws, name);
    }

    const dateRangeNote = (dateFrom || dateTo)
      ? `Date range: ${dateFrom || 'any'} → ${dateTo || 'any'}`
      : 'No date filter applied';

    // ── Summary sheet ──
    if (selectedSections.includes('summary')) {
      const sum = cache['summary'] || await fetchReport('summary');
      addSheet('Summary', ['Module','Total','Key Metric','Value'], [
        ['Report Generated', now, 'Filter', dateRangeNote],
        ['', '', '', ''],
        ['Audits',          sum.audits.total,    'Completed',       sum.audits.by_status['Completed']??0],
        ['Audit Tasks',     sum.tasks.total,     'Pending',         sum.tasks.by_status['Pending']??0],
        ['Action Plans',    sum.plans.total,     'Open',            sum.plans.by_status['Open']??0],
        ['KPI Indicators',  sum.kpis.total,      'Meeting Target',  sum.kpis.meeting_target],
        ['KPI Avg Value',   sum.kpis.avg??'',    '',                ''],
        ['Surveys',         sum.surveys.total,   'Responses',       sum.surveys.total_responses],
        ['Active Standards',sum.standards.active,'Active Policies', sum.standards.policies],
      ]);
    }

    if (selectedSections.includes('audits')) {
      let data = cache['audits'] || await fetchReport('audits');
      data = applyDateFilter(data, DATE_FIELD.audits, dateFrom, dateTo);
      addSheet('Audits',
        ['ID','Title','Type','Scheduled','Completion','Status','Completed Tasks','Total Tasks','Progress %','Notes'],
        data.map(r => [r.audit_id, r.title, r.audit_type, r.scheduled_date||'', r.completion_date||'', r.status, r.completed_tasks, r.total_tasks, r.progress_pct, r.notes||''])
      );
    }

    if (selectedSections.includes('tasks')) {
      let data = cache['tasks'] || await fetchReport('tasks');
      data = applyDateFilter(data, DATE_FIELD.tasks, dateFrom, dateTo);
      addSheet('Tasks',
        ['ID','Title','Audit','Standard Body','Standard','Due Date','Status','Remarks'],
        data.map(r => [r.task_id, r.title, r.audit_title||'', r.standard_body||'', r.standard_title||'', r.due_date||'', r.status, r.remarks||''])
      );
    }

    if (selectedSections.includes('kpis')) {
      const data = cache['kpis'] || await fetchReport('kpis');
      // KPIs: filter individual records by period year if date range given
      const filteredKpis = data.map(ind => {
        if (!dateFrom && !dateTo) return ind;
        const filteredRecords = (ind.records || []).filter(rec => {
          const y = String(rec.period_year || '');
          if (dateFrom && y < dateFrom.slice(0,4)) return false;
          if (dateTo   && y > dateTo.slice(0,4))   return false;
          return true;
        });
        return { ...ind, records: filteredRecords };
      }).filter(ind => !ind.records || ind.records.length > 0 || (!dateFrom && !dateTo));
      addSheet('KPIs',
        ['ID','Indicator','Category','Unit','Target','Latest Value','Period','Meets Target'],
        filteredKpis.map(r => [r.indicator_id, r.name, r.category||'', r.unit||'', r.target_value??'', r.latest_value??'', r.latest_period||'', r.meets_target===null?'N/A':r.meets_target?'Yes':'No'])
      );
    }

    if (selectedSections.includes('surveys')) {
      let data = cache['surveys'] || await fetchReport('surveys');
      data = applyDateFilter(data, DATE_FIELD.surveys, dateFrom, dateTo);
      addSheet('Surveys',
        ['ID','Title','Target Group','Start Date','End Date','Status','Questions','Responses','Created By'],
        data.map(r => [r.survey_id, r.title, r.target_group, r.start_date||'', r.end_date||'', r.status, r.questions_count, r.responses_count, r.creator_name||''])
      );
    }

    if (selectedSections.includes('action_plans')) {
      let data = cache['action_plans'] || await fetchReport('action_plans');
      data = applyDateFilter(data, DATE_FIELD.action_plans, dateFrom, dateTo);
      addSheet('Action Plans',
        ['ID','Title','Related Audit','Audit Type','Root Cause','Target Date','Status','Resolution','Created Date'],
        data.map(r => [r.plan_id, r.title, r.audit_title||'', r.audit_type||'', r.root_cause||'', r.target_date||'', r.status, r.resolution||'', r.created_date||''])
      );
    }

    if (selectedSections.includes('standards')) {
      let data = cache['standards'] || await fetchReport('standards');
      data = applyDateFilter(data, DATE_FIELD.standards, dateFrom, dateTo);
      addSheet('Standards',
        ['ID','Title','Body','Version','Effective Date','Status','Active Policies','Total Policies','Linked Tasks'],
        data.map(r => [r.standard_id, r.title, r.body, r.version||'', r.effective_date||'', r.status, r.active_policies, r.total_policies, r.linked_tasks])
      );
    }

    XLSX.writeFile(wb, 'QA_Reports_' + new Date().toISOString().slice(0,10) + '.xlsx');
    if (typeof toast !== 'undefined') toast.success('Excel exported successfully.', 'Export');
  }

  /* ──────────────────────────────────────────────────────────
     INIT — load default tab
  ────────────────────────────────────────────────────────── */
  loadSummary();

});
</script>
</body>
</html>