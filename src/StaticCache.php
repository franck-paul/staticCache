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
    protected string $cache_dir;
    protected string $cache_key;

    public function __construct(string $cache_dir, string $cache_key)
    {
        $cache_dir = Path::real($cache_dir, false);

        if ($cache_dir !== false && !is_dir($cache_dir)) {
            Files::makeDir($cache_dir);
        }

        if ($cache_dir === false || !is_writable($cache_dir)) {
            throw new Exception('Cache directory is not writable.');
        }

        $k = str_split($cache_key, 2);

        $this->cache_dir = sprintf('%s/%s/%s/%s/%s', $cache_dir, $k[0], $k[1], $k[2], $cache_key);
    }

    public static function initFromURL(string $cache_dir, string $url): self
    {
        $host = (string) preg_replace('#^(https?://(?:.+?))/(.*)$#', '$1', (string) $url);

        return new self($cache_dir, md5($host));
    }

    public function storeMtime(int $mtime): void
    {
        $file = $this->cache_dir . '/mtime';
        $dir  = dirname($file);

        if (!is_dir($dir)) {
            Files::makeDir($dir, true);
        }

        touch($file, $mtime);
    }

    /**
     * Gets the mtime.
     *
     * @return     false|int  The mtime.
     */
    public function getMtime(): int|bool
    {
        $file = $this->cache_dir . '/mtime';

        if (!file_exists($file)) {
            return false;
        }

        return filemtime($file);
    }

    /**
     * Stores a page.
     *
     * @param      string           $key           The key
     * @param      string           $content_type  The content type
     * @param      string           $content       The content
     * @param      int              $mtime         The mtime
     * @param      array<string>    $headers       The headers
     *
     * @throws     Exception
     */
    public function storePage(string $key, string $content_type, string $content, int $mtime, array $headers): void
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

    public function fetchPage(string $key, int $mtime): bool
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

    public function dropPage(string $key): bool
    {
        $file = $this->getCacheFileName($key);
        if (!file_exists($file) || !Files::isDeletable($file)) {
            return false;
        }

        return unlink($file);
    }

    public function getPageFile(string $key): string|bool
    {
        $file = $this->getCacheFileName($key);
        if (file_exists($file)) {
            return $file;
        }

        return false;
    }

    protected function getCacheFileName(string $key): string
    {
        $key = md5($key);
        $k   = str_split($key, 2);

        return $this->cache_dir . '/' . sprintf('%s/%s/%s/%s', $k[0], $k[1], $k[2], $key);
    }
}
