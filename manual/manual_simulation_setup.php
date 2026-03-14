<?php

$pageTitle = "Manual Simulation Setup";
$pageCSS = "/manual/css/manual_simulation.css";

require_once __DIR__ . '/../include/dataconnect.php';

$teamId = $_SESSION['team_id'];

$digisimId = isset($_GET['digisim_id']) ? intval($_GET['digisim_id']) : 0;

$companyName = "";
$simulationTitle = "";
$simulationContext = "";

if($digisimId){

$stmt=$conn->prepare("
SELECT di_name,di_casestudy
FROM mg5_digisim
WHERE di_id=?
");

$stmt->bind_param("i",$digisimId);
$stmt->execute();

$row=$stmt->get_result()->fetch_assoc();

$simulationTitle=$row['di_name'];

$data=json_decode($row['di_casestudy'],true);

$companyName=$data['company_name'] ?? "";
$simulationContext=$data['introduction'] ?? "";

}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $companyName = trim($_POST['company_name']);
    $simulationTitle = trim($_POST['simulation_title']);
    $simulationContext = trim($_POST['simulation_context']);

    if (!$companyName) $errors[] = "Organization name required";
    if (!$simulationTitle) $errors[] = "Simulation title required";
    if (!$simulationContext) $errors[] = "Simulation context required";

    if (empty($errors)) {

        $stmt = $conn->prepare("
            SELECT lg_id
            FROM mg5_digisim_category
            WHERE lg_team_pkid = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $teamId);
        $stmt->execute();
        $stmt->bind_result($categoryId);
        $stmt->fetch();
        $stmt->close();

        $json = json_encode([
            "company_name"=>$companyName,
            "title"=>$simulationTitle,
            "introduction"=>$simulationContext
        ], JSON_UNESCAPED_UNICODE);

        $createdDate = date("Y-m-d H:i:s");

        $stmt = $conn->prepare("
            INSERT INTO mg5_digisim
            (
                di_digisim_category_pkid,
                di_name,
                di_casestudy,
                di_createddate,
                di_status
            )
            VALUES (?,?,?,?,1)
        ");

        $stmt->bind_param(
            "isss",
            $categoryId,
            $simulationTitle,
            $json,
            $createdDate
        );

        $stmt->execute();

        $digisimId = $conn->insert_id;

        header("Location: manual_page_container.php?step=2&digisim_id=".$digisimId);
        exit;
    }

}

?>

<form method="POST">

<div class="manual-layout">

<div class="manual-left">

<h3>Cover Image</h3>

<div class="upload-box">
Upload Simulation Banner
</div>

<label>Organization Name</label>

<input type="text"
name="company_name"
value="<?=htmlspecialchars($companyName)?>">

<label>Simulation Title</label>

<input type="text"
name="simulation_title"
value="<?=htmlspecialchars($simulationTitle)?>">

</div>


<div class="manual-right">

<h2>Simulation Context</h2>

<textarea
name="simulation_context"
rows="14"><?=htmlspecialchars($simulationContext)?></textarea>

<?php
foreach($errors as $e){
echo "<p class='error'>$e</p>";
}
?>

<div class="form-actions">

<button class="btn-primary">
Next
</button>

</div>

</div>

</div>

</form>