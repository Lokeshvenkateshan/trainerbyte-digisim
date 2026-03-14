<?php

$pageTitle = 'Processing Configuration';
$pageCSS = '/manual/css/manual_processing_configuration.css';

require_once __DIR__ . '/../include/dataconnect.php';

$digisimId = isset($_GET['digisim_id']) ? intval($_GET['digisim_id']) : 0;

if ($digisimId <= 0) {
    header("Location: manual_page_container.php?step=1");
    exit;
}

$errors = [];

$priorityPoints = null;
$scoringLogic = null;
$scoringBasis = null;
$totalBasis = null;
$taskResultDisplay = null;


/* -----------------------------------------
LOAD EXISTING CONFIGURATION
----------------------------------------- */

$stmt = $conn->prepare("
SELECT
di_priority_point,
di_scoring_logic,
di_scoring_basis,
di_total_basis,
di_result_type
FROM mg5_digisim
WHERE di_id = ?
");

$stmt->bind_param("i",$digisimId);
$stmt->execute();

$result = $stmt->get_result();

if($row = $result->fetch_assoc()){

$priorityPoints = $row['di_priority_point'];
$scoringLogic = $row['di_scoring_logic'];
$scoringBasis = $row['di_scoring_basis'];
$totalBasis = $row['di_total_basis'];
$taskResultDisplay = $row['di_result_type'];

}

$stmt->close();


/* -----------------------------------------
HANDLE FORM SUBMISSION
----------------------------------------- */

if($_SERVER['REQUEST_METHOD'] === 'POST'){

/* PRIORITY POINTS */

if($_POST['priority_points'] === 'expert'){
$priorityPoints = 1;
}
elseif($_POST['priority_points'] === 'manual'){
$priorityPoints = 2;
}


/* SCORING LOGIC */

switch($_POST['scoring_logic']){

case 'atleast': $scoringLogic = 1; break;
case 'actual': $scoringLogic = 2; break;
case 'absolute': $scoringLogic = 3; break;

}


/* SCORING BASIS */

switch($_POST['scoring_basis']){

case 'all': $scoringBasis = 1; break;
case 'part': $scoringBasis = 2; break;
case 'minimum': $scoringBasis = 3; break;

}


/* TOTAL BASIS */

switch($_POST['total_basis']){

case 'all_tasks': $totalBasis = 1; break;
case 'marked_tasks': $totalBasis = 2; break;

}


/* RESULT DISPLAY */

$taskResultDisplay = 1;

if(in_array('percentage',$_POST['task_result_display'])){
$taskResultDisplay = 2;
}
elseif(in_array('raw_score',$_POST['task_result_display'])){
$taskResultDisplay = 3;
}
elseif(in_array('legend',$_POST['task_result_display'])){
$taskResultDisplay = 4;
}


/* UPDATE DIGISIM */

$stmt = $conn->prepare("
UPDATE mg5_digisim
SET
di_priority_point=?,
di_scoring_logic=?,
di_scoring_basis=?,
di_total_basis=?,
di_result_type=?
WHERE di_id=?
");

$stmt->bind_param(
"iiiiii",
$priorityPoints,
$scoringLogic,
$scoringBasis,
$totalBasis,
$taskResultDisplay,
$digisimId
);

$stmt->execute();


if(isset($_POST['action']) && $_POST['action'] === 'draft'){
    header("Location: manual_page_container.php?step=4&digisim_id=".$digisimId);
} else {
    header("Location: manual_page_container.php?step=5&digisim_id=".$digisimId);
}
exit;

}

?>


<!-- Material Symbols font -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">

<div class="proc-shell">

    <div class="proc-main">

        <!-- Progress -->
        <div class="proc-progress-wrap">
            <div class="proc-progress-header">
                <div class="proc-progress-title">
                    <h1>Processing Configuration</h1>
                    <p>Configure how simulation results are calculated and displayed</p>
                </div>
                <div class="proc-progress-step">
                    <p>Step 4: Processing Settings</p>
                    <span>80% Complete</span>
                </div>
            </div>
            <div class="proc-progress-bar-bg">
                <div class="proc-progress-bar-fill" style="width: 80%;"></div>
            </div>
        </div>

        <form method="POST" id="procForm">
            <div class="proc-grid">

                <!-- LEFT COLUMN -->
                <div class="proc-col">

                    <!-- Priority Points -->
                    <section class="proc-section">
                        <h2>Priority Points</h2>
                        <div class="proc-priority-grid">
                            <label>
                                <input type="radio" class="proc-hidden-radio" name="priority_points" value="expert" <?= $priorityPoints == 1 ? 'checked' : '' ?>>
                                <div class="proc-card-option">
                                    <strong>Expert</strong>
                                    <span>Pre-set weights</span>
                                    <span class="material-symbols-outlined proc-card-icon">check_circle</span>
                                </div>
                            </label>
                            <label>
                                <input type="radio" class="proc-hidden-radio" name="priority_points" value="manual" <?= $priorityPoints == 2 ? 'checked' : '' ?>>
                                <div class="proc-card-option">
                                    <strong>Manual</strong>
                                    <span>Custom weights</span>
                                    <span class="material-symbols-outlined proc-card-icon">check_circle</span>
                                </div>
                            </label>
                        </div>
                    </section>

                    <!-- Scoring Logic -->
                    <section class="proc-section">
                        <h2>Scoring Logic</h2>
                        <div class="proc-list-col">
                            <label class="proc-list-option">
                                <input type="radio" name="scoring_logic" value="atleast" <?= $scoringLogic == 1 ? 'checked' : '' ?>>
                                <div>
                                    <strong>Atleast</strong>
                                    <span>Threshold based logic</span>
                                </div>
                            </label>
                            <label class="proc-list-option">
                                <input type="radio" name="scoring_logic" value="actual" <?= $scoringLogic == 2 ? 'checked' : '' ?>>
                                <div>
                                    <strong>Actual</strong>
                                    <span>Direct performance value</span>
                                </div>
                            </label>
                            <label class="proc-list-option">
                                <input type="radio" name="scoring_logic" value="absolute" <?= $scoringLogic == 3 ? 'checked' : '' ?>>
                                <div>
                                    <strong>Absolute</strong>
                                    <span>Fixed value calculation</span>
                                </div>
                            </label>
                        </div>
                    </section>
                </div>

                <!-- RIGHT COLUMN -->
                <div class="proc-col">

                    <!-- Scoring Basis -->
                    <section class="proc-section">
                        <h2>Scoring Basis</h2>
                        <div class="proc-pills-row">
                            <label class="proc-pill">
                                <input type="radio" class="proc-hidden-radio" name="scoring_basis" value="all" <?= $scoringBasis == 1 ? 'checked' : '' ?>>
                                <span class="proc-pill-text">All</span>
                            </label>
                            <label class="proc-pill">
                                <input type="radio" class="proc-hidden-radio" name="scoring_basis" value="part" <?= $scoringBasis == 2 ? 'checked' : '' ?>>
                                <span class="proc-pill-text">Part</span>
                            </label>
                            <label class="proc-pill">
                                <input type="radio" class="proc-hidden-radio" name="scoring_basis" value="minimum" <?= $scoringBasis == 3 ? 'checked' : '' ?>>
                                <span class="proc-pill-text">Minimum</span>
                            </label>
                        </div>
                    </section>

                    <!-- Total Basis -->
                    <section class="proc-section">
                        <h2>Total Basis</h2>
                        <div class="proc-list-col">
                            <label class="proc-list-option right-radio">
                                <div class="icon-text">
                                    <span class="material-symbols-outlined">layers</span>
                                    <strong>All Tasks</strong>
                                </div>
                                <input type="radio" name="total_basis" value="all_tasks" <?= $totalBasis == 1 ? 'checked' : '' ?>>
                            </label>
                            <label class="proc-list-option right-radio">
                                <div class="icon-text">
                                    <span class="material-symbols-outlined">bookmark</span>
                                    <strong>Marked Tasks Only</strong>
                                </div>
                                <input type="radio" name="total_basis" value="marked_tasks" <?= $totalBasis == 2 ? 'checked' : '' ?>>
                            </label>
                        </div>
                    </section>

                    <!-- Task Result Display -->
                    <section class="proc-section">
                        <h2>Task Result Display</h2>
                        <div class="proc-list-col">
                            <label class="proc-check-row">
                                <input type="checkbox" name="task_result_display[]" value="percentage" <?= $taskResultDisplay == 2 ? 'checked' : '' ?>>
                                <span>Percentage</span>
                            </label>
                            <label class="proc-check-row">
                                <input type="checkbox" name="task_result_display[]" value="raw_score" <?= $taskResultDisplay == 3 ? 'checked' : '' ?>>
                                <span>Raw Score</span>
                            </label>
                            <label class="proc-check-row">
                                <input type="checkbox" name="task_result_display[]" value="legend" <?= $taskResultDisplay == 4 ? 'checked' : '' ?>>
                                <span>Legend</span>
                            </label>
                        </div>
                    </section>
                </div>
            </div><!-- /.proc-grid -->
        </form>

    </div><!-- /.proc-main -->

    <!-- Footer Actions -->
    <footer class="proc-footer">
        <div class="proc-footer-inner">
            <a href="manual_page_container.php?step=3&digisim_id=<?=$digisimId?>" class="proc-btn-back">
                <span class="material-symbols-outlined">arrow_back</span>
                Back
            </a>
            <div class="proc-footer-right">
                <button type="submit" form="procForm" name="action" value="draft" class="proc-btn-save-draft">Save Progress</button>
                <button type="submit" form="procForm" name="action" value="next" class="proc-btn-next">
                    Next Step
                    <span class="material-symbols-outlined">arrow_forward</span>
                </button>
            </div>
        </div>
    </footer>

</div><!-- /.proc-shell -->
