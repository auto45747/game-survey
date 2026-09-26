<?php
// save.php
require_once "db_config.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.html");
    exit();
}

$gender = trim($_POST['gender'] ?? '');
$age = intval($_POST['age'] ?? 0);
$occupation_id = intval($_POST['occupation_id'] ?? 0);
$income_month = floatval($_POST['income_month'] ?? 0);
$play_hours_week = floatval($_POST['play_hours_week'] ?? 0);
$peak_time = trim($_POST['peak_time'] ?? '');
$platforms = $_POST['platforms'] ?? [];
$genres = $_POST['genres'] ?? [];
$min_price = floatval($_POST['min_price'] ?? 0);
$max_price = floatval($_POST['max_price'] ?? 0);
$price_sentiment = trim($_POST['price_sentiment'] ?? '');
$survey_channel_id = intval($_POST['survey_channel_id'] ?? 0);

if (empty($gender) || $age <= 0 || $occupation_id <= 0 || empty($peak_time) || empty($platforms) || empty($genres) || empty($price_sentiment) || $survey_channel_id <= 0) {
    die("กรุณากรอกข้อมูลให้ครบถ้วนทุกข้อ <a href='index.html'>กลับไปแก้ไข</a>");
}

$conn->begin_transaction();

try {
    // 1. Insert Respondent
    $stmt1 = $conn->prepare("INSERT INTO Respondent (gender, age, occupation_id, income_month, play_hours_week, peak_time, survey_channel_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt1->bind_param("siiddsi", $gender, $age, $occupation_id, $income_month, $play_hours_week, $peak_time, $survey_channel_id);
    $stmt1->execute();
    $respondent_id = $conn->insert_id;
    $stmt1->close();

    // 2. Insert PriceHistory
    $stmt2 = $conn->prepare("INSERT INTO PriceHistory (respondent_id, min_price, max_price, price_sentiment) VALUES (?, ?, ?, ?)");
    $stmt2->bind_param("idds", $respondent_id, $min_price, $max_price, $price_sentiment);
    $stmt2->execute();
    $stmt2->close();

    // 3. Insert RespondentPlatform
    $stmt3 = $conn->prepare("INSERT INTO RespondentPlatform (respondent_id, platform_id) VALUES (?, ?)");
    foreach ($platforms as $p_id) {
        $plat_val = intval($p_id);
        $stmt3->bind_param("ii", $respondent_id, $plat_val);
        $stmt3->execute();
    }
    $stmt3->close();

    // 4. Insert RespondentGenre
    $stmt4 = $conn->prepare("INSERT INTO RespondentGenre (respondent_id, genre_id) VALUES (?, ?)");
    foreach ($genres as $g_id) {
        $genre_val = intval($g_id);
        $stmt4->bind_param("ii", $respondent_id, $genre_val);
        $stmt4->execute();
    }
    $stmt4->close();

    $conn->commit();

    // ส่งผู้ตอบไปหน้า summary.html ทันที
    header("Location: summary.html");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    die("เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage());
}
?>