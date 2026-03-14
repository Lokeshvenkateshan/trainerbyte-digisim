<?php
// processing_configuration.php - Step 3: Processing Configuration
// NO <head> or <body> - handled by layout/header.php
// Uses mg5_ms_userinput_master table

$pageTitle = 'Processing Configuration';
$pageCSS = '/css/multistage/processing_configuration.css';

require_once __DIR__ . '/../include/dataconnect.php';

$simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;

if ($simId <= 0) {
    header("Location: multistagedigisim.php?step=1");
    exit;
}

$errors = [];

// Initialize form data
$priorityPoints = $scoringLogic = $scoringBasis = $totalBasis = $taskResultDisplay = null;
$thresholdValue = 10; // Default

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and get priority points
    if (isset($_POST['priority_points']) && $_POST['priority_points'] === 'expert') {
        $priorityPoints = 1;
    } elseif (isset($_POST['priority_points']) && $_POST['priority_points'] === 'manual') {
        $priorityPoints = 2;
        $thresholdValue = isset($_POST['threshold_value']) ? intval($_POST['threshold_value']) : 10;
    } else {
        $errors['priority'] = 'Please select a priority point option';
    }

    // Validate and get scoring logic
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

    // Validate and get scoring basis
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

    // Validate and get total basis
    if (isset($_POST['total_basis'])) {
        switch ($_POST['total_basis']) {
            case 'all_tasks': $totalBasis = 1; break;
            case 'marked_tasks': $totalBasis = 2; break;
            default: $errors['total_basis'] = 'Invalid total basis selected';
        }
    } else {
        $errors['total_basis'] = 'Please select a total basis';
    }

    // Validate and get task result display
    if (isset($_POST['task_result_display']) && is_array($_POST['task_result_display'])) {
        if (in_array('percentage', $_POST['task_result_display'])) {
            $taskResultDisplay = 2;
        } elseif (in_array('raw_score', $_POST['task_result_display'])) {
            $taskResultDisplay = 3;
        } elseif (in_array('legend', $_POST['task_result_display'])) {
            $taskResultDisplay = 4;
        } else {
            $taskResultDisplay = 1; // NA
        }
    } else {
        $taskResultDisplay = 1; // Default to NA if nothing selected
    }

    // If no errors, process form
    if (empty($errors)) {
        try {
            // Update the master simulation record
            $updateStmt = $conn->prepare("
                UPDATE mg5_ms_userinput_master 
                SET 
                    ui_priority_points = ?,
                    ui_scoring_logic = ?,
                    ui_scoring_basis = ?,
                    ui_total_basis = ?,
                    ui_result = ?,
                    ui_cur_stage = 3
                WHERE ui_id = ? AND ui_team_pkid = ?
            ");

            $updateStmt->bind_param(
                'iiiiiii',
                $priorityPoints,
                $scoringLogic,
                $scoringBasis,
                $totalBasis,
                $taskResultDisplay,
                $simId,
                $_SESSION['team_id']
            );

            $updateStmt->execute();
            
            if ($updateStmt->error) {
                throw new Exception('Database update failed: ' . $updateStmt->error);
            }
            
            $updateStmt->close();

            // Redirect to next page (review or success)
            header("Location: multistagedigisim.php?step=4&sim_id=" . $simId);
            exit;
            
        } catch (Exception $e) {
            error_log("Processing config error: " . $e->getMessage());
            $errors['database'] = 'Error: ' . $e->getMessage();
        }
    }
} else {
    // Load existing data when viewing the page
    $loadStmt = $conn->prepare("
        SELECT 
            ui_priority_points, 
            ui_scoring_logic, 
            ui_scoring_basis, 
            ui_total_basis, 
            ui_result
        FROM mg5_ms_userinput_master 
        WHERE ui_id = ? AND ui_team_pkid = ?
        LIMIT 1
    ");

    $loadStmt->bind_param('ii', $simId, $_SESSION['team_id']);
    $loadStmt->execute();
    $result = $loadStmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $priorityPoints = $row['ui_priority_points'];
        $scoringLogic = $row['ui_scoring_logic'];
        $scoringBasis = $row['ui_scoring_basis'];
        $totalBasis = $row['ui_total_basis'];
        $taskResultDisplay = $row['ui_result'];
    }

    $loadStmt->close();
}
?>

