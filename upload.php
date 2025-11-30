<?php
/**
 * Simple File Upload Utility for CampusDigs
 * Upload images to the uploads/properties folder
 *
 * SECURITY WARNING: This file should be deleted after use or protected with authentication!
 */

// Suppress PHP errors from displaying on page
error_reporting(0);
ini_set('display_errors', 0);

// Simple password protection (CHANGE THIS PASSWORD!)
define('UPLOAD_PASSWORD', 'campusdigs2025'); // CHANGE THIS!

// Configuration
$uploadDir = __DIR__ . '/uploads/properties/';
$allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
$maxFileSize = 5 * 1024 * 1024; // 5MB

// Create upload directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Handle password check
session_start();
$isAuthenticated = isset($_SESSION['upload_authenticated']) && $_SESSION['upload_authenticated'] === true;

if (isset($_POST['password'])) {
    if ($_POST['password'] === UPLOAD_PASSWORD) {
        $_SESSION['upload_authenticated'] = true;
        $isAuthenticated = true;
    } else {
        $error = 'Invalid password!';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: upload.php');
    exit;
}

// Handle file upload
$message = '';
$uploadedFiles = [];
$hasPermissionError = false;

// Check directory permissions first
if ($isAuthenticated) {
    if (!is_dir($uploadDir)) {
        $message = "<strong>Directory Error:</strong> The uploads/properties directory does not exist.<br>";
        $message .= "Please create it manually or ask your server administrator.";
    } elseif (!is_writable($uploadDir)) {
        $hasPermissionError = true;
        $message = "<strong>Permission Error:</strong> The web server cannot write to the uploads/properties directory.<br><br>";
        $message .= "<strong>To fix this, run these commands on your server:</strong><br>";
        $message .= "<code>chmod 777 " . realpath($uploadDir) . "</code><br><br>";
        $message .= "<strong>Or contact your server administrator to:</strong><br>";
        $message .= "1. Set write permissions for the uploads directory<br>";
        $message .= "2. Change ownership to the web server user (usually www-data or apache)<br><br>";
        $message .= "<strong>Current directory path:</strong> <code>" . realpath($uploadDir) . "</code>";
    }
}

if ($isAuthenticated && isset($_FILES['files']) && !$hasPermissionError) {
    $files = $_FILES['files'];
    $fileCount = count($files['name']);

    for ($i = 0; $i < $fileCount; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $fileName = $files['name'][$i];
            $fileTmpName = $files['tmp_name'][$i];
            $fileSize = $files['size'][$i];
            $fileType = $files['type'][$i];

            // Validate file type
            if (!in_array($fileType, $allowedTypes)) {
                $message .= "[ERROR] {$fileName}: Invalid file type. Only images allowed.<br>";
                continue;
            }

            // Validate file size
            if ($fileSize > $maxFileSize) {
                $message .= "[ERROR] {$fileName}: File too large. Max 5MB.<br>";
                continue;
            }

            // Generate safe filename
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $newFileName = uniqid('property_') . '_' . time() . '.' . $fileExt;
            $destination = $uploadDir . $newFileName;

            // Move uploaded file (suppress PHP warnings)
            if (@move_uploaded_file($fileTmpName, $destination)) {
                $uploadedFiles[] = $newFileName;
                $message .= "[SUCCESS] {$fileName} uploaded successfully as {$newFileName}<br>";
            } else {
                $message .= "[ERROR] {$fileName}: Upload failed. Check server permissions.<br>";
            }
        } else {
            $errorMsg = '';
            switch ($files['error'][$i]) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errorMsg = 'File too large';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorMsg = 'Partial upload';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errorMsg = 'No file';
                    break;
                default:
                    $errorMsg = 'Upload error';
            }
            $message .= "[ERROR] {$files['name'][$i]}: {$errorMsg}.<br>";
        }
    }
}

// Get existing files
$existingFiles = [];
if (is_dir($uploadDir)) {
    $existingFiles = array_diff(scandir($uploadDir), ['.', '..']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusDigs - File Upload Utility</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
        .warning strong { display: block; margin-bottom: 5px; }
        .login-form {
            margin-top: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        input[type="password"], input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        input[type="file"] {
            padding: 10px;
        }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
        }
        .message {
            margin: 20px 0;
            padding: 15px;
            border-radius: 8px;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .file-list {
            margin-top: 30px;
        }
        .file-list h3 {
            margin-bottom: 15px;
            color: #333;
        }
        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
        }
        .file-item {
            position: relative;
            border: 2px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            background: #f8f9fa;
        }
        .file-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        .file-name {
            padding: 8px;
            font-size: 11px;
            word-break: break-all;
            background: #f8f9fa;
        }
        .logout {
            float: right;
            background: #dc3545;
            font-size: 14px;
            padding: 8px 20px;
        }
        .upload-area {
            border: 3px dashed #ddd;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            margin: 20px 0;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s;
        }
        .upload-area:hover {
            border-color: #667eea;
            background: #f0f0ff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>CampusDigs File Upload Utility</h1>

        <div class="warning">
            <strong>SECURITY WARNING:</strong>
            This file should be deleted after use or protected with a strong password!<br>
            Default password: <code>campusdigs2025</code> (Change in upload.php line 13)
        </div>

        <?php if (!$isAuthenticated): ?>
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label>Enter Password:</label>
                    <input type="password" name="password" required autofocus>
                </div>
                <?php if (isset($error)): ?>
                    <div class="message error"><?php echo $error; ?></div>
                <?php endif; ?>
                <button type="submit">Login</button>
            </form>
        <?php else: ?>
            <a href="?logout=1" class="logout">Logout</a>

            <form method="POST" enctype="multipart/form-data">
                <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                    <p style="font-size: 48px; margin-bottom: 10px;">[+]</p>
                    <p style="color: #666; font-size: 16px;">Click to select images</p>
                    <p style="color: #999; font-size: 12px; margin-top: 5px;">JPG, PNG, GIF, WebP (Max 5MB each)</p>
                </div>

                <div class="form-group">
                    <input type="file" id="fileInput" name="files[]" multiple accept="image/*" style="display: none;" onchange="this.form.submit()">
                </div>
            </form>

            <?php if ($message): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if (!empty($uploadedFiles)): ?>
                <div class="message">
                    <strong>To use these images in your properties:</strong><br>
                    Copy these filenames to your property image fields:<br><br>
                    <?php foreach ($uploadedFiles as $file): ?>
                        <code style="display: block; padding: 5px; background: white; margin: 5px 0; border-radius: 4px;">
                            <?php echo htmlspecialchars($file); ?>
                        </code>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="file-list">
                <h3>Uploaded Files (<?php echo count($existingFiles); ?>)</h3>
                <div class="file-grid">
                    <?php foreach ($existingFiles as $file): ?>
                        <?php
                        $filePath = $uploadDir . $file;
                        $fileUrl = 'uploads/properties/' . $file;
                        ?>
                        <div class="file-item">
                            <img src="<?php echo htmlspecialchars($fileUrl); ?>" alt="<?php echo htmlspecialchars($file); ?>">
                            <div class="file-name" title="<?php echo htmlspecialchars($file); ?>">
                                <?php echo htmlspecialchars($file); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
