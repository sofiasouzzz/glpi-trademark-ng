<?php

// Página pública (tela de login): não exige usuário autenticado
$SECURITY_STRATEGY = 'no_check';

$_GET['donotcheckversion']   = true;
$dont_check_maintenance_mode = true;

include('var/www/html/glpi/inc/includes.php');

// Aceita só nomes simples de tema (evita path traversal e injeção)
$theme = null;
if (isset($_GET['theme']) && is_string($_GET['theme'])) {
   if (preg_match('/^[A-Za-z0-9_-]{1,64}$/', $_GET['theme'])) {
      $theme = $_GET['theme'];
   }
}

// Redireciona para a URL com timestamp, se ainda não tiver
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

   // URL relativa, sem usar nada vindo do usuário no caminho
   Html::redirect(basename(__FILE__) . '?' . http_build_query($query), 302);
   die;
}

try {
   $css = PluginTrademarkScss::getLoginCSS($theme);
} catch (\Throwable $e) {
   \Toolbox::logInFile('trademark', 'login.css: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() . "\n");
   $css = '';
}

header('Content-Type: text/css; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

$is_cacheable = !isset($_GET['debug']) && !isset($_GET['nocache']);
if ($is_cacheable) {
   // Torna o CSS cacheável por navegadores e proxies
   $max_age = WEEK_TIMESTAMP;
   header_remove('Pragma');
   header('Cache-Control: public, max-age=' . $max_age);
   header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + $max_age));
} else {
   header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
}

echo $css;