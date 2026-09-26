<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");
require_once "db_config.php";
// Auto-create tables if they don't exist
$conn->query("CREATE TABLE IF NOT EXISTS Occupation (occupation_id INT AUTO_INCREMENT PRIMARY KEY, occupation_name VARCHAR(100) NOT NULL UNIQUE) ENGINE=InnoDB");
$conn->query("CREATE TABLE IF NOT EXISTS SurveyChannel (channel_id INT AUTO_INCREMENT PRIMARY KEY, channel_name VARCHAR(100) NOT NULL UNIQUE) ENGINE=InnoDB");
$conn->query("CREATE TABLE IF NOT EXISTS Platform (platform_id INT AUTO_INCREMENT PRIMARY KEY, platform_name VARCHAR(50) NOT NULL UNIQUE) ENGINE=InnoDB");
$conn->query("CREATE TABLE IF NOT EXISTS Genre (genre_id INT AUTO_INCREMENT PRIMARY KEY, genre_code VARCHAR(20) NOT NULL UNIQUE, genre_title VARCHAR(100) NOT NULL, genre_description VARCHAR(255)) ENGINE=InnoDB");
$conn->query("CREATE TABLE IF NOT EXISTS Respondent (respondent_id INT AUTO_INCREMENT PRIMARY KEY, gender VARCHAR(20) NOT NULL, age INT NOT NULL, occupation_id INT NOT NULL, income_month DECIMAL(10,2) NOT NULL, play_hours_week DECIMAL(5,2) NOT NULL, peak_time VARCHAR(50) NOT NULL, survey_channel_id INT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB");
$conn->query("CREATE TABLE IF NOT EXISTS PriceHistory (history_id INT AUTO_INCREMENT PRIMARY KEY, respondent_id INT NOT NULL UNIQUE, min_price DECIMAL(10,2) NOT NULL, max_price DECIMAL(10,2) NOT NULL, price_sentiment VARCHAR(50) NOT NULL) ENGINE=InnoDB");
$conn->query("CREATE TABLE IF NOT EXISTS PlatformGenreSupport (platform_id INT NOT NULL, genre_id INT NOT NULL, PRIMARY KEY (platform_id, genre_id)) ENGINE=InnoDB");
$conn->query("CREATE TABLE IF NOT EXISTS RespondentPlatform (respondent_id INT NOT NULL, platform_id INT NOT NULL, PRIMARY KEY (respondent_id, platform_id)) ENGINE=InnoDB");
$conn->query("CREATE TABLE IF NOT EXISTS RespondentGenre (respondent_id INT NOT NULL, genre_id INT NOT NULL, PRIMARY KEY (respondent_id, genre_id)) ENGINE=InnoDB");

// Initial seeds
$conn->query("INSERT IGNORE INTO Occupation (occupation_name) VALUES ('นักเรียน/นักศึกษา'), ('พนักงานบริษัทเอกชน'), ('ข้าราชการ/รัฐวิสาหกิจ'), ('ฟรีแลนซ์/ธุรกิจส่วนตัว'), ('ว่างงาน/พักงาน'), ('อื่นๆ')");
$conn->query("INSERT IGNORE INTO SurveyChannel (channel_name) VALUES ('Discord/Facebook กลุ่มเกม'), ('ไลน์ชั้นปี/มหาวิทยาลัย'), ('QR Code พื้นที่จริง'), ('ส่งต่อส่วนตัว')");
$conn->query("INSERT IGNORE INTO Platform (platform_name) VALUES ('PC'), ('Console'), ('Mobile')");
$conn->query("INSERT IGNORE INTO Genre (genre_code, genre_title, genre_description) VALUES 
('Action', '1. Action', 'FPS, Platformer, Fighting'),
('Adventure', '2. Adventure', 'Point & Click, Visual Novel'),
('Action-Adv', '3. Action-Adventure', 'Survival Horror, Stealth, Metroidvania'),
('RPG', '4. RPG', 'Soulslike, MMORPG, Turn-based RPG'),
('Strategy', '5. Strategy', 'RTS, Turn-base, MOBA, Tower Defence'),
('Simulation', '6. Simulation', 'City Builder / Tycoon, Farming Sim'),
('Sport-Racing', '7. Sport & Racing', 'Sport, Racing Simulator / Arcade'),
('Puzzle-Party', '8. Puzzle & Party Game', 'Puzzle Logic, Party / Co-op')");

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