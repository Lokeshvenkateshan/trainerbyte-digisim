<?php
// Set page title and CSS
$simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;

if ($simId <= 0) {
    header("Location: page-container.php?step=1");
    exit;
}

$pageTitle = 'Configure Inject Distribution';
$pageCSS = '/css/inject_distribution.css';



// Include database connection
require_once __DIR__ . '/../include/dataconnect.php';
$injectTypes = [];

$injectStmt = $conn->prepare("
    SELECT in_id, in_name, in_description
    FROM mg5_inject_master
    WHERE in_status = 1
    ORDER BY in_id ASC
");

$injectStmt->execute();
$result = $injectStmt->get_result();

while ($row = $result->fetch_assoc()) {
    $injectTypes[] = $row;
}

$injectStmt->close();




// Initialize form data
$injectsData = [];

foreach ($injectTypes as $type) {
    $key = strtolower($type['in_name']);
    $injectsData[$key] = 0;
}

$injectsData['total'] = 0;



$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $total = 0;
    $injectsArray = [];

    foreach ($injectTypes as $type) {

        $key = strtolower($type['in_name']);
        $value = isset($_POST[$key]) ? intval($_POST[$key]) : 0;

        $injectsArray[$key] = $value;
        $total += $value;
    }

    if ($total <= 0) {
        $errors['total'] = 'Total injects must be greater than zero';
    }

    if (empty($errors)) {

        $injectsArray['total'] = $total;
        $injectsJson = json_encode($injectsArray);

        $updateStmt = $conn->prepare("
            UPDATE mg5_digisim_userinput
            SET ui_injects = ?,
                ui_cur_step = 2
            WHERE ui_id = ? AND ui_team_pkid = ?

        ");

        $updateStmt->bind_param('sii', $injectsJson, $simId, $_SESSION['team_id']);
        $updateStmt->execute();
        $updateStmt->close();

        header("Location: page-container.php?step=3&sim_id=" . $simId);
        exit;
    }
} else {
    // Load existing data if available
    $loadStmt = $conn->prepare("SELECT ui_injects FROM mg5_digisim_userinput WHERE ui_id = ?");
    $loadStmt->bind_param('i', $simId);
    $loadStmt->execute();
    $result = $loadStmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (!empty($row['ui_injects'])) {
            $existingData = json_decode($row['ui_injects'], true);

            if (is_array($existingData)) {
                $injectsData = array_merge($injectsData, $existingData);
            }
        }
    }
    $loadStmt->close();
}
?>

<div class="container">
    <div class="modal-header">
        <h2>Configure Inject Distribution</h2>
        <p>Define the volume for each communication channel in the simulation.</p>
    </div>

    <form method="POST" action="">
        <div class="modal-content">
            <div class="total-injects">
                <div class="info-box">
                    <h3>Total Injects Required</h3>
                    <p>The baseline target for this scenario</p>
                </div>
                <input type="number" id="total" name="total"
                    value="<?= $injectsData['total'] ?>"
                    readonly>
            </div>

            <div class="channels-grid">

                <?php foreach ($injectTypes as $type):
                    $key = strtolower($type['in_name']);
                ?>

                    <div class="channel-card">
                        <div class="channel-icon">📌</div>
                        <div class="channel-name"><?= htmlspecialchars($type['in_name']) ?></div>
                        <input type="number"
                            name="<?= $key ?>"
                            value="<?= $injectsData[$key] ?? 0 ?>"
                            min="0"
                            class="channel-input">
                    </div>

                <?php endforeach; ?>

            </div>


            <?php if (isset($errors['total'])): ?>
                <p class="error"><?= $errors['total'] ?></p>
            <?php endif; ?>
        </div>

        <div class="modal-footer">
            <a href="page-container.php?step=1&sim_id=<?= $simId ?>" class="btn-secondary">Back</a>
            <button type="submit" class="btn-primary">Next</button> <!-- Changed from "Apply" to "Next" -->
        </div>
    </form>
</div>

<script>
    // Update total when any input changes
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('.channel-input');
        const totalInput = document.getElementById('total');

        function calculateTotal() {
            let total = 0;
            inputs.forEach(input => {
                const value = parseInt(input.value) || 0;
                total += value;
            });
            totalInput.value = total;
        }

        // Add event listeners to all inputs
        inputs.forEach(input => {
            input.addEventListener('input', calculateTotal);
        });

        // Calculate initial total
        calculateTotal();
    });
</script>