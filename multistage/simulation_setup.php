<?php
// simulation_setup.php - Step 1: Basic simulation info
// NO <head> or <body> - handled by layout/header.php

$pageTitle = 'Multi Stage Simulation Setup';
$pageCSS = '/pages/page-styles/simulation_setup.css';

require_once __DIR__ . '/../include/dataconnect.php';

$simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;

// Initialize variables
$simTitle = $simDesc = $industryType = $geography = $operatingScale = '';
$noStages = '';
$language = 'English'; // Default
$errors = [];

// Load existing data if editing
if ($simId > 0) {
    $stmt = $conn->prepare("SELECT * FROM mg5_ms_userinput_master WHERE ui_id = ? AND ui_team_pkid = ? LIMIT 1");
    $stmt->bind_param("ii", $simId, $_SESSION['team_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $simTitle = $row['ui_sim_title'];
        $simDesc = $row['ui_sim_desc'];
        $industryType = $row['ui_industry_type'];
        $geography = $row['ui_geography'];
        $operatingScale = $row['ui_operating_scale'];
        $language = $row['ui_lang'] ?? 'English';
        $noStages = $row['ui_no_stages'];
    }
    $stmt->close();
}

// Handle AJAX save request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_basic') {
    header('Content-Type: application/json');
    
    try {
        if (empty($_POST['sim_title'])) throw new Exception('Simulation title is required');
        if (empty($_POST['industry_type'])) throw new Exception('Industry type is required');
        if (empty($_POST['no_stages']) || intval($_POST['no_stages']) <= 0) throw new Exception('Please enter valid number of stages');
        
        $simTitle = trim($_POST['sim_title']);
        $simDesc = trim($_POST['sim_desc'] ?? '');
        $industryType = trim($_POST['industry_type']);
        $geography = trim($_POST['geography'] ?? '');
        $operatingScale = trim($_POST['operating_scale'] ?? '');
        $language = trim($_POST['language'] ?? 'English');
        $noStages = intval($_POST['no_stages']);
        
        if ($simId > 0) {
            // Update existing
            $stmt = $conn->prepare("UPDATE mg5_ms_userinput_master SET 
                ui_sim_title=?, ui_sim_desc=?, ui_industry_type=?, ui_geography=?, 
                ui_operating_scale=?, ui_lang=?, ui_no_stages=? 
                WHERE ui_id=? AND ui_team_pkid=?");
            $stmt->bind_param("ssssssiii", $simTitle, $simDesc, $industryType, $geography, $operatingScale, $language, $noStages, $simId, $_SESSION['team_id']);
        } else {
            // Insert new
            $stmt = $conn->prepare("INSERT INTO mg5_ms_userinput_master 
                (ui_team_pkid, ui_sim_title, ui_sim_desc, ui_industry_type, ui_geography, 
                 ui_operating_scale, ui_lang, ui_no_stages, ui_cur_stage, ui_created_at) 
                VALUES (?, ?,?,?,?,?,?,?, 1, NOW())");
            $stmt->bind_param("issssssi", $_SESSION['team_id'], $simTitle, $simDesc, $industryType, $geography, $operatingScale, $language, $noStages);
        }
        $stmt->execute();
        
        if ($simId === 0) {
            $simId = $conn->insert_id;
        }
        $stmt->close();
        
        echo json_encode(['success' => true, 'sim_id' => $simId]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Handle traditional form submit (fallback)
/* if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    // ... your existing validation and insert logic here ...
    // (Keeping your original code as fallback)
} */
?>

<div class="container">
    <div class="form-grid">
        <div class="form-column">
            <section class="form-section">
                <h2>🎮 Simulation Setup</h2>
                
                <form id="setupForm" method="POST">
                    <input type="hidden" name="sim_id" id="simId" value="<?= $simId ?>">
                    
                    <div class="form-group">
                        <label>Simulation Title *</label>
                        <input type="text" name="sim_title" id="sim_title"
                            value="<?= htmlspecialchars($simTitle) ?>" required>
                        <?php if (isset($errors['sim_title'])): ?>
                            <p class="error"><?= $errors['sim_title'] ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="sim_desc" id="sim_desc" rows="3"><?= htmlspecialchars($simDesc) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Industry Type *</label>
                        <input type="text" name="industry_type" id="industry_type"
                            value="<?= htmlspecialchars($industryType) ?>" required>
                        <?php if (isset($errors['industry_type'])): ?>
                            <p class="error"><?= $errors['industry_type'] ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Geography</label>
                        <input type="text" name="geography" id="geography"
                            value="<?= htmlspecialchars($geography) ?>">
                    </div>

                    <div class="form-group">
                        <label>Operating Scale</label>
                        <input type="text" name="operating_scale" id="operating_scale"
                            value="<?= htmlspecialchars($operatingScale) ?>">
                    </div>

                    <div class="form-group">
                        <label>Language</label>
                        <select name="language" id="language">
                            <option value="English" <?= $language==='English'?'selected':'' ?>>English</option>
                            <option value="Spanish" <?= $language==='Spanish'?'selected':'' ?>>Spanish</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>No of Stages *</label>
                        <input type="number" name="no_stages" id="no_stages"
                            min="1" max="10"
                            value="<?= htmlspecialchars($noStages) ?>" required>
                        <?php if (isset($errors['no_stages'])): ?>
                            <p class="error"><?= $errors['no_stages'] ?></p>
                        <?php endif; ?>
                        <small>Enter 1-10 stages for your simulation</small>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="saveBasicInfo()">💾 Save & Continue</button>
                        <span class="success" id="saveSuccess" style="display:none; margin-left:10px;">✓ Saved!</span>
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>

<script>
function saveBasicInfo() {
    const form = document.getElementById('setupForm');
    const data = new FormData(form);
    data.append('action', 'save_basic');
    
    const btn = form.querySelector('button[type="button"]');
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Saving...';
    
    fetch('', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                document.getElementById('saveSuccess').style.display = 'inline';
                setTimeout(() => {
                    // Redirect to stage builder
                    window.location.href = 'multistagedigisim.php?step=2&sim_id=' + res.sim_id;
                }, 1000);
            } else {
                alert('Error: ' + res.error);
                btn.disabled = false;
                btn.textContent = originalText;
            }
        })
        .catch(e => {
            alert('Network error: ' + e);
            btn.disabled = false;
            btn.textContent = originalText;
        });
}
</script>