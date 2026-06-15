<?php
session_start();
if (!isset($_SESSION['register_number'])) {
    die("Unauthorized access");
}

$servername = "";
$username = "";
$password = "";
$dbname = "";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$block = $_GET['block'] ?? '';
$room = $_GET['room'] ?? '';

if (empty($block) || empty($room)) {
    die("Invalid request");
}

$sql = "SELECT * FROM hostel_survey WHERE block = ? AND room_number = ? ORDER BY name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $block, $room);
$stmt->execute();
$result = $stmt->get_result();
$roommates = $result->fetch_all(MYSQLI_ASSOC);
?>

<?php if (empty($roommates)): ?>
    <div class="alert alert-info text-center py-4">
        <i class="fas fa-door-open fa-3x mb-3 text-muted"></i>
        <h4>No occupants found</h4>
        <p class="text-muted">This room currently has no registered occupants</p>
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($roommates as $roommate): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="flex-shrink-0 me-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                    style="width: 60px; height: 60px; font-size: 1.5rem;">
                                    <?php echo strtoupper(substr($roommate['name'], 0, 1)); ?>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-1"><?php echo htmlspecialchars($roommate['name']); ?></h5>
                                <p class="text-muted mb-1"><?php echo htmlspecialchars($roommate['class']); ?></p>

                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <span class="badge bg-primary bg-opacity-10 text-primary">
                                        <?php echo htmlspecialchars($roommate['block']); ?>
                                    </span>
                                    <a href="https://oursrmap.purlyedit.in/view_profile?register_number=<?php echo urlencode($roommate['register_number']); ?>"
                                        class="btn btn-sm btn-outline-primary" target="_blank">
                                        <i class="fas fa-external-link-alt me-1"></i> View Profile
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
