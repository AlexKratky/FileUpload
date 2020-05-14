<?php
/**
 * @name FileUpload.php
 * @link https://alexkratky.com                         Author website
 * @link https://panx.eu/docs/                          Documentation
 * @link https://github.com/AlexKratky/FileUpload/      Github Repository
 * @author Alex Kratky <alex@panx.dev>
 * @copyright Copyright (c) 2020 Alex Kratky
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @description Class to process file upload. Part of panx-framework.
 */

declare(strict_types=1);

namespace AlexKratky;

use AlexKratky\db;
use AlexKratky\File;
use AlexKratky\IFilenameGenerator;
use AlexKratky\FilenameGenerator;

class FileUpload {

    private static $_allowedExtensions = ["*"];
    private static $_maxFileSize = 0;
    private static $_onlyImages = false;
    private static $_uploadDirectory = __DIR__ . "/uploads/";
    private static $_savesToDatabase = true;
    private static $_filenameGenerator = FilenameGenerator::class;
    private static $_otherFields = []; // other fields in db

    private $allowedExtensions;
    private $maxFileSize;
    private $onlyImages;
    private $uploadDirectory;
    private $savesToDatabase;
    private $filenameGenerator;
    private $otherFields = [];

    private $userId = null;
    private $title = null;

    const ERROR_UPLOAD  = "failed-to-upload";
    const ERROR_NO_FILE = "no-file";

    const TYPE_SUCCESS = "success";
    const TYPE_ERROR = "error";

    //////////////////// Static

    public static function _setAllowedExtensions(array $allowedExtensions)
    {
        self::$_allowedExtensions = $allowedExtensions;
    }

    public static function _setMaxSize(int $maxFileSize)
    {
        self::$_maxFileSize = $maxFileSize;
    }

    public static function _setOnlyImages(bool $onlyImages)
    {
        self::$_onlyImages = $onlyImages;
    }

    public static function _setUploadDirectory(string $uploadDirectory)
    {
        self::$_uploadDirectory = $uploadDirectory;
    }

    public static function _setSavesToDatabase(bool $savesToDatabase)
    {
        self::$_savesToDatabase = $savesToDatabase;
    }

    public static function _setFilenameGenerator(IFilenameGenerator $filenameGenerator)
    {
        self::$_filenameGenerator = $filenameGenerator;
    }

    public static function _setOtherField(string $name, $value) {
        self::$_otherFields[$name] = $value;
    }

    //////////////////////////

    public function __construct()
    {
        $this->allowedExtensions = self::$_allowedExtensions;
        $this->maxFileSize = self::$_maxFileSize;
        $this->onlyImages = self::$_onlyImages;
        $this->uploadDirectory = self::$_uploadDirectory;
        $this->savesToDatabase = self::$_savesToDatabase;
        $this->filenameGenerator = self::$_filenameGenerator;
        $this->otherFields  = self::$_otherFields;
    } 

    public function setAllowedExtensions(array $allowedExtensions)
    {
        $this->allowedExtensions = $allowedExtensions;
    }

    public function setMaxSize(int $maxFileSize)
    {
        $this->maxFileSize = $maxFileSize;
    }

    public function setOnlyImages(bool $onlyImages)
    {
        $this->onlyImages = $onlyImages;
    }

    public function setUploadDirectory(string $uploadDirectory)
    {
        $this->uploadDirectory = $uploadDirectory;
    }

    public function setSavesToDatabase(bool $savesToDatabase)
    {
        $this->savesToDatabase = $savesToDatabase;
    }

    public function setFilenameGenerator(IFilenameGenerator $filenameGenerator)
    {
        $this->filenameGenerator = $filenameGenerator;
    }

    public function setOtherField(string $name, $value)
    {
        $this->otherFields[$name] = $value;
    }

    public function setTitle(string $title) {
        $this->title = $title;
    }

    public function setUserId(int $userId) {
        $this->userId = $userId;
    }

    //////////////////////////

