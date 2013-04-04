<?php
/**
 * This file is part of the Fso library.
 * (c) 2013 Vadim Kusakin <vadim.irbis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Irs\Fso;

abstract class Fso
{
    public static function copy($from, $to, $deep = true)
    {
        if ($deep || !self::isOsSupportsLinks()) {
            self::deepCopy($from, $to);
        } else {
            symlink($from, $to);
        }
    }

    protected static function deepCopy($from, $to)
    {
        if (is_dir($from)) {
            @mkdir($to);
            $directory = dir($from);
            while (false !== ($readdirectory = $directory->read())) {
                if ($readdirectory == '.' || $readdirectory == '..') {
                    continue;
                }
                self::deepCopy($from . DIRECTORY_SEPARATOR . $readdirectory, $to . DIRECTORY_SEPARATOR . $readdirectory);
            }
            $directory->close();
        } else {
            copy($from, $to);
        }
    }

    public static function move($from, $to)
    {
        return @rename($from, $to);
    }

    public static function delete($filename)
    {
        if (!file_exists($filename)){
            throw new \InvalidArgumentException("File '$filename' does not exist.");
        }

        if (is_file($filename) || is_link($filename)) {
            if (is_dir($filename) && self::isOsWindowsNt()) {
                return rmdir($filename);
            } else {
                return unlink($filename);
            }
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($filename),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ('.' == $item->getFilename() || '..' == $item->getFilename()) {
                continue;
            }
            if ($item->isDir()) {
                if ($item->isLink() && !self::isOsWindowsNt()) {
                    unlink((string)$item);
                } else {
                    rmdir((string)$item);
            	}
            } else {
                unlink((string)$item);
            }
        }

        unset($iterator);
        rmdir($filename);
    }

    protected static function isOsSupportsLinks()
    {
        return self::isOsWindowsNt()
            ? version_compare(php_uname('r'), '6.0', '>=')
            : true;
    }

    protected static function isOsWindowsNt()
    {
        return php_uname('s') == 'Windows NT';
    }
}
