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

    $intents = [
        'summary'       => ['summary', 'handover', 'overview', 'brief', 'shift', 'today', 'report', 'status all', 'full status'],
        'outage'        => ['outage', 'down', 'incident', 'service issue', 'downtime', 'disruption'],
        'ssl'           => ['ssl', 'certificate', 'cert', 'expir', 'tls', 'https'],
        'pending_mail'  => ['pending mail', 'pending email', 'unanswered', 'follow-up', 'follow up', 'pending mail'],
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
    $stats = ['total' => 0, 'active' => 0, 'resolved' => 0];
    $result = safeQuery($conn, "SELECT COUNT(*) as total, SUM(status='Active') as active, SUM(status='Resolved') as resolved FROM service_outage");
    if ($result) {
        $stats = mysqli_fetch_assoc($result) ?: $stats;
    }

    $recent = [];
    $r = safeQuery($conn, "SELECT service_name, status, start_time, resolution_time FROM service_outage ORDER BY serial_no DESC LIMIT 5");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $recent[] = $row;
        }
    }
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
    $stats = ['total' => 0, 'pending' => 0, 'replied' => 0];
    $stats_r = safeQuery($conn, "SELECT COUNT(*) as total, SUM(status='Pending' OR status IS NULL OR status='') as pending, SUM(status='Replied') as replied FROM pending_mail");
    if ($stats_r) {
        $stats = mysqli_fetch_assoc($stats_r) ?: $stats;
    }

    $recent = [];
    $r = safeQuery($conn, "SELECT subject, sender, status, received_date FROM pending_mail ORDER BY serial_no DESC LIMIT 5");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $recent[] = $row;
        }
    }
    return ['stats' => $stats, 'recent' => $recent];
}

function fetchSecurity(): array {
    $conn = getConnection();
    $stats = ['total' => 0, 'open' => 0, 'resolved' => 0];
    $stats_r = safeQuery($conn, "SELECT COUNT(*) as total, SUM(status='Open' OR status IS NULL OR status='') as open, SUM(status='Resolved') as resolved FROM security_mail");
    if ($stats_r) {
        $stats = mysqli_fetch_assoc($stats_r) ?: $stats;
    }

    $recent = [];
    $r = safeQuery($conn, "SELECT subject, sender, priority, status FROM security_mail ORDER BY serial_no DESC LIMIT 5");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $recent[] = $row;
        }
    }
    return ['stats' => $stats, 'recent' => $recent];
}

function fetchCampaigns(): array {
    $conn = getConnection();
    $stats = ['total' => 0, 'active' => 0, 'upcoming' => 0, 'ended' => 0];
    $stats_r = safeQuery($conn, "SELECT COUNT(*) as total, SUM(status='Active') as active, SUM(status='Upcoming') as upcoming, SUM(status='Ended') as ended FROM campaign");
    if ($stats_r) {
        $stats = mysqli_fetch_assoc($stats_r) ?: $stats;
    }

    $recent = [];
    $r = safeQuery($conn, "SELECT campaign_name, start_date, end_date, status FROM campaign ORDER BY serial_no DESC LIMIT 5");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $recent[] = $row;
        }
    }
    return ['stats' => $stats, 'recent' => $recent];
}

function fetchBanners(): array {
    $conn = getConnection();
    $stats = ['total' => 0, 'live' => 0, 'scheduled' => 0, 'expired' => 0];
    $stats_r = safeQuery($conn, "SELECT COUNT(*) as total, SUM(status='Live') as live, SUM(status='Scheduled') as scheduled, SUM(status='Expired') as expired FROM promo_banner");
    if ($stats_r) {
        $stats = mysqli_fetch_assoc($stats_r) ?: $stats;
    }

    $recent = [];
    $r = safeQuery($conn, "SELECT banner_name, status, start_date, end_date FROM promo_banner ORDER BY serial_no DESC LIMIT 5");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $recent[] = $row;
        }
    }
    return ['stats' => $stats, 'recent' => $recent];
}

