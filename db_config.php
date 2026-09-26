<?php
// db_config.php
$host = getenv('DB_HOST') ?: "mysql-ff35469-autosiri20-c79e.i.aivencloud.com";
$user = getenv('DB_USER') ?: "avnadmin";
$pass = getenv('DB_PASS') ?: base64_decode("QVZOU19Cb08tSlM4YjIxRGo1VXYyWVky");
$db   = getenv('DB_NAME') ?: "defaultdb";
$port = (int)(getenv('DB_PORT') ?: 19547);

$conn = mysqli_init();
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
$conn->real_connect($host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if ($conn->connect_error) {
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(["status" => "error", "message" => "Database Connection Failed: " . $conn->connect_error]);
    exit();
}

$conn->set_charset("utf8mb4");

// Auto-initialize 5NF schema and initial seed data if not exist
$initSql = <<<SQL
CREATE TABLE IF NOT EXISTS Occupation (
    occupation_id INT AUTO_INCREMENT PRIMARY KEY,
    occupation_name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS SurveyChannel (
    channel_id INT AUTO_INCREMENT PRIMARY KEY,
    channel_name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Platform (
    platform_id INT AUTO_INCREMENT PRIMARY KEY,
    platform_name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Genre (
    genre_id INT AUTO_INCREMENT PRIMARY KEY,
    genre_code VARCHAR(20) NOT NULL UNIQUE,
    genre_title VARCHAR(100) NOT NULL,
    genre_description VARCHAR(255)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Respondent (
    respondent_id INT AUTO_INCREMENT PRIMARY KEY,
    gender VARCHAR(20) NOT NULL,
    age INT NOT NULL,
    occupation_id INT NOT NULL,
    income_month DECIMAL(10, 2) NOT NULL,
    play_hours_week DECIMAL(5, 2) NOT NULL,
    peak_time VARCHAR(50) NOT NULL,
    survey_channel_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_resp_occ FOREIGN KEY (occupation_id) REFERENCES Occupation (occupation_id),
    CONSTRAINT fk_resp_channel FOREIGN KEY (survey_channel_id) REFERENCES SurveyChannel (channel_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS PriceHistory (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    respondent_id INT NOT NULL UNIQUE,
    min_price DECIMAL(10, 2) NOT NULL,
    max_price DECIMAL(10, 2) NOT NULL,
    price_sentiment VARCHAR(50) NOT NULL,
    CONSTRAINT fk_price_resp FOREIGN KEY (respondent_id) REFERENCES Respondent (respondent_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS PlatformGenreSupport (
    platform_id INT NOT NULL,
    genre_id INT NOT NULL,
    PRIMARY KEY (platform_id, genre_id),
    CONSTRAINT fk_pg_plat FOREIGN KEY (platform_id) REFERENCES Platform (platform_id),
    CONSTRAINT fk_pg_genre FOREIGN KEY (genre_id) REFERENCES Genre (genre_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS RespondentPlatform (
    respondent_id INT NOT NULL,
    platform_id INT NOT NULL,
    PRIMARY KEY (respondent_id, platform_id),
    CONSTRAINT fk_rp_resp FOREIGN KEY (respondent_id) REFERENCES Respondent (respondent_id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_plat FOREIGN KEY (platform_id) REFERENCES Platform (platform_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS RespondentGenre (
    respondent_id INT NOT NULL,
    genre_id INT NOT NULL,
    PRIMARY KEY (respondent_id, genre_id),
    CONSTRAINT fk_rg_resp FOREIGN KEY (respondent_id) REFERENCES Respondent (respondent_id) ON DELETE CASCADE,
    CONSTRAINT fk_rg_genre FOREIGN KEY (genre_id) REFERENCES Genre (genre_id)
) ENGINE=InnoDB;

INSERT IGNORE INTO Occupation (occupation_name) VALUES 
('นักเรียน/นักศึกษา'), ('พนักงานบริษัทเอกชน'), ('ข้าราชการ/รัฐวิสาหกิจ'), ('ฟรีแลนซ์/ธุรกิจส่วนตัว'), ('ว่างงาน/พักงาน'), ('อื่นๆ');

INSERT IGNORE INTO SurveyChannel (channel_name) VALUES 
('Discord/Facebook กลุ่มเกม'), ('ไลน์ชั้นปี/มหาวิทยาลัย'), ('QR Code พื้นที่จริง'), ('ส่งต่อส่วนตัว');

INSERT IGNORE INTO Platform (platform_name) VALUES ('PC'), ('Console'), ('Mobile');

INSERT IGNORE INTO Genre (genre_code, genre_title, genre_description) VALUES 
('Action', '1. Action', 'FPS, Platformer, Fighting'),
('Adventure', '2. Adventure', 'Point & Click, Visual Novel'),
('Action-Adv', '3. Action-Adventure', 'Survival Horror, Stealth, Metroidvania'),
('RPG', '4. RPG', 'Soulslike, MMORPG, Turn-based RPG'),
('Strategy', '5. Strategy', 'RTS, Turn-base, MOBA, Tower Defence'),
('Simulation', '6. Simulation', 'City Builder / Tycoon, Farming Sim'),
('Sport-Racing', '7. Sport & Racing', 'Sport, Racing Simulator / Arcade'),
('Puzzle-Party', '8. Puzzle & Party Game', 'Puzzle Logic, Party / Co-op');
SQL;

if ($conn->multi_query($initSql)) {
    do {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    } while ($conn->more_results() && $conn->next_result());
}
?>