<?php
// stage_builder.php - One stage at a time, deferred save, no emojis, #2563eb
$pageTitle = 'Stage configuration';
$pageCSS = '/css/multistage/stage_builder.css';
require_once __DIR__ . '/../include/dataconnect.php';

$simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;
$currentStage = isset($_GET['stage']) ? intval($_GET['stage']) : 1;

if ($simId <= 0) {
    header("Location: multistagedigisim.php?step=1");
    exit;
}

// Load master simulation data
$simData = null;
$stmt = $conn->prepare("SELECT * FROM mg5_ms_userinput_master WHERE ui_id = ? AND ui_team_pkid = ? LIMIT 1");
$stmt->bind_param("ii", $simId, $_SESSION['team_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $simData = $result->fetch_assoc();
}
$stmt->close();

if (!$simData) {
    echo "<p class='error'>Simulation not found. <a href='multistagedigisim.php?step=1'>Go back</a></p>";
    exit;
}

$noStages = $simData['ui_no_stages'];
if ($currentStage < 1) $currentStage = 1;
if ($currentStage > $noStages) $currentStage = $noStages;

// Fetch inject types for modals
$injectTypes = [];
$stmt = $conn->prepare("SELECT in_id, in_name, in_description FROM mg5_inject_master WHERE in_status = 1 ORDER BY in_name ASC");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $injectTypes[] = $row;
}
$stmt->close();

// Fetch score types for modals
$scoreTypes = [];
$stmt = $conn->prepare("SELECT st_id, st_name FROM mg5_scoretype ORDER BY st_name ASC");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $scoreTypes[] = $row;
}
$stmt->close();

// Load current stage data
$stageData = [
    'id' => null, 'name' => "Stage $currentStage", 'desc' => '', 'scenario' => '', 'objective' => '',
    'injects' => ['total' => 0], 'score_scale' => null, 'score_value' => []
];

$stmt = $conn->prepare("SELECT * FROM mg5_ms_stage_input WHERE st_userinput_pkid = ? AND st_stage_num = ? LIMIT 1");
$stmt->bind_param("ii", $simId, $currentStage);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $stageData = [
        'id' => $row['st_id'],
        'name' => $row['st_name'],
        'desc' => $row['st_desc'],
        'scenario' => $row['st_scenario'],
        'objective' => $row['st_objective'],
        'injects' => !empty($row['st_injects']) ? json_decode($row['st_injects'], true) : ['total' => 0],
        'score_scale' => $row['st_score_scale'],
        'score_value' => !empty($row['st_score_value']) ? json_decode($row['st_score_value'], true) : []
    ];
}
$stmt->close();

// Fetch scale name if selected
$scaleName = '';
if ($stageData['score_scale']) {
    $stmt = $conn->prepare("SELECT st_name FROM mg5_scoretype WHERE st_id = ?");
    $stmt->bind_param("i", $stageData['score_scale']);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $scaleName = $res->fetch_assoc()['st_name'];
    }
    $stmt->close();
}

// Fetch components for selected scale
$components = [];
if ($stageData['score_scale']) {
    $stmt = $conn->prepare("SELECT stv_id, stv_name, stv_value, stv_color FROM mg5_scoretype_value WHERE stv_scoretype_pkid = ? ORDER BY stv_value ASC");
    $stmt->bind_param("i", $stageData['score_scale']);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $components[] = $row;
    $stmt->close();
}

