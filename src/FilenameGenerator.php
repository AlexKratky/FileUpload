<?php
/**
 * @name FilenameGenerator.php
 * @link https://alexkratky.com                         Author website
 * @link https://panx.eu/docs/                          Documentation
 * @link https://github.com/AlexKratky/FileUpload/      Github Repository
 * @author Alex Kratky <alex@panx.dev>
 * @copyright Copyright (c) 2020 Alex Kratky
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @description Name generator for files. Part of panx-framework.
 */

declare(strict_types=1);

namespace AlexKratky;

use AlexKratky\File;
use AlexKratky\IFilenameGenerator;

class FilenameGenerator implements IFilenameGenerator {

    const NAME_LENGTH = 12;

    public static function generate(File $file, string $uploadDirectory) {
        $name = self::getName($file, $uploadDirectory);
        while(file_exists($name)) {
            $name = self::getName($file, $uploadDirectory);
        }
        return $name;
    }

    private static function getName(File $file, string $uploadDirectory) {
        return $uploadDirectory . (substr(md5(openssl_random_pseudo_bytes(20)),-self::NAME_LENGTH)) 
        . (($file->getExtension() === null || $file->getName() === $file->getExtension()) ? '' : '.' . $file->getExtension());
    }

}
