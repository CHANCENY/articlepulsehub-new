<?php

namespace Simp\Public\Module\news_org_api\src\Plugin;

use Simp\Core\modules\files\entity\File;

trait Helper
{
    protected function convertToWebP(string $sourceFile, string $destinationFile, int $quality = 80): bool
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
    public function processCover(string $full_path, string $needleFile): void
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
                    unlink($tempDest);
                }
            }
        }
    }
}