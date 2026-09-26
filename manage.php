<?php
// manage.php
require_once "db_config.php";

$sql = "
    SELECT 
        r.respondent_id,
        r.gender,
        r.age,
        o.occupation_name,
        r.income_month,
        r.play_hours_week,
        r.peak_time,
        ph.min_price,
        ph.max_price,
        ph.price_sentiment,
        sc.channel_name,
        r.created_at
    FROM Respondent r
    LEFT JOIN Occupation o ON r.occupation_id = o.occupation_id
    LEFT JOIN SurveyChannel sc ON r.survey_channel_id = sc.channel_id
    LEFT JOIN PriceHistory ph ON r.respondent_id = ph.respondent_id
    ORDER BY r.respondent_id DESC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการข้อมูลแบบสำรวจ - CPE-443</title>
    <style>
        body { background-color: #0b1329; color: #f8fafc; font-family: 'Segoe UI', sans-serif; padding: 24px; margin: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        h1 { color: #38bdf8; font-size: 1.4rem; }
        a { color: #94a3b8; text-decoration: none; margin-left: 12px; }
        table { width: 100%; border-collapse: collapse; background-color: #152238; border-radius: 8px; overflow: hidden; font-size: 0.85rem; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #223249; }
        th { background-color: #0f172a; color: #38bdf8; }
        tr:hover { background-color: #1e293b; }
    </style>
</head>
<body>

<div class="header">
    <h1>📋 ตารางข้อมูลแบบสำรวจสด (Data Verification Table)</h1>
    <div>
        <a href="analytics.html">Developer Console</a>
        <a href="index.html">กลับหน้าแบบสอบถาม</a>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>เพศ</th>
            <th>อายุ</th>
            <th>อาชีพ</th>
            <th>รายได้</th>
            <th>ชม./สัปดาห์</th>
            <th>ช่วงเวลา</th>
            <th>Min (บาท)</th>
            <th>Max (บาท)</th>
            <th>ความรู้สึกราคา</th>
            <th>ช่องทาง</th>
            <th>เวลาบันทึก</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['respondent_id']) ?></td>
                    <td><?= htmlspecialchars($row['gender']) ?></td>
                    <td><?= htmlspecialchars($row['age']) ?></td>
                    <td><?= htmlspecialchars($row['occupation_name']) ?></td>
                    <td><?= number_format($row['income_month']) ?></td>
                    <td><?= htmlspecialchars($row['play_hours_week']) ?></td>
                    <td><?= htmlspecialchars($row['peak_time']) ?></td>
                    <td><?= number_format($row['min_price']) ?></td>
                    <td><?= number_format($row['max_price']) ?></td>
                    <td><?= htmlspecialchars($row['price_sentiment']) ?></td>
                    <td><?= htmlspecialchars($row['channel_name']) ?></td>
                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="12" style="text-align: center; padding: 24px; color: #94a3b8;">ยังไม่มีข้อมูลในระบบ</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>