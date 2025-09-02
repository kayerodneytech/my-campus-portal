<?php
include_once '../../includes/session.php';
include_once '../../includes/config.php';
include_once '../../includes/header.php';

$lecturer_id = $_SESSION['lecturer_id'];
$assignments = [];

// --- Grade Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_submit'])) {
    $submission_id = $_POST['submission_id'];
    $grade = $_POST['grade'];
    $feedback = $_POST['feedback'];

    // Update grade in submissions table
    $stmt = $conn->prepare("UPDATE submissions SET grade=?, feedback=?, status='Graded', graded_at=NOW() WHERE id=?");
    $stmt->bind_param("ssi", $grade, $feedback, $submission_id);
    $stmt->execute();

    // Notify student
    $getStudent = $conn->prepare("SELECT student_id FROM submissions WHERE id=?");
    $getStudent->bind_param("i", $submission_id);
    $getStudent->execute();
    $student = $getStudent->get_result()->fetch_assoc();

    $msg = "Your assignment has been graded. Grade: $grade. Feedback: $feedback.";
    $notify = $conn->prepare("INSERT INTO notifications (student_id, title, message) VALUES (?, 'Assignment Graded', ?)");
    $notify->bind_param("is", $student['student_id'], $msg);
    $notify->execute();
}

// --- Fetch assignments ---
$sql = "
SELECT a.id AS assignment_id, a.title, c.name AS course_name, a.deadline
FROM assignments a
JOIN courses c ON a.course_id = c.id
JOIN lecturer_courses lc ON lc.course_id = c.id
WHERE lc.lecturer_id = ?
ORDER BY a.posted_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $lecturer_id);
$stmt->execute();
$assignments = $stmt->get_result();
?>

<h2>📑 Grading Panel</h2>

<?php if ($assignments->num_rows === 0): ?>
    <p>No assignments found.</p>
<?php else: ?>
    <?php while ($assignment = $assignments->fetch_assoc()): ?>
        <div style="margin-bottom:20px; background:#f9f9f9; padding:15px; border-radius:8px;">
            <h3><?= htmlspecialchars($assignment['title']) ?> - <?= htmlspecialchars($assignment['course_name']) ?></h3>
            <p>Deadline: <?= $assignment['deadline'] ?> | <a href="export_csv.php?id=<?= $assignment['assignment_id'] ?>">📤 Export Submissions</a></p>

            <?php
            $subs = $conn->prepare("
                SELECT s.*, st.fullname 
                FROM submissions s 
                JOIN students st ON s.student_id = st.id 
                WHERE s.assignment_id = ? 
                ORDER BY s.submitted_at DESC
            ");
            $subs->bind_param("i", $assignment['assignment_id']);
            $subs->execute();
            $results = $subs->get_result();
            ?>

            <?php if ($results->num_rows === 0): ?>
                <p>No submissions yet.</p>
            <?php else: ?>
                <table width="100%" cellpadding="8" cellspacing="0" border="1">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Submitted At</th>
                            <th>Status</th>
                            <th>File</th>
                            <th>Grade</th>
                            <th>Feedback</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($sub = $results->fetch_assoc()): ?>
                            <tr>
                                <form method="POST">
                                    <input type="hidden" name="submission_id" value="<?= $sub['id'] ?>">
                                    <td><?= htmlspecialchars($sub['fullname']) ?></td>
                                    <td><?= $sub['submitted_at'] ?></td>
                                    <td><?= $sub['status'] ?></td>
                                    <td><a href="<?= $sub['file_path'] ?>" target="_blank">📎 File</a></td>
                                    <td><input type="text" name="grade" value="<?= $sub['grade'] ?>" required></td>
                                    <td><input type="text" name="feedback" value="<?= htmlspecialchars($sub['feedback']) ?>"></td>
                                    <td><button type="submit" name="grade_submit">✅ Grade</button></td>
                                </form>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endwhile; ?>
<?php endif; ?>

<?php include_once '../../includes/footer.php'; ?>