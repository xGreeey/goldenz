<?php
$page_title = 'Add New Employee - Page 2 - Golden Z-5 HR System';
$page = 'add_employee_page2';

// Get logged-in user information
$current_user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 1;
$current_user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? $_SESSION['username'] ?? 'System Administrator';

// Try to get user name from database if we have user_id
if ($current_user_id && function_exists('get_db_connection')) {
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT name, username FROM users WHERE id = ?");
        $stmt->execute([$current_user_id]);
        $user = $stmt->fetch();
        if ($user) {
            $current_user_name = $user['name'] ?? $user['username'] ?? $current_user_name;
        }
    } catch (Exception $e) {
        // Use default if database query fails
    }
}
?>

<div class="container-fluid hrdash add-employee-container add-employee-modern">
    <!-- Page Header -->
    <div class="page-header-modern">
        <div class="page-title-modern">
            <h1 class="page-title-main">Add New Employee - Page 2</h1>
            <p class="page-subtitle-modern">Complete the employee application form</p>
        </div>
        <div class="page-actions-modern">
            <a href="?page=add_employee" class="btn btn-outline-modern me-2">
                <i class="fas fa-arrow-left me-2"></i>Back to Page 1
            </a>
            <a href="?page=employees" class="btn btn-outline-modern">
                <i class="fas fa-times me-2"></i>Cancel
            </a>
        </div>
    </div>

    <!-- Breadcrumb -->
    <nav class="hr-breadcrumb" aria-label="Breadcrumb">
        <ol class="hr-breadcrumb__list">
            <li class="hr-breadcrumb__item">
                <a href="?page=dashboard" class="hr-breadcrumb__link">Dashboard</a>
            </li>
            <li class="hr-breadcrumb__item">
                <a href="?page=employees" class="hr-breadcrumb__link">Employees</a>
            </li>
            <li class="hr-breadcrumb__item">
                <a href="?page=add_employee" class="hr-breadcrumb__link">Add Employee</a>
            </li>
            <li class="hr-breadcrumb__item hr-breadcrumb__current" aria-current="page">
                Page 2
            </li>
        </ol>
    </nav>

    <!-- Page 2 Form -->
    <div class="card card-modern">
        <div class="card-header card-header-modern">
            <h3 class="card-title-modern">Employee Application Form - Page 2</h3>
        </div>
        <div class="card-body card-body-modern">
            <form method="POST" id="page2EmployeeForm" enctype="multipart/form-data" action="?page=add_employee_page2" novalidate>
                
                <!-- Employee Created By Info -->
                <div class="alert alert-info">
                    <span class="hr-icon hr-icon-message me-2"></span>
                    <strong>Recorded By:</strong> <?php echo htmlspecialchars($current_user_name); ?> 
                    <?php if ($current_user_id): ?>
                        (User ID: <?php echo $current_user_id; ?>)
                    <?php endif; ?>
                </div>

                <!-- General Information Section -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <h4 class="form-section-title">GENERAL INFORMATION</h4>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">1. How did you know of the vacancy in the AGENCY?</label>
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="vacancy_source[]" id="vacancy_ads" value="Ads">
                                    <label class="form-check-label" for="vacancy_ads">Ads</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="vacancy_source[]" id="vacancy_walkin" value="Walk-in">
                                    <label class="form-check-label" for="vacancy_walkin">Walk-in</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="vacancy_source[]" id="vacancy_referral" value="Referral">
                                    <label class="form-check-label" for="vacancy_referral">Referral (Name)</label>
                                </div>
                                <input type="text" class="form-control" id="referral_name" name="referral_name" placeholder="Name" style="max-width: 300px; display: inline-block;">
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">2. Do you know anyone from the AGENCY prior to your application?</label>
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="knows_agency_person" id="knows_yes" value="Yes">
                                    <label class="form-check-label" for="knows_yes">Yes, state his/her name and your relationship with him/her</label>
                                </div>
                                <input type="text" class="form-control" id="agency_person_name" name="agency_person_name" placeholder="Name and relationship" style="max-width: 400px; display: inline-block;">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="knows_agency_person" id="knows_no" value="No">
                                    <label class="form-check-label" for="knows_no">No.</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">3. Do you have any physical defect/s or chronic ailments?</label>
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="physical_defect" id="defect_yes" value="Yes">
                                    <label class="form-check-label" for="defect_yes">Yes, please specify</label>
                                </div>
                                <input type="text" class="form-control" id="physical_defect_specify" name="physical_defect_specify" placeholder="Specify" style="max-width: 400px; display: inline-block;">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="physical_defect" id="defect_no" value="No">
                                    <label class="form-check-label" for="defect_no">No.</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">4. Do you drive?</label>
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="drives" id="drives_yes" value="Yes">
                                    <label class="form-check-label" for="drives_yes">Yes, Driver's License no. / Expiration Date:</label>
                                </div>
                                <input type="text" class="form-control" id="drivers_license_no" name="drivers_license_no" placeholder="License No." style="max-width: 180px; display: inline-block;">
                                <span style="margin: 0 5px;">/</span>
                                <input type="text" class="form-control" id="drivers_license_exp" name="drivers_license_exp" placeholder="Expiration Date" style="max-width: 180px; display: inline-block;">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="drives" id="drives_no" value="No">
                                    <label class="form-check-label" for="drives_no">No.</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">5. Do you drink alcoholic beverages?</label>
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="drinks_alcohol" id="alcohol_yes" value="Yes">
                                    <label class="form-check-label" for="alcohol_yes">Yes, how frequent?</label>
                                </div>
                                <input type="text" class="form-control" id="alcohol_frequency" name="alcohol_frequency" placeholder="Frequency" style="max-width: 300px; display: inline-block;">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="drinks_alcohol" id="alcohol_no" value="No">
                                    <label class="form-check-label" for="alcohol_no">No.</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">6. Are you taking prohibited drugs?</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="prohibited_drugs" id="drugs_yes" value="Yes">
                                    <label class="form-check-label" for="drugs_yes">Yes</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="prohibited_drugs" id="drugs_no" value="No">
                                    <label class="form-check-label" for="drugs_no">No</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <label for="security_guard_experience" class="form-label">7. How long have you worked as a Security Guard?</label>
                            <input type="text" class="form-control" id="security_guard_experience" name="security_guard_experience" placeholder="e.g., 2 years">
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">8. Have you ever been convicted of any <strong>OFFENSE (criminal or civil)</strong> before a court competent jurisdiction?</label>
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="convicted" id="convicted_yes" value="Yes">
                                    <label class="form-check-label" for="convicted_yes">Yes, please specify</label>
                                </div>
                                <input type="text" class="form-control" id="conviction_details" name="conviction_details" placeholder="Specify" style="max-width: 400px; display: inline-block;">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="convicted" id="convicted_no" value="No">
                                    <label class="form-check-label" for="convicted_no">No.</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">9. Have you filed any <strong>CRIMINAL / CIVIL CASE (labor)</strong> against any of your previous employer?</label>
                            <div class="d-flex flex-wrap gap-3 align-items-center mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filed_case" id="case_yes" value="Yes">
                                    <label class="form-check-label" for="case_yes">Yes</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filed_case" id="case_no" value="No">
                                    <label class="form-check-label" for="case_no">No.</label>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <label class="form-label mb-0">If YES, please specify:</label>
                                <input type="text" class="form-control" id="case_specify" name="case_specify" placeholder="Specify case" style="max-width: 300px; display: inline-block;">
                                <label class="form-label mb-0">and what was your action after your termination?</label>
                                <input type="text" class="form-control" id="action_after_termination" name="action_after_termination" placeholder="Action taken" style="max-width: 300px; display: inline-block;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Specimen Signature and Initial Section -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <h4 class="form-section-title">SPECIMEN SIGNATURE AND INITIAL</h4>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><strong>SIGNATURE</strong></label>
                            <div class="mb-2">
                                <label class="form-label small">1.</label>
                                <input type="text" class="form-control" id="signature_1" name="signature_1" placeholder="Signature line 1">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">2.</label>
                                <input type="text" class="form-control" id="signature_2" name="signature_2" placeholder="Signature line 2">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">3.</label>
                                <input type="text" class="form-control" id="signature_3" name="signature_3" placeholder="Signature line 3">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><strong>INITIAL (PINAIKLING PIRMA)</strong></label>
                            <div class="mb-2">
                                <label class="form-label small">1.</label>
                                <input type="text" class="form-control" id="initial_1" name="initial_1" placeholder="Initial 1">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">2.</label>
                                <input type="text" class="form-control" id="initial_2" name="initial_2" placeholder="Initial 2">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">3.</label>
                                <input type="text" class="form-control" id="initial_3" name="initial_3" placeholder="Initial 3">
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <p class="mb-0"><small>I HEREBY CERTIFY that the above specimen is my <strong>OFFICIAL</strong> signatures and initial of which I <strong>CONFIRM</strong> by my signature below.</small></p>
                        </div>
                    </div>
                </div>

                <!-- Fingerprints Section -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <h4 class="form-section-title">FINGERPRINTS</h4>
                    </div>
                    
                    <div class="col-12">
                        <div class="table-responsive">
                            <table class="table table-bordered text-center" style="max-width: 800px; margin: 0 auto;">
                                <thead>
                                    <tr>
                                        <th>RIGHT Thumb</th>
                                        <th>RIGHT Index Finger</th>
                                        <th>RIGHT Middle Finger</th>
                                        <th>RIGHT Ring Finger</th>
                                        <th>RIGHT Little Finger</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="height: 80px; vertical-align: middle;">
                                            <input type="file" class="form-control form-control-sm" name="fingerprint_right_thumb" accept="image/*">
                                        </td>
                                        <td style="height: 80px; vertical-align: middle;">
                                            <input type="file" class="form-control form-control-sm" name="fingerprint_right_index" accept="image/*">
                                        </td>
                                        <td style="height: 80px; vertical-align: middle;">
                                            <input type="file" class="form-control form-control-sm" name="fingerprint_right_middle" accept="image/*">
                                        </td>
                                        <td style="height: 80px; vertical-align: middle;">
                                            <input type="file" class="form-control form-control-sm" name="fingerprint_right_ring" accept="image/*">
                                        </td>
                                        <td style="height: 80px; vertical-align: middle;">
                                            <input type="file" class="form-control form-control-sm" name="fingerprint_right_little" accept="image/*">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="table-responsive mt-3">
                            <table class="table table-bordered text-center" style="max-width: 800px; margin: 0 auto;">
                                <thead>
                                    <tr>
                                        <th>LEFT Thumb</th>
                                        <th>LEFT Index Finger</th>
                                        <th>LEFT Middle Finger</th>
                                        <th>LEFT Ring Finger</th>
                                        <th>LEFT Little Finger</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="height: 80px; vertical-align: middle;">
                                            <input type="file" class="form-control form-control-sm" name="fingerprint_left_thumb" accept="image/*">
                                        </td>
                                        <td style="height: 80px; vertical-align: middle;">
                                            <input type="file" class="form-control form-control-sm" name="fingerprint_left_index" accept="image/*">
                                        </td>
                                        <td style="height: 80px; vertical-align: middle;">
                                            <input type="file" class="form-control form-control-sm" name="fingerprint_left_middle" accept="image/*">
                                        </td>
                                        <td style="height: 80px; vertical-align: middle;">
                                            <input type="file" class="form-control form-control-sm" name="fingerprint_left_ring" accept="image/*">
                                        </td>
                                        <td style="height: 80px; vertical-align: middle;">
                                            <input type="file" class="form-control form-control-sm" name="fingerprint_left_little" accept="image/*">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Basic Requirements Section -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <h4 class="form-section-title">BASIC REQUIREMENTS</h4>
                    </div>
                    
                    <div class="col-12 text-end mb-3">
                        <label class="form-label">Signature Over Printed Name</label>
                        <input type="text" class="form-control" id="requirements_signature" name="requirements_signature" placeholder="Signature" style="max-width: 400px; margin-left: auto;">
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label"><strong>Provided on Application:</strong> Y☐ N☐</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">1. Close up 2x2 (2pcs)</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_2x2" id="req_2x2_y" value="YO">
                                            <label class="form-check-label" for="req_2x2_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_2x2" id="req_2x2_n" value="NO">
                                            <label class="form-check-label" for="req_2x2_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">2. NSO, Birth Certificate</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_birth_cert" id="req_birth_cert_y" value="YO">
                                            <label class="form-check-label" for="req_birth_cert_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_birth_cert" id="req_birth_cert_n" value="NO">
                                            <label class="form-check-label" for="req_birth_cert_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">3. Barangay Clearance</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_barangay" id="req_barangay_y" value="YO">
                                            <label class="form-check-label" for="req_barangay_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_barangay" id="req_barangay_n" value="NO">
                                            <label class="form-check-label" for="req_barangay_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">4. Police Clearance (local)</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_police" id="req_police_y" value="YO">
                                            <label class="form-check-label" for="req_police_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_police" id="req_police_n" value="NO">
                                            <label class="form-check-label" for="req_police_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">5. NBI (for cases purposes)</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_nbi" id="req_nbi_y" value="YO">
                                            <label class="form-check-label" for="req_nbi_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_nbi" id="req_nbi_n" value="NO">
                                            <label class="form-check-label" for="req_nbi_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">6. D.I. Clearance</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_di" id="req_di_y" value="YO">
                                            <label class="form-check-label" for="req_di_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_di" id="req_di_n" value="NO">
                                            <label class="form-check-label" for="req_di_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">7. High School / College Diploma</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_diploma" id="req_diploma_y" value="YO">
                                            <label class="form-check-label" for="req_diploma_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_diploma" id="req_diploma_n" value="NO">
                                            <label class="form-check-label" for="req_diploma_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">8. Neuro & Drug test result</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_neuro_drug" id="req_neuro_drug_y" value="YO">
                                            <label class="form-check-label" for="req_neuro_drug_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_neuro_drug" id="req_neuro_drug_n" value="NO">
                                            <label class="form-check-label" for="req_neuro_drug_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">9. Sec.License Certi. fr. SOSIA</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_sec_license" id="req_sec_license_y" value="YO">
                                            <label class="form-check-label" for="req_sec_license_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_sec_license" id="req_sec_license_n" value="NO">
                                            <label class="form-check-label" for="req_sec_license_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label"><strong>I.D. copy provision:</strong></label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">10. Sec. Lic. No.</label>
                                    <div class="d-flex gap-3 align-items-center">
                                        <input type="text" class="form-control" id="sec_lic_no" name="sec_lic_no" placeholder="License Number" style="max-width: 300px;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_sec_lic_no" id="req_sec_lic_no_y" value="YO">
                                            <label class="form-check-label" for="req_sec_lic_no_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_sec_lic_no" id="req_sec_lic_no_n" value="NO">
                                            <label class="form-check-label" for="req_sec_lic_no_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">11. SSS No.</label>
                                    <div class="d-flex gap-3 align-items-center">
                                        <input type="text" class="form-control" id="sss_no_page2" name="sss_no_page2" placeholder="SSS Number" style="max-width: 300px;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_sss" id="req_sss_y" value="YO">
                                            <label class="form-check-label" for="req_sss_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_sss" id="req_sss_n" value="NO">
                                            <label class="form-check-label" for="req_sss_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">12. Pag-Ibig No.</label>
                                    <div class="d-flex gap-3 align-items-center">
                                        <input type="text" class="form-control" id="pagibig_no_page2" name="pagibig_no_page2" placeholder="Pag-Ibig Number" style="max-width: 300px;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_pagibig" id="req_pagibig_y" value="YO">
                                            <label class="form-check-label" for="req_pagibig_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_pagibig" id="req_pagibig_n" value="NO">
                                            <label class="form-check-label" for="req_pagibig_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">13. PhilHealth No.</label>
                                    <div class="d-flex gap-3 align-items-center">
                                        <input type="text" class="form-control" id="philhealth_no_page2" name="philhealth_no_page2" placeholder="PhilHealth Number" style="max-width: 300px;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_philhealth" id="req_philhealth_y" value="YO">
                                            <label class="form-check-label" for="req_philhealth_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_philhealth" id="req_philhealth_n" value="NO">
                                            <label class="form-check-label" for="req_philhealth_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">14. TIN No.</label>
                                    <div class="d-flex gap-3 align-items-center">
                                        <input type="text" class="form-control" id="tin_no_page2" name="tin_no_page2" placeholder="TIN Number" style="max-width: 300px;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_tin" id="req_tin_y" value="YO">
                                            <label class="form-check-label" for="req_tin_y">YO</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="req_tin" id="req_tin_n" value="NO">
                                            <label class="form-check-label" for="req_tin_n">NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sworn Statement Section -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <h4 class="form-section-title">SWORN STATEMENT</h4>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-group">
                            <p class="mb-3"><small>I HEREBY AUTHORIZE the Company, <strong>GOLDEN Z-5 SECURITY & INVESTIGATION AGENCY, INC.</strong> to conduct further investigation and inquiry as to my personal, past employment and such other related background Information. I hereby release from any and all liabilities all persons, companies, corporations, and institutions supplying any information with respect to my background, character, and employment history. I understand that any misinterpretation or omission of facts can lead to application revocation or dismissal.</small></p>
                            
                            <p class="mb-3"><small>I <strong>UNDERSTAND</strong> that if my application is considered, my appointment will be on a <strong>PROBATIONARY</strong> basis for a period not more than six (6) months, and that during this period, my services may be terminated without prior notice and without liability on the part of the Company. I further understand that my employment is subject to my compliance with all the rules and regulations of the Company, and that violation of any of these rules and regulations may result in my immediate dismissal.</small></p>
                            
                            <p class="mb-3"><small>I HEREBY CERTIFY that all information given in this application form are true and correct and any false statement or misrepresentation shall be a ground for the termination of my employment with the Company without prejudice to the filing of <strong>APPROPRIATE CRIMINAL PROCEEDINGS</strong> by reason thereof.</small></p>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">SUBSCRIBED AND SWORN to before me this</label>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <input type="text" class="form-control" id="sworn_day" name="sworn_day" placeholder="Day" style="max-width: 100px;">
                                <label class="form-label mb-0">day of</label>
                                <input type="text" class="form-control" id="sworn_month" name="sworn_month" placeholder="Month" style="max-width: 150px;">
                                <label class="form-label mb-0">on</label>
                                <input type="text" class="form-control" id="sworn_year" name="sworn_year" placeholder="Year" style="max-width: 100px;">
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">Tax Certificate No.</label>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <input type="text" class="form-control" id="tax_cert_no" name="tax_cert_no" placeholder="Tax Certificate No." style="max-width: 300px;">
                                <label class="form-label mb-0">issued at</label>
                                <input type="text" class="form-control" id="tax_cert_issued_at" name="tax_cert_issued_at" placeholder="Location" style="max-width: 300px;">
                            </div>
                        </div>
                    </div>

                    <div class="col-12 text-end">
                        <div class="form-group">
                            <label class="form-label">Signature Over Printed Name</label>
                            <input type="text" class="form-control" id="sworn_signature" name="sworn_signature" placeholder="Signature" style="max-width: 400px; margin-left: auto;">
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">Affiant exhibited to me his/her Community</label>
                            <input type="text" class="form-control" id="affiant_community" name="affiant_community" placeholder="Community" style="max-width: 400px;">
                        </div>
                    </div>

                    <div class="col-12 text-end">
                        <div class="form-group">
                            <label class="form-label"><strong>NOTARY PUBLIC</strong></label>
                        </div>
                    </div>
                </div>

                <!-- Form Footer -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <div class="d-flex flex-wrap gap-4">
                            <div class="form-group">
                                <label class="form-label">Doc. No.:</label>
                                <input type="text" class="form-control" id="doc_no" name="doc_no" placeholder="Document No." style="max-width: 150px;">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Page No.:</label>
                                <input type="text" class="form-control" id="page_no" name="page_no" placeholder="Page No." style="max-width: 150px;" value="2" readonly>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Book No.:</label>
                                <input type="text" class="form-control" id="book_no" name="book_no" placeholder="Book No." style="max-width: 150px;">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Series of:</label>
                                <input type="text" class="form-control" id="series_of" name="series_of" placeholder="Series" style="max-width: 150px;">
               Р            </div>
                        </div>
                    </div>
                </div>

                <!-- Page 2 Form Actions -->
                <div class="form-actions d-flex justify-content-start">
                    <a href="?page=add_employee" class="btn btn-outline-modern">
                        <i class="fas fa-arrow-left me-2"></i>Back to Page 1
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Include paths helper if not already included
if (!function_exists('base_url')) {
    require_once __DIR__ . '/../includes/paths.php';
}
// Calculate CSS path relative to project root
$root_prefix = root_prefix();
$css_path = ($root_prefix ? $root_prefix : '') . '/pages/css/add_employee.css';
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($css_path); ?>">

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Page 2: Make Y/N checkboxes mutually exclusive
    document.querySelectorAll('input[type="checkbox"][name^="req_"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                // Get all checkboxes with the same name
                const sameName = document.querySelectorAll(`input[type="checkbox"][name="${this.name}"]`);
                sameName.forEach(cb => {
                    if (cb !== this) {
                        cb.checked = false;
                    }
                });
            }
        });
    });
});
</script>
