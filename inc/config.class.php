<?php

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
   protected function renderPictureField(string $name, string $label, ?string $recommendedSize = null): void
   {
      $hasImage = !empty($this->fields[$name]);

      echo '<div class="mb-3">';
      echo '<label class="form-label fw-bold d-flex align-items-center justify-content-between">';
      echo '<span>' . $label . '</span>';
      if ($recommendedSize) {
         echo '<span class="badge bg-secondary-lt text-secondary font-monospace"><i class="ti ti-dimensions me-1"></i>' . sprintf(t_trademark('Recommended size: %1$s'), $recommendedSize) . '</span>';
      }
      echo '</label>';

      echo '<div class="card p-3 bg-body-tertiary border">';
      echo '<div class="row align-items-center g-3">';

      if ($hasImage) {
         $imageUrl = PluginTrademarkToolbox::getPictureUrl($this->fields[$name]);
         echo '<div class="col-auto">';
         echo '<div class="rounded border p-2 bg-body d-flex align-items-center justify-content-center shadow-sm" style="min-width: 110px; min-height: 75px; max-width: 180px;">';
         echo Html::image($imageUrl, [
            'style' => 'max-width: 140px; max-height: 65px; object-fit: contain;',
            'alt'   => $label,
            'class' => 'img-fluid',
         ]);
         echo '</div>';
         echo '</div>';

         echo '<div class="col-auto">';
         echo '<div class="form-check form-switch">';
         echo '<input class="form-check-input" type="checkbox" name="_blank_' . $name . '" id="_blank_' . $name . '" value="1">';
         echo '<label class="form-check-label text-danger small fw-semibold" for="_blank_' . $name . '">';
         echo '<i class="ti ti-trash me-1"></i>' . t_trademark('Reset') . ' (' . __('Remove') . ')';
         echo '</label>';
         echo '</div>';
         echo '</div>';
      }

      echo '<div class="col">';
      Html::file([
         'name'          => $name,
         'onlyimages'    => true,
         'min_file_size' => 1,
      ]);
      echo '</div>';

      echo '</div>'; // .row
      echo '</div>'; // .card
      echo '</div>'; // .mb-3
   }

   /**
    * Renderiza o editor de CSS/SCSS corporativo com CodeMirror e seletor de tipo.
    */
   protected function renderCssEditor(string $name, string $label): void
   {
      $fullName = "{$name}_css_custom";
      $type     = "{$name}_css_type";
      $rand     = mt_rand();

      echo '<div class="card mt-4 border shadow-sm">';
      echo '<div class="card-header bg-body-tertiary d-flex align-items-center justify-content-between">';
      echo '<div class="d-flex align-items-center gap-2">';
      echo '<i class="ti ti-code fs-2 text-primary"></i>';
      echo '<span class="fw-bold">' . sprintf(t_trademark('Custom CSS for %1$s'), $label) . '</span>';
      echo '</div>';

      echo '<div class="d-flex align-items-center gap-2">';
      echo '<label class="form-label m-0 small fw-semibold text-muted">' . t_trademark('CSS Type') . ':</label>';
      $css_type = [
         'off' => __('Disabled'),
         'css' => 'CSS',
      ];
      if (PluginTrademarkScss::hasScssSuport()) {
         $css_type['scss'] = 'SCSS';
      }
      Dropdown::showFromArray($type, $css_type, [
         'value'   => $this->fields[$type],
         'display' => true,
      ]);
      echo '</div>';
      echo '</div>'; // .card-header

      echo '<div class="card-body p-0">';
      echo sprintf(
         '<textarea %1$s>',
         Html::parseAttributes([
            'id'    => $fullName . '_' . $rand,
            'name'  => $fullName,
            'class' => 'form-control trademark-codemirror font-monospace',
         ])
      );
      echo htmlspecialchars(rtrim($this->fields[$fullName]), ENT_QUOTES, 'UTF-8');
      echo '</textarea>';
      echo '</div>'; // .card-body
      echo '</div>'; // .card

      echo Html::scriptBlock('
         $(function() {
            var textarea = document.getElementById("' . $fullName . '_' . $rand . '");
            if (textarea && typeof CodeMirror !== "undefined") {
               var editorCode = CodeMirror.fromTextArea(textarea, {
                  mode: "text/x-scss",
                  lineNumbers: true,
                  viewportMargin: Infinity,
                  extraKeys: {
                     "Ctrl-Space": "autocomplete"
                  },
                  foldGutter: true,
                  gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"]
               });
               $("#' . $fullName . '_' . $rand . '").data("CodeMirrorInstance", editorCode);
               setTimeout(function () { editorCode.refresh(); }, ' . (300 + self::$_i++ * 80) . ');
            }
         });
      ');
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

      echo '<style>
      .trademark-card {
         max-width: 1200px;
         margin: 0 auto;
      }
      .CodeMirror {
         border: 0 !important;
         min-height: 180px;
         font-size: 13px;
      }
      .trademark-themeselect-container .select2-selection__rendered,
      .trademark-themeselect-dropdown .select2-results__option {
         height: 60px !important;
         line-height: 60px !important;
      }
      .trademark-themeselect-container .select2-selection__rendered img,
      .trademark-themeselect-dropdown .select2-results__option img {
         width: 130px;
         height: 60px;
         object-fit: cover;
         border-radius: 4px;
         float: right;
      }
      .trademark-themeselect-container.select2-selection--single {
         height: 60px !important;
      }
      .trademark-themeselect-container.select2-selection--single:before {
         line-height: 60px !important;
      }
      .trademark-themeselect-container .select2-selection__arrow {
         height: 59px !important;
      }
      </style>';

      if ($canedit) {
         echo "<form name='form' action=\"" . Toolbox::getItemTypeFormURL('Config') . "\" method='post' enctype='multipart/form-data'>";
      }
      echo Html::hidden('config_context', ['value' => 'trademark']);
      echo Html::hidden('config_class', ['value' => __CLASS__]);

      echo '<div class="card card-glpi shadow-sm border trademark-card mb-4">';

      // ── Cabeçalho com Abas Corporativas Bootstrap 5 ─────────────────────────
      echo '<div class="card-header bg-body-tertiary border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">';
      echo '<div class="d-flex align-items-center gap-2">';
      echo '<i class="ti ti-palette fs-1 text-primary"></i>';
      echo '<div>';
      echo '<h3 class="card-title m-0 fw-bold">' . t_trademark('Trademark') . '</h3>';
      echo '<div class="text-muted small">' . __('Customize logos, backgrounds and custom styling') . '</div>';
      echo '</div>';
      echo '</div>';

      echo '<ul class="nav nav-tabs card-header-tabs" id="trademark-tabs-' . $rand . '" role="tablist">';
      echo '<li class="nav-item" role="presentation">';
      echo '<button class="nav-link active d-flex align-items-center gap-2 fw-semibold" id="tab-favicon-btn" data-bs-toggle="tab" data-bs-target="#tab-trademark-favicon" type="button" role="tab" aria-selected="true">';
      echo '<i class="ti ti-browser"></i><span>' . t_trademark('Favicon and Title') . '</span>';
      echo '</button>';
      echo '</li>';

      echo '<li class="nav-item" role="presentation">';
      echo '<button class="nav-link d-flex align-items-center gap-2 fw-semibold" id="tab-login-btn" data-bs-toggle="tab" data-bs-target="#tab-trademark-login" type="button" role="tab" aria-selected="false">';
      echo '<i class="ti ti-login"></i><span>' . t_trademark('Login Page') . '</span>';
      echo '</button>';
      echo '</li>';

      echo '<li class="nav-item" role="presentation">';
      echo '<button class="nav-link d-flex align-items-center gap-2 fw-semibold" id="tab-internal-btn" data-bs-toggle="tab" data-bs-target="#tab-trademark-internal" type="button" role="tab" aria-selected="false">';
      echo '<i class="ti ti-layout-navbar"></i><span>' . t_trademark('Internal Page') . '</span>';
      echo '</button>';
      echo '</li>';
      echo '</ul>';
      echo '</div>'; // .card-header

      // ── Corpo do Card com Conteúdo das Abas ─────────────────────────────────
      echo '<div class="card-body p-4 tab-content">';

      // ────────────────────────────────────────────────────────────────────────
      // ABA 1: Identidade & Geral
      // ────────────────────────────────────────────────────────────────────────
      echo '<div class="tab-pane fade show active" id="tab-trademark-favicon" role="tabpanel">';
      echo '<div class="row g-4">';

      echo '<div class="col-lg-6">';
      $this->renderPictureField('favicon_picture', __('Favicon') . ' (' . __('Picture') . ')', '192px x 192px');
      echo '</div>';

      echo '<div class="col-lg-6">';
      echo '<div class="mb-3">';
      echo '<label class="form-label fw-bold">' . __('Title') . ' (' . __('Browser tab title') . ')</label>';
      echo sprintf(
         '<input type="text" class="form-control" %1$s />',
         Html::parseAttributes([
            'name'        => 'page_title',
            'value'       => $this->fields['page_title'],
            'placeholder' => 'Ex: GLPI Corporativo',
         ])
      );
      echo '<div class="form-text text-muted small">' . __('Replaces the GLPI word in browser tab title.') . '</div>';
      echo '</div>';

      echo '<div class="mb-3">';
      echo '<label class="form-label fw-bold">' . t_trademark('Footer') . '</label>';
      Dropdown::showFromArray('page_footer_display', [
         'original' => __('Original'),
         'hide'     => t_trademark('Hide'),
         'custom'   => t_trademark('Custom'),
      ], [
         'value'   => $this->fields['page_footer_display'],
         'display' => true,
      ]);
      echo '</div>';
      echo '</div>'; // .col-lg-6

      echo '<div class="col-12">';
      echo '<div class="mb-3">';
      echo '<label class="form-label fw-bold">' . t_trademark('Footer text') . '</label>';
      Html::textarea([
         'rand'              => $rand,
         'editor_id'         => 'text' . $rand,
         'name'              => 'page_footer_text',
         'value'             => $this->fields['page_footer_text'],
         'style'             => 'width: 100%',
         'rows'              => 2,
         'enable_richtext'   => true,
         'enable_fileupload' => false,
         'enable_images'     => false,
      ]);
      ?>
      <script type="text/javascript">
         $(document).ready(function() {
            if (typeof tinyMCE !== "undefined") {
               var editor = tinyMCE.get(<?php echo json_encode('text' . $rand); ?>);
               if (editor && editor.settings) {
                  editor.settings.force_br_newlines = true;
                  editor.settings.force_p_newlines = false;
                  editor.settings.forced_root_block = '';
               }
            }
         });
      </script>
      <?php
      echo '</div>';
      echo '</div>'; // .col-12

      echo '</div>'; // .row
      echo '</div>'; // #tab-trademark-favicon

      // ────────────────────────────────────────────────────────────────────────
      // ABA 2: Tela de Login
      // ────────────────────────────────────────────────────────────────────────
      echo '<div class="tab-pane fade" id="tab-trademark-login" role="tabpanel">';
      echo '<div class="row g-4">';

      // Seletor de Tema
      echo '<div class="col-12">';
      echo '<div class="card p-3 bg-body-tertiary border">';
      echo '<div class="row align-items-center g-3">';
      echo '<div class="col-md-3">';
      echo '<label class="form-label m-0 fw-bold"><i class="ti ti-brush me-1 text-primary"></i>' . t_trademark('Theme') . '</label>';
      echo '<div class="small text-muted">' . __('Select a predefined visual theme') . '</div>';
      echo '</div>';

      echo '<div class="col-md-7">';
      echo sprintf('<select %1$s class="form-select">', Html::parseAttributes(['name' => 'login_theme']));
      echo '<option value="">GLPI (' . __('Original') . ')</option>';
      foreach (PluginTrademarkTheme::getLoginThemes() as $id => $theme) {
         $attrs = [
            'name'  => 'login_theme',
            'value' => $id,
         ];
         if (isset($theme['login-preview']) && $theme['login-preview']) {
            $attrs['data-preview'] = $theme['login-preview'];
         }
         if ($id === $this->fields['login_theme']) {
            $attrs['selected'] = 'selected';
         }
         echo sprintf(
            '<option %1$s>%2$s</option>',
            Html::parseAttributes($attrs),
            htmlspecialchars($theme['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
         );
      }
      echo '</select>';
      ?>
      <script type="text/javascript">
         function trademarkFormatThemes(theme) {
            var data = theme && theme.element && theme.element.dataset || {};
            if (!theme.id || !data.preview) {
               return $('<span></span>', {
                  html: '<img src="../plugins/trademark/pics/login.preview.png"/>&nbsp;' + theme.text
               });
            }
            return $('<span></span>', {
               html: '<img src="../plugins/trademark/themes/' + theme.id + '/' + data.preview + '"/>&nbsp;' + theme.text
            });
         }
         $("select[name=login_theme]").select2({
            templateResult: trademarkFormatThemes,
            templateSelection: trademarkFormatThemes,
            width: '100%',
            selectionCssClass: 'trademark-themeselect-container',
            dropdownCssClass: 'trademark-themeselect-dropdown',
            escapeMarkup: function(m) { return m; }
         });
      </script>
      <?php
      echo '</div>'; // .col-md-7

      echo '<div class="col-md-2 text-md-end">';
      echo '<button type="button" id="trademark-preview" class="btn btn-outline-primary d-inline-flex align-items-center gap-1 w-100 justify-content-center">';
      echo '<i class="ti ti-external-link"></i>' . __('Preview');
      echo '</button>';
      ?>
      <script type="text/javascript">
         $('#trademark-preview').on('click', function(e) {
            e.preventDefault();
            var url = <?php echo json_encode($CFG_GLPI['root_doc'] . "/index.php"); ?>;
            url += '?noAUTO=1';
            var theme = $("select[name=login_theme]").val() || 'original';
            url += '&theme=' + encodeURIComponent(theme);
            window.open(url, 'trademark_preview', 'titlebar=0&status=0');
         });
      </script>
      <?php
      echo '</div>'; // .col-md-2
      echo '</div>'; // .row
      echo '</div>'; // .card
      echo '</div>'; // .col-12

      // Variáveis do Tema
      $themeInfo = PluginTrademarkTheme::getThemeInfo($this->fields['login_theme']);
      if ($themeInfo && isset($themeInfo['variables'])) {
         echo '<div class="col-12">';
         echo '<div class="card p-3 border shadow-none bg-body-tertiary">';
         echo '<h5 class="fw-bold mb-3"><i class="ti ti-color-swatch me-1 text-primary"></i>' . __('Theme colors') . '</h5>';
         echo '<div class="row g-3">';
         foreach ($themeInfo['variables'] as $k => $v) {
            $themeId    = $themeInfo['id'];
            $fieldName  = "login_theme-$themeId-$k";
            $fieldValue = $v['default'];
            if (isset($this->fields[$fieldName]) && $this->fields[$fieldName]) {
               $fieldValue = $this->fields[$fieldName];
            }
            echo '<div class="col-md-4">';
            echo '<label class="form-label small fw-semibold">' . t_trademark($v['name']) . '</label>';
            echo '<div class="d-flex align-items-center gap-2">';
            Html::showColorField($fieldName, ['value' => $fieldValue]);
            echo '<div class="form-check form-switch m-0">';
            echo '<input class="form-check-input" type="checkbox" name="_blank_' . $fieldName . '" id="_blank_' . $fieldName . '" value="1">';
            echo '<label class="form-check-label text-muted small" for="_blank_' . $fieldName . '">' . t_trademark('Reset') . '</label>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
         }
         echo '</div>'; // .row
         echo '</div>'; // .card
         echo '</div>'; // .col-12
      }

      // Imagem do Logo de Login
      echo '<div class="col-lg-6">';
      $this->renderPictureField('login_picture', __('Login Logo') . ' (' . __('Picture') . ')', '145px x 80px');

      echo '<div class="row g-2">';
      echo '<div class="col-6">';
      echo '<label class="form-label small fw-semibold">' . __('Max Width') . '</label>';
      echo sprintf(
         '<input type="text" class="form-control form-control-sm font-monospace" %1$s />',
         Html::parseAttributes([
            'name'  => 'login_picture_max_width',
            'value' => $this->fields['login_picture_max_width'],
         ])
      );
      echo '</div>';

      echo '<div class="col-6">';
      echo '<label class="form-label small fw-semibold">' . __('Max Height') . '</label>';
      echo sprintf(
         '<input type="text" class="form-control form-control-sm font-monospace" %1$s />',
         Html::parseAttributes([
            'name'  => 'login_picture_max_height',
            'value' => $this->fields['login_picture_max_height'],
         ])
      );
      echo '</div>';
      echo '</div>'; // .row
      echo '</div>'; // .col-lg-6

      // Fundo de Login
      echo '<div class="col-lg-6">';
      $this->renderPictureField('login_background_picture', t_trademark('Background picture'), '1920px x 1080px');
      echo '</div>';

      // Editor CSS Login
      echo '<div class="col-12">';
      $this->renderCssEditor('login', t_trademark('Login Page'));
      echo '</div>';

      echo '</div>'; // .row
      echo '</div>'; // #tab-trademark-login

      // ────────────────────────────────────────────────────────────────────────
      // ABA 3: Interface Interna
      // ────────────────────────────────────────────────────────────────────────
      echo '<div class="tab-pane fade" id="tab-trademark-internal" role="tabpanel">';
      echo '<div class="row g-4">';

      echo '<div class="col-lg-7">';
      $this->renderPictureField('internal_picture', __('Header Logo') . ' (' . __('Internal Page') . ')', '100px x 55px');
      echo '</div>';

      echo '<div class="col-lg-5">';
      echo '<div class="card p-3 bg-body-tertiary border">';
      echo '<h5 class="fw-bold mb-3"><i class="ti ti-ruler-2 me-1 text-primary"></i>' . __('Logo Dimensions') . '</h5>';

      echo '<div class="mb-3">';
      echo '<label class="form-label small fw-semibold">' . __('Width') . '</label>';
      echo sprintf(
         '<input type="text" class="form-control font-monospace" %1$s />',
         Html::parseAttributes([
            'name'  => 'internal_picture_width',
            'value' => $this->fields['internal_picture_width'],
         ])
      );
      echo '</div>';

      echo '<div class="mb-2">';
      echo '<label class="form-label small fw-semibold">' . __('Height') . '</label>';
      echo sprintf(
         '<input type="text" class="form-control font-monospace" %1$s />',
         Html::parseAttributes([
            'name'  => 'internal_picture_height',
            'value' => $this->fields['internal_picture_height'],
         ])
      );
      echo '</div>';

      echo '</div>'; // .card
      echo '</div>'; // .col-lg-5

      // Editor CSS Interno
      echo '<div class="col-12">';
      $this->renderCssEditor('internal', t_trademark('Internal Page'));
      echo '</div>';

      echo '</div>'; // .row
      echo '</div>'; // #tab-trademark-internal

      echo '</div>'; // .card-body.tab-content

      // ── Rodapé com Botão Salvar Corporativo ─────────────────────────────────
      if ($canedit) {
         echo '<div class="card-footer bg-body-tertiary d-flex align-items-center justify-content-between p-3 border-top">';
         echo '<div class="text-muted small d-none d-sm-block">';
         echo '<i class="ti ti-check text-success me-1"></i>' . __('Click save to apply your brand modifications.');
         echo '</div>';
         echo '<button type="submit" name="update" value="1" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 shadow-sm">';
         echo '<i class="ti ti-device-floppy fs-2"></i>';
         echo '<span class="fw-semibold">' . _sx('button', 'Save') . '</span>';
         echo '</button>';
         echo '</div>';
      }

      echo '</div>'; // .card

      if ($canedit) {
         Html::closeForm();
      }

      // Script para persistência e refresh de CodeMirror nas abas Bootstrap 5
      echo Html::scriptBlock('
         $(function() {
            var tabsContainer = document.getElementById("trademark-tabs-' . $rand . '");
            if (tabsContainer) {
               tabsContainer.querySelectorAll("button[data-bs-toggle=\'tab\']").forEach(function(tabBtn) {
                  tabBtn.addEventListener("shown.bs.tab", function(event) {
                     var targetSelector = event.target.getAttribute("data-bs-target");
                     if (targetSelector) {
                        localStorage.setItem("trademark_last_tab", targetSelector);
                        $(targetSelector).find(".trademark-codemirror").each(function() {
                           var cm = $(this).data("CodeMirrorInstance");
                           if (cm) {
                              cm.refresh();
                           }
                        });
                     }
                  });
               });

               var savedTab = localStorage.getItem("trademark_last_tab");
               if (savedTab) {
                  var targetBtn = tabsContainer.querySelector("button[data-bs-target=\'" + savedTab + "\']");
                  if (targetBtn && typeof bootstrap !== "undefined" && bootstrap.Tab) {
                     var bsTab = new bootstrap.Tab(targetBtn);
                     bsTab.show();
                  }
               }
            }
         });
      ');

      return true;
   }
}