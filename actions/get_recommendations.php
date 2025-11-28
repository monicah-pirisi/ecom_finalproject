<?php
/**
 * CampusDigs Kenya - Get AI Recommendations API
 * Returns personalized property recommendations for students
 */

// Suppress ALL errors and warnings to prevent HTML output before JSON
error_reporting(0);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Start output buffering to catch any stray output
ob_start();

session_start();

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/recommendation_controller.php';

// Clean ALL output buffer content
while (ob_get_level()) {
    ob_end_clean();
}

// Start fresh buffer
ob_start();

header('Content-Type: application/json; charset=utf-8');

// Check if user is logged in as student
if (!isLoggedIn() || !isStudent()) {
    echo json_encode([
        'success' => false,
        'message' => 'Please log in as a student to get recommendations'
    ]);
    exit();
}

try {
    $studentId = $_SESSION['user_id'];
    $type = isset($_GET['type']) ? $_GET['type'] : 'personalized';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;

    $recommendations = [];

    switch ($type) {
        case 'personalized':
            $recommendations = getRecommendedProperties($studentId, $limit);
            break;

        case 'trending':
            $recommendations = getTrendingProperties($limit);
            break;

        case 'similar':
            $propertyId = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;
            if ($propertyId) {
                $recommendations = getSimilarProperties($propertyId, $limit);
            }
            break;

        default:
            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => 'Invalid recommendation type'
            ]);
            exit();
    }

    // Ensure recommendations is an array
    if (!is_array($recommendations)) {
        $recommendations = [];
    }

    // Clean output buffer before sending JSON
    ob_clean();

    echo json_encode([
        'success' => true,
        'recommendations' => $recommendations,
        'count' => count($recommendations),
        'type' => $type
    ], JSON_THROW_ON_ERROR);

    exit();

} catch (Exception $e) {
    error_log("Get recommendations error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    ob_clean();

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load recommendations. Please try again.',
        'error' => $e->getMessage()
    ]);

    exit();
} catch (Throwable $e) {
    error_log("Fatal error in recommendations: " . $e->getMessage());

    ob_clean();

    echo json_encode([
        'success' => false,
        'message' => 'System error. Please contact support.'
    ]);

    exit();
}
?>
