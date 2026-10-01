<?php
// edit.php
require_once "db_config.php";

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: manage.php");
    exit();
}

$error = "";

// บันทึกการแก้ไข (Update Logic)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $gender = trim($_POST['gender'] ?? '');
    $age = intval($_POST['age'] ?? 0);
    $occupation_id = intval($_POST['occupation_id'] ?? 0);
    $income_month = floatval($_POST['income_month'] ?? 0);
    $play_hours_week = floatval($_POST['play_hours_week'] ?? 0);
    $peak_time = trim($_POST['peak_time'] ?? '');
    $min_price = floatval($_POST['min_price'] ?? 0);
    $max_price = floatval($_POST['max_price'] ?? 0);
    $price_sentiment = trim($_POST['price_sentiment'] ?? '');
    $survey_channel_id = intval($_POST['survey_channel_id'] ?? 0);

    if ($max_price < $min_price) {
        $error = "ราคาสูงสุดต้องมากกว่าหรือเท่ากับราคาต่ำสุด";
    } else {
        $conn->begin_transaction();
        try {
            // อัปเดต Respondent
            $stmt1 = $conn->prepare("UPDATE Respondent SET gender = ?, age = ?, occupation_id = ?, income_month = ?, play_hours_week = ?, peak_time = ?, survey_channel_id = ? WHERE respondent_id = ?");
            $stmt1->bind_param("siiddsii", $gender, $age, $occupation_id, $income_month, $play_hours_week, $peak_time, $survey_channel_id, $id);
            $stmt1->execute();
            $stmt1->close();

            // อัปเดต PriceHistory
            $stmt2 = $conn->prepare("UPDATE PriceHistory SET min_price = ?, max_price = ?, price_sentiment = ? WHERE respondent_id = ?");
            $stmt2->bind_param("ddsi", $min_price, $max_price, $price_sentiment, $id);
            $stmt2->execute();
            $stmt2->close();

            $conn->commit();
            header("Location: manage.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = "เกิดข้อผิดพลาดในการบันทึก: " . $e->getMessage();
        }
    }
}

