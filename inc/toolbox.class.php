<?php

class PluginTrademarkToolbox {

   public static function getTimestamp() {
      global $GLPI_CACHE;

      if (!$GLPI_CACHE) {
         return PluginTrademarkConfig::getConfig('timestamp', time());
      }

      $timestamp = $GLPI_CACHE->get('trademark_timestamp');
      if (!$timestamp) {
         $timestamp = time();
         $GLPI_CACHE->set('trademark_timestamp', $timestamp);
      }
      return $timestamp;
   }

   public static function setTimestamp($timestamp = null) {
      global $GLPI_CACHE;

      if (!$timestamp) {
         $timestamp = time();
      }

      if (!$GLPI_CACHE) {
         Config::setConfigurationValues('trademark', [
            'timestamp' => $timestamp,
         ]);
         return;
      }

      $GLPI_CACHE->set('trademark_timestamp', $timestamp);
   }

   public static function startsWith($haystack, $needle) {
      $length = strlen($needle);
      return (substr($haystack, 0, $length) === $needle);
   }

   public static function getPictureUrl($path) {
      if (empty($path)) {
         return null;
      }

      $pictureParams = [];
      if (str_contains($path, '&theme=')) {
         [$picturePath, $theme] = explode('&theme=', $path, 2);
         $pictureParams['theme'] = $theme;
      } else {
         $picturePath = $path;
      }

      $pictureParams['path'] = $picturePath;
      $pictureParams['_']    = self::getTimestamp();

      return Html::getPrefixedUrl('/plugins/trademark/front/picture.send.php?' . http_build_query($pictureParams));
   }

   public static function savePicture($src, $uniq_prefix = null) {
      $basePath = GLPI_PLUGIN_DOC_DIR . "/trademark";

      if (method_exists(Document::class, 'isImage') && !Document::isImage($src)) {
         return false;
      }

      $filename     = uniqid($uniq_prefix);
      $ext          = pathinfo($src, PATHINFO_EXTENSION);
      $subdirectory = substr($filename, -2); // subdirectory based on last 2 hex digit

      $i = 0;
      do {
         // Iterate on possible suffix while dest exists.
         // This case will almost never exists as dest is based on an unique id.
         $dest = $basePath
         . '/' . $subdirectory
         . '/' . $filename . ($i > 0 ? '_' . $i : '') . '.' . $ext;
         $i++;
      } while (file_exists($dest));

      if (!is_dir($basePath . '/' . $subdirectory) && !mkdir($basePath . '/' . $subdirectory, 0777, true)) {
         return false;
      }

      if (!rename($src, $dest)) {
         return false;
      }

      return substr($dest, strlen($basePath . '/')); // Return dest relative to GLPI_PICTURE_DIR
   }

   public static function deletePicture($path) {
      $basePath = GLPI_PLUGIN_DOC_DIR . "/trademark";
      $fullpath = $basePath . '/' . $path;

      if (!file_exists($fullpath)) {
         return false;
      }

      $fullpath = realpath($fullpath);
      if (!static::startsWith($fullpath, realpath($basePath))) {
         return false;
      }

      return @unlink($fullpath);
   }

   /**
    * Resize a picture to fit within the requested dimensions while preserving its format and aspect ratio.
    *
    * @since 0.85
    *
    * @param string  $source_path   path of the picture to be resized
    * @param string  $dest_path     path of the new resized picture
    * @param integer $new_width     new width after resized (default 71)
    * @param integer $new_height    new height after resized (default 71)
    * @param integer $img_y         y axis of picture (default 0)
    * @param integer $img_x         x axis of picture (default 0)
    * @param integer $img_width     width of picture (default 0)
    * @param integer $img_height    height of picture (default 0)
    * @param integer $max_size      max size of the picture (default 500, is set to 0 no resize)
    *
    * @return boolean
    **/
   public static function resizePicture(
      $source_path,
      $dest_path,
      $new_width = 71,
      $new_height = 71,
      $img_y = 0,
      $img_x = 0,
      $img_width = 0,
      $img_height = 0,
      $max_size = 500
   ) {

      $img_infos = @getimagesize($source_path);
      if ($img_infos === false || $img_infos[0] <= 0 || $img_infos[1] <= 0) {
         return false;
      }

      if (empty($img_width)) {
         $img_width  = $img_infos[0];
      }
      if (empty($img_height)) {
         $img_height = $img_infos[1];
      }
      if (empty($new_width)) {
         $new_width  = $img_infos[0];
      }
      if (empty($new_height)) {
         $new_height = $img_infos[1];
      }

      if ($img_width <= 0 || $img_height <= 0 || $new_width <= 0 || $new_height <= 0) {
         return false;
      }

      $max_width  = $new_width;
      $max_height = $new_height;
      if ($max_size > 0) {
         $max_width  = min($max_width, $max_size);
         $max_height = min($max_height, $max_size);
      }

      $scale = min($max_width / $img_width, $max_height / $img_height);
      if ($max_size > 0) {
         $scale = min($scale, $max_size / max($img_width, $img_height));
      }

      $img_type = $img_infos[2];
      if ($img_type === IMAGETYPE_GIF || $scale >= 1.0) {
         return copy($source_path, $dest_path);
      }

      $new_width  = max(1, (int) round($img_width * $scale));
      $new_height = max(1, (int) round($img_height * $scale));

      switch ($img_type) {
         case IMAGETYPE_BMP:
            $source_res = function_exists('imagecreatefrombmp') ? imagecreatefrombmp($source_path) : false;
            break;

         case IMAGETYPE_GIF:
            $source_res = imagecreatefromgif($source_path);
            break;

         case IMAGETYPE_JPEG:
            $source_res = imagecreatefromjpeg($source_path);
            break;

         case IMAGETYPE_PNG:
            $source_res = imagecreatefrompng($source_path);
            break;

         case IMAGETYPE_WEBP:
            $source_res = function_exists('imagecreatefromwebp') ? imagecreatefromwebp($source_path) : false;
            break;

         default:
            return false;
      }

      if ($source_res === false) {
         return false;
      }

      $source_dest = imagecreatetruecolor($new_width, $new_height);
      if ($source_dest === false) {
         imagedestroy($source_res);
         return false;
      }

      if ($img_type === IMAGETYPE_GIF || $img_type === IMAGETYPE_PNG || $img_type === IMAGETYPE_WEBP) {
         imagecolortransparent($source_dest, imagecolorallocatealpha($source_dest, 0, 0, 0, 127));
         imagealphablending($source_dest, false);
         imagesavealpha($source_dest, true);
      }

      $resized = imagecopyresampled(
         $source_dest,
         $source_res,
         0,
         0,
         $img_x,
         $img_y,
         $new_width,
         $new_height,
         $img_width,
         $img_height
      );

      if (!$resized) {
         imagedestroy($source_res);
         imagedestroy($source_dest);
         return false;
      }

      $result = match ($img_type) {
         IMAGETYPE_BMP => function_exists('imagebmp') && imagebmp($source_dest, $dest_path),
         IMAGETYPE_GIF => imagegif($source_dest, $dest_path),
         IMAGETYPE_JPEG => imagejpeg($source_dest, $dest_path, 95),
         IMAGETYPE_PNG => imagepng($source_dest, $dest_path),
         IMAGETYPE_WEBP => function_exists('imagewebp') && imagewebp($source_dest, $dest_path, 90),
         default => false,
      };

      imagedestroy($source_res);
      imagedestroy($source_dest);

      return $result;
   }
}
