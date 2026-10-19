<?php

function mds_trim_slashes(string $path): string
{
    return trim($path, '/');
}

function mds_mount_path(): string
{
    $config = new configuration();
    $baseUrlPath = parse_url($config::$baseurl ?? '', PHP_URL_PATH);

    if (!is_string($baseUrlPath) || $baseUrlPath === '') {
        return '';
    }

    return rtrim($baseUrlPath, '/');
}

function mds_public_path(string $path = ''): string
{
    $mountPath = rtrim(mds_mount_path(), '/');
    $trimmedPath = mds_trim_slashes($path);

    return $trimmedPath === '' ? ($mountPath === '' ? '/' : $mountPath) : ($mountPath === '' ? '/' . $trimmedPath : $mountPath . '/' . $trimmedPath);
}

function mds_asset_path(string $path = ''): string
{
    $assetPath = 'assets/' . mds_trim_slashes($path);
    $url = mds_public_path($assetPath);
    $filePath = dirname(__DIR__, 2) . '/public/' . $assetPath;

    if (is_file($filePath)) {
        return $url . '?v=' . filemtime($filePath);
    }

    return $url;
}

function mds_route_path(string $path = ''): string
{
    return mds_public_path($path);
}

function mds_absolute_url(string $path = ''): string
{
    $config = new configuration();

    return rtrim($config::$baseurl, '/') . '/' . mds_trim_slashes($path);
}
