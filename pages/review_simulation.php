<?php
$pageTitle = 'Review Simulation';
$pageCSS = '/pages/page-styles/review_simulation.css';

require_once __DIR__ . '/../include/dataconnect.php';

$simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;

if ($simId <= 0) {
    header("Location: page-container.php?step=1");
    exit;
}

$simulation = null;

/* $stmt = $conn->prepare("
    SELECT *
    FROM mg5_digisim_userinput
    WHERE ui_id=? AND ui_team_pkid=?
"); */
$stmt = $conn->prepare("
    SELECT u.*, a.lg_name AS analysis_name
    FROM mg5_digisim_userinput u
    LEFT JOIN mg5_mdm_analysis a 
        ON u.ui_analysis_id = a.lg_id
    WHERE u.ui_id=? AND u.ui_team_pkid=?
");
$stmt->bind_param('ii', $simId, $_SESSION['team_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: page-container.php?step=1");
    exit;
}

$simulation = $result->fetch_assoc();
$stmt->close();


$analysisName = $simulation['analysis_name'] ?? '';
$injects = !empty($simulation['ui_injects']) ? json_decode($simulation['ui_injects'], true) : [];
$scoreValues = !empty($simulation['ui_score_value']) ? json_decode($simulation['ui_score_value'], true) : [];

$scaleName = '';
if (!empty($simulation['ui_score_scale'])) {
    $scaleStmt = $conn->prepare("SELECT st_name FROM mg5_scoretype WHERE st_id=?");
    $scaleStmt->bind_param('i', $simulation['ui_score_scale']);
    $scaleStmt->execute();
    $res = $scaleStmt->get_result();
    if ($res->num_rows > 0) {
        $scaleName = $res->fetch_assoc()['st_name'];
    }
    $scaleStmt->close();
}

$priorityMap = [1 => 'Expert', 2 => 'Manual'];
$scoringLogicMap = [1 => 'At Least', 2 => 'Actual', 3 => 'Absolute'];
$scoringBasisMap = [1 => 'All', 2 => 'Part', 3 => 'Minimum'];
$totalBasisMap = [1 => 'All Tasks', 2 => 'Marked Tasks Only'];
$resultDisplayMap = [2 => 'Percentage', 3 => 'Raw Score', 4 => 'Legend'];

$priorityLabel = $priorityMap[$simulation['ui_priority_points']] ?? 'Not Set';
$scoringLogicLabel = $scoringLogicMap[$simulation['ui_scoring_logic']] ?? 'Not Set';
$scoringBasisLabel = $scoringBasisMap[$simulation['ui_scoring_basis']] ?? 'Not Set';
$totalBasisLabel = $totalBasisMap[$simulation['ui_total_basis']] ?? 'Not Set';
$resultDisplayLabel = $resultDisplayMap[$simulation['ui_result']] ?? 'Not Set';
?>

<!-- ✅ Unified Page Layout -->
<div class="page-layout">
    <div class="page-content">
        <?php include 'stepper.php'; ?>

        <div class="content-container">

            <div class="page-header">
                <div>
                    <h1>Review & Summary</h1>
                    <p>Please review your simulation configuration before generating content.</p>
                </div>
            </div>

            <div class="review-grid">

                <!-- LEFT: Simulation Context -->
                <div class="review-sidebar">
                    <div class="review-card">
                        <div class="card-title">Simulation Context</div>

                        <div class="context-group">
                            <span>Title</span>
                            <p><?= htmlspecialchars($simulation['ui_sim_title']) ?></p>
                        </div>

                        <div class="context-row">
                            <div>
                                <span>Industry</span>
                                <p><?= htmlspecialchars($simulation['ui_industry_type']) ?></p>
                            </div>
                            <div>
                                <span>Geography</span>
                                <p><?= htmlspecialchars($simulation['ui_geography']) ?></p>
                            </div>
                        </div>

                        <div class="context-row">
                            <div>
                                <span>Scale</span>
                                <p><?= htmlspecialchars($simulation['ui_operating_scale']) ?></p>
                            </div>
                            <div>
                                <span>Language</span>
                                <p><?= htmlspecialchars($simulation['ui_lang']) ?></p>
                            </div>
                        </div>

                        <div class="context-group">
                            <span>Scenario</span>
                            <p><?= nl2br(htmlspecialchars($simulation['ui_scenario'])) ?></p>
                        </div>

                        <div class="context-group">
                            <span>Objective</span>
                            <p><?= nl2br(htmlspecialchars($simulation['ui_objective'])) ?></p>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Configuration Summary -->
                <div class="review-main">

                    <div class="review-card">
                        <div class="card-title">Configuration Summary</div>

                        <div class="summary-row">
                            <div class="summary-block">
                                <div class="summary-label">Injects</div>
                                <div class="inject-chips">
                                    <?php foreach ($injects as $k => $v): if ($k != "total"): ?>
                                            <div class="chip"><?= ucfirst($k) ?>: <?= $v ?></div>
                                    <?php endif;
                                    endforeach; ?>
                                </div>
                            </div>

                            <div class="summary-block">
                                <div class="summary-label">Response Scale</div>
                                <div class="scale-name"><?= htmlspecialchars($scaleName) ?></div>
                                <div class="scale-values">
                                    <?php foreach ($scoreValues as $label => $count): ?>
                                        <div class="scale-chip"><?= ucfirst($label) ?>: <?= $count ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="review-card">
                        <div class="card-title">Processing Settings</div>

                        <div class="processing-grid">
                            <div class="process-item">
                                <span>Priority Points</span>
                                <p><?= $priorityLabel ?></p>
                            </div>
                            <div class="process-item">
                                <span>Scoring Logic</span>
                                <p><?= $scoringLogicLabel ?></p>
                            </div>
                            <div class="process-item">
                                <span>Scoring Basis</span>
                                <p><?= $scoringBasisLabel ?></p>
                            </div>
                            <div class="process-item">
                                <span>Total Basis</span>
                                <p><?= $totalBasisLabel ?></p>
                            </div>
                            <div class="process-item">
                                <span>Analysis Type</span>
                                <p><?= htmlspecialchars($analysisName ?: 'Not Set') ?></p>
                            </div>
                        </div>

                        <div class="result-display">
                            <span>Task Result Display</span>
                            <div class="result-badge"><?= $resultDisplayLabel ?></div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>


</div>

<!-- Processing Overlay -->
<div id="processingOverlay" class="processing-overlay">
    <div class="processing-modal">
        <div class="spinner"></div>
        <h3>Generating Simulation...</h3>
        <p>Please wait while we prepare your content.</p>
    </div>
</div>

<script>
    document.getElementById('generateForm').addEventListener('submit', function() {

        document.getElementById('processingOverlay').style.display = 'flex';

        const btn = document.getElementById('generateBtn');
        btn.disabled = true;
        btn.innerText = "Generating...";

    });
</script>