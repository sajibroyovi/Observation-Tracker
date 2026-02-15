<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insert Observations</title>
    <!-- ... styles ... -->
</head>

<body>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // CSRF Validation
        if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
            log_error("CSRF token validation failed for observation create attempt");
            header('Location: ' . BASE_URL . '/index.php?status=error&msg=' . urlencode("Security Validation Failed: CSRF Token Mismatch."));
            exit;
        }

        // Get form data
        $observation_names = cleanInput($_POST['observation_names']);
        $team_names = isset($_POST['team_name']) ? implode(', ', $_POST['team_name']) : '';
        $team_name = cleanInput($team_names);
        $start_date = cleanInput($_POST['start_date']);
        $l1_observation = cleanInput($_POST['l1_observation']);

        // Automate L1 By
        $l1_observations_by = $_SESSION['username'];

        $l2_observation = cleanInput($_POST['l2_observation'] ?? '');

        // Automate L2 By if L2 observation is present
        $l2_observations_by = "";
        if (!empty($l2_observation)) {
            $l2_observations_by = $_SESSION['username'];
        }

        // Handle file uploads
        $l1_image_path = "";
        $l1_image_2_path = "";

        if (isset($_FILES['l1_images'])) {
            $total_files = count($_FILES['l1_images']['name']);
            $target_dir = ASSETS_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            for ($i = 0; $i < min($total_files, 2); $i++) {
                if ($_FILES['l1_images']['error'][$i] == 0) {
                    $file_extension = pathinfo($_FILES["l1_images"]["name"][$i], PATHINFO_EXTENSION);
                    $file_name = uniqid() . "_" . ($i + 1) . "." . $file_extension;
                    $target_file = $target_dir . $file_name;

                    if (move_uploaded_file($_FILES["l1_images"]["tmp_name"][$i], $target_file)) {
                        if ($i == 0) {
                            $l1_image_path = "uploads/" . $file_name;
                        } else {
                            $l1_image_2_path = "uploads/" . $file_name;
                        }
                    } else {
                        log_error("Failed to move uploaded file to: " . $target_file);
                    }
                } else {
                    log_error("File upload error", ['code' => $_FILES['l1_images']['error'][$i]]);
                }
            }
        }

        // Insert into observations table using prepared statements
        $created_by = $_SESSION['username'];
        $sql = "INSERT INTO observations (observation_names, team_name, start_date, l1_observation, l1_image, l1_image_2, l1_observations_by, l2_observation, l2_observations_by, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssssssssss", 
                $observation_names, 
                $team_name, 
                $start_date, 
                $l1_observation, 
                $l1_image_path, 
                $l1_image_2_path, 
                $l1_observations_by, 
                $l2_observation, 
                $l2_observations_by, 
                $created_by
            );

            if (mysqli_stmt_execute($stmt)) {
                header('Location: ' . BASE_URL . '/index.php?status=success&msg=' . urlencode("Observation record inserted successfully"));
            } else {
                log_error("Failed to insert observation record", ['error' => mysqli_stmt_error($stmt)]);
                header('Location: ' . BASE_URL . '/index.php?status=error&msg=' . urlencode("Critical Error: Failed to save record."));
            }
            mysqli_stmt_close($stmt);
        } else {
            log_error("Failed to prepare statement for observation", ['error' => mysqli_error($conn)]);
            header('Location: ' . BASE_URL . '/index.php?status=error&msg=' . urlencode("Critical Error: Internal Server Error."));
        }

        mysqli_close($conn);
        exit();
    }
    ?>

</body>

</html>



