<?php
// Set page title and CSS
$pageTitle = 'Scale Components';
$pageCSS = '/css/scale_components.css';



// Include database connection
require_once __DIR__ . '/../include/dataconnect.php';
$simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;

if ($simId <= 0) {
    header("Location: page-container.php?step=1");
    exit;
}

$errors = [];

// Get the selected score scale
$selectedScaleId = null;
$scaleName = '';

// Get the selected scale
$checkStmt = $conn->prepare("SELECT ui_score_scale 
                            FROM mg5_digisim_userinput 
                            WHERE ui_id = ? AND ui_team_pkid = ?");
$checkStmt->bind_param('ii', $simId, $_SESSION['team_id']);

$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $selectedScaleId = $row['ui_score_scale'];
}
$checkStmt->close();

if ($selectedScaleId) {
    // Get scale name
    $scaleNameStmt = $conn->prepare("SELECT st_name FROM mg5_scoretype WHERE st_id = ?");
    $scaleNameStmt->bind_param('i', $selectedScaleId);
    $scaleNameStmt->execute();
    $scaleNameResult = $scaleNameStmt->get_result();

    if ($scaleNameResult->num_rows > 0) {
        $scaleNameRow = $scaleNameResult->fetch_assoc();
        $scaleName = $scaleNameRow['st_name'];
    }
    $scaleNameStmt->close();

    // Get scale components
    $components = [];
    $getComponentsStmt = $conn->prepare("
        SELECT stv_id, stv_sname, stv_name, stv_value, stv_color 
        FROM mg5_scoretype_value 
        WHERE stv_scoretype_pkid = ?
        ORDER BY stv_value
    ");
    $getComponentsStmt->bind_param('i', $selectedScaleId);
    $getComponentsStmt->execute();
    $componentsResult = $getComponentsStmt->get_result();

    while ($row = $componentsResult->fetch_assoc()) {
        $components[] = $row;
    }
    $getComponentsStmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scaleValues = [];
    $totalResponses = 0;

    // Process all scale components using stv_name as key
    foreach ($components as $component) {
        $value = isset($_POST['component_' . $component['stv_name']]) ?
            intval($_POST['component_' . $component['stv_name']]) : 0;

        $scaleValues[$component['stv_name']] = $value;
        $totalResponses += $value;
    }

    // Validate total responses
    if ($totalResponses <= 0) {
        $errors['total'] = 'Total responses must be greater than zero';
    } else {
        try {
            // Store the scale values as JSON with stv_name as key
            $scaleValuesJson = json_encode($scaleValues);

            // Update the simulation
            $updateStmt = $conn->prepare("
               UPDATE mg5_digisim_userinput 
                SET ui_score_value = ?,
                    ui_cur_step = 4
                WHERE ui_id = ? AND ui_team_pkid = ?


            ");

            $updateStmt->bind_param(
                'sii',
                $scaleValuesJson,
                $simId,
                $_SESSION['team_id']
            );

            $updateStmt->execute();
            $updateStmt->close();

            header("Location: page-container.php?step=5&sim_id=" . $simId);
            exit;
        } catch (Exception $e) {
            $errors['database'] = 'Error: ' . $e->getMessage();
        }
    }
}

// Load existing scale values if available
$existingValues = [];
if ($selectedScaleId) {
    $loadStmt = $conn->prepare("SELECT ui_score_value 
                                FROM mg5_digisim_userinput 
                                WHERE ui_id = ? AND ui_team_pkid = ?
                                ");
    $loadStmt->bind_param('ii', $simId, $_SESSION['team_id']);
    $loadStmt->execute();
    $result = $loadStmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (!empty($row['ui_score_value'])) {
            $existingValues = json_decode($row['ui_score_value'], true);
        }
    }
    $loadStmt->close();
}
?>

<div class="container">
    <div class="page-header">
        <h1>Scale Components</h1>
        <p>Configure the components for <?= htmlspecialchars($scaleName) ?></p>
        <div class="total-responses">Total responses: <span id="totalResponses"><?= array_sum($existingValues) ?></span></div>
    </div>

    <?php if (isset($errors['database'])): ?>
        <p class="error"><?= $errors['database'] ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="components-grid">
            <?php foreach ($components as $component): ?>
                <div class="component-card" style="border-left: 4px solid <?= htmlspecialchars($component['stv_color']) ?>">
                    <div class="component-header">
                        <h3><?= htmlspecialchars($component['stv_name']) ?></h3>
                        <p><?= $component['stv_value'] ?></p>
                    </div>
                    <div class="response-input">
                        <input type="number"
                            id="component_<?= htmlspecialchars($component['stv_name']) ?>"
                            name="component_<?= htmlspecialchars($component['stv_name']) ?>"
                            value="<?= isset($existingValues[$component['stv_name']]) ? $existingValues[$component['stv_name']] : 0 ?>"
                            min="0"
                            oninput="updateTotal()">
                        <label for="component_<?= htmlspecialchars($component['stv_name']) ?>">Number of Responses</label>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (isset($errors['total'])): ?>
            <p class="error"><?= $errors['total'] ?></p>
        <?php endif; ?>

        <div class="form-actions">
            <a href="page-container.php?step=3&sim_id=<?= $simId ?>" class="btn-secondary">Back</a>
            <button type="submit" class="btn-primary">Next</button> <!-- Changed from "Continue to Mapping" to "Next" -->
        </div>
    </form>
</div>

<script>
    function updateTotal() {
        let total = 0;
        document.querySelectorAll('.response-input input').forEach(input => {
            total += parseInt(input.value) || 0;
        });
        document.getElementById('totalResponses').textContent = total;
    }

    // Initialize total responses on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateTotal();
    });
</script>