<?php
session_start();

require_once '../../../Controleur/session_helper.php';
require_once '../../../Controleur/reclamationC.php';

$isSuspendedAppeal = cc_is_suspended_appeal_session();
$currentReclamationUserId = cc_current_reclamation_user_id();

if ($currentReclamationUserId === null) {
    die("User not connected");
}

$reclamationC = new ReclamationC();
$liste = $reclamationC->afficherReclamationsAvecReponsesUser($currentReclamationUserId);
$frontActive = 'reclamation';
?>
<html lang="en">

<head>
    <meta charset="utf-8">
    <?php require_once __DIR__ . '/../layout/front-theme-bootstrap.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Cre8Connect - Complaints</title>
    <!-- Favicon-->
    <!-- Custom Google font-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@100;200;300;400;500;600;700;800;900&amp;display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700;800&family=Fraunces:wght@700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap icons-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Core theme CSS (includes Bootstrap)-->
    <link href="css/styles.css" rel="stylesheet">
    <link href="../layout/front-header.css" rel="stylesheet">
    <style>
        /* ── BACK TO MY SPACE ── */
        .btn-back-space {
            color: #6b7280 !important;
            font-weight: 700;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 3px;
            transition: color 0.18s;
            text-decoration: none;
        }
        .btn-back-space i {
            font-size: 20px;
            line-height: 1;
        }
        .btn-back-space:hover {
            color: #5b4fff !important;
            text-decoration: none;
        }

        /*
         * Complaints page theme: html[data-theme="light"|"dark"] + --reclam-* tokens.
         * (Removed legacy .light-mode rules that incorrectly forced dark colors when the
         * body had class light-mode — that broke light mode site-wide on this page.)
         */
        :root {
            --reclam-bg: #f6f7fb;
            --reclam-card: #ffffff;
            --reclam-card-soft: #f8fafc;
            --reclam-text: #111827;
            --reclam-muted: #64748b;
            --reclam-border: #dbe2ef;
            --reclam-input: #ffffff;
            --reclam-input-text: #111827;
            --reclam-input-focus-bg: #ffffff;
        }

        html[data-theme="dark"] {
            --reclam-bg: #101116;
            --reclam-card: #191c24;
            --reclam-card-soft: #20212b;
            --reclam-text: #f8fafc;
            --reclam-muted: #a5adc2;
            --reclam-border: rgba(148, 163, 184, 0.22);
            --reclam-input: #2a3038;
            --reclam-input-text: #f8fafc;
            --reclam-input-focus-bg: #151325;
        }

        body:has(.front-reclamation-page) {
            background-color: var(--reclam-bg) !important;
            color: var(--reclam-text);
        }

        body:has(.front-reclamation-page).bg-light {
            background-color: var(--reclam-bg) !important;
        }

        .front-reclamation-page {
            background: var(--reclam-bg);
            color: var(--reclam-text);
        }

        .front-reclamation-page section.py-5 {
            background-color: transparent !important;
            color: inherit;
        }

        .front-reclamation-page .container,
        .front-reclamation-page .row,
        .front-reclamation-page [class*="col-"] {
            background-color: transparent !important;
        }

        .front-reclamation-page .card,
        .front-reclamation-page .card-body {
            background-color: var(--reclam-card) !important;
            border-color: var(--reclam-border) !important;
            color: var(--reclam-text) !important;
        }

        html[data-theme="dark"] .front-reclamation-page .card {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3) !important;
        }

        .front-reclamation-page .form-label,
        .front-reclamation-page h1,
        .front-reclamation-page h2,
        .front-reclamation-page h3,
        .front-reclamation-page h4,
        .front-reclamation-page h5,
        .front-reclamation-page label {
            color: var(--reclam-text) !important;
        }

        .front-reclamation-page .text-gradient {
            background: linear-gradient(135deg, #5b4fff 0%, #c026d3 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        html[data-theme="dark"] .front-reclamation-page .text-gradient {
            background: linear-gradient(135deg, #8b7cff 0%, #f472b6 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .front-reclamation-page textarea,
        .front-reclamation-page select,
        .front-reclamation-page input.form-control,
        .front-reclamation-page .form-select {
            background-color: var(--reclam-input) !important;
            color: var(--reclam-input-text) !important;
            border-color: var(--reclam-border) !important;
        }

        .front-reclamation-page textarea::placeholder,
        .front-reclamation-page .form-control::placeholder {
            color: var(--reclam-muted) !important;
        }

        .front-reclamation-page .form-control:focus,
        .front-reclamation-page .form-select:focus {
            background-color: var(--reclam-input-focus-bg) !important;
            color: var(--reclam-input-text) !important;
            border-color: #7c6fff !important;
            box-shadow: 0 0 0 0.2rem rgba(124, 111, 255, 0.22) !important;
        }

        .front-reclamation-page .text-muted {
            color: var(--reclam-muted) !important;
        }

        .front-reclamation-page .alert {
            background-color: var(--reclam-card-soft) !important;
            border-color: var(--reclam-border) !important;
            color: var(--reclam-text) !important;
        }

        html[data-theme="light"] .front-reclamation-page .alert-success {
            background-color: #ecfdf5 !important;
            border-color: #a7f3d0 !important;
            color: #065f46 !important;
        }

        html[data-theme="light"] .front-reclamation-page .alert-warning {
            background-color: #fffbeb !important;
            border-color: #fde68a !important;
            color: #92400e !important;
        }

        html[data-theme="dark"] .front-reclamation-page .alert-success {
            background-color: #0d2e22 !important;
            border-color: rgba(52, 211, 153, 0.25) !important;
            color: #9ff0ce !important;
        }

        html[data-theme="dark"] .front-reclamation-page .alert-warning {
            background-color: #271d08 !important;
            border-color: rgba(251, 191, 36, 0.25) !important;
            color: #fcd77f !important;
        }

        .front-reclamation-page .modal-content,
        .front-reclamation-page .modal-header,
        .front-reclamation-page .modal-body,
        .front-reclamation-page .modal-footer {
            background-color: var(--reclam-card) !important;
            color: var(--reclam-text) !important;
            border-color: var(--reclam-border) !important;
        }

        html[data-theme="dark"] .front-reclamation-page .btn-close {
            filter: invert(1);
        }

        .front-reclamation-page .bg-gradient-primary-to-secondary {
            background: linear-gradient(135deg, #5b4fff 0%, #7c3aed 45%, #2563eb 100%) !important;
            color: #ffffff !important;
        }

        html[data-theme="dark"] .front-reclamation-page .bg-gradient-primary-to-secondary {
            background: linear-gradient(135deg, #201c3d 0%, #111827 100%) !important;
            color: #f8fafc !important;
        }

        body:has(.front-reclamation-page) footer.bg-white {
            background-color: var(--reclam-card) !important;
            color: var(--reclam-text) !important;
            border-top: 1px solid var(--reclam-border) !important;
        }

        body:has(.front-reclamation-page) footer a {
            color: var(--reclam-muted) !important;
        }

        html[data-theme="dark"] body:has(.front-reclamation-page) footer a {
            color: #c9c3ff !important;
        }

        .front-reclamation-page * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }
    </style>
    <link href="reclamation-front.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/reclamation-front.css')); ?>" rel="stylesheet">
<link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
</head>

<body class="d-flex flex-column h-100 bg-light">
    <main class="flex-shrink-0">
        <?php if (!$isSuspendedAppeal) { require_once __DIR__ . '/../layout/header.php'; } ?>
        <div class="front-reclamation-page">
        <!-- Projects Section-->
        <section class="py-5 reclamation-form-section">
            <div class="container px-5 mb-5 front-reclamation-shell">
                <div class="text-center mb-5 reclamation-hero">
                    <h1 class="display-5 fw-bolder mb-0">
                        <span class="text-gradient d-inline" data-i18n="<?php echo $isSuspendedAppeal ? 'account.suspensionAppealTitle' : 'account.submitComplaint'; ?>">
                            <?php echo $isSuspendedAppeal ? 'Suspension appeal' : 'Submit a complaint'; ?>
                        </span>
                    </h1>
                    <p class="reclamation-hero-copy" data-i18n="<?php echo $isSuspendedAppeal ? 'account.suspensionAppealCopy' : 'account.complaintHeroCopy'; ?>">
                        <?php echo $isSuspendedAppeal ? 'Your account is suspended. You can submit a complaint to request a review.' : 'Tell us what happened so the Cre8Connect team can help you clearly.'; ?>
                    </p>
                </div>

                <div class="row justify-content-center">
                    <div class="col-lg-8">

                        <div class="card shadow rounded-4 border-0 reclamation-form-card">
                            <div class="card-body p-5">

                                <form method="POST" action="traiterReclamation.php" onsubmit="return validateReclamation(this)">

                                    <!-- Description -->
                                    <div class="mb-4">
                                        <label class="form-label fw-bold" data-i18n="account.description">Description</label>
                                        <textarea name="description" id="descriptionInput" class="form-control" rows="4"
                                            placeholder="Describe your problem..." data-i18n-placeholder="account.descriptionPlaceholder"></textarea>
                                        <small class="text-danger d-none" id="descriptionError"></small>
                                    </div>

                                    <!-- Priority -->
                                    <div class="mb-4">
                                        <label class="form-label fw-bold" data-i18n="account.priority">Priority</label>
                                        <select name="priorite" id="prioriteInput" class="form-select">
                                            <option value="faible" data-i18n-opt="account.low">Low</option>
                                            <option value="normale" selected data-i18n-opt="account.normal">Normal</option>
                                            <option value="haute" data-i18n-opt="account.high">High</option>
                                        </select>
                                    </div>

                                    <!-- Button -->
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <span data-i18n="account.submitComplaintButton">Submit complaint</span>
                                        </button>
                                    </div>

                                </form>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </section>
        <section class="py-5 reclamation-list-section">
            <div class="container px-5 front-reclamation-shell">

                <div class="text-center mb-5 reclamation-section-heading">
                    <h2 class="fw-bolder" data-i18n="account.myComplaints">My complaints</h2>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success text-center">
                        <span data-i18n="account.complaintSuccess">Complaint sent successfully!</span>
                    </div>
                <?php endif; ?>

                <?php if (empty($liste)): ?>
                    <div class="reclamation-empty-state">
                        <span class="reclamation-empty-icon"><i class="bi bi-life-preserver"></i></span>
                        <p data-i18n="account.noComplaints">No complaints yet</p>
                    </div>
                <?php else: ?>

                    <div class="row">
                        <?php foreach ($liste as $rec): ?>

                            <div class="col-lg-6 mb-4">
                                <div class="card shadow-sm border-0 h-100 reclamation-card">
                                    <div class="card-body">

                                        <h5 class="fw-bold">
                                            <?php echo htmlspecialchars($rec['description']); ?>
                                        </h5>

                                        <p class="text-muted small">
                                            <?php echo $rec['date_creation']; ?>
                                        </p>

                                        <hr>

                                        <?php if ($rec['reponse']): ?>
                                            <div class="alert alert-success">
                                                <strong data-i18n="account.adminResponse">Admin response:</strong><br>
                                                <?php echo htmlspecialchars($rec['reponse']); ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-warning">
                                                <span data-i18n="account.waitingResponse">Waiting for a response...</span>
                                            </div>
                                        <?php endif; ?>

                                        <!-- 🔴 DELETE BUTTON -->
                                        <div class="text-end mt-3 d-flex justify-content-end gap-2">

                                            <!-- Edit -->
                                            <button class="btn btn-sm d-flex align-items-center justify-content-center reclamation-icon-btn reclamation-edit-btn"
                                                style="background-color:#AEEA94; width:40px; height:40px; border:none;"
                                                data-bs-toggle="modal" data-bs-target="#modalEdit<?php echo $rec['id']; ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                            <!-- Delete -->
                                            <form method="POST" action="supprimerReclamation.php"
                                                onsubmit="return confirm(cre8ComplaintText('account.deleteConfirm'));">

                                                <input type="hidden" name="id" value="<?php echo $rec['id']; ?>">

                                                <button type="submit"
                                                    class="btn btn-sm d-flex align-items-center justify-content-center reclamation-icon-btn reclamation-delete-btn"
                                                    style="background-color:#FF8383; width:40px; height:40px; border:none;">
                                                    <i class="bi bi-trash"></i>
                                                </button>

                                            </form>

                                        </div>
                                    </div>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    </div>

                <?php endif; ?>

            </div>
        </section>
        <?php foreach ($liste as $rec): ?>

            <div class="modal fade" id="modalEdit<?php echo $rec['id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">

                        <form method="POST" action="modifierReclamation.php">

                            <div class="modal-header">
                                <h5 class="modal-title" data-i18n="account.editComplaint">Edit complaint</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">

                                <input type="hidden" name="id" value="<?php echo $rec['id']; ?>">

                                <!-- Description -->
                                <div class="mb-3">
                                    <label data-i18n="account.description">Description</label>
                                    <textarea name="description" class="form-control"
                                        required><?php echo $rec['description']; ?></textarea>
                                </div>

                                <!-- Priority -->
                                <div class="mb-3">
                                    <label data-i18n="account.priority">Priority</label>
                                    <select name="priorite" class="form-select">
                                        <option value="faible" data-i18n-opt="account.low" <?php if ($rec['priorite'] == 'faible')
                                            echo 'selected'; ?>>
                                            Low</option>
                                        <option value="normale" data-i18n-opt="account.normal" <?php if ($rec['priorite'] == 'normale')
                                            echo 'selected'; ?>>
                                            Normal</option>
                                        <option value="haute" data-i18n-opt="account.high" <?php if ($rec['priorite'] == 'haute')
                                            echo 'selected'; ?>>High
                                        </option>
                                    </select>
                                </div>

                            </div>

                            <div class="modal-footer">
                                <button type="submit" class="btn btn-success" data-i18n="account.save">Save</button>
                            </div>

                        </form>

                    </div>
                </div>
            </div>

        <?php endforeach; ?>
        </div>
    </main>
    <!-- Footer-->
    <footer class="bg-white py-4 mt-auto">
        <div class="container px-5">
            <div class="row align-items-center justify-content-between flex-column flex-sm-row">
                <div class="col-auto">
                    <div class="small m-0">Copyright © Your Website 2023</div>
                </div>
                <div class="col-auto">
                    <a class="small" href="#!">Privacy</a>
                    <span class="mx-1">·</span>
                    <a class="small" href="#!">Terms</a>
                    <span class="mx-1">·</span>
                    <a class="small" href="#!">Contact</a>
                </div>
            </div>
        </div>
    </footer>
    <!-- Bootstrap core JS-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Core theme JS-->
    <script src="js/scripts.js"></script>

    <!-- JavaScript input validation -->
    <script>
        const cre8ComplaintTranslations = {
            en: {
                'account.submitComplaint': 'Submit a complaint',
                'account.suspensionAppealTitle': 'Suspension appeal',
                'account.suspensionAppealCopy': 'Your account is suspended. You can submit a complaint to request a review.',
                'account.description': 'Description',
                'account.descriptionPlaceholder': 'Describe your problem...',
                'account.priority': 'Priority',
                'account.low': 'Low',
                'account.normal': 'Normal',
                'account.high': 'High',
                'account.submitComplaintButton': 'Submit complaint',
                'account.myComplaints': 'My complaints',
                'account.complaintSuccess': 'Complaint sent successfully!',
                'account.noComplaints': 'No complaints yet',
                'account.complaintHeroCopy': 'Tell us what happened so the Cre8Connect team can help you clearly.',
                'account.adminResponse': 'Admin response:',
                'account.waitingResponse': 'Waiting for a response...',
                'account.deleteConfirm': 'Do you really want to delete this complaint?',
                'account.editComplaint': 'Edit complaint',
                'account.save': 'Save',
                'account.requiredError': 'Description is required.',
                'account.minError': 'Description must contain at least 10 characters.',
                'account.maxError': 'Description must not exceed 50 characters.',
                'account.spacesError': 'Description cannot contain only spaces.',
                'account.characters': 'characters'
            },
            fr: {
                'account.submitComplaint': 'Envoyer une reclamation',
                'account.suspensionAppealTitle': 'Recours de suspension',
                'account.suspensionAppealCopy': 'Votre compte est suspendu. Vous pouvez envoyer une reclamation pour demander une revue.',
                'account.description': 'Description',
                'account.descriptionPlaceholder': 'Decrivez votre probleme...',
                'account.priority': 'Priorite',
                'account.low': 'Faible',
                'account.normal': 'Normale',
                'account.high': 'Haute',
                'account.submitComplaintButton': 'Envoyer la reclamation',
                'account.myComplaints': 'Mes reclamations',
                'account.complaintSuccess': 'Reclamation envoyee avec succes !',
                'account.noComplaints': 'Aucune reclamation pour le moment',
                'account.complaintHeroCopy': 'Expliquez-nous ce qui s est passe afin que l equipe Cre8Connect puisse vous aider clairement.',
                'account.adminResponse': 'Reponse admin :',
                'account.waitingResponse': 'En attente de reponse...',
                'account.deleteConfirm': 'Voulez-vous vraiment supprimer cette reclamation ?',
                'account.editComplaint': 'Modifier la reclamation',
                'account.save': 'Enregistrer',
                'account.requiredError': 'La description est obligatoire.',
                'account.minError': 'La description doit contenir au moins 10 caracteres.',
                'account.maxError': 'La description ne doit pas depasser 50 caracteres.',
                'account.spacesError': 'La description ne peut pas contenir seulement des espaces.',
                'account.characters': 'caracteres'
            }
        };
        function cre8ComplaintLang() {
            if (typeof window.cre8FrontReadLang === 'function') {
                return window.cre8FrontReadLang();
            }
            try {
                return (localStorage.getItem('cre8_front_lang') || localStorage.getItem('cre8_lang')) === 'fr' ? 'fr' : 'en';
            } catch (e) {
                return 'en';
            }
        }
        function cre8ComplaintText(key) {
            const lang = cre8ComplaintLang();
            return (cre8ComplaintTranslations[lang] && cre8ComplaintTranslations[lang][key]) || cre8ComplaintTranslations.en[key] || key;
        }
        function cre8RegisterComplaintTranslations() {
            if (typeof window.cre8RegisterTranslations === 'function') {
                window.cre8RegisterTranslations(cre8ComplaintTranslations);
            }
            const counter = document.getElementById('charCounter');
            const input = document.getElementById('descriptionInput');
            if (counter && input) {
                counter.textContent = `${input.value.length}/50 ${cre8ComplaintText('account.characters')}`;
            }
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', cre8RegisterComplaintTranslations);
        } else {
            cre8RegisterComplaintTranslations();
        }
        window.addEventListener('cre8:languagechange', cre8RegisterComplaintTranslations);

        // ===== COMPLAINT FORM VALIDATION =====
        function validateReclamation(form) {
            const descriptionInput = document.getElementById('descriptionInput');
            const descriptionError = document.getElementById('descriptionError');
            
            const description = descriptionInput.value.trim();
            
            // Reset error styles
            descriptionInput.classList.remove('border-danger');
            descriptionError.classList.add('d-none');
            
            // Check 1: field must not be empty
            if (description === '') {
                descriptionError.textContent = cre8ComplaintText('account.requiredError');
                descriptionError.classList.remove('d-none');
                descriptionInput.classList.add('border-danger');
                descriptionInput.focus();
                return false;
            }
            
            // Check 2: minimum 10 characters
            if (description.length < 10) {
                descriptionError.textContent = `${cre8ComplaintText('account.minError')} (${description.length}/10)`;
                descriptionError.classList.remove('d-none');
                descriptionInput.classList.add('border-danger');
                descriptionInput.focus();
                return false;
            }
            
            // Check 3: maximum 50 characters
            if (description.length > 50) {
                descriptionError.textContent = `${cre8ComplaintText('account.maxError')} (${description.length}/50)`;
                descriptionError.classList.remove('d-none');
                descriptionInput.classList.add('border-danger');
                descriptionInput.focus();
                return false;
            }
            
            // Check 4: not only spaces
            if (!/\S/.test(description)) {
                descriptionError.textContent = cre8ComplaintText('account.spacesError');
                descriptionError.classList.remove('d-none');
                descriptionInput.classList.add('border-danger');
                descriptionInput.focus();
                return false;
            }
            
            // Success - everything is valid
            descriptionError.classList.add('d-none');
            descriptionInput.classList.remove('border-danger');
            return true;
        }
        
        // ===== DYNAMIC COUNTER DISPLAY =====
        const descriptionInput = document.getElementById('descriptionInput');
        if (descriptionInput) {
            // Create an element to display the counter
            const counterElement = document.createElement('small');
            counterElement.id = 'charCounter';
            counterElement.className = 'text-muted d-block mt-2';
            counterElement.textContent = `0/50 ${cre8ComplaintText('account.characters')}`;
            descriptionInput.parentNode.insertBefore(counterElement, descriptionInput.nextSibling);
            
            // Update the counter live
            descriptionInput.addEventListener('input', function() {
                const length = this.value.length;
                const counter = document.getElementById('charCounter');
                
                if (counter) {
                    counter.textContent = `${length}/50 ${cre8ComplaintText('account.characters')}`;
                    
                    // Counter color based on length
                    if (length < 10) {
                        counter.className = 'text-danger d-block mt-2 fw-bold';
                    } else if (length <= 50) {
                        counter.className = 'text-success d-block mt-2 fw-bold';
                    } else {
                        counter.className = 'text-danger d-block mt-2 fw-bold';
                    }
                }
            });
        }
        
        // ===== CLEAR ERRORS ON FOCUS =====
        descriptionInput.addEventListener('focus', function() {
            const descriptionError = document.getElementById('descriptionError');
            descriptionError.classList.add('d-none');
            this.classList.remove('border-danger');
        });
    </script>
    <script src="../layout/front-header.js"></script>

</body>

</html>

