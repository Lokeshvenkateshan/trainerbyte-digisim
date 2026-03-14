<?php

$pageTitle = 'Processing Configuration';
$pageCSS = '/manual/css/manual_processing_configuration.css';

require_once __DIR__ . '/../include/dataconnect.php';

$digisimId = isset($_GET['digisim_id']) ? intval($_GET['digisim_id']) : 0;

if ($digisimId <= 0) {
    header("Location: manual_page_container.php?step=1");
    exit;
}

$errors = [];

$priorityPoints = null;
$scoringLogic = null;
$scoringBasis = null;
$totalBasis = null;
$taskResultDisplay = null;


/* -----------------------------------------
LOAD EXISTING CONFIGURATION
----------------------------------------- */

$stmt = $conn->prepare("
SELECT
di_priority_point,
di_scoring_logic,
di_scoring_basis,
di_total_basis,
di_result_type
FROM mg5_digisim
WHERE di_id = ?
");

$stmt->bind_param("i",$digisimId);
$stmt->execute();

$result = $stmt->get_result();

if($row = $result->fetch_assoc()){

$priorityPoints = $row['di_priority_point'];
$scoringLogic = $row['di_scoring_logic'];
$scoringBasis = $row['di_scoring_basis'];
$totalBasis = $row['di_total_basis'];
$taskResultDisplay = $row['di_result_type'];

}

$stmt->close();


/* -----------------------------------------
HANDLE FORM SUBMISSION
----------------------------------------- */

if($_SERVER['REQUEST_METHOD'] === 'POST'){

/* PRIORITY POINTS */

if($_POST['priority_points'] === 'expert'){
$priorityPoints = 1;
}
elseif($_POST['priority_points'] === 'manual'){
$priorityPoints = 2;
}


/* SCORING LOGIC */

switch($_POST['scoring_logic']){

case 'atleast': $scoringLogic = 1; break;
case 'actual': $scoringLogic = 2; break;
case 'absolute': $scoringLogic = 3; break;

}


/* SCORING BASIS */

switch($_POST['scoring_basis']){

case 'all': $scoringBasis = 1; break;
case 'part': $scoringBasis = 2; break;
case 'minimum': $scoringBasis = 3; break;

}


/* TOTAL BASIS */

switch($_POST['total_basis']){

case 'all_tasks': $totalBasis = 1; break;
case 'marked_tasks': $totalBasis = 2; break;

}


/* RESULT DISPLAY */

$taskResultDisplay = 1;

if(in_array('percentage',$_POST['task_result_display'])){
$taskResultDisplay = 2;
}
elseif(in_array('raw_score',$_POST['task_result_display'])){
$taskResultDisplay = 3;
}
elseif(in_array('legend',$_POST['task_result_display'])){
$taskResultDisplay = 4;
}


/* UPDATE DIGISIM */

$stmt = $conn->prepare("
UPDATE mg5_digisim
SET
di_priority_point=?,
di_scoring_logic=?,
di_scoring_basis=?,
di_total_basis=?,
di_result_type=?
WHERE di_id=?
");

$stmt->bind_param(
"iiiiii",
$priorityPoints,
$scoringLogic,
$scoringBasis,
$totalBasis,
$taskResultDisplay,
$digisimId
);

$stmt->execute();


header("Location: manual_page_container.php?step=5&digisim_id=".$digisimId);
exit;

}

?>


<div class="container">

<div class="page-header">
<h1>Processing Configuration</h1>
<p>Refine how the simulation engine calculates performance metrics.</p>
</div>


<form method="POST">

<div class="config-grid">

<!-- PRIORITY POINTS -->

<div class="config-section">

<h2>Priority Points</h2>

<label class="option-card">

<input type="radio"
name="priority_points"
value="expert"
<?= $priorityPoints == 1 ? 'checked' : '' ?>
>

<div>
<h3>Expert</h3>
<p>Pre-set weights</p>
</div>

</label>


<label class="option-card">

<input type="radio"
name="priority_points"
value="manual"
<?= $priorityPoints == 2 ? 'checked' : '' ?>
>

<div>
<h3>Manual</h3>
<p>Custom weights</p>
</div>

</label>

</div>



<!-- SCORING LOGIC -->

<div class="config-section">

<h2>Scoring Logic</h2>

<label class="option-card">

<input type="radio"
name="scoring_logic"
value="atleast"
<?= $scoringLogic == 1 ? 'checked' : '' ?>
>

<h3>Atleast</h3>

</label>


<label class="option-card">

<input type="radio"
name="scoring_logic"
value="actual"
<?= $scoringLogic == 2 ? 'checked' : '' ?>
>

<h3>Actual</h3>

</label>


<label class="option-card">

<input type="radio"
name="scoring_logic"
value="absolute"
<?= $scoringLogic == 3 ? 'checked' : '' ?>
>

<h3>Absolute</h3>

</label>

</div>



<!-- SCORING BASIS -->

<div class="config-section">

<h2>Scoring Basis</h2>

<label class="pill">

<input type="radio"
name="scoring_basis"
value="all"
<?= $scoringBasis == 1 ? 'checked' : '' ?>
>

All

</label>


<label class="pill">

<input type="radio"
name="scoring_basis"
value="part"
<?= $scoringBasis == 2 ? 'checked' : '' ?>
>

Part

</label>


<label class="pill">

<input type="radio"
name="scoring_basis"
value="minimum"
<?= $scoringBasis == 3 ? 'checked' : '' ?>
>

Minimum

</label>

</div>



<!-- TOTAL BASIS -->

<div class="config-section">

<h2>Total Basis</h2>

<label class="option-card">

<input type="radio"
name="total_basis"
value="all_tasks"
<?= $totalBasis == 1 ? 'checked' : '' ?>
>

All Tasks

</label>


<label class="option-card">

<input type="radio"
name="total_basis"
value="marked_tasks"
<?= $totalBasis == 2 ? 'checked' : '' ?>
>

Marked Tasks Only

</label>

</div>



<!-- RESULT DISPLAY -->

<div class="config-section">

<h2>Task Result Display</h2>

<label class="checkbox-card">

<input type="checkbox"
name="task_result_display[]"
value="percentage"
<?= $taskResultDisplay == 2 ? 'checked' : '' ?>
>

Percentage

</label>


<label class="checkbox-card">

<input type="checkbox"
name="task_result_display[]"
value="raw_score"
<?= $taskResultDisplay == 3 ? 'checked' : '' ?>
>

Raw Score

</label>


<label class="checkbox-card">

<input type="checkbox"
name="task_result_display[]"
value="legend"
<?= $taskResultDisplay == 4 ? 'checked' : '' ?>
>

Legend

</label>

</div>

</div>


<div class="form-actions">

<a href="manual_page_container.php?step=3&digisim_id=<?=$digisimId?>" class="btn-secondary">
Previous
</a>

<button type="submit" class="btn-primary">
Next
</button>

</div>

</form>

</div>