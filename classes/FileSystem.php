<?php

use \Nette\Utils;

class FileSystem extends NObject
{

    public function __construct()
    {

    }

    /**
     *
     */
    public function directoryTree()
    {

    }

    /**
     * List directories in directory
     */
    public static function directoryList($listedDirectory) {
        $directories = array();
        foreach (Finder::findDirectories('*')->in($listedDirectory) as $key => $directory) {
            $directories[] = $directory;
        }
        return $directories;
    }

    public static function calculateDirectorySize($directory)
    {
        if (strpos('Windows', $_SERVER['HTTP_USER_AGENT'])!==false) {
            return FileSystem::calculateDirectorySizeWindows($directory);
        } else {
            return FileSystem::calculateDirectorySizeLinux($directory);
        }
    }

    private static function calculateDirectorySizeWindows($directory) {
        $obj = new COM ( 'scripting.filesystemobject' );
        if ( is_object ( $obj ) )
        {
            $ref = $obj->getfolder ( $directory );
            $obj = null;
            return $ref->size;
        }
        else
        {
            throw new InvalidArgumentException(sprintf('Cannot calculate size of directory %s', $directory));
        }
    }

    private static function calculateDirectorySizeLinux($directory) {
        $io = popen ( '/usr/bin/du -sk ' . $directory, 'r' );
        $size = fgets ( $io, 4096);
        $size = substr ( $size, 0, strpos ( $size, ' ' ) );
        pclose ( $io );
        return $size;
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