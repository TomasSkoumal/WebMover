<?php

/**
 * Trida pro FTP spojeni, praci se soubory apod.
 * @copyright  Copyright (c) 2012 Tomáš Skoumal
 * @author     Tomáš Skoumal
 * @package    WebMover/Ftp
 */
class Ftp extends \Nette\Object
{
    private $connection;
    private $error;
    private $homedir;
    private $depth;

    /**
     * pripoji se k ftp serveru a vrati vysledek
     * @param string $server
     * @param string $username
     * @param string $password
     * @param string $homedir
     */
    public function __construct($server, $username, $password, $homedir)
    {
        $this->connection = ftp_connect($server);
        if ($this->connection == false)
            throw new InvalidArgumentException(sprintf('K serveru ("%s") se nepodařilo připojit.', $server));

        if (!@ftp_login($this->connection, $username, $password))
            throw new InvalidArgumentException(sprintf('Špatné uživatelské jméno nebo heslo'));

        if (@ftp_chdir($this->connection, $homedir) == false)
            throw new InvalidArgumentException(sprintf('Špatná složka'));

        $this->homedir = $homedir;

        return true;
    }

    /**
     * Vrati seznam slozek
     * @return array|string
     */
    public function folders()
    {
        $folders = array();
        $folders = array_merge(array('/'), $this->ftp_list($this->homedir));
        return $folders;
    }

    /**
     * Vrati seznam slozek, ve kterych by mohlo byt umistene CMS
     * @param string $dir
     * @return array
     */
    private function ftp_list($dir)
    {
        $result = array();
        $folders = ftp_nlist($this->connection, $dir);
        $folders_raw = ftp_rawlist($this->connection, $dir, true);
        foreach ($folders as $folder) {
            if ($this->is_folder($folders_raw, $folder)) {
                $result[] = $folder;
                $result = array_merge($result, $this->ftp_list($folder));
            }
        }
        $this->depth++;
        return $result;
    }

