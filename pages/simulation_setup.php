<?php
$simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;

$pageTitle = 'Simulation Setup';
$pageCSS = '/pages/page-styles/simulation_setup.css';

require_once __DIR__ . '/../include/dataconnect.php';

$simTitle = $simDesc = $industryType = $geography = $operatingScale = $scenario = $objectives = $language = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_POST['sim_title'])) {
        $errors['sim_title'] = 'Simulation title is required';
    } else {
        $simTitle = htmlspecialchars($_POST['sim_title']);
    }

    $simDesc = !empty($_POST['sim_desc']) ? htmlspecialchars($_POST['sim_desc']) : '';

    if (empty($_POST['industry_type'])) {
        $errors['industry_type'] = 'Industry type is required';
    } else {
        $industryType = htmlspecialchars($_POST['industry_type']);
    }

    $geography = !empty($_POST['geography']) ? htmlspecialchars($_POST['geography']) : '';
    $operatingScale = !empty($_POST['operating_scale']) ? htmlspecialchars($_POST['operating_scale']) : '';

    if (empty($_POST['language'])) {
        $errors['language'] = 'Language is required';
    } else {
        $language = htmlspecialchars($_POST['language']);
    }

    $scenario = !empty($_POST['scenario']) ? $_POST['scenario'] : '';
    $objectives = !empty($_POST['objectives']) ? $_POST['objectives'] : '';

    if (empty($errors)) {

        if ($simId > 0) {

            $updateStmt = $conn->prepare("
            UPDATE mg5_digisim_userinput 
            SET 
            ui_sim_title=?,
            ui_sim_desc=?,
            ui_industry_type=?,
            ui_geography=?,
            ui_operating_scale=?,
            ui_lang=?,
            ui_scenario=?,
            ui_objective=?,
            ui_cur_step=1
            WHERE ui_id=? AND ui_team_pkid=?");

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
            INSERT INTO mg5_digisim_userinput(
            ui_team_pkid,ui_sim_title,ui_sim_desc,
            ui_industry_type,ui_geography,
            ui_operating_scale,ui_lang,
            ui_scenario,ui_objective,ui_cur_step
            ) VALUES (?,?,?,?,?,?,?,?,?,1)");

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

        header("Location: page-container.php?step=2&sim_id=" . $simId);
        exit;
    }
} else if ($simId > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {

    $loadStmt = $conn->prepare("
    SELECT * FROM mg5_digisim_userinput
    WHERE ui_id=? AND ui_team_pkid=? LIMIT 1");

    $loadStmt->bind_param("ii", $simId, $_SESSION['team_id']);
    $loadStmt->execute();

    $result = $loadStmt->get_result();

    if ($result->num_rows > 0) {

        $row = $result->fetch_assoc();

        $simTitle = $row['ui_sim_title'];
        $simDesc = $row['ui_sim_desc'];
        $industryType = $row['ui_industry_type'];
        $geography = $row['ui_geography'];
        $operatingScale = $row['ui_operating_scale'];
        $language = $row['ui_lang'];
        $scenario = $row['ui_scenario'];
        $objectives = $row['ui_objective'];
    }

    $loadStmt->close();
}
?>

<form method="POST" id="simForm">

    <div class="page-layout">

        <div class="page-content">
        <?php include 'stepper.php'; ?>

            <div class="content-container">

                <div class="sim-content">

                    <!-- LEFT PANEL -->

                    <div class="left-panel">

                        <div class="form-group">
                            <label>
                                Simulation Title
                                <span class="info-icon" data-tip="Title of the simulation exercise.">i</span>
                            </label>

                            <input type="text" name="sim_title"
                                placeholder="e.g. Cyber Security Incident Response"
                                value="<?= htmlspecialchars($simTitle) ?>">
                        </div>

                        <div class="form-group">
                            <label>
                                Simulation Description
                                <span class="info-icon" data-tip="Short description shown in simulation library.">i</span>
                            </label>

                            <textarea name="sim_desc"><?= htmlspecialchars($simDesc) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>
                                Industry Type
                                <span class="info-icon" data-tip="Industry where simulation applies.">i</span>
                            </label>

                            <input type="text" name="industry_type"
                                placeholder="Technology"
                                value="<?= htmlspecialchars($industryType) ?>">
                        </div>

                        <div class="form-group">
                            <label>
                                Geography
                                <span class="info-icon" data-tip="Simulation location.">i</span>
                            </label>

                            <input type="text" name="geography"
                                placeholder="Global"
                                value="<?= htmlspecialchars($geography) ?>">
                        </div>

                    </div>


                    <!-- RIGHT PANEL -->

                    <div class="right-panel">

                        <div class="form-group">
                            <label>
                                Operating Scale
                                <span class="info-icon" data-tip="Operational scale.">i</span>
                            </label>

                            <input type="text" name="operating_scale"
                                placeholder="Remote"
                                value="<?= htmlspecialchars($operatingScale) ?>">
                        </div>

                        <div class="form-group">
                            <label>
                                Language
                                <span class="info-icon" data-tip="Simulation language.">i</span>
                            </label>

                            <select name="language">
                                <option value="">Select Language</option>
                                <option value="English" <?= ($language == 'English') ? 'selected' : '' ?>>English</option>
                                <option value="Spanish" <?= ($language == 'Spanish') ? 'selected' : '' ?>>Spanish</option>
                            </select>

                        </div>

                        <div class="form-group">
                            <label>
                                Scenario
                                <span class="info-icon" data-tip="Describe scenario.">i</span>
                            </label>

                            <textarea name="scenario"><?= htmlspecialchars($scenario) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>
                                Objectives
                                <span class="info-icon" data-tip="Learning objectives.">i</span>
                            </label>

                            <textarea name="objectives"><?= htmlspecialchars($objectives) ?></textarea>
                        </div>

                    </div>

                </div>

            </div>


        </div>

    </div>

</form>