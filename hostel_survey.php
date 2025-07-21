<?php
session_start();


$servername = "";
$username = "";
$password = "";
$dbname = "";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$register_number = $_SESSION['register_number'];
$sql = "SELECT * FROM students WHERE register_number = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $register_number);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$check_sql = "SELECT * FROM hostel_survey WHERE register_number = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $register_number);
$check_stmt->execute();
$survey_result = $check_stmt->get_result();
$has_submitted = $survey_result->num_rows > 0;
$survey_data = $has_submitted ? $survey_result->fetch_assoc() : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_hostel_info'])) {
    $block = $_POST['block'];
    $room_number = $_POST['room_number'];

    if ($has_submitted) {
        $update_sql = "UPDATE hostel_survey SET block = ?, room_number = ? WHERE register_number = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sss", $block, $room_number, $register_number);
        $update_stmt->execute();
    } else {
        $insert_sql = "INSERT INTO hostel_survey (register_number, name, email, class, block, room_number) VALUES (?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ssssss", $register_number, $user['name'], $user['email'], $user['class'], $block, $room_number);
        $insert_stmt->execute();
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$roommates = [];
if ($has_submitted) {
    $roommate_sql = "SELECT * FROM hostel_survey WHERE block = ? AND room_number = ? AND register_number != ?";
    $roommate_stmt = $conn->prepare($roommate_sql);
    $roommate_stmt->bind_param("sss", $survey_data['block'], $survey_data['room_number'], $register_number);
    $roommate_stmt->execute();
    $roommate_result = $roommate_stmt->get_result();
    $roommates = $roommate_result->fetch_all(MYSQLI_ASSOC);
}

