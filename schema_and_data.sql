CREATE DATABASE IF NOT EXISTS `cpe443_game_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `cpe443_game_db`;

-- 1. Lookup Tables
CREATE TABLE `Occupation` (
    `occupation_id` INT AUTO_INCREMENT PRIMARY KEY,
    `occupation_name` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE `SurveyChannel` (
    `channel_id` INT AUTO_INCREMENT PRIMARY KEY,
    `channel_name` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE `Platform` (
    `platform_id` INT AUTO_INCREMENT PRIMARY KEY,
    `platform_name` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE `Genre` (
    `genre_id` INT AUTO_INCREMENT PRIMARY KEY,
    `genre_code` VARCHAR(20) NOT NULL UNIQUE,
    `genre_title` VARCHAR(100) NOT NULL,
    `genre_description` VARCHAR(255)
) ENGINE=InnoDB;

-- 2. Core Entities
CREATE TABLE `Respondent` (
    `respondent_id` INT AUTO_INCREMENT PRIMARY KEY,
    `gender` VARCHAR(20) NOT NULL,
    `age` INT NOT NULL,
    `occupation_id` INT NOT NULL,
    `income_month` DECIMAL(10, 2) NOT NULL,
    `play_hours_week` DECIMAL(5, 2) NOT NULL,
    `peak_time` VARCHAR(50) NOT NULL,
    `survey_channel_id` INT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_resp_occ` FOREIGN KEY (`occupation_id`) REFERENCES `Occupation` (`occupation_id`),
    CONSTRAINT `fk_resp_channel` FOREIGN KEY (`survey_channel_id`) REFERENCES `SurveyChannel` (`channel_id`),
    CONSTRAINT `chk_age` CHECK (`age` BETWEEN 10 AND 70),
    CONSTRAINT `chk_hours` CHECK (`play_hours_week` BETWEEN 0 AND 168)
) ENGINE=InnoDB;

CREATE TABLE `PriceHistory` (
    `history_id` INT AUTO_INCREMENT PRIMARY KEY,
    `respondent_id` INT NOT NULL UNIQUE,
    `min_price` DECIMAL(10, 2) NOT NULL,
    `max_price` DECIMAL(10, 2) NOT NULL,
    `price_sentiment` VARCHAR(50) NOT NULL,
    CONSTRAINT `fk_price_resp` FOREIGN KEY (`respondent_id`) REFERENCES `Respondent` (`respondent_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. 5NF Decomposition Tables
CREATE TABLE `PlatformGenreSupport` (
    `platform_id` INT NOT NULL,
    `genre_id` INT NOT NULL,
    PRIMARY KEY (`platform_id`, `genre_id`),
    CONSTRAINT `fk_pg_plat` FOREIGN KEY (`platform_id`) REFERENCES `Platform` (`platform_id`),
    CONSTRAINT `fk_pg_genre` FOREIGN KEY (`genre_id`) REFERENCES `Genre` (`genre_id`)
) ENGINE=InnoDB;

CREATE TABLE `RespondentPlatform` (
    `respondent_id` INT NOT NULL,
    `platform_id` INT NOT NULL,
    PRIMARY KEY (`respondent_id`, `platform_id`),
    CONSTRAINT `fk_rp_resp` FOREIGN KEY (`respondent_id`) REFERENCES `Respondent` (`respondent_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rp_plat` FOREIGN KEY (`platform_id`) REFERENCES `Platform` (`platform_id`)
) ENGINE=InnoDB;

CREATE TABLE `RespondentGenre` (
    `respondent_id` INT NOT NULL,
    `genre_id` INT NOT NULL,
    PRIMARY KEY (`respondent_id`, `genre_id`),
    CONSTRAINT `fk_rg_resp` FOREIGN KEY (`respondent_id`) REFERENCES `Respondent` (`respondent_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rg_genre` FOREIGN KEY (`genre_id`) REFERENCES `Genre` (`genre_id`)
) ENGINE=InnoDB;

-- Initial Seed Data
INSERT INTO `Occupation` (`occupation_name`) VALUES 
('นักเรียน/นักศึกษา'), ('พนักงานบริษัทเอกชน'), ('ข้าราชการ/รัฐวิสาหกิจ'), ('ฟรีแลนซ์/ธุรกิจส่วนตัว'), ('ว่างงาน/พักงาน'), ('อื่นๆ');

INSERT INTO `SurveyChannel` (`channel_name`) VALUES 
('Discord/Facebook กลุ่มเกม'), ('ไลน์ชั้นปี/มหาวิทยาลัย'), ('QR Code พื้นที่จริง'), ('ส่งต่อส่วนตัว');

INSERT INTO `Platform` (`platform_name`) VALUES ('PC'), ('Console'), ('Mobile');

INSERT INTO `Genre` (`genre_code`, `genre_title`, `genre_description`) VALUES 
('Action', '1. Action', 'FPS, Platformer, Fighting'),
('Adventure', '2. Adventure', 'Point & Click, Visual Novel'),
('Action-Adv', '3. Action-Adventure', 'Survival Horror, Stealth, Metroidvania'),
('RPG', '4. RPG', 'Soulslike, MMORPG, Turn-based RPG'),
('Strategy', '5. Strategy', 'RTS, Turn-base, MOBA, Tower Defence'),
('Simulation', '6. Simulation', 'City Builder / Tycoon, Farming Sim'),
('Sport-Racing', '7. Sport & Racing', 'Sport, Racing Simulator / Arcade'),
('Puzzle-Party', '8. Puzzle & Party Game', 'Puzzle Logic, Party / Co-op');

-- Platform & Genre Capabilities Seed
INSERT INTO `PlatformGenreSupport` (`platform_id`, `genre_id`) VALUES
(1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8), -- PC รองรับทุกแนว
(2,1),(2,2),(2,3),(2,4),(2,5),(2,6),(2,7),(2,8), -- Console รองรับทุกแนว
(3,1),(3,4),(3,5),(3,6),(3,8);                   -- Mobile เน้นบางหมวด