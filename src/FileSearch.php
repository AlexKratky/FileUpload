<?php
/**
 * @name FileSearch.php
 * @link https://alexkratky.cz                          Author website
 * @link https://panx.eu/docs/                          Documentation
 * @link https://github.com/AlexKratky/panx-framework/  Github Repository
 * @author Alex Kratky <alex@panx.dev>
 * @copyright Copyright (c) 2020 Alex Kratky
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @description Search in files. Part of panx-framework.
 */

declare(strict_types=1);

namespace AlexKratky;

class FileSearch {

    const PARAM_ID   = 'ID';
    const PARAM_NAME = 'NAME';
    const PARAM_SIZE = 'SIZE';
    const PARAM_TYPE = 'TYPE';
    const PARAM_EXT  = 'EXT';
    const PARAM_PATH = 'PATH';

    public static $maxReadSize = 524288; // 512 kB

    const SEPARATOR = "/";

    public static function searchByFile(File $file, bool $db = true, ?string $directory = null): array {
        $res = [];
        if($db) {
            $files = db::multipleSelect("SELECT * FROM `files` WHERE `HASH`=?", array($file->getHash()));
            foreach ($files as $f) {
                $f = new File($f["NAME"], (int) $f["SIZE"], null, $f["TYPE"], $f["PATH"], $f["ID"]);
                if ($file->compareTo($f)) {
                    array_push($res, $f);
                }
            }
        } else {
            $files = self::getFilesInDirectory($directory);
            foreach ($files as $f) {
                $f = File::create($f);
                if ($file->compareTo($f)) {
                    array_push($res, $f);
                }
            }
        }
        return $res;
    }

    public static function searchByParameter(array $param, bool $db = true, ?string $directory = null) {
        $res = [];
        if ($db) {
            $files = db::multipleSelect("SELECT * FROM `files` WHERE `".$param[0]."`=?", array($param[1]));
            foreach ($files as $file) {
                $file = new File($file["NAME"], (int) $file["SIZE"], null, $file["TYPE"], $file["PATH"], $file["ID"]);
                $res[] = $file;
            }
        } else {
            $files = self::getFilesInDirectory($directory);
            foreach ($files as $f) {
                $f = File::create($f);
                $match = false;
                switch($param[0]) {
                    case self::PARAM_NAME:
                        $match = ($param[1] === $f->getName());
                        break;
                    case self::PARAM_SIZE:
                        $match = ($param[1] === $f->getSize());
                        break;
                    case self::PARAM_TYPE:
                        $match = ($param[1] === $f->getType());
                        break;
                    case self::PARAM_EXT:
                        $match = ($param[1] === $f->getExtension());
                        break;
                    case self::PARAM_PATH:
                        $match = ($param[1] === $f->getPath());
                        break;
                }
                if ($match) $res[] = $f;
            }
        }
        return $res;
    }

    public static function searchByText(string $text, bool $db = true, ?string $directory = null) {
        $res = [];
        if ($db) {
            $files = db::multipleSelect("SELECT `PATH` FROM `files`");
            foreach ($files as $file) {
                $file = File::create($file["PATH"]);
                if(strpos($file->read(self::$maxReadSize), $text) !== false) {
                    $res[] = $file;
                } 
            }
        } else {
            $files = self::getFilesInDirectory($directory);
            foreach ($files as $file) {
                $file = File::create($file);
                if (strpos($file->read(self::$maxReadSize), $text) !== false) {
                    $res[] = $file;
                }
            }
        }
        return $res;
    }

    public static function getFilesInDirectory($directory) {
        $res = [];
        $files = scandir($directory);
        foreach ($files as $file) {
            if($file == "." || $file == "..") continue;
            if(is_dir($directory . self::SEPARATOR . $file)) {
                $res = array_merge($res, self::getFilesInDirectory($directory . self::SEPARATOR . $file));
            } else {
                $res[] = $directory . self::SEPARATOR . $file;
            }
        }
        return $res;
    }
}