<div class="container">
    <div class="page-header">
        <h1>Processing Configuration</h1>
        <p>Refine how the simulation engine calculates performance metrics and awards priority points during runtime execution.</p>
    </div>

    <?php if (isset($errors['database'])): ?>
        <p class="error"><?= $errors['database'] ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="config-grid">
            
            <!-- Priority Points -->
            <div class="config-section">
                <h2>Priority Points</h2>

                <div class="option-card <?= $priorityPoints == 1 ? 'selected' : '' ?>">
                    <input type="radio" id="priority_expert" name="priority_points" value="expert"
                        <?= $priorityPoints == 1 ? 'checked' : '' ?>
                        onchange="updateCardSelection(this)">
                    <label for="priority_expert">
                        <h3>Expert</h3>
                        <p>Automated weight calculation based on preset expert patterns</p>
                    </label>
                </div>

                <div class="option-card <?= $priorityPoints == 2 ? 'selected' : '' ?>">
                    <input type="radio" id="priority_manual" name="priority_points" value="manual"
                        <?= $priorityPoints == 2 ? 'checked' : '' ?>
                        onchange="updateCardSelection(this)">
                    <label for="priority_manual">
                        <h3>Manual</h3>
                        <p>Custom score assignment for granular prioritization</p>
                    </label>
                    <div class="threshold-input" id="thresholdWrapper" style="<?= $priorityPoints != 2 ? 'display:none;' : '' ?>">
                        <label for="threshold_value">Threshold</label>
                        <input type="number" id="threshold_value" name="threshold_value"
                            value="<?= $thresholdValue ?? 10 ?>" min="0" max="100">
                        <span class="threshold-unit">points</span>
                    </div>
                </div>
                <?php if (isset($errors['priority'])): ?>
                    <p class="error"><?= $errors['priority'] ?></p>
                <?php endif; ?>
            </div>

            <!-- Scoring Logic -->
            <div class="config-section">
                <h2>Scoring Logic</h2>

                <div class="option-card <?= $scoringLogic == 1 ? 'selected' : '' ?>">
                    <input type="radio" id="scoring_atleast" name="scoring_logic" value="atleast"
                        <?= $scoringLogic == 1 ? 'checked' : '' ?>
                        onchange="updateCardSelection(this)">
                    <label for="scoring_atleast">
                        <h3>At Least</h3>
                        <p>Minimum threshold to pass</p>
                    </label>
                </div>

                <div class="option-card <?= $scoringLogic == 2 ? 'selected' : '' ?>">
                    <input type="radio" id="scoring_actual" name="scoring_logic" value="actual"
                        <?= $scoringLogic == 2 ? 'checked' : '' ?>
                        onchange="updateCardSelection(this)">
                    <label for="scoring_actual">
                        <h3>Actual</h3>
                        <p>Exact score calculation</p>
                    </label>
                </div>

                <div class="option-card <?= $scoringLogic == 3 ? 'selected' : '' ?>">
                    <input type="radio" id="scoring_absolute" name="scoring_logic" value="absolute"
                        <?= $scoringLogic == 3 ? 'checked' : '' ?>
                        onchange="updateCardSelection(this)">
                    <label for="scoring_absolute">
                        <h3>Absolute</h3>
                        <p>Fixed score threshold</p>
                    </label>
                </div>
                <?php if (isset($errors['scoring_logic'])): ?>
                    <p class="error"><?= $errors['scoring_logic'] ?></p>
                <?php endif; ?>
            </div>

            <!-- Scoring Basis -->
            <div class="config-section">
                <h2>Scoring Basis</h2>

                <div class="option-card <?= $scoringBasis == 1 ? 'selected' : '' ?>">
                    <input type="radio" id="scoring_all" name="scoring_basis" value="all"
                        <?= $scoringBasis == 1 ? 'checked' : '' ?>
                        onchange="updateCardSelection(this)">
                    <label for="scoring_all">
                        <h3>All</h3>
                        <p>Calculate score based on the entire set of available tasks</p>
                    </label>
                </div>

                <div class="option-card <?= $scoringBasis == 2 ? 'selected' : '' ?>">
                    <input type="radio" id="scoring_part" name="scoring_basis" value="part"
                        <?= $scoringBasis == 2 ? 'checked' : '' ?>
                        onchange="updateCardSelection(this)">
                    <label for="scoring_part">
                        <h3>Part</h3>
                        <p>Calculate score based on a subset of tasks</p>
                    </label>
                </div>

                <div class="option-card <?= $scoringBasis == 3 ? 'selected' : '' ?>">
                    <input type="radio" id="scoring_minimum" name="scoring_basis" value="minimum"
                        <?= $scoringBasis == 3 ? 'checked' : '' ?>
                        onchange="updateCardSelection(this)">
                    <label for="scoring_minimum">
                        <h3>Minimum</h3>
                        <p>Calculate score based on minimum required tasks</p>
                    </label>
                </div>
                <?php if (isset($errors['scoring_basis'])): ?>
                    <p class="error"><?= $errors['scoring_basis'] ?></p>
                <?php endif; ?>
            </div>

            <!-- Total Basis -->
            <div class="config-section">
                <h2>Total Basis</h2>

                <div class="option-card <?= $totalBasis == 1 ? 'selected' : '' ?>">
                    <input type="radio" id="total_all" name="total_basis" value="all_tasks"
                        <?= $totalBasis == 1 ? 'checked' : '' ?>
                        onchange="updateCardSelection(this)">
                    <label for="total_all">
                        <h3>All Tasks</h3>
                        <p>Calculate score based on the entire set of available tasks</p>
                    </label>
                </div>

                <div class="option-card <?= $totalBasis == 2 ? 'selected' : '' ?>">
                    <input type="radio" id="total_marked" name="total_basis" value="marked_tasks"
                        <?= $totalBasis == 2 ? 'checked' : '' ?>
                        onchange="updateCardSelection(this)">
                    <label for="total_marked">
                        <h3>Marked Tasks Only</h3>
                        <p>Only include tasks explicitly flagged for evaluation</p>
                    </label>
                </div>
                <?php if (isset($errors['total_basis'])): ?>
                    <p class="error"><?= $errors['total_basis'] ?></p>
                <?php endif; ?>
            </div>

            <!-- Task Result Display -->
            <div class="config-section">
                <h2>Task Result Display</h2>
                <p class="section-desc">Select which data points will be visible to participants upon completion of the simulation.</p>

                <div class="result-options">
                    <div class="option-card <?= $taskResultDisplay == 2 ? 'selected' : '' ?>">
                        <input type="checkbox" id="result_percentage" name="task_result_display[]" value="percentage"
                            <?= $taskResultDisplay == 2 ? 'checked' : '' ?>
                            onchange="updateResultSelection(this)">
                        <label for="result_percentage">
                            <h3>Percentage</h3>
                            <p>e.g. 85%</p>
                        </label>
                    </div>

                    <div class="option-card <?= $taskResultDisplay == 3 ? 'selected' : '' ?>">
                        <input type="checkbox" id="result_raw" name="task_result_display[]" value="raw_score"
                            <?= $taskResultDisplay == 3 ? 'checked' : '' ?>
                            onchange="updateResultSelection(this)">
                        <label for="result_raw">
                            <h3>Raw Score</h3>
                            <p>e.g. 42/50</p>
                        </label>
                    </div>

                    <div class="option-card <?= $taskResultDisplay == 4 ? 'selected' : '' ?>">
                        <input type="checkbox" id="result_legend" name="task_result_display[]" value="legend"
                            <?= $taskResultDisplay == 4 ? 'checked' : '' ?>
                            onchange="updateResultSelection(this)">
                        <label for="result_legend">
                            <h3>Legend</h3>
                            <p>Performance tiers</p>
                        </label>
                    </div>
                </div>
                <?php if (isset($errors['task_result'])): ?>
                    <p class="error"><?= $errors['task_result'] ?></p>
                <?php endif; ?>
            </div>

        </div>

        <div class="form-actions">
            <a href="multistagedigisim.php?step=2&sim_id=<?= $simId ?>" class="btn-secondary">Back</a>
            <button type="submit" class="btn-primary">Save & Continue</button>
        </div>
    </form>