$blocks_sql = "SELECT DISTINCT block FROM hostel_survey ORDER BY block";
$blocks_result = $conn->query($blocks_sql);
$all_blocks = $blocks_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel Roommate Finder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }

        body {
            background-color: #f5f7ff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            border: none;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-weight: 600;
            padding: 15px 20px;
            border-bottom: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: var(--accent-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin: 0 auto 15px;
        }

        .room-number-badge {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 20px;
        }

        .nav-pills .nav-link.active {
            background-color: var(--primary-color);
        }

        .nav-pills .nav-link {
            color: var(--dark-color);
            font-weight: 500;
            margin-right: 10px;
            border-radius: 8px;
        }

        .thankyou-banner {
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.1), rgba(63, 55, 201, 0.1));
            border-left: 4px solid var(--primary-color);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .view-profile-btn {
            background-color: white;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
            transition: all 0.3s;
        }

        .view-profile-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(72, 149, 239, 0.25);
        }

        .roommate-card {
            border-left: 4px solid var(--accent-color);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
    </style>
</head>

<body>

    <div class="folder-container" style="font-family: Arial, sans-serif; margin: 20px;">


        <div class="header" style="position: fixed; top: 0; left: 0; width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 10px 15px; background-color: #000000; z-index: 1000; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
            <!-- Back Icon on the Left -->
            <a href="javascript:history.back()" style="color: #ffffff; text-decoration: none; font-size: 18px;">
                <i class="fas fa-arrow-left"></i> Room Mates
            </a>

            <!-- Three Icons on the Right -->
            <div style="display: flex; gap: 10px; align-items: center; padding-right: 5px;">
                <!-- Share Icon -->
                <a href="#" onclick="shareRoommateForm()" id="share-btn"
                    style="color: #ffffff; text-decoration: none; font-size: 18px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 50%; background-color: #1e1e1e; border: 1px solid rgba(255,255,255,0.2); transition: all 0.3s ease;">
                    <i class="fas fa-share-alt" style="pointer-events: none;"></i>
                </a>

                <!-- Edit Profile Icon -->
                <a href="../profile.php"
                    style="color: #ffffff; text-decoration: none; font-size: 18px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 50%; background-color: #1e1e1e; border: 1px solid rgba(255,255,255,0.2); transition: all 0.3s ease;">
                    <i class="fas fa-user-edit" style="pointer-events: none;"></i>
                </a>

                <!-- WhatsApp Icon -->
                <a href="https://whatsapp.com/channel/0029VbAKw2f1Hspre2meUQ2c"
                    target="_blank"
                    style="color: #ffffff; text-decoration: none; font-size: 18px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 50%; background-color: #1e1e1e; border: 1px solid rgba(255,255,255,0.2); transition: all 0.3s ease;">
                    <i class="fab fa-whatsapp" style="color: #25D366; pointer-events: none;"></i>
                </a>

            </div>
        </div>

        <!-- WhatsApp Channel Bottom Bar (Slightly Taller with Icon Animation) -->
        <div style="position: fixed; bottom: 0; left: 0; width: 100%; background: linear-gradient(to right, #075E54, #128C7E); color: white; padding: 10px 16px; display: flex; align-items: center; justify-content: space-between; font-family: sans-serif; font-size: 14px; z-index: 999; box-shadow: 0 -2px 5px rgba(0,0,0,0.3); height: 50px;">

            <!-- Animated WhatsApp Icon and Text -->
            <div style="display: flex; align-items: center; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; flex: 1; margin-right: 10px;">
                <i class="fab fa-whatsapp" style="margin-right: 8px; font-size: 18px; animation: pulse 1.5s infinite;"></i>
                <span style="overflow: hidden; text-overflow: ellipsis;">Follow our <strong>CONNECT SRMAP</strong> WhatsApp channel for more updates</span>
            </div>

            <!-- Join Now Button -->
            <a href="https://whatsapp.com/channel/0029VbAKw2f1Hspre2meUQ2c" target="_blank"
                style="background: white; color: #128C7E; font-weight: bold; padding: 6px 14px; border-radius: 18px; text-decoration: none; font-size: 13px; white-space: nowrap; box-shadow: 0 1px 3px rgba(0,0,0,0.2);">
                Join Now
            </a>
        </div>

        <!-- FontAwesome for Icon -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <!-- Animation Styles -->
        <style>
            @keyframes pulse {
                0% {
                    transform: scale(1);
                    opacity: 1;
                }

                50% {
                    transform: scale(1.3);
                    opacity: 0.7;
                }

                100% {
                    transform: scale(1);
                    opacity: 1;
                }
            }
        </style>



        <!-- =========================  Main Loading Page ========================-->

        <div id="main-profile-loading-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #000; display: flex; z-index: 99999; align-items: center; justify-content: center; color: #fff; flex-direction: column;">
            <!-- Spinner at the top -->
            <div id="main-profile-loading-spinner" style="display: flex; align-items: center; gap: 15px; font-size: 18px; margin-bottom: 20px;">
                <div class="spinner" style="width: 50px; height: 50px; border: 4px solid rgba(255, 255, 255, 0.3); border-top: 4px solid #fff; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            </div>

            <!-- "Opening your profile" text at the bottom -->
            <div style="font-size: 18px; text-align: center; padding: 10px 20px;">
                <span>Loading Room Mates...</span>
            </div>
        </div>

        <!-- Add CSS for Spinner Animation -->
        <style>
            @keyframes spin {
                0% {
                    transform: rotate(0deg);
                }

                100% {
                    transform: rotate(360deg);
                }
            }
        </style>

        <!-- JavaScript to show the loading overlay until the page is ready -->
        <script>
            // Show the loading overlay immediately
            document.getElementById('main-profile-loading-overlay').style.display = 'flex';

            // Prevent scrolling in the background
            document.body.style.overflow = 'hidden';

            // Hide the loading overlay after content is ready (or after a delay)
            window.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    // Hide the loading modal
                    document.getElementById('main-profile-loading-overlay').style.display = 'none';

                    // Allow scrolling again once the modal is hidden
                    document.body.style.overflow = 'auto';
                }, 1000); // Adjust time as needed
            });
        </script>



        <br>



        <div class="container py-4">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="fw-bold" style="color: var(--primary-color);">Hostel Roommate Finder</h2>
                    <p class="text-muted">Connect with your roommates and block mates</p>
                </div>
            </div>

            <!-- Hostel Information Form -->
            <div class="row mb-5">
                <div class="col-lg-8 mx-auto">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><?php echo $has_submitted ? 'Update Hostel Details' : 'Share Your Hostel Details'; ?></span>
                            <?php if ($has_submitted): ?>
                                <span class="badge bg-white text-primary">Submitted</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if ($has_submitted): ?>
                                <div class="thankyou-banner d-flex justify-content-between align-items-center mb-4">
                                    <div>
                                        <i class="fas fa-check-circle me-2 text-success"></i>
                                        Thank you for sharing your hostel details!
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary" onclick="toggleEditForm()">
                                        <i class="fas fa-edit me-1"></i> Edit Details
                                    </button>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Full Name</label>
                                            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($survey_data['name']); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Register Number</label>
                                            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($register_number); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Class</label>
                                            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($survey_data['class']); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Email</label>
                                            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($survey_data['email']); ?>" readonly>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <form id="hostelForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" <?php echo $has_submitted ? 'style="display: none;"' : ''; ?>>
                                <input type="hidden" name="name" value="<?php echo htmlspecialchars($user['name']); ?>">
                                <input type="hidden" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                                <input type="hidden" name="class" value="<?php echo htmlspecialchars($user['class']); ?>">

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="block" class="form-label">Hostel Block</label>
                                            <select id="block" name="block" class="form-select" required>
                                                <option value="" disabled <?php echo !$has_submitted ? 'selected' : ''; ?>>Select your block</option>
                                                <option value="GANGA" <?php echo ($has_submitted && $survey_data['block'] == 'GANGA') ? 'selected' : ''; ?>>GANGA</option>
                                                <option value="VEDAVATHI" <?php echo ($has_submitted && $survey_data['block'] == 'VEDAVATHI') ? 'selected' : ''; ?>>VEDAVATHI</option>
                                                <option value="GODAVARI" <?php echo ($has_submitted && $survey_data['block'] == 'GODAVARI') ? 'selected' : ''; ?>>GODAVARI</option>
                                                <option value="NARMADA" <?php echo ($has_submitted && $survey_data['block'] == 'NARMADA') ? 'selected' : ''; ?>>NARMADA</option>
                                                <option value="YAMUNA" <?php echo ($has_submitted && $survey_data['block'] == 'YAMUNA') ? 'selected' : ''; ?>>YAMUNA</option>
                                                <option value="KRISHNA" <?php echo ($has_submitted && $survey_data['block'] == 'KRISHNA') ? 'selected' : ''; ?>>KRISHNA</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="room_number" class="form-label">Room Number</label>
                                            <input type="text" class="form-control" id="room_number" name="room_number"
                                                value="<?php echo $has_submitted ? htmlspecialchars($survey_data['room_number']) : ''; ?>"
                                                pattern="\d{1,4}" title="Room number should be 1-4 digits" required>
                                            <small class="text-muted">Enter your 1-4 digit room number</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid mt-3">
                                    <button type="submit" name="submit_hostel_info" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> <?php echo $has_submitted ? 'Update Details' : 'Submit Details'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Your Roommates Section -->
            <?php if ($has_submitted): ?>
                <div class="row mb-5">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header" style="background-color: #343a40; color: white;">
                                <i class="fas fa-users me-2"></i> Your Roommates in <?php echo htmlspecialchars($survey_data['block']); ?> - Room <?php echo htmlspecialchars($survey_data['room_number']); ?>
                            </div>
                            <div class="card-body">
                                <?php if (empty($roommates)): ?>
                                    <div class="text-center py-5">
                                        <!-- No Roommates Icon -->
                                        <div class="mb-3" style="font-size: 40px; color: #aaa;">
                                            <i class="fas fa-user-friends"></i>
                                        </div>

                                        <!-- No Roommates Message -->
                                        <h5 class="mb-2" style="color: #666;">No roommates found yet</h5>
                                        <p class="text-muted mb-4">Your roommates will appear here once they submit their details.</p>

                                        <!-- Share Section -->
                                        <div class="share-box mx-auto p-3" style="
        background-color: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 10px;
        max-width: 450px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    ">
                                            <div class="d-flex align-items-start mb-2">
                                                <i class="fas fa-share-alt me-2 mt-1" style="color: #007bff; font-size: 18px;"></i>
                                                <div>
                                                    <strong style="color: #333;">Share this form in your srmap groups</strong>
                                                    <p class="mb-1" style="font-size: 13px; color: #666;">Click below to share the survey form directly.</p>
                                                </div>
                                            </div>

                                            <!-- Link Display -->
                                            <div class="px-3 py-2 mb-2" style="
            background-color: #fff;
            border: 1px dashed #ccc;
            border-radius: 6px;
            font-size: 13px;
            color: #333;
            word-break: break-all;
        ">
                                                https://oursrmap.purlyedit.in/Survey/hostel_survey.php
                                            </div>

                                            <!-- Share Button -->
                                            <button onclick="shareRoommateForm()" class="btn btn-primary w-100" style="
            font-size: 14px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        ">
                                                <i class="fas fa-paper-plane"></i> Share Now
                                            </button>
                                        </div>
                                    </div>

                                    <script>
                                        function shareRoommateForm() {
                                            const shareData = {
                                                title: "Hostel Roommate Survey",
                                                text: "Hey! Fill out this SRM hostel survey form so we can know our roommates' details:",
                                                url: "https://oursrmap.purlyedit.in/Survey/hostel_survey.php"
                                            };

                                            if (navigator.share) {
                                                navigator.share(shareData)
                                                    .then(() => console.log('Shared successfully'))
                                                    .catch(err => console.log('Share failed:', err));
                                            } else {
                                                alert("Sharing is not supported on your device. You can copy the link manually.");
                                            }
                                        }
                                    </script>

                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($roommates as $roommate): ?>
                                            <div class="col-md-6 col-lg-4 mb-3">
                                                <div class="card h-100" style="border: 1px solid #ddd; border-radius: 10px;">
                                                    <div class="card-body d-flex justify-content-between align-items-center" style="padding: 10px 15px;">

                                                        <!-- Left: Avatar + Name & Class -->
                                                        <div class="d-flex align-items-center">
                                                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                                                style="width: 55px; height: 55px; background-color: #007bff; color: white; font-size: 22px; font-weight: bold; margin-right: 12px;">
                                                                <?php echo strtoupper(substr($roommate['name'], 0, 1)); ?>
                                                            </div>
                                                            <div>
                                                                <div style="font-weight: 600; font-size: 16px;">
                                                                    <?php echo htmlspecialchars($roommate['name']); ?>
                                                                </div>
                                                                <div style="font-size: 14px; color: #666;">
                                                                    <?php echo htmlspecialchars($roommate['class']); ?>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Right: View Profile Button -->
                                                        <a href="https://oursrmap.purlyedit.in/view_profile?register_number=<?php echo urlencode($roommate['register_number']); ?>"
                                                            target="_blank"
                                                            style="font-size: 14px; text-decoration: none; color: #007bff; display: flex; align-items: center;">
                                                            <i class="fas fa-eye me-1"></i> View
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>


            <?php
            // Total records in hostel_survey
            $total_sql = "SELECT COUNT(*) AS total FROM hostel_survey";
            $total_result = $conn->query($total_sql);
            $total_row = $total_result->fetch_assoc();
            $total_submitted = $total_row['total'];

            // Count of rooms with 2 or more students in the same block & room
            $matched_sql = "SELECT COUNT(*) AS matched FROM (
    SELECT block, room_number 
    FROM hostel_survey 
    GROUP BY block, room_number 
    HAVING COUNT(*) >= 2
) AS matches";
            $matched_result = $conn->query($matched_sql);
            $matched_row = $matched_result->fetch_assoc();
            $matched_count = $matched_row['matched'];
            ?>

            <!-- Summary Section -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card text-white bg-primary">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title mb-1">Total Submitted Records</h5>
                                <h3 class="mb-0"><?php echo $total_submitted; ?></h3>
                            </div>
                            <i class="fas fa-file-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card text-white bg-success">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title mb-1">Matched Roommates Found</h5>
                                <h3 class="mb-0"><?php echo $matched_count; ?></h3>
                            </div>
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Block Wise Room Numbers -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header" style="background-color: #343a40; color: #fff;">
                            <i class="fas fa-building me-2"></i> Block Wise Room Numbers
                        </div>
                        <div class="card-body">
                            <!-- Scrollable Tabs -->
                            <div style="overflow-x: auto; white-space: nowrap; padding-bottom: 10px;">
                                <ul class="nav nav-pills mb-3" id="blockTabs" role="tablist" style="display: inline-flex;">
                                    <?php foreach ($all_blocks as $index => $block): ?>
                                        <li class="nav-item" role="presentation" style="flex: 0 0 auto; margin-right: 6px;">
                                            <button class="nav-link <?php echo $index === 0 ? 'active' : ''; ?>"
                                                id="block-<?php echo htmlspecialchars($block['block']); ?>-tab"
                                                data-bs-toggle="pill"
                                                data-bs-target="#block-<?php echo htmlspecialchars($block['block']); ?>"
                                                type="button" role="tab"
                                                style="min-width: 100px; text-align: center;">
                                                <?php echo htmlspecialchars($block['block']); ?>
                                            </button>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <!-- Tab Content -->
                            <div class="tab-content" id="blockTabsContent">
                                <?php foreach ($all_blocks as $index => $block): ?>
                                    <div class="tab-pane fade <?php echo $index === 0 ? 'show active' : ''; ?>"
                                        id="block-<?php echo htmlspecialchars($block['block']); ?>"
                                        role="tabpanel">

                                        <?php
                                        $rooms_sql = "
                                SELECT room_number, COUNT(*) AS student_count 
                                FROM hostel_survey 
                                WHERE block = ? 
                                GROUP BY room_number 
                                HAVING room_number+0 BETWEEN 10 AND 9999 AND COUNT(*) > 0
                                ORDER BY student_count DESC, room_number+0 ASC
                            ";
                                        $rooms_stmt = $conn->prepare($rooms_sql);
                                        $rooms_stmt->bind_param("s", $block['block']);
                                        $rooms_stmt->execute();
                                        $rooms_result = $rooms_stmt->get_result();
                                        $rooms = $rooms_result->fetch_all(MYSQLI_ASSOC);
                                        ?>

                                        <?php if (empty($rooms)): ?>
                                            <div style="text-align: center; padding: 30px 0; font-size: 18px; color: #999;">
                                                <i class="fas fa-info-circle me-2"></i>No rooms found for <strong><?php echo htmlspecialchars($block['block']); ?></strong>
                                            </div>
                                        <?php else: ?>

                                            <!-- Search and Filter Section -->
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <div class="input-group" style="width: 300px;">
                                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                                    <input type="text" class="form-control room-search-input"
                                                        placeholder="Search Room Number..."
                                                        data-target="#block-<?php echo htmlspecialchars($block['block']); ?> .room-card">
                                                </div>

                                                <!-- Filter Button -->
                                                <div class="dropdown">
                                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                                                        id="filterDropdown-<?php echo htmlspecialchars($block['block']); ?>"
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-filter me-1"></i> Filter
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-end p-3" style="width: 300px;">
                                                        <h6 class="dropdown-header">Filter Options</h6>

                                                        <!-- Occupancy Filter -->
                                                        <div class="mb-2">
                                                            <label class="form-label">Occupancy</label>
                                                            <select class="form-select form-select-sm occupancy-filter">
                                                                <option value="all">All Rooms</option>
                                                                <option value="1">Single Occupancy</option>
                                                                <option value="2">Double Occupancy</option>
                                                                <option value="3">Triple Occupancy</option>
                                                                <option value="4+">4+ Occupancy</option>
                                                            </select>
                                                        </div>

                                                        <!-- Room Number Range -->
                                                        <div class="row mb-2">
                                                            <div class="col-6">
                                                                <label class="form-label">From</label>
                                                                <input type="number" class="form-control form-control-sm room-min" placeholder="Min">
                                                            </div>
                                                            <div class="col-6">
                                                                <label class="form-label">To</label>
                                                                <input type="number" class="form-control form-control-sm room-max" placeholder="Max">
                                                            </div>
                                                        </div>

                                                        <!-- Sort Options -->
                                                        <div class="mb-2">
                                                            <label class="form-label">Sort By</label>
                                                            <select class="form-select form-select-sm sort-filter">
                                                                <option value="number-asc">Room Number (Asc)</option>
                                                                <option value="number-desc">Room Number (Desc)</option>
                                                                <option value="occupancy-asc">Occupancy (Low to High)</option>
                                                                <option value="occupancy-desc" selected>Occupancy (High to Low)</option>
                                                            </select>
                                                        </div>

                                                        <div class="d-flex justify-content-between mt-2">
                                                            <button class="btn btn-sm btn-outline-danger reset-filters">
                                                                <i class="fas fa-times me-1"></i> Reset
                                                            </button>
                                                            <button class="btn btn-sm btn-primary apply-filters">
                                                                <i class="fas fa-check me-1"></i> Apply
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row room-container">
                                                <?php foreach ($rooms as $room): ?>
                                                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3 room-card"
                                                        data-room-number="<?php echo htmlspecialchars($room['room_number']); ?>"
                                                        data-occupancy="<?php echo $room['student_count']; ?>">
                                                        <div class="card" style="border: 1px solid #ddd; border-radius: 10px; height: 100%;">
                                                            <div class="card-body d-flex justify-content-between align-items-center" style="padding: 10px 15px;">
                                                                <div style="font-weight: bold; font-size: 16px; color: #333;">
                                                                    <i class="fas fa-door-open me-1 text-secondary"></i>
                                                                    <?php echo htmlspecialchars($room['room_number']); ?>
                                                                    <span style="font-size: 12px; color: #888;">(<?php echo $room['student_count']; ?> Added)</span>
                                                                </div>
                                                                <button class="btn btn-sm btn-outline-primary view-roommates"
                                                                    data-block="<?php echo htmlspecialchars($block['block']); ?>"
                                                                    data-room="<?php echo htmlspecialchars($room['room_number']); ?>"
                                                                    style="font-size: 13px; padding: 4px 8px;">
                                                                    <i class="fas fa-eye me-1"></i> View
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- JavaScript for Room Search and Filters -->
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Search functionality
                    document.querySelectorAll('.room-search-input').forEach(input => {
                        input.addEventListener('input', function() {
                            const search = this.value.toLowerCase();
                            const targetSelector = this.getAttribute('data-target');
                            const cards = document.querySelectorAll(targetSelector);

                            cards.forEach(card => {
                                const roomText = card.textContent.toLowerCase();
                                card.style.display = roomText.includes(search) ? 'block' : 'none';
                            });
                        });
                    });

                    // Filter functionality
                    document.querySelectorAll('.apply-filters').forEach(button => {
                        button.addEventListener('click', function() {
                            const dropdown = this.closest('.dropdown-menu');
                            const blockId = this.closest('.tab-pane').id;
                            const container = document.querySelector(`#${blockId} .room-container`);

                            // Get filter values
                            const occupancyFilter = dropdown.querySelector('.occupancy-filter').value;
                            const roomMin = dropdown.querySelector('.room-min').value;
                            const roomMax = dropdown.querySelector('.room-max').value;
                            const sortFilter = dropdown.querySelector('.sort-filter').value;

                            // Filter rooms
                            const rooms = Array.from(container.querySelectorAll('.room-card'));

                            rooms.forEach(room => {
                                const roomNumber = parseInt(room.getAttribute('data-room-number'));
                                const occupancy = parseInt(room.getAttribute('data-occupancy'));
                                let show = true;

                                // Apply occupancy filter
                                if (occupancyFilter !== 'all') {
                                    if (occupancyFilter === '4+' && occupancy < 4) {
                                        show = false;
                                    } else if (occupancyFilter !== '4+' && occupancy !== parseInt(occupancyFilter)) {
                                        show = false;
                                    }
                                }

                                // Apply room number range filter
                                if (roomMin && roomNumber < parseInt(roomMin)) show = false;
                                if (roomMax && roomNumber > parseInt(roomMax)) show = false;

                                room.style.display = show ? 'block' : 'none';
                            });

                            // Sort rooms
                            rooms.sort((a, b) => {
                                const aNum = parseInt(a.getAttribute('data-room-number'));
                                const bNum = parseInt(b.getAttribute('data-room-number'));
                                const aOcc = parseInt(a.getAttribute('data-occupancy'));
                                const bOcc = parseInt(b.getAttribute('data-occupancy'));

                                switch (sortFilter) {
                                    case 'number-asc':
                                        return aNum - bNum;
                                    case 'number-desc':
                                        return bNum - aNum;
                                    case 'occupancy-asc':
                                        return aOcc - bOcc || aNum - bNum;
                                    case 'occupancy-desc':
                                        return bOcc - aOcc || aNum - bNum;
                                    default:
                                        return 0;
                                }
                            });

                            // Re-append sorted rooms
                            rooms.forEach(room => container.appendChild(room));

                            // Close dropdown
                            const dropdownInstance = bootstrap.Dropdown.getInstance(button.closest('.dropdown').querySelector('[data-bs-toggle="dropdown"]'));
                            dropdownInstance.hide();
                        });
                    });

                    // Reset filters
                    document.querySelectorAll('.reset-filters').forEach(button => {
                        button.addEventListener('click', function() {
                            const dropdown = this.closest('.dropdown-menu');
                            const blockId = this.closest('.tab-pane').id;
                            const container = document.querySelector(`#${blockId} .room-container`);

                            // Reset filter inputs
                            dropdown.querySelector('.occupancy-filter').value = 'all';
                            dropdown.querySelector('.room-min').value = '';
                            dropdown.querySelector('.room-max').value = '';
                            dropdown.querySelector('.sort-filter').value = 'occupancy-desc';

                            // Show all rooms
                            container.querySelectorAll('.room-card').forEach(room => {
                                room.style.display = 'block';
                            });

                            // Reset to default sort
                            const rooms = Array.from(container.querySelectorAll('.room-card'));
                            rooms.sort((a, b) => {
                                const aOcc = parseInt(a.getAttribute('data-occupancy'));
                                const bOcc = parseInt(b.getAttribute('data-occupancy'));
                                const aNum = parseInt(a.getAttribute('data-room-number'));
                                const bNum = parseInt(b.getAttribute('data-room-number'));
                                return bOcc - aOcc || aNum - bNum;
                            });

                            rooms.forEach(room => container.appendChild(room));
                        });
                    });
                });
            </script>





            <!-- Roommates Modal -->
            <div class="modal fade" id="roommatesModal" tabindex="-1" aria-labelledby="roommatesModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-fullscreen" style="max-width: 100%;">
                    <div class="modal-content" style="border-radius: 10px;">
                        <div class="modal-header" style="background-color: #343a40; color: white; padding: 15px 20px;">
                            <h5 class="modal-title" id="roommatesModalLabel">
                                <i class="fas fa-users me-2"></i> Room Mates
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                                style="filter: invert(1);"></button>
                        </div>

                        <div class="modal-body" id="roommatesModalBody" style="padding: 30px;">
                            <!-- Default Spinner while loading -->
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <div class="mt-3" style="color: #888;">Fetching Roommates...</div>
                            </div>
                        </div>

                        <div class="modal-footer" style="justify-content: center; padding: 20px;">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                                style="padding: 8px 20px; font-size: 15px;">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>


            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script>
                function toggleEditForm() {
                    $('#hostelForm').slideToggle();
                }

                $(document).ready(function() {
                    // Handle view roommates button click
                    $('.view-roommates').click(function() {
                        const block = $(this).data('block');
                        const room = $(this).data('room');

                        $('#roommatesModalLabel').text(`${block} - Room ${room} Mates`);

                        // Load content via AJAX
                        $.ajax({
                            url: 'fetch_roommates.php',
                            method: 'GET',
                            data: {
                                block: block,
                                room: room
                            },
                            success: function(response) {
                                $('#roommatesModalBody').html(response);
                            },
                            error: function() {
                                $('#roommatesModalBody').html('<div class="alert alert-danger">Error loading roommates data.</div>');
                            },
                            complete: function() {
                                $('#roommatesModal').modal('show');
                            }
                        });
                    });

                    // Validate room number input
                    $('#room_number').on('input', function() {
                        this.value = this.value.replace(/[^0-9]/g, '');
                        if (this.value.length > 4) {
                            this.value = this.value.slice(0, 4);
                        }
                    });
                });
            </script>
</body>

</html>