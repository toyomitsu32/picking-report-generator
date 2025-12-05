<?php
/**
 * Picking Report Generator - Entry Point
 * 
 * This is the main entry point for the web application.
 */

declare(strict_types=1);

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Load Composer autoloader
require BASE_PATH . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

// Set timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Tokyo');

// Set error reporting based on environment
if ($_ENV['APP_ENV'] === 'production') {
    error_reporting(0);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Set PHP configuration
ini_set('memory_limit', $_ENV['PDF_MEMORY_LIMIT'] ?? '256M');
ini_set('max_execution_time', '60');

// Start session
session_start([
    'cookie_httponly' => filter_var($_ENV['SESSION_HTTPONLY'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'cookie_secure' => filter_var($_ENV['SESSION_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'gc_maxlifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 30) * 60,
]);

// Initialize application
use PickingReport\Bootstrap;
use PickingReport\Controllers\ReportController;

Bootstrap::init();
$logger = Bootstrap::getLogger();

// Handle PDF download
if (isset($_GET['download']) && isset($_SESSION['pdf_path'])) {
    $pdfPath = $_SESSION['pdf_path'];
    $fileName = $_SESSION['pdf_filename'];
    
    if (file_exists($pdfPath)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($pdfPath));
        readfile($pdfPath);
        
        // Clean up session
        unset($_SESSION['pdf_ready'], $_SESSION['pdf_path'], $_SESSION['pdf_filename']);
        
        // Optionally delete the file
        // unlink($pdfPath);
        exit;
    }
}

// Handle file upload and PDF generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $controller = new ReportController();
    $config = $controller->getDefaultConfig();
    $result = $controller->processUploadedFile($_FILES['csv_file'], $config);
    
    if ($result['success']) {
        // Store PDF path in session for download
        $_SESSION['pdf_ready'] = true;
        $_SESSION['pdf_path'] = $result['pdf_path'];
        $_SESSION['pdf_filename'] = basename($result['pdf_path']);
        $_SESSION['success_message'] = 'PDF生成が完了しました！ダウンロードが開始されます。';
        header('Location: /');
        exit;
    } else {
        // Store error in session for display
        $_SESSION['error'] = $result['error'];
        $_SESSION['error_type'] = $result['type'];
        if (isset($result['errors'])) {
            $_SESSION['errors'] = $result['errors'];
        }
        header('Location: /');
        exit;
    }
}

// Display upload form
$error = $_SESSION['error'] ?? null;
$errorType = $_SESSION['error_type'] ?? null;
$errors = $_SESSION['errors'] ?? [];
$pdfReady = $_SESSION['pdf_ready'] ?? false;
$successMessage = $_SESSION['success_message'] ?? null;
unset($_SESSION['error'], $_SESSION['error_type'], $_SESSION['errors'], $_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ピッキング帳票生成システム</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .upload-area {
            border: 2px dashed #667eea;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            background: #f8f9ff;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .upload-area:hover {
            border-color: #764ba2;
            background: #f0f2ff;
        }
        .upload-area.dragover {
            border-color: #764ba2;
            background: #e8ebff;
        }
        .upload-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .upload-text {
            color: #666;
            margin-bottom: 10px;
        }
        .file-input {
            display: none;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.2s ease;
            margin-top: 20px;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .error-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .error-list {
            margin-top: 10px;
            padding-left: 20px;
        }
        .selected-file {
            margin-top: 15px;
            padding: 10px;
            background: #e8f5e9;
            border-radius: 6px;
            color: #2e7d32;
        }
        .info {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            color: #1565c0;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            font-size: 14px;
        }
        .success {
            background: #e8f5e9;
            border: 1px solid #81c784;
            color: #2e7d32;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .success-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 ピッキング帳票生成</h1>
        <p class="subtitle">CSVファイルをアップロードしてPDF帳票を生成します</p>
        
        <?php if ($successMessage): ?>
        <div class="success">
            <div class="success-title">✓ 成功</div>
            <div><?php echo htmlspecialchars($successMessage); ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="error">
            <div class="error-title">エラーが発生しました</div>
            <div><?php echo htmlspecialchars($error); ?></div>
            <?php if (!empty($errors)): ?>
            <ul class="error-list">
                <?php foreach ($errors as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="upload-area" id="uploadArea">
                <div class="upload-icon">📁</div>
                <div class="upload-text">
                    <strong>クリックしてファイルを選択</strong><br>
                    またはドラッグ&ドロップ
                </div>
                <div style="color: #999; font-size: 12px; margin-top: 10px;">
                    CSV形式のみ対応 (最大10MB)
                </div>
                <input type="file" name="csv_file" id="csvFile" class="file-input" accept=".csv,text/csv" required>
            </div>
            
            <div id="selectedFile" class="selected-file" style="display: none;">
                <strong>選択されたファイル:</strong> <span id="fileName"></span>
            </div>
            
            <button type="submit" class="btn" id="submitBtn" disabled>PDF生成</button>
        </form>
        
        <div class="info">
            <strong>💡 使い方:</strong><br>
            1. 受注データのCSVファイルを選択<br>
            2. 「PDF生成」ボタンをクリック<br>
            3. 生成されたPDFが自動ダウンロードされます
        </div>
    </div>
    
    <script>
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('csvFile');
        const submitBtn = document.getElementById('submitBtn');
        const selectedFile = document.getElementById('selectedFile');
        const fileName = document.getElementById('fileName');
        
        // Click to upload
        uploadArea.addEventListener('click', () => fileInput.click());
        
        // File selected
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                fileName.textContent = file.name;
                selectedFile.style.display = 'block';
                submitBtn.disabled = false;
            }
        });
        
        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                const file = e.dataTransfer.files[0];
                fileName.textContent = file.name;
                selectedFile.style.display = 'block';
                submitBtn.disabled = false;
            }
        });
        
        // Form submission
        document.getElementById('uploadForm').addEventListener('submit', () => {
            submitBtn.disabled = true;
            submitBtn.textContent = '処理中...';
        });
        
        // Auto-download PDF if ready
        <?php if ($pdfReady): ?>
        window.addEventListener('load', () => {
            // Trigger download
            window.location.href = '?download=1';
            
            // Reset form after a short delay
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'PDF生成';
                selectedFile.style.display = 'none';
                fileInput.value = '';
            }, 1000);
        });
        <?php endif; ?>
    </script>
</body>
</html>
