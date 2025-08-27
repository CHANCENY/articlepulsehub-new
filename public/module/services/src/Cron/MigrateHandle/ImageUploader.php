<?php

namespace Simp\Public\Module\services\src\Cron\MigrateHandle;

use Simp\Core\modules\files\entity\File;

class ImageUploader
{
    public bool $is_cover = false;
    public int $file_fid = 0;

    public function __construct(private readonly string $name, bool $is_cover = false)
    {
        $dir = __DIR__ . "/old/public/all";
        $needleFile = pathinfo($this->name, PATHINFO_FILENAME);   // e.g. 31699_Rihanna

        // Scan directory
        $files = array_diff(scandir($dir), ['.', '..']);

        // Try to find a file that contains the needle (case-insensitive)
        $found = null;
        foreach ($files as $file) {
            $haystackFile = pathinfo($file, PATHINFO_FILENAME);
            if (stripos($haystackFile, $needleFile) !== false) {
                $found = $dir . "/" . $file;
                break;
            }
        }

        $full_path = $found ?? $dir . "/" . $this->name;

        // If a valid file exists, proceed
        if (file_exists($full_path)) {

            if ($is_cover) {
                $this->processCover($full_path, $needleFile);
            } else {
                $this->processImage($full_path, $needleFile);
            }

        } else {
            if ($is_cover) {
                $this->is_cover = false;
            }
        }
    }

    private function processCover(string $full_path, string $needleFile): void
    {
        $destDir = "public://blogs/cover";
        if (!is_dir($destDir)) {
            mkdir($destDir, 0777, true);
        }

        $tempDest = $destDir . "/" . uniqid() . ".webp";

        if ($this->convertToWebP($full_path, $tempDest, 80)) {
            $index = 0;
            $useDest = $destDir . "/" . $needleFile . ".webp";

            while (file_exists($useDest)) {
                $index++;
                $useDest = $destDir . "/" . $needleFile . "_" . $index . ".webp";
            }

            if (copy($tempDest, $useDest)) {
                $file = File::create([
                    'name' => basename($useDest),
                    'uri' => $useDest,
                    'size' => filesize($useDest),
                    'mime_type' => 'image/webp',
                    'extension' => 'webp',
                    'uid' => 1,
                ]);

                if ($file instanceof File) {
                    $this->file_fid = $file->getFid();
                    $this->is_cover = true;
                    unlink($tempDest);
                }
            }
        }
    }

    private function processImage(string $full_path, string $needleFile): void
    {
        $destDir = "public://blogs/images";
        if (!is_dir($destDir)) {
            mkdir($destDir, 0777, true);
        }

        $ext = pathinfo($full_path, PATHINFO_EXTENSION);
        $useDest = $destDir . "/" . $this->name;
        $index = 0;

        while (file_exists($useDest)) {
            $index++;
            $useDest = $destDir . "/" . $needleFile . "_" . $index . "." . $ext;
        }

        if (copy($full_path, $useDest)) {
            $file = File::create([
                'name' => basename($useDest),
                'uri' => $useDest,
                'size' => filesize($useDest),
                'mime_type' => mime_content_type($useDest),
                'extension' => pathinfo($useDest, PATHINFO_EXTENSION),
                'uid' => 1,
            ]);

            if ($file instanceof File) {
                $this->file_fid = $file->getFid();
            }
        }
    }

    function convertToWebP(string $sourceFile, string $destinationFile, int $quality = 80): bool
    {
        $info = getimagesize($sourceFile);
        if (!$info) {
            return false; // Not an image
        }

        $mime = $info['mime'];
        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($sourceFile);
                break;
            case 'image/png':
                $image = imagecreatefrompng($sourceFile);
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($sourceFile);
                break;
            default:
                return false; // Unsupported type
        }

        $result = imagewebp($image, $destinationFile, $quality);
        imagedestroy($image);

        return $result;
    }

    public static function factory(string $name, bool $is_cover = false): ImageUploader
    {
        return new self($name, $is_cover);
    }
}
