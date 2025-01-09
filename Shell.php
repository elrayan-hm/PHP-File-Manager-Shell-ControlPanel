<?php
// WARNING: This script is for educational purposes only.
// Do NOT use it on a production or public server without proper security measures!

// Directory to manage (adjust as needed)
$target_dir = __DIR__; // Change to a specific directory if needed

// Display current directory path
echo "<h2>Current Directory:</h2>";
echo "<p>" . htmlspecialchars($target_dir) . "</p>";

// Function to delete a folder and its contents recursively
function deleteFolder($folder) {
    if (!is_dir($folder)) return false; // If it's not a folder, skip it

    $items = array_diff(scandir($folder), ['.', '..']); // Exclude '.' and '..'
    foreach ($items as $item) {
        $item_path = $folder . DIRECTORY_SEPARATOR . $item;
        if (is_dir($item_path)) {
            deleteFolder($item_path); // Recursively delete subfolders
        } else {
            unlink($item_path); // Delete files
        }
    }
    return rmdir($folder); // Delete the folder itself
}

// Handle file upload
if (isset($_FILES['file'])) {
    $upload_file = $target_dir . '/' . basename($_FILES['file']['name']);
    if (move_uploaded_file($_FILES['file']['tmp_name'], $upload_file)) {
        echo "File uploaded successfully: " . htmlspecialchars($_FILES['file']['name']) . "<br>";
    } else {
        echo "Failed to upload file.<br>";
    }
}

// Handle file or folder deletion
if (isset($_GET['delete'])) {
    $delete_path = $target_dir . '/' . basename($_GET['delete']);
    if (is_dir($delete_path)) {
        if (deleteFolder($delete_path)) {
            echo "Folder deleted successfully: " . htmlspecialchars($_GET['delete']) . "<br>";
        } else {
            echo "Failed to delete folder: " . htmlspecialchars($_GET['delete']) . "<br>";
        }
    } elseif (file_exists($delete_path)) {
        unlink($delete_path);
        echo "File deleted successfully: " . htmlspecialchars($_GET['delete']) . "<br>";
    } else {
        echo "File or folder not found: " . htmlspecialchars($_GET['delete']) . "<br>";
    }
}

// Handle file unzipping
if (isset($_GET['unzip'])) {
    $zip_file = $target_dir . '/' . basename($_GET['unzip']);

    if (file_exists($zip_file)) {
        $zip = new ZipArchive;
        if ($zip->open($zip_file) === TRUE) {
            $zip->extractTo($target_dir); // Extract directly to the current directory
            $zip->close();
            echo "File unzipped successfully: " . htmlspecialchars($_GET['unzip']) . "<br>";
        } else {
            echo "Failed to unzip file: " . htmlspecialchars($_GET['unzip']) . "<br>";
        }
    } else {
        echo "Zip file not found: " . htmlspecialchars($_GET['unzip']) . "<br>";
    }
}

// List files and folders in the directory
$files = scandir($target_dir);
echo "<h2>File List</h2>";
echo "<ul>";
foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
        $file_path = $target_dir . '/' . $file;
        echo "<li>" . htmlspecialchars($file);

        // Add delete link
        echo " <a href='?delete=" . urlencode($file) . "'>Delete</a>";

        // Add unzip link if it's a .zip file
        if (pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
            echo " <a href='?unzip=" . urlencode($file) . "'>Unzip</a>";
        }

        // Indicate if it's a folder
        if (is_dir($file_path)) {
            echo " (Folder)";
        }

        echo "</li>";
    }
}
echo "</ul>";
?>

<h2>Upload File</h2>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="file">
    <button type="submit">Upload</button>
</form>
