<?php

use Glpi\Application\View\TemplateRenderer;

/**
 * Hook: injeta CSS e comportamentos na tela de login do GLPI.
 *
 * Segurança:
 *  - $theme: validado por regex antes de qualquer uso (evita path traversal).
 *  - URL do CSS construída com http_build_query() — sem concatenação manual de $_GET.
 *  - max-width/max-height: validados como dimensões CSS antes de passar ao template.
 *  - HTML/JS gerado pelo template Twig com auto-escaping ativo.
 */
function plugin_trademark_display_login(): void
{
   // 1. Resolve o tema — aceita apenas nomes alfanuméricos seguros.
   $theme = null;
   if (
      isset($_GET['theme'])
      && is_string($_GET['theme'])
      && preg_match('/^[A-Za-z0-9_-]{1,64}$/', $_GET['theme'])
   ) {
      $theme = $_GET['theme'];
   }

   $themeInfo = $theme ? PluginTrademarkTheme::getThemeInfo($theme) : null;
   if (!$themeInfo) {
      $savedTheme = PluginTrademarkConfig::getConfig('login_theme', '');
      $themeInfo  = PluginTrademarkTheme::getThemeInfo($savedTheme);
   }

   // 2. Imagem do logo de login.
   $loginPicture = PluginTrademarkConfig::getConfig('login_picture');
   if (!$loginPicture && $themeInfo && isset($themeInfo['login-logo'])) {
      // 'id' vem de getThemeInfo() que usa o nome do diretório validado no passo 1.
      $loginPicture = $themeInfo['login-logo'] . '&theme=' . $themeInfo['id'];
   }

   // 3. O CSS normal é carregado pelo hook de páginas anônimas.
   //    Mantém uma folha adicional apenas para pré-visualização de tema ou nocache.
   if ($theme !== null || isset($_GET['nocache'])) {
      $cssParams = ['_' => PluginTrademarkToolbox::getTimestamp()];
      if ($theme !== null) {
         $cssParams['theme'] = $theme;
      }
      if (isset($_GET['nocache'])) {
         $cssParams['nocache'] = 1;
      }
      $cssUrl = '/plugins/trademark/front/login.css.php?' . http_build_query($cssParams);
      echo Html::css($cssUrl, ['version' => PLUGIN_TRADEMARK_VERSION]);
   }

   // 4. Dados do logo (sobrescrita de marca).
   $pictureUrl = null;
   $maxWidth   = '145px';
   $maxHeight  = '80px';
   if ($loginPicture) {
      $pictureUrl = PluginTrademarkToolbox::getPictureUrl($loginPicture);

      // Valida que os valores de tamanho são dimensões CSS seguras antes de passar ao template.
      $rawWidth  = PluginTrademarkConfig::getConfig('login_picture_max_width', '145px');
      $rawHeight = PluginTrademarkConfig::getConfig('login_picture_max_height', '80px');
      $cssDim    = '/^\d+(\.\d+)?(px|em|rem|%|vh|vw)$/';
      $maxWidth  = preg_match($cssDim, $rawWidth)  ? $rawWidth  : '145px';
      $maxHeight = preg_match($cssDim, $rawHeight) ? $rawHeight : '80px';
   }

   // 5. Favicon.
   $faviconPicture = PluginTrademarkConfig::getConfig('favicon_picture');
   $faviconUrl     = $faviconPicture
      ? PluginTrademarkToolbox::getPictureUrl($faviconPicture)
      : null;

   // 6. Título da página e rodapé.
   $pageTitle     = PluginTrademarkConfig::getConfig('page_title', '');
   $footerDisplay = PluginTrademarkConfig::getConfig('page_footer_display', 'original');
   $footerText    = '';
   if ($footerDisplay === 'custom') {
      $footerText = \Glpi\RichText\RichText::getEnhancedHtml(
         PluginTrademarkConfig::getConfig('page_footer_text', '')
      );
   }

   // 7. Renderiza HTML/JS via Twig (C6).
   //    O auto-escaping do Twig protege contra XSS; valores em JS usam json_encode no template.
   TemplateRenderer::getInstance()->display('@trademark/login_hook.html.twig', [
      'picture_url'    => $pictureUrl,
      'max_width'      => $maxWidth,
      'max_height'     => $maxHeight,
      'login_label'    => __('Login'),
      'password_label' => __('Password'),
      'favicon_url'    => $faviconUrl,
      'page_title'     => $pageTitle,
      'footer_display' => $footerDisplay,
      'footer_text'    => $footerText,
   ]);
}

function plugin_trademark_install(): bool
{
   return true;
}

function plugin_trademark_uninstall(): bool
{
   return true;
}