    /**
     * Overi, zda objekt na FTP je soubor
     * @param array $files_raw
     * @param string $file
     * @return bool
     */
    private function is_file($files_raw, $file)
    {
        $file = ' ' . pathinfo($file, PATHINFO_BASENAME);

        foreach ($files_raw as $val) {
            if (strpos($val, $file) !== false) {
                if ($val[0] == '-') {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Overi, zda objekt na FTP je slozka
     * @param array $folders_raw
     * @param string $folder
     * @return bool
     */
    private function is_folder($folders_raw, $folder)
    {
        $folder = ' ' . pathinfo($folder, PATHINFO_BASENAME);
        foreach ($folders_raw as $val) {
            if (strpos($val, $folder) !== false) {
                if ($val[0] == 'd') {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Ulozi soubor na FTP
     * @param string $path
     * @param string $content
     */
    public function saveFile($path, $content)
    {
        $this->resetDirectory();
        $temp = TEMP_DIR . '/temp.ftp';
        file_put_contents($temp, $content);
        ftp_put($this->connection, $this->path($path), $temp, FTP_BINARY);
        unlink($temp);
    }

    /**
     * Resetuje aktualni adresar FTP spojeni
     */
    private function resetDirectory()
    {
        ftp_chdir($this->connection, "~");
        ftp_chdir($this->connection, $this->homedir);
    }

    /**
     * Vytvori slozku
     * @param string $folder
     */
    public function createFolder($folder)
    {
        ftp_mkdir($this->connection, $this->path($folder));
    }

    /**
     * Smaze obsah adresare
     * @param string $directory
     */
    private function recursiveDelete($directory)
    {
        # here we attempt to delete the file/directory
        if (!(@ftp_rmdir($this->connection, $directory) || @ftp_delete($this->connection, $directory))) {
            # if the attempt to delete fails, get the file listing
            $filelist = @ftp_nlist($this->connection, $directory);
            # loop through the file list and recursively delete the FILE in the list
            if ($filelist != false) {
                foreach ($filelist as $file) {
                    $this->recursiveDelete($file);
                }
            }
        }
    }

    /**
     * Nastavi opravneni slozce a jejim podslozkam
     * @param string $dir
     */
    public function permission_folder($dir)
    {
        $folders = ftp_nlist($this->connection, $dir);
        $folders_raw = ftp_rawlist($this->connection, $dir);

        foreach ($folders as $folder) {
            if (($this->is777FolderInPath($folder) || $this->is777Folder($folder)) && $this->is_folder($folders_raw, $folder)) {
                if ($this->is777Folder($folder)) {
                    // teto slozce nastavim opravneni 777
                    // chmodnu to 2x, jednou pres ftp a jednou prs filesystem
                    @ftp_chmod($this->connection, 0777, $folder); // nekdy se nepodari nastavit opravneni slozce, ale stane se to i kdyzje opravneni v poradku
                    @chmod($this->full_path($folder), 0777);
                    // ted bych mel otestovat opravneni slozky a kdyz je spatne, tak to nahrat do nejakeho seznamu a vypsat uzivateli at to napravi, ale mozna nebude nutne

                    // a take chmodnu vsechny soubory v teto slozce na 666

                    $files = ftp_nlist($this->connection, $folder);
                    $files_raw = ftp_rawlist($this->connection, $folder);
                    foreach ($files as $file) {
                        if ($this->is_file($files_raw, $file)) {
                            @ftp_chmod($this->connection, 0666, $file);
                            @chmod($this->file_full_path($file), 0666);

                            // zjistim opravneni, pokud neni 0666, tak preulozim soubor
                            if (substr(sprintf('%o', fileperms($this->file_full_path($file))), -4) !== '0666') {
                                // otevru soubor pres filesystem
                                $handle = fopen($this->file_full_path($file), "r");
                                // ulozim pomoci ftp do docasneho souboru
                                if (ftp_fput($this->connection, $file . 'temp', $handle, FTP_ASCII)) {
                                    // smazu puvodni soubor
                                    if (ftp_delete($this->connection, $file)) {
                                        //prejmenuji temp soubor
                                        if (ftp_rename($this->connection, $file . 'temp', $file)) {
                                            ftp_chmod($this->connection, 0666, $file);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $this->permission_folder($folder);
            }
        }
    }

    /**
     * Vrati kompletni cestu k souboru
     * @param string $path
     * @return string
     */
    private function file_full_path($path)
    {
        return $this->fs_homedir . $this->last_part_path($path, true);
    }

    /**
     * Vrati kompletni cestu ke slozce
     * @param string $path
     * @return string
     */
    private function full_path($path)
    {
        return $this->fs_homedir . $this->last_part_path($path);
    }

    /**
     * Vrati posledni cast cesty k objektu
     * @param string $path
     * @param bool $file
     * @return string
     */
    private function last_part_path($path, $file = false)
    {
        return (($this->homedir != '/') ? preg_replace('/' . str_replace('/', '\/', $this->homedir) . '/', '', $path, 1) : $path) . (($file == false) ? ('/') : (''));
    }

    /**
     * Overi, zda v teto slozce je neco, cemu se bude nastavovat opravneni
     * @param string $folder
     * @return bool
     */
    private function is777FolderInPath($folder)
    {
        $last_part = $this->last_part_path($folder);

        $targetFolders = array(
            '/app/',
            '/nettelog/',
            '/temp/',
            '/.app/',
            '/.nettelog/',
            '/.temp/',
            '/www/',
        );

        foreach ($targetFolders as $targetFolder)
            if (strpos($last_part, $targetFolder) !== false) return true;
        return false;
    }

    /**
     * Overi, zda se teto slozce ma nastavovat opravneni
     * @param string $folder
     * @return bool
     */
    private function is777Folder($folder)
    {
        $last_part = $this->last_part_path($folder);

        $targetFolders = array(
            '/app/lang/',
            '/nettelog/',
            '/temp/',
            '/.app/lang/',
            '/.nettelog/',
            '/.temp/',
            '/data/',
            '/images/',
            '/www/data/',
            '/www/images/',
        );

        foreach ($targetFolders as $targetFolder)
            if (strpos($last_part, $targetFolder) !== false) return true;
        return false;
    }

     /**
      * Call to undefined method.
      *
      * @param  string  method name
      * @param  array   arguments
      * @return mixed
      * @throws MemberAccessException
      */
     public function __call($name, $args)
     {
         $function = 'ftp_' . $name;
         if (function_exists($function)) {
             foreach ($args as $key => $value) {
                 if ($value instanceof self) {
                     $args[$key] = $value->getImageResource();

                 }
             }
             array_unshift($args, $this->connection);

             $res = call_user_func_array($function, $args);
             return $res;
         }

         //return parent::__call($name, $args);
     }

}