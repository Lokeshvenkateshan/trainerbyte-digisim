<?php
// Set page title and CSS
$simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;

if ($simId <= 0) {
    header("Location: page-container.php?step=1");
    exit;
}

$pageTitle = 'Configure Inject Distribution';
$pageCSS = '/pages/page-styles/inject_distribution.css';



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

<div class="inject-wrapper">

    <?php include 'stepper.php'; ?>

    <div class="inject-content">

        <div class="inject-main">

            <div class="inject-header">

                <div>
                    <h2>Configure Injects</h2>
                    <p>Define the count of injects for the given inject types (channels).</p>
                </div>

                <div class="total-card">
                    <span>Total Injects</span>
                    <strong id="totalDisplay"><?= $injectsData['total'] ?></strong>
                    <input type="hidden" id="total" name="total" value="<?= $injectsData['total'] ?>">
                </div>

            </div>


            <form method="POST" id="injectForm">

                <div class="channels-grid">

                    <?php foreach ($injectTypes as $type):

                        $key = strtolower($type['in_name']);
                        $value = $injectsData[$key] ?? 0;

                    ?>

                        <div class="channel-card">

                            <div class="channel-left">

                                <div class="icon">📩</div>

                                <div class="channel-text">
                                    <h4><?= htmlspecialchars($type['in_name']) ?></h4>
                                    <p><?= htmlspecialchars($type['in_description'] ?? '') ?></p>
                                </div>

                            </div>


                            <div class="counter">

                                <button type="button" class="minus">−</button>

                                <input
                                    type="number"
                                    class="channel-input"
                                    name="<?= $key ?>"
                                    value="<?= $value ?>"
                                    min="0">

                                <button type="button" class="plus">+</button>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

                <?php if (isset($errors['total'])): ?>
                    <p class="error"><?= $errors['total'] ?></p>
                <?php endif; ?>

            </form>

        </div>




    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const inputs = document.querySelectorAll('.channel-input');
        const totalInput = document.getElementById('total');
        const totalDisplay = document.getElementById('totalDisplay');

        function updateTotal() {

            let total = 0;

            inputs.forEach(input => {
                total += parseInt(input.value) || 0;
            });

            totalInput.value = total;
            totalDisplay.textContent = total;

        }

        inputs.forEach(input => {
            input.addEventListener('input', updateTotal);
        });


        document.querySelectorAll('.plus').forEach(btn => {
            btn.addEventListener('click', function() {

                let input = this.parentElement.querySelector('input');
                input.value = parseInt(input.value || 0) + 1;

                updateTotal();

            });
        });


        document.querySelectorAll('.minus').forEach(btn => {
            btn.addEventListener('click', function() {

                let input = this.parentElement.querySelector('input');
                let value = parseInt(input.value || 0);

                if (value > 0) {
                    input.value = value - 1;
                }

                updateTotal();

            });
        });

        updateTotal();

    });
</script>