<?php
// manage.php
require_once "db_config.php";

$msg = "";

// จัดการการลบข้อมูล (Delete Action)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = intval($_GET['id']);
    
    // ลบ Respondent (เนื่องจากทำ ON DELETE CASCADE ตาราง PriceHistory, RespondentPlatform, RespondentGenre จะถูกลบตามอัตโนมัติ)
    $stmt = $conn->prepare("DELETE FROM Respondent WHERE respondent_id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        header("Location: manage.php?status=deleted");
        exit();
    } else {
        $msg = "เกิดข้อผิดพลาดในการลบ: " . $conn->error;
    }
    $stmt->close();
}

if (isset($_GET['status']) && $_GET['status'] === 'deleted') {
    $msg = "✅ ลบระเบียนข้อมูลสำเร็จเรียบร้อยแล้ว";
}

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
        a.nav-btn { color: #94a3b8; text-decoration: none; margin-left: 12px; font-size: 0.9rem; }
        a.nav-btn:hover { color: #38bdf8; }
        .alert { background-color: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 10px 16px; border-radius: 6px; margin-bottom: 16px; font-size: 0.9rem; }
        table { width: 100%; border-collapse: collapse; background-color: #152238; border-radius: 8px; overflow: hidden; font-size: 0.85rem; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #223249; }
        th { background-color: #0f172a; color: #38bdf8; }
        tr:hover { background-color: #1e293b; }
        .btn-action { display: inline-block; padding: 4px 8px; border-radius: 4px; text-decoration: none; font-size: 0.8rem; font-weight: 600; margin-right: 4px; }
        .btn-edit { background-color: #0284c7; color: white; }
        .btn-edit:hover { background-color: #0369a1; }
        .btn-del { background-color: #ef4444; color: white; }
        .btn-del:hover { background-color: #dc2626; }
    </style>
</head>
<body>

<div class="header">
    <h1>📋 ระบบจัดการและตรวจสอบข้อมูล (Admin CRUD Console)</h1>
    <div>
        <a href="analytics.html" class="nav-btn">Developer Console</a>
        <a href="index.html" class="nav-btn">กลับหน้าแบบสอบถาม</a>
    </div>
</div>

<?php if (!empty($msg)): ?>
    <div class="alert"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>เพศ</th>
            <th>อายุ</th>
            <th>อาชีพ</th>
            <th>รายได้ (บาท)</th>
            <th>ชม./สัปดาห์</th>
            <th>ช่วงเวลา</th>
            <th>Min (บาท)</th>
            <th>Max (บาท)</th>
            <th>ความรู้สึกราคา</th>
            <th>ช่องทาง</th>
            <th>จัดการข้อมูล (Actions)</th>
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
                    <td>
                        <a href="edit.php?id=<?= $row['respondent_id'] ?>" class="btn-action btn-edit">แก้ไข</a>
                        <a href="manage.php?action=delete&id=<?= $row['respondent_id'] ?>" class="btn-action btn-del" onclick="return confirm('ยืนยันที่จะลบข้อมูล ID: <?= $row['respondent_id'] ?> หรือไม่?');">ลบ</a>
                    </td>
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