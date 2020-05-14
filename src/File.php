<?php
/**
 * @name File.php
 * @link https://alexkratky.com                         Author website
 * @link https://panx.eu/docs/                          Documentation
 * @link https://github.com/AlexKratky/FileUpload/      Github Repository
 * @author Alex Kratky <alex@panx.dev>
 * @copyright Copyright (c) 2020 Alex Kratky
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @description Class representing File Object. Part of panx-framework.
 */

declare(strict_types=1);

namespace AlexKratky;

// TODO: read & write using filestream and for big files

class File {

    private $id = null;
    private $name;
    private $size;
    private $tmp_name;
    private $type;
    private $ext;
    private $path = null;

    const ERROR_MAX_FILE_SIZE = 'max-file-size';
    const ERROR_INVALID_EXTENSION = 'invalid-extension';
    const ERROR_NOT_IMAGE = 'not-image';
    const SEPARATOR = "/";

    public function __construct(?string $name = null, int $size = 0, ?string $tmp_name = null, ?string $type = null, ?string $path = null, $id = null) {
        $this->name = $name;
        $this->size = $size;
        $this->tmp_name = $tmp_name;
        $this->type = $type;
        $this->path = $path;
        $this->id = $id;
        $this->ext = ($name === null ? null : strtolower(explode("." ,$name)[count(explode(".", $name)) - 1]));
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function setId(?int $id) {
        $this->id = $id;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function setName(?string $name) {
        $this->name = $name;
    }

    public function getSize(): int {
        return $this->size;
    }

    public function getTempName(): ?string {
        return $this->tmp_name;
    }

    public function getType(): ?string {
        return $this->type;
    }

    public function getExtension(): ?string {
        return $this->ext;
    }

    public function getPath(): ?string {
        return $this->path;
    }

    public function setPath(?string $path) {
        $this->path = $path;
    }

    public function getHash(): ?string {
        if(!$this->path) return null;
        return md5_file($this->path);
    }

    /**
     * @param int $size Maximum file size.
     * @param array $ext Allowed extensions.
     * @param bool $onlyImage Determines if the file need to be image.
     * @return bool Returns true if current file is valid (have smaller size, its extension is allowed and match the $onlyImage rule), false otherwise.
     */
    public function validate(int $size = 0, array $ext = ['*'], bool $onlyImage = false): bool {
        if($size !== 0) {
            if($this->size > $size) {
                return self::ERROR_MAX_FILE_SIZE;
            }
        }

        if (!in_array('*', $ext)) {
            if (!in_array($this->ext, $ext)) {
                return self::ERROR_INVALID_EXTENSION;
            }
        }

        if($onlyImage) {
            if(getimagesize($this->tmp_name) === false) {
                return self::ERROR_NOT_IMAGE;
            }
        }

        return true;
    }

    /**
     * Compare two files between each other (size, type and hash).
     * @param File $file
     * @return bool
     */
    public function compareTo(File $file): bool {
        if ($this->getSize() !== $file->getSize()) return false;
        if ($this->getType() !== $file->getType()) return false;
        if ($this->getHash() !== $file->getHash()) return false;
        return true;
    }

    /**
     * Compare two files between each other (size, type, hash, name and extension).
     * @param File $file
     * @return bool
     */
    public function compareToStrict(File $file): bool {
        if (!$this->compareTo($file)) return false;
        if ($this->getName() !== $file->getName()) return false;
        if ($this->getExtension() !== $file->getExtension()) return false;
        return true;
    }

    /**
     * Initialize File instance by file path.
     * @param string $source The path of file.
     * @return File
     */
    public function init(string $source): File {
        $this->name = explode(self::SEPARATOR, $source);
        $this->name = $this->name[count($this->name) - 1];
        $this->size = filesize($source);
        $this->type = mime_content_type($source);
        $this->path = $source;
        $this->ext = ($this->name === null ? null : strtolower(explode(".", $this->name)[count(explode(".", $this->name)) - 1]));
        return $this;
    }

    /**
     * Returns a File instance created by file path.
     * @param string $source The path of file.
     * @return File
     */
    public static function create(string $source): File {
        $file = new File();
        $file->init($source);
        return $file;
    }

    /**
     * Rename a file.
     * @param string $name New name.
     * @param string $ext If sets to null it will use current extension, otherwise change the extension.
     * @return bool Returns true on success, false otherwise.
     */
    public function rename(string $name, ?string $ext = null): bool {
        $path = explode(self::SEPARATOR, $this->getPath());
        $old_name = $path[count($path) - 1];
        $ext = $ext ?? (strpos($old_name, ".") !== false ? explode(".", $old_name)[count(explode(".", $old_name)) - 1] : '');
        $ext = $ext !== '' ? "." . $ext : ''; 
        array_pop($path);
        $path = join(self::SEPARATOR, $path) . self::SEPARATOR;
        return rename($this->getPath(), $path . $name . $ext);
    }

    /**
     * Delete a file.
     * @return bool Returns true on success, false otherwise.
     */
    public function delete(): bool {
        return unlink($this->getPath());
    }

    /**
     * Move a file to a new location.
     * @param string $destination New destination.
     * @param string $name If sets to null it will use current name, otherwise change the name.
     * @param string $ext If sets to null it will use current extension, otherwise change the extension.
     * @return bool Returns true on success, false otherwise.
     */
    public function move(string $destination, ?string $name = null, ?string $ext = null): bool {
        $path = explode(self::SEPARATOR, $this->getPath());
        $old_name = $path[count($path) - 1];
        $ext = $ext ?? (strpos($old_name, ".") !== false ? explode(".", $old_name)[count(explode(".", $old_name)) - 1] : '');
        $ext = $ext !== '' ? "." . $ext : '';
        $name = $name ?? $old_name;
        if(substr($destination, -strlen($destination)) !== self::SEPARATOR) $destination .= self::SEPARATOR;
        return rename($this->getPath(), $destination . $name . $ext);
    }

    /**
     * Copy a file to a location.
     * @param string $destination Location.
     * @param string $name If sets to null it will use current name, otherwise change the name.
     * @param string $ext If sets to null it will use current extension, otherwise change the extension.
     * @return bool Returns true on success, false otherwise.
     */
    public function copy(string $destination, ?string $name = null, ?string $ext = null ): bool {
        $path = explode(self::SEPARATOR, $this->getPath());
        $old_name = $path[count($path) - 1];
        $ext = $ext ?? (strpos($old_name, ".") !== false ? explode(".", $old_name)[count(explode(".", $old_name)) - 1] : '');
        $ext = $ext !== '' ? "." . $ext : '';
        $name = $name ?? $old_name;
        if (substr($destination, -strlen($destination)) !== self::SEPARATOR) $destination .= self::SEPARATOR;
        return copy($this->getPath(), $destination . $name . $ext);
    }

    /**
     * Writes text to the file.
     * @return int|bool Returns the number of written bytes or false on failure.
     */
    public function write($text) {
        return file_put_contents($this->getPath(), $text);
    }

    /**
     * Reads text from the file.
     * @param int $maxlen Maximum bytes to read.
     * @return string The readed value.
     */
    public function read($maxlen = null) {
        return file_get_contents($this->getPath(), false, null, 0, $maxlen);
    }

}
