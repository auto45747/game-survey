<?php
require_once "db_config.php";

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $age = intval($_POST['age']);
    $income = floatval($_POST['income']);
    $playtime = floatval($_POST['playtime']);
    $max_price = floatval($_POST['max_price']);
    $sentiment = $_POST['sentiment'];

    // อัปเดตข้อมูลตาราง Respondent
    $u1 = $conn->prepare("UPDATE Respondent SET age=?, income_month=?, play_hours_week=? WHERE respondent_id=?");
    $u1->bind_param("iddi", $age, $income, $playtime, $id);
    $u1->execute();
    $u1->close();

    // อัปเดตข้อมูลตาราง PriceHistory
    $u2 = $conn->prepare("UPDATE PriceHistory SET max_price=?, price_sentiment=? WHERE respondent_id=?");
    $u2->bind_param("dsi", $max_price, $sentiment, $id);
    $u2->execute();
    $u2->close();

    header("Location: manage.php");
    exit();
}

$stmt = $conn->prepare("SELECT r.*, ph.max_price, ph.price_sentiment FROM Respondent r LEFT JOIN PriceHistory ph ON r.respondent_id = ph.respondent_id WHERE r.respondent_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
if (!$data) die("ไม่พบข้อมูล");
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>แก้ไขข้อมูลระเบียน #<?= $id ?></title>
  <style>
    body { font-family: sans-serif; background: #0f172a; color: #f8fafc; padding: 30px; }
    .card { max-width: 480px; margin: 0 auto; background: #1e293b; padding: 24px; border-radius: 8px; border: 1px solid #334155; }
    input, select { width: 100%; padding: 10px; margin: 8px 0 16px 0; background: #0f172a; border: 1px solid #334155; color: #fff; border-radius: 6px; box-sizing: border-box; }
    button { background: #38bdf8; color: #0f172a; padding: 10px 18px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
  </style>
</head>
<body>
  <div class="card">
    <h2>✏️ แก้ไขข้อมูลระเบียน ID: <?= $id ?></h2>
    <form method="POST">
      <label>อายุ (ปี):</label>
      <input type="number" name="age" value="<?= $data['age'] ?>" min="10" max="70" required>

      <label>รายได้ต่อเดือน (บาท):</label>
      <input type="number" name="income" value="<?= $data['income_month'] ?>" min="0" required>

      <label>เวลาเล่นต่อสัปดาห์ (ชม.):</label>
      <input type="number" name="playtime" value="<?= $data['play_hours_week'] ?>" min="0" max="168" required>

      <label>ราคาเกมแพงสุดที่เคยซื้อ (Target):</label>
      <input type="number" name="max_price" value="<?= $data['max_price'] ?>" min="0" required>

      <label>ความรู้สึกต่อราคา:</label>
      <select name="sentiment" required>
        <option value="คุ้มค่ามาก" <?= $data['price_sentiment'] == 'คุ้มค่ามาก' ? 'selected' : '' ?>>คุ้มค่ามาก</option>
        <option value="เหมาะสมกับคุณภาพ" <?= $data['price_sentiment'] == 'เหมาะสมกับคุณภาพ' ? 'selected' : '' ?>>เหมาะสมกับคุณภาพ</option>
        <option value="แพงเกินไป" <?= $data['price_sentiment'] == 'แพงเกินไป' ? 'selected' : '' ?>>แพงเกินไป</option>
      </select>

      <div style="display: flex; justify-content: space-between; margin-top: 10px;">
        <a href="manage.php" style="color: #94a3b8; text-decoration: none; align-self: center;">ยกเลิก</a>
        <button type="submit">บันทึกการแก้ไข</button>
      </div>
    </form>
  </div>
</body>
</html>