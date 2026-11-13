<?php

/**
 * Trademark plugin — Internal pages CSS
 *
 * Provides custom CSS and overrides the GLPI header logo with GLPI 12 CSS variables.
 */

$_GET['donotcheckversion']   = true;
$dont_check_maintenance_mode = true;

include('../../../inc/includes.php');

$name = 'internal';
$css  = '';

$picture = PluginTrademarkConfig::getConfig("{$name}_picture", '');
if ($picture) {
   $picUrl = PluginTrademarkToolbox::getPictureUrl($picture);
   $width  = PluginTrademarkConfig::getConfig("{$name}_picture_width", '100px');
   $height = PluginTrademarkConfig::getConfig("{$name}_picture_height", '55px');

   // GLPI 12 uses CSS variables (--glpi-logo, --glpi-logo-light, --glpi-logo-dark).
   // Overriding them on :root and .glpi-logo ensures immediate rendering without flicker.
   $css .= ":root {\n";
   $css .= "  --glpi-logo: url(\"" . $picUrl . "\") !important;\n";
   $css .= "  --glpi-logo-light: url(\"" . $picUrl . "\") !important;\n";
   $css .= "  --glpi-logo-dark: url(\"" . $picUrl . "\") !important;\n";
   $css .= "}\n";
   $css .= ".page .glpi-logo {\n";
   $css .= "  --glpi-logo: url(\"" . $picUrl . "\") !important;\n";
   $css .= "  width: " . $width . " !important;\n";
   $css .= "  height: " . $height . " !important;\n";
   $css .= "  background-size: contain !important;\n";
   $css .= "  background-repeat: no-repeat !important;\n";
   $css .= "  background-position: center !important;\n";
   $css .= "  background-image: url(\"" . $picUrl . "\") !important;\n";
   $css .= "}\n";
}

$css_type   = PluginTrademarkConfig::getConfig("{$name}_css_type", 'scss');
$css_custom = PluginTrademarkConfig::getConfig("{$name}_css_custom", '');

$css_custom = html_entity_decode($css_custom);

if ($css_type === 'scss' && $css_custom && PluginTrademarkScss::hasScssSuport()) {
   try {
      $variables = [];
      $variables['trademark_timestamp'] = PluginTrademarkToolbox::getTimestamp();

      $css .= PluginTrademarkScss::compileScss($css_custom, $variables);
   } catch (\Throwable $th) {
      \Glpi\Application\ErrorHandler::getInstance()->handleException($th);
   }
} elseif ($css_type === 'css') {
   $css .= $css_custom;
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