function fetchCR(): array {
    $conn = getConnection();
    $stats = ['total' => 0, 'open' => 0, 'in_progress' => 0, 'closed' => 0];
    $stats_r = safeQuery($conn, "SELECT COUNT(*) as total, SUM(status='Open') as open, SUM(status='In Progress') as in_progress, SUM(status='Closed') as closed FROM cr_list");
    if ($stats_r) {
        $stats = mysqli_fetch_assoc($stats_r) ?: $stats;
    }

    $recent = [];
    $r = safeQuery($conn, "SELECT cr_title, cr_type, status, planned_date FROM cr_list ORDER BY serial_no DESC LIMIT 5");
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
    $r = safeQuery($conn, "SELECT observation_names, technician_name, team_name, l2_observation, start_date FROM observations ORDER BY serial_no DESC LIMIT 5");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $recent[] = $row;
        }
    }
    return ['stats' => $stats, 'recent' => $recent];
}

function fetchED(): array {
    $conn = getConnection();
    $stats = ['total' => 0, 'enabled' => 0, 'disabled' => 0];
    $stats_r = safeQuery($conn, "SELECT COUNT(*) as total, SUM(service_status='Enabled') as enabled, SUM(service_status='Disabled') as disabled FROM enable_disable");
    if ($stats_r) {
        $stats = mysqli_fetch_assoc($stats_r) ?: $stats;
    }

    $disabled = [];
    $r = safeQuery($conn, "SELECT service_name, service_status, reason, updated_at FROM enable_disable WHERE service_status='Disabled' ORDER BY serial_no DESC LIMIT 5");
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
    $outage_active = (int)($data['outage']['stats']['active'] ?? 0);
    if ($outage_active > 0)
        $alerts[] = "🔴 **{$outage_active} active service outage(s)** need immediate attention.";
    else
        $ok[] = "✅ Service Outages: All clear";

    $ssl_expiring = (int)($data['ssl']['stats']['expiring_soon'] ?? 0);
    $ssl_expired  = (int)($data['ssl']['stats']['expired'] ?? 0);
    if ($ssl_expired > 0)
        $alerts[] = "🔴 **{$ssl_expired} SSL certificate(s) have already expired!**";
    if ($ssl_expiring > 0)
        $warnings[] = "⚠️ **{$ssl_expiring} SSL certificate(s) expiring within 30 days.**";
    if ($ssl_expired === 0 && $ssl_expiring === 0)
        $ok[] = "✅ SSL Certificates: All valid";

    $security_open = (int)($data['security']['stats']['open'] ?? 0);
    if ($security_open > 0)
        $warnings[] = "⚠️ **{$security_open} open security mail(s)** require review.";
    else
        $ok[] = "✅ Security Alerts: None open";

    $pm_pending = (int)($data['pending_mail']['stats']['pending'] ?? 0);
    if ($pm_pending > 0)
        $warnings[] = "⚠️ **{$pm_pending} pending mail(s)** awaiting response.";
    else
        $ok[] = "✅ Pending Mails: All replied";

    $cr_open = (int)($data['cr']['stats']['open'] ?? 0);
    if ($cr_open > 0)
        $ok[] = "📋 CR List: **{$cr_open} open** change request(s)";

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
    $active  = (int)($d['stats']['active'] ?? 0);
    $resolved= (int)($d['stats']['resolved'] ?? 0);
    $total   = (int)($d['stats']['total'] ?? 0);

    $msg = "## 🔴 Service Outage Status\n\n";
    $msg .= "| Metric | Count |\n|--------|-------|\n";
    $msg .= "| 🔴 Active | **{$active}** |\n";
    $msg .= "| ✅ Resolved | **{$resolved}** |\n";
    $msg .= "| 📋 Total | **{$total}** |\n\n";

    if ($active > 0) {
        $msg .= "### 🚨 Active Outages — Immediate Action Required!\n";
        $msg .= "_Check the Service Outage module to update resolution time and status._\n\n";
        foreach ($d['recent'] as $row) {
            if (($row['status'] ?? '') === 'Active') {
                $start = $row['start_time'] ? date('d M, H:i', strtotime($row['start_time'])) : 'Unknown';
                $msg .= "- **{$row['service_name']}** — Started: {$start}\n";
            }
        }
    } else {
        $msg .= "✅ _No active outages. All services are running normally._\n\n";
    }

    $msg .= "\n**Suggestion:** Ensure all resolved outages have documented resolution notes before shift handover.";

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

    return ['message' => $msg, 'type' => 'ssl', 'data' => $d];
}

