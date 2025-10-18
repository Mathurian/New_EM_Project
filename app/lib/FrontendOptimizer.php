<?php
declare(strict_types=1);

namespace App;

/**
 * Frontend optimization service for CSS/JS minification and compression
 */
class FrontendOptimizer
{
    private static string $assetsDir;
    private static string $cacheDir;
    private static bool $minifyEnabled = true;

    public static function init(): void
    {
        self::$assetsDir = __DIR__ . '/../../public/assets/';
        self::$cacheDir = __DIR__ . '/../../storage/cache/assets/';
        
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
    }

    /**
     * Minify CSS content
     */
    public static function minifyCss(string $css): string
    {
        if (!self::$minifyEnabled) {
            return $css;
        }

        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove unnecessary whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\s*{\s*/', '{', $css);
        $css = preg_replace('/;\s*/', ';', $css);
        $css = preg_replace('/\s*}\s*/', '}', $css);
        $css = preg_replace('/\s*,\s*/', ',', $css);
        $css = preg_replace('/\s*:\s*/', ':', $css);
        $css = preg_replace('/\s*;\s*/', ';', $css);
        
        // Remove trailing semicolons
        $css = preg_replace('/;}/', '}', $css);
        
        return trim($css);
    }

    /**
     * Minify JavaScript content
     */
    public static function minifyJs(string $js): string
    {
        if (!self::$minifyEnabled) {
            return $js;
        }

        // Remove single-line comments
        $js = preg_replace('~//[^\r\n]*~', '', $js);
        
        // Remove multi-line comments
        $js = preg_replace('~/\*.*?\*/~s', '', $js);
        
        // Remove unnecessary whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        $js = preg_replace('/\s*{\s*/', '{', $js);
        $js = preg_replace('/\s*}\s*/', '}', $js);
        $js = preg_replace('/\s*;\s*/', ';', $js);
        $js = preg_replace('/\s*,\s*/', ',', $js);
        $js = preg_replace('/\s*:\s*/', ':', $js);
        
        return trim($js);
    }

    /**
     * Get optimized CSS file with caching
     */
    public static function getOptimizedCss(string $file): string
    {
        $sourceFile = self::$assetsDir . 'css/' . $file;
        $cacheFile = self::$cacheDir . 'css_' . md5($file . filemtime($sourceFile)) . '.css';
        
        if (!file_exists($sourceFile)) {
            return '';
        }
        
        if (!file_exists($cacheFile) || filemtime($sourceFile) > filemtime($cacheFile)) {
            $css = file_get_contents($sourceFile);
            $minified = self::minifyCss($css);
            file_put_contents($cacheFile, $minified);
        }
        
        return $cacheFile;
    }

    /**
     * Get optimized JS file with caching
     */
    public static function getOptimizedJs(string $file): string
    {
        $sourceFile = self::$assetsDir . 'js/' . $file;
        $cacheFile = self::$cacheDir . 'js_' . md5($file . filemtime($sourceFile)) . '.js';
        
        if (!file_exists($sourceFile)) {
            return '';
        }
        
        if (!file_exists($cacheFile) || filemtime($sourceFile) > filemtime($cacheFile)) {
            $js = file_get_contents($sourceFile);
            $minified = self::minifyJs($js);
            file_put_contents($cacheFile, $minified);
        }
        
        return $cacheFile;
    }

    /**
     * Generate asset URL with versioning
     */
    public static function assetUrl(string $path): string
    {
        $fullPath = self::$assetsDir . $path;
        
        if (file_exists($fullPath)) {
            $version = filemtime($fullPath);
            return url("assets/{$path}?v={$version}");
        }
        
        return url("assets/{$path}");
    }

    /**
     * Generate optimized CSS link tag
     */
    public static function cssLink(string $file, array $attributes = []): string
    {
        $optimizedFile = self::getOptimizedCss($file);
        
        if (!$optimizedFile) {
            return '';
        }
        
        $url = str_replace(self::$cacheDir, '/storage/cache/assets/', $optimizedFile);
        $url = url($url);
        
        $attrs = array_merge([
            'rel' => 'stylesheet',
            'href' => $url,
            'type' => 'text/css'
        ], $attributes);
        
        $attrString = '';
        foreach ($attrs as $key => $value) {
            $attrString .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
        
        return "<link{$attrString}>";
    }

    /**
     * Generate optimized JS script tag
     */
    public static function jsScript(string $file, array $attributes = []): string
    {
        $optimizedFile = self::getOptimizedJs($file);
        
        if (!$optimizedFile) {
            return '';
        }
        
        $url = str_replace(self::$cacheDir, '/storage/cache/assets/', $optimizedFile);
        $url = url($url);
        
        $attrs = array_merge([
            'src' => $url,
            'type' => 'text/javascript'
        ], $attributes);
        
        $attrString = '';
        foreach ($attrs as $key => $value) {
            $attrString .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
        
        return "<script{$attrString}></script>";
    }

    /**
     * Generate preload links for critical resources
     */
    public static function generatePreloads(): string
    {
        $preloads = [];
        
        // Preload critical CSS
        $criticalCss = self::getOptimizedCss('style.css');
        if ($criticalCss) {
            $url = str_replace(self::$cacheDir, '/storage/cache/assets/', $criticalCss);
            $preloads[] = '<link rel="preload" href="' . url($url) . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';
        }
        
        return implode("\n    ", $preloads);
    }

    /**
     * Generate service worker for offline functionality
     */
    public static function generateServiceWorker(): string
    {
        $cacheFiles = [
            'css/style.css',
            'js/app.js'
        ];
        
        $swContent = "const CACHE_NAME = 'contest-app-v1';\n";
        $swContent .= "const urlsToCache = [\n";
        
        foreach ($cacheFiles as $file) {
            $swContent .= "  '" . url("assets/{$file}") . "',\n";
        }
        
        $swContent .= "];\n\n";
        $swContent .= "self.addEventListener('install', function(event) {\n";
        $swContent .= "  event.waitUntil(\n";
        $swContent .= "    caches.open(CACHE_NAME)\n";
        $swContent .= "      .then(function(cache) {\n";
        $swContent .= "        return cache.addAll(urlsToCache);\n";
        $swContent .= "      })\n";
        $swContent .= "  );\n";
        $swContent .= "});\n";
        
        return $swContent;
    }

    /**
     * Clear asset cache
     */
    public static function clearCache(): bool
    {
        $files = glob(self::$cacheDir . '*');
        $success = true;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $success = unlink($file) && $success;
            }
        }
        
        return $success;
    }

    /**
     * Get cache statistics
     */
    public static function getCacheStats(): array
    {
        $files = glob(self::$cacheDir . '*');
        $totalSize = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
        }
        
        return [
            'total_files' => count($files),
            'total_size' => $totalSize,
            'cache_dir' => self::$cacheDir
        ];
    }
}