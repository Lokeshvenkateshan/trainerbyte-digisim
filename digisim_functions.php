<?php


function getActivePrompts($conn): array
{
    $prompts = [];

    $stmt = $conn->prepare("
        SELECT pr_seq, pr_prompt
        FROM mg5_digisim_prompt
        WHERE pr_status = 1
        ORDER BY pr_seq ASC
    ");

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $prompts[(int)$row['pr_seq']] = $row['pr_prompt'];
    }

    $stmt->close();

    return $prompts;
}
/* 
   MESSAGE HELPERS */

function addUser(array &$messages, string $content): void
{
    $messages[] = [
        "role" => "user",
        "content" => $content
    ];
}

function addAssistant(array &$messages): string
{
    $response = callOpenAI($messages);

    if (isOpenAIError($response)) {
        throw new Exception($response);
    }

    $messages[] = [
        "role" => "assistant",
        "content" => $response
    ];

    return $response;
}

function isOpenAIError(string $response): bool
{
    return str_starts_with($response, "OpenAI API error")
        || str_starts_with($response, "Curl error")
        || str_contains($response, "not found");
}
function fillPrompt(string $template, array $vars): string
{
    foreach ($vars as $key => $value) {
        $template = str_replace("{{" . $key . "}}", $value, $template);
    }
    return $template;
}



