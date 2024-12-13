<?php
// Initialize variables for summary statistics
$totalOriginalSize = 0;
$totalCompressedSize = 0;

// Function to compress images dynamically based on size
function compressImage($file, $destination, $quality) {
    global $totalOriginalSize, $totalCompressedSize;

    $info = getimagesize($file);
    $originalSize = filesize($file);
    $totalOriginalSize += $originalSize;

    $compressedSize = $originalSize;

    if ($info['mime'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($file);
        while ($compressedSize > 500 * 1024 && $quality > 10) { // Compress iteratively for large files
            imagejpeg($image, $destination, $quality);
            $compressedSize = filesize($destination);
            $quality -= 10;
        }
    } elseif ($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($file);
        while ($compressedSize > 500 * 1024 && $quality > 10) {
            imagepng($image, $destination, (int)($quality / 10));
            $compressedSize = filesize($destination);
            $quality -= 10;
        }
    }

    $totalCompressedSize += $compressedSize;
    $compressionPercentage = 100 - (($compressedSize / $originalSize) * 100);
    return round($compressionPercentage, 2);
}

// Function to compress other files and calculate compression percentage
function compressFile($file, $destination) {
    global $totalOriginalSize, $totalCompressedSize;

    $originalSize = filesize($file);
    $totalOriginalSize += $originalSize;
    
    $zip = new ZipArchive();
    $zipFile = $destination . '.zip';

    if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
        $zip->addFile($file, basename($file));
        $zip->close();
    }

    $compressedSize = filesize($zipFile);
    $totalCompressedSize += $compressedSize;
    $compressionPercentage = 100 - (($compressedSize / $originalSize) * 100);
    return round($compressionPercentage, 2);
}

// Function to traverse and process files
function traverseAndCompress($dir) {
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'docx', 'xlsx', 'pptx'];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
        if ($file->isFile()) {
            $filePath = $file->getPathname();
            $fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

            if (!in_array($fileExt, $allowedExtensions)) continue;

            if (in_array($fileExt, ['jpg', 'jpeg', 'png'])) {
                // Compress image dynamically and get percentage
                $compressionPercentage = compressImage($filePath, $filePath, 60);
                echo "Compressed image: $filePath | Reduced by: $compressionPercentage%\n";
            } elseif (in_array($fileExt, $allowedExtensions)) {
                // Compress document and get percentage
                $compressionPercentage = compressFile($filePath, $filePath);
                echo "Compressed file: $filePath | Reduced by: $compressionPercentage%\n";
            }
        }
    }
}

// Function to calculate and log summary statistics
function calculateAndLogSummary() {
    global $totalOriginalSize, $totalCompressedSize;

    $totalSaved = $totalOriginalSize - $totalCompressedSize;
    $totalPercentageSaved = ($totalSaved / $totalOriginalSize) * 100;

    echo "\nSummary:\n";
    echo "Total Original Size: " . round($totalOriginalSize / 1024, 2) . " KB\n";
    echo "Total Compressed Size: " . round($totalCompressedSize / 1024, 2) . " KB\n";
    echo "Total Space Saved: " . round($totalSaved / 1024, 2) . " KB | Reduced by: " . round($totalPercentageSaved, 2) . "%\n";
}


// Starting point
$rootDir = '.';
traverseAndCompress($rootDir);
calculateAndLogSummary();
