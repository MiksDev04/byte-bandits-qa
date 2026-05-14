<?php
session_start();

// Check authentication
if (empty($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'QA System User Manual';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - QA System</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/styles.css">

    <style>
        /* ── Manual Page Specific Styles ───────────────────────── */
        
        /* Table of Contents Panel */
        .toc-panel {
            width: 260px;
            flex-shrink: 0;
            position: sticky;
            top: var(--header-h);
            height: calc(100vh - var(--header-h));
            overflow-y: auto;
            background: var(--bg-card);
            border-right: 1px solid var(--border);
            padding: 28px 0;
            z-index: 50;
        }

        .toc-label {
            font-size: .67rem;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--text-muted);
            padding: 0 20px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .toc-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-primary);
            padding: 0 20px 20px;
            line-height: 1.3;
            letter-spacing: -.3px;
            border-bottom: 1px solid var(--border-light);
            margin-bottom: 8px;
        }

        .toc-nav {
            padding: 4px 0;
        }

        .toc-nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: .85rem;
            font-weight: 500;
            border-left: 3px solid transparent;
            transition: all var(--transition);
            position: relative;
        }

        .toc-nav a:hover {
            background: var(--bg-main);
            color: var(--text-primary);
        }

        .toc-nav a.active {
            background: var(--primary-light);
            color: var(--primary);
            font-weight: 600;
            border-left-color: var(--primary);
        }

        .toc-nav a .toc-num {
            font-size: .7rem;
            font-weight: 700;
            color: var(--primary);
            background: var(--primary-light);
            width: 22px;
            height: 22px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .toc-nav a.active .toc-num {
            background: var(--primary);
            color: #fff;
        }

        .toc-nav a i {
            width: 16px;
            text-align: center;
            font-size: .85rem;
            opacity: .7;
        }

        .toc-nav a.active i {
            opacity: 1;
        }

        .toc-footer {
            margin-top: auto;
            padding: 16px 20px 0;
            border-top: 1px solid var(--border-light);
            font-size: .7rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        /* Manual Layout */
        .manual-layout {
            display: flex;
            min-height: calc(100vh - var(--header-h));
        }

        .manual-body {
            flex: 1;
            min-width: 0;
            background: var(--bg-main);
        }

        .manual-content {
            max-width: 860px;
            margin: 0 auto;
            padding: 40px 48px 80px;
        }

        /* Cover Block */
        .manual-cover {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 40px 44px 36px;
            margin-bottom: 40px;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
        }

        .manual-cover::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent-blue), var(--accent-green));
        }

        .cover-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary-light);
            border-radius: 20px;
            padding: 5px 14px;
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--primary);
            margin-bottom: 20px;
        }

        .cover-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.2;
            margin: 0 0 8px;
            letter-spacing: -.5px;
        }

        .cover-subtitle {
            font-size: .88rem;
            color: var(--text-secondary);
            margin: 0 0 24px;
        }

        .cover-meta {
            display: flex;
            gap: 28px;
            flex-wrap: wrap;
        }

        .cover-meta-item {
            font-size: .75rem;
            color: var(--text-muted);
        }

        .cover-meta-item strong {
            display: block;
            font-size: .82rem;
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 2px;
        }

        /* Sections */
        .manual-section {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 32px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
            animation: fadeUp .4s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .manual-section:nth-child(1) { animation-delay: .05s; }
        .manual-section:nth-child(2) { animation-delay: .1s; }
        .manual-section:nth-child(3) { animation-delay: .15s; }
        .manual-section:nth-child(4) { animation-delay: .2s; }
        .manual-section:nth-child(5) { animation-delay: .25s; }
        .manual-section:nth-child(6) { animation-delay: .3s; }

        .section-header {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-light);
        }

        .section-num {
            font-size: .75rem;
            font-weight: 700;
            color: var(--primary);
            background: var(--primary-light);
            border-radius: 6px;
            padding: 6px 12px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .section-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
            color: #fff;
        }

        .icon-purple { background: var(--primary); }
        .icon-blue   { background: var(--accent-blue); }
        .icon-orange { background: var(--accent-orange); }
        .icon-green  { background: var(--accent-green); }
        .icon-slate  { background: #4a5568; }
        .icon-rose   { background: #e11d48; }

        .section-title-wrap h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 4px;
            letter-spacing: -.3px;
        }

        .section-title-wrap p {
            font-size: .8rem;
            color: var(--text-muted);
            margin: 0;
        }

        .section-body p {
            color: var(--text-secondary);
            margin-bottom: 16px;
            font-size: .9rem;
            line-height: 1.6;
        }

        /* Info Box */
        .info-box {
            background: var(--bg-main);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 22px 24px;
            margin-bottom: 16px;
        }

        .info-box h5 {
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-light);
        }

        .info-box ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .info-box ul li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 7px 0;
            border-bottom: 1px solid var(--border-light);
            font-size: .88rem;
            color: var(--text-secondary);
        }

        .info-box ul li:last-child {
            border-bottom: none;
        }

        .info-box ul li::before {
            content: '▸';
            color: var(--primary);
            font-size: .75rem;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .info-cols {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        @media (max-width: 640px) {
            .info-cols {
                grid-template-columns: 1fr;
            }
        }

        /* KPI Table */
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: .85rem;
        }

        .kpi-table thead th {
            background: var(--primary);
            color: #fff;
            padding: 10px 14px;
            font-weight: 600;
            font-size: .72rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            text-align: left;
        }

        .kpi-table thead th:first-child {
            border-radius: 6px 0 0 0;
        }

        .kpi-table thead th:last-child {
            border-radius: 0 6px 0 0;
        }

        .kpi-table tbody tr {
            border-bottom: 1px solid var(--border-light);
            transition: background var(--transition);
        }

        .kpi-table tbody tr:hover {
            background: var(--primary-xlight);
        }

        .kpi-table tbody td {
            padding: 11px 14px;
            color: var(--text-secondary);
            vertical-align: top;
        }

        .kpi-table tbody td:first-child {
            font-weight: 600;
            color: var(--text-primary);
        }

        /* Integration Cards */
        .integration-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 16px;
        }

        @media (max-width: 640px) {
            .integration-grid {
                grid-template-columns: 1fr;
            }
        }

        .int-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px 20px;
            text-align: center;
            transition: box-shadow var(--transition), transform var(--transition);
        }

        .int-card:hover {
            box-shadow: var(--shadow);
            transform: translateY(-2px);
        }

        .int-card .int-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #fff;
            margin: 0 auto 14px;
        }

        .int-card h6 {
            font-weight: 700;
            font-size: .9rem;
            color: var(--text-primary);
            margin-bottom: 6px;
        }

        .int-card p {
            font-size: .8rem;
            color: var(--text-muted);
            margin: 0;
            line-height: 1.5;
        }

        .int-note {
            background: var(--primary-light);
            border-left: 3px solid var(--primary);
            padding: 14px 18px;
            border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
            font-size: .82rem;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* FAQ */
        .faq-item {
            background: var(--bg-main);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            margin-bottom: 10px;
            overflow: hidden;
            transition: border-color var(--transition);
        }

        .faq-item:hover {
            border-color: var(--primary);
        }

        .faq-item.open {
            border-color: var(--primary);
            background: var(--bg-card);
        }

        .faq-q {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 20px;
            cursor: pointer;
            user-select: none;
            transition: background var(--transition);
        }

        .faq-q:hover {
            background: var(--primary-xlight);
        }

        .faq-q-num {
            font-size: .75rem;
            font-weight: 700;
            color: var(--primary);
            background: var(--primary-light);
            border-radius: 6px;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .faq-item.open .faq-q-num {
            background: var(--primary);
            color: #fff;
        }

        .faq-q strong {
            font-size: .9rem;
            font-weight: 600;
            color: var(--text-primary);
            flex: 1;
        }

        .faq-q .faq-arrow {
            color: var(--text-muted);
            font-size: .75rem;
            transition: transform .2s;
        }

        .faq-item.open .faq-arrow {
            transform: rotate(180deg);
            color: var(--primary);
        }

        .faq-a {
            display: none;
            padding: 0 20px 16px 64px;
            font-size: .85rem;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .faq-item.open .faq-a {
            display: block;
        }

        /* Divider */
        .manual-divider {
            display: flex;
            align-items: center;
            gap: 16px;
            margin: 8px 0 32px;
            color: var(--text-muted);
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .1em;
            font-weight: 600;
        }

        .manual-divider::before,
        .manual-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* Scrollbar for TOC */
        .toc-panel::-webkit-scrollbar {
            width: 4px;
        }

        .toc-panel::-webkit-scrollbar-track {
            background: transparent;
        }

        .toc-panel::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 2px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .toc-panel {
                display: none;
            }

            .manual-content {
                padding: 24px 20px 60px;
            }

            .manual-cover {
                padding: 28px 24px;
            }

            .cover-title {
                font-size: 1.5rem;
            }

            .manual-section {
                padding: 24px 20px;
            }
        }
    </style>
</head>

<body>

<div class="qa-wrapper">
    <?php include '../partials/sidebar.php'; ?>

    <div class="qa-content">
        <?php include '../partials/header.php'; ?>

        <div class="manual-layout">

            <!-- TABLE OF CONTENTS -->
            <nav class="toc-panel">
                <div class="toc-label">
                    <i class="fa-solid fa-list"></i>
                    Table of Contents
                </div>
                <div class="toc-title">QA System<br>User Manual</div>
                <div class="toc-nav">
                    <a href="#overview" class="active">
                        <span class="toc-num">1</span>
                        <i class="fa-solid fa-circle-info"></i>
                        System Overview
                    </a>
                    <a href="#survey">
                        <span class="toc-num">2</span>
                        <i class="fa-solid fa-clipboard-question"></i>
                        Survey Form Module
                    </a>
                    <a href="#kpi">
                        <span class="toc-num">3</span>
                        <i class="fa-solid fa-chart-line"></i>
                        KPI Records
                    </a>
                    <a href="#action">
                        <span class="toc-num">4</span>
                        <i class="fa-solid fa-briefcase"></i>
                        Action Plans
                    </a>
                    <a href="#integration">
                        <span class="toc-num">5</span>
                        <i class="fa-solid fa-plug"></i>
                        System Integration
                    </a>
                    <a href="#faq">
                        <span class="toc-num">6</span>
                        <i class="fa-solid fa-circle-question"></i>
                        FAQ
                    </a>
                </div>
                <div class="toc-footer">
                    QA System &copy; <?= date('Y') ?><br>
                    For internal use only
                </div>
            </nav>

            <!-- MAIN CONTENT -->
            <div class="manual-body">
                <div class="manual-content">

                    <!-- COVER -->
                    <div class="manual-cover">
                        <div class="cover-badge">
                            <i class="fa-solid fa-book"></i>
                            Official Documentation
                        </div>
                        <h1 class="cover-title">QA System User Manual</h1>
                        <p class="cover-subtitle">Survey &nbsp;•&nbsp; KPI &nbsp;•&nbsp; Action Plans &nbsp;•&nbsp; LMS &nbsp;•&nbsp; HRIS Integration</p>
                        <div class="cover-meta">
                            <div class="cover-meta-item">
                                <strong>Document Type</strong>
                                User Manual
                            </div>
                            <div class="cover-meta-item">
                                <strong>Audience</strong>
                                All System Users
                            </div>
                            <div class="cover-meta-item">
                                <strong>Last Updated</strong>
                                <?= date('F Y') ?>
                            </div>
                        </div>
                    </div>

                    <!-- §1 SYSTEM OVERVIEW -->
                    <div class="manual-section" id="overview">
                        <div class="section-header">
                            <span class="section-num">§1</span>
                            <div class="section-icon icon-purple">
                                <i class="fa-solid fa-circle-info"></i>
                            </div>
                            <div class="section-title-wrap">
                                <h2>System Overview</h2>
                                <p>Platform scope &amp; module summary</p>
                            </div>
                        </div>
                        <div class="section-body">
                            <p>
                                The QA System is a centralized platform for managing quality assurance processes including surveys,
                                KPIs, audits, reports, and integration with LMS, Faculty Evaluation, and HRIS systems.
                            </p>
                            <div class="info-cols">
                                <div class="info-box">
                                    <h5>Core Modules</h5>
                                    <ul>
                                        <li>Dashboard &amp; Reports</li>
                                        <li>Standards, Policies, Audits</li>
                                        <li>Survey Form Module (User Responses)</li>
                                        <li>KPI Records Management</li>
                                        <li>Action Plans (HRIS Integration)</li>
                                        <li>System Integration Viewer</li>
                                    </ul>
                                </div>
                                <div class="info-box">
                                    <h5>External Systems</h5>
                                    <ul>
                                        <li>Learning Management System (LMS)</li>
                                        <li>Faculty Evaluation System</li>
                                        <li>Human Resources Information System (HRIS)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="manual-divider">Section Break</div>

                    <!-- §2 SURVEY -->
                    <div class="manual-section" id="survey">
                        <div class="section-header">
                            <span class="section-num">§2</span>
                            <div class="section-icon icon-blue">
                                <i class="fa-solid fa-clipboard-question"></i>
                            </div>
                            <div class="section-title-wrap">
                                <h2>Survey Form Module</h2>
                                <p>Respondent types &amp; response workflow</p>
                            </div>
                        </div>
                        <div class="section-body">
                            <p>
                                Users can answer surveys which are used for quality analysis and KPI computation.
                            </p>
                            <div class="info-cols">
                                <div class="info-box">
                                    <h5>Users</h5>
                                    <ul>
                                        <li>Students</li>
                                        <li>Alumni</li>
                                        <li>Employers</li>
                                        <li>Faculty</li>
                                        <li>Staff</li>
                                    </ul>
                                </div>
                                <div class="info-box">
                                    <h5>Process</h5>
                                    <ul>
                                        <li>Submit survey responses</li>
                                        <li>Stored in QA database</li>
                                        <li>Used for KPI generation</li>
                                        <li>Included in reports</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="manual-divider">Section Break</div>

                    <!-- §3 KPI -->
                    <div class="manual-section" id="kpi">
                        <div class="section-header">
                            <span class="section-num">§3</span>
                            <div class="section-icon icon-orange">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                            <div class="section-title-wrap">
                                <h2>KPI Records</h2>
                                <p>Data sources &amp; analytics storage</p>
                            </div>
                        </div>
                        <div class="section-body">
                            <p>KPI Records are generated from LMS and Faculty Evaluation data.</p>
                            <div class="info-box">
                                <h5>Data Sources</h5>
                                <table class="kpi-table">
                                    <thead>
                                        <tr>
                                            <th>Source</th>
                                            <th>Data Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>LMS Reports</td>
                                            <td>Grades, Completion, Performance</td>
                                        </tr>
                                        <tr>
                                            <td>Faculty Evaluation</td>
                                            <td>Teaching Performance Scores</td>
                                        </tr>
                                        <tr>
                                            <td>Survey Results</td>
                                            <td>Aggregated Stakeholder Feedback</td>
                                        </tr>
                                        <tr>
                                            <td>QA Database</td>
                                            <td>Stored KPI Analytics</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="manual-divider">Section Break</div>

                    <!-- §4 ACTION PLANS -->
                    <div class="manual-section" id="action">
                        <div class="section-header">
                            <span class="section-num">§4</span>
                            <div class="section-icon icon-green">
                                <i class="fa-solid fa-briefcase"></i>
                            </div>
                            <div class="section-title-wrap">
                                <h2>Action Plans</h2>
                                <p>QA findings &amp; HRIS improvement tracking</p>
                            </div>
                        </div>
                        <div class="section-body">
                            <p>
                                Action Plans are created based on QA findings and KPI results.
                                These are sent to HRIS for training and improvement tracking.
                            </p>
                            <div class="info-box">
                                <h5>Workflow Steps</h5>
                                <ul>
                                    <li>Create improvement strategies</li>
                                    <li>Send to HRIS system</li>
                                    <li>Track implementation progress</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="manual-divider">Section Break</div>

                    <!-- §5 INTEGRATION -->
                    <div class="manual-section" id="integration">
                        <div class="section-header">
                            <span class="section-num">§5</span>
                            <div class="section-icon icon-slate">
                                <i class="fa-solid fa-plug"></i>
                            </div>
                            <div class="section-title-wrap">
                                <h2>System Integration</h2>
                                <p>Connected external platforms</p>
                            </div>
                        </div>
                        <div class="section-body">
                            <div class="integration-grid">
                                <div class="int-card">
                                    <div class="int-icon icon-purple">
                                        <i class="fa-solid fa-graduation-cap"></i>
                                    </div>
                                    <h6>LMS</h6>
                                    <p>Student performance data</p>
                                </div>
                                <div class="int-card">
                                    <div class="int-icon icon-blue">
                                        <i class="fa-solid fa-chalkboard-user"></i>
                                    </div>
                                    <h6>Faculty Eval</h6>
                                    <p>Teaching performance scores</p>
                                </div>
                                <div class="int-card">
                                    <div class="int-icon icon-green">
                                        <i class="fa-solid fa-users-gear"></i>
                                    </div>
                                    <h6>HRIS</h6>
                                    <p>Training &amp; development plans</p>
                                </div>
                            </div>
                            <div class="int-note">
                                <i class="fa-solid fa-shield-halved"></i>
                                Data is synchronized through secure API endpoints.
                            </div>
                        </div>
                    </div>

                    <div class="manual-divider">Section Break</div>

                    <!-- §6 FAQ -->
                    <div class="manual-section" id="faq">
                        <div class="section-header">
                            <span class="section-num">§6</span>
                            <div class="section-icon icon-rose">
                                <i class="fa-solid fa-circle-question"></i>
                            </div>
                            <div class="section-title-wrap">
                                <h2>FAQ</h2>
                                <p>Frequently asked questions</p>
                            </div>
                        </div>
                        <div class="section-body">
                            <div class="faq-item open" data-faq="1">
                                <div class="faq-q">
                                    <span class="faq-q-num">1</span>
                                    <strong>Who can answer surveys?</strong>
                                    <i class="fa-solid fa-chevron-down faq-arrow"></i>
                                </div>
                                <div class="faq-a">Students, Alumni, Employers, Faculty, and Staff can all participate in surveys relevant to their role.</div>
                            </div>
                            <div class="faq-item" data-faq="2">
                                <div class="faq-q">
                                    <span class="faq-q-num">2</span>
                                    <strong>Where does KPI data come from?</strong>
                                    <i class="fa-solid fa-chevron-down faq-arrow"></i>
                                </div>
                                <div class="faq-a">KPI data is automatically pulled from the LMS and Faculty Evaluation System through API integration.</div>
                            </div>
                            <div class="faq-item" data-faq="3">
                                <div class="faq-q">
                                    <span class="faq-q-num">3</span>
                                    <strong>What happens to Action Plans?</strong>
                                    <i class="fa-solid fa-chevron-down faq-arrow"></i>
                                </div>
                                <div class="faq-a">Action Plans are sent to HRIS for execution and tracking of improvement initiatives.</div>
                            </div>
                        </div>
                    </div>

                </div><!-- /.manual-content -->
            </div><!-- /.manual-body -->
        </div><!-- /.manual-layout -->
    </div><!-- /.qa-content -->
</div><!-- /.qa-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // FAQ accordion
    document.querySelectorAll('.faq-q').forEach(q => {
        q.addEventListener('click', () => {
            const item = q.closest('.faq-item');
            
            // Close other open items
            document.querySelectorAll('.faq-item.open').forEach(openItem => {
                if (openItem !== item) {
                    openItem.classList.remove('open');
                }
            });
            
            item.classList.toggle('open');
        });
    });

    // TOC active state on scroll
    const sections = document.querySelectorAll('.manual-section[id]');
    const navLinks = document.querySelectorAll('.toc-nav a');

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                navLinks.forEach(a => a.classList.remove('active'));
                const active = document.querySelector(`.toc-nav a[href="#${entry.target.id}"]`);
                if (active) active.classList.add('active');
            }
        });
    }, { 
        rootMargin: '-10% 0px -70% 0px',
        threshold: 0 
    });

    sections.forEach(s => observer.observe(s));

    // Smooth scroll for TOC links
    navLinks.forEach(a => {
        a.addEventListener('click', e => {
            e.preventDefault();
            const target = document.querySelector(a.getAttribute('href'));
            if (target) {
                const headerOffset = 80;
                const elementPosition = target.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
</script>
</body>
</html>