function buildPendingMailResponse(): array {
    $d = fetchPendingMail();
    $total   = (int)($d['stats']['total'] ?? 0);
    $pending = (int)($d['stats']['pending'] ?? 0);
    $replied = (int)($d['stats']['replied'] ?? 0);

    $msg = "## 📧 Pending Mail Status\n\n";
    $msg .= "| Metric | Count |\n|--------|-------|\n";
    $msg .= "| ⏳ Pending Reply | **{$pending}** |\n";
    $msg .= "| ✅ Replied | **{$replied}** |\n";
    $msg .= "| 📋 Total | **{$total}** |\n\n";

    if (!empty($d['recent'])) {
        $msg .= "### 📬 Recent Entries\n";
        foreach ($d['recent'] as $mail) {
            $status = $mail['status'] ?? 'Pending';
            $icon = ($status === 'Replied') ? "✅" : "⏳";
            $subject = $mail['subject'] ?? 'No Subject';
            $sender = $mail['sender'] ?? 'Unknown';
            $msg .= "- {$icon} **" . htmlspecialchars($subject, ENT_QUOTES) . "** — From: {$sender} — *{$status}*\n";
        }
        $msg .= "\n";
    }

    if ($pending > 0) {
        $msg .= "**💡 Suggestion:** Ensure all pending mails are addressed or handed over with notes before shift end.";
    } else {
        $msg .= "✅ _All mails have been replied to. Great job!_";
    }

    return ['message' => $msg, 'type' => 'pending_mail', 'data' => $d];
}

function buildSecurityResponse(): array {
    $d = fetchSecurity();
    $total    = (int)($d['stats']['total'] ?? 0);
    $open     = (int)($d['stats']['open'] ?? 0);
    $resolved = (int)($d['stats']['resolved'] ?? 0);

    $msg = "## 🛡️ Security Mail Status\n\n";
    $msg .= "| Metric | Count |\n|--------|-------|\n";
    $msg .= "| 🔴 Open / Unresolved | **{$open}** |\n";
    $msg .= "| ✅ Resolved | **{$resolved}** |\n";
    $msg .= "| 📋 Total | **{$total}** |\n\n";

    if (!empty($d['recent'])) {
        $msg .= "### 🚨 Recent Security Alerts\n";
        foreach ($d['recent'] as $mail) {
            $priority = $mail['priority'] ?? 'Normal';
            $status = $mail['status'] ?? 'Open';
            $icon = (strtolower($status) === 'resolved') ? "✅" : (strtolower($priority) === 'high' ? "🔴" : "⚠️");
            $subject = $mail['subject'] ?? 'No Subject';
            $msg .= "- {$icon} **" . htmlspecialchars($subject, ENT_QUOTES) . "** — Priority: {$priority} — Status: *{$status}*\n";
        }
        $msg .= "\n";
    }

    if ($open > 0) {
        $msg .= "**🚨 Action Required:** {$open} security alert(s) remain unresolved. Escalate if not resolved before shift change.";
    } else {
        $msg .= "✅ _No open security alerts. The system is secure._";
    }

    return ['message' => $msg, 'type' => 'security', 'data' => $d];
}

