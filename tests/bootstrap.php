<?php

require_once __DIR__ . '/../vendor/autoload.php';

if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', __DIR__ . '/fixtures/glpi');
}

require_once __DIR__ . '/../inc/toolbox.class.php';
require_once __DIR__ . '/../inc/theme.class.php';
require_once __DIR__ . '/../inc/fileversion.class.php';
require_once __DIR__ . '/../inc/scss.class.php';
