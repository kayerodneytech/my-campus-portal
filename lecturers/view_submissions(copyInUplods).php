<?php
include_once '../../includes/session.php';
include_once '../../includes/config.php';
include_once '../../includes/header.php';

     // grading logic

/* if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_submit'])) {
    $submission_id = $_POST['submission_id'];
    $grade = $_POST['grade'];
    $feedback = $_POST['feedback'];

    $grade_stmt = $conn->prepare("
        UPDATE submissions SET grade = ?, feedback = ?, graded_at = NOW(), status = 'Graded'
        WHERE id = ?
    ");
    $grade_stmt->bind_param("ssi", $grade, $feedback, $submission_id);
    $grade_stmt->execute();
}

*/




       // better logic 


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_submit'])) {
    $submission_id = $_POST['submission_id'];
    $grade = $_POST['grade'];
    $feedback = $_POST['feedback'];

    // Update grade
    $grade_stmt = $conn->prepare("
        UPDATE submissions SET grade = ?, feedback = ?, graded_at = NOW(), status = 'Graded'
        WHERE id = ?
    ");
    $grade_stmt->bind_param("ssi", $grade, $feedback, $submission_id);
    $grade_stmt->execute();

    // Get student ID
    $get_student = $conn->prepare("SELECT student_id FROM submissions WHERE id = ?");
    $get_student->bind_param("i", $submission_id);
    $get_student->execute();
    $res = $get_student->get_result()->fetch_assoc();
    $student_id = $res['student_id'];

    // Insert notification
    $notify = $conn->prepare("
        INSERT INTO notifications (student_id, title, message)
        VALUES (?, 'New Grade Available', ?)
    ");
    $msg = "Your assignment has been graded. Grade: $grade. Feedback: $feedback.";
    $notify->bind_param("is", $student_id, $msg);
    $notify->execute();
}


         // end of logic 

$lecturer_id = $_SESSION['lecturer_id'];
$assignments = [];

// Get assignments by this lecturer (linked via courses they own)
$sql = "
SELECT a.id AS assignment_id, a.title, a.deadline, c.name AS course_name
FROM assignments a
JOIN courses c ON a.course_id = c.id
JOIN lecturer_courses lc ON lc.course_id = c.id
WHERE lc.lecturer_id = ?
ORDER BY a.posted_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $lecturer_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $assignments[] = $row;
}
?>

<h2>📥 Assignment Submissions</h2>

<?php if (empty($assignments)): ?>
    <p>No assignments posted yet.</p>
<?php else: ?>
    <?php foreach ($assignments as $a): ?>
        <div style="margin-bottom: 25px; border: 1px solid #ccc; border-radius:10px; padding:15px; background:#f4f4f4;">
            <h3><?= htmlspecialchars($a['title']) ?> – <?= $a['course_name'] ?></h3>
            <p><strong>Deadline:</strong> <?= $a['deadline'] ?>
            <a href="export_csv.php?id=<?= $a['assignment_id'] ?>" target="_blank">📤 Export CSV</a>
            </p>

            <?php
            // Fetch student submissions for this assignment
            $sub_stmt = $conn->prepare("
                SELECT s.*, st.fullname
                FROM submissions s
                JOIN students st ON s.student_id = st.id
                WHERE s.assignment_id = ?
                ORDER BY s.submitted_at DESC
            ");
            $sub_stmt->bind_param("i", $a['assignment_id']);
            $sub_stmt->execute();
            $subs = $sub_stmt->get_result();
            ?>

            <?php if ($subs->num_rows > 0): ?>
                <table width="100%" border="1" cellpadding="6" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Submitted On</th>
                            <th>Status</th>
                            <th>Download</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($s = $subs->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($s['fullname']) ?></td>
                                <td><?= $s['submitted_at'] ?></td>
                                <td><?= $s['status'] ?></td>
                                <td><a href="<?= $s['file_path'] ?>" target="_blank">📎 View File</a></td>
                            </tr>
                            
                            
                            
              // inserted code for grading form
                            
                            <tr>
    
    <td><?= $s['submitted_at'] ?></td>
    <td><?= $s['status'] ?></td>
    <td><a href="<?= $s['file_path'] ?>" target="_blank">📎 View File</a></td>
</tr>
<tr>
    <td colspan="4">
        <form method="POST" style="display:flex; gap:10px;">
            <input type="hidden" name="submission_id" value="<?= $s['id'] ?>">
            <input type="text" name="grade" placeholder="Grade" value="<?= $s['grade'] ?? '' ?>" required>
            <input type="text" name="feedback" placeholder="Feedback" value="<?= htmlspecialchars($s['feedback'] ?? '') ?>">
            <button type="submit" name="grade_submit">✅ Save</button>
        </form>
    </td>
</tr>
<!-- end of insert -->




                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No submissions yet.</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include_once '../../includes/footer.php'; ?>