// ========== AJAX HANDLER - DEFERRED SAVE ==========
// ========== AJAX HANDLER - DEFERRED SAVE ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        // ===== NAVIGATE TO STAGE =====
        if ($_POST['action'] === 'navigate') {
            $targetStage = intval($_POST['target_stage'] ?? 1);
            if ($targetStage < 1) $targetStage = 1;
            if ($targetStage > $noStages) $targetStage = $noStages;
            echo json_encode(['success' => true, 'redirect' => 'multistagedigisim.php?step=2&sim_id=' . $simId . '&stage=' . $targetStage]);
            
        // ===== GET COMPONENTS (Read-only, for modal) =====
        } elseif ($_POST['action'] === 'get_components') {
            $scaleId = intval($_POST['scale_id'] ?? 0);
            $comps = [];
            if ($scaleId) {
                $stmt = $conn->prepare("SELECT stv_id, stv_name, stv_value, stv_color FROM mg5_scoretype_value WHERE stv_scoretype_pkid = ? ORDER BY stv_value ASC");
                $stmt->bind_param("i", $scaleId);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) $comps[] = $row;
                $stmt->close();
            }
            echo json_encode(['success' => true, 'components' => $comps]);
            
        // ===== SAVE ALL STAGE DATA (Deferred - called on final submit) =====
        } elseif ($_POST['action'] === 'save_stage_all') {
            $stageName = trim($_POST['stage_name'] ?? "Stage $currentStage");
            $stageDesc = trim($_POST['stage_desc'] ?? '');
            $scenario = $_POST['scenario'] ?? '';
            $objective = $_POST['objective'] ?? '';
            
            // Parse and validate injects JSON
            $injectsArray = json_decode($_POST['injects'] ?? '{}', true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid injects JSON: ' . json_last_error_msg());
            }
            if (!is_array($injectsArray) || ($injectsArray['total'] ?? 0) <= 0) {
                throw new Exception('Total injects must be greater than zero');
            }
            $injectsJson = json_encode($injectsArray);
            if ($injectsJson === false) {
                throw new Exception('Failed to encode injects: ' . json_last_error_msg());
            }
            
            // Parse and validate scoring JSON
            $scaleId = intval($_POST['score_scale'] ?? 0);
            $scaleValues = json_decode($_POST['score_values'] ?? '{}', true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid score values JSON: ' . json_last_error_msg());
            }
            if (!$scaleId || empty($scaleValues) || array_sum($scaleValues) <= 0) {
                throw new Exception('Please configure scoring with at least one response');
            }
            $scoreValuesJson = json_encode($scaleValues);
            if ($scoreValuesJson === false) {
                throw new Exception('Failed to encode score values: ' . json_last_error_msg());
            }
            
            // Debug logging (remove in production)
            // error_log("Saving stage: injects=$injectsJson, scores=$scoreValuesJson");
            
            if ($stageData['id']) {
                // Update existing stage - 9 params: 6 strings + 3 integers
                $stmt = $conn->prepare("UPDATE mg5_ms_stage_input SET 
                    st_name=?, st_desc=?, st_scenario=?, st_objective=?,
                    st_injects=?, st_score_scale=?, st_score_value=? 
                    WHERE st_id=? AND st_userinput_pkid=?");
                // Types: s-s-s-s-s-i-s-i-i = "ssssssisi"
                $stmt->bind_param("sssssisii", 
                    $stageName, $stageDesc, $scenario, $objective, 
                    $injectsJson, $scaleId, $scoreValuesJson, 
                    $stageData['id'], $simId);
                $stmt->execute();
                if ($stmt->error) {
                    throw new Exception('Database update failed: ' . $stmt->error);
                }
                $stmt->close();
            } else {
                // Insert new stage - 9 params
                $stmt = $conn->prepare("INSERT INTO mg5_ms_stage_input 
                    (st_userinput_pkid, st_stage_num, st_name, st_desc, st_scenario, st_objective, 
                     st_injects, st_score_scale, st_score_value) 
                    VALUES (?, ?,?,?,?,?,?,?,?)");
                // Types: i-i-s-s-s-s-s-i-s = "iisssssis"
                $stmt->bind_param("iisssssis", 
                    $simId, $currentStage, $stageName, $stageDesc, 
                    $scenario, $objective, $injectsJson, $scaleId, $scoreValuesJson);
                $stmt->execute();
                if ($stmt->error) {
                    throw new Exception('Database insert failed: ' . $stmt->error);
                }
                $stmt->close();
            }
            
            echo json_encode(['success' => true]);
            
        // ===== COMPLETE ALL STAGES =====
        } elseif ($_POST['action'] === 'complete') {
            $verifyStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM mg5_ms_stage_input 
                WHERE st_userinput_pkid = ? AND st_injects IS NOT NULL AND st_score_value IS NOT NULL AND st_score_value != '0'");
            $verifyStmt->bind_param("i", $simId);
            $verifyStmt->execute();
            $verifyRes = $verifyStmt->get_result();
            $verifyRow = $verifyRes->fetch_assoc();
            
            if ($verifyRow['cnt'] < $noStages) {
                throw new Exception("Please configure all $noStages stages before continuing");
            }
            $verifyStmt->close();
            
            $updMaster = $conn->prepare("UPDATE mg5_ms_userinput_master SET ui_cur_stage = 3 WHERE ui_id = ?");
            $updMaster->bind_param("i", $simId);
            $updMaster->execute();
            $updMaster->close();
            
            echo json_encode(['success' => true, 'redirect' => 'multistagedigisim.php?step=3&sim_id=' . $simId]);
        }
        
    } catch (Exception $e) {
        // Log error for debugging
        error_log("Stage builder error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

$isConfigured = !empty($stageData['injects']['total']) && !empty($stageData['score_scale']);
?>

<!-- Page Header with Simulation Summary -->
<div class="stage-header">
    <h1>Build Stage <?= $currentStage ?> of <?= $noStages ?></h1>
    <p>Configure each stage of your simulation</p>
    
    <!-- Progress Bar -->
    <div class="progress-bar">
        <?php for ($i = 1; $i <= $noStages; $i++): ?>
            <div class="progress-step <?= $i < $currentStage ? 'done' : ($i == $currentStage ? 'active' : '') ?>" 
                 data-stage="<?= $i ?>">
                <?= $i ?>
            </div>
        <?php endfor; ?>
    </div>
    
    <!-- Sim Summary (No repeated fields) -->
    <div class="sim-summary">
        <span><strong><?= htmlspecialchars($simData['ui_sim_title']) ?></strong></span>
        <span><?= htmlspecialchars($simData['ui_industry_type']) ?></span>
        <span><?= htmlspecialchars($simData['ui_geography'] ?: 'Global') ?></span>
        <span><?= htmlspecialchars($simData['ui_operating_scale'] ?: 'Standard') ?></span>
        <span><?= htmlspecialchars($simData['ui_lang'] ?? 'English') ?></span>
    </div>
</div>

<!-- Single Stage Card -->
<div class="stage-card <?= $isConfigured ? 'configured' : '' ?>" id="current-stage">
    
    <!-- Stage Header -->
    <div class="stage-header-card">
        <h3>Stage <?= $currentStage ?>: <span id="stage-name-display"><?= htmlspecialchars($stageData['name']) ?></span></h3>
        <span class="stage-badge <?= $isConfigured ? 'done' : 'pending' ?>" id="stage-badge">
            <?= $isConfigured ? 'Configured' : 'In Progress' ?>
        </span>
    </div>
    
    <!-- Stage Form -->
    <div class="stage-form-grid">
        <div>
            <div class="form-group">
                <label>Stage Name</label>
                <input type="text" id="stage_name" 
                       value="<?= htmlspecialchars($stageData['name']) ?>"
                       placeholder="e.g. Initial Assessment"
                       oninput="document.getElementById('stage-name-display').textContent = this.value || 'Stage <?= $currentStage ?>'">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="stage_desc" rows="3"
                          placeholder="Brief description of this stage..."><?= htmlspecialchars($stageData['desc']) ?></textarea>
            </div>
            <div class="form-group">
                <label>Scenario</label>
                <small>Recommended: 200-500 words</small>
                <textarea id="scenario" rows="5"
                          placeholder="Describe the situation, context, and challenges..."><?= htmlspecialchars($stageData['scenario']) ?></textarea>
            </div>
        </div>
        <div>
            <div class="form-group">
                <label>Objectives</label>
                <small>What should participants achieve?</small>
                <textarea id="objective" rows="5"
                          placeholder="Key learning outcomes and success criteria..."><?= htmlspecialchars($stageData['objective']) ?></textarea>
            </div>
            
            <!-- Injects Config Card (Popup) with Preview -->
            <div class="config-card <?= !empty($stageData['injects']['total']) ? 'configured' : '' ?>" 
                 onclick="openInjectModal()" id="inject-card">
                <div class="config-title">Inject Distribution</div>
                <div class="config-summary" id="inject-summary">
                    <?= !empty($stageData['injects']['total']) 
                        ? "Total: {$stageData['injects']['total']} injects" 
                        : "Click to configure" ?>
                </div>
                
                <!-- Preview of selected injects (non-zero values only) -->
                <?php if (!empty($stageData['injects']['total'])): ?>
                <div class="preview-list" id="inject-preview">
                    <?php foreach ($injectTypes as $type): 
                        $key = strtolower($type['in_name']);
                        if (!empty($stageData['injects'][$key])):
                    ?>
                        <div class="preview-item">
                            <span class="preview-label"><?= htmlspecialchars($type['in_name']) ?>:</span>
                            <span class="preview-value"><?= $stageData['injects'][$key] ?></span>
                        </div>
                    <?php endif; endforeach; ?>
                </div>
                <?php endif; ?>
                
                <span class="status <?= !empty($stageData['injects']['total'])?'status-done':'status-pending' ?>" id="inject-status">
                    <?= !empty($stageData['injects']['total'])?'Configured':'Not Set' ?>
                </span>
                <button type="button" class="config-btn">
                    <?= !empty($stageData['injects']['total'])?'Edit':'Configure' ?>
                </button>
            </div>
            
            <!-- Scoring Config Card (Popup) with Preview -->
            <div class="config-card <?= !empty($stageData['score_scale']) ? 'configured' : '' ?>" 
                 onclick="openScoreModal()" id="score-card">
                <div class="config-title">Scoring Setup</div>
                <div class="config-summary" id="score-summary">
                    <?= $scaleName ? "Scale: " . htmlspecialchars($scaleName) : "Click to configure" ?>
                </div>
                
                <!-- Preview of selected score components (non-zero values only) -->
                <?php if (!empty($stageData['score_value']) && array_sum($stageData['score_value']) > 0): ?>
                <div class="preview-list" id="score-preview">
                    <?php foreach ($components as $comp): 
                        if (!empty($stageData['score_value'][$comp['stv_name']])):
                    ?>
                        <div class="preview-item">
                            <span class="preview-label"><?= htmlspecialchars($comp['stv_name']) ?>:</span>
                            <span class="preview-value"><?= $stageData['score_value'][$comp['stv_name']] ?></span>
                        </div>
                    <?php endif; endforeach; ?>
                </div>
                <?php endif; ?>
                
                <span class="status <?= !empty($stageData['score_scale'])?'status-done':'status-pending' ?>" id="score-status">
                    <?= !empty($stageData['score_scale'])?'Configured':'Not Set' ?>
                </span>
                <button type="button" class="config-btn">
                    <?= !empty($stageData['score_scale'])?'Edit':'Configure' ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Stage Actions -->
    <div class="stage-actions">
        <?php if ($currentStage > 1): ?>
            <button type="button" class="btn-secondary" onclick="navigateToStage(<?= $currentStage - 1 ?>)">
                Previous Stage
            </button>
        <?php endif; ?>
        
        <button type="button" class="btn-primary" onclick="saveCurrentStage()">
            Save & <?= $currentStage < $noStages ? 'Next Stage' : 'Continue' ?>
        </button>
        
        <span class="success" id="stage-success" style="display:none; margin-left:10px;">Saved!</span>
    </div>
</div>

<!-- Final Complete Button (Only on Last Stage) -->
<?php if ($currentStage == $noStages): ?>
<div class="form-actions">
    <button type="button" class="btn-primary" id="completeBtn" onclick="completeAllStages()" style="width:100%; max-width:400px;">
        All Stages Complete - Continue to Next Step
    </button>
</div>
<?php endif; ?>

<!-- INJECT MODAL -->
<div id="injectModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeModal('injectModal')">&times;</span>
        <h3>Inject Distribution - Stage <?= $currentStage ?></h3>
        <p>Set the number of injects for each communication channel</p>
        
        <div class="total-row">
            <span>Total Injects:</span>
            <span id="inject-total-display"><?= $stageData['injects']['total'] ?></span>
        </div>
        
        <div class="channels-grid">
            <?php foreach ($injectTypes as $type): 
                $key = strtolower($type['in_name']);
            ?>
                <div class="channel-card">
                    <div class="channel-name"><?= htmlspecialchars($type['in_name']) ?></div>
                    <input type="number" class="channel-input" data-key="<?= $key ?>" 
                           value="<?= $stageData['injects'][$key] ?? 0 ?>" min="0" oninput="calcInjectTotal()">
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="error" id="inject-error" style="display:none"></div>
        
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeModal('injectModal')">Cancel</button>
            <button type="button" class="btn-primary" onclick="applyInjects()">Apply Changes</button>
        </div>
    </div>
</div>

<!-- SCORE MODAL -->
<div id="scoreModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeModal('scoreModal')">&times;</span>
        <h3>Scoring Setup - Stage <?= $currentStage ?></h3>
        
        <!-- Step 1: Select Scale -->
        <div id="scale-step">
            <p><strong>1. Choose a score scale</strong></p>
            <div class="search-box">
                <input type="text" id="scale-search" placeholder="Search scales..." oninput="filterScales(this.value)">
            </div>
            <div class="scale-grid" id="scale-grid">
                <?php foreach ($scoreTypes as $st): ?>
                    <label class="scale-option <?= $stageData['score_scale']==$st['st_id']?'selected':'' ?>">
                        <input type="radio" name="score_scale" value="<?= $st['st_id'] ?>" 
                               <?= $stageData['score_scale']==$st['st_id']?'checked':'' ?>
                               onchange="pickScale(this.value)">
                        <div>
                            <h4><?= htmlspecialchars($st['st_name']) ?></h4>
                            <p>Evaluation criteria</p>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('scoreModal')">Cancel</button>
                <button type="button" class="btn-primary" id="scale-next-btn" onclick="goToComponents()" disabled>
                    Next: Set Components
                </button>
            </div>
        </div>
        
        <!-- Step 2: Configure Components -->
        <div id="component-step" style="display:<?= $stageData['score_scale']?'block':'none' ?>">
            <p><strong>2. Set responses for <em id="picked-scale-name"><?= htmlspecialchars($scaleName) ?></em></strong></p>
            
            <div class="total-row">
                <span>Total Responses:</span>
                <span id="component-total-display"><?= array_sum($stageData['score_value']) ?></span>
            </div>
            
            <div class="components-grid" id="components-grid">
                <?php foreach ($components as $comp): ?>
                    <div class="component-card" style="border-left-color:#2563eb">
                        <div class="component-header">
                            <strong><?= htmlspecialchars($comp['stv_name']) ?></strong>
                            <small>Value: <?= $comp['stv_value'] ?></small>
                        </div>
                        <div class="component-input">
                            <input type="number" data-comp="<?= htmlspecialchars($comp['stv_name']) ?>"
                                   value="<?= $stageData['score_value'][$comp['stv_name']] ?? 0 ?>" min="0" 
                                   oninput="calcComponentTotal()">
                            <label>responses</label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="error" id="component-error" style="display:none"></div>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="goBackToScales()">Change Scale</button>
                <button type="button" class="btn-primary" onclick="applyComponents()">Apply Changes</button>
            </div>
        </div>
    </div>
</div>

<script>
// ===== GLOBAL STATE (Client-side only until final save) =====
let simId = <?= $simId ?>;
let currentStage = <?= $currentStage ?>;
let noStages = <?= $noStages ?>;
let components = <?= json_encode($components) ?>;
let scaleValues = <?= json_encode($stageData['score_value']) ?>;
let injectValues = <?= json_encode($stageData['injects']) ?>;
let selectedScaleId = <?= $stageData['score_scale'] ? $stageData['score_scale'] : 'null' ?>;
let selectedScaleName = <?= $scaleName ? "'".addslashes($scaleName)."'" : 'null' ?>;

// ===== MODAL FUNCTIONS =====
function openModal(id) { const m = document.getElementById(id); if(m) m.classList.add('active'); }
function closeModal(id) { const m = document.getElementById(id); if(m) m.classList.remove('active'); }

document.querySelectorAll('.modal').forEach(m => {
    m.addEventListener('click', e => { if(e.target===m) closeModal(m.id); });
});
document.addEventListener('keydown', e => {
    if(e.key==='Escape') document.querySelectorAll('.modal.active').forEach(m => closeModal(m.id));
});

// ===== SAVE CURRENT STAGE (DEFERRED - ALL DATA AT ONCE) =====
function saveCurrentStage() {
    // Validate basic info
    const stageName = document.getElementById('stage_name')?.value.trim();
    if(!stageName) return alert('Enter a stage name');
    
    // Validate injects
    if(!injectValues || injectValues['total'] <= 0) {
        return alert('Configure injects before continuing');
    }
    
    // Validate scoring
    if(!selectedScaleId || !scaleValues || Object.values(scaleValues).reduce((a,b)=>a+b,0) <= 0) {
        return alert('Configure scoring before continuing');
    }
    
    const btn = document.querySelector('.stage-actions .btn-primary');
    if(btn) { btn.disabled = true; btn.textContent = 'Saving...'; }
    
    // Prepare ALL data for single submit
    const data = new FormData();
    data.append('action', 'save_stage_all');
    data.append('sim_id', simId);
    data.append('stage_name', document.getElementById('stage_name').value);
    data.append('stage_desc', document.getElementById('stage_desc').value);
    data.append('scenario', document.getElementById('scenario').value);
    data.append('objective', document.getElementById('objective').value);
    data.append('injects', JSON.stringify(injectValues));
    data.append('score_scale', selectedScaleId);
    data.append('score_values', JSON.stringify(scaleValues));
    
    fetch('', { method:'POST', body:data })
        .then(r=>r.json())
        .then(res => {
            if(res.success) {
                const success = document.getElementById('stage-success');
                if(success) {
                    success.style.display = 'inline';
                    setTimeout(()=>success.style.display='none', 1500);
                }
                updateStageBadge();
                
                // Navigate after short delay
                setTimeout(() => {
                    if (currentStage < noStages) {
                        navigateToStage(currentStage + 1);
                    } else if (noStages > 1) {
                        const completeBtn = document.getElementById('completeBtn');
                        if(completeBtn) completeBtn.style.display = 'block';
                    }
                }, 800);
            } else {
                alert('Error: '+res.error);
                if(btn) { btn.disabled = false; btn.textContent = 'Save & Next Stage'; }
            }
        })
        .catch(e => {
            alert('Network error: '+e);
            if(btn) { btn.disabled = false; btn.textContent = 'Save & Next Stage'; }
        });
}

// ===== NAVIGATE BETWEEN STAGES =====
function navigateToStage(target) {
    if(target < 1 || target > noStages) return;
    
    const data = new FormData();
    data.append('action', 'navigate');
    data.append('sim_id', simId);
    data.append('target_stage', target);
    
    fetch('', { method:'POST', body:data })
        .then(r=>r.json())
        .then(res => {
            if(res.success && res.redirect) {
                window.location.href = res.redirect;
            }
        });
}

// ===== UPDATE STAGE BADGE =====
function updateStageBadge() {
    const injectCard = document.getElementById('inject-card');
    const scoreCard = document.getElementById('score-card');
    const badge = document.getElementById('stage-badge');
    const stageCard = document.getElementById('current-stage');
    
    const injectDone = injectCard?.classList.contains('configured');
    const scoreDone = scoreCard?.classList.contains('configured');
    
    if(injectDone && scoreDone) {
        if(badge) { badge.textContent = 'Configured'; badge.className = 'stage-badge done'; }
        if(stageCard) stageCard.classList.add('configured');
    } else {
        if(badge) { badge.textContent = 'In Progress'; badge.className = 'stage-badge pending'; }
        if(stageCard) stageCard.classList.remove('configured');
    }
    
    document.querySelectorAll('.progress-step').forEach((step, idx) => {
        const stepNum = idx + 1;
        if(stepNum < currentStage) {
            step.className = 'progress-step done';
        } else if(stepNum == currentStage) {
            step.className = 'progress-step active';
        } else {
            step.className = 'progress-step';
        }
    });
}

// ===== INJECTS - CLIENT SIDE ONLY (No DB save until final) =====
function openInjectModal() {
    document.querySelectorAll('.channel-input').forEach(inp => {
        const key = inp.dataset.key;
        inp.value = injectValues[key] || 0;
    });
    calcInjectTotal();
    openModal('injectModal');
}

function calcInjectTotal() {
    let total = 0;
    document.querySelectorAll('.channel-input').forEach(inp => {
        total += parseInt(inp.value) || 0;
    });
    const el = document.getElementById('inject-total-display');
    if(el) el.textContent = total;
}

function applyInjects() {
    // Update LOCAL state only - NO database save
    document.querySelectorAll('.channel-input').forEach(inp => {
        injectValues[inp.dataset.key] = parseInt(inp.value) || 0;
    });
    injectValues['total'] = parseInt(document.getElementById('inject-total-display').textContent) || 0;
    
    // Update preview in card
    updateInjectPreview();
    
    // Update card status
    const card = document.getElementById('inject-card');
    const summary = document.getElementById('inject-summary');
    const status = document.getElementById('inject-status');
    const btn = card?.querySelector('.config-btn');
    
    if(injectValues['total'] > 0) {
        if(card) card.classList.add('configured');
        if(summary) summary.textContent = `Total: ${injectValues['total']} injects`;
        if(status) { status.textContent = 'Configured'; status.className = 'status status-done'; }
        if(btn) btn.textContent = 'Edit';
    }
    
    closeModal('injectModal');
}

function updateInjectPreview() {
    const preview = document.getElementById('inject-preview');
    if(!preview) return;
    
    let html = '';
    <?php foreach ($injectTypes as $type): 
        $key = strtolower($type['in_name']);
    ?>
    if(injectValues['<?= $key ?>'] > 0) {
        html += `<div class="preview-item"><span class="preview-label"><?= htmlspecialchars($type['in_name']) ?>:</span><span class="preview-value">${injectValues['<?= $key ?>']}</span></div>`;
    }
    <?php endforeach; ?>
    
    if(html) {
        preview.innerHTML = html;
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
}

// ===== SCORES - CLIENT SIDE ONLY (No DB save until final) =====
function openScoreModal() {
    document.getElementById('scale-step').style.display = 'block';
    document.getElementById('component-step').style.display = 'none';
    document.getElementById('scale-next-btn').disabled = !document.querySelector('input[name="score_scale"]:checked');
    
    <?php if ($stageData['score_scale']): ?>
        const radio = document.querySelector(`input[name="score_scale"][value="<?= $stageData['score_scale'] ?>"]`);
        if(radio) {
            radio.checked = true;
            radio.closest('.scale-option')?.classList.add('selected');
            document.getElementById('scale-next-btn').disabled = false;
        }
    <?php endif; ?>
    
    openModal('scoreModal');
}

function filterScales(term) {
    term = term.toLowerCase();
    document.querySelectorAll('.scale-option').forEach(opt => {
        opt.style.display = opt.textContent.toLowerCase().includes(term) ? 'block' : 'none';
    });
}

function pickScale(value) {
    document.querySelectorAll('.scale-option').forEach(o => o.classList.remove('selected'));
    const target = event.target.closest('.scale-option');
    if(target) target.classList.add('selected');
    document.getElementById('scale-next-btn').disabled = false;
}

function goToComponents() {
    const selected = document.querySelector('input[name="score_scale"]:checked');
    if(!selected) return;
    
    selectedScaleId = parseInt(selected.value);
    selectedScaleName = selected.closest('.scale-option').querySelector('h4').textContent;
    
    document.getElementById('picked-scale-name').textContent = selectedScaleName;
    document.getElementById('score-summary').textContent = `Scale: ${selectedScaleName}`;
    
    // Fetch components (read-only)
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_components&scale_id=' + selectedScaleId
    })
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            components = res.components;
            scaleValues = {};
            const grid = document.getElementById('components-grid');
            if(grid) {
                grid.innerHTML = '';
                res.components.forEach(c => {
                    const val = <?= json_encode($stageData['score_value']) ?>[c.stv_name] || 0;
                    scaleValues[c.stv_name] = val;
                    grid.innerHTML += `
                        <div class="component-card" style="border-left-color:#2563eb">
                            <div class="component-header">
                                <strong>${c.stv_name}</strong>
                                <small>Value: ${c.stv_value}</small>
                            </div>
                            <div class="component-input">
                                <input type="number" data-comp="${c.stv_name}" 
                                       value="${val}" min="0" oninput="calcComponentTotal()">
                                <label>responses</label>
                            </div>
                        </div>`;
                });
            }
            
            document.getElementById('scale-step').style.display = 'none';
            document.getElementById('component-step').style.display = 'block';
            calcComponentTotal();
        } else {
            alert('Error: '+res.error);
        }
    });
}

