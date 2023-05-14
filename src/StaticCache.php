<?php
/**
 * @brief staticCache, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Franck Paul and contributors
 *
 * @copyright Franck Paul carnet.franck.paul@gmail.com
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\staticCache;

use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Exception;

class StaticCache
{
    protected $cache_dir;
    protected $cache_key;

    public function __construct($cache_dir, $cache_key)
    {
        $cache_dir = Path::real($cache_dir, false);

        if (!is_dir($cache_dir)) {
            Files::makeDir($cache_dir);
        }

        if (!is_writable($cache_dir)) {
            throw new Exception('Cache directory is not writable.');
        }

        $k = str_split($cache_key, 2);

        $this->cache_dir = sprintf('%s/%s/%s/%s/%s', $cache_dir, $k[0], $k[1], $k[2], $cache_key);
    }

    public static function initFromURL($cache_dir, $url)
    {
        $host = preg_replace('#^(https?://(?:.+?))/(.*)$#', '$1', (string) $url);

        return new self($cache_dir, md5($host));
    }

    public function storeMtime($mtime)
    {
        $file = $this->cache_dir . '/mtime';
        $dir  = dirname($file);

        if (!is_dir($dir)) {
            Files::makeDir($dir, true);
        }

        touch($file, $mtime);
    }

    public function getMtime()
    {
        $file = $this->cache_dir . '/mtime';

        if (!file_exists($file)) {
            return false;
        }

        return filemtime($file);
    }

    public function storePage($key, $content_type, $content, $mtime, $headers)
    {
        if (trim((string) $content) == '') {
            throw new Exception('No content to cache');
        }

        $file     = $this->getCacheFileName($key);
        $dir      = dirname($file);
        $tmp_file = $dir . '/._' . basename($file);

        if (!is_dir($dir)) {
            Files::makeDir($dir, true);
        }

        $fp = @fopen($tmp_file, 'wb');
        if (!$fp) {
            throw new Exception('Unable to create cache file.');
        }

        // Content-type
        fwrite($fp, $content_type . "\n");

        // Additional headers
        $remove_headers = ['Date', 'Last-Modified', 'Cache-Control'];
        foreach ($headers as $header) {
            // Ignore some headers as:
            // Date: Mon, 14 Feb 2022 15:29:55 GMT
            // Last-Modified: Mon, 14 Feb 2022 15:29:46 GMT
            // Cache-Control: must-revalidate, max-age=86400
            $cache_header = true;
            foreach ($remove_headers as $remove) {
                if (stripos($header, $remove) === 0) {
                    $cache_header = false;

                    break;
                }
            }
            if ($cache_header) {
                fwrite($fp, $header . "\n");
            }
        }

        // Blank line separator
        fwrite($fp, "\n");

        // Page content
        fwrite($fp, $content);
        fclose($fp);

        if (file_exists($file)) {
            unlink($file);
        }
        rename($tmp_file, $file);
        touch($file, $mtime);
        $this->storeMtime($mtime);
        Files::inheritChmod($file);
    }

    public function fetchPage($key, $mtime)
    {
        $file = $this->getCacheFileName($key);
        if (!file_exists($file) || !is_readable($file) || !Files::isDeletable($file)) {
            return false;
        }

        $page_mtime = filemtime($file);
        if ($mtime > $page_mtime) {
            return false;
        }

        $fp = @fopen($file, 'rb');
        if (!$fp) {
            return false;
        }

        // Get content-type, 1st line of cached file
        $content_type = trim((string) fgets($fp));

        // This first header might be not necessary (it should already be in stored headers in cache file)
        header('Content-Type: ' . $content_type . '; charset=UTF-8');

        header('X-Dotclear-Static-Cache: true; mtime: ' . $page_mtime);

        // Send additionnal cached headers (up to 1st empty line)
        do {
            $header = trim((string) fgets($fp));
            if ($header !== '') {
                header($header);
            }
        } while ($header !== '');

        // Send everything else (cached content)
        fpassthru($fp);
        fclose($fp);

        return true;
    }

    public function dropPage($key)
    {
        $file = $this->getCacheFileName($key);
        if (!file_exists($file) || !Files::isDeletable($file)) {
            return false;
        }

        unlink($file);
    }

    public function getPageFile($key)
    {
        $file = $this->getCacheFileName($key);
        if (file_exists($file)) {
            return $file;
        }

        return false;
    }

    protected function getCacheFileName($key)
    {
        $key = md5($key);
        $k   = str_split($key, 2);

        return $this->cache_dir . '/' . sprintf('%s/%s/%s/%s', $k[0], $k[1], $k[2], $key);
    }
}
