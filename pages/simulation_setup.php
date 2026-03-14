<?php
$simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;
// Set page title and CSS
$pageTitle = 'Simulation Setup';
$pageCSS = '/css/simulation_setup.css';



// Include database connection
require_once __DIR__ . '/../include/dataconnect.php';
$simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;


// Initialize form data
$simTitle = $simDesc = $industryType = $geography = $operatingScale = $scenario = $objectives = $language = '';
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    if (empty($_POST['sim_title'])) {
        $errors['sim_title'] = 'Simulation title is required';
    } else {
        $simTitle = htmlspecialchars($_POST['sim_title']);
    }

    $simDesc = !empty($_POST['sim_desc'])
        ? htmlspecialchars($_POST['sim_desc'])
        : '';

    if (empty($_POST['industry_type'])) {
        $errors['industry_type'] = 'Industry type is required';
    } else {
        $industryType = htmlspecialchars($_POST['industry_type']);
    }

    // Get all fields
    $geography = !empty($_POST['geography']) ? htmlspecialchars($_POST['geography']) : '';
    $operatingScale = !empty($_POST['operating_scale']) ? htmlspecialchars($_POST['operating_scale']) : '';

    if (empty($_POST['language'])) {
    $errors['language'] = 'Language is required';
    } else {
    $language = htmlspecialchars($_POST['language']);
    }

    $scenario = !empty($_POST['scenario']) ? $_POST['scenario'] : '';
    $objectives = !empty($_POST['objectives']) ? $_POST['objectives'] : '';

    // If no errors, process form
    if (empty($errors)) {
        try {
            // Check if simulation exists for this team

            if ($simId > 0) {

                $updateStmt = $conn->prepare("
        UPDATE mg5_digisim_userinput 
        SET 
            ui_sim_title = ?, 
            ui_sim_desc = ?,
            ui_industry_type = ?, 
            ui_geography = ?, 
            ui_operating_scale = ?, 
            ui_lang = ?, 
            ui_scenario = ?, 
            ui_objective = ?,
            ui_cur_step = 1
        WHERE ui_id = ? AND ui_team_pkid = ?
    ");

                $updateStmt->bind_param(
                    'ssssssssii',
                    $simTitle,
                    $simDesc,
                    $industryType,
                    $geography,
                    $operatingScale,
                    $language,
                    $scenario,
                    $objectives,
                    $simId,
                    $_SESSION['team_id']
                );

                $updateStmt->execute();
                $updateStmt->close();
            } else {

                $insertStmt = $conn->prepare("
        INSERT INTO mg5_digisim_userinput (
            ui_team_pkid, ui_sim_title, ui_sim_desc,ui_industry_type, ui_geography, 
            ui_operating_scale, ui_lang, ui_scenario, ui_objective, ui_cur_step
        ) VALUES (?, ?,?, ?, ?, ?, ?, ?, ?, 1)
    ");

                $insertStmt->bind_param(
                    'issssssss',
                    $_SESSION['team_id'],
                    $simTitle,  
                    $simDesc,
                    $industryType,
                    $geography,
                    $operatingScale,
                    $language,
                    $scenario,
                    $objectives
                );

                $insertStmt->execute();
                $simId = $insertStmt->insert_id;
                $insertStmt->close();
            }


            // Redirect to inject distribution page
            header("Location: page-container.php?step=2&sim_id=" . $simId);
            exit;
        } catch (Exception $e) {
            $errors['database'] = 'Error: ' . $e->getMessage();
        }
    }
}
/* 
   LOAD EXISTING DATA WHEN RETURNING TO STEP
 */

else if ($simId > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {

    $loadStmt = $conn->prepare("
        SELECT *
        FROM mg5_digisim_userinput
        WHERE ui_id = ? AND ui_team_pkid = ?
        LIMIT 1
    ");

    $loadStmt->bind_param("ii", $simId, $_SESSION['team_id']);
    $loadStmt->execute();
    $result = $loadStmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        $simTitle       = $row['ui_sim_title'];
        $simDesc        = $row['ui_sim_desc'];
        $industryType   = $row['ui_industry_type'];
        $geography      = $row['ui_geography'];
        $operatingScale = $row['ui_operating_scale'];
        $language       = $row['ui_lang'];
        $scenario       = $row['ui_scenario'];
        $objectives     = $row['ui_objective'];
    }

    $loadStmt->close();
}
?>

<form method="POST" action="">
    <div class="container">
        <div class="form-grid">
            <!-- Left Column -->
            <div class="form-column">
                <!-- Basic Information -->
                <section class="form-section">
                    <h2>Basic Information</h2>

                    <div class="form-group">
                        <label for="sim_title">Simulation Title *</label>
                        <input type="text" id="sim_title" name="sim_title"
                            placeholder="e.g. Cyber Security Incident Response"
                            value="<?= htmlspecialchars($simTitle) ?>" required>
                        <?php if (isset($errors['sim_title'])): ?>
                            <p class="error"><?= $errors['sim_title'] ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="sim_desc">Simulation Description</label>
                        <textarea id="sim_desc" name="sim_desc"
                            placeholder="Brief description of the simulation (used in library display)..."
                            rows="3"><?= htmlspecialchars($simDesc) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="industry_type">Industry Type *</label>
                        <input type="text" id="industry_type" name="industry_type"
                            placeholder="e.g. Technology, Healthcare, Finance"
                            value="<?= htmlspecialchars($industryType) ?>" required>
                        <?php if (isset($errors['industry_type'])): ?>
                            <p class="error"><?= $errors['industry_type'] ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="geography">Geography</label>
                        <input type="text" id="geography" name="geography"
                            placeholder="e.g. Global, North America, Europe"
                            value="<?= htmlspecialchars($geography) ?>">
                    </div>

                    <div class="form-group">
                        <label for="operating_scale">Operating Scale / Conditions</label>
                        <input type="text" id="operating_scale" name="operating_scale"
                            placeholder="e.g. Remote Only, High Stress, Multi-location"
                            value="<?= htmlspecialchars($operatingScale) ?>">
                    </div>

                    <div class="form-group">
                        <label for="language">Language *</label>
                        <select id="language" name="language" required>
                            <option value="">Select Language</option>
                            <option value="English" <?= ($language == 'English') ? 'selected' : '' ?>>
                                English
                            </option>
                            <option value="Spanish" <?= ($language === 'Spanish') ? 'selected' : '' ?>>
                                Spanish
                            </option>
                        </select>
                    </div>
                </section>

                
            </div>

            <!-- Right Column -->
            <div class="form-column">
                <!-- Context & Objectives -->
                <section class="form-section">
                    <h2>Context & Objectives</h2>

                    <div class="form-group">
                        <label for="scenario">BRIEF ABOUT SCENARIO</label>
                        <small>Recommended: 200-500 words</small>
                        <textarea id="scenario" name="scenario"
                            placeholder="Describe the core conflict, historical context, and the immediate situation..."
                            rows="8"><?= htmlspecialchars($scenario) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="objectives">EXERCISE OBJECTIVES</label>
                        <small>What should participants learn?</small>
                        <textarea id="objectives" name="objectives"
                            placeholder="Identify key learning outcomes, specific skills to be tested, and success criteria..."
                            rows="8"><?= htmlspecialchars($objectives) ?></textarea>
                    </div>
                </section>
            </div>
        </div>

        <div class="form-actions">
            <?php if (isset($errors['database'])): ?>
                <p class="error"><?= $errors['database'] ?></p>
            <?php endif; ?>

            <button type="submit" class="btn-primary">Next</button>
        </div>
    </div>
</form>