</div>

<script>
// Update card selection styling for radio buttons
function updateCardSelection(el) {
    // Remove selected class from siblings
    const container = el.closest('.config-section');
    container.querySelectorAll('.option-card').forEach(card => {
        card.classList.remove('selected');
    });
    // Add selected class to parent card
    if (el.closest('.option-card')) {
        el.closest('.option-card').classList.add('selected');
    }
    
    // Show/hide threshold input for priority manual
    if (el.id === 'priority_manual') {
        document.getElementById('thresholdWrapper').style.display = 'block';
    } else if (el.id === 'priority_expert') {
        document.getElementById('thresholdWrapper').style.display = 'none';
    }
}

// Handle checkbox selection for result display (single select behavior)
function updateResultSelection(el) {
    if (el.checked) {
        // Uncheck all other result checkboxes
        document.querySelectorAll('input[name="task_result_display[]"]').forEach(cb => {
            if (cb !== el) cb.checked = false;
        });
        // Update card styling
        el.closest('.option-card').classList.add('selected');
        document.querySelectorAll('#result_percentage, #result_raw, #result_legend').forEach(cb => {
            if (cb !== el && cb.closest('.option-card')) {
                cb.closest('.option-card').classList.remove('selected');
            }
        });
    } else {
        // If unchecking, remove selected class
        if (el.closest('.option-card')) {
            el.closest('.option-card').classList.remove('selected');
        }
    }
}

// Initialize card selections on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set initial selected states based on checked inputs
    document.querySelectorAll('.config-section input[type="radio"]:checked').forEach(radio => {
        if (radio.closest('.option-card')) {
            radio.closest('.option-card').classList.add('selected');
        }
    });
    
    document.querySelectorAll('.result-options input[type="checkbox"]:checked').forEach(checkbox => {
        if (checkbox.closest('.option-card')) {
            checkbox.closest('.option-card').classList.add('selected');
        }
    });
    
    // Handle threshold input visibility on load
    const priorityManual = document.getElementById('priority_manual');
    const thresholdWrapper = document.getElementById('thresholdWrapper');
    if (thresholdWrapper) {
        thresholdWrapper.style.display = priorityManual?.checked ? 'block' : 'none';
    }
});
</script>