// ดึงข้อมูลเดิมมาแสดงในฟอร์ม
$stmt = $conn->prepare("
    SELECT r.*, ph.min_price, ph.max_price, ph.price_sentiment 
    FROM Respondent r 
    LEFT JOIN PriceHistory ph ON r.respondent_id = ph.respondent_id 
    WHERE r.respondent_id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    die("ไม่พบข้อมูลผู้ตอบรหัสนี้ <a href='manage.php'>กลับ</a>");
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขข้อมูลระเบียน ID: <?= $id ?></title>
    <style>
        body { background-color: #0b1329; color: #f8fafc; font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; padding: 30px; }
        .form-card { background-color: #152238; border: 1px solid #334155; padding: 28px; border-radius: 10px; width: 100%; max-width: 500px; }
        h2 { color: #38bdf8; margin-bottom: 20px; font-size: 1.3rem; }
        .form-group { margin-bottom: 14px; }
        label { display: block; font-size: 0.85rem; color: #94a3b8; margin-bottom: 6px; }
        input, select { width: 100%; padding: 10px; background-color: #0d1b2a; border: 1px solid #334155; color: white; border-radius: 6px; box-sizing: border-box; }
        .btn-group { display: flex; justify-content: space-between; margin-top: 20px; }
        button { padding: 10px 18px; border-radius: 6px; border: none; cursor: pointer; font-weight: bold; }
        .btn-save { background-color: #0284c7; color: white; }
        .btn-cancel { background-color: #475569; color: white; text-decoration: none; padding: 10px 18px; border-radius: 6px; font-size: 0.85rem; display: inline-block; }
        .err { color: #ef4444; margin-bottom: 12px; font-size: 0.9rem; }
    </style>
</head>
<body>

<div class="form-card">
    <h2>✏️ แก้ไขข้อมูลระเบียน ID: <?= $id ?></h2>
    <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>เพศ</label>
            <select name="gender" required>
                <option value="ชาย" <?= $data['gender'] === 'ชาย' ? 'selected' : '' ?>>ชาย</option>
                <option value="หญิง" <?= $data['gender'] === 'หญิง' ? 'selected' : '' ?>>หญิง</option>
                <option value="เพศทางเลือก / ไม่ระบุ" <?= $data['gender'] === 'เพศทางเลือก / ไม่ระบุ' ? 'selected' : '' ?>>เพศทางเลือก / ไม่ระบุ</option>
            </select>
        </div>

        <div class="form-group">
            <label>อายุ (ปี)</label>
            <input type="number" name="age" value="<?= htmlspecialchars($data['age']) ?>" required>
        </div>

        <div class="form-group">
            <label>อาชีพ</label>
            <select name="occupation_id" required>
                <option value="1" <?= $data['occupation_id'] == 1 ? 'selected' : '' ?>>นักเรียน/นักศึกษา</option>
                <option value="2" <?= $data['occupation_id'] == 2 ? 'selected' : '' ?>>พนักงานบริษัทเอกชน</option>
                <option value="3" <?= $data['occupation_id'] == 3 ? 'selected' : '' ?>>ข้าราชการ/รัฐวิสาหกิจ</option>
                <option value="4" <?= $data['occupation_id'] == 4 ? 'selected' : '' ?>>ฟรีแลนซ์/ธุรกิจส่วนตัว</option>
                <option value="5" <?= $data['occupation_id'] == 5 ? 'selected' : '' ?>>ว่างงาน/พักงาน</option>
                <option value="6" <?= $data['occupation_id'] == 6 ? 'selected' : '' ?>>อื่นๆ</option>
            </select>
        </div>

        <div class="form-group">
            <label>รายได้ต่อเดือน (บาท)</label>
            <input type="number" name="income_month" value="<?= htmlspecialchars($data['income_month']) ?>" required>
        </div>

        <div class="form-group">
            <label>ชั่วโมงเล่นเกมต่อสัปดาห์</label>
            <input type="number" step="0.5" name="play_hours_week" value="<?= htmlspecialchars($data['play_hours_week']) ?>" required>
        </div>

        <div class="form-group">
            <label>ช่วงเวลาเล่นเกม</label>
            <select name="peak_time" required>
                <option value="กลางวัน (06:00 - 18:00)" <?= $data['peak_time'] === 'กลางวัน (06:00 - 18:00)' ? 'selected' : '' ?>>กลางวัน (06:00 - 18:00)</option>
                <option value="กลางคืน (18:01 - 05:59)" <?= $data['peak_time'] === 'กลางคืน (18:01 - 05:59)' ? 'selected' : '' ?>>กลางคืน (18:01 - 05:59)</option>
            </select>
        </div>

        <div class="form-group">
            <label>ราคาต่ำสุด (บาท)</label>
            <input type="number" name="min_price" value="<?= htmlspecialchars($data['min_price']) ?>" required>
        </div>

        <div class="form-group">
            <label>ราคาสูงสุดที่จ่ายได้ (บาท)</label>
            <input type="number" name="max_price" value="<?= htmlspecialchars($data['max_price']) ?>" required>
        </div>

        <div class="form-group">
            <label>ความรู้สึกต่อระดับราคา</label>
            <select name="price_sentiment" required>
                <option value="ถูกเกินไป" <?= $data['price_sentiment'] === 'ถูกเกินไป' ? 'selected' : '' ?>>ถูกเกินไป</option>
                <option value="เหมาะสมคุ้มค่า" <?= $data['price_sentiment'] === 'เหมาะสมคุ้มค่า' ? 'selected' : '' ?>>เหมาะสมคุ้มค่า</option>
                <option value="ค่อนข้างแพง" <?= $data['price_sentiment'] === 'ค่อนข้างแพง' ? 'selected' : '' ?>>ค่อนข้างแพง</option>
                <option value="แพงเกินไปมาก" <?= $data['price_sentiment'] === 'แพงเกินไปมาก' ? 'selected' : '' ?>>แพงเกินไปมาก</option>
            </select>
        </div>

        <div class="form-group">
            <label>ช่องทางที่ได้รับแบบสำรวจ</label>
            <select name="survey_channel_id" required>
                <option value="1" <?= $data['survey_channel_id'] == 1 ? 'selected' : '' ?>>Discord/Facebook กลุ่มเกม</option>
                <option value="2" <?= $data['survey_channel_id'] == 2 ? 'selected' : '' ?>>ไลน์ชั้นปี/มหาวิทยาลัย</option>
                <option value="3" <?= $data['survey_channel_id'] == 3 ? 'selected' : '' ?>>QR Code พื้นที่จริง</option>
                <option value="4" <?= $data['survey_channel_id'] == 4 ? 'selected' : '' ?>>ส่งต่อส่วนตัว</option>
            </select>
        </div>

        <div class="btn-group">
            <a href="manage.php" class="btn-cancel">ยกเลิก</a>
            <button type="submit" class="btn-save">บันทึกการแก้ไข</button>
        </div>
    </form>
</div>

</body>
</html>