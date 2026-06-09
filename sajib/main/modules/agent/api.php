<?php
/**
 * ============================================================
 * AI AGENT API - Shift Handover Intelligence Engine
 * ============================================================
 * Rule-based analytics backend. Parses user intent via
 * keyword matching and executes safe SQL queries to return
 * structured JSON responses.
 *
 * Security: Session-authenticated, parameterized queries only.
 * No external API dependencies — works 100% locally.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../config/app.php';
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Only accept POST JSON requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// CSRF validation
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

header('Content-Type: application/json');

$user_message = trim(strip_tags($input['message'] ?? ''));
if (mb_strlen($user_message) > 500) {
    $user_message = mb_substr($user_message, 0, 500);
}

// ============================================================
// INTENT DETECTION
// ============================================================
function detectIntent(string $msg): string {
    $msg = mb_strtolower($msg);

    // 1. Action / Write intents (checked first to avoid matching general nouns)
    if (preg_match('/(resolve\s+(?:outage|incident)|close\s+incident|resolve\s+it|resolve\s+the\s+first|resolve\s+first)/i', $msg)) {
        return 'write_resolve_outage';
    }
    if (preg_match('/(mark\s+answered|close\s+mail|answer\s+email|reply\s+email|mark\s+as\s+answered|answer\s+it|answer\s+the\s+first|answer\s+first|mark\s+answered\s+(?:security|pending)?\s*mail)/i', $msg)) {
        return 'write_answer_mail';
    }
    if (preg_match('/(create\s+observation|add\s+observation|new\s+observation|log\s+observation|add\s+note|create\s+note)/i', $msg)) {
        return 'write_observation';
    }
    if (preg_match('/(compare|vs|difference|delta|previous\s+shift|shift\s+comparison|last\s+shift)/i', $msg)) {
        return 'comparison';
    }
    if (preg_match('/(peak\s+hour|peak\s+time|timing|most\s+common\s+time|peak\s+outage)/i', $msg)) {
        return 'peak_hours';
    }
    if ($msg === 'greet' || $msg === 'proactive_check' || $msg === 'init_check') {
        return 'proactive_check';
    }

    // 2. Query / Info intents
    $intents = [
        'summary'       => ['summary', 'handover', 'overview', 'brief', 'shift', 'today', 'report', 'status all', 'full status'],
        'outage'        => ['outage', 'down', 'incident', 'service issue', 'downtime', 'disruption'],
        'ssl'           => ['ssl', 'certificate', 'cert', 'expir', 'tls', 'https'],
        'pending_mail'  => ['pending mail', 'pending email', 'unanswered', 'follow-up', 'follow up'],
        'security'      => ['security', 'alert', 'threat', 'hack', 'breach', 'security mail', 'escalat'],
        'campaign'      => ['campaign', 'promo', 'promotion', 'marketing', 'offer'],
        'banner'        => ['banner', 'promo banner', 'hero', 'advertisement'],
        'cr'            => ['cr', 'change request', 'change order', 'cr list', 'change management'],
        'observation'   => ['observation', 'obs', 'l1', 'l2', 'check', 'inspection', 'monitor'],
        'ed'            => ['enable', 'disable', 'service status', 'toggle', 'enable/disable', 'ed'],
        'analyze'       => ['analyze', 'analyse', 'trend', 'pattern', 'insight', 'analytics', 'performance'],
        'help'          => ['help', 'what can you', 'commands', 'how to use', 'guide', 'options', 'features'],
        'greeting'      => ['hello', 'hi', 'hey', 'good morning', 'good evening', 'good night', 'howdy'],
    ];

    foreach ($intents as $intent => $keywords) {
        foreach ($keywords as $kw) {
            if (str_contains($msg, $kw)) {
                return $intent;
            }
        }
    }

    return 'unknown';
}

function detectMultipleIntents(string $msg): array {
    $msg = mb_strtolower($msg);
    $detected = [];
    
    $keywords_map = [
        'outage'       => ['outage', 'down', 'incident', 'service issue', 'downtime'],
        'ssl'          => ['ssl', 'certificate', 'cert', 'expir', 'tls'],
        'pending_mail' => ['pending mail', 'unanswered', 'follow-up'],
        'security'     => ['security', 'alert', 'threat', 'security mail'],
        'campaign'     => ['campaign', 'promo', 'marketing'],
        'banner'       => ['banner', 'promo banner'],
        'cr'           => ['cr', 'change request', 'cr list'],
        'observation'  => ['observation', 'obs', 'l1', 'l2'],
        'ed'           => ['enable', 'disable', 'service status', 'ed']
    ];
    
    foreach ($keywords_map as $intent => $kws) {
        foreach ($kws as $kw) {
            if (str_contains($msg, $kw)) {
                $detected[] = $intent;
                break;
            }
        }
    }
    
    return array_unique($detected);
}

// Helper for safe DB query execution and exact error logging
function safeQuery($conn, $sql) {
    if (!$conn) {
        log_error("Agent DB Connection missing or null.");
        return null;
    }
    $res = mysqli_query($conn, $sql);
    if ($res === false) {
        log_error("Agent SQL Query Failed", ["query" => $sql, "error" => mysqli_error($conn)]);
        return null;
    }
    return $res;
}

function fetchOutages(): array {
    $conn = getConnection();
    $stats = ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'resolved' => 0];
    // Use LOWER() for case-insensitive matching regardless of how status was stored
    $result = safeQuery($conn, "SELECT COUNT(*) as total, SUM(LOWER(status)='pending') as pending, SUM(LOWER(status)='in_progress') as in_progress, SUM(LOWER(status)='resolved') as resolved FROM service_outage");
    if ($result) {
        $stats = mysqli_fetch_assoc($result) ?: $stats;
    }

    $recent = [];
    $r = safeQuery($conn, "SELECT serial_no, details, incident_id, technician, status, created_at FROM service_outage ORDER BY serial_no DESC LIMIT 5");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $recent[] = $row;
        }
    }
    $_SESSION['agent_last_results']['outage'] = $recent;
    return ['stats' => $stats, 'recent' => $recent];
}

function fetchSSL(): array {
    $conn = getConnection();
    $stats = ['total' => 0, 'pending' => 0, 'renewed' => 0, 'expiring_soon' => 0, 'expired' => 0];
    $stats_r = safeQuery($conn, "SELECT COUNT(*) as total, SUM(renewal_status='Pending') as pending, SUM(renewal_status='Renewed') as renewed, SUM(DATEDIFF(expiration_date, CURDATE()) <= 30 AND DATEDIFF(expiration_date, CURDATE()) >= 0) as expiring_soon, SUM(expiration_date < CURDATE()) as expired FROM ssl_certificate");
    if ($stats_r) {
        $stats = mysqli_fetch_assoc($stats_r) ?: $stats;
    }

    $critical = [];
    $r = safeQuery($conn, "SELECT certificate_name, expiration_date, renewal_status, DATEDIFF(expiration_date, CURDATE()) as days_left FROM ssl_certificate WHERE expiration_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) ORDER BY expiration_date ASC LIMIT 5");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $critical[] = $row;
        }
    }
    return ['stats' => $stats, 'critical' => $critical];
}

function fetchPendingMail(): array {
    $conn = getConnection();
    $stats = ['total' => 0, 'pending' => 0, 'answered' => 0, 'follow_up' => 0];
    $stats_r = safeQuery($conn, "SELECT COUNT(*) as total, SUM(LOWER(status)='pending') as pending, SUM(LOWER(status)='answered') as answered, SUM(LOWER(status)='follow_up') as follow_up FROM pending_mail");
    if ($stats_r) {
        $stats = mysqli_fetch_assoc($stats_r) ?: $stats;
    }

    $recent = [];
    $r = safeQuery($conn, "SELECT serial_no, subject_line, priority, status, created_at FROM pending_mail ORDER BY serial_no DESC LIMIT 5");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $recent[] = $row;
        }
    }
    $_SESSION['agent_last_results']['pending_mail'] = $recent;
    return ['stats' => $stats, 'recent' => $recent];
}

function fetchSecurity(): array {
    $conn = getConnection();
    $stats = ['total' => 0, 'pending' => 0, 'answered' => 0, 'follow_up' => 0];
    $stats_r = safeQuery($conn, "SELECT COUNT(*) as total, SUM(LOWER(status)='pending') as pending, SUM(LOWER(status)='answered') as answered, SUM(LOWER(status)='follow_up') as follow_up FROM security_mail");
    if ($stats_r) {
        $stats = mysqli_fetch_assoc($stats_r) ?: $stats;
    }

    $recent = [];
    $r = safeQuery($conn, "SELECT serial_no, subject_line, priority, status, created_at FROM security_mail ORDER BY serial_no DESC LIMIT 5");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $recent[] = $row;
        }
    }
    $_SESSION['agent_last_results']['security'] = $recent;
    return ['stats' => $stats, 'recent' => $recent];
}

function fetchCampaigns(): array {
    $conn = getConnection();
    $stats = ['total' => 0, 'active' => 0, 'inactive' => 0, 'completed' => 0];
    $stats_r = safeQuery($conn, "SELECT COUNT(*) as total, SUM(status='active') as active, SUM(status='inactive') as inactive, SUM(status='completed') as completed FROM campaign");
    if ($stats_r) {
        $stats = mysqli_fetch_assoc($stats_r) ?: $stats;
    }

    $recent = [];
    $r = safeQuery($conn, "SELECT campaign_name, start_date, status, description FROM campaign ORDER BY serial_no DESC LIMIT 5");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $recent[] = $row;
        }
    }
    return ['stats' => $stats, 'recent' => $recent];
}

function fetchBanners(): array {
    $conn = getConnection();
    $stats = ['total' => 0, 'live' => 0, 'scheduled' => 0, 'draft' => 0];
    $stats_r = safeQuery($conn, "SELECT COUNT(*) as total, SUM(status='live') as live, SUM(status='scheduled') as scheduled, SUM(status='draft') as draft FROM promo_banner");
    if ($stats_r) {
        $stats = mysqli_fetch_assoc($stats_r) ?: $stats;
    }

    $recent = [];
    $r = safeQuery($conn, "SELECT subject_line, status, start_time FROM promo_banner ORDER BY serial_no DESC LIMIT 5");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $recent[] = $row;
        }
    }
    return ['stats' => $stats, 'recent' => $recent];
}

function fetchCR(): array {
    $conn = getConnection();
    $stats = ['total' => 0, 'downtime' => 0, 'no_impact' => 0, 'fluctuation' => 0];
    $stats_r = safeQuery($conn, "SELECT COUNT(*) as total, SUM(downtime='0') as downtime, SUM(downtime='1') as no_impact, SUM(downtime='2') as fluctuation FROM cr_list");
    if ($stats_r) {
        $stats = mysqli_fetch_assoc($stats_r) ?: $stats;
    }

    $recent = [];
    $r = safeQuery($conn, "SELECT cr_subject, impacted_area, downtime, cr_start_time FROM cr_list ORDER BY serial_no DESC LIMIT 5");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $recent[] = $row;
        }
    }
    return ['stats' => $stats, 'recent' => $recent];
}

function fetchObservations(): array {
    $conn = getConnection();
    $stats = ['total' => 0, 'pending' => 0, 'complete' => 0];
    $stats_r = safeQuery($conn, "SELECT COUNT(*) as total, SUM(l2_observation IS NULL OR l2_observation='') as pending, SUM(l2_observation IS NOT NULL AND l2_observation!='') as complete FROM observations");
    if ($stats_r) {
        $stats = mysqli_fetch_assoc($stats_r) ?: $stats;
    }

    $recent = [];
    $r = safeQuery($conn, "SELECT serial_no, observation_names, technician_name, team_name, l2_observation, start_date FROM observations ORDER BY serial_no DESC LIMIT 5");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $recent[] = $row;
        }
    }
    $_SESSION['agent_last_results']['observation'] = $recent;
    return ['stats' => $stats, 'recent' => $recent];
}

function fetchED(): array {
    $conn = getConnection();
    $stats = ['total' => 0, 'enabled' => 0, 'disabled' => 0, 'hidden' => 0];
    $stats_r = safeQuery($conn, "SELECT COUNT(*) as total, SUM(action_taken='0') as enabled, SUM(action_taken='1') as disabled, SUM(action_taken='2') as hidden FROM enable_disable");
    if ($stats_r) {
        $stats = mysqli_fetch_assoc($stats_r) ?: $stats;
    }

    $disabled = [];
    $r = safeQuery($conn, "SELECT service_name, action_taken, reference, action_date, action_taken_by FROM enable_disable WHERE action_taken='1' ORDER BY serial_no DESC LIMIT 5");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $disabled[] = $row;
        }
    }
    return ['stats' => $stats, 'disabled' => $disabled];
}

function fetchFullSummary(): array {
    return [
        'outage'      => fetchOutages(),
        'ssl'         => fetchSSL(),
        'pending_mail'=> fetchPendingMail(),
        'security'    => fetchSecurity(),
        'campaigns'   => fetchCampaigns(),
        'banners'     => fetchBanners(),
        'cr'          => fetchCR(),
        'observation' => fetchObservations(),
        'ed'          => fetchED(),
    ];
}

// ============================================================
// RESPONSE BUILDERS
// ============================================================

function buildSummaryResponse(): array {
    $data = fetchFullSummary();
    $shift = getCurrentShift();
    $now = date('d M Y, h:i A');

    $alerts = [];
    $warnings = [];
    $ok = [];

    // Check each module for issues
    $outage_pending = (int)($data['outage']['stats']['pending'] ?? 0);
    $outage_in_progress = (int)($data['outage']['stats']['in_progress'] ?? 0);
    $outage_active = $outage_pending + $outage_in_progress;
    if ($outage_active > 0)
        $alerts[] = "🔴 **{$outage_active} active/pending service outage(s)** need immediate attention.";
    else
        $ok[] = "✅ Service Outages: All resolved";

    $ssl_expiring = (int)($data['ssl']['stats']['expiring_soon'] ?? 0);
    $ssl_expired  = (int)($data['ssl']['stats']['expired'] ?? 0);
    if ($ssl_expired > 0)
        $alerts[] = "🔴 **{$ssl_expired} SSL certificate(s) have already expired!**";
    if ($ssl_expiring > 0)
        $warnings[] = "⚠️ **{$ssl_expiring} SSL certificate(s) expiring within 30 days.**";
    if ($ssl_expired === 0 && $ssl_expiring === 0)
        $ok[] = "✅ SSL Certificates: All valid";

    $security_pending = (int)($data['security']['stats']['pending'] ?? 0);
    if ($security_pending > 0)
        $warnings[] = "⚠️ **{$security_pending} pending security mail(s)** require review.";
    else
        $ok[] = "✅ Security Alerts: None pending";

    $pm_pending = (int)($data['pending_mail']['stats']['pending'] ?? 0);
    if ($pm_pending > 0)
        $warnings[] = "⚠️ **{$pm_pending} pending mail(s)** awaiting response.";
    else
        $ok[] = "✅ Pending Mails: All answered";

    $cr_total = (int)($data['cr']['stats']['total'] ?? 0);
    $cr_downtime = (int)($data['cr']['stats']['downtime'] ?? 0);
    if ($cr_total > 0)
        $ok[] = "📋 CR List: **{$cr_total} total** change request(s) (**{$cr_downtime}** with downtime)";

    $obs_pending = (int)($data['observation']['stats']['pending'] ?? 0);
    if ($obs_pending > 0)
        $warnings[] = "⚠️ **{$obs_pending} observation(s)** pending L2 analysis.";
    else
        $ok[] = "✅ Observations: All complete";

    $campaigns_active = (int)($data['campaigns']['stats']['active'] ?? 0);
    $ok[] = "📣 Campaigns: **{$campaigns_active} active** campaign(s) running.";

    $disabled_count = (int)($data['ed']['stats']['disabled'] ?? 0);
    if ($disabled_count > 0)
        $warnings[] = "⚠️ **{$disabled_count} service(s) currently disabled.**";

    // Build text
    $message = "## 📊 Shift Handover Summary\n";
    $message .= "**Shift:** {$shift} &nbsp;|&nbsp; **Time:** {$now}\n\n";

    if (!empty($alerts)) {
        $message .= "### 🚨 Critical Alerts\n" . implode("\n", $alerts) . "\n\n";
    }
    if (!empty($warnings)) {
        $message .= "### ⚠️ Warnings\n" . implode("\n", $warnings) . "\n\n";
    }
    if (!empty($ok)) {
        $message .= "### ✅ All Good\n" . implode("\n", $ok) . "\n\n";
    }

    $total_issues = count($alerts) + count($warnings);
    if ($total_issues === 0) {
        $message .= "_Everything looks great for this shift! No critical issues found._ 🎉";
    } else {
        $message .= "_Found **{$total_issues} item(s)** that need attention. Ask me about any module for details._";
    }

    return ['message' => $message, 'type' => 'summary', 'data' => $data];
}

function buildOutageResponse(): array {
    $d = fetchOutages();
    $pending     = (int)($d['stats']['pending'] ?? 0);
    $in_progress = (int)($d['stats']['in_progress'] ?? 0);
    $resolved    = (int)($d['stats']['resolved'] ?? 0);
    $total       = (int)($d['stats']['total'] ?? 0);
    $active      = $pending + $in_progress;

    $msg = "## 🔴 Service Outage Status\n\n";
    
    $bg_class = ($active > 0) ? 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-20' : 'bg-success bg-opacity-10 text-success border border-success border-opacity-20';
    $msg .= "<div class='p-3 rounded-3 mb-3 {$bg_class}'>";
    $msg .= "<div class='fw-bold mb-1'>" . ($active > 0 ? "⚠️ INCIDENTS ACTIVE" : "🟢 ALL SERVICES STABLE") . "</div>";
    $msg .= "<div class='small'>There are **{$active}** unresolved outage(s) and **{$resolved}** resolved today.</div>";
    $msg .= "</div>";

    $msg .= "| Metric | Count |\n|---|---|\n";
    $msg .= "| 🔴 Pending | **{$pending}** |\n";
    $msg .= "| 🔄 In Progress | **{$in_progress}** |\n";
    $msg .= "| ✅ Resolved | **{$resolved}** |\n";
    $msg .= "| 📋 Total | **{$total}** |\n\n";

    if ($active > 0) {
        $msg .= "### 🚨 Active Outages\n";
        foreach ($d['recent'] as $row) {
            $st = strtolower($row['status'] ?? '');
            if ($st === 'pending' || $st === 'in_progress') {
                $created = $row['created_at'] ? date('d M, H:i', strtotime($row['created_at'])) : 'Unknown';
                $details = htmlspecialchars(mb_substr($row['details'] ?? 'N/A', 0, 50), ENT_QUOTES);
                $status_badge = ($st === 'in_progress') ? "🔄 In Progress" : "⏳ Pending";
                $msg .= "- **{$details}**\n";
                $msg .= "  {$status_badge} &nbsp;|&nbsp; INC: `{$row['incident_id']}` &nbsp;|&nbsp; Opened: _{$created}_<br>";
                $msg .= "  <button class='action-trigger-btn' data-action='resolve outage {$row['serial_no']}'>✅ Mark Resolved</button>\n";
            }
        }
    } else {
        $msg .= "✅ _No active outages. All services are running normally._\n\n";
    }

    $msg .= "\n<a href='" . BASE_URL . "/modules/outages/view' class='btn btn-xs btn-outline-danger px-3 py-1 rounded-pill mt-2 d-inline-block font-monospace'><i class='fa-solid fa-arrow-right-long me-1'></i> Open Outages Module</a>\n";

    return ['message' => $msg, 'type' => 'outage', 'data' => $d];
}

function buildSSLResponse(): array {
    $d = fetchSSL();
    $total   = (int)($d['stats']['total'] ?? 0);
    $expiring= (int)($d['stats']['expiring_soon'] ?? 0);
    $expired = (int)($d['stats']['expired'] ?? 0);
    $pending = (int)($d['stats']['pending'] ?? 0);
    $renewed = (int)($d['stats']['renewed'] ?? 0);

    $msg = "## 🔒 SSL Certificate Status\n\n";
    $msg .= "| Metric | Count |\n|--------|-------|\n";
    $msg .= "| 🔴 Expired | **{$expired}** |\n";
    $msg .= "| ⚠️ Expiring Soon (≤30d) | **{$expiring}** |\n";
    $msg .= "| ⏳ Renewal Pending | **{$pending}** |\n";
    $msg .= "| ✅ Renewed | **{$renewed}** |\n";
    $msg .= "| 📋 Total Certs | **{$total}** |\n\n";

    if (!empty($d['critical'])) {
        $msg .= "### ⚠️ Certificates Needing Attention\n";
        foreach ($d['critical'] as $cert) {
            $days = (int)$cert['days_left'];
            $icon = $days < 0 ? "🔴" : ($days <= 7 ? "🔴" : ($days <= 30 ? "⚠️" : "🟡"));
            $label = $days < 0 ? "EXPIRED {$days} days ago" : "Expires in {$days} days";
            $expiry_date = $cert['expiration_date'] ? date('d M Y', strtotime($cert['expiration_date'])) : 'N/A';
            $msg .= "- {$icon} **{$cert['certificate_name']}** — {$label} ({$expiry_date}) — Renewal: *{$cert['renewal_status']}*\n";
        }
        $msg .= "\n";
    }

    if ($expired > 0) {
        $msg .= "**🚨 Action Required:** {$expired} certificate(s) have already expired. Initiate emergency renewal immediately!\n";
    } elseif ($expiring > 0) {
        $msg .= "**💡 Suggestion:** Schedule renewal for certificates expiring within 30 days to avoid service disruption.\n";
    } else {
        $msg .= "✅ _All certificates are valid. No immediate action needed._\n";
    }

    $msg .= "\n<a href='" . BASE_URL . "/modules/ssl/view' class='btn btn-xs btn-outline-warning px-3 py-1 rounded-pill mt-2 d-inline-block font-monospace'><i class='fa-solid fa-arrow-right-long me-1'></i> Open SSL Module</a>\n";

    return ['message' => $msg, 'type' => 'ssl', 'data' => $d];
}

function buildPendingMailResponse(): array {
    $d = fetchPendingMail();
    $total    = (int)($d['stats']['total'] ?? 0);
    $pending  = (int)($d['stats']['pending'] ?? 0);
    $answered = (int)($d['stats']['answered'] ?? 0);
    $follow_up= (int)($d['stats']['follow_up'] ?? 0);

    $bg_class = ($pending > 0)
        ? 'bg-warning bg-opacity-10 border border-warning border-opacity-25'
        : 'bg-success bg-opacity-10 border border-success border-opacity-25';
    
    $msg = "## 📧 Pending Mail Status\n\n";
    $msg .= "<div class='p-3 rounded-3 mb-3 {$bg_class}'>";
    $msg .= "<div class='fw-bold mb-1'>" . ($pending > 0 ? "⚠️ {$pending} MAIL(S) AWAITING RESPONSE" : "🟢 ALL MAILS ANSWERED") . "</div>";
    $msg .= "<div class='small text-muted'>{$answered} answered, {$follow_up} follow-up, {$total} total</div>";
    $msg .= "</div>";

    $msg .= "| Metric | Count |\n|--------|-------|\n";
    $msg .= "| ⏳ Pending | **{$pending}** |\n";
    $msg .= "| ✅ Answered | **{$answered}** |\n";
    $msg .= "| 🔄 Follow-up | **{$follow_up}** |\n";
    $msg .= "| 📋 Total | **{$total}** |\n\n";

    if (!empty($d['recent'])) {
        $msg .= "### 📬 Recent Entries\n";
        foreach ($d['recent'] as $mail) {
            $st = strtolower($mail['status'] ?? 'pending');
            $priority = strtolower($mail['priority'] ?? 'normal');
            $icon = ($st === 'answered') ? "✅" : ($st === 'follow_up' ? "🔄" : "⏳");
            $pri_icon = ($priority === 'high') ? " 🔴" : ($priority === 'medium' ? " 🟡" : "");
            $subject = $mail['subject_line'] ?? 'No Subject';
            $created = $mail['created_at'] ? date('d M', strtotime($mail['created_at'])) : '?';
            $msg .= "- {$icon}{$pri_icon} **" . htmlspecialchars(mb_substr($subject, 0, 55), ENT_QUOTES) . "** — _{$mail['status']}_ ({$created})";
            if ($st === 'pending' || $st === 'follow_up') {
                $msg .= "<br>  <button class='action-trigger-btn btn-answered' data-action='mark answered mail {$mail['serial_no']}'>✅ Mark Answered</button>";
            }
            $msg .= "\n";
        }
        $msg .= "\n";
    }

    if ($pending > 0) {
        $msg .= "**💡 Tip:** Click **Mark Answered** above, or type `answer mail #ID` to update status.\n";
    } else {
        $msg .= "✅ _All mails have been answered. Great job!_\n";
    }

    $msg .= "\n<a href='" . BASE_URL . "/modules/pm/view' class='btn btn-xs btn-outline-primary px-3 py-1 rounded-pill mt-2 d-inline-block font-monospace'><i class='fa-solid fa-arrow-right-long me-1'></i> Open Pending Mail Module</a>\n";

    return ['message' => $msg, 'type' => 'pending_mail', 'data' => $d];
}

function buildSecurityResponse(): array {
    $d = fetchSecurity();
    $total    = (int)($d['stats']['total'] ?? 0);
    $pending  = (int)($d['stats']['pending'] ?? 0);
    $answered = (int)($d['stats']['answered'] ?? 0);
    $follow_up= (int)($d['stats']['follow_up'] ?? 0);

    $bg_class = ($pending > 0)
        ? 'bg-danger bg-opacity-10 border border-danger border-opacity-25'
        : 'bg-success bg-opacity-10 border border-success border-opacity-25';

    $msg = "## 🛡️ Security Mail Status\n\n";
    $msg .= "<div class='p-3 rounded-3 mb-3 {$bg_class}'>";
    $msg .= "<div class='fw-bold mb-1'>" . ($pending > 0 ? "🔴 {$pending} SECURITY ALERT(S) UNRESOLVED" : "🟢 ALL SECURITY MAILS CLEARED") . "</div>";
    $msg .= "<div class='small text-muted'>{$answered} answered · {$follow_up} follow-up · {$total} total</div>";
    $msg .= "</div>";
    
    $msg .= "| Metric | Count |\n|--------|-------|\n";
    $msg .= "| 🔴 Pending | **{$pending}** |\n";
    $msg .= "| ✅ Answered | **{$answered}** |\n";
    $msg .= "| 🔄 Follow-up | **{$follow_up}** |\n";
    $msg .= "| 📋 Total | **{$total}** |\n\n";

    if (!empty($d['recent'])) {
        $msg .= "### 🚨 Recent Security Alerts\n";
        foreach ($d['recent'] as $mail) {
            $priority = $mail['priority'] ?? 'normal';
            $st = strtolower($mail['status'] ?? 'pending');
            $icon = ($st === 'answered') ? "✅" : (strtolower($priority) === 'high' ? "🔴" : "⚠️");
            $subject = $mail['subject_line'] ?? 'No Subject';
            $created = $mail['created_at'] ? date('d M', strtotime($mail['created_at'])) : '?';
            $msg .= "- {$icon} **" . htmlspecialchars(mb_substr($subject, 0, 55), ENT_QUOTES) . "** — Priority: `{$priority}` — _{$mail['status']}_ ({$created})";
            if ($st === 'pending' || $st === 'follow_up') {
                $msg .= "<br>  <button class='action-trigger-btn btn-answered' data-action='mark answered security mail {$mail['serial_no']}'>✅ Mark Answered</button>";
            }
            $msg .= "\n";
        }
        $msg .= "\n";
    }

    if ($pending > 0) {
        $msg .= "**🚨 Action Required:** {$pending} security alert(s) remain pending. Escalate if not resolved before shift change.\n";
    } else {
        $msg .= "✅ _No open security alerts. The system is secure._\n";
    }

    $msg .= "\n<a href='" . BASE_URL . "/modules/sc/view' class='btn btn-xs btn-outline-danger px-3 py-1 rounded-pill mt-2 d-inline-block font-monospace'><i class='fa-solid fa-arrow-right-long me-1'></i> Open Security Mail Module</a>\n";

    return ['message' => $msg, 'type' => 'security', 'data' => $d];
}

function buildCampaignResponse(): array {
    $d = fetchCampaigns();
    $active    = (int)($d['stats']['active'] ?? 0);
    $inactive  = (int)($d['stats']['inactive'] ?? 0);
    $completed = (int)($d['stats']['completed'] ?? 0);
    $total     = (int)($d['stats']['total'] ?? 0);

    $msg = "## 📣 Campaign Status\n\n";
    $msg .= "| Metric | Count |\n|--------|-------|\n";
    $msg .= "| 🟢 Active | **{$active}** |\n";
    $msg .= "| 🔴 Inactive | **{$inactive}** |\n";
    $msg .= "| ✅ Completed | **{$completed}** |\n";
    $msg .= "| 📋 Total | **{$total}** |\n\n";

    if (!empty($d['recent'])) {
        $msg .= "### 📋 Recent Campaigns\n";
        foreach ($d['recent'] as $c) {
            $status = $c['status'] ?? 'unknown';
            $icon = strtolower($status) === 'active' ? "🟢" : (strtolower($status) === 'completed' ? "✅" : "🔴");
            $start = $c['start_date'] ? date('d M', strtotime($c['start_date'])) : '?';
            $name  = $c['campaign_name'] ?? 'Unnamed';
            $desc  = mb_substr($c['description'] ?? '', 0, 50);
            $msg .= "- {$icon} **" . htmlspecialchars($name, ENT_QUOTES) . "** — Started: {$start} — *{$status}*" . ($desc ? " — _{$desc}_" : "") . "\n";
        }
    }

    $msg .= "\n💡 _Ensure active campaigns have up-to-date notes and team assignments for smooth handover._\n";
    $msg .= "\n<a href='" . BASE_URL . "/modules/campaigns/view' class='btn btn-xs btn-outline-success px-3 py-1 rounded-pill mt-2 d-inline-block font-monospace'><i class='fa-solid fa-arrow-right-long me-1'></i> Open Campaigns Module</a>\n";

    return ['message' => $msg, 'type' => 'campaign', 'data' => $d];
}

function buildBannerResponse(): array {
    $d = fetchBanners();
    $live      = (int)($d['stats']['live'] ?? 0);
    $scheduled = (int)($d['stats']['scheduled'] ?? 0);
    $draft     = (int)($d['stats']['draft'] ?? 0);
    $total     = (int)($d['stats']['total'] ?? 0);

    $msg = "## 🖼️ Promo Banner Status\n\n";
    $msg .= "| Metric | Count |\n|--------|-------|\n";
    $msg .= "| 🟢 Live | **{$live}** |\n";
    $msg .= "| 📅 Scheduled | **{$scheduled}** |\n";
    $msg .= "| 📝 Draft | **{$draft}** |\n";
    $msg .= "| 📋 Total | **{$total}** |\n\n";

    if (!empty($d['recent'])) {
        $msg .= "### 🎨 Recent Banners\n";
        foreach ($d['recent'] as $b) {
            $status = $b['status'] ?? 'unknown';
            $icon = strtolower($status) === 'live' ? "🟢" : (strtolower($status) === 'scheduled' ? "📅" : "📝");
            $start = $b['start_time'] ? date('d M', strtotime($b['start_time'])) : '?';
            $name  = $b['subject_line'] ?? 'Unnamed';
            $msg .= "- {$icon} **" . htmlspecialchars(mb_substr($name, 0, 60), ENT_QUOTES) . "** — From: {$start} — *{$status}*\n";
        }
    }

    $msg .= "\n<a href='" . BASE_URL . "/modules/banners/view' class='btn btn-xs btn-outline-secondary px-3 py-1 rounded-pill mt-2 d-inline-block font-monospace'><i class='fa-solid fa-arrow-right-long me-1'></i> Open Banners Module</a>\n";

    return ['message' => $msg, 'type' => 'banner', 'data' => $d];
}

function buildCRResponse(): array {
    $d = fetchCR();
    $downtime    = (int)($d['stats']['downtime'] ?? 0);
    $no_impact   = (int)($d['stats']['no_impact'] ?? 0);
    $fluctuation = (int)($d['stats']['fluctuation'] ?? 0);
    $total       = (int)($d['stats']['total'] ?? 0);

    $msg = "## 📋 Change Request (CR) Status\n\n";
    $msg .= "| Metric | Count |\n|--------|-------|\n";
    $msg .= "| 🔴 With Downtime | **{$downtime}** |\n";
    $msg .= "| 🟡 Fluctuation | **{$fluctuation}** |\n";
    $msg .= "| ✅ No Impact | **{$no_impact}** |\n";
    $msg .= "| 📋 Total CRs | **{$total}** |\n\n";

    if (!empty($d['recent'])) {
        $msg .= "### 📝 Recent Change Requests\n";
        foreach ($d['recent'] as $cr) {
            $dt = (string)($cr['downtime'] ?? '3');
            $icon = $dt === '0' ? "🔴" : ($dt === '2' ? "🟡" : ($dt === '1' ? "✅" : "⬜"));
            $label = $dt === '0' ? 'Downtime' : ($dt === '2' ? 'Fluctuation' : ($dt === '1' ? 'No Impact' : 'N/A'));
            $start = $cr['cr_start_time'] ? date('d M, H:i', strtotime($cr['cr_start_time'])) : '?';
            $subject = $cr['cr_subject'] ?? 'Unnamed CR';
            $area = $cr['impacted_area'] ?? '';
            $msg .= "- {$icon} **" . htmlspecialchars(mb_substr($subject, 0, 60), ENT_QUOTES) . "** — {$label}" . ($area ? " — Area: {$area}" : "") . " — {$start}\n";
        }
    }

    $msg .= "\n💡 _Ensure all in-progress CRs have clear owners and next steps documented for the incoming shift._\n";
    $msg .= "\n<a href='" . BASE_URL . "/modules/change_requests/view' class='btn btn-xs btn-outline-info px-3 py-1 rounded-pill mt-2 d-inline-block font-monospace'><i class='fa-solid fa-arrow-right-long me-1'></i> Open CR Module</a>\n";

    return ['message' => $msg, 'type' => 'cr', 'data' => $d];
}

function buildObservationResponse(): array {
    $d = fetchObservations();
    $total    = (int)($d['stats']['total'] ?? 0);
    $pending  = (int)($d['stats']['pending'] ?? 0);
    $complete = (int)($d['stats']['complete'] ?? 0);
    $progress = $total > 0 ? round(($complete / $total) * 100) : 0;

    $msg = "## 🔍 Observations Status\n\n";
    $msg .= "| Metric | Count |\n|--------|-------|\n";
    $msg .= "| ⏳ Pending L2 | **{$pending}** |\n";
    $msg .= "| ✅ Complete | **{$complete}** |\n";
    $msg .= "| 📋 Total | **{$total}** |\n";
    $msg .= "| 📊 Progress | **{$progress}%** |\n\n";

    if (!empty($d['recent'])) {
        $msg .= "### 📌 Recent Observations\n";
        foreach ($d['recent'] as $obs) {
            $is_complete = !empty($obs['l2_observation']);
            $icon = $is_complete ? "✅" : "⏳";
            $name = $obs['observation_names'] ?? 'Unnamed';
            $tech = $obs['technician_name'] ?? 'N/A';
            $date_str = $obs['start_date'] ? date('d M', strtotime($obs['start_date'])) : '?';
            $msg .= "- {$icon} **" . htmlspecialchars(mb_substr($name, 0, 40), ENT_QUOTES) . "** — Tech: {$tech} — {$date_str}\n";
        }
    }

    if ($pending > 0) {
        $msg .= "\n💡 _L2 team should complete analysis on {$pending} pending observation(s) before handover._\n";
    } else {
        $msg .= "\n✅ _All observations have been analyzed. Great job L2 team!_\n";
    }

    $msg .= "\n<a href='" . BASE_URL . "/modules/observations/view' class='btn btn-xs btn-outline-info px-3 py-1 rounded-pill mt-2 d-inline-block font-monospace'><i class='fa-solid fa-arrow-right-long me-1'></i> Open Observations Module</a>";
    $msg .= " &nbsp;<button class='action-trigger-btn' style='margin:0' data-action='create observation:'>✏️ Log New Observation</button>\n";

    return ['message' => $msg, 'type' => 'observation', 'data' => $d];
}

function buildEDResponse(): array {
    $d = fetchED();
    $enabled  = (int)($d['stats']['enabled'] ?? 0);
    $disabled = (int)($d['stats']['disabled'] ?? 0);
    $hidden   = (int)($d['stats']['hidden'] ?? 0);
    $total    = (int)($d['stats']['total'] ?? 0);

    $msg = "## 🔀 Enable/Disable Service Log\n\n";
    $msg .= "| Metric | Count |\n|--------|-------|\n";
    $msg .= "| 🟢 Enabled | **{$enabled}** |\n";
    $msg .= "| 🔴 Disabled | **{$disabled}** |\n";
    $msg .= "| 👁️ Hidden | **{$hidden}** |\n";
    $msg .= "| 📋 Total Logs | **{$total}** |\n\n";

    if (!empty($d['disabled'])) {
        $msg .= "### 🔴 Recently Disabled Services\n";
        foreach ($d['disabled'] as $svc) {
            $ref = $svc['reference'] ? htmlspecialchars(mb_substr($svc['reference'], 0, 40), ENT_QUOTES) : 'No reference';
            $date = $svc['action_date'] ? date('d M, H:i', strtotime($svc['action_date'])) : '?';
            $by   = $svc['action_taken_by'] ?? 'Unknown';
            $msg .= "- 🔴 **{$svc['service_name']}** — By: {$by} — Ref: _{$ref}_ — {$date}\n";
        }
        $msg .= "\n⚠️ _Ensure disabled services are intentional and documented in the handover notes._";
    } else {
        $msg .= "✅ _No services were recently disabled. All services appear operational._";
    }

    $msg .= "\n<a href='" . BASE_URL . "/modules/ed/view' class='btn btn-xs btn-outline-dark px-3 py-1 rounded-pill mt-2 d-inline-block font-monospace'><i class='fa-solid fa-arrow-right-long me-1'></i> Open Enable/Disable Module</a>\n";

    return ['message' => $msg, 'type' => 'ed', 'data' => $d];
}

// ============================================================
// DATABASE WRITE & ACTION FUNCTIONS
// ============================================================

function handleResolveOutage(string $user_message): array {
    $conn = getConnection();
    $id = null;
    $incident_id = null;

    if (preg_match('/resolve\s+(?:outage|incident)\s+#?([0-9]+)/i', $user_message, $matches)) {
        $id = (int)$matches[1];
    } elseif (preg_match('/resolve\s+(?:outage|incident)\s+(INC[0-9]+)/i', $user_message, $matches)) {
        $incident_id = trim($matches[1]);
    } else {
        $last_outages = $_SESSION['agent_last_results']['outage'] ?? [];
        foreach ($last_outages as $o) {
            $st = strtolower($o['status'] ?? '');
            if ($st === 'pending' || $st === 'in_progress') {
                $id = (int)$o['serial_no'];
                break;
            }
        }
    }

    if (!$id && !$incident_id) {
        return [
            'message' => "❌ **No active outages found in recent context.** Please specify the ID (e.g. `resolve outage 15` or `resolve INC00102`).",
            'type' => 'write_error'
        ];
    }

    if ($_SESSION['role'] === 'l2') {
        return [
            'message' => "❌ **Permission Denied:** L2 Analytical role does not have permission to modify outages.",
            'type' => 'write_error'
        ];
    }

    $edited_by = $_SESSION['username'] ?? 'System';
    if ($incident_id) {
        $sql = "UPDATE service_outage SET status='resolved', edited_by=?, edited_at=NOW() WHERE incident_id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $edited_by, $incident_id);
    } else {
        $sql = "UPDATE service_outage SET status='resolved', edited_by=?, edited_at=NOW() WHERE serial_no=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $edited_by, $id);
    }

    if (mysqli_stmt_execute($stmt)) {
        $identifier = $incident_id ?: "#{$id}";
        mysqli_stmt_close($stmt);
        return [
            'message' => "✅ **Service Outage {$identifier} has been marked as Resolved!**\n\n- Updated by: `{$edited_by}`\n- Status: `resolved`\n- Time: " . date('h:i A'),
            'type' => 'write_success'
        ];
    } else {
        mysqli_stmt_close($stmt);
        return [
            'message' => "⚠️ **Failed to update outage status.** Please try again or check the system logs.",
            'type' => 'write_error'
        ];
    }
}

function handleAnswerMail(string $user_message): array {
    $conn = getConnection();
    $id = null;
    $type = 'pending_mail';

    if (preg_match('/security/i', $user_message)) {
        $type = 'security_mail';
    }

    // Matches button-generated actions: "mark answered security mail 5" or "mark answered mail 12"
    if (preg_match('/mark\s+answered\s+(?:security\s+)?mail\s+#?([0-9]+)/i', $user_message, $matches)) {
        $id = (int)$matches[1];
    } elseif (preg_match('/(?:mark\s+)?(?:mail|email|incident|alert)\s+#?([0-9]+)\s+as\s+answered/i', $user_message, $matches)) {
        $id = (int)$matches[1];
    } elseif (preg_match('/(?:answer|close)\s+(?:mail|email|alert)\s+#?([0-9]+)/i', $user_message, $matches)) {
        $id = (int)$matches[1];
    } else {
        $last_mails = $_SESSION['agent_last_results']['pending_mail'] ?? [];
        if (empty($last_mails) && $type === 'security_mail') {
            $last_mails = $_SESSION['agent_last_results']['security'] ?? [];
        }
        foreach ($last_mails as $m) {
            $st = strtolower($m['status'] ?? '');
            if ($st === 'pending' || $st === 'follow_up') {
                $id = (int)$m['serial_no'];
                break;
            }
        }
        // Also auto-detect which table based on session context
        if ($id && empty($_SESSION['agent_last_results']['pending_mail']) && !empty($_SESSION['agent_last_results']['security'])) {
            $type = 'security_mail';
        }
    }

    if (!$id) {
        return [
            'message' => "❌ **No pending mails found in recent context.** Please specify the ID (e.g. `answer mail 12` or `answer security mail 5`).",
            'type' => 'write_error'
        ];
    }

    if ($_SESSION['role'] === 'l2') {
        return [
            'message' => "❌ **Permission Denied:** L2 Analytical role does not have permission to update mails.",
            'type' => 'write_error'
        ];
    }

    $edited_by = $_SESSION['username'] ?? 'System';
    $table = ($type === 'security_mail') ? 'security_mail' : 'pending_mail';
    $sql = "UPDATE `{$table}` SET status='answered', edited_by=?, edited_at=NOW() WHERE serial_no=?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $edited_by, $id);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return [
            'message' => "✅ **Mail #{$id} ({$table}) has been marked as Answered!**\n\n- Updated by: `{$edited_by}`\n- Status: `answered`",
            'type' => 'write_success'
        ];
    } else {
        mysqli_stmt_close($stmt);
        return [
            'message' => "⚠️ **Failed to update mail status.** Please try again or check the system logs.",
            'type' => 'write_error'
        ];
    }
}

function handleCreateObservation(string $user_message): array {
    $conn = getConnection();
    $tech = 'N/A';
    $team = 'L1';
    
    $content = '';
    if (preg_match('/(?:create|add|new|log)\s+observation:?\s*(.*)$/i', $user_message, $matches)) {
        $content = trim($matches[1]);
    }
    
    if (empty($content)) {
        return [
            'message' => "❌ **Observation details cannot be empty.** Please type: `create observation: <Your observation text here>`.",
            'type' => 'write_error'
        ];
    }

    if (preg_match('/for\s+(?:technician|tech)\s+([a-zA-Z\s0-9_]+)/i', $content, $matches)) {
        $tech = trim($matches[1]);
        $content = trim(preg_replace('/for\s+(?:technician|tech)\s+[a-zA-Z\s0-9_]+/i', '', $content));
    }
    
    if (preg_match('/for\s+team\s+([a-zA-Z0-9_\s]+)/i', $content, $matches)) {
        $team = trim($matches[1]);
        $content = trim(preg_replace('/for\s+team\s+[a-zA-Z0-9_\s]+/i', '', $content));
    }

    if (!in_array($_SESSION['role'], ['super_admin', 'l1'])) {
        return [
            'message' => "❌ **Permission Denied:** Your role (`{$_SESSION['role']}`) is not authorized to create L1 observations.",
            'type' => 'write_error'
        ];
    }

    $subject = mb_substr($content, 0, 100);
    $l1_obs = $content;
    $start_date = date('Y-m-d H:i:s');
    $created_by = $_SESSION['username'] ?? 'System';
    $l1_by = $created_by;

    $sql = "INSERT INTO observations (observation_names, team_name, technician_name, start_date, l1_observation, l1_observations_by, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssss", $subject, $team, $tech, $start_date, $l1_obs, $l1_by, $created_by);

    if (mysqli_stmt_execute($stmt)) {
        $last_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        return [
            'message' => "✅ **New Observation successfully created!**\n\n" .
                         "| Field | Value |\n" .
                         "|---|---|\n" .
                         "| **ID** | #{$last_id} |\n" .
                         "| **Title** | " . htmlspecialchars($subject, ENT_QUOTES) . " |\n" .
                         "| **Assigned Tech** | " . htmlspecialchars($tech, ENT_QUOTES) . " |\n" .
                         "| **Assigned Team** | " . htmlspecialchars($team, ENT_QUOTES) . " |\n" .
                         "| **Created By** | `{$created_by}` |\n\n" .
                         "[View Observations List](" . BASE_URL . "/modules/observations/view)",
            'type' => 'write_success'
        ];
    } else {
        mysqli_stmt_close($stmt);
        return [
            'message' => "⚠️ **Failed to create observation.** Please check database logs.",
            'type' => 'write_error'
        ];
    }
}

// ============================================================
// SHIFT COMPARISON & PEAK HOUR ANALYTICS
// ============================================================

function getShiftTimeRange(string $date, string $shift): array {
    if ($shift === 'Morning') {
        return ["{$date} 06:00:00", "{$date} 13:59:59"];
    } elseif ($shift === 'Evening') {
        return ["{$date} 14:00:00", "{$date} 21:59:59"];
    } else {
        $next_date = date('Y-m-d', strtotime("{$date} +1 day"));
        return ["{$date} 22:00:00", "{$next_date} 05:59:59"];
    }
}

function countRecordsInShift($conn, $table, $date, $shift, $date_col = 'created_at'): int {
    list($start, $end) = getShiftTimeRange($date, $shift);
    $q = safeQuery($conn, "SELECT COUNT(*) as cnt FROM `{$table}` WHERE `{$date_col}` BETWEEN '{$start}' AND '{$end}'");
    $r = $q ? mysqli_fetch_assoc($q) : null;
    return (int)($r['cnt'] ?? 0);
}

function buildComparisonResponse(): array {
    $conn = getConnection();
    $current_shift = getCurrentShift();
    $today = date('Y-m-d');
    
    $prev_shift = 'Night';
    $prev_date = date('Y-m-d', strtotime('-1 day'));
    
    if ($current_shift === 'Evening') {
        $prev_shift = 'Morning';
        $prev_date = $today;
    } elseif ($current_shift === 'Night') {
        $prev_shift = 'Evening';
        $prev_date = $today;
    }

    $outages_curr = countRecordsInShift($conn, 'service_outage', $today, $current_shift, 'created_at');
    $outages_prev = countRecordsInShift($conn, 'service_outage', $prev_date, $prev_shift, 'created_at');
    
    $obs_curr = countRecordsInShift($conn, 'observations', $today, $current_shift, 'start_date');
    $obs_prev = countRecordsInShift($conn, 'observations', $prev_date, $prev_shift, 'start_date');
    
    $pm_curr = countRecordsInShift($conn, 'pending_mail', $today, $current_shift, 'created_at');
    $pm_prev = countRecordsInShift($conn, 'pending_mail', $prev_date, $prev_shift, 'created_at');
    
    $sec_curr = countRecordsInShift($conn, 'security_mail', $today, $current_shift, 'created_at');
    $sec_prev = countRecordsInShift($conn, 'security_mail', $prev_date, $prev_shift, 'created_at');

    $cr_curr = countRecordsInShift($conn, 'cr_list', $today, $current_shift, 'cr_start_time');
    $cr_prev = countRecordsInShift($conn, 'cr_list', $prev_date, $prev_shift, 'cr_start_time');

    $msg = "## 📈 Shift Comparison: **{$current_shift}** vs **{$prev_shift}**\n";
    $msg .= "_Comparing today's shift with the immediately preceding shift ({$prev_date} {$prev_shift})._\n\n";
    $msg .= "| Metric | {$prev_shift} Shift | {$current_shift} Shift (Current) | Status / Delta |\n";
    $msg .= "|---|---|---|---|\n";
    
    $metrics = [
        ['Outages Logged', $outages_prev, $outages_curr, true],
        ['Observations logged', $obs_prev, $obs_curr, false],
        ['Pending Mails recd', $pm_prev, $pm_curr, true],
        ['Security Alerts recd', $sec_prev, $sec_curr, true],
        ['Change Requests', $cr_prev, $cr_curr, false],
    ];

    foreach ($metrics as $m) {
        list($label, $prev, $curr, $lower_is_better) = $m;
        $diff = $curr - $prev;
        $arrow = '';
        $color = '';
        if ($diff > 0) {
            $arrow = "📈 +{$diff}";
            $color = $lower_is_better ? '🔴' : '🟢';
        } elseif ($diff < 0) {
            $arrow = "📉 {$diff}";
            $color = $lower_is_better ? '🟢' : '🔴';
        } else {
            $arrow = "➖ 0";
            $color = '⚪';
        }
        $msg .= "| **{$label}** | {$prev} | {$curr} | {$color} {$arrow} |\n";
    }

    $msg .= "\n**Summary:**\n";
    $net_outages = $outages_curr - $outages_prev;
    if ($net_outages > 0) {
        $msg .= "- ⚠️ **Outages have increased** by **{$net_outages}** compared to the last shift. Monitor system health closely.\n";
    } elseif ($net_outages < 0) {
        $msg .= "- 🎉 **Outages have decreased** by **" . abs($net_outages) . "** compared to the last shift. Great stability!\n";
    } else {
        $msg .= "- 🟢 **Outage volume is identical** to the last shift.\n";
    }

    return ['message' => $msg, 'type' => 'comparison'];
}

function buildPeakHoursResponse(): array {
    $conn = getConnection();
    $sql = "SELECT HOUR(created_at) as hr, COUNT(*) as count 
            FROM service_outage 
            GROUP BY hr 
            ORDER BY count DESC, hr ASC 
            LIMIT 5";
    $res = safeQuery($conn, $sql);
    
    $msg = "## 🕒 Peak Hours Incident Analysis\n";
    $msg .= "_Analyzing historical data to identify timeframes with the highest frequency of service outages._\n\n";
    
    if ($res && mysqli_num_rows($res) > 0) {
        $msg .= "| Hour Range | Incidents Count | Peak Indicator |\n";
        $msg .= "|---|---|---|\n";
        
        $rank = 1;
        while ($row = mysqli_fetch_assoc($res)) {
            $hr = (int)$row['hr'];
            $count = (int)$row['count'];
            
            $start_ampm = date('h:i A', strtotime("{$hr}:00:00"));
            $end_ampm = date('h:i A', strtotime(($hr + 1) . ":00:00"));
            
            $indicator = str_repeat('🔥', max(1, 4 - $rank));
            $msg .= "| **{$start_ampm} - {$end_ampm}** | {$count} | {$indicator} |\n";
            $rank++;
        }
        $msg .= "\n💡 **Recommendation:** Schedule system updates, critical handovers, or high-risk changes outside these peak failure periods.";
    } else {
        $msg .= "📋 _No historical outage records found to analyze peak hours._";
    }
    
    return ['message' => $msg, 'type' => 'peak_hours'];
}

function buildProactiveCheckResponse(): array {
    $conn = getConnection();
    
    // Use LOWER() for case-insensitive status comparison
    $outages_q = safeQuery($conn, "SELECT COUNT(*) as cnt FROM service_outage WHERE LOWER(status)='pending' OR LOWER(status)='in_progress'");
    $outages = $outages_q ? (int)(mysqli_fetch_assoc($outages_q)['cnt'] ?? 0) : 0;
    
    $ssl_q = safeQuery($conn, "SELECT COUNT(*) as cnt FROM ssl_certificate WHERE expiration_date < CURDATE()");
    $ssl = $ssl_q ? (int)(mysqli_fetch_assoc($ssl_q)['cnt'] ?? 0) : 0;
    
    $sec_q = safeQuery($conn, "SELECT COUNT(*) as cnt FROM security_mail WHERE LOWER(priority)='high' AND LOWER(status)='pending'");
    $sec = $sec_q ? (int)(mysqli_fetch_assoc($sec_q)['cnt'] ?? 0) : 0;
    
    $total_alerts = $outages + $ssl + $sec;
    
    if ($total_alerts > 0) {
        $summary_items = [];
        if ($outages > 0) $summary_items[] = "🔴 **{$outages} active outage(s)**";
        if ($ssl > 0) $summary_items[] = "🔒 **{$ssl} expired SSL certificate(s)**";
        if ($sec > 0) $summary_items[] = "🛡️ **{$sec} high-priority security alert(s)**";
        
        $msg = "### 🚨 Critical Attention Needed!\n";
        $msg .= "There are **{$total_alerts} urgent issues** registered in this shift:\n";
        foreach ($summary_items as $item) {
            $msg .= "- {$item}\n";
        }
        $msg .= "\n_Please review these immediately to ensure operational continuity._";
        
        return [
            'has_alerts' => true,
            'alert_count' => $total_alerts,
            'message' => $msg,
            'type' => 'proactive_check'
        ];
    }
    
    return [
        'has_alerts' => false,
        'alert_count' => 0,
        'message' => "All good!",
        'type' => 'proactive_check'
    ];
}

function buildAnalyzeResponse(): array {
    $conn = getConnection();
    $shift = getCurrentShift();

    // Gather counts across all modules for analysis
    $obs_q = safeQuery($conn, "SELECT COUNT(*) as total, SUM(l2_observation IS NULL OR l2_observation='') as pending FROM observations WHERE start_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $obs_r = $obs_q ? mysqli_fetch_assoc($obs_q) : null;

    $outage_q = safeQuery($conn, "SELECT COUNT(*) as total, SUM(status='Pending') as active FROM service_outage WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $outage_r = $outage_q ? mysqli_fetch_assoc($outage_q) : null;

    $ssl_q = safeQuery($conn, "SELECT SUM(expiration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND expiration_date >= CURDATE()) as expiring FROM ssl_certificate");
    $ssl_r = $ssl_q ? mysqli_fetch_assoc($ssl_q) : null;

    $pm_q = safeQuery($conn, "SELECT COUNT(*) as total FROM pending_mail WHERE status='Pending'");
    $pm_r = $pm_q ? mysqli_fetch_assoc($pm_q) : null;

    $cr_q = safeQuery($conn, "SELECT COUNT(*) as total FROM cr_list WHERE downtime='0'");
    $cr_r = $cr_q ? mysqli_fetch_assoc($cr_q) : null;

    $obs_pending = (int)($obs_r['pending'] ?? 0);
    $obs_total   = (int)($obs_r['total'] ?? 0);
    $outage_total= (int)($outage_r['total'] ?? 0);
    $outage_active=(int)($outage_r['active'] ?? 0);
    $ssl_expiring= (int)($ssl_r['expiring'] ?? 0);
    $pm_pending  = (int)($pm_r['total'] ?? 0);
    $cr_open     = (int)($cr_r['total'] ?? 0);

    $health_score = 100;
    $recommendations = [];

    if ($outage_active > 0) {
        $health_score -= 30;
        $recommendations[] = "🔴 **CRITICAL:** {$outage_active} active outage(s). Resolve immediately and update resolution notes.";
    }
    if ($ssl_expiring > 0) {
        $health_score -= 15;
        $recommendations[] = "⚠️ **HIGH:** {$ssl_expiring} SSL certificate(s) expiring in ≤30 days. Initiate renewal process.";
    }
    if ($pm_pending > 0) {
        $health_score -= 10;
        $recommendations[] = "⚠️ **MEDIUM:** {$pm_pending} pending mail(s) awaiting reply. Assign to responsible team member.";
    }
    if ($obs_pending > 0 && $obs_total > 0) {
        $pct = round(($obs_pending / $obs_total) * 100);
        $health_score -= min(15, $pct / 5);
        $recommendations[] = "⚠️ **MEDIUM:** {$obs_pending} observations ({$pct}%) pending L2 analysis. L2 team action needed.";
    }
    if ($cr_open > 0) {
        $recommendations[] = "📋 **INFO:** {$cr_open} CR(s) recorded with downtime impact. Ensure these are documented for the incoming shift.";
    }

    $health_score = max(0, (int)$health_score);
    $health_label = $health_score >= 85 ? "🟢 Excellent" : ($health_score >= 60 ? "🟡 Fair" : "🔴 Critical");

    $msg = "## 🧠 Shift Intelligence Analysis\n\n";
    $msg .= "**Current Shift:** {$shift} &nbsp;|&nbsp; **Analysis Period:** Last 7 days\n\n";
    $msg .= "### 📈 System Health Score\n";
    $msg .= "**{$health_score}/100** — {$health_label}\n\n";
    $msg .= "### 🎯 Recommendations (Priority Order)\n";

    if (!empty($recommendations)) {
        foreach ($recommendations as $rec) {
            $msg .= "{$rec}\n\n";
        }
    } else {
        $msg .= "✅ _No critical issues. System is in excellent health!_\n\n";
    }

    $msg .= "### 📊 7-Day Activity Snapshot\n";
    $msg .= "- Outages recorded: **{$outage_total}** (Active: **{$outage_active}**)\n";
    $msg .= "- Observations logged: **{$obs_total}** (Pending: **{$obs_pending}**)\n";
    $msg .= "- SSL certs expiring soon: **{$ssl_expiring}**\n";
    $msg .= "- Pending mails: **{$pm_pending}**\n";
    $msg .= "- Open CRs: **{$cr_open}**\n";

    return ['message' => $msg, 'type' => 'analyze'];
}

function buildHelpResponse(): array {
    $msg = "## 🤖 What I Can Help You With\n\n";
    $msg .= "I'm your **Shift Intelligence Agent** — I have live access to all operational modules.\n\n";
    $msg .= "### 📋 Quick Info Commands\n";
    $msg .= "| Ask me... | What I'll show |\n|-------------|----------------|\n";
    $msg .= "| `summary` or `handover` | Full shift status across all modules |\n";
    $msg .= "| `outages` or `incidents` | Active & recent service outages |\n";
    $msg .= "| `ssl` or `certificates` | SSL cert status & expiry alerts |\n";
    $msg .= "| `pending mail` | Unanswered mail queue |\n";
    $msg .= "| `security` or `alerts` | Open security mail & threats |\n";
    $msg .= "| `campaigns` | Active & upcoming campaigns |\n";
    $msg .= "| `banners` | Promo banner status |\n";
    $msg .= "| `CR list` | Change request status |\n";
    $msg .= "| `observations` | L1/L2 observation progress |\n";
    $msg .= "| `enable disable` | Service enable/disable status |\n";
    $msg .= "| `analyze` | 📊 AI health score & recommendations |\n";
    $msg .= "| `compare shifts` | Compare current vs previous shift |\n";
    $msg .= "| `peak hours` | Show peak outage time analysis |\n\n";
    $msg .= "### ✍️ Action Commands (Write)\n";
    $msg .= "| Command | What it does |\n|---------|--------------|\n";
    $msg .= "| `resolve outage #ID` | Mark a service outage as resolved |\n";
    $msg .= "| `resolve outage INC00123` | Resolve by incident ID |\n";
    $msg .= "| `answer mail #ID` | Mark pending mail as answered |\n";
    $msg .= "| `answer security mail #ID` | Mark security alert as answered |\n";
    $msg .= "| `create observation: <text>` | Log a new L1 observation |\n\n";
    $msg .= "💡 _You can also just ask in plain English — I'll understand!_\n";
    $msg .= "⌨️ _Keyboard shortcut: **Ctrl+Shift+A** to toggle this chat window._";

    return ['message' => $msg, 'type' => 'help'];
}

function buildGreetingResponse(): array {
    $shift = getCurrentShift();
    $hour  = (int)date('H');
    $greeting = $hour < 12 ? "Good morning" : ($hour < 18 ? "Good afternoon" : "Good evening");
    $user = $_SESSION['username'] ?? 'there';

    $msg = "## 👋 {$greeting}, {$user}!\n\n";
    $msg .= "I'm your **Shift Intelligence Agent**. I'm connected to all operational modules and ready to help.\n\n";
    $msg .= "**Current Shift:** {$shift}\n\n";
    $msg .= "Here are some things you can ask me:\n";
    $msg .= "- 📊 _\"Give me a shift summary\"_\n";
    $msg .= "- 🔴 _\"Show active outages\"_\n";
    $msg .= "- 🔒 _\"Check SSL certificates\"_\n";
    $msg .= "- 🧠 _\"Analyze the system\"_\n\n";
    $msg .= "Type `help` to see all available commands.";

    return ['message' => $msg, 'type' => 'greeting'];
}

function buildUnknownResponse(string $msg_text): array {
    $msg = "I'm not quite sure what you're asking about. Here are some things I can help with:\n\n";
    $msg .= "- **`summary`** — Full shift handover summary\n";
    $msg .= "- **`outages`** — Service outage status\n";
    $msg .= "- **`ssl`** — SSL certificate expiry alerts\n";
    $msg .= "- **`analyze`** — System health analysis & recommendations\n\n";
    $msg .= "Type **`help`** to see all available commands.";

    return ['message' => $msg, 'type' => 'unknown'];
}

// ============================================================
// MAIN DISPATCH
// ============================================================
$intent = detectIntent($user_message);
$response = null;

try {
    if ($intent === 'unknown') {
        $multi_intents = detectMultipleIntents($user_message);
        if (count($multi_intents) > 1) {
            $merged_msg = "## 🤖 Merged Shift Information\n\n";
            $merged_data = [];
            foreach ($multi_intents as $mint) {
                $resp = match($mint) {
                    'summary'      => buildSummaryResponse(),
                    'outage'       => buildOutageResponse(),
                    'ssl'          => buildSSLResponse(),
                    'pending_mail' => buildPendingMailResponse(),
                    'security'     => buildSecurityResponse(),
                    'campaign'     => buildCampaignResponse(),
                    'banner'       => buildBannerResponse(),
                    'cr'           => buildCRResponse(),
                    'observation'  => buildObservationResponse(),
                    'ed'           => buildEDResponse(),
                    default        => null,
                };
                if ($resp) {
                    $msg_body = preg_replace('/^##\s+.*\n+/m', '', $resp['message']);
                    $merged_msg .= "### " . ucwords(str_replace('_', ' ', $mint)) . "\n" . $msg_body . "\n\n---\n\n";
                    $merged_data[$mint] = $resp['data'] ?? null;
                }
            }
            $response = [
                'message' => rtrim($merged_msg, "\n- "),
                'type' => 'multi_intent',
                'data' => $merged_data
            ];
        }
    }

    if (!$response) {
        $response = match($intent) {
            'summary'              => buildSummaryResponse(),
            'outage'               => buildOutageResponse(),
            'ssl'                  => buildSSLResponse(),
            'pending_mail'         => buildPendingMailResponse(),
            'security'             => buildSecurityResponse(),
            'campaign'             => buildCampaignResponse(),
            'banner'               => buildBannerResponse(),
            'cr'                   => buildCRResponse(),
            'observation'          => buildObservationResponse(),
            'ed'                   => buildEDResponse(),
            'analyze'              => buildAnalyzeResponse(),
            'help'                 => buildHelpResponse(),
            'greeting'             => buildGreetingResponse(),
            'comparison'           => buildComparisonResponse(),
            'peak_hours'           => buildPeakHoursResponse(),
            'proactive_check'      => buildProactiveCheckResponse(),
            'write_resolve_outage' => handleResolveOutage($user_message),
            'write_answer_mail'    => handleAnswerMail($user_message),
            'write_observation'    => handleCreateObservation($user_message),
            default                => buildUnknownResponse($user_message),
        };
    }
} catch (Throwable $e) {
    log_error("Agent API error", [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    $response = [
        'message' => "⚠️ PHP Error: " . $e->getMessage() . " in **" . basename($e->getFile()) . "** on line **" . $e->getLine() . "**",
        'type' => 'error'
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
