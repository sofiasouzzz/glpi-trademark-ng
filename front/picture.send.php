<?php

include('../../../inc/includes.php');

$path = $_GET['path'] ?? '';
if (!is_string($path) || $path === '') {
   http_response_code(400);
   die(__('Invalid filename'));
}

if (isset($_GET['theme'])) {
   $theme = $_GET['theme'];
   if (!is_string($theme) || !preg_match('/^[\w\-]+$/', $theme)) {
      http_response_code(400);
      die(__('Invalid filename'));
   }
   $baseDir = PluginTrademarkTheme::getThemePublicFolder() . '/' . $theme;
} else {
   $baseDir = GLPI_PLUGIN_DOC_DIR . '/trademark';
}

$base     = realpath($baseDir);
$fullPath = $base !== false ? realpath($base . '/' . $path) : false;

// Must exist and stay inside the allowed directory
if ($fullPath === false || !is_file($fullPath) || !str_starts_with($fullPath, $base . DIRECTORY_SEPARATOR)) {
   http_response_code(404);
   die(__('File not found'));
}

$mimes = [
   'bmp'  => 'image/bmp',
   'png'  => 'image/png',
   'jpg'  => 'image/jpeg',
   'jpeg' => 'image/jpeg',
   'gif'  => 'image/gif',
   'webp' => 'image/webp',
   'ico'  => 'image/x-icon',
   'svg'  => 'image/svg+xml',
];
$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
if (!isset($mimes[$ext])) {
   http_response_code(403);
   die(__('Invalid filename'));
}

$name = preg_replace('/[^\w\-.]+/', '', basename($fullPath));

header('Content-Type: ' . $mimes[$ext]);
header('Content-Length: ' . filesize($fullPath));
header('Content-Disposition: inline; filename="' . $name . '"');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'");
header('Cache-Control: public, max-age=' . WEEK_TIMESTAMP);

readfile($fullPath);
exit;