<?php
// admin/notifications.php
include("includes/sidebar.php");
include("../includes/config.php");
?>
<link rel="stylesheet" href="../students_frrdy/style.css">
<div class="dashboard-container" style="margin-left:240px;">
  <div class="dashboard-header">
    <h1>Message Board / Notifications</h1>
  </div>
  <div class="dashboard-stats">
    <form id="notifForm" style="background:#232526;padding:20px;border-radius:12px;margin-bottom:30px;">
      <label>Send To:</label>
      <select name="recipient_type" required>
        <option value="all">All Users</option>
        <option value="students">Students</option>
        <option value="lecturers">Lecturers</option>
      </select>
      <input type="text" name="message" placeholder="Notification message..." style="width:60%;margin:0 10px;" required>
      <button type="submit" class="action-btn"><i class="fas fa-paper-plane"></i> Send</button>
    </form>
    <h2 class="section-title">Sent Notifications</h2>
    <table style="width:100%;background:#232526;color:#fff;border-radius:12px;overflow:hidden;">
      <thead>
        <tr style="background:#4e54c8;color:#fff;">
          <th>Message</th>
          <th>Recipient</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $result = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 30");
        while($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?php echo htmlspecialchars($row['message']); ?></td>
          <td><?php echo htmlspecialchars($row['recipient_type'] ?? 'user'); ?></td>
          <td><?php echo htmlspecialchars($row['created_at']); ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
<script>
document.getElementById('notifForm').onsubmit = function(e) {
  e.preventDefault();
  const form = e.target;
  const data = new URLSearchParams(new FormData(form));
  fetch('api_notifications.php', {
    method: 'POST',
    body: data
  }).then(r=>r.json()).then(data=>{
    if(data.success) location.reload();
    else alert('Error: ' + (data.error||'Unknown'));
  });
};
</script>
