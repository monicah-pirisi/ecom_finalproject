<?php
/**
 * CampusDigs Kenya - Export Reports
 * Handles PDF, Excel, and CSV export functionality
 */

// Suppress warnings and notices to prevent them from appearing in exported files
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/user_controller.php';
require_once '../controllers/property_controller.php';
require_once '../controllers/booking_controller.php';
require_once '../controllers/review_controller.php';

requireAdmin();

// Get parameters
$format = isset($_GET['format']) ? sanitizeInput($_GET['format']) : 'pdf';
$dateRange = isset($_GET['range']) ? sanitizeInput($_GET['range']) : '30';
$customStartDate = isset($_GET['start']) ? sanitizeInput($_GET['start']) : '';
$customEndDate = isset($_GET['end']) ? sanitizeInput($_GET['end']) : '';

// Calculate date range
if ($dateRange === 'custom' && $customStartDate && $customEndDate) {
    $startDate = $customStartDate;
    $endDate = $customEndDate;
} else {
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime("-{$dateRange} days"));
}

// Get all analytics data
$userAnalytics = getUserAnalytics($startDate, $endDate);
$propertyAnalytics = getPropertyAnalytics($startDate, $endDate);
$bookingAnalytics = getBookingAnalytics($startDate, $endDate);
$revenueAnalytics = getRevenueAnalytics($startDate, $endDate);

// Prepare report data with null-safe access
$reportData = [
    'period' => "Report Period: " . formatDate($startDate) . " to " . formatDate($endDate),
    'generated' => "Generated: " . date('Y-m-d H:i:s'),
    'user_stats' => [
        'Total Users' => $userAnalytics['total_users'] ?? 0,
        'Students' => $userAnalytics['total_students'] ?? 0,
        'Landlords' => $userAnalytics['total_landlords'] ?? 0,
        'New Users (Period)' => $userAnalytics['new_users'] ?? 0,
        'Growth Rate' => ($userAnalytics['growth_rate'] ?? 0) . '%'
    ],
    'property_stats' => [
        'Total Properties' => $propertyAnalytics['total_properties'] ?? 0,
        'Active Properties' => $propertyAnalytics['active_properties'] ?? 0,
        'Pending Approval' => $propertyAnalytics['pending_properties'] ?? 0,
        'New Properties (Period)' => $propertyAnalytics['new_properties'] ?? 0
    ],
    'booking_stats' => [
        'Total Bookings' => $bookingAnalytics['total_bookings'] ?? 0,
        'Confirmed Bookings' => $bookingAnalytics['confirmed_bookings'] ?? 0,
        'Pending Bookings' => $bookingAnalytics['pending_bookings'] ?? 0,
        'Cancelled Bookings' => $bookingAnalytics['cancelled_bookings'] ?? 0
    ],
    'revenue_stats' => [
        'Total Revenue' => 'KES ' . number_format($revenueAnalytics['total_revenue'] ?? 0, 2),
        'Commission Earned' => 'KES ' . number_format($revenueAnalytics['commission_earned'] ?? 0, 2),
        'Average Booking Value' => 'KES ' . number_format($revenueAnalytics['average_booking_value'] ?? 0, 2)
    ]
];

// Export based on format
switch ($format) {
    case 'csv':
        exportCSV($reportData);
        break;
    case 'excel':
        exportExcel($reportData);
        break;
    case 'pdf':
        exportPDF($reportData);
        break;
    default:
        redirectWithMessage('../admin/reports.php', 'Invalid export format', 'error');
}

/**
 * Export report as CSV
 */