function buildCampaignResponse(): array {
    $d = fetchCampaigns();
    $active   = (int)($d['stats']['active'] ?? 0);
    $upcoming = (int)($d['stats']['upcoming'] ?? 0);
    $ended    = (int)($d['stats']['ended'] ?? 0);
    $total    = (int)($d['stats']['total'] ?? 0);

    $msg = "## 📣 Campaign Status\n\n";
    $msg .= "| Metric | Count |\n|--------|-------|\n";
    $msg .= "| 🟢 Active | **{$active}** |\n";
    $msg .= "| 📅 Upcoming | **{$upcoming}** |\n";
    $msg .= "| 🔚 Ended | **{$ended}** |\n";
    $msg .= "| 📋 Total | **{$total}** |\n\n";

    if (!empty($d['recent'])) {
        $msg .= "### 📋 Recent Campaigns\n";
        foreach ($d['recent'] as $c) {
            $status = $c['status'] ?? 'Unknown';
            $icon = $status === 'Active' ? "🟢" : ($status === 'Upcoming' ? "📅" : "⬜");
            $start = $c['start_date'] ? date('d M', strtotime($c['start_date'])) : '?';
            $end   = $c['end_date']   ? date('d M', strtotime($c['end_date']))   : '?';
            $name  = $c['campaign_name'] ?? 'Unnamed';
            $msg .= "- {$icon} **" . htmlspecialchars($name, ENT_QUOTES) . "** — {$start} to {$end} — *{$status}*\n";
        }
    }

    $msg .= "\n💡 _Ensure active campaigns have up-to-date notes and team assignments for smooth handover._";

    return ['message' => $msg, 'type' => 'campaign', 'data' => $d];
}

function buildBannerResponse(): array {
    $d = fetchBanners();
    $live      = (int)($d['stats']['live'] ?? 0);
    $scheduled = (int)($d['stats']['scheduled'] ?? 0);
    $expired   = (int)($d['stats']['expired'] ?? 0);
    $total     = (int)($d['stats']['total'] ?? 0);

    $msg = "## 🖼️ Promo Banner Status\n\n";
    $msg .= "| Metric | Count |\n|--------|-------|\n";
    $msg .= "| 🟢 Live | **{$live}** |\n";
    $msg .= "| 📅 Scheduled | **{$scheduled}** |\n";
    $msg .= "| ⬜ Expired | **{$expired}** |\n";
    $msg .= "| 📋 Total | **{$total}** |\n\n";

    if (!empty($d['recent'])) {
        $msg .= "### 🎨 Recent Banners\n";
        foreach ($d['recent'] as $b) {
            $status = $b['status'] ?? 'Unknown';
            $icon = $status === 'Live' ? "🟢" : ($status === 'Scheduled' ? "📅" : "⬜");
            $start = $b['start_date'] ? date('d M', strtotime($b['start_date'])) : '?';
            $end   = $b['end_date']   ? date('d M', strtotime($b['end_date']))   : '?';
            $name  = $b['banner_name'] ?? 'Unnamed';
            $msg .= "- {$icon} **" . htmlspecialchars($name, ENT_QUOTES) . "** — {$start} to {$end} — *{$status}*\n";
        }
    }

    return ['message' => $msg, 'type' => 'banner', 'data' => $d];
}

function buildCRResponse(): array {
    $d = fetchCR();
    $open        = (int)($d['stats']['open'] ?? 0);
    $in_progress = (int)($d['stats']['in_progress'] ?? 0);
    $closed      = (int)($d['stats']['closed'] ?? 0);
    $total       = (int)($d['stats']['total'] ?? 0);

    $msg = "## 📋 Change Request (CR) Status\n\n";
    $msg .= "| Metric | Count |\n|--------|-------|\n";
    $msg .= "| 🔵 Open | **{$open}** |\n";
    $msg .= "| 🔄 In Progress | **{$in_progress}** |\n";
    $msg .= "| ✅ Closed | **{$closed}** |\n";
    $msg .= "| 📋 Total | **{$total}** |\n\n";

    if (!empty($d['recent'])) {
        $msg .= "### 📝 Recent CRs\n";
        foreach ($d['recent'] as $cr) {
            $status = $cr['status'] ?? 'Unknown';
            $icon = $status === 'Open' ? "🔵" : ($status === 'In Progress' ? "🔄" : "✅");
            $planned = $cr['planned_date'] ? date('d M', strtotime($cr['planned_date'])) : '?';
            $title = $cr['cr_title'] ?? 'Unnamed CR';
            $type  = $cr['cr_type'] ?? '';
            $msg .= "- {$icon} **" . htmlspecialchars($title, ENT_QUOTES) . "** ({$type}) — Planned: {$planned} — *{$status}*\n";
        }
    }

    $msg .= "\n💡 _Ensure all in-progress CRs have clear owners and next steps documented for the incoming shift._";

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
        $msg .= "\n💡 _L2 team should complete analysis on {$pending} pending observation(s) before handover._";
    } else {
        $msg .= "\n✅ _All observations have been analyzed. Great job L2 team!_";
    }

    return ['message' => $msg, 'type' => 'observation', 'data' => $d];
}

