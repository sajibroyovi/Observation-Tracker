<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php';


if (!canEditL1() && !canEditL2()) {
    header('Location: ' . BASE_URL . '/');
    exit;
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Fetch existing record using prepared statement
    $stmt_fetch = mysqli_prepare($conn, "SELECT * FROM observations WHERE serial_no = ?");
    if ($stmt_fetch) {
        mysqli_stmt_bind_param($stmt_fetch, "i", $id);
        mysqli_stmt_execute($stmt_fetch);
        $result = mysqli_stmt_get_result($stmt_fetch);
        
        if ($result && mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);
        } else {
            log_error("Observation record not found", ['id' => $id]);
            echo "Record not found";
            exit;
        }
        mysqli_stmt_close($stmt_fetch);
    } else {
        log_error("Failed to prepare fetch statement for observations", ['error' => mysqli_error($conn)]);
        echo "Internal Server Error";
        exit;
    }
} else {
    echo "No ID provided";
    exit;
}

// Fetch L2 users for technician assignment
$l2_users_sql = "SELECT username FROM users WHERE role = 'l2' ORDER BY username ASC";
$l2_users_stmt = executePreparedStatement($conn, $l2_users_sql);
$l2_users_result = $l2_users_stmt ? mysqli_stmt_get_result($l2_users_stmt) : false;
$l2_users = [];
if ($l2_users_result) {
    while ($u = mysqli_fetch_assoc($l2_users_result)) {
        $l2_users[] = $u['username'];
    }
}
if ($l2_users_stmt) mysqli_stmt_close($l2_users_stmt);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        log_error("CSRF token validation failed for update attempt on observations", ['id' => $_POST['id'] ?? 'unknown']);
        die("Security Validation Failed: CSRF Token Mismatch.");
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    $fields = [];
    $params = [];
    $types = "";

    // SUPER ADMIN & L1: Update L1 Fields
    if (canEditL1()) {
        $observation_names = cleanInput($_POST['observation_names']);
        $team_names = isset($_POST['team_name']) ? implode(', ', $_POST['team_name']) : '';
        $team_name = cleanInput($team_names);
        $start_date = cleanInput($_POST['start_date']);
        $technician_name = cleanInput($_POST['technician_name']);
        $l1_observation = cleanInput($_POST['l1_observation']);

        $fields[] = "observation_names = ?";
        $params[] = $observation_names;
        $types .= "s";

        $fields[] = "technician_name = ?";
        $params[] = $technician_name;
        $types .= "s";

        $fields[] = "team_name = ?";
        $params[] = $team_name;
        $types .= "s";

        $fields[] = "start_date = ?";
        $params[] = $start_date;
        $types .= "s";

        $fields[] = "l1_observation = ?";
        $params[] = $l1_observation;
        $types .= "s";

        // L1 By Logic
        $l1_observations_by = cleanInput($_POST['existing_l1_by']);
        if (empty($l1_observations_by)) {
            $l1_observations_by = $_SESSION['username'];
        }
        $fields[] = "l1_observations_by = ?";
        $params[] = $l1_observations_by;
        $types .= "s";

        // Image Logic
        $l1_image = $row['l1_image'];
        $l1_image_2 = $row['l1_image_2'];

        if (isset($_POST['remove_image_1']) && $_POST['remove_image_1'] == "1") {
            if (!empty($l1_image) && file_exists(ASSETS_PATH . "/" . $l1_image)) unlink(ASSETS_PATH . "/" . $l1_image);
            $l1_image = "";
        }
        if (isset($_POST['remove_image_2']) && $_POST['remove_image_2'] == "1") {
            if (!empty($l1_image_2) && file_exists(ASSETS_PATH . "/" . $l1_image_2)) unlink(ASSETS_PATH . "/" . $l1_image_2);
            $l1_image_2 = "";
        }

        if (isset($_FILES['l1_images']) && !empty($_FILES['l1_images']['name'][0])) {
            $total_files = count($_FILES['l1_images']['name']);
            $target_dir = ASSETS_PATH . '/uploads/';
            for ($i = 0; $i < $total_files; $i++) {
                if ($_FILES['l1_images']['error'][$i] == 0) {
                    $tmp_file = $_FILES["l1_images"]["tmp_name"][$i];
                    $check = getimagesize($tmp_file);
                    if ($check !== false) {
                        $max_size = 5 * 1024 * 1024; // 5MB
                        if ($_FILES["l1_images"]["size"][$i] > $max_size) {
                            log_error("File size exceeds limit", ['file' => $_FILES["l1_images"]["name"][$i], 'size' => $_FILES["l1_images"]["size"][$i]]);
                            $msg = "Error: File " . $_FILES["l1_images"]["name"][$i] . " exceeds the 5MB limit.";
                            echo "<script>alert(" . json_encode($msg) . "); window.history.back();</script>";
                            exit;
                        }
                        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg', 'image/webp'];
                        if (in_array($check['mime'], $allowed_mimes)) {
                            $slot = 0;
                            if (empty($l1_image)) $slot = 1;
                            elseif (empty($l1_image_2)) $slot = 2;
                            if ($slot > 0) {
                                $ext = pathinfo($_FILES["l1_images"]["name"][$i], PATHINFO_EXTENSION);
                                $fname = uniqid() . "_" . $slot . "." . $ext;
                                if (move_uploaded_file($tmp_file, $target_dir . $fname)) {
                                    if ($slot == 1) $l1_image = "uploads/" . $fname;
                                    else $l1_image_2 = "uploads/" . $fname;
                                }
                            }
                        } else {
                            log_error("Invalid file type uploaded", ['mime' => $check['mime']]);
                        }
                    } else {
                        log_error("File is not an image", ['file' => $_FILES["l1_images"]["name"][$i]]);
                    }
                }
            }
        }
        $fields[] = "l1_image = ?";
        $params[] = $l1_image;
        $types .= "s";

        $fields[] = "l1_image_2 = ?";
        $params[] = $l1_image_2;
        $types .= "s";
    }

    // ADMIN & L2: Update L2 Fields
    if (canEditL2()) {
        $l2_observation = cleanInput($_POST['l2_observation']);
        $fields[] = "l2_observation = ?";
        $params[] = $l2_observation;
        $types .= "s";

        // L2 By Logic
        $l2_observations_by = cleanInput($_POST['existing_l2_by']);
        if (empty($l2_observations_by) && !empty($l2_observation)) {
            $l2_observations_by = $_SESSION['username'];
        }
        $fields[] = "l2_observations_by = ?";
        $params[] = $l2_observations_by;
        $types .= "s";
    }

    // Common Updates
    $fields[] = "edited_by = ?";
    $params[] = $_SESSION['username'];
    $types .= "s";

    $fields[] = "edited_at = NOW()";

    if (!empty($fields)) {
        $sql = "UPDATE observations SET " . implode(", ", $fields) . " WHERE serial_no = ?";
        $params[] = $id;
        $types .= "i";

        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            if (mysqli_stmt_execute($stmt)) {
                // Send email assignment if technician changed
                if (isset($technician_name) && !empty($technician_name) && $technician_name !== $row['technician_name']) {
                    sendAssignmentEmail($conn, $technician_name, $observation_names ?? $row['observation_names'], $team_name ?? $row['team_name']);
                }
                echo "<script>alert('Record updated successfully'); window.location.href='" . BASE_URL . "/modules/observations/view';</script>";
            } else {
                log_error("Update Error for observations", ['id' => $id, 'error' => mysqli_stmt_error($stmt)]);
                echo "<script>alert('Critical Error: Failed to update record.'); window.location.href='" . BASE_URL . "/modules/observations/view';</script>";
            }
            mysqli_stmt_close($stmt);
        } else {
            log_error("Prepare Error for observations update", ['error' => mysqli_error($conn)]);
            echo "<script>alert('Critical Error: Internal server error.'); window.location.href='" . BASE_URL . "/modules/observations/view';</script>";
        }
    } else {
        echo "<script>alert('No changes to save.'); window.location.href='" . BASE_URL . "/modules/observations/view';</script>";
    }
    mysqli_close($conn);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Observations | Shift Handover</title>
    <link rel="icon" type="image/png" href="<?= ASSETS_URL ?>/uploads/bkash_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <script src="<?= ASSETS_URL ?>/js/script.js" defer></script>
    <style>
        .image-preview-slot {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: var(--transition-base);
        }

        .image-preview-slot img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-preview-slot.removed {
            filter: grayscale(1) blur(2px);
            opacity: 0.5;
        }

        .overlay-btn {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 10;
        }

        .section-divider {
            height: 1px;
            background: linear-gradient(to right, transparent, rgba(0,0,0,0.1), transparent);
            margin: 2rem 0;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php include INCLUDES_PATH . '/sidebar.php'; ?>
        <div class="main-content">
            <div class="container-fluid ps-0 mt-3">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="view-header fw-bold mb-1"><i class="fa-solid fa-clipboard-list text-teal me-2"></i> Update Operational Observation</h1>
                        <p class="text-muted small mb-0">Refine investigation findings, L1 evidence, and L2 analyst feedback.</p>
                    </div>
                    <a href="<?= BASE_URL ?>/modules/observations/view" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm fw-bold">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to List
                    </a>
                </div>

                <div class="row justify-content-center">
                    <div class="col-lg-10 col-xl-8">
                        <div class="glass-card p-5 shadow-lg border-0 mb-5">
                            <form method="POST" action="" enctype="multipart/form-data">
                                <?php echo getCsrfField(); ?>
                                <input type="hidden" name="id" value="<?php echo $row['serial_no']; ?>">

                                <!-- L1 Section -->
                                <div class="mb-5">
                                    <div class="d-flex align-items-center mb-4">
                                        <div class="icon-box bg-teal-soft text-teal me-3">
                                            <i class="fa-solid fa-signature"></i>
                                        </div>
                                        <h4 class="fw-bold mb-0">Level 1 Investigation</h4>
                                    </div>

                                    <div class="row g-4">
                                        <div class="col-md-7">
                                            <label class="form-label small fw-bold text-muted text-uppercase">Observation Name</label>
                                            <?php if (canEditL1()): ?>
                                                <input type="text" class="form-control bg-light border-0 p-3" name="observation_names" 
                                                    value="<?php echo htmlspecialchars($row['observation_names']); ?>" required>
                                            <?php else: ?>
                                                <div class="p-3 bg-light border-0 rounded-3 text-dark"><?php echo htmlspecialchars($row['observation_names']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small fw-bold text-muted text-uppercase">Timestamp</label>
                                            <?php if (canEditL1()): ?>
                                                <input type="datetime-local" class="form-control bg-light border-0 p-3" name="start_date" 
                                                    value="<?php echo date('Y-m-d\TH:i', strtotime($row['start_date'])); ?>" required>
                                            <?php else: ?>
                                                <div class="p-3 bg-light border-0 rounded-3 text-dark"><?php echo date('d M, Y H:i', strtotime($row['start_date'])); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="row g-4 mt-2">
                                        <div class="col-md-12">
                                            <label class="form-label small fw-bold text-muted text-uppercase">Impacted Teams</label>
                                            <?php if (canEditL1()): ?>
                                                <!-- Team selection code remains here -->
                                                <div class="dropdown custom-team-dropdown">
                                                    <button class="btn btn-white border w-100 text-start d-flex justify-content-between align-items-center py-3 px-3 rounded-3 shadow-sm dropdown-toggle" type="button" id="teamDropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                                        <span id="teamDropdownLabel">Select Teams</span>
                                                    </button>
                                                    <div class="dropdown-menu w-100 p-2 shadow-lg border-0 rounded-4 mt-2" aria-labelledby="teamDropdown" style="max-height: 280px; overflow-y: auto; background: rgba(255,255,255,0.98); backdrop-filter: blur(15px);">
                                                        <div class="dropdown-item p-2 rounded hover-bg-light transition-all border-bottom mb-2 fw-bold text-primary d-flex justify-content-between align-items-center" style="cursor: pointer;" id="selectAllTeams">
                                                            <span>SELECT ALL TEAMS</span>
                                                            <i class="fa-solid fa-check-double opacity-50"></i>
                                                        </div>
                                                        <?php
                                                        $all_teams = [
                                                            'Tech Service Operations', 
                                                            'Tech Service Delivery', 
                                                            'Central Monitoring Center', 
                                                            'Network Operations', 
                                                            'Data Center Operations', 
                                                            'Server Storage & Backup Management', 
                                                            'Incident & Performance Management', 
                                                            'Database Management'
                                                        ];
                                                        $current_teams = explode(', ', $row['team_name'] ?? '');
                                                        foreach($all_teams as $index => $team) {
                                                            $selected = in_array($team, $current_teams) ? 'checked' : '';
                                                            echo '
                                                            <div class="dropdown-item p-2 rounded transition-all team-item d-flex justify-content-between align-items-center mb-1" style="cursor: pointer;" data-value="' . $team . '" data-id="team_' . $index . '">
                                                                <span class="small fw-medium">' . $team . '</span>
                                                                <i class="fa-solid fa-check check-icon opacity-0"></i>
                                                                <input class="team-checkbox d-none" type="checkbox" name="team_name[]" value="' . $team . '" id="team_' . $index . '" ' . $selected . '>
                                                            </div>';
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                                <div id="selectedTeamsContainer" class="d-flex flex-wrap gap-2 mt-2">
                                                    <!-- Badges injected here -->
                                                </div>
                                                <small class="text-muted d-block mt-2"><i class="fa-solid fa-circle-info me-1"></i> Selection synced with previous record.</small>


                                            <?php else: ?>
                                                <div class="p-3 bg-light border-0 rounded-3 text-dark">
                                                    <?php
                                                    $current_teams = explode(', ', $row['team_name'] ?? '');
                                                    foreach ($current_teams as $team) {
                                                        echo '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 me-1 mb-1" style="font-size: 0.65rem;">' . htmlspecialchars($team) . '</span>';
                                                    }
                                                    ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Assigned Technician Below Teams -->
                                        <div class="col-12 mt-3">
                                            <label class="form-label small fw-bold text-muted text-uppercase mb-2"><i class="fa-solid fa-user-gear me-1"></i> Assigned Technician (L2)</label>
                                            <?php if (canEditL1()): ?>
                                                <div class="dropdown custom-technician-dropdown">
                                                    <button class="btn btn-white border w-100 text-start d-flex justify-content-between align-items-center py-3 px-3 rounded-3 shadow-none dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" style="background: #f8f9fa;">
                                                        <span class="tech-dropdown-label"><?= !empty($row['technician_name']) ? e($row['technician_name']) : 'Select Technician from L2 Roster' ?></span>
                                                    </button>
                                                    <div class="dropdown-menu w-100 p-2 shadow-lg border-0 rounded-4 mt-2" style="max-height: 350px; overflow-y: auto; background: rgba(255,255,255,0.98); backdrop-filter: blur(15px);">
                                                        <div class="px-3 py-2 border-bottom mb-2">
                                                            <input type="text" class="form-control form-control-sm border-0 bg-light rounded-pill px-3 tech-search-field" placeholder="Search by name..." autocomplete="off">
                                                        </div>
                                                        <div class="tech-list-container">
                                                            <?php if (empty($l2_users)): ?>
                                                                <div class="dropdown-item p-3 text-muted small italic text-center">No L2 Analyst found</div>
                                                            <?php else: ?>
                                                                <?php foreach ($l2_users as $uname): ?>
                                                                    <?php $isSelected = ($row['technician_name'] === $uname); ?>
                                                                    <div class="dropdown-item p-3 rounded transition-all tech-item d-flex justify-content-between align-items-center mb-1 <?= $isSelected ? 'bg-primary-soft text-primary' : '' ?>" style="cursor: pointer;" data-value="<?= e($uname) ?>">
                                                                        <div class="d-flex align-items-center">
                                                                            <div class="avatar-sm bg-primary-soft text-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 10px;">
                                                                                <i class="fa-solid fa-user"></i>
                                                                            </div>
                                                                            <span class="small fw-medium"><?= e($uname) ?></span>
                                                                        </div>
                                                                        <i class="fa-solid fa-circle-check check-icon text-success <?= $isSelected ? '' : 'opacity-0' ?>"></i>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <input type="hidden" name="technician_name" class="selected-tech-input" value="<?= e($row['technician_name'] ?? '') ?>">
                                                </div>
                                            <?php else: ?>
                                                <div class="p-3 bg-info bg-opacity-5 border border-info border-opacity-10 rounded-3 text-dark">
                                                    <i class="fa-solid fa-user-tie me-2 text-info"></i>
                                                    <span class="fw-bold"><?php echo htmlspecialchars($row['technician_name'] ?: 'Not Assigned'); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-bold text-muted text-uppercase">Timestamp</label>
                                            <?php if (canEditL1()): ?>
                                                <input type="datetime-local" class="form-control bg-light border-0 p-3" name="start_date" 
                                                    value="<?php echo date('Y-m-d\TH:i', strtotime($row['start_date'])); ?>" required>
                                            <?php else: ?>
                                                <div class="p-3 bg-light border-0 rounded-3 text-dark"><?php echo date('d M, Y H:i', strtotime($row['start_date'])); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small fw-bold text-muted text-uppercase">L1 Investigation Findings</label>
                                            <?php if (canEditL1()): ?>
                                                <textarea class="form-control bg-light border-0 p-3" name="l1_observation" rows="4" required><?php echo htmlspecialchars($row['l1_observation']); ?></textarea>
                                            <?php else: ?>
                                                <div class="p-3 bg-light border-0 rounded-3 text-dark min-height-100"><?php echo nl2br(htmlspecialchars($row['l1_observation'])); ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label small fw-bold text-muted text-uppercase">Evidence Images</label>
                                            <div class="d-flex gap-3 mb-3">
                                                <?php for($i=1; $i<=2; $i++): $img_key = ($i==1) ? 'l1_image' : 'l1_image_2'; ?>
                                                    <?php if (!empty($row[$img_key])): ?>
                                                        <div class="image-preview-slot" id="container_image_<?php echo $i; ?>">
                                                            <img src="<?= e(ASSETS_URL . '/' . ltrim($row[$img_key], '/')) ?>" alt="Evidence <?php echo $i; ?>">
                                                            <?php if (canEditL1()): ?>
                                                                <div class="overlay-btn">
                                                                    <button type="button" class="btn btn-danger btn-sm rounded-circle px-2" onclick="toggleRemove(<?php echo $i; ?>)" id="remove_btn_<?php echo $i; ?>">
                                                                        <i class="fa-solid fa-trash-can"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-success btn-sm rounded-pill px-3 d-none" onclick="toggleRemove(<?php echo $i; ?>)" id="undo_btn_<?php echo $i; ?>">
                                                                        Undo
                                                                    </button>
                                                                </div>
                                                                <input type="hidden" name="remove_image_<?php echo $i; ?>" id="remove_image_<?php echo $i; ?>" value="0">
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                            <?php if (canEditL1()): ?>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-0"><i class="fa-solid fa-camera"></i></span>
                                                    <input type="file" class="form-control bg-light border-0" name="l1_images[]" multiple onchange="validateImageCount(this)" accept="image/*">
                                                </div>
                                                <div class="d-flex justify-content-between mt-2">
                                                    <small class="text-muted"><i class="fa-solid fa-circle-info me-1"></i> Max 2 images. Total limit 5MB per file.</small>
                                                    <small id="imageCountBadge" class="badge bg-light text-muted border px-2 py-1">0 / 2 selected</small>
                                                </div>
                                                <small class="text-muted d-block mt-1">Uploading new images will fill empty slots or replace removed ones.</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <input type="hidden" name="existing_l1_by" value="<?php echo htmlspecialchars($row['l1_observations_by']); ?>">
                                </div>

                                <div class="section-divider"></div>

                                <!-- L2 Section -->
                                <div class="mb-5">
                                    <div class="d-flex align-items-center mb-4">
                                        <div class="icon-box bg-info-soft text-info me-3">
                                            <i class="fa-solid fa-user-check"></i>
                                        </div>
                                        <h4 class="fw-bold mb-0">Level 2 Analysis & Feedback</h4>
                                    </div>

                                    <?php if (canEditL2()): ?>
                                        <div class="col-12">
                                            
                                            <textarea class="form-control border-info bg-opacity-5 p-3" name="l2_observation" rows="4" placeholder="Enter L2 analyst feedback here..."><?php echo htmlspecialchars($row['l2_observation'] ?? ''); ?></textarea>
                                        </div>
                                        <input type="hidden" name="existing_l2_by" value="<?php echo htmlspecialchars($row['l2_observations_by'] ?? ''); ?>">
                                    <?php else: ?>
                                        <div class="p-4 rounded-3 border-start border-info border-4" style="background: rgba(13, 202, 240, 0.05);">
                                            <?php if (!empty($row['l2_observation'])): ?>
                                                <p class="text-dark mb-2"><?php echo nl2br(htmlspecialchars($row['l2_observation'])); ?></p>
                                                <small class="text-muted"><i class="fa-solid fa-user-tag me-1"></i> Evaluated by <b><?php echo htmlspecialchars($row['l2_observations_by']); ?></b></small>
                                            <?php else: ?>
                                                <span class="text-muted italic"><i class="fa-solid fa-hourglass-half me-2"></i> Awaiting L2 analysis...</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="alert alert-secondary border-0 rounded-3 small mb-5">
                                    <div class="row align-items-center">
                                        <div class="col-sm-6 border-end border-2 border-white">
                                            <i class="fa-solid fa-user-pen me-2"></i> L1 Reported By: <b><?php echo htmlspecialchars($row['l1_observations_by']); ?></b>
                                        </div>
                                        <div class="col-sm-6">
                                            <i class="fa-solid fa-history me-2"></i> Last audit by <b><?php echo htmlspecialchars($row['edited_by'] ?? 'System'); ?></b> on <?php echo date('d M, Y H:i', strtotime($row['edited_at'] ?? $row['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary py-3 fw-bold rounded-pill shadow-lg" style="background: #2a9d8f; border: none;">
                                        <i class="fa-solid fa-save me-2"></i> Save Observation Updates
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div> <!-- End container-fluid -->
        </div> <!-- End main-content -->
    </div> <!-- End dashboard-container -->

    <script>
        function toggleRemove(id) {
            const container = document.getElementById('container_image_' + id);
            const input = document.getElementById('remove_image_' + id);
            const removeBtn = document.getElementById('remove_btn_' + id);
            const undoBtn = document.getElementById('undo_btn_' + id);

            if (input.value == "0") {
                input.value = "1";
                container.classList.add('removed');
                removeBtn.classList.add('d-none');
                undoBtn.classList.remove('d-none');
            } else {
                input.value = "0";
                container.classList.remove('removed');
                removeBtn.classList.remove('d-none');
                undoBtn.classList.add('d-none');
            }
        }

        function validateImageCount(input) {
            const existing1 = document.getElementById('remove_image_1') ? (document.getElementById('remove_image_1').value == "0" ? 1 : 0) : 0;
            const existing2 = document.getElementById('remove_image_2') ? (document.getElementById('remove_image_2').value == "0" ? 1 : 0) : 0;
            const totalExisting = existing1 + existing2;
            
            if (input.files.length + totalExisting > 2) {
                alert("Maximum 2 images allowed across existing and new uploads.");
                input.value = "";
                return;
            }

            let currentBatchSize = 0;
            for (let i = 0; i < input.files.length; i++) {
                currentBatchSize += input.files[i].size;
                if (input.files[i].size > 5 * 1024 * 1024) {
                    alert("File " + input.files[i].name + " exceeds the 5MB limit.");
                    input.value = "";
                    return;
                }
            }
            
            // Log or show total size if needed, but the alert handles the primary feedback.
            console.log("Total selected size: " + (currentBatchSize / 1024 / 1024).toFixed(2) + " MB");
        }
    </script>
    <script src="<?= ASSETS_URL ?>/js/observations.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>



