<?php

session_start();

ini_set('display_errors',1);
error_reporting(E_ALL);

require "../include/dataconnect.php";
require "../openai_call.php";
require "../digisim_functions.php";
require "ms_digisim_functions.php";

$simId = isset($_GET['sim_id']) ? intval($_GET['sim_id']) : 0;
$teamId = $_SESSION['team_id'];

if($simId <= 0){
    throw new Exception("Invalid simulation ID.");
}

/* 
   LOAD MASTER MULTISTAGE INPUT */

$stmt = $conn->prepare("
SELECT *
FROM mg5_ms_userinput_master
WHERE ui_id=? AND ui_team_pkid=?
LIMIT 1
");

$stmt->bind_param("ii",$simId,$teamId);
$stmt->execute();
$master = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$master){
    throw new Exception("Simulation configuration not found.");
}

/* 
   CREATE MULTISTAGE MASTER RECORD
 */

$createdDate = date("Y-m-d H:i:s");

$stmt = $conn->prepare("
INSERT INTO mg5_ms_digisim_master
(ms_team_pkid, ms_name, ms_desc,ms_status, ms_createddate)
VALUES (?,?,?,1,?)
");

$stmt->bind_param(
"isss",
$teamId,
$master['ui_sim_title'],
$master['ui_sim_desc'],
$createdDate
);

$stmt->execute();
$msId = $conn->insert_id;
$stmt->close();

/* 
   LOAD STAGES
 */

$stages = getStages($conn,$simId);

if(empty($stages)){
    throw new Exception("No stages found.");
}

/* 
   GET CATEGORY
 */

// $lg_id = getDigisimCategory($conn,$teamId);

/* 
   LOAD PROMPTS
 */

$prompts = getActivePrompts($conn);

if(empty($prompts)){
    throw new Exception("No prompts configured.");
}

$conn->begin_transaction();

try{

foreach($stages as $stage){

    $messages=[];

    $vars = [

        "sim_title"       => $master['ui_sim_title']." - ".$stage['st_name'],
        "industry"        => $master['ui_industry_type'],
        "geography"       => $master['ui_geography'],
        "operating_scale" => $master['ui_operating_scale'],

        "scenario"        => $stage['st_scenario'],
        "objective"       => $stage['st_objective'],

        "injects"         => $stage['st_injects'],
        "score_value"     => $stage['st_score_value'],

        "language"        => $master['ui_lang'],
        "score_scale"     => $stage['st_score_scale']
    ];

    /* CREATE DIGISIM */

    $digisimId = createStageDigisim(
        $conn,
        $teamId,
        $vars['sim_title'],
        $stage['st_desc'],
        $stage['st_score_scale']
    );

    /* STORE ROUND RELATION */

$stageNo = $stage['st_stage_num'];

$stmtRound = $conn->prepare("
INSERT INTO mg5_ms_rounds
(
    r_digisim_master_pkid,
    r_digisim_pkid,
    r_stage_no
)
VALUES (?,?,?)
");

$stmtRound->bind_param(
"iii",
$msId,
$digisimId,
$stageNo
);

$stmtRound->execute();
$stmtRound->close();
    /* UPDATE CONFIG */

    updateDigisimConfig(
        $conn,
        $digisimId,
        $master
    );

    /* 
       PROMPT PIPELINE
     */

    $prompt1 = fillPrompt($prompts[1],$vars);
    addUser($messages,$prompt1);
    addAssistant($messages);

    $prompt2 = fillPrompt($prompts[2],$vars);
    addUser($messages,$prompt2);
    $orgProfile = addAssistant($messages);
    storeOrganizationProfile($conn,$digisimId,$orgProfile);

    $prompt3 = fillPrompt($prompts[3],$vars);
    addUser($messages,$prompt3);
    $injectRaw = addAssistant($messages);

    storeInjectsFullStructure(
        $conn,
        $digisimId,
        $injectRaw,
        $vars['sim_title']
    );

    $prompt4 = fillPrompt($prompts[4],$vars);
    addUser($messages,$prompt4);
    $responseRaw = addAssistant($messages);

    storeResponseTasksOnlyStatements3(
        $conn,
        $digisimId,
        $stage['st_score_scale'],
        $responseRaw,
        $vars['sim_title']
    );

    addUser($messages,$prompts[5]);
    $answerKeyRaw = addAssistant($messages);
    storeAnswerKey($conn,$digisimId,$answerKeyRaw);

    addUser($messages,$prompts[6]);
    $manualRaw = addAssistant($messages);
    storeModeratorManual($conn,$digisimId,$manualRaw);

}

$conn->commit();

header("Location: multistage_success.php?ms_id=".$msId);
exit;

}catch(Exception $e){

$conn->rollback();
echo "Failed: ".$e->getMessage();

}