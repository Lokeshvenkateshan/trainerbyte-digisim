<?php
$pageTitle = 'Review Simulation';
$pageCSS = '/css/review_simulation.css';

require_once __DIR__ . '/../include/dataconnect.php';

$simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;

if ($simId <= 0) {
    header("Location: page-container.php?step=1");
    exit;
}

$simulation = null;

$stmt = $conn->prepare("
    SELECT *
    FROM mg5_digisim_userinput
    WHERE ui_id = ? AND ui_team_pkid = ?
");

$stmt->bind_param('ii', $simId, $_SESSION['team_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: " . BASE_PATH . "/pages/simulation_setup.php");
    exit;
}

$simulation = $result->fetch_assoc();
$stmt->close();

/* Decode JSON fields */
$injects = !empty($simulation['ui_injects'])
    ? json_decode($simulation['ui_injects'], true)
    : [];

$scoreValues = !empty($simulation['ui_score_value'])
    ? json_decode($simulation['ui_score_value'], true)
    : [];

/* Get Score Scale Name */
$scaleName = '';
if (!empty($simulation['ui_score_scale'])) {
    $scaleStmt = $conn->prepare("SELECT st_name FROM mg5_scoretype WHERE st_id = ?");
    $scaleStmt->bind_param('i', $simulation['ui_score_scale']);
    $scaleStmt->execute();
    $scaleResult = $scaleStmt->get_result();
    if ($scaleResult->num_rows > 0) {
        $scaleName = $scaleResult->fetch_assoc()['st_name'];
    }
    $scaleStmt->close();
}


/* ================================
   PROCESSING CONFIGURATION LABEL MAPS
================================ */

$priorityMap = [
    1 => 'Expert',
    2 => 'Manual'
];

$scoringLogicMap = [
    1 => 'At Least',
    2 => 'Actual',
    3 => 'Absolute'
];

$scoringBasisMap = [
    1 => 'All',
    2 => 'Part',
    3 => 'Minimum'
];

$totalBasisMap = [
    1 => 'All Tasks',
    2 => 'Marked Tasks Only'
];

$resultDisplayMap = [
    2 => 'Percentage',
    3 => 'Raw Score',
    4 => 'Legend'
];

/* Get Safe Labels */
$priorityLabel      = $priorityMap[$simulation['ui_priority_points']] ?? 'Not Set';
$scoringLogicLabel  = $scoringLogicMap[$simulation['ui_scoring_logic']] ?? 'Not Set';
$scoringBasisLabel  = $scoringBasisMap[$simulation['ui_scoring_basis']] ?? 'Not Set';
$totalBasisLabel    = $totalBasisMap[$simulation['ui_total_basis']] ?? 'Not Set';
$resultDisplayLabel = $resultDisplayMap[$simulation['ui_result']] ?? 'Not Set';
?>

<div class="container">
    <h1>Review Simulation</h1>

    <!-- Basic Info -->
    <section>
        <h2>Basic Information</h2>
        <p><strong>Title:</strong> <?= htmlspecialchars($simulation['ui_sim_title']) ?></p>
        <p><strong>Industry:</strong> <?= htmlspecialchars($simulation['ui_industry_type']) ?></p>
        <p><strong>Geography:</strong> <?= htmlspecialchars($simulation['ui_geography']) ?></p>
        <p><strong>Operating Scale:</strong> <?= htmlspecialchars($simulation['ui_operating_scale']) ?></p>
        <p><strong>Language :</strong> <?= htmlspecialchars($simulation['ui_lang']) ?></p>
    </section>

    <!-- Scenario -->
    <section>
        <h2>Scenario</h2>
        <p><?= nl2br(htmlspecialchars($simulation['ui_scenario'])) ?></p>
    </section>

    <!-- Objectives -->
    <section>
        <h2>Objectives</h2>
        <p><?= nl2br(htmlspecialchars($simulation['ui_objective'])) ?></p>
    </section>

    <!-- Inject Distribution -->
    <section>
        <h2>Inject Distribution</h2>
        <?php if (!empty($injects)): ?>
            <ul>
                <?php foreach ($injects as $key => $value): ?>
                    <li><strong><?= ucfirst($key) ?>:</strong> <?= $value ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No inject data configured.</p>
        <?php endif; ?>
    </section>

    <!-- Score Scale -->
    <section>
        <h2>Score Scale</h2>
        <p><strong>Selected Scale:</strong> <?= htmlspecialchars($scaleName) ?></p>
    </section>

    <!-- Scale Components -->
    <section>
        <h2>Scale Component Values</h2>
        <?php if (!empty($scoreValues)): ?>
            <ul>
                <?php foreach ($scoreValues as $key => $value): ?>
                    <li><strong><?= htmlspecialchars($key) ?>:</strong> <?= $value ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No component values configured.</p>
        <?php endif; ?>
    </section>


    <section>
        <h2>Processing Configuration</h2>
        <p><strong>Priority Points:</strong> <?= $priorityLabel ?></p>
        <p><strong>Scoring Logic:</strong> <?= $scoringLogicLabel ?></p>
        <p><strong>Scoring Basis:</strong> <?= $scoringBasisLabel ?></p>
        <p><strong>Total Basis:</strong> <?= $totalBasisLabel ?></p>
        <p><strong>Result Display:</strong> <?= $resultDisplayLabel ?></p>
    </section>

    <div class="form-actions">
        <a href="page-container.php?step=5&sim_id=<?= $simId ?>" class="btn-secondary">Back</a>
        <!-- <a href="../test_generate.php?sim_id=<?= $simId ?>" class="btn-primary">Confirm & Finish</a> -->
        <form id="generateForm" method="POST" action="../test_generate.php?sim_id=<?= $simId ?>">
            <button type="submit" id="confirmBtn" class="btn-primary">
                Confirm & Finish
            </button>
        </form>
    </div>


</div>

<div id="processingOverlay" class="processing-overlay" style="display:none;">
    <div class="processing-modal">
        <div class="spinner"></div>
        <h3>Generating Simulation...</h3>
        <p>Please wait while we prepare your content.</p>
    </div>
</div>
<script>
    document.getElementById('generateForm').addEventListener('submit', function() {

        const overlay = document.getElementById('processingOverlay');
        const button = document.getElementById('confirmBtn');

        overlay.style.display = 'flex';
        button.disabled = true;
        button.innerText = 'Processing...';

    });
</script>