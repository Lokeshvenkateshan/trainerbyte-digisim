<?php
// Set page title and CSS
$pageTitle = 'Select Score Scale';
$pageCSS = '/css/score_scale.css';



// Include database connection
require_once __DIR__ . '/../include/dataconnect.php';

$simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;

if ($simId <= 0) {
    header("Location: page-container.php?step=1");
    exit;
}




$errors = [];

// Get current scale selection
$selectedScaleId = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['selected_scale'])) {
        $errors['scale'] = 'Please select a score scale';
    } else {
        $selectedScaleId = intval($_POST['selected_scale']);

        try {
            // Update the simulation with selected scale
            $updateStmt = $conn->prepare("
                UPDATE mg5_digisim_userinput 
                SET ui_score_scale = ?,
                    ui_cur_step = 3
                WHERE ui_id = ? AND ui_team_pkid = ?



            ");

            $updateStmt->bind_param(
                'iii',
                $selectedScaleId,
                $simId,
                $_SESSION['team_id']
            );

            $updateStmt->execute();
            $updateStmt->close();

            // Redirect to scale components page
            header("Location: page-container.php?step=4&sim_id=" . $simId);
            exit;
        } catch (Exception $e) {
            $errors['database'] = 'Error: ' . $e->getMessage();
        }
    }
} else {
    // If we're loading the page, check if there's already a selected scale
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
}

// Get all score types (removed st_description since it doesn't exist)
$scoreTypes = [];
$getScoreTypesStmt = $conn->prepare("SELECT st_id, st_name FROM mg5_scoretype");
$getScoreTypesStmt->execute();
$scoreTypesResult = $getScoreTypesStmt->get_result();

while ($row = $scoreTypesResult->fetch_assoc()) {
    $scoreTypes[] = $row;
}
$getScoreTypesStmt->close();
?>

<div class="container">
    <div class="page-header">
        <h1>Select Score Scale</h1>
        <p>Choose a measurement logic for your evaluation</p>
    </div>

    <?php if (isset($errors['database'])): ?>
        <p class="error"><?= $errors['database'] ?></p>
    <?php endif; ?>

    <div class="search-bar">
        <input type="text" placeholder="Filter scales (e.g. 1-5, binary)..." id="scaleSearch">
    </div>

    <form method="POST" action="">
        <div class="scale-grid">
            <?php foreach ($scoreTypes as $scoreType): ?>
                <div class="scale-card <?= $selectedScaleId == $scoreType['st_id'] ? 'selected' : '' ?>">
                    <input type="radio" id="scale-<?= $scoreType['st_id'] ?>"
                        name="selected_scale" value="<?= $scoreType['st_id'] ?>"
                        <?= $selectedScaleId == $scoreType['st_id'] ? 'checked' : '' ?>>
                    <label for="scale-<?= $scoreType['st_id'] ?>">
                        <h3><?= htmlspecialchars($scoreType['st_name']) ?></h3>
                        <p class="scale-desc">Configure evaluation criteria</p>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (isset($errors['scale'])): ?>
            <p class="error"><?= $errors['scale'] ?></p>
        <?php endif; ?>

        <div class="form-actions">
            <a href="page-container.php?step=2&sim_id=<?= $simId ?>" class="btn-secondary">Back</a>
                <button type="submit" class="btn-primary">Next</button>
        </div>
    </form>
</div>

<script>
    // Search functionality
    document.getElementById('scaleSearch').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const scaleCards = document.querySelectorAll('.scale-card');

        scaleCards.forEach(card => {
            const cardText = card.textContent.toLowerCase();
            if (cardText.includes(searchTerm)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });

    // Radio button selection
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.scale-card').forEach(card => {
                card.classList.remove('selected');
            });
            this.closest('.scale-card').classList.add('selected');
        });
    });
</script>