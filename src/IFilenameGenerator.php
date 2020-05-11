<?php
/**
 * @name IFilenameGenerator.php
 * @link https://alexkratky.cz                          Author website
 * @link https://panx.eu/docs/                          Documentation
 * @link https://github.com/AlexKratky/panx-framework/  Github Repository
 * @author Alex Kratky <alex@panx.dev>
 * @copyright Copyright (c) 2020 Alex Kratky
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @description FilenameGenerator interface. Part of panx-framework.
 */

declare(strict_types=1);

namespace AlexKratky;

use AlexKratky\File;

interface IFilenameGenerator {

    public static function generate(File $file, string $uploadDirectory);

}
