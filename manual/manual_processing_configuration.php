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

// to fetch the analyses from the our table
$analysisList = [];

$analysisQuery = $conn->prepare("
    SELECT lg_id, lg_name 
    FROM mg5_mdm_analysis 
    WHERE lg_status = 1
    ORDER BY lg_order ASC
");
$analysisQuery->execute();
$resAnalysis = $analysisQuery->get_result();

while ($row = $resAnalysis->fetch_assoc()) {
    $analysisList[] = $row;
}
$analysisQuery->close();


/* 
LOAD EXISTING CONFIGURATION
 */

$stmt = $conn->prepare("
SELECT
di_analysis_id,
di_priority_point,
di_scoring_logic,
di_scoring_basis,
di_total_basis,
di_result_type
FROM mg5_digisim
WHERE di_id = ?
");

$stmt->bind_param("i", $digisimId);
$stmt->execute();

$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $analysisId = $row['di_analysis_id'] ?? null;
    $priorityPoints = $row['di_priority_point'];
    $scoringLogic = $row['di_scoring_logic'];
    $scoringBasis = $row['di_scoring_basis'];
    $totalBasis = $row['di_total_basis'];
    $taskResultDisplay = $row['di_result_type'];
}

$stmt->close();


/* 
HANDLE FORM SUBMISSION
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $errors = [];

    /* ANALYSIS TYPE */
    if (empty($_POST['analysis_id'])) {
        $errors[] = "Please select analysis type";
    } else {
        $analysisId = intval($_POST['analysis_id']);
    }

    /* PRIORITY POINTS */
    if (!empty($_POST['priority_points'])) {
        if ($_POST['priority_points'] === 'expert') {
            $priorityPoints = 1;
        } else {
            $priorityPoints = 2;
        }
    } else {
        $errors[] = "Please select priority points";
    }

    /* SCORING LOGIC */
    if (empty($_POST['scoring_logic'])) {
        $errors[] = "Please select scoring logic";
    } else {
        switch ($_POST['scoring_logic']) {
            case 'atleast': $scoringLogic = 1; break;
            case 'actual': $scoringLogic = 2; break;
            case 'absolute': $scoringLogic = 3; break;
        }
    }

    /* SCORING BASIS */
    if (empty($_POST['scoring_basis'])) {
        $errors[] = "Please select scoring basis";
    } else {
        switch ($_POST['scoring_basis']) {
            case 'all': $scoringBasis = 1; break;
            case 'part': $scoringBasis = 2; break;
            case 'minimum': $scoringBasis = 3; break;
        }
    }

    /* TOTAL BASIS */
    if (empty($_POST['total_basis'])) {
        $errors[] = "Please select total basis";
    } else {
        switch ($_POST['total_basis']) {
            case 'all_tasks': $totalBasis = 1; break;
            case 'marked_tasks': $totalBasis = 2; break;
        }
    }

    /* RESULT DISPLAY */
    if (!empty($_POST['task_result_display'])) {

        if ($_POST['task_result_display'] === 'percentage') {
            $taskResultDisplay = 2;
        } elseif ($_POST['task_result_display'] === 'raw_score') {
            $taskResultDisplay = 3;
        } elseif ($_POST['task_result_display'] === 'legend') {
            $taskResultDisplay = 4;
        }

    } else {
        $errors[] = "Please select task result display";
    }

    if (empty($errors)) {

        /* UPDATE DIGISIM */

        $stmt = $conn->prepare("
            UPDATE mg5_digisim
            SET
            di_analysis_id=?, 
            di_priority_point=?,
            di_scoring_logic=?,
            di_scoring_basis=?,
            di_total_basis=?,
            di_result_type=?
            WHERE di_id=?
            ");

        $stmt->bind_param(
            "iiiiiii",
            $analysisId,
            $priorityPoints,
            $scoringLogic,
            $scoringBasis,
            $totalBasis,
            $taskResultDisplay,
            $digisimId
        );

        $stmt->execute();


        if (isset($_POST['action']) && $_POST['action'] === 'draft') {
            header("Location: manual_page_container.php?step=4&digisim_id=" . $digisimId);
        } else {
            header("Location: manual_page_container.php?step=5&digisim_id=" . $digisimId);
        }
        exit;
    }
}

?>




<div class="page-container">
    <?php include 'stepper.php'; ?>
    <div class="proc-shell">

        <div class="proc-main">

            <div style="margin-bottom: 20px;">
                <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">Processing Settings</h2>
                <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">Configure scoring logic and priority rules</p>
            </div>

            <!-- error messages -->
             <?php if (!empty($errors)): ?>
                <div style="background:#ffeaea; color:#b91c1c; padding:10px; border-radius:6px; margin-bottom:15px;">
                    <?php foreach ($errors as $err): ?>
                        <div>⚠️ <?= htmlspecialchars($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

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

                        <!-- for analysis -->
                        <section class="proc-section">
                            <h2>Analysis Type</h2>

                            <div class="proc-list-col">
                                <?php foreach ($analysisList as $analysis): ?>
                                    <label class="proc-list-option">
                                        <input
                                            type="radio"
                                            name="analysis_id"
                                            value="<?= $analysis['lg_id'] ?>"
                                            <?= ($analysisId == $analysis['lg_id']) ? 'checked' : '' ?>>
                                        <div>
                                            <strong><?= htmlspecialchars($analysis['lg_name']) ?></strong>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
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
                                    <input type="radio" name="task_result_display" value="percentage"
                                        <?= $taskResultDisplay == 2 ? 'checked' : '' ?>>
                                    <span>Percentage</span>
                                </label>

                                <label class="proc-check-row">
                                    <input type="radio" name="task_result_display" value="raw_score"
                                        <?= $taskResultDisplay == 3 ? 'checked' : '' ?>>
                                    <span>Raw Score</span>
                                </label>

                                <label class="proc-check-row">
                                    <input type="radio" name="task_result_display" value="legend"
                                        <?= $taskResultDisplay == 4 ? 'checked' : '' ?>>
                                    <span>Legend</span>
                                </label>

                            </div>
                        </section>
                    </div>
                </div>
            </form>

        </div>



    </div>
</div>
<script>
    /* document.getElementById("procForm").addEventListener("submit", function(e) {

    const requiredRadios = [
        "analysis_id",
        "priority_points",
        "scoring_logic",
        "scoring_basis",
        "total_basis"
    ];

    for (let name of requiredRadios) {
        if (!document.querySelector(`input[name="${name}"]:checked`)) {
            alert("Please complete all required selections");
            e.preventDefault();
            return;
        }
    }

    const resultDisplay = document.querySelector("input[name='task_result_display']:checked");

    if (!resultDisplay) {
        alert("Please select task result display");
        e.preventDefault();
    }
    }); */
</script>