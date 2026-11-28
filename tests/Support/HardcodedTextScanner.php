<?php

namespace Tests\Support;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Pemeriksa teks literal di view Blade/Livewire (T0-11).
 *
 * Mengabaikan komentar Blade, @php, <script>, <style>, <svg>, ekspresi {{ }} / {!! !!}, direktif,
 * dan isi tag HTML. Sisanya yang berisi >= 2 kata dihitung "teks hardcode". Teks yang sudah
 * lewat __(), trans() atau $t() berada di dalam {{ }} sehingga tidak terhitung.
 */
class HardcodedTextScanner
{
    /** @return array<string, int> path relatif => jumlah baris teks literal */
    public static function scan(string $viewsPath): array
    {
        $result = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsPath, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($viewsPath) + 1));
            $count = self::count((string) file_get_contents($file->getPathname()));

            if ($count > 0) {
                $result[$relative] = $count;
            }
        }

        ksort($result);

        return $result;
    }

    public static function count(string $source): int
    {
        $patterns = [
            '/\{\{--.*?--\}\}/s',
            '/@php.*?@endphp/s',
            '/@verbatim.*?@endverbatim/s',
            '/<script\b.*?<\/script>/s',
            '/<style\b.*?<\/style>/s',
            '/<svg\b.*?<\/svg>/s',
            '/\{!!.*?!!\}/s',
            '/\{\{.*?\}\}/s',
            '/^[ \t]*@\w+.*$/m',
            '/<[^>]*>/s',
        ];

        $text = $source;
        foreach ($patterns as $pattern) {
            $replacement = $pattern === '/<[^>]*>/s' ? "\n" : '';
            $text = preg_replace($pattern, $replacement, $text) ?? $text;
        }

        $count = 0;
        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            if (preg_match('/\p{L}{2,}\s+\p{L}{2,}/u', trim($line)) === 1) {
                $count++;
            }
        }

        return $count;
    }
}
