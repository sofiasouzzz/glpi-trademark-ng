<?php

/**
 * Trademark plugin — Internal pages JavaScript
 *
 * Sets favicon, page title and custom footer behavior.
 */

$_GET['donotcheckversion']   = true;
$dont_check_maintenance_mode = true;

include('../../../inc/includes.php');

PluginTrademarkToolbox::setAssetHeaders('application/javascript; charset=UTF-8');
?>
$(function () {
<?php
$jsonFlags = JSON_INVALID_UTF8_SUBSTITUTE
   | JSON_HEX_TAG
   | JSON_HEX_APOS
   | JSON_HEX_QUOT
   | JSON_HEX_AMP;

$favicon = PluginTrademarkConfig::getConfig('favicon_picture');
if ($favicon) :
   $faviconUrl = PluginTrademarkToolbox::getPictureUrl($favicon);
   ?>
   var $icon = $('link[rel*=icon]');
   $icon.attr('type', null);
   $icon.attr('href', <?php echo json_encode($faviconUrl, $jsonFlags); ?>);
<?php
endif;

$pageTitle = PluginTrademarkConfig::getConfig('page_title');
if ($pageTitle) :
   ?>
   var $title = $('title');
   var newTitle = $title.text().replace('GLPI', <?php echo json_encode($pageTitle, $jsonFlags); ?>);
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
   $('div[id^=about_modal_] .copyright').parent().replaceWith(<?php echo json_encode($footerText, $jsonFlags); ?>);
<?php
endif;
?>
});
