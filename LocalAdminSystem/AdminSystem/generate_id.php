<?php
require 'vendor/autoload.php';
use PhpOffice\PhpWord\TemplateProcessor;
require_once __DIR__ . '/../Config/database.php';

// ---------------- FETCH STUDENT ----------------
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) die("Invalid student ID.");

$stmt = $pdo->prepare("SELECT * FROM register WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) die("Student not found.");

// ---------------- ARCHIVE & DELETE ----------------
try {
    $stmtCheck = $pdo->prepare("SELECT id FROM archive WHERE id_number = ?");
    $stmtCheck->execute([$student['id_number']]);
    $archived = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($archived) {
        $update = $pdo->prepare("UPDATE archive SET printed_at = NOW() WHERE id = ?");
        $update->execute([$archived['id']]);
    } else {
        $insert = $pdo->prepare("
            INSERT INTO archive
            (
                lrn, full_name, id_number, grade, strand, course, home_address,
                guardian_name, guardian_contact, photo, photo_blob,
                created_at, printed_at, status, released_at
            )
            VALUES (?,?,?,?,?,?,?,?,?,?,?, ?, NOW(), 'Pending', NULL)
        ");
        $insert->execute([
            $student['lrn'],
            $student['full_name'],
            $student['id_number'],
            $student['grade'],
            $student['strand'],
            $student['course'],
            $student['home_address'],
            $student['guardian_name'],
            $student['guardian_contact'],
            $student['photo'] ?? null,
            $student['photo_blob'] ?? null,
            $student['created_at'] ?? date('Y-m-d H:i:s')
        ]);
    }

    $pdo->prepare("DELETE FROM register WHERE id = ?")->execute([$id]);
} catch (Exception $e) {
    die("Archiving & deletion failed: " . $e->getMessage());
}

// ---------------- TEMPLATE ----------------
$template = new TemplateProcessor(__DIR__ . '/template/template.docx');

// ---------------- SAFE PHOTO HANDLER ----------------
function getSafePhotoPath($student) {
    $uploadsDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0777, true);

    $filename = basename($student['photo'] ?? '');
    $filePath = $uploadsDir . $filename;

    if ($filename && file_exists($filePath) && @getimagesize($filePath)) {
        return $filePath;
    }

    if (!file_exists($filePath) && !empty($student['photo_blob'])) {
        $blob = $student['photo_blob'];
        if (is_resource($blob)) $blob = stream_get_contents($blob);
        file_put_contents($filePath, $blob);
        if (@getimagesize($filePath)) return $filePath;
        @unlink($filePath);
    }

    // default placeholder
    $default = $uploadsDir . 'default.png';
    if (!file_exists($default)) {
        $im = imagecreatetruecolor(50, 65);
        $bg = imagecolorallocate($im, 200, 200, 200);
        imagefilledrectangle($im, 0, 0, 50, 65, $bg);
        imagepng($im, $default);
        imagedestroy($im);
    }

    return $default;
}

// ---------------- REPLACE PLACEHOLDERS ----------------
$template->setValue('name', htmlspecialchars($student['full_name']));
$template->setValue('id_number', htmlspecialchars($student['id_number']));
$template->setValue('lrn', htmlspecialchars($student['lrn']));
$template->setValue(
    'strand',
    htmlspecialchars(!empty($student['strand']) ? $student['strand'] : (!empty($student['grade']) ? $student['grade'] : $student['course']))
);
$template->setValue('address', htmlspecialchars($student['home_address']));
$template->setValue('guardian_name', htmlspecialchars($student['guardian_name']));
$template->setValue('guardian_contact', htmlspecialchars($student['guardian_contact']));

$photoPath = getSafePhotoPath($student);

// Target size in pixels (matches your table cell or ID design)
$targetW = 110;
$targetH = 110;

// Load original
$src = imagecreatefromstring(file_get_contents($photoPath));
$origW = imagesx($src);
$origH = imagesy($src);

// Calculate scale to fill frame (cover)
$scale = max($targetW / $origW, $targetH / $origH);
$newW = intval($origW * $scale);
$newH = intval($origH * $scale);

// Create destination image
$dst = imagecreatetruecolor($targetW, $targetH);

// Center crop
$srcX = intval(($newW - $targetW) / 2 / $scale);
$srcY = intval(($newH - $targetH) / 2 / $scale);

imagecopyresampled(
    $dst, $src,
    0, 0,         // destination x, y
    $srcX, $srcY, // source x, y
    $targetW, $targetH, // destination width/height
    $origW - $srcX*2, $origH - $srcY*2 // source width/height
);

// Save temp image
$tempPhoto = __DIR__ . '/temp/photo_'.$student['id_number'].'.png';
imagepng($dst, $tempPhoto);
imagedestroy($src);
imagedestroy($dst);

// Insert into Word
$template->setImageValue('photo', [
    'path' => $tempPhoto,
    'width' => $targetW,
    'height' => $targetH
]);


// ---------------- OUTPUT DOCX ----------------
$filename = "ID_" . $student['id_number'] . ".docx";
$tempDir = __DIR__ . '/temp/';
if(!is_dir($tempDir)) mkdir($tempDir, 0777, true);
$tempFile = $tempDir . $filename;

$template->saveAs($tempFile);

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
readfile($tempFile);
@unlink($tempFile);

// Redirect back
header("Location: records.php?printed=1");
exit;