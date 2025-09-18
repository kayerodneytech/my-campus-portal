<?php
// admin/utils/activity_logger.php

/**
 * Logs user activity to the database
 *
 * @param string $activityType Type of activity (e.g., 'election_create', 'login')
 * @param string $description Description of the activity
 * @param string|null $additionalData Additional data related to the activity
 * @return bool True if logging was successful, false otherwise
 */
function logActivity($activityType, $description, $additionalData = null)
{
    global $conn;

    if (!isset($_SESSION['user_id'])) {
        error_log("Attempted to log activity without a user session");
        return false;
    }

    $userId = $_SESSION['user_id'];
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    try {
        $stmt = $conn->prepare("
            INSERT INTO activity_logs
            (user_id, activity_type, description, ip_address, user_agent, additional_data)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "isssss",
            $userId,
            $activityType,
            $description,
            $ipAddress,
            $userAgent,
            $additionalData
        );

        $success = $stmt->execute();
        $stmt->close();

        return $success;
    } catch (Exception $e) {
        error_log("Error logging activity: " . $e->getMessage());
        return false;
    }
}
