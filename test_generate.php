<?php
session_start();
if (!isset($_SESSION['team_id'])) {
    die("team_id not found in session");
}


ini_set('display_errors', 1);
error_reporting(E_ALL);

require "include/dataconnect.php";
require "openai_call.php";
require "digisim_functions.php";
// require "digisim_prompts.php";
$simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;
$teamId = $_SESSION['team_id'];

if ($simId <= 0) {
    throw new Exception("Invalid simulation ID.");
}
// fetching  user input
$stmt = $conn->prepare("
    SELECT *
    FROM mg5_digisim_userinput
    WHERE ui_id = ? AND ui_team_pkid = ?
    LIMIT 1
");

$stmt->bind_param("ii", $simId, $teamId);
$stmt->execute();
$userInput = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$userInput) {
    throw new Exception("Simulation configuration not found.");
}

//assignnind variabsles for userinput
$vars = [
    "sim_title"       => $userInput['ui_sim_title'],
    "industry"        => $userInput['ui_industry_type'],
    "geography"       => $userInput['ui_geography'],
    "operating_scale" => $userInput['ui_operating_scale'],
    "scenario"        => $userInput['ui_scenario'],
    "objective"       => $userInput['ui_objective'],
    "injects"         => $userInput['ui_injects'],
    "score_value"     => $userInput['ui_score_value'],
    "language"        => $userInput['ui_lang'],
    "score_scale"     => $scaleName ?? '',
];
/* 
   MARK SIMULATION AS REVIEW COMPLETED */

$updateStepStmt = $conn->prepare("
    UPDATE mg5_digisim_userinput
    SET ui_cur_step = 6,
        ui_updated_at = NOW()
    WHERE ui_id = ? AND ui_team_pkid = ?
");

$updateStepStmt->bind_param(
    "ii",
    $simId,
    $_SESSION['team_id']
);

$updateStepStmt->execute();
$updateStepStmt->close();


$conn->begin_transaction();

try {

    $messages = [];

    $teamId = $_SESSION['team_id'];

    $stmt = $conn->prepare("
    SELECT lg_id
    FROM mg5_digisim_category
    WHERE lg_team_pkid = ? 
    LIMIT 1
");

    $stmt->bind_param("i", $teamId);
    $stmt->execute();
    $stmt->bind_result($lg_id);
    $stmt->fetch();
    $stmt->close();

    if (!$lg_id) {
        throw new Exception("Digisim category not found for this team.");
    }
    $sim_title = $userInput['ui_sim_title'] ?? 'Simulation';
    $createdDate = date("Y-m-d H:i:s");

    $stmt = $conn->prepare("
    INSERT INTO mg5_digisim 
    (
        di_digisim_category_pkid,
        di_name,
        di_description,
        di_createddate,
        di_scoretype_id
    )
    VALUES (?, ?,?, ?, ?)
");


    $stmt->bind_param(
        "isssi",
        $lg_id,
        $sim_title,
        $userInput['ui_sim_desc'],
        $createdDate,
        $userInput['ui_score_scale']    // st_id
    );

    $stmt->execute();

    $digisimId = $conn->insert_id;

    /* 
   UPDATE DIGISIM CONFIG FROM USER INPUT
 */

    $updateStmt = $conn->prepare("
    UPDATE mg5_digisim
    SET
        di_analysis_id  = ?,
        di_priority_point  = ?,
        di_scoring_logic   = ?,
        di_scoring_basis   = ?,
        di_total_basis     = ?,
        di_result_type     = ?,
        di_min_select      = ?,
        di_max_score       = ?,
        di_status          = 1
    WHERE di_id = ?
");

    $updateStmt->bind_param(
        "iiiiiiiii",
        $userInput['ui_analysis_id'],
        $userInput['ui_priority_points'],
        $userInput['ui_scoring_logic'],
        $userInput['ui_scoring_basis'],
        $userInput['ui_total_basis'],
        $userInput['ui_result'],
        $userInput['ui_min_select'],
        $userInput['ui_max_score'],
        $digisimId
    );

    $updateStmt->execute();
    $updateStmt->close();

    $prompts = getActivePrompts($conn);

    if (empty($prompts)) {
        throw new Exception("No active prompts found.");
    }


    /* 
       PROMPT 1
     */
    $prompt1 = fillPrompt($prompts[1], $vars);
    addUser($messages, $prompt1);
    addAssistant($messages);
    //    echo "Prompt 1 Completed\n";

    /* 
      PROMPT 2 */
    $prompt2 = fillPrompt($prompts[2], $vars);
    addUser($messages, $prompt2);
    $orgProfile = addAssistant($messages);

    storeOrganizationProfile($conn, $digisimId, $orgProfile);

    //    echo "Organization Profile Stored\n";

    //    echo "Prompt 2 Completed\n";

    /* 
       PROMPT 3 */


    $prompt3 = fillPrompt($prompts[3], $vars);
    addUser($messages, $prompt3);
    $injectRaw = addAssistant($messages);
    storeInjectsFullStructure(
        $conn,
        $digisimId,
        $injectRaw,
        $sim_title // base name
    );

    //    echo "Injects Stored Successfully\n";
    //    echo "Prompt 3 Completed\n";

    /* 
       PROMPT 4 –
     */
    $prompt4 = fillPrompt($prompts[4], $vars);
    addUser($messages, $prompt4);
    $responseRaw = addAssistant($messages);

    storeResponseTasksOnlyStatements3(
        $conn,
        $digisimId,
        $userInput['ui_score_scale'],
        $responseRaw,
        $sim_title
    );



    //    echo "Prompt 4 Completed\n";
    /*  PROMPT 5  */

    addUser($messages, $prompts[5]);
    $answerKeyRaw = addAssistant($messages);
    storeAnswerKey($conn, $digisimId, $answerKeyRaw);

    //    echo "Debrief & Learning Objective Stored\n";


    /* 
       PROMPT 6
     */
    addUser($messages, $prompts[6]);
    $manualRaw = addAssistant($messages);
    storeModeratorManual($conn, $digisimId, $manualRaw);

    //    echo "Moderator Manual Stored\n";


    $conn->commit();

    header("Location: /trainerbyte-digisim/pages/digisim_success.php?digisim_id=" . $digisimId);
    exit;
} catch (Exception $e) {

    $conn->rollback();
    echo "Failed: " . $e->getMessage();
}
