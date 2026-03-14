<?php

$pageTitle = "Answer Key & Moderator Manual";
$pageCSS   = "/manual/css/manual_answer_manual.css";

require_once __DIR__ . '/../include/dataconnect.php';

$digisimId = isset($_GET['digisim_id']) ? intval($_GET['digisim_id']) : 0;

if ($digisimId <= 0) {
    header("Location: manual_page_container.php?step=1");
    exit;
}

$answerKey = "";
$manualContent = "";

/* LOAD EXISTING DATA */

$stmt = $conn->prepare("
SELECT di_answerkey, di_manual
FROM mg5_digisim
WHERE di_id = ?
");

$stmt->bind_param("i",$digisimId);
$stmt->execute();
$result = $stmt->get_result();

if($row = $result->fetch_assoc()){

$answerKey = $row['di_answerkey'];
$manualContent = $row['di_manual'];

}

$stmt->close();


/* SAVE DATA */

if($_SERVER['REQUEST_METHOD']=="POST"){

$answerKey = $_POST['answer_key'] ?? "";
$manualContent = $_POST['moderator_manual'] ?? "";

$stmt = $conn->prepare("
UPDATE mg5_digisim
SET
di_answerkey = ?,
di_manual = ?
WHERE di_id = ?
");

$stmt->bind_param("ssi",$answerKey,$manualContent,$digisimId);
$stmt->execute();

header("Location: manual_page_container.php?step=6&digisim_id=".$digisimId);
exit;

}

?>

<form method="POST">

<div class="manual-editor">

<h3>De-briefing Content</h3>

<textarea
name="answer_key"
rows="12"
placeholder="Write the answer key / debriefing content here..."
><?= htmlspecialchars($answerKey ?? '') ?></textarea>


<h3>Moderator Manual</h3>

<textarea
name="moderator_manual"
rows="12"
placeholder="Write the moderator manual instructions..."
><?= htmlspecialchars($manualContent ?? '') ?></textarea>

</div>


<div class="form-actions">

<a class="btn-secondary"
href="manual_page_container.php?step=4&digisim_id=<?=$digisimId?>">
Previous
</a>

<button type="submit" class="btn-primary">
Finish
</button>

</div>

</form>