<?php
// review_simulation.php - Step 4: Review all simulation settings
// NO <head> or <body> - handled by layout/header.php
// Uses mg5_ms_userinput_master and mg5_ms_stage_input tables

$pageTitle = 'Review Simulation';
$pageCSS = '/css/multistage/review_simulation.css';

require_once __DIR__ . '/../include/dataconnect.php';

$simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;

if ($simId <= 0) {
    header("Location: multistagedigisim.php?step=1");
    exit;
}

// Load master simulation data
$simulation = null;
$stmt = $conn->prepare("SELECT * FROM mg5_ms_userinput_master WHERE ui_id = ? AND ui_team_pkid = ? LIMIT 1");
$stmt->bind_param('ii', $simId, $_SESSION['team_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: multistagedigisim.php?step=1");
    exit;
}
$simulation = $result->fetch_assoc();
$stmt->close();

$noStages = $simulation['ui_no_stages'] ?? 1;

// Load all stage data
$stages = [];
$stmt = $conn->prepare("SELECT * FROM mg5_ms_stage_input WHERE st_userinput_pkid = ? ORDER BY st_stage_num ASC");
$stmt->bind_param('i', $simId);
$stmt->execute();
$stagesResult = $stmt->get_result();
while ($row = $stagesResult->fetch_assoc()) {
    $stageNum = $row['st_stage_num'];
    $stages[$stageNum] = [
        'id' => $row['st_id'],
        'name' => $row['st_name'],
        'desc' => $row['st_desc'],
        'scenario' => $row['st_scenario'],
        'objective' => $row['st_objective'],
        'injects' => !empty($row['st_injects']) ? json_decode($row['st_injects'], true) : [],
        'score_scale' => $row['st_score_scale'],
        'score_value' => !empty($row['st_score_value']) ? json_decode($row['st_score_value'], true) : []
    ];
}
$stmt->close();

// Processing configuration label maps
$priorityMap = [1 => 'Expert', 2 => 'Manual'];
$scoringLogicMap = [1 => 'At Least', 2 => 'Actual', 3 => 'Absolute'];
$scoringBasisMap = [1 => 'All', 2 => 'Part', 3 => 'Minimum'];
$totalBasisMap = [1 => 'All Tasks', 2 => 'Marked Tasks Only'];
$resultDisplayMap = [1 => 'None', 2 => 'Percentage', 3 => 'Raw Score', 4 => 'Legend'];

$priorityLabel = $priorityMap[$simulation['ui_priority_points']] ?? 'Not Set';
$scoringLogicLabel = $scoringLogicMap[$simulation['ui_scoring_logic']] ?? 'Not Set';
$scoringBasisLabel = $scoringBasisMap[$simulation['ui_scoring_basis']] ?? 'Not Set';
$totalBasisLabel = $totalBasisMap[$simulation['ui_total_basis']] ?? 'Not Set';
$resultDisplayLabel = $resultDisplayMap[$simulation['ui_result']] ?? 'Not Set';
?>

<div class="container">
    <div class="page-header">
        <h1>Review Simulation</h1>
        <p>Confirm your simulation configuration before finalizing</p>
    </div>

    <!-- Simulation Summary Card -->
    <div class="summary-card">
        <div class="summary-grid">
            <div class="summary-item">
                <span class="summary-label">Title</span>
                <span class="summary-value"><?= htmlspecialchars($simulation['ui_sim_title']) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Industry</span>
                <span class="summary-value"><?= htmlspecialchars($simulation['ui_industry_type']) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Geography</span>
                <span class="summary-value"><?= htmlspecialchars($simulation['ui_geography'] ?: 'Global') ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Stages</span>
                <span class="summary-value"><?= $noStages ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Language</span>
                <span class="summary-value"><?= htmlspecialchars($simulation['ui_lang'] ?? 'English') ?></span>
            </div>
        </div>
    </div>

    <!-- Basic Information Section -->
    <section class="review-section">
        <h2>Basic Information</h2>
        <div class="info-grid">
            <div class="info-item">
                <label>Simulation Title</label>
                <p><?= htmlspecialchars($simulation['ui_sim_title']) ?></p>
            </div>
            <div class="info-item">
                <label>Description</label>
                <p><?= nl2br(htmlspecialchars($simulation['ui_sim_desc'])) ?: '<em class="text-muted">Not provided</em>' ?></p>
            </div>
            <div class="info-item">
                <label>Industry Type</label>
                <p><?= htmlspecialchars($simulation['ui_industry_type']) ?></p>
            </div>
            <div class="info-item">
                <label>Geography</label>
                <p><?= htmlspecialchars($simulation['ui_geography'] ?: 'Global') ?></p>
            </div>
            <div class="info-item">
                <label>Operating Scale</label>
                <p><?= htmlspecialchars($simulation['ui_operating_scale'] ?: 'Standard') ?></p>
            </div>
            <div class="info-item">
                <label>Language</label>
                <p><?= htmlspecialchars($simulation['ui_lang'] ?? 'English') ?></p>
            </div>
        </div>
    </section>

    <!-- Stages Review - EACH STAGE WITH DESC, SCENARIO, OBJECTIVE -->
    <section class="review-section">
        <h2>Stages Configuration</h2>
        <p class="section-desc">Review each stage's description, scenario, objectives, injects and scoring setup</p>
        
        <div class="stages-review">
            <?php for ($i = 1; $i <= $noStages; $i++): 
                $stage = $stages[$i] ?? null;
            ?>
            <div class="stage-review-card">
                <div class="stage-review-header">
                    <h3>Stage <?= $i ?>: <?= htmlspecialchars($stage['name'] ?? "Stage $i") ?></h3>
                    <span class="stage-status configured">Configured</span>
                </div>
                
                <div class="stage-review-content">
                    <?php if ($stage): ?>
                        
                        <!-- Stage Description -->
                        <div class="stage-detail">
                            <label>Description</label>
                            <div class="text-block">
                                <?= nl2br(htmlspecialchars($stage['desc'])) ?: '<em class="text-muted">Not provided</em>' ?>
                            </div>
                        </div>
                        
                        <!-- Stage Scenario -->
                        <div class="stage-detail">
                            <label>Scenario</label>
                            <div class="text-block">
                                <?= nl2br(htmlspecialchars($stage['scenario'])) ?: '<em class="text-muted">Not provided</em>' ?>
                            </div>
                        </div>
                        
                        <!-- Stage Objectives -->
                        <div class="stage-detail">
                            <label>Objectives</label>
                            <div class="text-block">
                                <?= nl2br(htmlspecialchars($stage['objective'])) ?: '<em class="text-muted">Not provided</em>' ?>
                            </div>
                        </div>
                        
                        <!-- Stage Injects -->
                        <div class="stage-detail">
                            <label>Inject Distribution</label>
                            <?php if (!empty($stage['injects']) && ($stage['injects']['total'] ?? 0) > 0): ?>
                                <div class="inject-preview">
                                    <span class="inject-total">Total: <?= $stage['injects']['total'] ?> injects</span>
                                    <div class="inject-list">
                                        <?php foreach ($stage['injects'] as $key => $val): 
                                            if ($key !== 'total' && $val > 0):
                                        ?>
                                            <span class="inject-item"><?= ucfirst(str_replace('_', ' ', $key)) ?>: <?= $val ?></span>
                                        <?php endif; endforeach; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No injects configured</p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Stage Scoring -->
                        <div class="stage-detail">
                            <label>Scoring Setup</label>
                            <?php if (!empty($stage['score_scale'])): 
                                // Get scale name for this stage
                                $stageScaleName = '';
                                $sStmt = $conn->prepare("SELECT st_name FROM mg5_scoretype WHERE st_id = ?");
                                $sStmt->bind_param('i', $stage['score_scale']);
                                $sStmt->execute();
                                $sRes = $sStmt->get_result();
                                if ($sRes->num_rows > 0) {
                                    $stageScaleName = $sRes->fetch_assoc()['st_name'];
                                }
                                $sStmt->close();
                            ?>
                                <p><strong>Scale:</strong> <?= htmlspecialchars($stageScaleName) ?></p>
                                <?php if (!empty($stage['score_value']) && array_sum($stage['score_value']) > 0): ?>
                                    <div class="score-preview">
                                        <?php foreach ($stage['score_value'] as $key => $val): if ($val > 0): ?>
                                            <span class="score-item"><?= htmlspecialchars($key) ?>: <?= $val ?></span>
                                        <?php endif; endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No score values configured</p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="text-muted">No scoring configured</p>
                            <?php endif; ?>
                        </div>
                        
                    <?php else: ?>
                        <p class="text-muted">Stage not configured</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endfor; ?>
        </div>
    </section>

    <!-- Processing Configuration -->
    <section class="review-section">
        <h2>Processing Configuration</h2>
        <div class="config-review-grid">
            <div class="config-review-item">
                <label>Priority Points</label>
                <p><?= $priorityLabel ?></p>
            </div>
            <div class="config-review-item">
                <label>Scoring Logic</label>
                <p><?= $scoringLogicLabel ?></p>
            </div>
            <div class="config-review-item">
                <label>Scoring Basis</label>
                <p><?= $scoringBasisLabel ?></p>
            </div>
            <div class="config-review-item">
                <label>Total Basis</label>
                <p><?= $totalBasisLabel ?></p>
            </div>
            <div class="config-review-item">
                <label>Result Display</label>
                <p><?= $resultDisplayLabel ?></p>
            </div>
        </div>
    </section>

    <!-- Final Actions -->
    <div class="form-actions">
        <a href="multistagedigisim.php?step=3&sim_id=<?= $simId ?>" class="btn-secondary">Edit Configuration</a>
        <form id="generateForm" method="POST" action="ms_generate.php?sim_id=<?= $simId ?>" style="display:inline;">
            <button type="submit" id="confirmBtn" class="btn-primary">
                Confirm & Generate Simulation
            </button>
        </form>
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
// Show processing overlay on form submit
document.getElementById('generateForm')?.addEventListener('submit', function(e) {
    const overlay = document.getElementById('processingOverlay');
    const button = document.getElementById('confirmBtn');
    
    if (overlay && button) {
        overlay.style.display = 'flex';
        button.disabled = true;
        button.textContent = 'Processing...';
    }
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>