function exportCSV($data) {
    // Clear any output buffers
    if (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="campusdigs_report_' . date('Y-m-d') . '.csv"');
    header('X-Content-Type-Options: nosniff');

    $output = fopen('php://output', 'w');

    // Header
    fputcsv($output, ['CampusDigs Kenya - Analytics Report']);
    fputcsv($output, [$data['period']]);
    fputcsv($output, [$data['generated']]);
    fputcsv($output, []); // Empty row

    // User Statistics
    fputcsv($output, ['USER STATISTICS']);
    fputcsv($output, ['Metric', 'Value']);
    foreach ($data['user_stats'] as $key => $value) {
        fputcsv($output, [$key, $value]);
    }
    fputcsv($output, []); // Empty row

    // Property Statistics
    fputcsv($output, ['PROPERTY STATISTICS']);
    fputcsv($output, ['Metric', 'Value']);
    foreach ($data['property_stats'] as $key => $value) {
        fputcsv($output, [$key, $value]);
    }
    fputcsv($output, []); // Empty row

    // Booking Statistics
    fputcsv($output, ['BOOKING STATISTICS']);
    fputcsv($output, ['Metric', 'Value']);
    foreach ($data['booking_stats'] as $key => $value) {
        fputcsv($output, [$key, $value]);
    }
    fputcsv($output, []); // Empty row

    // Revenue Statistics
    fputcsv($output, ['REVENUE STATISTICS']);
    fputcsv($output, ['Metric', 'Value']);
    foreach ($data['revenue_stats'] as $key => $value) {
        fputcsv($output, [$key, $value]);
    }

    fclose($output);
    exit();
}

/**
 * Export report as Excel (HTML table that Excel can import)
 */
function exportExcel($data) {
    // Clear any output buffers
    if (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="campusdigs_report_' . date('Y-m-d') . '.xls"');
    header('X-Content-Type-Options: nosniff');

    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    echo '<style>
        body { font-family: Arial, sans-serif; }
        h1 { color: #059669; font-size: 24px; }
        h2 { color: #047857; font-size: 18px; margin-top: 20px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #059669; color: white; font-weight: bold; }
        .header { background-color: #f0fdf4; padding: 10px; margin-bottom: 20px; }
    </style>';
    echo '</head>';
    echo '<body>';

    // Header
    echo '<div class="header">';
    echo '<h1>CampusDigs Kenya - Analytics Report</h1>';
    echo '<p>' . htmlspecialchars($data['period']) . '</p>';
    echo '<p>' . htmlspecialchars($data['generated']) . '</p>';
    echo '</div>';

    // User Statistics
    echo '<h2>User Statistics</h2>';
    echo '<table>';
    echo '<tr><th>Metric</th><th>Value</th></tr>';
    foreach ($data['user_stats'] as $key => $value) {
        echo '<tr><td>' . htmlspecialchars((string)$key) . '</td><td>' . htmlspecialchars((string)$value) . '</td></tr>';
    }
    echo '</table>';

    // Property Statistics
    echo '<h2>Property Statistics</h2>';
    echo '<table>';
    echo '<tr><th>Metric</th><th>Value</th></tr>';
    foreach ($data['property_stats'] as $key => $value) {
        echo '<tr><td>' . htmlspecialchars((string)$key) . '</td><td>' . htmlspecialchars((string)$value) . '</td></tr>';
    }
    echo '</table>';

    // Booking Statistics
    echo '<h2>Booking Statistics</h2>';
    echo '<table>';
    echo '<tr><th>Metric</th><th>Value</th></tr>';
    foreach ($data['booking_stats'] as $key => $value) {
        echo '<tr><td>' . htmlspecialchars((string)$key) . '</td><td>' . htmlspecialchars((string)$value) . '</td></tr>';
    }
    echo '</table>';

    // Revenue Statistics
    echo '<h2>Revenue Statistics</h2>';
    echo '<table>';
    echo '<tr><th>Metric</th><th>Value</th></tr>';
    foreach ($data['revenue_stats'] as $key => $value) {
        echo '<tr><td>' . htmlspecialchars((string)$key) . '</td><td>' . htmlspecialchars((string)$value) . '</td></tr>';
    }
    echo '</table>';

    echo '</body></html>';
    exit();
}

/**
 * Export report as PDF (printable HTML that can be saved as PDF)
 */
function exportPDF($data) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>CampusDigs Kenya - Analytics Report</title>
        <style>
            @media print {
                body { margin: 0; }
                .no-print { display: none; }
            }
            body {
                font-family: Arial, sans-serif;
                max-width: 900px;
                margin: 0 auto;
                padding: 20px;
                color: #333;
            }
            .header {
                background: linear-gradient(135deg, #059669 0%, #d97706 100%);
                color: white;
                padding: 20px;
                border-radius: 8px;
                margin-bottom: 30px;
            }
            h1 {
                margin: 0 0 10px 0;
                font-size: 28px;
            }
            .meta {
                font-size: 14px;
                opacity: 0.9;
            }
            h2 {
                color: #059669;
                border-bottom: 2px solid #059669;
                padding-bottom: 8px;
                margin-top: 30px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 12px;
                text-align: left;
            }
            th {
                background-color: #059669;
                color: white;
                font-weight: bold;
            }
            tr:nth-child(even) {
                background-color: #f0fdf4;
            }
            .btn-print {
                background: #059669;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 5px;
                cursor: pointer;
                font-size: 16px;
                margin-bottom: 20px;
            }
            .btn-print:hover {
                background: #047857;
            }
        </style>
    </head>
    <body>
        <button class="btn-print no-print" onclick="window.print()">
            Print / Save as PDF
        </button>

        <div class="header">
            <h1>CampusDigs Kenya - Analytics Report</h1>
            <div class="meta">
                <p><?php echo htmlspecialchars($data['period']); ?></p>
                <p><?php echo htmlspecialchars($data['generated']); ?></p>
            </div>
        </div>

        <h2>User Statistics</h2>
        <table>
            <tr>
                <th>Metric</th>
                <th>Value</th>
            </tr>
            <?php foreach ($data['user_stats'] as $key => $value): ?>
                <tr>
                    <td><?php echo htmlspecialchars((string)$key); ?></td>
                    <td><?php echo htmlspecialchars((string)$value); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <h2>Property Statistics</h2>
        <table>
            <tr>
                <th>Metric</th>
                <th>Value</th>
            </tr>
            <?php foreach ($data['property_stats'] as $key => $value): ?>
                <tr>
                    <td><?php echo htmlspecialchars((string)$key); ?></td>
                    <td><?php echo htmlspecialchars((string)$value); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <h2>Booking Statistics</h2>
        <table>
            <tr>
                <th>Metric</th>
                <th>Value</th>
            </tr>
            <?php foreach ($data['booking_stats'] as $key => $value): ?>
                <tr>
                    <td><?php echo htmlspecialchars((string)$key); ?></td>
                    <td><?php echo htmlspecialchars((string)$value); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <h2>Revenue Statistics</h2>
        <table>
            <tr>
                <th>Metric</th>
                <th>Value</th>
            </tr>
            <?php foreach ($data['revenue_stats'] as $key => $value): ?>
                <tr>
                    <td><?php echo htmlspecialchars((string)$key); ?></td>
                    <td><?php echo htmlspecialchars((string)$value); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <script>
            // Auto-print on load (optional)
            // window.onload = function() { window.print(); }
        </script>
    </body>
    </html>
    <?php
    exit();
}

?>
