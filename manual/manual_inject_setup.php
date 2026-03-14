<?php

$pageTitle = "Manual Inject Builder";
$pageCSS   = "/manual/css/manual_inject_setup.css";

require_once __DIR__ . '/../include/dataconnect.php';

$digisimId = intval($_GET['digisim_id'] ?? 0);

if ($digisimId <= 0) {
    die("Invalid Digisim ID");
}

/* ----------------------------
GET OR CREATE INJECT GROUP
---------------------------- */

$stmt = $conn->prepare("
SELECT di_injects_id
FROM mg5_digisim
WHERE di_id = ?
");

$stmt->bind_param("i",$digisimId);
$stmt->execute();
$stmt->bind_result($injectGroupId);
$stmt->fetch();
$stmt->close();

if(!$injectGroupId){

/* GET SIMULATION TITLE */

$stmt = $conn->prepare("
SELECT di_name
FROM mg5_digisim
WHERE di_id = ?
");

$stmt->bind_param("i",$digisimId);
$stmt->execute();
$stmt->bind_result($simTitle);
$stmt->fetch();
$stmt->close();

/* CREATE INJECT GROUP NAME */

$injectGroupName = $simTitle . "_injects";

$date = date("Y-m-d H:i:s");

$stmt=$conn->prepare("
INSERT INTO mg5_mdm_injectes
(lg_digisim_pkid,lg_name,lg_status,lg_order,createddate)
VALUES (?, ?,1,1,?)
");

$stmt->bind_param("iss",$digisimId,$injectGroupName,$date);
$stmt->execute();

$injectGroupId=$conn->insert_id;

$stmt=$conn->prepare("
UPDATE mg5_digisim
SET di_injects_id=?
WHERE di_id=?
");

$stmt->bind_param("ii",$injectGroupId,$digisimId);
$stmt->execute();

}

/* ----------------------------
FETCH INJECT TYPES
---------------------------- */

$injectTypes=[];

$res=$conn->query("
SELECT in_name
FROM mg5_inject_master
WHERE in_status=1
ORDER BY in_id
");

while($r=$res->fetch_assoc()){
$injectTypes[]=$r['in_name'];
}

$currentType=$_GET['type'] ?? $injectTypes[0];

/* ----------------------------
EDIT MODE
---------------------------- */

$editId=intval($_GET['edit'] ?? 0);

$subject="";
$body="";
$trigger=1;

if($editId){

$stmt=$conn->prepare("
SELECT m.dm_subject,m.dm_message,m.dm_trigger,c.ch_level
FROM mg5_digisim_message m
JOIN mg5_sub_channels c
ON m.dm_injectes_pkid=c.ch_id
WHERE m.dm_id=? AND m.dm_digisim_pkid=?
");

$stmt->bind_param("ii",$editId,$digisimId);
$stmt->execute();

$res=$stmt->get_result()->fetch_assoc();

$subject=$res['dm_subject'];
$body=$res['dm_message'];
$trigger=$res['dm_trigger'];
$currentType=$res['ch_level'];

}

/* ----------------------------
SAVE / UPDATE
---------------------------- */

if($_SERVER['REQUEST_METHOD']=="POST"){

$type=$_POST['inject_type'];
$subject=$_POST['subject'];
$body=$_POST['body'];
$trigger=intval($_POST['trigger']);
$editId=intval($_POST['edit_id'] ?? 0);

/* FIND CHANNEL */

$stmt=$conn->prepare("
SELECT ch_id
FROM mg5_sub_channels
WHERE ch_level=? AND in_group_pkid=?
");

$stmt->bind_param("si",$type,$injectGroupId);
$stmt->execute();
$stmt->bind_result($channelId);
$stmt->fetch();
$stmt->close();

/* CREATE CHANNEL IF NOT EXIST */

if(!$channelId){

$stmt=$conn->prepare("
INSERT INTO mg5_sub_channels
(ch_level,ch_status,in_group_pkid,ch_sequence)
VALUES (?,1,?,1)
");

$stmt->bind_param("si",$type,$injectGroupId);
$stmt->execute();

$channelId=$conn->insert_id;

}

/* UPDATE */

if($editId){

$stmt=$conn->prepare("
UPDATE mg5_digisim_message
SET dm_subject=?,dm_message=?,dm_trigger=?
WHERE dm_id=?
");

$stmt->bind_param("ssii",$subject,$body,$trigger,$editId);
$stmt->execute();

}else{

$stmt=$conn->prepare("
INSERT INTO mg5_digisim_message
(dm_digisim_pkid,dm_injectes_pkid,dm_subject,dm_message,dm_attachment,dm_trigger,dm_event)
VALUES (?,?,?,?, '',?,0)
");

$stmt->bind_param("iissi",$digisimId,$channelId,$subject,$body,$trigger);
$stmt->execute();

}

header("Location: manual_page_container.php?step=2&digisim_id=$digisimId&type=$type");
exit;

}

/* ----------------------------
FETCH EXISTING INJECTS
---------------------------- */

$stmt=$conn->prepare("
SELECT m.dm_id,m.dm_subject,c.ch_level,m.dm_trigger
FROM mg5_digisim_message m
JOIN mg5_sub_channels c
ON m.dm_injectes_pkid=c.ch_id
WHERE m.dm_digisim_pkid=?
ORDER BY m.dm_id DESC
");

$stmt->bind_param("i",$digisimId);
$stmt->execute();

$existing=$stmt->get_result();
?>

<div class="inject-layout">

<div class="inject-left">

<div class="inject-tabs">

<?php foreach($injectTypes as $t): ?>

<a class="inject-tab <?= $t==$currentType?'active':'' ?>"
href="manual_page_container.php?step=2&digisim_id=<?=$digisimId?>&type=<?=$t?>">
<?=$t?>
</a>

<?php endforeach; ?>

</div>

<form method="POST">

<input type="hidden" name="inject_type" value="<?=$currentType?>">
<input type="hidden" name="edit_id" value="<?=$editId?>">

<label>Inject Name</label>
<input type="text" name="subject"
value="<?=htmlspecialchars($subject)?>" required>

<label>Body Content</label>
<textarea name="body" rows="10"><?=htmlspecialchars($body)?></textarea>

<label>Trigger Type</label>

<select name="trigger">

<option value="1" <?= $trigger==1?'selected':'' ?>>Start</option>
<option value="2" <?= $trigger==2?'selected':'' ?>>Task</option>
<option value="3" <?= $trigger==3?'selected':'' ?>>Progressive</option>

</select>

<button class="btn-primary">

<?= $editId ? "Update Inject" : "Save & Add Inject" ?>

</button>

</form>

<div class="step-navigation">

<a class="btn-secondary"
href="manual_page_container.php?step=1&digisim_id=<?=$digisimId?>">

Previous

</a>

<a class="btn-primary"
href="manual_page_container.php?step=3&digisim_id=<?=$digisimId?>">

Next

</a>

</div>

</div>

<div class="inject-right">

<h3>Existing Injects</h3>

<?php while($row=$existing->fetch_assoc()): ?>

<a class="inject-card"
href="manual_page_container.php?step=2&digisim_id=<?=$digisimId?>&type=<?=$row['ch_level']?>&edit=<?=$row['dm_id']?>">

<strong><?=$row['ch_level']?></strong><br>
<?=$row['dm_subject']?><br>

<span class="trigger-label">

<?php
if($row['dm_trigger']==1) echo "Start";
if($row['dm_trigger']==2) echo "Task";
if($row['dm_trigger']==3) echo "Progressive";
?>

</span>

</a>

<?php endwhile; ?>

</div>

</div>