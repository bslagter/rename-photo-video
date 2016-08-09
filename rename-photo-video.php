<?php
/**
 * Author: Bert Slagter <bert@procurios.nl>
 * License: MIT
 */

class RenamePhotoVideo
{
    /**
     * @param string $path
     */
    public function rename($path)
    {
        foreach (new DirectoryIterator($path) as $fileInfo) {

            if ($this->shouldSkipItem($fileInfo)) {
                continue;
            }

            if ($fileInfo->isDir()) {
                $this->rename($fileInfo->getPathname());
                continue;
            }

            if ($this->isPhoto($fileInfo)) {
                $this->processPhoto($fileInfo);
                continue;
            }

            if ($this->isVideo($fileInfo)) {
                $this->processVideo($fileInfo);
                continue;
            }
        }
    }

    /**
     * @param DirectoryIterator $fileInfo
     * @return bool
     */
    private function shouldSkipItem(DirectoryIterator $fileInfo)
    {
        if ($fileInfo->isDot()) {
            return true;
        }

        if ($fileInfo->isLink()) {
            return true;
        }

        if (substr($fileInfo->getFilename(), 0, 1) === '.') {
            return true;
        }

        return false;
    }

     /**
     * @param DirectoryIterator $fileInfo
     * @return bool
     */
    private function isPhoto(DirectoryIterator $fileInfo)
    {
        if (in_array(strtolower($fileInfo->getExtension()), ['jpg', 'jpeg'])) {
            return true;
        }

        return false;
    }

    /**
     * @param DirectoryIterator $fileInfo
     */
    private function processPhoto(DirectoryIterator $fileInfo)
    {
        $date = $this->getDateFromPhoto($fileInfo);
        $this->renameFile($fileInfo, $date);
    }

    /**
     * @param DirectoryIterator $fileInfo
     * @return DateTimeImmutable
     */
    private function getDateFromPhoto(DirectoryIterator $fileInfo)
    {
        $exif = @exif_read_data($fileInfo->getPathname());

        if (!empty($exif['DateTime'])) {
            try {
                return new DateTimeImmutable($exif['DateTime']);
            } catch (Exception $e) {
                // fallback to file date
            }
        }

        return DateTimeImmutable::createFromFormat('U', $fileInfo->getMTime());
    }

    /**
     * @param DirectoryIterator $fileInfo
     * @return bool
     */
    private function isVideo(DirectoryIterator $fileInfo)
    {
        if (in_array(strtolower($fileInfo->getExtension()), ['mov', 'mp4', 'm4v'])) {
            return true;
        }

        return false;
    }

    /**
     * @param DirectoryIterator $fileInfo
     */
    private function processVideo(DirectoryIterator $fileInfo)
    {
        $date = $this->getDateFromVideo($fileInfo);
        $this->renameFile($fileInfo, $date);
    }

    /**
     * @param DirectoryIterator $fileInfo
     * @return DateTimeImmutable
     */
    private function getDateFromVideo(DirectoryIterator $fileInfo)
    {
        @exec('ffprobe -show_format -v quiet ' . escapeshellarg($fileInfo->getPathname()) . ' 2>&1', $output);

        $output = implode("\n", $output);

        if (preg_match('/^TAG:date=(.*?)$/ism', $output, $match)) {
            return new DateTimeImmutable($match[1]);
        }
        if (preg_match('/^TAG:creation_time=(.*?)$/ism', $output, $match)) {
            return new DateTimeImmutable($match[1]);
        }

        return DateTimeImmutable::createFromFormat('U', $fileInfo->getMTime());
    }

    /**
     * @param DirectoryIterator $fileInfo
     * @param DateTimeImmutable $date
     */
    private function renameFile(DirectoryIterator $fileInfo, DateTimeImmutable $date)
    {
        $oldFileName = $fileInfo->getPathname();

        preg_match('/(\S+)$/is', $fileInfo->getFilename(), $match);
        $originalFileName = $match[1];

        $parentName = basename($fileInfo->getPath());

        $newFileName =
            $fileInfo->getPath() .
            DIRECTORY_SEPARATOR .
            $date->format('Y-m-d') . ' - ' . $parentName . ' - ' . $originalFileName;

        if ($oldFileName == $newFileName) {
            //echo 'Skipping already renamed file: ' . $oldFileName . "\n";
            return;
        }

        echo "\n" . 'Renaming file' . "\n";
        echo 'Old: ' . $oldFileName . "\n";
        echo 'New: ' . $newFileName . "\n\n";

        rename($oldFileName, $newFileName);
    }
}

$worker = new RenamePhotoVideo();
$worker->rename(realpath('.'));