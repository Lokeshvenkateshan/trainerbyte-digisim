<?php
// Set page title and CSS
$pageTitle = 'Processing Configuration';
$pageCSS = '/css/processing_configuration.css';



// Include database connection
require_once __DIR__ . '/../include/dataconnect.php';
$simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;

if ($simId <= 0) {
    header("Location: page-container.php?step=1");
    exit;
}


$errors = [];

// Initialize form data
$priorityPoints = $scoringLogic = $scoringBasis = $totalBasis = $taskResultDisplay = $thresholdValue = null;

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
            case 'atleast':
                $scoringLogic = 1;
                break;
            case 'actual':
                $scoringLogic = 2;
                break;
            case 'absolute':
                $scoringLogic = 3;
                break;
            default:
                $errors['scoring_logic'] = 'Invalid scoring logic selected';
        }
    } else {
        $errors['scoring_logic'] = 'Please select a scoring logic';
    }

    // Validate and get scoring basis
    if (isset($_POST['scoring_basis'])) {
        switch ($_POST['scoring_basis']) {
            case 'all':
                $scoringBasis = 1;
                break;
            case 'part':
                $scoringBasis = 2;
                break;
            case 'minimum':
                $scoringBasis = 3;
                break;
            default:
                $errors['scoring_basis'] = 'Invalid scoring basis selected';
        }
    } else {
        $errors['scoring_basis'] = 'Please select a scoring basis';
    }

    // Validate and get total basis
    if (isset($_POST['total_basis'])) {
        switch ($_POST['total_basis']) {
            case 'all_tasks':
                $totalBasis = 1;
                break;
            case 'marked_tasks':
                $totalBasis = 2;
                break;
            default:
                $errors['total_basis'] = 'Invalid total basis selected';
        }
    } else {
        $errors['total_basis'] = 'Please select a total basis';
    }

    // Validate and get task result display
    if (isset($_POST['task_result_display'])) {
        $taskResultDisplay = 1; // Default to NA

        if (in_array('percentage', $_POST['task_result_display'])) {
            $taskResultDisplay = 2; // Percentage
        } elseif (in_array('raw_score', $_POST['task_result_display'])) {
            $taskResultDisplay = 3; // Score
        } elseif (in_array('legend', $_POST['task_result_display'])) {
            $taskResultDisplay = 4; // Band
        }
    } else {
        $errors['task_result'] = 'Please select at least one result display option';
    }

    // If no errors, process form
    if (empty($errors)) {
        try {
            // Update the simulation
            $updateStmt = $conn->prepare("
    UPDATE mg5_digisim_userinput 
    SET 
        ui_priority_points = ?,
        ui_scoring_logic = ?,
        ui_scoring_basis = ?,
        ui_total_basis = ?,
        ui_result = ?,
        ui_cur_step = 5
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
            $updateStmt->close();

            // Redirect to next page     
            header("Location: page-container.php?step=6&sim_id=" . $simId);
            exit;
        } catch (Exception $e) {
            $errors['database'] = 'Error: ' . $e->getMessage();
        }
    }
} else {

    $loadStmt = $conn->prepare("
        SELECT 
            ui_priority_points, 
            ui_scoring_logic, 
            ui_scoring_basis, 
            ui_total_basis, 
            ui_result
        FROM mg5_digisim_userinput 
        WHERE ui_id = ? AND ui_team_pkid = ?
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

                <div class="option-card">
                    <input type="radio" id="priority_expert" name="priority_points" value="expert"
                        <?= $priorityPoints == 1 ? 'checked' : '' ?>>
                    <label for="priority_expert">
                        <h3>Expert</h3>
                        <p>Automated weight calculation based on preset expert patterns</p>
                    </label>
                </div>

                <div class="option-card">
                    <input type="radio" id="priority_manual" name="priority_points" value="manual"
                        <?= $priorityPoints == 2 ? 'checked' : '' ?>>
                    <label for="priority_manual">
                        <h3>Manual</h3>
                        <p>Custom score assignment for granular prioritization</p>
                    </label>
                    <!-- <div class="threshold-input" style="<?= $priorityPoints != 2 ? 'display:none;' : '' ?>">
                        <input type="number" id="threshold_value" name="threshold_value"
                            value="<?= $thresholdValue ?? 10 ?>" min="0" max="100">
                        <span>PTS</span>
                    </div> -->
                </div>
            </div>

            <!-- Scoring Logic -->
            <div class="config-section">
                <h2>Scoring Logic</h2>

                <div class="option-card">
                    <input type="radio" id="scoring_atleast" name="scoring_logic" value="atleast"
                        <?= $scoringLogic == 1 ? 'checked' : '' ?>>
                    <label for="scoring_atleast">
                        <h3>Atleast</h3>
                        <p>Minimum threshold to pass</p>
                    </label>
                </div>

                <div class="option-card">
                    <input type="radio" id="scoring_actual" name="scoring_logic" value="actual"
                        <?= $scoringLogic == 2 ? 'checked' : '' ?>>
                    <label for="scoring_actual">
                        <h3>Actual</h3>
                        <p>Exact score calculation</p>
                    </label>
                </div>

                <div class="option-card">
                    <input type="radio" id="scoring_absolute" name="scoring_logic" value="absolute"
                        <?= $scoringLogic == 3 ? 'checked' : '' ?>>
                    <label for="scoring_absolute">
                        <h3>Absolute</h3>
                        <p>Fixed score threshold</p>
                    </label>
                </div>
            </div>

            <!-- Scoring Basis -->
            <div class="config-section">
                <h2>Scoring Basis</h2>

                <div class="option-card">
                    <input type="radio" id="scoring_all" name="scoring_basis" value="all"
                        <?= $scoringBasis == 1 ? 'checked' : '' ?>>
                    <label for="scoring_all">
                        <h3>All</h3>
                        <p>Calculate score based on the entire set of available tasks</p>
                    </label>
                </div>

                <div class="option-card">
                    <input type="radio" id="scoring_part" name="scoring_basis" value="part"
                        <?= $scoringBasis == 2 ? 'checked' : '' ?>>
                    <label for="scoring_part">
                        <h3>Part</h3>
                        <p>Calculate score based on a subset of tasks</p>
                    </label>
                </div>

                <div class="option-card">
                    <input type="radio" id="scoring_minimum" name="scoring_basis" value="minimum"
                        <?= $scoringBasis == 3 ? 'checked' : '' ?>>
                    <label for="scoring_minimum">
                        <h3>Minimum</h3>
                        <p>Calculate score based on minimum required tasks</p>
                    </label>
                </div>

                <!-- <div class="threshold-slider">
                    <label>THRESHOLD VALUE</label>
                    <input type="range" id="threshold_slider" min="0" max="100" value="75">
                    <span class="slider-value">75%</span>
                </div> -->
            </div>

            <!-- Total Basis -->
            <div class="config-section">
                <h2>Total Basis</h2>

                <div class="option-card">
                    <input type="radio" id="total_all" name="total_basis" value="all_tasks"
                        <?= $totalBasis == 1 ? 'checked' : '' ?>>
                    <label for="total_all">
                        <h3>All Tasks</h3>
                        <p>Calculate score based on the entire set of available tasks</p>
                    </label>
                </div>

                <div class="option-card">
                    <input type="radio" id="total_marked" name="total_basis" value="marked_tasks"
                        <?= $totalBasis == 2 ? 'checked' : '' ?>>
                    <label for="total_marked">
                        <h3>Marked Tasks Only</h3>
                        <p>Only include tasks explicitly flagged for evaluation</p>
                    </label>
                </div>
            </div>

            <!-- Task Result Display -->
            <div class="config-section">
                <h2>Task Result Display</h2>
                <p>Select which data points will be visible to participants upon completion of the simulation.</p>

                <div class="result-options">
                    <div class="option-card">
                        <input type="checkbox" id="result_percentage" name="task_result_display[]" value="percentage"
                            <?= $taskResultDisplay == 2 ? 'checked' : '' ?>>
                        <label for="result_percentage">
                            <h3>Percentage</h3>
                            <p>e.g. 85%</p>
                        </label>
                    </div>

                    <div class="option-card">
                        <input type="checkbox" id="result_raw" name="task_result_display[]" value="raw_score"
                            <?= $taskResultDisplay == 3 ? 'checked' : '' ?>>
                        <label for="result_raw">
                            <h3>Raw Score</h3>
                            <p>e.g. 42/50</p>
                        </label>
                    </div>

                    <div class="option-card">
                        <input type="checkbox" id="result_legend" name="task_result_display[]" value="legend"
                            <?= $taskResultDisplay == 4 ? 'checked' : '' ?>>
                        <label for="result_legend">
                            <h3>Legend</h3>
                            <p>Performance tiers</p>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="page-container.php?step=4&sim_id=<?= $simId ?>" class="btn-secondary">Back</a>
            <button type="submit" class="btn-primary">Next</button>
        </div>
    </form>
</div>

<script>
    // Handle priority points selection
    document.addEventListener('DOMContentLoaded', function() {
        const priorityManual = document.getElementById('priority_manual');
        const thresholdInput = document.querySelector('.threshold-input');

        function toggleThresholdInput() {
            if (priorityManual.checked) {
                thresholdInput.style.display = 'block';
            } else {
                thresholdInput.style.display = 'none';
            }
        }

        // Initial check
        toggleThresholdInput();

        // Add event listener
        priorityManual.addEventListener('change', toggleThresholdInput);

        // Handle threshold slider
        const slider = document.getElementById('threshold_slider');
        const sliderValue = document.querySelector('.slider-value');

        slider.addEventListener('input', function() {
            sliderValue.textContent = this.value + '%';
        });

        // Prevent multiple checkboxes from being selected
        const resultCheckboxes = document.querySelectorAll('input[name="task_result_display[]"]');
        resultCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    resultCheckboxes.forEach(other => {
                        if (other !== this) other.checked = false;
                    });
                }
            });
        });
    });
</script>