<?php
if (isset($_GET['type'])) {
    $type = $_GET['type'];
    
    // බටන් එක අනුව path එක තෝරා ගැනීම
    if ($type == 'apple') {
        $dirPath = 'uploads/assets/app_store';
        $zipName = 'Fabricare_iOS_Assets.zip';
    } elseif ($type == 'android') {
        $dirPath = 'uploads/assets/play_store';
        $zipName = 'Fabricare_Android_Assets.zip';
    } else {
        die("Invalid request.");
    }

    // Folder එක පවතිනවාදැයි පරීක්ෂා කිරීම
    if (!is_dir($dirPath)) {
        die("Error: Source folder not found at $dirPath");
    }

    $zip = new ZipArchive();

    if ($zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                // Folder එක ඇතුළේ තියෙන structure එක zip එකට එකතු කිරීම
                $relativePath = substr($filePath, strlen(realpath($dirPath)) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();

        // Download එක Force කිරීම
        if (file_exists($zipName)) {
            header('Content-Type: application/zip');
            header('Content-disposition: attachment; filename='.$zipName);
            header('Content-Length: ' . filesize($zipName));
            header('Pragma: no-cache');
            header('Expires: 0');
            readfile($zipName);

            // Server එකේ ඉඩ ඉතිරි කරගැනීමට zip එක මැකීම
            unlink($zipName);
            exit;
        }
    } else {
        echo 'Failed to create zip file.';
    }
}
?>