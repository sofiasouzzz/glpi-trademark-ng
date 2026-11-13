<?php

/**
 * Trademark plugin — Internal pages JavaScript
 *
 * Sets favicon, page title and custom footer behavior.
 */

$_GET['donotcheckversion']   = true;
$dont_check_maintenance_mode = true;

include('../../../inc/includes.php');

header('Content-Type: application/javascript; charset=UTF-8');
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
?>
$(function () {
<?php
$favicon = PluginTrademarkConfig::getConfig('favicon_picture');
if ($favicon) :
   $faviconUrl = PluginTrademarkToolbox::getPictureUrl($favicon);
   ?>
   var $icon = $('link[rel*=icon]');
   $icon.attr('type', null);
   $icon.attr('href', <?php echo json_encode($faviconUrl); ?>);
<?php
endif;

$pageTitle = PluginTrademarkConfig::getConfig('page_title');
if ($pageTitle) :
   ?>
   var $title = $('title');
   var newTitle = $title.text().replace('GLPI', <?php echo json_encode($pageTitle); ?>);
   $title.text(newTitle);
<?php
endif;

$footerDisplay = PluginTrademarkConfig::getConfig('page_footer_display', 'original');
$footerText    = PluginTrademarkConfig::getConfig('page_footer_text', '');
if ($footerDisplay === 'hide') :
   ?>
   $('a[id^=show_about_modal_]').hide();
<?php
endif;

if ($footerDisplay === 'custom') :
   $footerText = \Glpi\RichText\RichText::getEnhancedHtml($footerText);
   ?>
   $('div[id^=about_modal_] .copyright').parent().parent().html(<?php echo json_encode($footerText); ?>);
<?php
endif;
?>
});
