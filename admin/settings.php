// admin/settings.php
include("includes/sidebar.php");
?>
<link rel="stylesheet" href="../students_frrdy/style.css">
<div class="dashboard-container" style="margin-left:240px;">
  <div class="dashboard-header">
    <h1>Settings</h1>
  </div>
  <div class="dashboard-stats">
    <p style="color:#bdbdbd;">Settings page coming soon. Add your admin settings here.</p>
  </div>
</div>

<?php
include("includes/sidebar.php");
include("../includes/config.php");
// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name = trim($_POST['site_name'] ?? '');
    $voting_status = $_POST['voting_status'] ?? '';
    if ($site_name) {
        $conn->query("UPDATE system_settings SET value='".$conn->real_escape_string($site_name)."' WHERE name='site_name'");
    }
    if ($voting_status) {
        $conn->query("UPDATE voting_status SET status='".$conn->real_escape_string($voting_status)."'");
    }
    header('Location: settings.php?success=1');
    exit;
}
// Fetch current settings
$site_name = '';
$res = $conn->query("SELECT value FROM system_settings WHERE name='site_name' LIMIT 1");
if ($row = $res->fetch_assoc()) $site_name = $row['value'];
$voting_status = '';
$res = $conn->query("SELECT status FROM voting_status LIMIT 1");
if ($row = $res->fetch_assoc()) $voting_status = $row['status'];
?>
<link rel="stylesheet" href="../students_frrdy/style.css">
<div class="dashboard-container" style="margin-left:240px;">
  <div class="dashboard-header">
    <h1>Settings</h1>
  </div>
  <div class="dashboard-stats">
    <?php if(isset($_GET['success'])): ?>
      <div style="color:#4caf50;margin-bottom:20px;">Settings updated successfully!</div>
    <?php endif; ?>
    <form method="post" style="background:#232526;padding:20px;border-radius:12px;max-width:500px;">
      <label>Site Name:</label>
      <input type="text" name="site_name" value="<?php echo htmlspecialchars($site_name); ?>" required style="width:100%;margin-bottom:15px;">
      <label>Voting Status:</label>
      <select name="voting_status" style="width:100%;margin-bottom:15px;">
        <option value="active" <?php if($voting_status=='active') echo 'selected'; ?>>Active</option>
        <option value="inactive" <?php if($voting_status=='inactive') echo 'selected'; ?>>Inactive</option>
      </select>
      <button class="action-btn" type="submit">Save Settings</button>
    </form>
  </div>
</div>
