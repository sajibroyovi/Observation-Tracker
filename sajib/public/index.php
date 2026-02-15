<!DOCTYPE html>
<?php
require_once __DIR__ . '/../config/app.php';
include INCLUDES_PATH . '/auth_check.php';
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shift Handover Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <script src="<?php echo ASSETS_URL; ?>/js/script.js" defer></script>
    <script src="<?php echo ASSETS_URL; ?>/js/toast.js" defer></script>
    <style>
        .service-card {
            transition: all 0.3s ease;
            border: none;
            background: rgba(210, 208, 208, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1) !important;
        }
        .service-icon {
            width: 50px;
            height: 50px;
            min-height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        /* Mini Stat Pills */
        .stats-horizontal-container {
            margin-bottom: 1.5rem;
            max-width: 100%;
            overflow: hidden;
        }
        .stat-mini-pill {
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            background: rgba(255, 255, 255, 0.5);
            min-width: 140px;
        }
        .dashboard-container {
            min-height: 100vh;
            width: 100%;
        }
        .main-content {
            padding: 30px;
            margin-left: 280px;
            /* Width of sidebar */
            background-color: #f4f6f9;
            min-height: 100vh;
            transition: all 0.4s ease;
        }
        @media (max-width: 576px) {
            .stat-mini-pill {
                min-width: 120px;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php include INCLUDES_PATH . '/sidebar.php'; ?>
        <div class="main-content">
            <!-- Hidden toggles to keep script.js happy if needed, though sidebar has its own -->
            <div style="display: none;">
                <input type="checkbox" id="night-mode-toggle">
            </div>
            <!-- Dashboard Header -->
            <div class="mb-4">
                <div class="row align-items-center g-3">
                    <div class="col-7 col-md-auto pe-md-4">
                        <h1 class="view-header fw-bold mb-1" style="font-size: clamp(1.8rem, 5vw, 2.5rem);">Observation Overview</h1>
                        <p class="text-muted mb-0 small">Welcome back, <strong><?php echo htmlspecialchars($user_name); ?></strong>.</p>
                    </div>
                    <div class="col-5 col-md-auto ms-md-auto">
                        <div class="glass-card px-3 py-2 text-center shadow-sm" style="min-width: 130px;">
                            <div class="small fw-bold text-uppercase opacity-50" style="font-size: 0.65rem;">
                                <i class="fa-solid fa-clock opacity-50 me-1"></i> <span id="real-time-clock"><?php echo date('h:i:s A'); ?></span>
                            </div>
                            <div class="h6 mb-0 fw-bold text-primary" id="current-shift-display"><?php echo getCurrentShift(); ?></div>
                            <div class="small text-muted fw-bold text-uppercase" style="font-size: 0.55rem;">Current Shift</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Overview Stats Bar -->
            <?php
            // Helper function to safely get table count
            function getTableCount($conn, $table, $where = "") {
                $sql = "SELECT COUNT(*) as c FROM $table" . ($where ? " WHERE $where" : "");
                $stmt = executePreparedStatement($conn, $sql);
                if (!$stmt) return 0;
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);
                return $row ? (int)$row['c'] : 0;
            }
            
            $counts = [
                'enable_disable' => getTableCount($conn, 'enable_disable'),
                'pending_mails' => getTableCount($conn, "pending_mail", "status IN ('pending', 'follow_up')"),
                'security_mails' => getTableCount($conn, "security_mail", "status IN ('pending', 'follow_up')"),
                'change_requests' => getTableCount($conn, 'cr_list'),
                'promo_banners' => getTableCount($conn, 'promo_banner'),
                'service_outages' => getTableCount($conn, 'service_outage'),
                'ssl_certificates' => getTableCount($conn, 'ssl_certificate'),
                'campaigns' => getTableCount($conn, 'campaign'),
                'observations' => getTableCount($conn, "observations", "l2_observation IS NULL OR l2_observation = ''"),
                'total_obs' => getTableCount($conn, 'observations'),
                'l2_done' => getTableCount($conn, "observations", "l2_observation IS NOT NULL AND l2_observation != ''")
            ];

            $stat_info = [
                'enable_disable' => ['icon' => 'fa-toggle-on', 'color' => '#4361ee', 'label' => 'Reachability'],
                'pending_mails' => ['icon' => 'fa-envelope', 'color' => '#f72585', 'label' => 'Pending Mail'],
                'security_mails' => ['icon' => 'fa-shield-halved', 'color' => '#ff9f1c', 'label' => 'Security Info'],
                'change_requests' => ['icon' => 'fa-file-invoice', 'color' => '#4cc9f0', 'label' => 'CR Status'],
                'promo_banners' => ['icon' => 'fa-image', 'color' => '#7209b7', 'label' => 'Promo Bnr'],
                'service_outages' => ['icon' => 'fa-triangle-exclamation', 'color' => '#ef233c', 'label' => 'Outages'],
                'ssl_certificates' => ['icon' => 'fa-lock', 'color' => '#560bad', 'label' => 'SSL Certs'],
                'campaigns' => ['icon' => 'fa-bullhorn', 'color' => '#3a0ca3', 'label' => 'Campaigns'],
                'observations' => ['icon' => 'fa-clipboard-check', 'color' => '#2a9d8f', 'label' => 'Observations']
            ];

            // Granular module visibility for statistics
            $stat_info = array_filter($stat_info, function($key) {
                // Map stat keys to module titles if they differ
                $title_map = [
                    'enable_disable' => 'Enable/Disable',
                    'pending_mails' => 'Pending Mail',
                    'security_mails' => 'Security Mail',
                    'cr_list' => 'CR List',
                    'promo_banners' => 'Promo Banner',
                    'service_outages' => 'Service Outage',
                    'ssl_certificates' => 'SSL Certificate',
                    'campaigns' => 'Campaign',
                    'observations' => 'Observations'
                ];
                $title = isset($title_map[$key]) ? $title_map[$key] : $key;
                return canViewModule($title);
            }, ARRAY_FILTER_USE_KEY);
            ?>
            <div class="stats-horizontal-container">
                <div class="row">
                    <?php foreach($stat_info as $key => $info): ?>
                        <!-- stat column decide by changing col-2 to col-3 etc -->
                        <?php
                        $count = count($stat_info);
                        $col_class = 'col-2';
                        if ($count == 1) {
                            $col_class = 'col-12';
                        } elseif ($count == 2) {
                            $col_class = 'col-6';
                        }
                        ?>
                        <div class="<?php echo $col_class; ?> mb-3">
                            <div class="stat-mini-pill glass-card p-2 h-100 d-flex flex-column justify-content-center text-center border-bottom border-4" style="border-color: <?php echo $info['color']; ?> !important;">
                                <div class="small fw-bold text-uppercase opacity-50 mb-1" style="font-size: 0.65rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo $info['label']; ?>
                                </div>
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <i class="fa-solid <?php echo $info['icon']; ?> opacity-75" style="color: <?php echo $info['color']; ?>; font-size: 1.1rem;"></i>
                                    <?php if ($key === 'observations'): ?>
                                        <div class="d-flex gap-1 text-start border-start ps-1 overflow-hidden">
                                            <div class="flex-shrink-1">
                                                <div style="font-size: 0.5rem;" class="text-muted fw-bold mb-0">PENDING</div>
                                                <div class="fw-bold" style="font-size: 0.85rem; color: #ef233c;"><?php echo $counts['observations']; ?></div>
                                            </div>
                                            <div class="border-start ps-1 flex-shrink-1">
                                                <div style="font-size: 0.5rem;" class="text-muted fw-bold mb-0">TOTAL</div>
                                                <div class="fw-bold" style="font-size: 0.85rem;"><?php echo $counts['total_obs']; ?></div>
                                            </div>
                                            <div class="border-start ps-1 flex-shrink-1">
                                                <div style="font-size: 0.5rem;" class="text-muted fw-bold mb-0">PROG</div>
                                                <?php $perc = ($counts['total_obs'] > 0) ? round(($counts['l2_done'] / $counts['total_obs']) * 100) : 0; ?>
                                                <div class="fw-bold text-success" style="font-size: 0.85rem;"><?php echo $perc; ?>%</div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <h5 class="mb-0 fw-bold" style="font-size: 1.25rem;"><?php echo $counts[$key]; ?></h5>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (canDispatchHandover()): ?>
            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="glass-card p-3">
                        <h6 class="fw-bold mb-3 border-bottom pb-2"><i class="fa-solid fa-paper-plane me-2 text-primary"></i> Dispatch Shift Handover</h6>
                        <form id="add-shift-handover-form" method="POST" action="<?php echo BASE_URL; ?>/modules/email/send.php">
                            <?php echo getCsrfField(); ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="shift" class="form-label small text-muted text-uppercase fw-bold">Select Shift</label>
                                    <select name="shift" id="shift" class="form-select border-0 bg-light" required>
                                        <option value="Morning">Morning</option>
                                        <option value="Evening">Evening</option>
                                        <option value="Night">Night</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="date" class="form-label small text-muted text-uppercase fw-bold">Handover Date</label>
                                    <input type="date" name="date" id="date" class="form-control border-0 bg-light" required>
                                </div>
                                <div class="col-12 mt-3">
                                    <button type="submit" class="btn btn-primary w-100 py-1 fw-bold small">
                                        <i class="fa-solid fa-envelope-circle-check me-1"></i> Send Handover
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="glass-card h-100 shadow-sm">
                        <div class="card-header bg-accent-pink text-white p-2 px-3 d-flex align-items-center" style="background-color: var(--accent-pink) !important; border-radius: var(--border-radius) var(--border-radius) 0 0;">
                            <h6 class="mb-0 fw-bold small"><i class="fa-solid fa-clock-rotate-left me-2"></i> Last Handover</h6>
                        </div>
                        <div class="card-body p-3">
                            <?php
                            $prev_sql = "SELECT * FROM handover ORDER BY created_at DESC LIMIT 1";
                            $prev_result = mysqli_query($conn, $prev_sql);
                            if ($prev_result && mysqli_num_rows($prev_result) > 0) {
                                $prev_row = mysqli_fetch_assoc($prev_result);
                                echo "<div class='mb-1 small text-muted text-uppercase' style='font-size: 0.65rem;'>Shift Info</div>";
                                echo "<h6 class='fw-bold mb-0'>" . $prev_row['shift'] . "</h6>";
                                echo "<p class='mb-0 text-primary small'><i class='fa-solid fa-calendar-day me-1'></i> " . date('d M, Y', strtotime($prev_row['handover_date'])) . "</p>";
                            } else {
                                echo "<p class='text-muted italic small mb-0'>No previous handover.</p>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="service mt-2">
                <div class="d-flex align-items-center mb-3">
                    <h5 class="view-header fw-bold mb-0 text-uppercase opacity-75" style="letter-spacing: 1px;">Operation Center</h5>
                    <div class="ms-3 flex-grow-1 border-bottom opacity-25"></div>
                </div>

            <div class="row g-4 mt-2">
                <?php
                $modules = [
                    ['icon' => 'fa-toggle-on', 'color' => '#4361ee', 'bg' => 'rgba(67, 97, 238, 0.1)', 'title' => 'Enable/Disable', 'desc' => 'Control and track service reachability status for this shift.', 'modal' => 'staticBackdrop_ed', 'view' => BASE_URL . '/modules/ed/view.php', 'can_add' => canEditGlobal()],
                    ['icon' => 'fa-envelope', 'color' => '#f72585', 'bg' => 'rgba(247, 37, 133, 0.1)', 'title' => 'Pending Mail', 'desc' => 'Manage and track unanswered or follow-up communications.', 'modal' => 'staticBackdrop_pdmail', 'view' => BASE_URL . '/modules/pm/view.php', 'can_add' => canEditGlobal()],
                    ['icon' => 'fa-shield-halved', 'color' => '#ff9f1c', 'bg' => 'rgba(255, 159, 28, 0.1)', 'title' => 'Security Mail', 'desc' => 'Monitor high-priority alerts, security warnings, and escalations.', 'modal' => 'staticBackdrop_scmail', 'view' => BASE_URL . '/modules/sc/view.php', 'can_add' => canEditGlobal()],
                    ['icon' => 'fa-file-invoice', 'color' => '#4cc9f0', 'bg' => 'rgba(76, 201, 240, 0.1)', 'title' => 'CR List', 'desc' => 'Track ongoing and upcoming change request lifecycle.', 'modal' => 'staticBackdrop_crlist', 'view' => BASE_URL . '/modules/change_requests/view.php', 'can_add' => canEditGlobal()],
                    ['icon' => 'fa-image', 'color' => '#7209b7', 'bg' => 'rgba(114, 9, 183, 0.1)', 'title' => 'Promo Banner', 'desc' => 'Manage live and scheduled banners along with approval notes.', 'modal' => 'staticBackdrop_herobanner', 'view' => BASE_URL . '/modules/banners/view.php', 'can_add' => canEditGlobal()],
                    ['icon' => 'fa-triangle-exclamation', 'color' => '#ef233c', 'bg' => 'rgba(239, 35, 60, 0.1)', 'title' => 'Service Outage', 'desc' => 'Track downtime issues, incidents, and their resolutions.', 'modal' => 'staticBackdrop_soutage', 'view' => BASE_URL . '/modules/outages/view.php', 'can_add' => canEditGlobal()],
                    ['icon' => 'fa-lock', 'color' => '#560bad', 'bg' => 'rgba(86, 11, 173, 0.1)', 'title' => 'SSL Certificate', 'desc' => 'Monitor expiration dates and renewal status of certificates.', 'modal' => 'staticBackdrop_SSLcertificate', 'view' => BASE_URL . '/modules/ssl/view.php', 'can_add' => canEditGlobal()],
                    ['icon' => 'fa-bullhorn', 'color' => '#3a0ca3', 'bg' => 'rgba(58, 12, 163, 0.1)', 'title' => 'Campaign', 'desc' => 'Planning and tracking of upcoming or ongoing campaigns.', 'modal' => 'staticBackdrop_campaign', 'view' => BASE_URL . '/modules/campaigns/view.php', 'can_add' => canEditGlobal()],
                    ['icon' => 'fa-clipboard-check', 'color' => '#2a9d8f', 'bg' => 'rgba(42, 157, 143, 0.1)', 'title' => 'Observations', 'desc' => 'Detailed L1 and L2 operational observations and insights.', 'modal' => 'staticBackdrop_observations', 'view' => BASE_URL . '/modules/observations/view.php', 'can_add' => canAddObservation()]
                ];

                // Granular module visibility for grid
                $modules = array_filter($modules, function($mod) {
                    return canViewModule($mod['title']);
                });

                foreach ($modules as $mod): ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="service-card p-4 shadow-sm border">
                        <div class="service-icon" style="background-color: <?php echo $mod['bg']; ?>; color: <?php echo $mod['color']; ?>;">
                            <i class="fa-solid <?php echo $mod['icon']; ?> fa-xl"></i>
                        </div>
                        <h6 class="fw-bold mb-2"><?php echo $mod['title']; ?></h6>
                        <p class="text-muted small mb-3"><?php echo $mod['desc']; ?></p>
                        <div class="d-flex gap-2">
                            <?php if ($mod['can_add']): ?>
                                <button class="btn btn-sm px-4 fw-bold rounded-pill text-white shadow-sm" style="background-color: <?php echo $mod['color']; ?>;" data-bs-toggle="modal" data-bs-target="#<?php echo $mod['modal']; ?>">
                                    <i class="fa-solid fa-plus me-1"></i> Add
                                </button>
                            <?php endif; ?>
                            <a href="<?php echo $mod['view']; ?>" class="btn btn-sm px-4 fw-bold rounded-pill shadow-sm" style="border: 2px solid <?php echo $mod['color']; ?>; color: <?php echo $mod['color']; ?>; background: white;">
                                <i class="fa-solid fa-arrow-right-long me-1"></i> View
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <!-- Modals Container -->
            <?php include INCLUDES_PATH . '/modals.php'; ?>
        </div> <!-- End service section -->
    </div> <!-- End main-content -->
</div> <!-- End dashboard-container -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
        crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/1dc05809f5.js" crossorigin="anonymous"></script>

    <script>
        function updateShift() {
            const now = new Date();
            // Force adjustment to Asia/Dhaka (UTC+6) for the live display
            const dhakaTime = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Dhaka' }));
            const hour = dhakaTime.getHours();
            
            let shift = 'Night';
            if (hour >= 6 && hour < 14) shift = 'Morning';
            else if (hour >= 14 && hour < 22) shift = 'Evening';
            
            const clockEl = document.getElementById('real-time-clock');
            const shiftEl = document.getElementById('current-shift-display');
            
            if (clockEl) {
                clockEl.textContent = dhakaTime.toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit', 
                    second: '2-digit', 
                    hour12: true 
                });
            }
            if (shiftEl) {
                shiftEl.textContent = shift;
            }
        }
        setInterval(updateShift, 1000);
        updateShift();
    </script>
</body>

</html>