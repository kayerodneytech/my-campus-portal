<?php
// admin/lecturers.php
include("includes/sidebar.php");
include("../includes/config.php");
?>
<link rel="stylesheet" href="../students_frrdy/style.css">
<div class="dashboard-container" style="margin-left:240px;">
  <div class="dashboard-header">
    <h1>Manage Lecturers</h1>
    <button class="action-btn" onclick="showAddLecturerModal()"><i class="fas fa-user-plus"></i> Add Lecturer</button>
  </div>
  <div class="dashboard-stats">
    <table style="width:100%;background:#232526;color:#fff;border-radius:12px;overflow:hidden;">
      <thead>
        <tr style="background:#4e54c8;color:#fff;">
          <th>ID</th>
          <th>Name</th>
          <th>Email</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $result = $conn->query("SELECT id, full_name, email, status FROM lecturers");
        while($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?php echo $row['id']; ?></td>
          <td><?php echo htmlspecialchars($row['full_name']); ?></td>
          <td><?php echo htmlspecialchars($row['email']); ?></td>
          <td><?php echo $row['status'] == 'locked' ? '<span style=\'color:#ff6b6b\'>Locked</span>' : 'Active'; ?></td>
          <td>
            <button class="action-btn" onclick="editLecturer(<?php echo $row['id']; ?>)"><i class="fas fa-edit"></i></button>
            <button class="action-btn" onclick="removeLecturer(<?php echo $row['id']; ?>)"><i class="fas fa-trash"></i></button>
            <button class="action-btn" onclick="toggleLock(<?php echo $row['id']; ?>)"><i class="fas fa-lock"></i></button>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <!-- Modals for Add/Edit can be implemented here -->
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
<script>
function showAddLecturerModal() {
  const name = prompt('Enter lecturer name:');
  const email = prompt('Enter lecturer email:');
  if(name && email) {
    fetch('api_lecturers.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `action=add&full_name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}`
    }).then(r=>r.json()).then(data=>{
      if(data.success) location.reload();
      else alert('Error: ' + (data.error||'Unknown'));
    });
  }
}
function editLecturer(id) {
  const name = prompt('Enter new name:');
  const email = prompt('Enter new email:');
  if(name && email) {
    fetch('api_lecturers.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `action=edit&id=${id}&full_name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}`
    }).then(r=>r.json()).then(data=>{
      if(data.success) location.reload();
      else alert('Error: ' + (data.error||'Unknown'));
    });
  }
}
function removeLecturer(id) {
  if(confirm('Are you sure you want to remove this lecturer?')) {
    fetch('api_lecturers.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `action=remove&id=${id}`
    }).then(r=>r.json()).then(data=>{
      if(data.success) location.reload();
      else alert('Error: ' + (data.error||'Unknown'));
    });
  }
}
function toggleLock(id) {
  fetch('api_lecturers.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=toggle_lock&id=${id}`
  }).then(r=>r.json()).then(data=>{
    if(data.success) location.reload();
    else alert('Error: ' + (data.error||'Unknown'));
  });
}
</script>
