<?php
/**
 * Path helpers to build consistent asset and public URLs
 * Works regardless of entry folder (developer, hr-admin, etc.)
 */

if (!function_exists('root_prefix')) {
    function root_prefix(): string
    {
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        // Strip role subfolders so assets resolve from project root
        $rootDir = preg_replace('#/(developer|hr-admin|super-admin|employee|accounting|operation)$#', '', $scriptDir);
        return $rootDir === '' ? '' : $rootDir;
    }
}

if (!function_exists('base_url')) {
    function base_url(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        return $scheme . '://' . $host . root_prefix();
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path = ''): string
    {
        return base_url() . '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('public_url')) {
    function public_url(string $path = ''): string
    {
        return base_url() . '/public/' . ltrim($path, '/');
    }
}

