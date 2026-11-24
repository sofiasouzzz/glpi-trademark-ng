<?php

use Glpi\Http\Firewall;

define('PLUGIN_TRADEMARK_VERSION', '0.0.1');

// Minimal GLPI version, inclusive
define("PLUGIN_TRADEMARK_MIN_GLPI_VERSION", "12.0.0");

// Maximum GLPI version, exclusive
define("PLUGIN_TRADEMARK_MAX_GLPI_VERSION", "12.9.9");

// Minimal PHP version, inclusive
define("PLUGIN_TRADEMARK_MIN_PHP_VERSION", "8.3.0");


$folder = basename(dirname(__FILE__));

if ($folder !== "trademark") {
   $msg = sprintf("Please, rename the plugin folder \"%s\" to \"trademark\"", $folder);
   Session::addMessageAfterRedirect($msg, true, ERROR);
}

// Init the hooks of the plugins -Needed
function plugin_init_trademark() {
   global $PLUGIN_HOOKS, $CFG_GLPI;
   $PLUGIN_HOOKS['csrf_compliant']['trademark'] = true;

   $PLUGIN_HOOKS['config_page']['trademark'] = '../../front/config.form.php?itemtype=Config&glpi_tab=PluginTrademarkConfig$1';

   $plugin = new Plugin();

   if ($plugin->isInstalled('trademark') && $plugin->isActivated('trademark')) {

      $autoload = __DIR__ . '/vendor/autoload.php';
      if (file_exists($autoload)) {
         include_once $autoload;
      };

      Plugin::registerClass('PluginTrademarkConfig', [
         'addtabon' => ['Config']
      ]);

      $PLUGIN_HOOKS['display_login']['trademark'] = "plugin_trademark_display_login";

      $assetVersion = PluginTrademarkToolbox::getTimestamp();
      $PLUGIN_HOOKS["add_css"]['trademark'] = 'front/internal.css.php?_=' . $assetVersion;
      $PLUGIN_HOOKS['add_css_anonymous_page']['trademark'] = 'front/login.css.php?_=' . $assetVersion;
      $PLUGIN_HOOKS["add_javascript"]['trademark'] = 'front/internal.js.php?_=' . $assetVersion;

      $CFG_GLPI['javascript']['config']['config'][] = 'codemirror';
      $CFG_GLPI['javascript']['config']['config'][] = 'tinymce';

      // Make the callback page public again.
      Firewall::addPluginStrategyForLegacyScripts('trademark', '#^/front/login.css.php$#', Firewall::STRATEGY_NO_CHECK);
      Firewall::addPluginStrategyForLegacyScripts('trademark', '#^/front/picture.send.php$#', Firewall::STRATEGY_NO_CHECK);
         
   }
}

function plugin_version_trademark() {
    return [
        'name'           => 'Trademark',
        'version'        => PLUGIN_TRADEMARK_VERSION,
        'author'         => 'edgardmessias, ssouz(Fork GLPI 12)',
        'license'        => 'GPLv2+',
        'homepage'       => 'https://github.com/sofiasouzzz/trademark-glpi',
        'requirements'   => [
            'glpi' => [
                'min' => PLUGIN_TRADEMARK_MIN_GLPI_VERSION,
            ],
            'php' => [
                'min' => PLUGIN_TRADEMARK_MIN_PHP_VERSION,
            ],
        ]
    ];
}

// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_trademark_check_prerequisites() {
   if (version_compare(GLPI_VERSION, PLUGIN_TRADEMARK_MIN_GLPI_VERSION, '<')) {
      echo "This plugin requires GLPI >= " . PLUGIN_TRADEMARK_MIN_GLPI_VERSION;
      return false;
   }
   if (version_compare(GLPI_VERSION, PLUGIN_TRADEMARK_MAX_GLPI_VERSION, '>=')) {
      echo "This plugin is not yet validated for this GLPI version";
      return false;
   }
   if (version_compare(PHP_VERSION, PLUGIN_TRADEMARK_MIN_PHP_VERSION, '<')) {
      echo "This plugin requires PHP >= " . PLUGIN_TRADEMARK_MIN_PHP_VERSION;
      return false;
   }
   return true;
}

function plugin_trademark_check_config() {
   return true;
}

function t_trademark($str) {
   return __($str, 'trademark');
}
