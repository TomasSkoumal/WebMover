<?php

class FileSystem
{

    public function __construct()
    {

    }

    public function directoryTree()
    {

    }

    public function calculateDirectorySize()
    {

    }

    public function copyDirectory($source, $target)
    {
        $source = rtrim($source, '/');
        $target = rtrim($target, '/');

        if (!is_dir($source)) {
            throw new InvalidArgumentException(sprintf('Source ("%s") is not a valid directory.', $source));
        }

        if (!is_readable($source)) {
            throw new InvalidArgumentException(sprintf('Source ("%s") is not readable.', $source));
        }

        if (!is_dir($target))
            $r = mkdir($target);

        if (!is_dir($target)) {
            throw new InvalidArgumentException(sprintf('Target ("%s") is not a valid directory.', $target));
        }

        if (!is_writable($target)) {
            throw new InvalidArgumentException(sprintf('Target ("%s") is not a writeable.', $target));
        }

        $dirs = array('');

        while (count($dirs)) {
            $dir = array_shift($dirs);
            $base = $source . '/' . $dir;
            $d = dir($base);
            if (!$d) {
                throw new Exception(sprintf('Unable to open directory "%s".', $base));
            }

            while (false !== ($entry = $d->read())) {
                // skip self and parent directories
                if (in_array($entry, array('.', '..'))) {
                    continue;
                }

                // put subdirectories on stack
                if (is_dir($base . '/' . $entry)) {
                    $dirs[] = $dir . '/' . $entry;
                    continue;
                }

                // copy file
                $from = $base . '/' . $entry;
                $to = $target . '/' . $dir . '/' . $entry;
                $result = copy($from, $to);
                if (!$result) {
                    throw new Exception(sprintf('Failed to copy file (from "%s" to "%s").', $from, $to));
                }
            }
            $d->close();
        }
    }

    public function zipDirectory($source, $fileName)
    {
        $zipFile = new createZipDirectory();
        $zipFile->get_files_from_folder($source,'/');

        $fileResource = fopen ($fileName, 'wb');
        fwrite ($fileResource, $zipFile->getZippedfile());
        fclose ($fileResource);
    }
}