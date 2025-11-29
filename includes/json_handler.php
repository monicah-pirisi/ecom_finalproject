<?php
/**
 * JSON Response Handler
 * Ensures all JSON responses are properly formatted
 */

// Suppress all errors and warnings
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering
if (!ob_get_level()) {
    ob_start();
}

// Register shutdown function to catch fatal errors
register_shutdown_function('handleFatalError');

/**
 * Send JSON response
 */
function sendJSON($data) {
    // Clean any output
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Set JSON header
    header('Content-Type: application/json; charset=utf-8');

    // Ensure data is an array
    if (!is_array($data)) {
        $data = ['success' => false, 'message' => 'Invalid response format'];
    }

    // Send JSON
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

/**
 * Handle fatal errors
 */
function handleFatalError() {
    $error = error_get_last();

    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Clean output
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Set JSON header
        header('Content-Type: application/json; charset=utf-8');

        // Log error
        error_log("Fatal error in JSON handler: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);

        // Send error response
        echo json_encode([
            'success' => false,
            'message' => 'Server error occurred. Please try again later.',
            'error' => ENVIRONMENT === 'development' ? $error['message'] : null
        ]);
        exit();
    }
}
?>