// store orgg profile
function storeOrganizationProfile($conn, int $digisimId, string $rawResponse)
{
    // Clean possible markdown
    $clean = trim($rawResponse);
    $clean = preg_replace('/^```json|```$/m', '', $clean);

    $data = json_decode($clean, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON from OpenAI: " . json_last_error_msg());
    }

    $required = ['company_name', 'title', 'introduction'];

    foreach ($required as $key) {
        if (empty($data[$key])) {
            throw new Exception("Missing required field: $key");
        }
    }

    $jsonToStore = json_encode($data, JSON_UNESCAPED_UNICODE);

    $stmt = $conn->prepare("
        UPDATE mg5_digisim
        SET di_casestudy = ?
        WHERE di_id = ?
    ");

    $stmt->bind_param("si", $jsonToStore, $digisimId);
    $stmt->execute();
}
function storeInjectsFullStructure(
    $conn,
    int $digisimId,
    string $injectRaw,
    string $baseName
) {

    /* 
       1. CLEAN & DECODE JSON */

    $clean = trim($injectRaw);
    $clean = preg_replace('/^```json|```$/m', '', $clean);

    $injectData = json_decode($clean, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid Inject JSON: " . json_last_error_msg());
    }

    if (!is_array($injectData)) {
        throw new Exception("Inject data is not array.");
    }

    /* 
       2. INSERT INTO mg5_mdm_injectes
     */

    $teamId = $_SESSION['team_id'];

    $injectGroupName = $baseName . "_injects";
    $status = 1;
    $order = 1;
    $createdDate = date("Y-m-d H:i:s");

    $stmt = $conn->prepare("
        INSERT INTO mg5_mdm_injectes
        (lg_digisim_pkid, lg_name, lg_status, lg_order, createddate)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("isiis", $digisimId, $injectGroupName, $status, $order, $createdDate);
    $stmt->execute();

    $injectGroupId = $conn->insert_id;

    /* 
       3. CREATE SUB CHANNELS */

    $mediaTypes = [];

    foreach ($injectData as $inject) {

        if (
            empty($inject['media_type']) ||
            empty($inject['subject']) ||
            empty($inject['body'])
        ) {
            throw new Exception("Invalid inject structure.");
        }

        $mediaTypes[] = trim($inject['media_type']);
    }

    $uniqueMediaTypes = array_unique($mediaTypes);

    $channelMap = [];

    $stmt = $conn->prepare("
        INSERT INTO mg5_sub_channels
        (ch_level, ch_status, in_group_pkid, ch_sequence)
        VALUES (?, 1, ?, ?)
    ");

    $sequence = 1;

    foreach ($uniqueMediaTypes as $mediaType) {

        $stmt->bind_param("sii", $mediaType, $injectGroupId, $sequence);
        $stmt->execute();

        $channelMap[$mediaType] = $conn->insert_id;
        $sequence++;
    }

    /* 
       4. INSERT MESSAGES
     */

    $stmt = $conn->prepare("
        INSERT INTO mg5_digisim_message
        (dm_digisim_pkid,
         dm_injectes_pkid,
         dm_subject,
         dm_message,
         dm_attachment,
         dm_trigger,
         dm_event)
        VALUES (?, ?, ?, ?, '', 1, 0)
    ");

    foreach ($injectData as $inject) {

        $mediaType = trim($inject['media_type']);

        if (!isset($channelMap[$mediaType])) {
            throw new Exception("Channel not found for media type: $mediaType");
        }

        $channelId = $channelMap[$mediaType];

        $subject = $inject['subject'];
        $body = $inject['body'];

        $stmt->bind_param(
            "iiss",
            $digisimId,
            $channelId,
            $subject,
            $body
        );

        $stmt->execute();
    }
    /* 
       5. UPDATE mg5_digisim
     */

    $stmt = $conn->prepare("
        UPDATE mg5_digisim
        SET di_injects_id = ?
        WHERE di_id = ?
    ");

    $stmt->bind_param("ii", $injectGroupId, $digisimId);
    $stmt->execute();
}
//old
function storeResponseTasksOnlyStatements(
    $conn,
    int $digisimId,
    string $responseRaw
) {

    /* 1. CLEAN JSON */

    $clean = trim($responseRaw);
    $clean = preg_replace('/^```json|```$/m', '', $clean);

    $data = json_decode($clean, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON in response tasks: " . json_last_error_msg());
    }

    if (!is_array($data)) {
        throw new Exception("Response tasks is not an array.");
    }

    /* 2. PREPARE INSERT */

    $stmt = $conn->prepare("
        INSERT INTO mg5_digisim_response
        (
            dr_digisim_pkid,
            dr_response_pkid,
            dr_order,
            dr_tasks,
            dr_score_pkid,
            dr_benchmark_pkid
        )
        VALUES (?, 0, ?, ?, 1, 0)
    ");

    $order = 1;

    foreach ($data as $item) {

        if (empty($item['statement'])) {
            throw new Exception("Missing statement field in response.");
        }

        $statement = trim($item['statement']);

        $stmt->bind_param(
            "iis",
            $digisimId,
            $order,
            $statement
        );

        $stmt->execute();
        $order++;
    }
}
function storeResponseTasksOnlyStatements2(
    $conn,
    int $digisimId,
    int $scoreTypeId,
    string $responseRaw
) {

    /* 1️⃣ CLEAN JSON */
    $clean = trim($responseRaw);
    $clean = preg_replace('/^```json|```$/m', '', $clean);

    $data = json_decode($clean, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON in response tasks: " . json_last_error_msg());
    }

    if (!is_array($data)) {
        throw new Exception("Response tasks is not an array.");
    }

    /* 2️ FETCH SCORE TYPE VALUES */
    $scoreMap = [];

    $scoreStmt = $conn->prepare("
        SELECT stv_id, stv_name
        FROM mg5_scoretype_value
        WHERE stv_scoretype_pkid = ?
    ");

    $scoreStmt->bind_param("i", $scoreTypeId);
    $scoreStmt->execute();
    $result = $scoreStmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $normalized = strtolower(trim($row['stv_name']));
        $normalized = str_replace([' ', '-'], '', $normalized);

        $scoreMap[$normalized] = $row['stv_id'];

        /* Optional synonym support */
        if ($normalized === 'moderate') {
            $scoreMap['medium'] = $row['stv_id'];
        }
    }

    $scoreStmt->close();

    if (empty($scoreMap)) {
        throw new Exception("No score values found for selected scale.");
    }

    /* 3️ PREPARE INSERT */
    $stmt = $conn->prepare("
        INSERT INTO mg5_digisim_response
        (
            dr_digisim_pkid,
            dr_response_pkid,
            dr_order,
            dr_tasks,
            dr_score_pkid,
            dr_benchmark_pkid
        )
        VALUES (?, 0, ?, ?, ?, 0)
    ");

    $order = 1;

    foreach ($data as $item) {

        if (empty($item['statement']) || empty($item['scale'])) {
            throw new Exception("Missing statement or scale field.");
        }

        $statement = trim($item['statement']);

        $scale = strtolower(trim($item['scale']));
        $scale = str_replace([' ', '-'], '', $scale);

        if (!isset($scoreMap[$scale])) {
            throw new Exception("Invalid scale value returned: " . $item['scale']);
        }

        $scorePkid = $scoreMap[$scale];

        $stmt->bind_param(
            "iisi",
            $digisimId,
            $order,
            $statement,
            $scorePkid
        );

        $stmt->execute();
        $order++;
    }
}


//3rd new with multipe relation response table this is correcct original function the above are not..
function storeResponseTasksOnlyStatements3(
    $conn,
    int $digisimId,
    int $scoreTypeId,
    string $responseRaw,
    string $baseName
) {

    $createdDate = date("Y-m-d H:i:s");

    /* 
       1️ CLEAN JSON RESPONSE
     */

    $clean = trim($responseRaw);
    $clean = preg_replace('/^```json|```$/m', '', $clean);

    $data = json_decode($clean, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON in response tasks: " . json_last_error_msg());
    }

    if (!is_array($data)) {
        throw new Exception("Response tasks is not an array.");
    }

    /* 
       2️ CREATE MDM RESPONSE GROUP
     */

    $groupName = $baseName . "_mdmresponse";

    $stmtGroup = $conn->prepare("
        INSERT INTO mg5_mdm_response
        (
            lg_digisim_pkid,
            lg_name,
            lg_description,
            lg_status,
            lg_order,
            createddate
        )
        VALUES (?, ?, '', 1, 1, ?)
    ");

    $stmtGroup->bind_param("iss", $digisimId, $groupName, $createdDate);
    $stmtGroup->execute();

    $responseGroupId = $conn->insert_id;
    $stmtGroup->close();


    /* 
       3️ UPDATE DIGISIM WITH RESPONSE GROUP ID
     */

    $stmtUpdate = $conn->prepare("
        UPDATE mg5_digisim
        SET di_response_id = ?
        WHERE di_id = ?
    ");

    $stmtUpdate->bind_param("ii", $responseGroupId, $digisimId);
    $stmtUpdate->execute();
    $stmtUpdate->close();


    /* 
       4️ CREATE SUB INDEX
     */

    $subIndexName = $baseName . "_subindex";

    

    $stmtSub = $conn->prepare("
    INSERT INTO mg5_sub_index
    (
        ln_name,
        ln_desc,
        ln_status,
        ix_group_pkid,
        ln_image,
        ln_sequence
    )
    VALUES (?, '', 1, ?, '', 1)
");

    $stmtSub->bind_param("si", $subIndexName, $responseGroupId);

    $stmtSub->execute();

    $subIndexId = $conn->insert_id;
    $stmtSub->close();


    /* 
       5️ FETCH SCORE TYPE VALUES (DYNAMIC MAPPING)
     */

    $scoreMap = [];

    $scoreStmt = $conn->prepare("
        SELECT stv_id, stv_name
        FROM mg5_scoretype_value
        WHERE stv_scoretype_pkid = ?
    ");

    $scoreStmt->bind_param("i", $scoreTypeId);
    $scoreStmt->execute();
    $result = $scoreStmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $normalized = strtolower(trim($row['stv_name']));
        $normalized = str_replace([' ', '-'], '', $normalized);

        $scoreMap[$normalized] = $row['stv_id'];

        // Optional synonym handling
        if ($normalized === 'moderate') {
            $scoreMap['medium'] = $row['stv_id'];
        }
    }

    $scoreStmt->close();

    if (empty($scoreMap)) {
        throw new Exception("No score values found for selected scale.");
    }


    /* 
       6️ INSERT RESPONSE STATEMENTS
     */

    $stmt = $conn->prepare("
        INSERT INTO mg5_digisim_response
        (
            dr_digisim_pkid,
            dr_response_pkid,
            dr_order,
            dr_tasks,
            dr_score_pkid,
            dr_benchmark_pkid
        )
        VALUES (?, ?, ?, ?, ?, 0)
    ");

    $order = 1;

    foreach ($data as $item) {

        if (empty($item['statement']) || empty($item['scale'])) {
            throw new Exception("Missing statement or scale field.");
        }

        $statement = trim($item['statement']);

        $scale = strtolower(trim($item['scale']));
        $scale = str_replace([' ', '-'], '', $scale);

        if (!isset($scoreMap[$scale])) {
            throw new Exception("Invalid scale value returned: " . $item['scale']);
        }

        $scorePkid = $scoreMap[$scale];

        $stmt->bind_param(
            "iiisi",
            $digisimId,
            $subIndexId,
            $order,
            $statement,
            $scorePkid
        );

        $stmt->execute();
        $order++;
    }

    $stmt->close();
}


function storeAnswerKey(
    $conn,
    int $digisimId,
    string $response
) {

    $stmt = $conn->prepare("
        UPDATE mg5_digisim
        SET di_answerkey = ?
        WHERE di_id = ?
    ");

    $stmt->bind_param("si", $response, $digisimId);
    $stmt->execute();
}

function storeModeratorManual(
    $conn,
    int $digisimId,
    string $manualContent
) {

    $stmt = $conn->prepare("
        UPDATE mg5_digisim
        SET di_manual = ?
        WHERE di_id = ?
    ");

    $stmt->bind_param("si", $manualContent, $digisimId);
    $stmt->execute();
}
