<?php

use PHPUnit\Framework\TestCase;

class FileVersionTest extends TestCase {
   public function testFirstConversionReturnsUnversionedPathAndNextAddsTimestamp(): void {
      $previousCache = $GLOBALS['GLPI_CACHE'] ?? null;
      $hadCache = array_key_exists('GLPI_CACHE', $GLOBALS);
      $GLOBALS['GLPI_CACHE'] = new class {
         public function get(string $key): int {
            return 1700000000;
         }
      };

      try {
         $fileVersion = new PluginTrademarkFileVersion('assets/theme.css');

         self::assertSame('assets/theme.css', (string) $fileVersion);
         self::assertSame('assets/theme.css?_=1700000000', (string) $fileVersion);
      } finally {
         if ($hadCache) {
            $GLOBALS['GLPI_CACHE'] = $previousCache;
         } else {
            unset($GLOBALS['GLPI_CACHE']);
         }
      }
   }
}
