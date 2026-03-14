<?php

function getStages($conn,$simId){

$stages=[];

$stmt=$conn->prepare("
SELECT *
FROM mg5_ms_stage_input
WHERE st_userinput_pkid=?
ORDER BY st_stage_num ASC
");

$stmt->bind_param("i",$simId);
$stmt->execute();

$res=$stmt->get_result();

while($row=$res->fetch_assoc()){
$stages[]=$row;
}

$stmt->close();

return $stages;

}

function getDigisimCategory($conn,$teamId){
$lg_id = null;
$stmt=$conn->prepare("
SELECT lg_id
FROM mg5_digisim_category
WHERE lg_team_pkid=?
LIMIT 1
");

$stmt->bind_param("i",$teamId);
$stmt->execute();
$stmt->bind_result($lg_id);
$stmt->fetch();
$stmt->close();

if(!$lg_id){
throw new Exception("Digisim category not found.");
}

return $lg_id;

}

function createStageDigisim($conn,$categoryId,$name,$desc,$scoreScale){

$createdDate=date("Y-m-d H:i:s");

$stmt=$conn->prepare("
INSERT INTO mg5_digisim
(
di_digisim_category_pkid,
di_name,
di_description,
di_createddate,
di_scoretype_id
)
VALUES (?,?,?,?,?)
");

$stmt->bind_param(
"isssi",
$categoryId,
$name,
$desc,
$createdDate,
$scoreScale
);

$stmt->execute();

$digisimId=$conn->insert_id;

$stmt->close();

return $digisimId;

}

function updateDigisimConfig($conn,$digisimId,$master){

$stmt=$conn->prepare("
UPDATE mg5_digisim
SET
di_analysis_id=1,
di_priority_point=?,
di_scoring_logic=?,
di_scoring_basis=?,
di_total_basis=?,
di_result_type=?,
di_status=1
WHERE di_id=?
");

$stmt->bind_param(
"iiiiii",
$master['ui_priority_points'],
$master['ui_scoring_logic'],
$master['ui_scoring_basis'],
$master['ui_total_basis'],
$master['ui_result'],
$digisimId
);

$stmt->execute();
$stmt->close();

}