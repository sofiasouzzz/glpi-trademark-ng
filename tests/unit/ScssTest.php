<?php

use PHPUnit\Framework\TestCase;

class ScssTest extends TestCase {
   private mixed $previousCache;

   private bool $hadCache;

   private array $previousGet;

   protected function setUp(): void {
      parent::setUp();

      $this->hadCache = array_key_exists('GLPI_CACHE', $GLOBALS);
      $this->previousCache = $GLOBALS['GLPI_CACHE'] ?? null;
      $this->previousGet = $_GET;
      $GLOBALS['GLPI_CACHE'] = null;
      $_GET = [];
   }

   protected function tearDown(): void {
      if ($this->hadCache) {
         $GLOBALS['GLPI_CACHE'] = $this->previousCache;
      } else {
         unset($GLOBALS['GLPI_CACHE']);
      }
      $_GET = $this->previousGet;

      parent::tearDown();
   }

   public function testScssSupportIsAvailable(): void {
      self::assertTrue(PluginTrademarkScss::hasScssSuport());
   }

   public function testCompileScssCompilesStylesAndVariables(): void {
      $css = PluginTrademarkScss::compileScss(
         '.button { color: $brand; padding: 2px 3px; }',
         ['brand' => \ScssPhp\ScssPhp\ValueConverter::parseValue('#123456')]
      );

      self::assertSame('.button{color:#123456;padding:2px 3px}', trim($css));
   }

   public function testCompileScssLoadsTildeImportsFromGlpiCssLibrary(): void {
      $css = PluginTrademarkScss::compileScss(
         '@import "~test/colors"; .button { color: $fixture-color; }'
      );

      self::assertSame('.button{color:#123456}', trim($css));
   }

   public function testCompileScssCachesCompiledStyles(): void {
      $cache = $this->createCache();
      $GLOBALS['GLPI_CACHE'] = $cache;
      $content = '.button { color: red; }';

      $compiled = PluginTrademarkScss::compileScss($content);
      self::assertSame('.button{color:red}', trim($compiled));
      self::assertCount(1, $cache->values);
      self::assertSame(1, $cache->setCalls);

      $cache->values[array_key_first($cache->values)] = 'cached css';
      self::assertSame('cached css', PluginTrademarkScss::compileScss($content));
      self::assertSame(1, $cache->getCalls);
   }

   public function testCompileScssUsesDifferentCacheEntriesForDifferentVariables(): void {
      $cache = $this->createCache();
      $GLOBALS['GLPI_CACHE'] = $cache;
      $content = '.button { color: $brand; }';

      $red = PluginTrademarkScss::compileScss(
         $content,
         ['brand' => \ScssPhp\ScssPhp\ValueConverter::parseValue('red')]
      );
      $blue = PluginTrademarkScss::compileScss(
         $content,
         ['brand' => \ScssPhp\ScssPhp\ValueConverter::parseValue('blue')]
      );

      self::assertSame('.button{color:red}', trim($red));
      self::assertSame('.button{color:blue}', trim($blue));
      self::assertCount(2, $cache->values);
   }

   public function testCompileScssInvalidatesCacheWhenImportedFileChanges(): void {
      $cache = $this->createCache();
      $GLOBALS['GLPI_CACHE'] = $cache;
      $filename = 'cache-' . bin2hex(random_bytes(8)) . '.scss';
      $importPath = GLPI_ROOT . '/' . $filename;
      $content = "@import '$filename';";

      try {
         file_put_contents($importPath, '.button { color: red; }');
         $first = PluginTrademarkScss::compileScss($content);
         file_put_contents($importPath, '.button { color: blue; }');
         $second = PluginTrademarkScss::compileScss($content);

         self::assertSame('.button{color:red}', trim($first));
         self::assertSame('.button{color:blue}', trim($second));
         self::assertCount(2, $cache->values);
      } finally {
         if (file_exists($importPath)) {
            unlink($importPath);
         }
      }
   }

   public function testReloadBypassesCachedStylesAndRefreshesCache(): void {
      $cache = $this->createCache();
      $GLOBALS['GLPI_CACHE'] = $cache;
      $content = '.button { color: red; }';
      PluginTrademarkScss::compileScss($content);
      $key = array_key_first($cache->values);
      $cache->values[$key] = 'stale css';
      $_GET['reload'] = '1';

      self::assertSame('.button{color:red}', trim(PluginTrademarkScss::compileScss($content)));
      self::assertSame(0, $cache->getCalls);
      self::assertSame(2, $cache->setCalls);
      self::assertSame('.button{color:red}', trim($cache->values[$key]));
   }

   public function testNoCacheSkipsCacheWrite(): void {
      $cache = $this->createCache();
      $GLOBALS['GLPI_CACHE'] = $cache;
      $_GET['nocache'] = '1';

      self::assertSame('.button{color:red}', trim(PluginTrademarkScss::compileScss('.button { color: red; }')));
      self::assertSame(0, $cache->setCalls);
      self::assertSame([], $cache->values);
   }

   private function createCache(): object {
      return new class {
         public array $values = [];
         public int $hasCalls = 0;
         public int $getCalls = 0;
         public int $setCalls = 0;

         public function has(string $key): bool {
            $this->hasCalls++;
            return array_key_exists($key, $this->values);
         }

         public function get(string $key): mixed {
            $this->getCalls++;
            return $this->values[$key];
         }

         public function set(string $key, mixed $value): void {
            $this->setCalls++;
            $this->values[$key] = $value;
         }
      };
   }
}
