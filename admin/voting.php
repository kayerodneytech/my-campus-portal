<?php
// admin/voting.php
include("includes/sidebar.php");
include("../includes/config.php");

// Get voting status
$status = 'inactive';
$res = $conn->query("SELECT status FROM voting_status LIMIT 1");
if ($row = $res->fetch_assoc()) $status = $row['status'];


// Get candidates with post title
$candidates = $conn->query("SELECT c.*, p.title as post_title FROM src_candidates c LEFT JOIN src_positions p ON c.position_id = p.id ORDER BY p.title");

// Get posts
$posts = $conn->query("SELECT * FROM src_positions");

// Get vote counts
$votes = $conn->query("SELECT candidate_id, COUNT(*) as total FROM src_votes GROUP BY candidate_id");
$vote_counts = [];
while($v = $votes->fetch_assoc()) $vote_counts[$v['candidate_id']] = $v['total'];
?>
<link rel="stylesheet" href="../students_frrdy/style.css">
<div class="dashboard-container" style="margin-left:240px;">
  <div class="dashboard-header">
    <h1>Voting Management</h1>
    <form method="post" action="voting.php" style="display:inline-block;">
      <input type="hidden" name="toggle_voting" value="1">
      <button class="action-btn" style="background:<?php echo $status=='active'?'#ff6b6b':'#4caf50'; ?>;color:#fff;" type="submit">
        <?php echo $status=='active'?'Turn Voting Off':'Turn Voting On'; ?>
      </button>
    </form>
  </div>
  <div class="dashboard-stats">
    <h2 class="section-title">Upload SRC Candidates</h2>
    <form method="post" action="voting.php" enctype="multipart/form-data" style="margin-bottom:30px;">
      <input type="hidden" name="upload_candidates" value="1">
      <input type="file" name="csv" accept=".csv" required>
      <button class="action-btn" type="submit"><i class="fas fa-upload"></i> Upload CSV</button>
      <span style="color:#bdbdbd;font-size:0.9em;">Format: full_name,email,post</span>
    </form>
    <h2 class="section-title">Candidates & Vote Counts</h2>
    <table style="width:100%;background:#232526;color:#fff;border-radius:12px;overflow:hidden;">
      <thead>
        <tr style="background:#4e54c8;color:#fff;">
          <th>Post</th>
          <th>Name</th>
          <th>Email</th>
          <th>Votes</th>
        </tr>
      </thead>
      <tbody>
        <?php while($c = $candidates->fetch_assoc()): ?>
        <tr>
          <td><?php echo htmlspecialchars($c['post_title']); ?></td>
          <td><?php echo htmlspecialchars($c['full_name']); ?></td>
          <td><?php echo htmlspecialchars($c['email']); ?></td>
          <td><?php echo $vote_counts[$c['id']] ?? 0; ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <form method="post" action="voting.php" style="margin-top:30px;">
      <input type="hidden" name="send_winners" value="1">
      <button class="action-btn" type="submit"><i class="fas fa-trophy"></i> Send Winners Notification</button>
    </form>
  </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
<?php
// Handle voting toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_voting'])) {
    $new_status = ($status == 'active') ? 'inactive' : 'active';
    $conn->query("UPDATE voting_status SET status='$new_status'");
    header('Location: voting.php');
    exit;
}
// Handle candidate upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_candidates']) && isset($_FILES['csv'])) {
  $file = $_FILES['csv']['tmp_name'];
  if (($handle = fopen($file, 'r')) !== false) {
    while (($data = fgetcsv($handle)) !== false) {
      if (count($data) >= 3) {
        // Find or create position
        $pos_title = trim($data[2]);
        $pos = $conn->query("SELECT id FROM src_positions WHERE title='".$conn->real_escape_string($pos_title)."' LIMIT 1");
        if ($row = $pos->fetch_assoc()) {
          $pos_id = $row['id'];
        } else {
          $conn->query("INSERT INTO src_positions (title) VALUES ('".$conn->real_escape_string($pos_title)."')");
          $pos_id = $conn->insert_id;
        }
        $stmt = $conn->prepare("INSERT INTO src_candidates (full_name, email, position_id) VALUES (?, ?, ?)");
        $stmt->bind_param('ssi', $data[0], $data[1], $pos_id);
        $stmt->execute();
      }
    }
    fclose($handle);
  }
  header('Location: voting.php');
  exit;
}
// Handle send winners notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_winners'])) {
  // For each position, get candidate with max votes
  $winners = [];
  $posts = $conn->query("SELECT * FROM src_positions");
  while($p = $posts->fetch_assoc()) {
    $pos_id = $p['id'];
    $pos_title = $p['title'];
    $winner = $conn->query("SELECT c.full_name, p.title as post_title FROM src_candidates c LEFT JOIN src_votes v ON c.id=v.candidate_id LEFT JOIN src_positions p ON c.position_id=p.id WHERE c.position_id=$pos_id GROUP BY c.id ORDER BY COUNT(v.id) DESC LIMIT 1")->fetch_assoc();
    if ($winner) $winners[] = $winner;
  }
  $msg = "SRC Election Results: ";
  foreach($winners as $w) $msg .= $w['post_title'] . ': ' . $w['full_name'] . '; ';
  $stmt = $conn->prepare("INSERT INTO notifications (message, recipient_type, created_at) VALUES (?, 'all', NOW())");
  $stmt->bind_param('s', $msg);
  $stmt->execute();
  header('Location: voting.php');
  exit;
}
?>