function goBackToScales() {
    document.getElementById('component-step').style.display = 'none';
    document.getElementById('scale-step').style.display = 'block';
}

function calcComponentTotal() {
    let total = 0;
    document.querySelectorAll('#component-step .component-input input').forEach(inp => {
        const val = parseInt(inp.value) || 0;
        total += val;
        scaleValues[inp.dataset.comp] = val;
    });
    const el = document.getElementById('component-total-display');
    if(el) el.textContent = total;
}

function applyComponents() {
    // Update LOCAL state only - NO database save
    const total = parseInt(document.getElementById('component-total-display').textContent) || 0;
    
    // Update preview
    updateScorePreview();
    
    // Update card status
    const card = document.getElementById('score-card');
    const summary = document.getElementById('score-summary');
    const status = document.getElementById('score-status');
    const btn = card?.querySelector('.config-btn');
    
    if(selectedScaleId && total > 0) {
        if(card) card.classList.add('configured');
        if(summary) summary.textContent = `Scale: ${selectedScaleName}`;
        if(status) { status.textContent = 'Configured'; status.className = 'status status-done'; }
        if(btn) btn.textContent = 'Edit';
    }
    
    closeModal('scoreModal');
}

function updateScorePreview() {
    const preview = document.getElementById('score-preview');
    if(!preview) return;
    
    let html = '';
    <?php foreach ($components as $comp): ?>
    if(scaleValues['<?= addslashes($comp['stv_name']) ?>'] > 0) {
        html += `<div class="preview-item"><span class="preview-label"><?= htmlspecialchars($comp['stv_name']) ?>:</span><span class="preview-value">${scaleValues['<?= addslashes($comp['stv_name']) ?>']}</span></div>`;
    }
    <?php endforeach; ?>
    
    if(html) {
        preview.innerHTML = html;
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
}

// ===== COMPLETE ALL STAGES =====
function completeAllStages() {
    const btn = document.getElementById('completeBtn');
    if(btn) { btn.disabled = true; btn.textContent = 'Processing...'; }
    
    const data = new FormData();
    data.append('action', 'complete');
    data.append('sim_id', simId);
    
    fetch('', { method:'POST', body:data })
        .then(r=>r.json())
        .then(res => {
            if(res.success && res.redirect) {
                window.location.href = 'multistagedigisim.php?step=3&sim_id=' + simId;
            } else {
                alert(res.error || 'All stages saved!');
                if(res.redirect) window.location.href = res.redirect;
            }
        })
        .catch(e => {
            alert('Error: '+e);
            if(btn) { btn.disabled = false; btn.textContent = 'All Stages Complete - Continue to Next Step'; }
        });
}

// Init
document.addEventListener('DOMContentLoaded', () => {
    updateStageBadge();
    calcInjectTotal();
    calcComponentTotal();
    updateInjectPreview();
    updateScorePreview();
});
</script>