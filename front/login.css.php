<?php

/**
 * Trademark plugin — Login page CSS
 *
 * Public endpoint: no authentication required.
 * Access control is handled by Firewall::addPluginStrategyForLegacyScripts()
 * registered in setup.php — the $SECURITY_STRATEGY global is obsolete in GLPI 12.
 */

$_GET['donotcheckversion']   = true;
$dont_check_maintenance_mode = true;

// C1 fix: use a path relative to this file, not an absolute server path.
include('../../../inc/includes.php');

// Aceita só nomes simples de tema (evita path traversal e injeção).
$theme = null;
if (isset($_GET['theme']) && is_string($_GET['theme'])) {
   if (preg_match('/^[A-Za-z0-9_-]{1,64}$/', $_GET['theme'])) {
      $theme = $_GET['theme'];
   }
}

// Redireciona para a URL com timestamp, se ainda não tiver.
if (!isset($_GET['_'])) {
   header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
   header('Pragma: no-cache');

   $query = ['_' => PluginTrademarkToolbox::getTimestamp()];
   if (isset($_GET['v']) && is_string($_GET['v']) && preg_match('/^[A-Za-z0-9._-]{1,32}$/', $_GET['v'])) {
      $query['v'] = $_GET['v'];
   }
   if ($theme !== null) {
      $query['theme'] = $theme;
   }

   Html::redirect(basename(__FILE__) . '?' . http_build_query($query), 302);
   die;
}

try {
   $css = PluginTrademarkScss::getLoginCSS($theme);
} catch (\Throwable $e) {
   \Toolbox::logInFile('trademark', 'login.css: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() . "\n");
   $css = '';
}

$activeTheme = $theme ?: PluginTrademarkConfig::getConfig('login_theme', '');
$themeInfo   = $activeTheme ? PluginTrademarkTheme::getThemeInfo($activeTheme) : null;
$loginPicture = PluginTrademarkConfig::getConfig('login_picture', '');
if (!$loginPicture && $themeInfo && isset($themeInfo['login-logo'])) {
   $loginPicture = $themeInfo['login-logo'] . '&theme=' . $themeInfo['id'];
}

if ($loginPicture) {
   $pictureUrl = PluginTrademarkToolbox::getPictureUrl($loginPicture);
   $rawWidth   = PluginTrademarkConfig::getConfig('login_picture_max_width', '145px');
   $rawHeight  = PluginTrademarkConfig::getConfig('login_picture_max_height', '80px');
   $cssDim     = '/^\d+(\.\d+)?(px|em|rem|%|vh|vw)$/';
   $maxWidth   = preg_match($cssDim, $rawWidth) ? $rawWidth : '145px';
   $maxHeight  = preg_match($cssDim, $rawHeight) ? $rawHeight : '80px';
   $css       .= '.page-anonymous .glpi-logo{'
      . '--logo:url("' . $pictureUrl . '");'
      . 'content:url("' . $pictureUrl . '");'
      . 'width:auto;height:auto;'
      . 'max-width:' . $maxWidth . ';max-height:' . $maxHeight . ';'
      . '}';
}

header('Content-Type: text/css; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

$is_cacheable = !isset($_GET['debug']) && !isset($_GET['nocache']);
if ($is_cacheable) {
   $max_age = WEEK_TIMESTAMP;
   header_remove('Pragma');
   header('Cache-Control: public, max-age=' . $max_age);
   header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + $max_age));
} else {
   header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
}

echo $css;
