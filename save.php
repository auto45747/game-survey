<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");
require_once "db_config.php";

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["status" => "error", "message" => "No input payload"]);
    exit();
}

$conn->begin_transaction();
try {
    // 1. ดึง ID ของ Occupation
    $stmtOcc = $conn->prepare("SELECT occupation_id FROM Occupation WHERE occupation_name = ?");
    $stmtOcc->bind_param("s", $data['occupation']);
    $stmtOcc->execute();
    $occRes = $stmtOcc->get_result()->fetch_assoc();
    $occId = $occRes ? $occRes['occupation_id'] : 1;
    $stmtOcc->close();

    // 2. ดึง ID ของ Channel
    $stmtCh = $conn->prepare("SELECT channel_id FROM SurveyChannel WHERE channel_name = ?");
    $stmtCh->bind_param("s", $data['survey_channel']);
    $stmtCh->execute();
    $chRes = $stmtCh->get_result()->fetch_assoc();
    $channelId = $chRes ? $chRes['channel_id'] : 1;
    $stmtCh->close();

    // 3. บันทึกข้อมูลลง Respondent
    $stmtResp = $conn->prepare("INSERT INTO Respondent (gender, age, occupation_id, income_month, play_hours_week, peak_time, survey_channel_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmtResp->bind_param("siiddsi", 
        $data['gender'], 
        $data['age'], 
        $occId, 
        $data['income_month'], 
        $data['play_hours_week'], 
        $data['peak_time'], 
        $channelId
    );
    $stmtResp->execute();
    $newRespId = $stmtResp->insert_id;
    $stmtResp->close();

    // 4. บันทึกประวัติราคาลง PriceHistory
    $stmtPrice = $conn->prepare("INSERT INTO PriceHistory (respondent_id, min_price, max_price, price_sentiment) VALUES (?, ?, ?, ?)");
    $stmtPrice->bind_param("idds", 
        $newRespId, 
        $data['min_game_price'], 
        $data['max_game_price'], 
        $data['max_price_sentiment']
    );
    $stmtPrice->execute();
    $stmtPrice->close();

    // 5. เชื่อม Platform (5NF Relation)
    $stmtPlat = $conn->prepare("SELECT platform_id FROM Platform WHERE platform_name = ?");
    $stmtPlat->bind_param("s", $data['primary_platform']);
    $stmtPlat->execute();
    $platRes = $stmtPlat->get_result()->fetch_assoc();
    if ($platRes) {
        $pId = $platRes['platform_id'];
        $insRP = $conn->prepare("INSERT INTO RespondentPlatform (respondent_id, platform_id) VALUES (?, ?)");
        $insRP->bind_param("ii", $newRespId, $pId);
        $insRP->execute();
        $insRP->close();
    }
    $stmtPlat->close();

    // 6. เชื่อม Genre (5NF Relation)
    $stmtGenre = $conn->prepare("SELECT genre_id FROM Genre WHERE genre_code = ? OR genre_title LIKE ?");
    $genreQuery = "%" . $data['primary_genre'] . "%";
    $stmtGenre->bind_param("ss", $data['primary_genre'], $genreQuery);
    $stmtGenre->execute();
    $genreRes = $stmtGenre->get_result()->fetch_assoc();
    if ($genreRes) {
        $gId = $genreRes['genre_id'];
        $insRG = $conn->prepare("INSERT INTO RespondentGenre (respondent_id, genre_id) VALUES (?, ?)");
        $insRG->bind_param("ii", $newRespId, $gId);
        $insRG->execute();
        $insRG->close();
    }
    $stmtGenre->close();

    $conn->commit();
    echo json_encode(["status" => "success", "id" => $newRespId]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
$conn->close();
?>