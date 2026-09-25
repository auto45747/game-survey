<?php
require_once "db_config.php";

// จัดการลบข้อมูล (Delete)
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM Respondent WHERE respondent_id = $id");
    header("Location: manage.php");
    exit();
}

// อ่านข้อมูลผ่านการ Join ตารางที่แยก 5NF กลับมาแสดงผล (Read)
$sql = "SELECT r.respondent_id, r.gender, r.age, o.occupation_name, r.income_month, 
               r.play_hours_week, r.peak_time, p.platform_name, g.genre_title, 
               ph.min_price, ph.max_price, ph.price_sentiment, sc.channel_name, r.created_at
        FROM Respondent r
        JOIN Occupation o ON r.occupation_id = o.occupation_id
        JOIN SurveyChannel sc ON r.survey_channel_id = sc.channel_id
        LEFT JOIN PriceHistory ph ON r.respondent_id = ph.respondent_id
        LEFT JOIN RespondentPlatform rp ON r.respondent_id = rp.respondent_id
        LEFT JOIN Platform p ON rp.platform_id = p.platform_id
        LEFT JOIN RespondentGenre rg ON r.respondent_id = rg.respondent_id
        LEFT JOIN Genre g ON rg.genre_id = g.genre_id
        ORDER BY r.respondent_id DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>ระบบจัดการข้อมูลแบบสำรวจ (CRUD - Read/Delete)</title>
  <style>
    body { font-family: sans-serif; background: #0f172a; color: #f8fafc; padding: 24px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.88rem; background: #1e293b; }
    th, td { padding: 10px 12px; border: 1px solid #334155; text-align: left; }
    th { background: #38bdf8; color: #0f172a; }
    tr:hover { background: #24324a; }
    .btn { padding: 6px 12px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.8rem; }
    .btn-edit { background: #eab308; color: #000; }
    .btn-del { background: #ef4444; color: #fff; }
    .btn-home { background: #38bdf8; color: #0f172a; margin-right: 10px; }
  </style>
</head>
<body>
  <div style="display: flex; justify-content: space-between; align-items: center;">
    <h2>📊 รายการข้อมูลผู้ตอบแบบสำรวจ (5NF Normalized DB)</h2>
    <div>
      <a href="index.html" class="btn btn-home">หน้าแบบสอบถาม</a>
      <a href="analytics.html" class="btn btn-home">หน้า Developer</a>
    </div>
  </div>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>เพศ</th>
        <th>อายุ</th>
        <th>อาชีพ</th>
        <th>รายได้ (บาท)</th>
        <th>เวลาเล่น/สัปดาห์</th>
        <th>แพลตฟอร์ม</th>
        <th>แนวเกม</th>
        <th>ราคาแพงสุด (Target)</th>
        <th>ความรู้สึก</th>
        <th>ช่องทาง</th>
        <th>จัดการ</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $row['respondent_id'] ?></td>
            <td><?= htmlspecialchars($row['gender']) ?></td>
            <td><?= $row['age'] ?></td>
            <td><?= htmlspecialchars($row['occupation_name']) ?></td>
            <td><?= number_format($row['income_month'], 2) ?></td>
            <td><?= $row['play_hours_week'] ?> ชม.</td>
            <td><?= htmlspecialchars($row['platform_name'] ?? '-') ?></td>
            <td><?= htmlspecialchars($row['genre_title'] ?? '-') ?></td>
            <td><strong style="color: #38bdf8;"><?= number_format($row['max_price'], 2) ?></strong></td>
            <td><?= htmlspecialchars($row['price_sentiment'] ?? '-') ?></td>
            <td><?= htmlspecialchars($row['channel_name']) ?></td>
            <td>
              <a href="edit.php?id=<?= $row['respondent_id'] ?>" class="btn btn-edit">แก้ไข</a>
              <a href="manage.php?delete=<?= $row['respondent_id'] ?>" class="btn btn-del" onclick="return confirm('ยืนยันลบระเบียนนี้?')">ลบ</a>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="12" style="text-align: center;">ยังไม่มีข้อมูลในระบบ</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>