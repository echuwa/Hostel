<?php
function logAction(
    mysqli $mysqli,
    string $actionType,
    string $description,
    ?array $additionalData = null,
    ?string $affectedTable = null,
    ?int $affectedRecordId = null
): void {
    $query = "INSERT INTO audit_logs (
                user_id, 
                action_type, 
                description, 
                ip_address, 
                user_agent, 
                additional_data,
                affected_table,
                affected_record_id
              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param(
        "issssssi",
        $_SESSION['user_id'] ?? null,
        $actionType,
        $description,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        $additionalData ? json_encode($additionalData, JSON_PRETTY_PRINT) : null,
        $affectedTable,
        $affectedRecordId
    );
    $stmt->execute();
}