    public function process(string $attribute) {
        $errors = ['upload' => self::TYPE_ERROR, 'errors' => []];
        $uploaded_files = ['upload' => self::TYPE_SUCCESS, 'files' => []];
        if (isset($_FILES[$attribute])) {
            if (!is_array($_FILES[$attribute]['name'])) return $this->processSingleFile($attribute);
            if (count($_FILES[$attribute]['name']) === 0) return self::ERROR_NO_FILE;
            for ($i = 0; $i < count($_FILES[$attribute]['name']); $i++) {
                $file = new File(
                    $_FILES[$attribute]['name'][$i],
                    $_FILES[$attribute]['size'][$i],
                    $_FILES[$attribute]['tmp_name'][$i],
                    $_FILES[$attribute]['type'][$i]
                );
                $validate = $this->validate($file);
                if($validate === true) {
                    $id = $this->saveFile($file);
                    if ($id === false || $id === null) {
                        $errors['errors'][] = [self::ERROR_UPLOAD, $file];
                    } else if ($id !== true) {
                        $file->setId((int) $id);
                    }
                    $uploaded_files['files'][] = $file;
                } else {
                    $errors['errors'][] = [$validate, $file];
                }
            }
        } else {
            return self::ERROR_NO_FILE;
        }
        return (count($errors['errors']) === 0 ? ($uploaded_files) : $errors);
    }

    public function processSingleFile(string $attribute) {
        if (empty($_FILES[$attribute]['name'])) return self::ERROR_NO_FILE;
        $file = new File(
            $_FILES[$attribute]['name'],
            $_FILES[$attribute]['size'],
            $_FILES[$attribute]['tmp_name'],
            $_FILES[$attribute]['type']
        );
        $validate = $this->validate($file);
        if ($validate === true) {
            $id = $this->saveFile($file);
            if ($id === false || $id === null) {
                return ['upload' => self::TYPE_ERROR, 'errors' => [self::ERROR_UPLOAD, $file]];
            } else if($id !== true) {
                $file->setId((int) $id);
            }
            return ['upload' => self::TYPE_SUCCESS, 'files' => [$file]];
        } else {
            return ['upload' => self::TYPE_ERROR, 'errors' => [$validate, $file]];
        }
    }

    public function processPostString($attribute) {
        if (empty($_POST[$attribute])) return self::ERROR_NO_FILE;
        $file = new File();

        $dest = $this->filenameGenerator::generate($file, $this->uploadDirectory);
        if(!file_put_contents($dest, file_get_contents($_POST[$attribute]))) {
            return ['upload' => self::TYPE_ERROR, 'errors' => [self::ERROR_UPLOAD, $file]];
        }
        $file->init($dest);

        $validate = $this->validate($file);
        if($validate !== true) {
            $file->delete();
            return ['upload' => self::TYPE_ERROR, 'errors' => [$validate, $file]];
        }

        if ($this->savesToDatabase) {
            $id = $this->saveFileToDatabase($file);
            $file->setId((int) $id);
        }

        return ['upload' => self::TYPE_SUCCESS, 'files' => [$file]];
    }

    public function saveFile(File $file) {
        $dest = $this->filenameGenerator::generate($file, $this->uploadDirectory);
        $file->setPath($dest);
        $file->setName(str_replace($this->uploadDirectory, "", $dest));
        
        if(!move_uploaded_file($file->getTempName(), $dest)) {
            return false;
        }
        
        if($this->savesToDatabase) return $this->saveFileToDatabase($file);
        
        return true;
    }

    public function validate(File $file) {
        return $file->validate($this->maxFileSize, $this->allowedExtensions, $this->onlyImages);
    }

    public function saveFileToDatabase(File $file) {
        $otherColumns = "";
        $otherMarks = "";
        $otherArr = [];
        foreach ($this->otherFields as $name => $value) {
            $otherColumns .= ", `{$name}`";
            $otherMarks .= ", ?";
            $otherArr[] = $value;
        }
        return db::query("INSERT INTO `files` (`NAME`, `BASE_NAME`, `EXT`, `SIZE`, `HASH`, `PATH`, `TYPE`, `TITLE`, `USER_ID`".$otherColumns.") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?".$otherMarks.")", array_merge(
            array(
                $file->getName(),
                str_replace('.' . $file->getExtension(), "", $file->getName()),
                $file->getExtension(),
                $file->getSize(),
                md5_file($file->getPath()),
                $file->getPath(),
                $file->getType(),
                $this->title,
                $this->userId
            ),
            $otherArr
        ));
    }

}
