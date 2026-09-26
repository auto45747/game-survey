<?php
// get_analytics_data.php
header("Content-Type: application/json; charset=UTF-8");
require_once "db_config.php";

$action = $_GET['action'] ?? 'all';

try {
    // ดึงค่าภาพรวมสำหรับ Dashboard และ Summary
    $overviewSql = "
        SELECT 
            COUNT(DISTINCT r.respondent_id) as total_respondents,
            COALESCE(AVG(ph.max_price), 0) as avg_max_price,
            COALESCE(AVG(ph.min_price), 0) as avg_min_price,
            COALESCE(AVG(r.play_hours_week), 0) as avg_hours
        FROM Respondent r
        LEFT JOIN PriceHistory ph ON r.respondent_id = ph.respondent_id
    ";
    $overviewRes = $conn->query($overviewSql);
    $overview = $overviewRes->fetch_assoc();

    // ดึงข้อมูลแนวเกมทั้งหมด
    $genres = [];
    $genreRes = $conn->query("SELECT genre_id, genre_code, genre_title FROM Genre ORDER BY genre_id ASC");
    while ($row = $genreRes->fetch_assoc()) {
        $genres[] = $row;
    }

    // ดึงข้อมูลแพลตฟอร์มทั้งหมด
    $platforms = [];
    $platRes = $conn->query("SELECT platform_id, platform_name FROM Platform ORDER BY platform_id ASC");
    while ($row = $platRes->fetch_assoc()) {
        $platforms[] = $row;
    }

    // กรณีต้องการคำนวณราคาเฉพาะ Genre และ Platform ผ่าน POST
    $calculatedPrice = null;
    $rawInput = file_get_contents("php://input");
    if (!empty($rawInput)) {
        $postData = json_decode($rawInput, true);
        if ($postData && isset($postData['genre_id']) && isset($postData['platform_id'])) {
            $g_id = intval($postData['genre_id']);
            $p_id = intval($postData['platform_id']);

            $calcSql = "
                SELECT 
                    COALESCE(AVG(ph.max_price), 0) as target_max,
                    COALESCE(AVG(ph.min_price), 0) as target_min,
                    COUNT(r.respondent_id) as sample_count
                FROM Respondent r
                JOIN PriceHistory ph ON r.respondent_id = ph.respondent_id
                JOIN RespondentGenre rg ON r.respondent_id = rg.respondent_id
                JOIN RespondentPlatform rp ON r.respondent_id = rp.respondent_id
                WHERE rg.genre_id = ? AND rp.platform_id = ?
            ";
            $stmt = $conn->prepare($calcSql);
            $stmt->bind_param("ii", $g_id, $p_id);
            $stmt->execute();
            $calculatedPrice = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    }

    echo json_encode([
        "status" => "success",
        "overview" => $overview,
        "genres" => $genres,
        "platforms" => $platforms,
        "calculation" => $calculatedPrice
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>