function buildEDResponse(): array {
    $d = fetchED();
    $enabled  = (int)($d['stats']['enabled'] ?? 0);
    $disabled = (int)($d['stats']['disabled'] ?? 0);
    $total    = (int)($d['stats']['total'] ?? 0);

    $msg = "## 🔀 Enable/Disable Service Status\n\n";
    $msg .= "| Metric | Count |\n|--------|-------|\n";
    $msg .= "| 🟢 Enabled | **{$enabled}** |\n";
    $msg .= "| 🔴 Disabled | **{$disabled}** |\n";
    $msg .= "| 📋 Total Services | **{$total}** |\n\n";

    if (!empty($d['disabled'])) {
        $msg .= "### 🔴 Currently Disabled Services\n";
        foreach ($d['disabled'] as $svc) {
            $reason = $svc['reason'] ? htmlspecialchars(mb_substr($svc['reason'], 0, 60), ENT_QUOTES) : 'No reason provided';
            $updated = $svc['updated_at'] ? date('d M, H:i', strtotime($svc['updated_at'])) : '?';
            $msg .= "- 🔴 **{$svc['service_name']}** — Reason: _{$reason}_ — Updated: {$updated}\n";
        }
        $msg .= "\n⚠️ _Ensure disabled services are intentional and documented in the handover notes._";
    } else {
        $msg .= "✅ _All services are currently enabled. No action needed._";
    }

    return ['message' => $msg, 'type' => 'ed', 'data' => $d];
}

function buildAnalyzeResponse(): array {
    $conn = getConnection();
    $shift = getCurrentShift();

    // Gather counts across all modules for analysis
    $obs_q = safeQuery($conn, "SELECT COUNT(*) as total, SUM(l2_observation IS NULL OR l2_observation='') as pending FROM observations WHERE start_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $obs_r = $obs_q ? mysqli_fetch_assoc($obs_q) : null;

    $outage_q = safeQuery($conn, "SELECT COUNT(*) as total, SUM(status='Active') as active FROM service_outage WHERE start_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $outage_r = $outage_q ? mysqli_fetch_assoc($outage_q) : null;

    $ssl_q = safeQuery($conn, "SELECT SUM(expiration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND expiration_date >= CURDATE()) as expiring FROM ssl_certificate");
    $ssl_r = $ssl_q ? mysqli_fetch_assoc($ssl_q) : null;

    $pm_q = safeQuery($conn, "SELECT COUNT(*) as total FROM pending_mail WHERE (status='Pending' OR status IS NULL OR status='')");
    $pm_r = $pm_q ? mysqli_fetch_assoc($pm_q) : null;

    $cr_q = safeQuery($conn, "SELECT COUNT(*) as total FROM cr_list WHERE status='Open' OR status='In Progress'");
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
        $recommendations[] = "📋 **INFO:** {$cr_open} open/in-progress CR(s). Ensure owners are aware and updates are logged.";
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
    $msg .= "### 📋 Quick Commands\n";
    $msg .= "| What to ask | What I'll show |\n|-------------|----------------|\n";
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
    $msg .= "| `analyze` | 📊 AI-powered health score & recommendations |\n\n";
    $msg .= "_You can also just ask in plain English — I'll understand!_";

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

try {
    $response = match($intent) {
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
        'analyze'      => buildAnalyzeResponse(),
        'help'         => buildHelpResponse(),
        'greeting'     => buildGreetingResponse(),
        default        => buildUnknownResponse($user_message),
    };
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
