<?php

session_start();

$pageCSS = "/pages/page-styles/page_container.css";

require_once __DIR__ . '/../include/dataconnect.php';

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$simId = isset($_GET['sim_id']) ? (int)$_GET['sim_id'] : 0;

$steps = [
    1 => 'simulation_setup.php',
    2 => 'inject_distribution.php',
    3 => 'score_scale.php',
    4 => 'processing_configuration.php',
    5 => 'review_simulation.php',
    6 => 'digisim_success.php'
];

if (!array_key_exists($step, $steps)) {
    $step = 1;
}

/* 
   CHECK FOR DRAFT (ONLY WHEN STEP 1 & NO sim_id)
 */

$draftSimId = 0;

if ($step == 1 && $simId == 0) {

    $draftStmt = $conn->prepare("
        SELECT ui_id
        FROM mg5_digisim_userinput
        WHERE ui_team_pkid = ?
          AND ui_cur_step < 6
        ORDER BY ui_updated_at DESC
        LIMIT 1
    ");

    $draftStmt->bind_param('i', $_SESSION['team_id']);
    $draftStmt->execute();
    $draftResult = $draftStmt->get_result();

    if ($draftResult->num_rows > 0) {
        $draftRow = $draftResult->fetch_assoc();
        $draftSimId = $draftRow['ui_id'];
    }

    $draftStmt->close();
}
/*

| STEP VALIDATION
        
*/

if ($simId > 0) {

    $checkStmt = $conn->prepare("
        SELECT ui_cur_step 
        FROM mg5_digisim_userinput
        WHERE ui_id = ? AND ui_team_pkid = ?
    ");

    $checkStmt->bind_param('ii', $simId, $_SESSION['team_id']);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $currentStepInDB = (int)$row['ui_cur_step'];

        if ($step > $currentStepInDB + 1) {
            $step = $currentStepInDB;
        }
    }

    $checkStmt->close();
}

$pageFile = __DIR__ . '/' . $steps[$step];

/*

| CAPTURE STEP PAGE OUTPUT

*/

ob_start();
require $pageFile;
$pageContent = ob_get_clean();

/*

| NOW LOAD HEADER (it can access $pageTitle & $pageCSS)

*/

require_once __DIR__ . '/../layout/header.php';


/* SHOW DRAFT POPUP ONLY ON STEP 1 */
if ($step == 1 && $simId == 0 && $draftSimId > 0) {
?>
    <div class="draft-overlay">
        <div class="draft-modal">
            <h3>Draft Simulation Found</h3>
            <p>You have an unfinished simulation. Would you like to continue?</p>

            <div class="draft-actions">
                <a href="page-container.php?step=1&sim_id=<?= $draftSimId ?>" class="pbtn-primary">
                    Continue Draft
                </a>

                <a href="page-container.php?step=1&new=1" class="sbtn-secondary">
                    Create New
                </a>
            </div>
        </div>
    </div>
    <style>
        .draft-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .draft-modal {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            width: 400px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .draft-modal h3 {
            margin-bottom: 10px;
        }

        .draft-actions {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .pbtn-primary {
            background: #2c4152;
            color: #fff;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
        }

        .sbtn-secondary {
            background: #eee;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
        }
    </style>

<?php
}
/*

| PRINT PAGE CONTENT

*/

echo '<div class="page-container">';

include __DIR__ . "/progress_bar.php";

echo $pageContent;

echo '</div>';

/*

| FOOTER

*/

require_once __DIR__ . '/../layout/footer.php';
