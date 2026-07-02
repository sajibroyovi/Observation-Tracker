
<div class="modals-container">
    <!-- Service Enable/Disable Modal -->
    <div class="modal fade" id="staticBackdrop_ed" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-dark">
                <form action="<?php echo BASE_URL; ?>/modules/ed/create" method="POST">
                    <?php echo getCsrfField(); ?>
                    <div class="modal-header border-0 bg-primary">
                        <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-toggle-on me-2"></i> Service Enable/Disable</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Service Name</label>
                            <textarea class="form-control" name="service_name" rows="1" placeholder="e.g. Mobile Banking App" required></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Action Date & Time</label>
                            <input type="datetime-local" class="form-control" name="action_date" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Action Taken</label>
                            <select class="form-select" name="action_taken" required>
                                <option value="1">Enable</option>
                                <option value="0">Disable</option>
                                <option value="2">Hide</option>
                                <option value="3">Unhide</option>
                            </select>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Action By</label>
                                <input class="form-control" type="text" name="action_taken_by" placeholder="Name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Reference</label>
                                <input class="form-control" type="text" name="reference" placeholder="Ticket/Ref #" required>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Handed over to</label>
                                <select class="form-select" name="handed_over_to" required>
                                    <option value="Morning">Morning</option>
                                    <option value="Evening">Evening</option>
                                    <option value="Night">Night</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Handover Date</label>
                                <input class="form-control" type="date" name="handover_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm">Save Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Pending Mail Modal -->
    <div class="modal fade" id="staticBackdrop_pdmail" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-dark">
                <form action="<?php echo BASE_URL; ?>/modules/pm/create" method="POST">
                    <?php echo getCsrfField(); ?>
                    <div class="modal-header border-0 bg-accent-pink">
                        <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-envelope me-2"></i> Pending Mail Entry</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Subject Line</label>
                            <input type="text" class="form-control" name="subject_lines" placeholder="e.g. Follow-up on System Update" required>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Priority</label>
                                <select class="form-select" name="priority" required>
                                    <option value="high">High</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="low">Low</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="pending">Pending</option>
                                    <option value="follow_up">Follow Up</option>
                                    <option value="answered">Answered</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Handed over to</label>
                                <select class="form-select" name="handed_over_to" required>
                                    <option value="Morning">Morning</option>
                                    <option value="Evening">Evening</option>
                                    <option value="Night">Night</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Handover Date</label>
                                <input class="form-control" type="date" name="handover_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm" style="background: var(--accent-pink); border: none;">Save Communication</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Security Mail Modal -->
    <div class="modal fade" id="staticBackdrop_scmail" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-dark">
                <form action="<?php echo BASE_URL; ?>/modules/sc/create" method="POST">
                    <?php echo getCsrfField(); ?>
                    <div class="modal-header border-0 bg-warning">
                        <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-shield-halved me-2"></i> Security Mail Entry</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Subject Line</label>
                            <textarea class="form-control" name="subject_line" rows="2" placeholder="Describe the security alert..." required></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Priority</label>
                                <select class="form-select" name="priority" required>
                                    <option value="high">High</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="low">Low</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="pending">Pending</option>
                                    <option value="follow_up">Follow Up</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Handed over to</label>
                                <select class="form-select" name="handed_over_to" required>
                                    <option value="Morning">Morning</option>
                                    <option value="Evening">Evening</option>
                                    <option value="Night">Night</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Handover Date</label>
                                <input class="form-control" type="date" name="handover_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="submit" class="btn btn-warning w-100 py-3 rounded-pill fw-bold text-white shadow-sm" style="background: #ff9f1c; border: none;">Add Security Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- CR List Modal -->
    <div class="modal fade" id="staticBackdrop_crlist" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content text-dark">
                <form action="<?php echo BASE_URL; ?>/modules/change_requests/create" method="POST">
                    <?php echo getCsrfField(); ?>
                    <div class="modal-header border-0 bg-info">
                        <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-file-invoice me-2"></i> Change Request (CR) Entry</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-4">
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted text-uppercase">CR Subject / ID / Details</label>
                                <textarea class="form-control" name="cr_subject" rows="1" placeholder="e.g. CR-2023-001 - Database Migration" required></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted text-uppercase">Impacted Area</label>
                                <textarea class="form-control" name="impacted_area" rows="2" placeholder="Which services will be affected?" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Start Time</label>
                                <input type="datetime-local" class="form-control" name="cr_start_time" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">End Time</label>
                                <input type="datetime-local" class="form-control" name="cr_end_time" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Downtime Required</label>
                                <select class="form-select" name="downtime" required>
                                    <option value="1">No</option>
                                    <option value="0">Yes</option>
                                    <option value="2">Fluctuation</option>
                                    <option value="3">N/A</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Meeting Attended By</label>
                                <input class="form-control" type="text" name="cr_meeting_attended" placeholder="Attendee Name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Handed over to</label>
                                <select class="form-select" name="handed_over_to" required>
                                    <option value="Morning">Morning</option>
                                    <option value="Evening">Evening</option>
                                    <option value="Night">Night</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Handover Date</label>
                                <input class="form-control" type="date" name="handover_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="submit" class="btn btn-info w-100 py-3 rounded-pill fw-bold text-white shadow-sm" style="background: #4cc9f0; border: none;">Register Change Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- L1 Instructions Modal -->
    <div class="modal fade" id="staticBackdrop_l1_instructions" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-dark">
                <form action="<?php echo BASE_URL; ?>/modules/l1_instructions/create" method="POST">
                    <?php echo getCsrfField(); ?>
                    <div class="modal-header border-0 bg-danger" style="background-color: #d90429 !important;">
                        <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-bullhorn me-2"></i> L1 Instruction Entry</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Instruction Text</label>
                            <textarea class="form-control" name="instruction_text" rows="3" placeholder="Enter the instruction to display on the global marquee..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="submit" class="btn w-100 py-3 rounded-pill fw-bold text-white shadow-sm" style="background: #d90429; border: none;">Publish Instruction</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Promo Banner Modal -->
    <div class="modal fade" id="staticBackdrop_herobanner" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-dark">
                <form action="<?php echo BASE_URL; ?>/modules/banners/create" method="POST">
                    <?php echo getCsrfField(); ?>
                    <div class="modal-header border-0 bg-primary" style="background-color: #7209b7 !important;">
                        <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-image me-2"></i> Promo Banner Entry</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Subject / Campaign Title</label>
                            <textarea class="form-control" name="subject_line" rows="2" placeholder="e.g. Eid Mega Sale Banner" required></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Start Date</label>
                                <input type="datetime-local" class="form-control" name="start_date" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Banner Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="live">Live</option>
                                    <option value="scheduled" selected>Scheduled</option>
                                    <option value="draft">Draft</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Handed over to</label>
                                <select class="form-select" name="handed_over_to" required>
                                    <option value="Morning">Morning</option>
                                    <option value="Evening">Evening</option>
                                    <option value="Night">Night</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Handover Date</label>
                                <input class="form-control" type="date" name="handover_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="submit" class="btn w-100 py-3 rounded-pill fw-bold text-white shadow-sm" style="background: #7209b7; border: none;">Launch Banner Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Service Outage Modal -->
    <div class="modal fade" id="staticBackdrop_soutage" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content text-dark">
                <form action="<?php echo BASE_URL; ?>/modules/outages/create" method="POST">
                    <?php echo getCsrfField(); ?>
                    <div class="modal-header border-0 bg-danger">
                        <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-triangle-exclamation me-2"></i> Service Outage Entry</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Outage Details</label>
                            <textarea class="form-control" name="details" rows="3" placeholder="Describe the incident in detail..." required></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Incident ID</label>
                                <input class="form-control" type="text" name="incident_id" placeholder="INC0000123" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Problem Ticket</label>
                                <input class="form-control" type="text" name="problem_ticket" placeholder="PRB0000456">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="Ongoing" selected>Ongoing</option>
                                    <option value="Resolved">Resolved</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Assigned Technician</label>
                                <input class="form-control" type="text" name="technician" placeholder="Name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Handed over to</label>
                                <select class="form-select" name="handed_over_to" required>
                                    <option value="Morning">Morning</option>
                                    <option value="Evening">Evening</option>
                                    <option value="Night">Night</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Handover Date</label>
                                <input class="form-control" type="date" name="handover_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="submit" class="btn btn-danger w-100 py-3 rounded-pill fw-bold shadow-sm">Report Incident</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SSL Certificate Modal -->
    <div class="modal fade" id="staticBackdrop_SSLcertificate" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-dark">
                <form action="<?php echo BASE_URL; ?>/modules/ssl/create" method="POST">
                    <?php echo getCsrfField(); ?>
                    <div class="modal-header border-0" style="background: #560bad;">
                        <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-lock me-2"></i> SSL Certificate Entry</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Certificate Name</label>
                            <input type="text" class="form-control" name="certificate_name" placeholder="e.g. *.example.com" required>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Expiration Date</label>
                                <input type="date" class="form-control" name="expiration_date" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Renewal Status</label>
                                <select class="form-select" name="renewal_status" required>
                                    <option value="pending" selected>Pending</option>
                                    <option value="renewed">Renewed</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Known Issues</label>
                            <textarea class="form-control" name="issues" rows="2" placeholder="Any issues during renewal?"></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Handed over to</label>
                                <select class="form-select" name="handed_over_to" required>
                                    <option value="Morning">Morning</option>
                                    <option value="Evening">Evening</option>
                                    <option value="Night">Night</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Handover Date</label>
                                <input class="form-control" type="date" name="handover_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="submit" class="btn w-100 py-3 rounded-pill fw-bold text-white shadow-sm" style="background: #560bad; border: none;">Track Certificate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Campaign Entry Modal -->
    <div class="modal fade" id="staticBackdrop_campaign" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-dark">
                <form action="<?php echo BASE_URL; ?>/modules/campaigns/create" method="POST">
                    <?php echo getCsrfField(); ?>
                    <div class="modal-header border-0 px-4" style="background: #3a0ca3;">
                        <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-bullhorn me-2"></i> Campaign Entry</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-4">
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Campaign Name</label>
                            <textarea class="form-control" name="campaign_names" placeholder="e.g. Winter Sale 2023" required></textarea>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Start Date</label>
                                <input type="date" class="form-control" name="start_date" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Campaign Description</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Briefly describe the campaign goals..." required></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Handed over to</label>
                                <select class="form-select" name="handed_over_to" required>
                                    <option value="Morning">Morning</option>
                                    <option value="Evening">Evening</option>
                                    <option value="Night">Night</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Handover Date</label>
                                <input class="form-control" type="date" name="handover_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="submit" class="btn w-100 py-3 rounded-pill fw-bold text-white shadow-sm" style="background: #3a0ca3; border: none;">Launch Campaign Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Operational Observation Modal -->
    <?php
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
    ?>
    <div class="modal fade" id="staticBackdrop_observations" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-2xl rounded-4 overflow-hidden">
                <form action="<?php echo BASE_URL; ?>/modules/observations/create" method="POST" enctype="multipart/form-data">
                    <?php echo getCsrfField(); ?>
                    <div class="modal-header border-0 py-4 px-4 d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #2a9d8f 0%, #264653 100%);">
                        <div>
                            <h4 class="modal-title fw-bold text-white mb-0"><i class="fa-solid fa-clipboard-check me-2"></i>Observation Entry</h4>
                            <p class="text-white opacity-75 small mb-0 mt-1">Documenting operational findings and team assignments.</p>
                        </div>
                        <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body p-4 pt-5">
                        <div class="row g-4">
                            <!-- Section 1: General Information -->
                            <div class="col-12 mb-2">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="section-icon bg-teal-soft text-teal rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                        <i class="fa-solid fa-circle-info small"></i>
                                    </div>
                                    <h6 class="fw-bold text-uppercase letter-spacing-1 mb-0 text-dark opacity-75">General Information</h6>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-7">
                                        <label class="form-label small fw-bold text-muted text-uppercase mb-2"><i class="fa-solid fa-signature me-1"></i> Observation Name</label>
                                        <input type="text" class="form-control border-0 bg-light p-3 rounded-3" name="observation_names" placeholder="e.g. Health Check" required>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label small fw-bold text-muted text-uppercase mb-2"><i class="fa-solid fa-calendar-alt me-1"></i> Date & Time</label>
                                        <input type="datetime-local" class="form-control border-0 bg-light p-3 rounded-3" name="start_date" required>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12"><hr class="opacity-10"></div>

                            <!-- Section 2: Team Assignment -->
                            <div class="col-12 mb-2">
                                <!-- <div class="d-flex align-items-center mb-3">
                                    <div class="section-icon bg-blue-soft text-primary rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                        <i class="fa-solid fa-users small"></i>
                                    </div>
                                    <h6 class="fw-bold text-uppercase letter-spacing-1 mb-0 text-dark opacity-75">Team Assignment</h6>
                                </div> -->
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-2"><i class="fa-solid fa-user-plus me-1"></i> Impacted Teams</label>
                                    <div class="dropdown custom-team-dropdown">
                                        <button class="btn btn-white border w-100 text-start d-flex justify-content-between align-items-center py-3 px-3 rounded-3 shadow-none dropdown-toggle" type="button" id="teamDropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" style="background: #f8f9fa;">
                                            <span id="teamDropdownLabel">Select Teams</span>
                                        </button>
                                        <div class="dropdown-menu w-100 p-2 shadow-lg border-0 rounded-4 mt-2" aria-labelledby="teamDropdown" style="max-height: 280px; overflow-y: auto; background: rgba(255,255,255,0.98); backdrop-filter: blur(15px);">
                                            <div class="dropdown-item p-3 rounded hover-bg-light transition-all border-bottom mb-2 fw-bold text-primary d-flex justify-content-between align-items-center" style="cursor: pointer;" id="selectAllTeams">
                                                <span>SELECT ALL TEAMS</span>
                                                <i class="fa-solid fa-check-double opacity-50"></i>
                                            </div>
                                            <?php
                                            $teams = [
                                                "Tech Service Operations",
                                                "Tech Service Delivery",
                                                "Central Monitoring Center",
                                                "Network Operations",
                                                "Data Center Operations",
                                                "Server Storage & Backup Management",
                                                "Incident & Performance Management",
                                                "Database Management"
                                            ];
                                            foreach ($teams as $index => $team) {
                                                echo '
                                                <div class="dropdown-item p-3 rounded transition-all team-item d-flex justify-content-between align-items-center mb-1" style="cursor: pointer;" data-value="' . $team . '" data-id="team_' . $index . '">
                                                    <span class="small fw-medium">' . $team . '</span>
                                                    <i class="fa-solid fa-check check-icon opacity-0"></i>
                                                    <input class="team-checkbox d-none" type="checkbox" name="team_name[]" value="' . $team . '" id="team_' . $index . '">
                                                </div>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div id="selectedTeamsContainer" class="d-flex flex-wrap gap-2 mt-3">
                                        <!-- Badges will be injected here -->
                                        <span class="text-muted small italic opacity-50" id="noTeamsPlaceholder">No teams assigned yet.</span>
                                    </div>
                                </div>

                                <div class="col-12 mt-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-2"><i class="fa-solid fa-user-gear me-1"></i> Assigned Technician (L2)</label>
                                    <div class="dropdown custom-technician-dropdown">
                                        <button class="btn btn-white border w-100 text-start d-flex justify-content-between align-items-center py-3 px-3 rounded-3 shadow-none dropdown-toggle" type="button" id="techDropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" style="background: #f8f9fa;">
                                            <span id="techDropdownLabel" class="tech-dropdown-label">Select Technician from L2 Roster</span>
                                        </button>
                                        <div class="dropdown-menu w-100 p-2 shadow-lg border-0 rounded-4 mt-2" aria-labelledby="techDropdown" style="max-height: 350px; overflow-y: auto; background: rgba(255,255,255,0.98); backdrop-filter: blur(15px);">
                                            <div class="px-3 py-2 border-bottom mb-2">
                                                <input type="text" class="form-control form-control-sm border-0 bg-light rounded-pill px-3 tech-search-field" placeholder="Search by name..." autocomplete="off">
                                            </div>
                                            <div class="tech-list-container">
                                                <?php if (empty($l2_users)): ?>
                                                    <div class="dropdown-item p-3 text-muted small italic text-center">No L2 Analyst registered</div>
                                                <?php else: ?>
                                                    <?php foreach ($l2_users as $uname): ?>
                                                        <div class="dropdown-item p-3 rounded transition-all tech-item d-flex justify-content-between align-items-center mb-1" style="cursor: pointer;" data-value="<?= e($uname) ?>">
                                                            <div class="d-flex align-items-center">
                                                                <div class="avatar-sm bg-primary-soft text-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 10px;">
                                                                    <i class="fa-solid fa-user"></i>
                                                                </div>
                                                                <span class="small fw-medium"><?= e($uname) ?></span>
                                                            </div>
                                                            <i class="fa-solid fa-circle-check check-icon opacity-0 text-success"></i>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <input type="hidden" name="technician_name" id="selectedTechnician" class="selected-tech-input" value="">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12"><hr class="opacity-10"></div>

                            <!-- Section 3: Investigation Details -->
                            <div class="col-12 mb-2">
                                <!-- <div class="d-flex align-items-center mb-3">
                                    <div class="section-icon bg-orange-soft text-orange rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                        <i class="fa-solid fa-magnifying-glass small"></i>
                                    </div>
                                     <h6 class="fw-bold text-uppercase letter-spacing-1 mb-0 text-dark opacity-75">Investigation & Outcome</h6> 
                                </div> -->
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label small fw-bold text-muted text-uppercase mb-2"><i class="fa-solid fa-file-lines me-1"></i> L1 Investigation Findings</label>
                                        <textarea class="form-control border-0 bg-light p-3 rounded-3" name="l1_observation" rows="4" placeholder="Detail your findings, impact assessment, and any immediate actions taken..." required></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold text-muted text-uppercase mb-2"><i class="fa-solid fa-camera me-1"></i> Visual Evidence (Max 2 images)</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0"><i class="fa-solid fa-cloud-arrow-up text-muted"></i></span>
                                            <input type="file" class="form-control border-0 bg-light h-auto p-3" name="l1_images[]" multiple onchange="validateImageCount(this)" accept="image/*">
                                        </div>
                                        <div class="d-flex justify-content-between mt-2">
                                            <small class="text-muted"><i class="fa-solid fa-info-circle me-1"></i> Supported: JPG, PNG, GIF</small>
                                            <small id="imageCountBadge" class="badge bg-light text-muted border px-2 py-1">0 / 2 selected</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if (canEditL2()): ?>
                                <div class="col-12"><hr class="opacity-10"></div>
                                <div class="col-12">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="section-icon bg-info-soft text-info rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <i class="fa-solid fa-user-check small"></i>
                                        </div>
                                        <h6 class="fw-bold text-uppercase letter-spacing-1 mb-0 text-dark opacity-75">Level 2 Analyst Feedback</h6>
                                    </div>
                                    <textarea class="form-control border-info bg-info bg-opacity-5 p-3 rounded-3" name="l2_observation" rows="3" placeholder="Additional analysis, recommendations, or validation notes..."></textarea>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="modal-footer border-0 p-4 pt-0">
                        <div class="row w-100 g-3">
                            <div class="col-md-4">
                                <button type="button" class="btn btn-light w-100 py-3 rounded-pill fw-bold text-muted" data-bs-dismiss="modal">Discard changes</button>
                            </div>
                            <div class="col-md-8">
                                <button type="submit" class="btn w-100 py-3 rounded-pill fw-bold text-white shadow-lg transition-all hover-scale" style="background: linear-gradient(135deg, #2a9d8f 0%, #264653 100%); border: none;">
                                    <i class="fa-solid fa-cloud-arrow-up me-2"></i>Publish Observation Record
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Include Observations JavaScript -->
    <script src="<?php echo ASSETS_URL; ?>/js/observations.js"></script>
</div> <!-- End modals-container -->
