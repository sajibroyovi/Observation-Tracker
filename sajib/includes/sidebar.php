<?php
// sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<button class="btn d-md-none position-fixed top-0 start-0 m-3 z-index-2000 text-white shadow" id="mobileSidebarToggle" style="background: var(--primary-blue); border-radius: 10px;">
    <i class="fa-solid fa-bars"></i>
</button>

<div class="sidebar" id="sidebar">
    <div class="sidebar-logo-top">
        <img src="<?php echo ASSETS_URL; ?>/uploads/bkash_logo.png" alt="bKash Logo">
    </div>
    <div class="sidebar-header">
        <div class="profile-img shadow-sm mx-auto mb-2">
            <?php echo e(strtoupper(substr($user_name, 0, 1))); ?>
        </div>
        <div class="user-name fw-bold"><?php echo htmlspecialchars($user_name); ?></div>
        <div class="user-role small opacity-75 text-uppercase letter-spacing-1">
            <?php
            if ($user_role === 'super_admin') echo 'Super Admin';
            elseif ($user_role === 'l1') echo 'L1 Operational';
            elseif ($user_role === 'admin') echo 'Administrator';
            elseif ($user_role === 'l2') echo 'L2 Analytical';
            else echo 'Unknown Role';
            ?>
        </div>
    </div>

    <!-- Global Search -->
    <div class="px-3 py-3">
        <div class="position-relative">
            <input type="text" id="globalSearch" class="form-control form-control-sm" 
                   placeholder="Search across modules..." 
                   autocomplete="off"
                   style="padding-left: 35px; border-radius: 20px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white;">
            <i class="fa-solid fa-search position-absolute" style="left: 12px; top: 50%; transform: translateY(-50%); opacity: 0.6;"></i>
        </div>
        <div id="searchResults" class="position-absolute bg-white rounded shadow-lg mt-2" 
             style="width: calc(100% - 2rem); max-height: 400px; overflow-y: auto; display: none; z-index: 1050; left: 1rem;">
        </div>
    </div>

    <ul class="nav-links">
        <li>
            <a href="<?php echo BASE_URL; ?>/" class="<?php echo ($current_page == 'index.php' || $current_page == 'main') ? 'active' : ''; ?>">
                <i class="fa-solid fa-gauge"></i> <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/users/profile" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/users/profile.php') !== false) ? 'active' : ''; ?>">
                <i class="fa-solid fa-user-circle"></i> <span>My Profile</span>
            </a>
        </li>
        
        <li class="nav-section-title mt-3 mb-2 px-3 small text-uppercase opacity-50">Operational Modules</li>
        
        <?php if ($user_role !== 'l1' && $user_role !== 'l2'): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/banners/view" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/banners/view.php') !== false) ? 'active' : ''; ?>">
                <i class="fa-solid fa-image"></i> <span>Promo Banner</span>
            </a>
        </li>
        <?php endif; ?>
        <?php if ($user_role !== 'l1' && $user_role !== 'l2'): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/campaigns/view" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/campaigns/view.php') !== false) ? 'active' : ''; ?>">
                <i class="fa-solid fa-bullhorn"></i> <span>Campaign</span>
            </a>
        </li>
        <?php endif; ?>
        <?php if ($user_role !== 'l1' && $user_role !== 'l2'): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/change_requests/view" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/change_requests/view.php') !== false) ? 'active' : ''; ?>">
                <i class="fa-solid fa-file-invoice"></i> <span>CR List</span>
            </a>
        </li>
        <?php endif; ?>
        <?php if ($user_role !== 'l1' && $user_role !== 'l2'): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/ed/view" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/ed/view.php') !== false) ? 'active' : ''; ?>">
                <i class="fa-solid fa-toggle-on"></i> <span>Enable/Disable</span>
            </a>
        </li>
        <?php endif; ?>
        <?php if ($user_role !== 'l1' && $user_role !== 'l2'): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/outages/view" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/outages/view.php') !== false) ? 'active' : ''; ?>">
                <i class="fa-solid fa-triangle-exclamation"></i> <span>Service Outage</span>
            </a>
        </li>
        <?php endif; ?>
        <?php if ($user_role !== 'l1' && $user_role !== 'l2'): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/pm/view" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/pm/view.php') !== false) ? 'active' : ''; ?>">
                <i class="fa-solid fa-envelope"></i> <span>Pending Mail</span>
            </a>
        </li>
        <?php endif; ?>
        <?php if ($user_role !== 'l1' && $user_role !== 'l2'): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/sc/view" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/sc/view.php') !== false) ? 'active' : ''; ?>">
                <i class="fa-solid fa-shield-halved"></i> <span>Security Mail</span>
            </a>
        </li>
        <?php endif; ?>
        <?php if ($user_role !== 'l1' && $user_role !== 'l2'): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/ssl/view" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/ssl/view.php') !== false) ? 'active' : ''; ?>">
                <i class="fa-solid fa-lock"></i> <span>SSL Certificate</span>
            </a>
        </li>
        <?php endif; ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/observations/view" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/observations/view.php') !== false) ? 'active' : ''; ?>">
                <i class="fa-solid fa-clipboard-check"></i> <span>Observations</span>
            </a>
        </li>

        <?php if (isSuperAdmin()): ?>
            <li class="nav-section-title mt-3 mb-2 px-3 small text-uppercase opacity-50">Administration</li>
            <li>
                <a href="<?php echo BASE_URL; ?>/modules/users/manage" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/users/manage.php') !== false || strpos($_SERVER['PHP_SELF'], '/users/add_user.php') !== false || strpos($_SERVER['PHP_SELF'], '/users/edit_user.php') !== false) ? 'active' : ''; ?>">
                    <i class="fa-solid fa-users-cog"></i> <span>Manage Users</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <div class="sidebar-footer">
        <div class="night-mode-wrapper px-3 mb-3">
            <div class="d-flex align-items-center justify-content-between">
                <span><i class="fa-solid fa-moon me-2"></i> Night Mode</span>
                <label class="switch mb-0">
                    <input type="checkbox" id="night-mode-toggle-sidebar">
                    <span class="slider round"></span>
                </label>
            </div>
        </div>
        <a href="<?php echo BASE_URL; ?>/logout" class="logout-link">
            <i class="fa-solid fa-power-off"></i> <span>LOGOUT</span>
        </a>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<script src="<?php echo ASSETS_URL; ?>/js/search.js"></script>
