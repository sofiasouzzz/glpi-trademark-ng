<?php

use Glpi\Application\View\TemplateRenderer;

class PluginTrademarkConfig extends CommonDBTM
{
   private static ?array $_cache = null;
   private static int $_i = 1;

   /**
    * Lê um valor de configuração do plugin (com cache em memória por request).
    */
   public static function getConfig(string $name, mixed $defaultValue = null): mixed
   {
      if (self::$_cache === null) {
         $config = new self();
         $config->getEmpty();
         $config->fields = array_merge($config->fields, Config::getConfigurationValues('trademark'));

         self::$_cache = $config->fields;
      }

      if (isset(self::$_cache[$name]) && self::$_cache[$name] !== '') {
         return self::$_cache[$name];
      }
      return $defaultValue;
   }

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {
      switch (get_class($item)) {
         case 'Config':
            return [1 => t_trademark('Trademark')];
         default:
            return '';
      }
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
   {
      switch (get_class($item)) {
         case 'Config':
            $config = new self();
            $config->showFormDisplay();
            break;
      }
      return true;
   }

   protected static function checkPicture(
      string $name,
      array $input,
      array $old,
      int $width = 0,
      int $height = 0,
      int $max_size = 500
   ): array {
      $blank = "_blank_$name";
      $new   = "_$name";

      if (isset($input[$blank]) && $input[$blank]) {
         unset($input[$blank]);
         if (isset($old[$name]) && $old[$name]) {
            PluginTrademarkToolbox::deletePicture($old[$name]);
         }
         $input[$name] = '';
      } elseif (isset($input[$new]) && !empty($input[$new]) && !empty($input[$new][0])) {
         $picName        = array_shift($input[$new]);
         $picPath        = GLPI_TMP_DIR . '/' . $picName;
         $picResizedPath = GLPI_TMP_DIR . '/resized_' . $picName;

         if ($width || $height) {
            if (PluginTrademarkToolbox::resizePicture($picPath, $picResizedPath, $width, $height, 0, 0, 0, 0, $max_size)) {
               $picPath = $picResizedPath;
            }
         }

         if ($dest = PluginTrademarkToolbox::savePicture($picPath)) {
            $input[$name] = $dest;
         } else {
            Session::addMessageAfterRedirect(__('Unable to save picture file.'), true, ERROR);
         }

         if (isset($old[$name]) && $old[$name]) {
            PluginTrademarkToolbox::deletePicture($old[$name]);
         }
      }

      unset(
         $input["_$name"],
         $input["_prefix_$name"],
         $input["_prefix_new_$name"],
         $input["_tag_$name"],
         $input["_tag_new_$name"],
         $input["_uploader_$name"],
         $input["new_$name"],
         $input[$blank],
         $input[$new]
      );

      return $input;
   }

   protected static function checkCSS(string $name, string $label, array $input, array $old): array
   {
      $fullName = "{$name}_css_custom";
      $type     = "{$name}_css_type";

      if (!isset($input[$type])) {
         $input[$type] = 'css';
      }

      if (isset($input[$fullName])) {
         $input[$fullName] = html_entity_decode($input[$fullName]);
         $input[$fullName] = preg_replace('/\\\\r\\\\n/', "\n", $input[$fullName]);
         $input[$fullName] = preg_replace('/\\\\n/', "\n", $input[$fullName]);

         if ($input[$type] === 'scss' && PluginTrademarkScss::hasScssSuport()) {
            try {
               $variables                         = [];
               $variables['trademark_timestamp'] = PluginTrademarkToolbox::getTimestamp();
               PluginTrademarkScss::compileScss($input[$fullName], $variables);
            } catch (\Throwable $th) {
               $message = sprintf(t_trademark('Unable to compile the SCSS (%1$s). Message: '), $label);
               Session::addMessageAfterRedirect($message . $th->getMessage(), true, ERROR);
            }
         }
      }

      return $input;
   }

   public static function configUpdate(array $input): array
   {
      $old = Config::getConfigurationValues('trademark');

      unset($input['_no_history']);

      $input = self::checkPicture('favicon_picture', $input, $old, 192, 192, 192);
      $input = self::checkPicture('login_picture', $input, $old, 145, 80, 300);
      $input = self::checkPicture('internal_picture', $input, $old, 100, 55, 300);
      $input = self::checkPicture('login_background_picture', $input, $old);

      $input = self::checkCSS('login', t_trademark('Login Page'), $input, $old);
      $input = self::checkCSS('internal', t_trademark('Internal Page'), $input, $old);

      foreach ($input as $key => $value) {
         if ($value && strpos($key, '_blank_') === 0) {
            $name        = substr($key, 7);
            $input[$name] = '';
         }
      }

      $input['timestamp'] = time();

      PluginTrademarkToolbox::setTimestamp($input['timestamp']);

      Session::addMessageAfterRedirect(__('Item successfully updated'), false, INFO);

      return $input;
   }

   public function getEmpty(): bool
   {
      $defaultCss = 'css';

      if (PluginTrademarkScss::hasScssSuport()) {
         $defaultCss = 'scss';
      }

      $this->fields = [
         'favicon_picture'           => '',
         'page_title'                => '',
         'page_footer_display'       => 'original',
         'page_footer_text'          => '',
         'login_picture'             => '',
         'login_picture_max_width'   => '240px',
         'login_picture_max_height'  => '130px',
         'login_css_custom'          => '',
         'login_css_type'            => $defaultCss,
         'login_theme'               => '',
         'internal_picture'          => '',
         'internal_picture_width'    => '100px',
         'internal_picture_height'   => '55px',
         'internal_css_custom'       => '',
         'internal_css_type'         => $defaultCss,
      ];

      return true;
   }

   /**
    * Renderiza um bloco corporativo moderno de upload e pré-visualização de imagem.
    */
   protected function getPictureField(string $name, string $label, ?string $recommendedSize = null): array
   {
      $hasImage = !empty($this->fields[$name]);

      return [
         'name'              => $name,
         'label'             => $label,
         'recommended_size'  => $recommendedSize,
         'recommended_label' => $recommendedSize
            ? sprintf(t_trademark('Recommended size: %1$s'), $recommendedSize)
            : null,
         'image_url'         => $hasImage ? PluginTrademarkToolbox::getPictureUrl($this->fields[$name]) : null,
         'upload_html'       => Html::file([
            'name'          => $name,
            'onlyimages'    => true,
            'min_file_size' => 1,
            'display'       => false,
         ]),
      ];
   }

   /**
    * Prepares the CSS/SCSS editor data and GLPI dropdown markup.
    */
   protected function getCssEditor(string $name, string $label): array
   {
      $fullName = "{$name}_css_custom";
      $type     = "{$name}_css_type";
      $rand     = mt_rand();

      $css_type = [
         'off' => __('Disabled'),
         'css' => 'CSS',
      ];
      if (PluginTrademarkScss::hasScssSuport()) {
         $css_type['scss'] = 'SCSS';
      }
      return [
         'label'        => $label,
         'field_name'   => $fullName,
         'type_name'    => $type,
         'textarea_id'  => $fullName . '_' . $rand,
         'value'        => rtrim($this->fields[$fullName]),
         'select_html'  => Dropdown::showFromArray($type, $css_type, [
            'value'   => $this->fields[$type],
            'display' => false,
         ]),
         'refresh_wait' => 300 + self::$_i++ * 80,
      ];
   }

   /**
    * Exibe o formulário corporativo moderno de configuração na aba Configuração > Trademark.
    */
   public function showFormDisplay(): bool
   {
      global $CFG_GLPI;

      if (!Config::canView()) {
         return false;
      }

      $this->getEmpty();
      $this->fields = array_merge($this->fields, Config::getConfigurationValues('trademark'));

      $canedit = Session::haveRight(Config::$rightname, UPDATE);
      $rand    = mt_rand();
      $pictureFields = [
         'favicon' => $this->getPictureField(
            'favicon_picture',
            __('Favicon') . ' (' . __('Picture') . ')',
            '192px x 192px'
         ),
         'login' => $this->getPictureField(
            'login_picture',
            __('Login Logo') . ' (' . __('Picture') . ')',
            '145px x 80px'
         ),
         'background' => $this->getPictureField(
            'login_background_picture',
            t_trademark('Background picture'),
            '1920px x 1080px'
         ),
         'internal' => $this->getPictureField(
            'internal_picture',
            __('Header Logo') . ' (' . __('Internal Page') . ')',
            '100px x 55px'
         ),
      ];

      $themeOptions = [];
      foreach (PluginTrademarkTheme::getLoginThemes() as $id => $theme) {
         $themeOptions[] = [
            'id'       => $id,
            'name'     => $theme['name'],
            'preview'  => $theme['login-preview'] ?? null,
            'selected' => $id === $this->fields['login_theme'],
         ];
      }

      $themeVariables = [];
      $themeInfo = PluginTrademarkTheme::getThemeInfo($this->fields['login_theme']);
      if ($themeInfo && isset($themeInfo['variables'])) {
         foreach ($themeInfo['variables'] as $key => $variable) {
            $fieldName = "login_theme-{$themeInfo['id']}-{$key}";
         $fieldValue = $this->fields[$fieldName] ?? null;
            if (!$fieldValue) {
               $fieldValue = $variable['default'];
            }
            $themeVariables[] = [
               'field_name' => $fieldName,
               'label'      => t_trademark($variable['name']),
               'value'      => $fieldValue,
               'color_html' => Html::showColorField($fieldName, [
                  'value'   => $fieldValue,
                  'display' => false,
               ]),
            ];
         }
      }

      $cssEditors = [
         'login'    => $this->getCssEditor('login', t_trademark('Login Page')),
         'internal' => $this->getCssEditor('internal', t_trademark('Internal Page')),
      ];

      TemplateRenderer::getInstance()->display('@trademark/config_form.html.twig', [
         'can_edit'                  => $canedit,
         'form_url'                  => Toolbox::getItemTypeFormURL('Config'),
         'config_context_html'       => Html::hidden('config_context', ['value' => 'trademark']),
         'config_class_html'         => Html::hidden('config_class', ['value' => __CLASS__]),
         'close_form_html'           => Html::closeForm(false),
         'fields'                    => $this->fields,
         'picture_fields'            => $pictureFields,
         'theme_options'             => $themeOptions,
         'theme_variables'           => $themeVariables,
         'css_editors'               => $cssEditors,
         'page_footer_display_html'  => Dropdown::showFromArray('page_footer_display', [
            'original' => __('Original'),
            'hide'     => t_trademark('Hide'),
            'custom'   => t_trademark('Custom'),
         ], [
            'value'   => $this->fields['page_footer_display'],
            'display' => false,
         ]),
         'page_footer_text_html'     => Html::textarea([
            'rand'              => $rand,
            'editor_id'         => 'text' . $rand,
            'name'              => 'page_footer_text',
            'value'             => $this->fields['page_footer_text'],
            'style'             => 'width: 100%',
            'rows'              => 2,
            'enable_richtext'   => true,
            'enable_fileupload' => false,
            'enable_images'     => false,
            'display'           => false,
         ]),
         'footer_editor_id'          => 'text' . $rand,
         'rand'                      => $rand,
         'root_doc'                  => $CFG_GLPI['root_doc'],
         'labels'                    => [
            'trademark'                 => t_trademark('Trademark'),
            'customize'                 => __('Customize logos, backgrounds and custom styling'),
            'favicon_title'             => t_trademark('Favicon and Title'),
            'login_page'                => t_trademark('Login Page'),
            'internal_page'             => t_trademark('Internal Page'),
            'title'                     => __('Title'),
            'browser_tab_title'         => __('Browser tab title'),
            'replaces_title'            => __('Replaces the GLPI word in browser tab title.'),
            'footer'                    => t_trademark('Footer'),
            'footer_text'               => t_trademark('Footer text'),
            'theme'                     => t_trademark('Theme'),
            'select_theme'              => __('Select a predefined visual theme'),
            'original'                  => __('Original'),
            'preview'                   => __('Preview'),
            'theme_colors'              => __('Theme colors'),
            'remove'                    => __('Remove'),
            'reset'                     => t_trademark('Reset'),
            'max_width'                 => t_trademark('Max Width'),
            'max_height'                => t_trademark('Max Height'),
            'logo_dimensions'           => __('Logo Dimensions'),
            'width'                     => __('Width'),
            'height'                    => __('Height'),
            'save_hint'                 => t_trademark('Click save to apply your brand modifications.'),
            'save'                      => _sx('button', 'Save'),
            'custom_css'                => t_trademark('Custom CSS for %1$s'),
            'css_type'                  => t_trademark('CSS Type'),
         ],
      ]);

      return true;
   }
}