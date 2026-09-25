<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once "db_config.php";

// 1. ดึงภาพรวมสถิติราคาแพงสุดที่เคยซื้อ (Target) และราคาต่ำสุด
$overviewSql = "SELECT 
                    COUNT(r.respondent_id) AS total_respondents,
                    AVG(ph.max_price) AS global_avg_max_price,
                    AVG(ph.min_price) AS global_avg_min_price
                FROM Respondent r
                JOIN PriceHistory ph ON r.respondent_id = ph.respondent_id";
$overviewRes = $conn->query($overviewSql)->fetch_assoc();

// 2. ดึงค่าเฉลี่ยราคาและชั่วโมงเล่นแยกตามแนวเกม (Genre Breakdown)
$genreSql = "SELECT 
                g.genre_code,
                g.genre_title,
                COUNT(r.respondent_id) AS genre_count,
                AVG(ph.max_price) AS avg_max_price,
                AVG(r.play_hours_week) AS avg_play_hours
             FROM Genre g
             JOIN RespondentGenre rg ON g.genre_id = rg.genre_id
             JOIN Respondent r ON rg.respondent_id = r.respondent_id
             JOIN PriceHistory ph ON r.respondent_id = ph.respondent_id
             GROUP BY g.genre_id";
$genreResult = $conn->query($genreSql);
$genreStats = [];
while ($row = $genreResult->fetch_assoc()) {
    $genreStats[$row['genre_code']] = [
        'count' => (int)$row['genre_count'],
        'avg_max_price' => (float)$row['avg_max_price'],
        'avg_play_hours' => (float)$row['avg_play_hours']
    ];
}

// 3. ดึงค่าเฉลี่ยราคาแยกตามแพลตฟอร์ม (Platform Breakdown)
$platformSql = "SELECT 
                    p.platform_name,
                    COUNT(r.respondent_id) AS platform_count,
                    AVG(ph.max_price) AS avg_max_price
                FROM Platform p
                JOIN RespondentPlatform rp ON p.platform_id = rp.platform_id
                JOIN Respondent r ON rp.respondent_id = r.respondent_id
                JOIN PriceHistory ph ON r.respondent_id = ph.respondent_id
                GROUP BY p.platform_id";
$platformResult = $conn->query($platformSql);
$platformStats = [];
while ($row = $platformResult->fetch_assoc()) {
    $platformStats[$row['platform_name']] = [
        'count' => (int)$row['platform_count'],
        'avg_max_price' => (float)$row['avg_max_price']
    ];
}

// 4. สัดส่วนความคุ้มค่า (Sentiment Breakdown)
$sentimentSql = "SELECT 
                    price_sentiment, 
                    COUNT(*) AS count,
                    AVG(max_price) AS avg_price
                 FROM PriceHistory 
                 GROUP BY price_sentiment";
$sentimentResult = $conn->query($sentimentSql);
$sentimentStats = [];
while ($row = $sentimentResult->fetch_assoc()) {
    $sentimentStats[$row['price_sentiment']] = [
        'count' => (int)$row['count'],
        'avg_price' => (float)$row['avg_price']
    ];
}

echo json_encode([
    "status" => "success",
    "overview" => $overviewRes,
    "genres" => $genreStats,
    "platforms" => $platformStats,
    "sentiments" => $sentimentStats
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>