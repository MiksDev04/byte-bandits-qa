<?php
session_start();

// Check authentication
if (empty($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Standards & Policies';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-api-key" content="<?= htmlspecialchars(getenv('APP_API_KEY'), ENT_QUOTES, 'UTF-8') ?>">

    <title><?= htmlspecialchars($pageTitle) ?> - QA System</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/styles.css">

    <style>
        .standard-card {
            transition: all 0.2s ease;
            border-left: 3px solid var(--primary);
        }

        .standard-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .policy-item {
            transition: all 0.2s ease;
            border-left: 2px solid var(--border);
        }

        .policy-item:hover {
            background: var(--primary-xlight);
            border-left-color: var(--primary);
        }

        .nav-tabs .nav-link {
            color: var(--text-secondary);
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .tab-pane {
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .filter-bar {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 12px 16px;
            margin-bottom: 20px;
        }

        /* ── PDF Upload Widget ── */
        .pdf-upload-zone {
            border: 2px dashed var(--border);
            border-radius: var(--radius);
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
            background: var(--bg-page, #f9fafb);
            position: relative;
        }

        .pdf-upload-zone:hover,
        .pdf-upload-zone.dragover {
            border-color: var(--primary);
            background: var(--primary-xlight, #eef2ff);
        }

        .pdf-upload-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .pdf-upload-zone .upload-icon {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 6px;
        }

        .pdf-upload-zone .upload-label {
            font-size: .85rem;
            color: var(--text-secondary);
        }

        .pdf-upload-zone .upload-label strong {
            color: var(--primary);
        }

        .pdf-file-preview {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: var(--radius);
            background: var(--primary-xlight, #eef2ff);
            border: 1px solid var(--primary-light, #c7d2fe);
            margin-top: 8px;
        }

        .pdf-file-preview .pdf-name {
            flex: 1;
            font-size: .85rem;
            font-weight: 500;
            color: var(--text-primary);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .pdf-file-preview .btn-remove-pdf {
            background: none;
            border: none;
            color: var(--accent-orange, #f97316);
            cursor: pointer;
            padding: 0 4px;
            font-size: .9rem;
            flex-shrink: 0;
        }

        .pdf-upload-progress {
            margin-top: 8px;
            display: none;
        }

        .pdf-upload-progress .progress {
            height: 6px;
            border-radius: 99px;
        }

        .existing-doc-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: .82rem;
            color: var(--primary);
            margin-top: 6px;
            text-decoration: none;
        }

        .existing-doc-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="qa-wrapper">
        <?php include '../partials/sidebar.php'; ?>

        <div class="qa-content">
            <?php include '../partials/header.php'; ?>

            <div class="qa-page">
                <!-- Tabs -->
                <ul class="nav nav-tabs mb-4" id="mainTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="standards-tab" data-bs-toggle="tab" data-bs-target="#standards" type="button" role="tab">
                            <i class="fa-solid fa-book-bookmark me-2"></i>Standards
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="policies-tab" data-bs-toggle="tab" data-bs-target="#policies" type="button" role="tab">
                            <i class="fa-solid fa-file-signature me-2"></i>Policies
                        </button>
                    </li>
                </ul>

                <div class="mb-2">
                    <h2 class="mb-0" style="font-size:1.25rem;font-weight:700;letter-spacing:-.4px;">
                        Standards & Policies
                    </h2>
                    <p class="text-muted-qa mb-0" style="font-size:.83rem; margin-top:2px;">
                        Manage your quality assurance standards and policies in one place. Create, edit, and organize all your QA guidelines to ensure compliance and continuous improvement across your institution.
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                    <div>
                        <button class="btn-primary-qa" id="addStandardBtn" data-bs-toggle="modal" data-bs-target="#standardModal" onclick="resetStandardForm()">
                            <i class="fa-solid fa-plus"></i> Add Standard
                        </button>
                        <button class="btn-primary-qa d-none" id="addPolicyBtn" data-bs-toggle="modal" data-bs-target="#policyModal" onclick="resetPolicyForm()">
                            <i class="fa-solid fa-plus"></i> Add Policy
                        </button>
                    </div>
                    <div class="d-flex gap-2">
                        <div class="header-search" style="width: 250px;">
                            <i class="fa-solid fa-magnifying-glass search-icon"></i>
                            <input type="text" id="searchInput" placeholder="Search standards or policies..." class="form-control-qa" style="padding-left: 34px;">
                        </div>
                        <select id="statusFilter" class="form-control-qa" style="width: 130px;">
                            <option value="all">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Archived">Archived</option>
                        </select>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="standards" role="tabpanel">
                        <div id="standardsList"></div>
                    </div>
                    <div class="tab-pane fade" id="policies" role="tabpanel">
                        <div id="policiesList"></div>
                    </div>
                </div>

            </div><!-- /.qa-page -->
        </div><!-- /.qa-content -->
    </div><!-- /.qa-wrapper -->

    <!-- STANDARD MODAL -->
    <div class="modal fade" id="standardModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: var(--radius-lg);">
                <div class="modal-header" style="border-bottom-color: var(--border-light);">
                    <h5 class="modal-title" style="font-weight: 700;">
                        <i class="fa-solid fa-book-bookmark me-2" style="color: var(--primary);"></i>
                        <span id="standardModalTitle">Add Standard</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="standardForm">
                        <input type="hidden" id="standard_id" name="standard_id">
                        <div class="mb-3">
                            <label class="form-label-qa">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control-qa" id="title" name="title" required>
                            <div class="form-error-msg" id="err-title"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-qa">Body/Type <span class="text-danger">*</span></label>
                            <select class="form-control-qa" id="body" name="body">
                                <option value="CHED">CHED</option>
                                <option value="ISO">ISO</option>
                                <option value="Institutional">Institutional</option>
                                <option value="Other">Other</option>
                            </select>
                            <div class="form-error-msg" id="err-body"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-qa">Description</label>
                            <textarea class="form-control-qa" id="description" name="description" rows="3"></textarea>
                            <div class="form-error-msg" id="err-description"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label-qa">Version</label>
                                <input type="text" class="form-control-qa" id="version" name="version" placeholder="e.g., v1.0">
                                <div class="form-error-msg" id="err-version"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-qa">Effective Date</label>
                                <input type="date" class="form-control-qa" id="effective_date" name="effective_date">
                                <div class="form-error-msg" id="err-effective_date"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-qa">Status</label>
                            <select class="form-control-qa" id="status" name="status">
                                <option value="Active">Active</option>
                                <option value="Archived">Archived</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border-top-color: var(--border-light);">
                    <button type="button" class="btn-outline-qa" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-primary-qa" id="saveStandardBtn">Save Standard</button>
                </div>
            </div>
        </div>
    </div>

    <!-- POLICY MODAL -->
    <div class="modal fade" id="policyModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: var(--radius-lg);">
                <div class="modal-header" style="border-bottom-color: var(--border-light);">
                    <h5 class="modal-title" style="font-weight: 700;">
                        <i class="fa-solid fa-file-signature me-2" style="color: var(--primary);"></i>
                        <span id="policyModalTitle">Add Policy</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="policyForm">
                        <input type="hidden" id="policy_id" name="policy_id">
                        <!-- Hidden field holds the final Cloudinary URL -->
                        <input type="hidden" id="document_url" name="document_url">

                        <div class="mb-3">
                            <label class="form-label-qa">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control-qa" id="policy_title" name="title" required>
                            <div class="form-error-msg" id="err-policy_title"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-qa">Associated Standard</label>
                            <select class="form-control-qa" id="standard_id_select" name="standard_id">
                                <option value="">-- None / General Policy --</option>
                            </select>
                            <div class="form-error-msg" id="err-standard_id"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-qa">Content <span class="text-danger">*</span></label>
                            <textarea class="form-control-qa" id="content" name="content" rows="5" placeholder="Policy content..."></textarea>
                            <div class="form-error-msg" id="err-content"></div>
                        </div>

                        <!-- ── PDF Upload ── -->
                        <div class="mb-3">
                            <label class="form-label-qa">Policy Document <span class="text-muted" style="font-weight:400;">(PDF only, max 10 MB)</span></label>

                            <!-- Existing file link (shown when editing a policy that already has a document) -->
                            <div id="existingDocWrap" class="mb-2" style="display:none;">
                                <a id="existingDocLink" href="#" target="_blank" class="existing-doc-link">
                                    <i class="fa-solid fa-file-pdf"></i>
                                    <span id="existingDocName">Current document</span>
                                    <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:.7rem;"></i>
                                </a>
                                <span class="text-muted ms-2" style="font-size:.78rem;">(upload a new file to replace)</span>
                            </div>

                            <!-- Drop zone -->
                            <div class="pdf-upload-zone" id="pdfDropZone">
                                <input type="file" id="pdfFileInput" accept="application/pdf">
                                <div class="upload-icon"><i class="fa-solid fa-file-arrow-up"></i></div>
                                <div class="upload-label">
                                    <strong>Click to browse</strong> or drag &amp; drop<br>
                                    PDF files only · max 10 MB
                                </div>
                            </div>

                            <!-- Chosen file preview (before upload) -->
                            <div id="pdfFilePreview" class="pdf-file-preview" style="display:none;">
                                <i class="fa-solid fa-file-pdf" style="color:var(--accent-orange,#f97316);font-size:1.2rem;flex-shrink:0;"></i>
                                <span class="pdf-name" id="pdfFileName"></span>
                                <button type="button" class="btn-remove-pdf" id="removePdfBtn" title="Remove file">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>

                            <!-- Upload progress -->
                            <div class="pdf-upload-progress" id="pdfUploadProgress">
                                <div class="d-flex justify-content-between mb-1" style="font-size:.78rem;">
                                    <span>Uploading to Cloudinary…</span>
                                    <span id="pdfUploadPct">0%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar" id="pdfProgressBar" role="progressbar" style="width:0%"></div>
                                </div>
                            </div>

                            <div class="form-error-msg" id="err-document_url"></div>
                        </div>
                        <!-- ── /PDF Upload ── -->

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label-qa">Created Date</label>
                                <input type="date" class="form-control-qa" id="created_date" name="created_date">
                                <div class="form-error-msg" id="err-created_date"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-qa">Status</label>
                                <select class="form-control-qa" id="policy_status" name="status">
                                    <option value="Active">Active</option>
                                    <option value="Archived">Archived</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border-top-color: var(--border-light);">
                    <button type="button" class="btn-outline-qa" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-primary-qa" id="savePolicyBtn">Save Policy</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content" style="border-radius: var(--radius-lg);">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this item?</p>
                    <p class="text-danger small">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-qa" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-primary-qa" id="confirmDeleteBtn" style="background: var(--accent-orange);">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/app.js"></script>
    <script>
        let deleteType = null;
        let deleteId = null;

        // ── Cloudinary config (set these to your own values) ──────────────────────
        const CLOUDINARY_CLOUD_NAME = 'dcumsgzer'; // e.g. 'myapp'
        const CLOUDINARY_UPLOAD_PRESET = 'qa_system'; // unsigned preset name
        // ──────────────────────────────────────────────────────────────────────────

        $(document).ready(function() {
            loadStandards();
            loadPolicies();
            loadStandardsForDropdown();
            updateActionButtons();

            $('#searchInput').on('keyup', function() {
                $('#standards-tab').hasClass('active') ? loadStandards() : loadPolicies();
            });
            $('#statusFilter').on('change', function() {
                $('#standards-tab').hasClass('active') ? loadStandards() : loadPolicies();
            });

            $('#saveStandardBtn').click(saveStandard);
            $('#savePolicyBtn').click(savePolicy);

            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', updateActionButtons);

            // ── PDF upload widget events ──────────────────────────────────────────
            $('#pdfFileInput').on('change', function() {
                handleFileChosen(this.files[0]);
            });

            $('#removePdfBtn').on('click', function() {
                clearPdfSelection();
            });

            // Drag-and-drop highlight
            $('#pdfDropZone').on('dragover dragenter', function(e) {
                e.preventDefault();
                $(this).addClass('dragover');
            }).on('dragleave drop', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
                if (e.type === 'drop') {
                    const file = e.originalEvent.dataTransfer.files[0];
                    if (file) handleFileChosen(file);
                }
            });
        });

        /* ── PDF helpers ────────────────────────────────────────────────────── */
        function handleFileChosen(file) {
            if (!file) return;
            if (file.type !== 'application/pdf') {
                toast.error('Only PDF files are allowed');
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                toast.error('File must be 10 MB or smaller');
                return;
            }
            $('#pdfFileName').text(file.name);
            $('#pdfFilePreview').show();
            $('#pdfDropZone').hide();
            // Store file reference on the input element for later upload
            $('#pdfFileInput')[0]._selectedFile = file;
        }

        function clearPdfSelection() {
            $('#pdfFileInput').val('');
            $('#pdfFileInput')[0]._selectedFile = null;
            $('#pdfFilePreview').hide();
            $('#pdfDropZone').show();
        }

        /**
         * Upload the chosen PDF to Cloudinary via the unsigned upload API.
         * Returns the secure URL string, or null if no file was chosen.
         */
        function uploadPdfToCloudinary() {
            return new Promise(function(resolve, reject) {
                const file = $('#pdfFileInput')[0]._selectedFile;
                if (!file) {
                    resolve(null); // no new file; keep existing URL
                    return;
                }

                const formData = new FormData();
                formData.append('file', file);
                formData.append('upload_preset', CLOUDINARY_UPLOAD_PRESET);
                formData.append('resource_type', 'raw');

                $('#pdfUploadProgress').show();
                $('#pdfProgressBar').css('width', '0%');
                $('#pdfUploadPct').text('0%');

                const xhr = new XMLHttpRequest();
                xhr.open('POST', `https://api.cloudinary.com/v1_1/${CLOUDINARY_CLOUD_NAME}/raw/upload`);

                xhr.upload.onprogress = function(e) {
                    if (e.lengthComputable) {
                        const pct = Math.round((e.loaded / e.total) * 100);
                        $('#pdfProgressBar').css('width', pct + '%');
                        $('#pdfUploadPct').text(pct + '%');
                    }
                };

                xhr.onload = function() {
                    $('#pdfUploadProgress').hide();
                    if (xhr.status === 200) {
                        const resp = JSON.parse(xhr.responseText);
                        resolve(resp.secure_url);
                    } else {
                        reject(new Error('Cloudinary upload failed: ' + xhr.status));
                    }
                };

                xhr.onerror = function() {
                    $('#pdfUploadProgress').hide();
                    reject(new Error('Network error during upload'));
                };

                xhr.send(formData);
            });
        }

        /* ── Tab button visibility ──────────────────────────────────────────── */
        function updateActionButtons() {
            const isStandards = $('#standards-tab').hasClass('active');
            $('#addStandardBtn').toggleClass('d-none', !isStandards);
            $('#addPolicyBtn').toggleClass('d-none', isStandards);
        }

        /* ── Load / render standards ────────────────────────────────────────── */
        function loadStandards() {
            $.ajax({
                url: '../../backend/api/standards_api.php?action=list',
                type: 'GET',
                data: {
                    search: $('#searchInput').val(),
                    status: $('#statusFilter').val()
                },
                dataType: 'json',
                success: function(r) {
                    r.success ? renderStandards(r.data) : $('#standardsList').html('<div class="alert alert-warning">Failed to load standards</div>');
                },
                error: function() {
                    $('#standardsList').html('<div class="alert alert-danger">Error loading standards</div>');
                }
            });
        }

        function renderStandards(standards) {
            if (!standards.length) {
                $('#standardsList').html('<div class="text-center py-5 text-muted">No standards found</div>');
                return;
            }
            let html = '<div class="row">';
            standards.forEach(s => {
                const badge = s.status === 'Active' ? '<span class="badge-qa active">Active</span>' : '<span class="badge-qa cancelled">Archived</span>';
                html += `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card standard-card" style="border-radius: var(--radius);">
                    <div class="card-body-custom p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge-qa new" style="background: var(--primary-light); color: var(--primary);">${s.body}</span>
                            ${badge}
                        </div>
                        <h6 class="fw-600 mb-2" style="font-size:1rem;">${escapeHtml(s.title)}</h6>
                        <p class="text-muted small mb-2">${s.description ? escapeHtml(s.description.substring(0,100)) : 'No description'}</p>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <small class="text-muted">v${s.version || '1.0'} | ${s.effective_date || 'N/A'}</small>
                            <div>
                                <button class="btn btn-sm btn-outline-secondary me-1" onclick="editStandard(${s.standard_id})" style="padding:4px 8px;"><i class="fa-solid fa-edit"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete('standard',${s.standard_id})" style="padding:4px 8px;"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
            });
            html += '</div>';
            $('#standardsList').html(html);
        }

        /* ── Load / render policies ─────────────────────────────────────────── */
        function loadPolicies() {
            $.ajax({
                url: '../../backend/api/policies_api.php?action=list',
                type: 'GET',
                data: {
                    search: $('#searchInput').val(),
                    status: $('#statusFilter').val()
                },
                dataType: 'json',
                success: function(r) {
                    r.success ? renderPolicies(r.data) : $('#policiesList').html('<div class="alert alert-warning">Failed to load policies</div>');
                },
                error: function() {
                    $('#policiesList').html('<div class="alert alert-danger">Error loading policies</div>');
                }
            });
        }

        function renderPolicies(policies) {
            if (!policies.length) {
                $('#policiesList').html('<div class="text-center py-5 text-muted">No policies found</div>');
                return;
            }
            let html = '<div class="list-group">';
            policies.forEach(p => {
                const badge = p.status === 'Active' ? '<span class="badge-qa active">Active</span>' : '<span class="badge-qa cancelled">Archived</span>';
                const stdBadge = p.standard_title ? `<span class="badge-qa in-progress">${escapeHtml(p.standard_title)}</span>` : '<span class="badge-qa pending">General</span>';
                const docLink = p.document_url ?
                    `<small class="text-primary"><i class="fa-solid fa-file-pdf"></i> <a href="${escapeHtml(p.document_url)}" target="_blank">View Document</a></small>` :
                    '';
                html += `
            <div class="list-group-item policy-item" style="border-radius:var(--radius);margin-bottom:8px;background:var(--bg-card);">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-2">${badge}${stdBadge}</div>
                        <h6 class="fw-600 mb-1">${escapeHtml(p.title)}</h6>
                        <p class="text-muted small mb-1">${p.content ? escapeHtml(p.content.substring(0,150)) : 'No content'}</p>
                        ${docLink}
                        <div><small class="text-muted">Created: ${p.created_date || 'N/A'}</small></div>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary me-1" onclick="editPolicy(${p.policy_id})" style="padding:4px 8px;"><i class="fa-solid fa-edit"></i></button>
                        <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete('policy',${p.policy_id})" style="padding:4px 8px;"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>
            </div>`;
            });
            html += '</div>';
            $('#policiesList').html(html);
        }

        function loadStandardsForDropdown() {
            $.ajax({
                url: '../../backend/api/standards_api.php?action=list',
                type: 'GET',
                data: {
                    status: 'Active'
                },
                dataType: 'json',
                success: function(r) {
                    if (r.success) {
                        let opts = '<option value="">-- None / General Policy --</option>';
                        r.data.forEach(s => {
                            opts += `<option value="${s.standard_id}">${escapeHtml(s.title)}</option>`;
                        });
                        $('#standard_id_select').html(opts);
                    }
                }
            });
        }

        /* ── Form resets ────────────────────────────────────────────────────── */
        function resetStandardForm() {
            $('#standardForm')[0].reset();
            $('#standard_id').val('');
            $('#standardModalTitle').text('Add Standard');
            clearFormErrors('#standardForm');
        }

        function resetPolicyForm() {
            $('#policyForm')[0].reset();
            $('#policy_id').val('');
            $('#document_url').val('');
            $('#policyModalTitle').text('Add Policy');
            $('#standard_id_select').val('');
            clearFormErrors('#policyForm');
            // Reset upload widget
            clearPdfSelection();
            $('#existingDocWrap').hide();
            $('#pdfUploadProgress').hide();
        }

        function clearFormErrors(form) {
            $(form).find('.is-invalid').removeClass('is-invalid');
            $(form).find('.form-error-msg').text('').removeClass('show');
        }

        /* ── Save standard (unchanged) ──────────────────────────────────────── */
        function saveStandard() {
            const formData = {
                standard_id: $('#standard_id').val(),
                title: $('#title').val(),
                body: $('#body').val(),
                description: $('#description').val(),
                version: $('#version').val(),
                effective_date: $('#effective_date').val(),
                status: $('#status').val(),
                action: $('#standard_id').val() ? 'update' : 'create'
            };
            if (!formData.title) {
                toast.error('Title is required');
                return;
            }

            const btn = $('#saveStandardBtn');
            btnLoading(btn[0], 'Saving...');

            $.ajax({
                url: '../../backend/api/standards_api.php',
                type: 'POST',
                data: JSON.stringify(formData),
                contentType: 'application/json',
                dataType: 'json',
                success: function(r) {
                    btnReset(btn[0]);
                    if (r.success) {
                        $('#standardModal').modal('hide');
                        toast.success(r.message);
                        loadStandards();
                        loadStandardsForDropdown();
                    } else {
                        if (r.errors) applyServerErrors('#standardForm', r.errors);
                        toast.error(r.message);
                    }
                },
                error: function() {
                    btnReset(btn[0]);
                    toast.error('An error occurred');
                }
            });
        }

        /* ── Save policy (with Cloudinary upload) ───────────────────────────── */
        async function savePolicy() {
            const title = $('#policy_title').val();
            const content = $('#content').val();
            if (!title || !content) {
                toast.error('Title and Content are required');
                return;
            }

            const btn = $('#savePolicyBtn');
            btnLoading(btn[0], 'Saving...');

            let documentUrl = $('#document_url').val(); // may hold existing URL

            // Upload new file if one was chosen
            const newFile = $('#pdfFileInput')[0]._selectedFile;
            if (newFile) {
                try {
                    documentUrl = await uploadPdfToCloudinary();
                } catch (err) {
                    btnReset(btn[0]);
                    toast.error('PDF upload failed: ' + err.message);
                    return;
                }
            }

            const formData = {
                policy_id: $('#policy_id').val(),
                title,
                standard_id: $('#standard_id_select').val(),
                content,
                document_url: documentUrl || '',
                created_date: $('#created_date').val(),
                status: $('#policy_status').val(),
                action: $('#policy_id').val() ? 'update' : 'create'
            };

            $.ajax({
                url: '../../backend/api/policies_api.php',
                type: 'POST',
                data: JSON.stringify(formData),
                contentType: 'application/json',
                dataType: 'json',
                success: function(r) {
                    btnReset(btn[0]);
                    if (r.success) {
                        $('#policyModal').modal('hide');
                        toast.success(r.message);
                        loadPolicies();
                        if ($('#standards-tab').hasClass('active')) loadStandards();
                    } else {
                        if (r.errors) applyServerErrors('#policyForm', r.errors);
                        toast.error(r.message);
                    }
                },
                error: function() {
                    btnReset(btn[0]);
                    toast.error('An error occurred');
                }
            });
        }

        /* ── Edit standard ──────────────────────────────────────────────────── */
        function editStandard(id) {
            $.ajax({
                url: '../../backend/api/standards_api.php?action=get&id=' + id,
                type: 'GET',
                dataType: 'json',
                success: function(r) {
                    if (r.success && r.data) {
                        const s = r.data;
                        $('#standard_id').val(s.standard_id);
                        $('#title').val(s.title);
                        $('#body').val(s.body);
                        $('#description').val(s.description);
                        $('#version').val(s.version);
                        $('#effective_date').val(s.effective_date);
                        $('#status').val(s.status);
                        $('#standardModalTitle').text('Edit Standard');
                        $('#standardModal').modal('show');
                        clearFormErrors('#standardForm');
                    } else {
                        toast.error('Failed to load standard data');
                    }
                },
                error: function() {
                    toast.error('Error loading standard');
                }
            });
        }

        /* ── Edit policy ────────────────────────────────────────────────────── */
        function editPolicy(id) {
            $.ajax({
                url: '../../backend/api/policies_api.php?action=get&id=' + id,
                type: 'GET',
                dataType: 'json',
                success: function(r) {
                    if (r.success && r.data) {
                        const p = r.data;
                        resetPolicyForm(); // clean slate
                        $('#policy_id').val(p.policy_id);
                        $('#policy_title').val(p.title);
                        $('#standard_id_select').val(p.standard_id);
                        $('#content').val(p.content);
                        $('#created_date').val(p.created_date);
                        $('#policy_status').val(p.status);
                        $('#policyModalTitle').text('Edit Policy');

                        // Show existing document link if present
                        if (p.document_url) {
                            $('#document_url').val(p.document_url);
                            // Extract a readable filename from the URL
                            const urlParts = p.document_url.split('/');
                            const fname = decodeURIComponent(urlParts[urlParts.length - 1]) || 'Current document';
                            $('#existingDocLink').attr('href', p.document_url);
                            $('#existingDocName').text(fname);
                            $('#existingDocWrap').show();
                        }

                        $('#policyModal').modal('show');
                        clearFormErrors('#policyForm');
                    } else {
                        toast.error('Failed to load policy data');
                    }
                },
                error: function() {
                    toast.error('Error loading policy');
                }
            });
        }

        /* ── Delete ─────────────────────────────────────────────────────────── */
        function confirmDelete(type, id) {
            deleteType = type;
            deleteId = id;
            $('#deleteModal').modal('show');
        }

        $('#confirmDeleteBtn').click(function() {
            if (!deleteType || !deleteId) return;
            const apiUrl = deleteType === 'standard' ? '../../backend/api/standards_api.php' : '../../backend/api/policies_api.php';
            const btn = $('#confirmDeleteBtn');
            btnLoading(btn[0], 'Deleting...');

            $.ajax({
                url: apiUrl,
                type: 'POST',
                data: JSON.stringify({
                    action: 'delete',
                    id: deleteId
                }),
                contentType: 'application/json',
                dataType: 'json',
                success: function(r) {
                    btnReset(btn[0]);
                    if (r.success) {
                        $('#deleteModal').modal('hide');
                        toast.success(r.message);
                        if (deleteType === 'standard') {
                            loadStandards();
                            loadStandardsForDropdown();
                            loadPolicies();
                        } else {
                            loadPolicies();
                        }
                        deleteType = null;
                        deleteId = null;
                    } else {
                        toast.error(r.message);
                    }
                },
                error: function() {
                    btnReset(btn[0]);
                    toast.error('Delete failed');
                }
            });
        });

        /* ── Utilities ──────────────────────────────────────────────────────── */
        function applyServerErrors(form, errors) {
            for (const [field, msg] of Object.entries(errors)) {
                const input = $(form).find(`[name="${field}"]`);
                if (input.length) {
                    input.addClass('is-invalid');
                    const errEl = input.closest('.mb-3').find('.form-error-msg');
                    if (errEl.length) errEl.text(msg).addClass('show');
                }
            }
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, m => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;'
            } [m]));
        }
    </script>

</body>

</html>