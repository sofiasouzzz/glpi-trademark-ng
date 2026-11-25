<?php

use PHPUnit\Framework\TestCase;

class ThemeTest extends TestCase {
   public function testGetThemeInfoLoadsThemeMetadataAndAssets(): void {
      $theme = PluginTrademarkTheme::getThemeInfo('original');

      self::assertIsArray($theme);
      self::assertSame('original', $theme['id']);
      self::assertSame('GLPI 9.5', $theme['name']);
      self::assertSame('original/login.scss', PluginTrademarkTheme::getThemePath('original', 'login.scss'));
      self::assertSame('original/login.logo.png', PluginTrademarkTheme::getThemePath('original', 'login.logo.png'));
   }

   public function testGetThemeInfoReturnsFalseForMissingTheme(): void {
      self::assertFalse(PluginTrademarkTheme::getThemeInfo('missing-theme'));
   }

   public function testGetLoginThemesIncludesValidThemes(): void {
      $themes = PluginTrademarkTheme::getLoginThemes();

      self::assertArrayHasKey('original', $themes);
      self::assertSame('original', $themes['original']['id']);
   }
}
