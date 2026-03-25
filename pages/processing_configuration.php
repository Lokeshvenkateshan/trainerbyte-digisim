<?php
$pageTitle = 'Processing Configuration';
$pageCSS = '/pages/page-styles/processing_configuration.css';

require_once __DIR__ . '/../include/dataconnect.php';
$simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;

if ($simId <= 0) {
    header("Location: page-container.php?step=1");
    exit;
}

$errors = [];
$priorityPoints = $scoringLogic = $scoringBasis = $totalBasis = $taskResultDisplay = $thresholdValue = null;

// to fetch the analyses for the db
$analysisList = [];

$analysisQuery = $conn->prepare("
    SELECT lg_id, lg_name 
    FROM mg5_mdm_analysis 
    WHERE lg_status = 1
    ORDER BY lg_order ASC
");
$analysisQuery->execute();
$resultAnalysis = $analysisQuery->get_result();

while ($row = $resultAnalysis->fetch_assoc()) {
    $analysisList[] = $row;
}
$analysisQuery->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Priority Points
    if (isset($_POST['priority_points']) && $_POST['priority_points'] === 'expert') {
        $priorityPoints = 1;
    } elseif (isset($_POST['priority_points']) && $_POST['priority_points'] === 'manual') {
        $priorityPoints = 2;
        $thresholdValue = isset($_POST['threshold_value']) ? intval($_POST['threshold_value']) : 10;
    } else {
        $errors['priority'] = 'Please select a priority point option';
    }

    // Scoring Logic
    if (isset($_POST['scoring_logic'])) {
        switch ($_POST['scoring_logic']) {
            case 'atleast': $scoringLogic = 1; break;
            case 'actual': $scoringLogic = 2; break;
            case 'absolute': $scoringLogic = 3; break;
            default: $errors['scoring_logic'] = 'Invalid scoring logic selected';
        }
    } else {
        $errors['scoring_logic'] = 'Please select a scoring logic';
    }

    // Scoring Basis
    if (isset($_POST['scoring_basis'])) {
        switch ($_POST['scoring_basis']) {
            case 'all': $scoringBasis = 1; break;
            case 'part': $scoringBasis = 2; break;
            case 'minimum': $scoringBasis = 3; break;
            default: $errors['scoring_basis'] = 'Invalid scoring basis selected';
        }
    } else {
        $errors['scoring_basis'] = 'Please select a scoring basis';
    }

    // Total Basis
    if (isset($_POST['total_basis'])) {
        switch ($_POST['total_basis']) {
            case 'all_tasks': $totalBasis = 1; break;
            case 'marked_tasks': $totalBasis = 2; break;
            default: $errors['total_basis'] = 'Invalid total basis selected';
        }
    } else {
        $errors['total_basis'] = 'Please select a total basis';
    }

    // Task Result Display - SINGLE SELECTION ONLY (radio buttons)
    if (isset($_POST['task_result_display'])) {
        switch ($_POST['task_result_display']) {
            case 'percentage': $taskResultDisplay = 2; break;
            case 'raw_score': $taskResultDisplay = 3; break;
            case 'legend': $taskResultDisplay = 4; break;
            default: $taskResultDisplay = 1;
        }
    } else {
        $taskResultDisplay = 1;
    }

    // Analysis Selection
    if (isset($_POST['analysis_id'])) {
        $analysisId = intval($_POST['analysis_id']);
    } else {
        $errors['analysis'] = 'Please select an analysis type';
    }

    // Save if no errors
    if (empty($errors)) {
        try {
            $updateStmt = $conn->prepare("
                UPDATE mg5_digisim_userinput 
                SET 
                    ui_analysis_id = ?,
                    ui_priority_points = ?,
                    ui_scoring_logic = ?,
                    ui_scoring_basis = ?,
                    ui_total_basis = ?,
                    ui_result = ?,
                    ui_cur_step = 5
                WHERE ui_id = ? AND ui_team_pkid = ?
            ");
            $updateStmt->bind_param(
                'iiiiiiii',
                $analysisId, $priorityPoints, $scoringLogic, $scoringBasis,
                $totalBasis, $taskResultDisplay, $simId, $_SESSION['team_id']
            );
            $updateStmt->execute();
            $updateStmt->close();

            header("Location: page-container.php?step=5&sim_id=" . $simId);
            exit;
        } catch (Exception $e) {
            $errors['database'] = 'Error: ' . $e->getMessage();
        }
    }
} else {
    // Load existing data
    $loadStmt = $conn->prepare("
        SELECT ui_analysis_id, ui_priority_points, ui_scoring_logic, ui_scoring_basis, ui_total_basis, ui_result
        FROM mg5_digisim_userinput 
        WHERE ui_id = ? AND ui_team_pkid = ?
    ");
    $loadStmt->bind_param('ii', $simId, $_SESSION['team_id']);
    $loadStmt->execute();
    $result = $loadStmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $analysisId = $row['ui_analysis_id']?? null;
        $priorityPoints = $row['ui_priority_points'];
        $scoringLogic = $row['ui_scoring_logic'];
        $scoringBasis = $row['ui_scoring_basis'];
        $totalBasis = $row['ui_total_basis'];
        $taskResultDisplay = $row['ui_result'];
    }
    $loadStmt->close();

    // for analysis
    $analysisName = '';

    if (!empty($analysisId)) {
        $analysisStmt = $conn->prepare("
            SELECT lg_name 
            FROM mg5_mdm_analysis 
            WHERE lg_id = ?
        ");
        $analysisStmt->bind_param('i', $analysisId);
        $analysisStmt->execute();
        $analysisResult = $analysisStmt->get_result();

        if ($analysisResult->num_rows > 0) {
            $analysisRow = $analysisResult->fetch_assoc();
            $analysisName = $analysisRow['lg_name'];
        }

        $analysisStmt->close();
    }
}
?>

<!-- Unified Page Layout -->
<div class="page-layout">
    <div class="page-content">
        <?php include 'stepper.php'; ?>

        <div class="content-container">
            
            <div class="page-header">
                <div>
                    <h1>Processing Configuration</h1>
                    <p>Refine how the simulation engine calculates performance metrics.</p>
                </div>
            </div>
            <?php if (!empty($analysisName)): ?>
                <div class="analysis-display" style="margin:10px 0; padding:10px; background:#f5f5f5; border-radius:6px;">
                    <strong>Analysis Type:</strong> <?= htmlspecialchars($analysisName) ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="configForm" class="pc-form">
                <div class="pc-grid">

                    <!-- Priority Points -->
                    <div class="pc-card">
                        <h3>Priority Points</h3>
                        <label class="pc-option <?= $priorityPoints == 1 ? 'active' : '' ?>">
                            <input type="radio" name="priority_points" value="expert" <?= $priorityPoints == 1 ? 'checked' : '' ?>>
                            <div><strong>Expert</strong><p>Automated weight calculation</p></div>
                        </label>
                        <label class="pc-option <?= $priorityPoints == 2 ? 'active' : '' ?>">
                            <input type="radio" name="priority_points" value="manual" <?= $priorityPoints == 2 ? 'checked' : '' ?>>
                            <div><strong>Manual</strong><p>Custom score assignment</p></div>
                        </label>
                    </div>

                    <!-- Scoring Logic -->
                    <div class="pc-card">
                        <h3>Scoring Logic</h3>
                        <label class="pc-option <?= $scoringLogic == 1 ? 'active' : '' ?>">
                            <input type="radio" name="scoring_logic" value="atleast" <?= $scoringLogic == 1 ? 'checked' : '' ?>>
                            <div><strong>Atleast</strong><p>Minimum threshold to pass</p></div>
                        </label>
                        <label class="pc-option <?= $scoringLogic == 2 ? 'active' : '' ?>">
                            <input type="radio" name="scoring_logic" value="actual" <?= $scoringLogic == 2 ? 'checked' : '' ?>>
                            <div><strong>Actual</strong><p>Exact score calculation</p></div>
                        </label>
                        <label class="pc-option <?= $scoringLogic == 3 ? 'active' : '' ?>">
                            <input type="radio" name="scoring_logic" value="absolute" <?= $scoringLogic == 3 ? 'checked' : '' ?>>
                            <div><strong>Absolute</strong><p>Fixed score threshold</p></div>
                        </label>
                    </div>

                    <!-- Scoring Basis -->
                    <div class="pc-card">
                        <h3>Scoring Basis</h3>
                        <label class="pc-option <?= $scoringBasis == 1 ? 'active' : '' ?>">
                            <input type="radio" name="scoring_basis" value="all" <?= $scoringBasis == 1 ? 'checked' : '' ?>>
                            <div><strong>All</strong><p>Based on all available tasks</p></div>
                        </label>
                        <label class="pc-option <?= $scoringBasis == 2 ? 'active' : '' ?>">
                            <input type="radio" name="scoring_basis" value="part" <?= $scoringBasis == 2 ? 'checked' : '' ?>>
                            <div><strong>Part</strong><p>Based on subset of tasks</p></div>
                        </label>
                        <label class="pc-option <?= $scoringBasis == 3 ? 'active' : '' ?>">
                            <input type="radio" name="scoring_basis" value="minimum" <?= $scoringBasis == 3 ? 'checked' : '' ?>>
                            <div><strong>Minimum</strong><p>Based on required tasks</p></div>
                        </label>
                    </div>

                    <!-- Total Basis -->
                    <div class="pc-card">
                        <h3>Total Basis</h3>
                        <label class="pc-option <?= $totalBasis == 1 ? 'active' : '' ?>">
                            <input type="radio" name="total_basis" value="all_tasks" <?= $totalBasis == 1 ? 'checked' : '' ?>>
                            <div><strong>All Tasks</strong><p>Include all tasks</p></div>
                        </label>
                        <label class="pc-option <?= $totalBasis == 2 ? 'active' : '' ?>">
                            <input type="radio" name="total_basis" value="marked_tasks" <?= $totalBasis == 2 ? 'checked' : '' ?>>
                            <div><strong>Marked Only</strong><p>Only flagged tasks</p></div>
                        </label>
                    </div>

                    <!-- ✅ Task Result Display - SINGLE SELECT -->
                    <div class="pc-card pc-wide">
                        <h3>Task Result Display</h3>
                        <p class="pc-hint">Select one display format</p>
                        <div class="pc-result">
                            <label class="pc-option small <?= $taskResultDisplay == 2 ? 'active' : '' ?>">
                                <input type="radio" name="task_result_display" value="percentage" <?= $taskResultDisplay == 2 ? 'checked' : '' ?>>
                                <div><strong>Percentage</strong><p>e.g. 85%</p></div>
                            </label>
                            <label class="pc-option small <?= $taskResultDisplay == 3 ? 'active' : '' ?>">
                                <input type="radio" name="task_result_display" value="raw_score" <?= $taskResultDisplay == 3 ? 'checked' : '' ?>>
                                <div><strong>Raw Score</strong><p>e.g. 42/50</p></div>
                            </label>
                            <label class="pc-option small <?= $taskResultDisplay == 4 ? 'active' : '' ?>">
                                <input type="radio" name="task_result_display" value="legend" <?= $taskResultDisplay == 4 ? 'checked' : '' ?>>
                                <div><strong>Legend</strong><p>Performance tiers</p></div>
                            </label>
                        </div>
                    </div>

                    <!-- analaysis card -->
                    <div class="pc-card">
                        <h3>Analysis Type</h3>

                        <?php foreach ($analysisList as $analysis): ?>
                            <label class="pc-option <?= ($analysisId == $analysis['lg_id']) ? 'active' : '' ?>">
                                <input 
                                    type="radio" 
                                    name="analysis_id" 
                                    value="<?= $analysis['lg_id'] ?>"
                                    <?= ($analysisId == $analysis['lg_id']) ? 'checked' : '' ?>
                                >
                                <div>
                                    <strong><?= htmlspecialchars($analysis['lg_name']) ?></strong>
                                </div>
                            </label>
                        <?php endforeach; ?>

                    </div>

                </div>

                <?php if (!empty($errors)): ?>
                    <div class="error-banner">
                        <?php foreach ($errors as $err): ?>
                            <p>⚠️ <?= htmlspecialchars($err) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Handle card selection styling for radio buttons
    document.querySelectorAll(".pc-option").forEach(card => {
        card.addEventListener("click", function(e) {
            if (e.target.tagName === 'INPUT') return;
            const input = this.querySelector("input");
            if (input.type === "radio") {
                document.querySelectorAll(`input[name="${input.name}"]`).forEach(radio => {
                    radio.closest(".pc-option")?.classList.remove("active");
                    radio.checked = false;
                });
                input.checked = true;
                this.classList.add("active");
            }
        });
    });

    // Handle direct input changes for accessibility
    document.querySelectorAll(".pc-option input[type='radio']").forEach(input => {
        input.addEventListener("change", function() {
            document.querySelectorAll(`input[name="${this.name}"]`).forEach(radio => {
                radio.closest(".pc-option")?.classList.remove("active");
            });
            if (this.checked) {
                this.closest(".pc-option")?.classList.add("active");
            }
        });
    });
});
</script>