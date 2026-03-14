<?php

$pageTitle = "Manual Response Builder";
$pageCSS   = "/manual/css/manual_response_setup.css";

require_once __DIR__ . '/../include/dataconnect.php';

$digisimId = isset($_GET['digisim_id']) ? intval($_GET['digisim_id']) : 0;

if ($digisimId <= 0) {
    die("Invalid Digisim ID");
}

/* ----------------------------------
GET SELECTED SCORE SCALE
---------------------------------- */

$stmt = $conn->prepare("
SELECT di_scoretype_id
FROM mg5_digisim
WHERE di_id = ?
");

$stmt->bind_param("i",$digisimId);
$stmt->execute();
$stmt->bind_result($selectedScaleId);
$stmt->fetch();
$stmt->close();

/* ----------------------------------
FETCH SCORE TYPES
---------------------------------- */

$scoreTypes = [];

$res = $conn->query("
SELECT st_id, st_name
FROM mg5_scoretype
");

while($row = $res->fetch_assoc()){
    $scoreTypes[] = $row;
}

/* ----------------------------------
FETCH SCALE COMPONENTS
---------------------------------- */

$scaleComponents = [];

$res = $conn->query("
SELECT stv_scoretype_pkid, stv_id, stv_name, stv_color
FROM mg5_scoretype_value
ORDER BY stv_value DESC
");

while($row = $res->fetch_assoc()){
    $scaleComponents[$row['stv_scoretype_pkid']][] = $row;
}

/* ----------------------------------
FETCH SAVED STATEMENTS
---------------------------------- */

$savedStatements = [];

$stmt = $conn->prepare("
SELECT dr_tasks, dr_score_pkid
FROM mg5_digisim_response
WHERE dr_digisim_pkid = ?
ORDER BY dr_order
");

$stmt->bind_param("i",$digisimId);
$stmt->execute();
$result = $stmt->get_result();

while($row = $result->fetch_assoc()){
    $savedStatements[$row['dr_score_pkid']][] = $row['dr_tasks'];
}

$stmt->close();


/* ----------------------------------
SAVE STATEMENTS
---------------------------------- */

if($_SERVER['REQUEST_METHOD']=="POST"){

$scaleId = intval($_POST['score_scale']);

/* UPDATE SCALE */

$stmt = $conn->prepare("
UPDATE mg5_digisim
SET di_scoretype_id=?
WHERE di_id=?
");

$stmt->bind_param("ii",$scaleId,$digisimId);
$stmt->execute();

/* DELETE OLD RESPONSES */

$conn->query("
DELETE FROM mg5_digisim_response
WHERE dr_digisim_pkid=".$digisimId
);

/* INSERT NEW */

if(isset($_POST['statement'])){

foreach($_POST['statement'] as $scoreId=>$statements){

$order=1;

foreach($statements as $s){

$s = trim($s);

if($s=="") continue;

$stmt = $conn->prepare("
INSERT INTO mg5_digisim_response
(dr_digisim_pkid,dr_response_pkid,dr_order,dr_tasks,dr_score_pkid,dr_benchmark_pkid)
VALUES (?,?,?,?,?,0)
");

$stmt->bind_param(
"iiisi",
$digisimId,
$digisimId,
$order,
$s,
$scoreId
);

$stmt->execute();

$order++;

}

}

}

header("Location: manual_page_container.php?step=4&digisim_id=".$digisimId);
exit;

}

?>

<form method="POST">

<div class="scale-selection">

<h3>Choose Response Scale</h3>

<div class="scale-grid">

<?php foreach($scoreTypes as $st): ?>

<label class="scale-card">

<input
type="radio"
name="score_scale"
value="<?=$st['st_id']?>"
<?= $selectedScaleId==$st['st_id']?'checked':'' ?>
>

<h4><?=$st['st_name']?></h4>

</label>

<?php endforeach; ?>

</div>

</div>


<h3>Configure Response Statements</h3>

<div id="response-container"></div>


<div class="form-actions">

<a class="btn-secondary"
href="manual_page_container.php?step=2&digisim_id=<?=$digisimId?>">
Previous
</a>

<button type="submit" class="btn-primary">
Next
</button>

</div>

</form>


<script>

const scaleComponents = <?=json_encode($scaleComponents)?>;
const savedStatements = <?=json_encode($savedStatements)?>;

const container = document.getElementById("response-container")

document.querySelectorAll("input[name='score_scale']").forEach(radio=>{

radio.addEventListener("change",function(){
renderResponses(this.value)
})

})

let selected = document.querySelector("input[name='score_scale']:checked")

if(selected){
renderResponses(selected.value)
}

function renderResponses(scaleId){

container.innerHTML=""

let components = scaleComponents[scaleId]

if(!components) return

components.forEach(comp=>{

let block = document.createElement("div")

block.className="response-block"

block.style.borderLeft="5px solid "+(comp.stv_color || "#ccc")

block.innerHTML = `
<h4>${comp.stv_name}</h4>

<div class="statement-list" data-score="${comp.stv_id}"></div>

<button type="button"
class="add-statement"
data-score="${comp.stv_id}">
+ Add Statement
</button>
`

container.appendChild(block)

let list = block.querySelector(".statement-list")

if(savedStatements[comp.stv_id]){

savedStatements[comp.stv_id].forEach(text=>{

let input = document.createElement("input")

input.type="text"
input.name="statement["+comp.stv_id+"][]"
input.value=text

list.appendChild(input)

})

}else{

let input = document.createElement("input")

input.type="text"
input.name="statement["+comp.stv_id+"][]"
input.placeholder="Enter response statement"

list.appendChild(input)

}

})

initAddButtons()

}

function initAddButtons(){

document.querySelectorAll(".add-statement").forEach(btn=>{

btn.onclick=function(){

let score=this.dataset.score

let list=document.querySelector(
'.statement-list[data-score="'+score+'"]'
)

let input=document.createElement("input")

input.type="text"
input.name="statement["+score+"][]"
input.placeholder="Enter response statement"

list.appendChild(input)

}

})

}

</script>