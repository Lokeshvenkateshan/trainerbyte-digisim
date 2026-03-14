<?php
// Set page title and CSS
$pageTitle = 'Processing Configuration';
$pageCSS = '//pages/page-styles/processing_configuration.css';



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
            header("Location: page-container.php?step=5&sim_id=" . $simId);
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

    <div class="pc-header">
        <h1>Processing Configuration</h1>
        <p>Refine how the simulation engine calculates performance metrics and awards priority points during runtime execution.</p>
    </div>

    <form method="POST">

        <div class="pc-grid">

            <div class="pc-card">
                <h3>Priority Points</h3>

                <label class="pc-option <?= $priorityPoints == 1 ? 'active' : '' ?>">
                    <input type="radio" name="priority_points" value="expert" <?= $priorityPoints == 1 ? 'checked' : '' ?>>
                    <div>
                        <strong>Expert</strong>
                        <p>Automated weight calculation based on preset expert patterns</p>
                    </div>
                </label>

                <label class="pc-option <?= $priorityPoints == 2 ? 'active' : '' ?>">
                    <input type="radio" name="priority_points" value="manual" <?= $priorityPoints == 2 ? 'checked' : '' ?>>
                    <div>
                        <strong>Manual</strong>
                        <p>Custom score assignment for granular prioritization</p>
                    </div>
                </label>

            </div>


            <div class="pc-card">
                <h3>Scoring Logic</h3>

                <label class="pc-option <?= $scoringLogic == 1 ? 'active' : '' ?>">
                    <input type="radio" name="scoring_logic" value="atleast" <?= $scoringLogic == 1 ? 'checked' : '' ?>>
                    <div>
                        <strong>Atleast</strong>
                        <p>Minimum threshold to pass</p>
                    </div>
                </label>

                <label class="pc-option <?= $scoringLogic == 2 ? 'active' : '' ?>">
                    <input type="radio" name="scoring_logic" value="actual" <?= $scoringLogic == 2 ? 'checked' : '' ?>>
                    <div>
                        <strong>Actual</strong>
                        <p>Exact score calculation</p>
                    </div>
                </label>

                <label class="pc-option <?= $scoringLogic == 3 ? 'active' : '' ?>">
                    <input type="radio" name="scoring_logic" value="absolute" <?= $scoringLogic == 3 ? 'checked' : '' ?>>
                    <div>
                        <strong>Absolute</strong>
                        <p>Fixed score threshold</p>
                    </div>
                </label>

            </div>


            <div class="pc-card">
                <h3>Scoring Basis</h3>

                <label class="pc-option <?= $scoringBasis == 1 ? 'active' : '' ?>">
                    <input type="radio" name="scoring_basis" value="all" <?= $scoringBasis == 1 ? 'checked' : '' ?>>
                    <div>
                        <strong>All</strong>
                        <p>Calculate score based on the entire set of available tasks</p>
                    </div>
                </label>

                <label class="pc-option <?= $scoringBasis == 2 ? 'active' : '' ?>">
                    <input type="radio" name="scoring_basis" value="part" <?= $scoringBasis == 2 ? 'checked' : '' ?>>
                    <div>
                        <strong>Part</strong>
                        <p>Calculate score based on a subset of tasks</p>
                    </div>
                </label>

                <label class="pc-option <?= $scoringBasis == 3 ? 'active' : '' ?>">
                    <input type="radio" name="scoring_basis" value="minimum" <?= $scoringBasis == 3 ? 'checked' : '' ?>>
                    <div>
                        <strong>Minimum</strong>
                        <p>Calculate score based on minimum required tasks</p>
                    </div>
                </label>

            </div>


            <div class="pc-card">
                <h3>Total Basis</h3>

                <label class="pc-option <?= $totalBasis == 1 ? 'active' : '' ?>">
                    <input type="radio" name="total_basis" value="all_tasks" <?= $totalBasis == 1 ? 'checked' : '' ?>>
                    <div>
                        <strong>All Tasks</strong>
                        <p>Calculate score based on the entire set of available tasks</p>
                    </div>
                </label>

                <label class="pc-option <?= $totalBasis == 2 ? 'active' : '' ?>">
                    <input type="radio" name="total_basis" value="marked_tasks" <?= $totalBasis == 2 ? 'checked' : '' ?>>
                    <div>
                        <strong>Marked Tasks Only</strong>
                        <p>Only include tasks explicitly flagged for evaluation</p>
                    </div>
                </label>

            </div>


            <div class="pc-card pc-wide">

                <h3>Task Result Display</h3>

                <div class="pc-result">

                    <label class="pc-option small <?= $taskResultDisplay == 2 ? 'active' : '' ?>">
                        <input type="checkbox" name="task_result_display[]" value="percentage" <?= $taskResultDisplay == 2 ? 'checked' : '' ?>>
                        <div>
                            <strong>Percentage</strong>
                            <p>e.g. 85%</p>
                        </div>
                    </label>

                    <label class="pc-option small <?= $taskResultDisplay == 3 ? 'active' : '' ?>">
                        <input type="checkbox" name="task_result_display[]" value="raw_score" <?= $taskResultDisplay == 3 ? 'checked' : '' ?>>
                        <div>
                            <strong>Raw Score</strong>
                            <p>e.g. 42/50</p>
                        </div>
                    </label>

                    <label class="pc-option small <?= $taskResultDisplay == 4 ? 'active' : '' ?>">
                        <input type="checkbox" name="task_result_display[]" value="legend" <?= $taskResultDisplay == 4 ? 'checked' : '' ?>>
                        <div>
                            <strong>Legend</strong>
                            <p>Performance tiers</p>
                        </div>
                    </label>

                </div>

            </div>

        </div>

        <div class="form-actions">
            <a href="page-container.php?step=3&sim_id=<?= $simId ?>" class="btn-secondary">Back</a>
            <button type="submit" class="btn-primary">Next</button>
        </div>

    </form>
</div>

<script>
    document.querySelectorAll(".pc-option").forEach(card => {

        card.addEventListener("click", () => {

            const input = card.querySelector("input");

            if (input.type === "radio") {
                document.querySelectorAll(`input[name="${input.name}"]`).forEach(r => {
                    r.closest(".pc-option").classList.remove("active");
                });
                input.checked = true;
                card.classList.add("active");
            }

            if (input.type === "checkbox") {
                input.checked = !input.checked;
                card.classList.toggle("active", input.checked);
            }

        });

    });
</script>