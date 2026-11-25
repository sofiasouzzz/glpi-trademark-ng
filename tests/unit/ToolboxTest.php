<?php

use PHPUnit\Framework\TestCase;

class ToolboxTest extends TestCase {
   #[\PHPUnit\Framework\Attributes\DataProvider('validCssDimensions')]
   public function testGetCssDimensionAcceptsSupportedValues(string $value): void {
      self::assertSame($value, PluginTrademarkToolbox::getCssDimension($value, '100px'));
   }

   public static function validCssDimensions(): iterable {
      yield 'pixels' => ['240px'];
      yield 'decimal rem' => ['1.5rem'];
      yield 'percentage' => ['75%'];
      yield 'viewport height' => ['50vh'];
   }

   #[\PHPUnit\Framework\Attributes\DataProvider('invalidCssDimensions')]
   public function testGetCssDimensionUsesDefaultForInvalidValues(mixed $value): void {
      self::assertSame('100px', PluginTrademarkToolbox::getCssDimension($value, '100px'));
   }

   public static function invalidCssDimensions(): iterable {
      yield 'empty value' => [''];
      yield 'unitless number' => ['240'];
      yield 'negative value' => ['-10px'];
      yield 'unsupported unit' => ['10pt'];
      yield 'CSS injection' => ['10px; color: red'];
      yield 'non-string value' => [240];
      yield 'too long' => [str_repeat('1', 33) . 'px'];
   }
}
