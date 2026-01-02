<?php

declare(strict_types=1);

namespace Prayog\Tests;

use PHPUnit\Framework\TestCase;
use Prayog\Output\Formatter;

final class FormatterTest extends TestCase
{
    private Formatter $formatter;

    private Formatter $noColorFormatter;

    protected function setUp(): void
    {
        $this->formatter = new Formatter(colorize: true);
        $this->noColorFormatter = new Formatter(colorize: false);
    }

    public function test_format_null(): void
    {
        $result = $this->formatter->format(null);
        $this->assertStringContainsString('null', $result);
        $this->assertStringContainsString("\033", $result);

        $noColorResult = $this->noColorFormatter->format(null);
        $this->assertSame('null', $noColorResult);
    }

    public function test_format_boolean_true(): void
    {
        $result = $this->formatter->format(true);
        $this->assertStringContainsString('true', $result);
        $this->assertStringContainsString("\033", $result);

        $noColorResult = $this->noColorFormatter->format(true);
        $this->assertSame('true', $noColorResult);
    }

    public function test_format_boolean_false(): void
    {
        $result = $this->formatter->format(false);
        $this->assertStringContainsString('false', $result);
        $this->assertStringContainsString("\033", $result);

        $noColorResult = $this->noColorFormatter->format(false);
        $this->assertSame('false', $noColorResult);
    }

    public function test_format_integer(): void
    {
        $result = $this->formatter->format(42);
        $this->assertStringContainsString('42', $result);
        $this->assertStringContainsString("\033", $result);

        $noColorResult = $this->noColorFormatter->format(42);
        $this->assertSame('42', $noColorResult);
    }

    public function test_format_negative_integer(): void
    {
        $result = $this->formatter->format(-100);
        $this->assertStringContainsString('-100', $result);
    }

    public function test_format_float(): void
    {
        $result = $this->formatter->format(3.14);
        $this->assertStringContainsString('3.14', $result);
        $this->assertStringContainsString("\033", $result);

        $noColorResult = $this->noColorFormatter->format(3.14);
        $this->assertSame('3.14', $noColorResult);
    }

    public function test_format_string(): void
    {
        $result = $this->formatter->format('hello');
        $this->assertStringContainsString("'hello'", $result);
        $this->assertStringContainsString("\033", $result);

        $noColorResult = $this->noColorFormatter->format('hello');
        $this->assertStringContainsString("'hello'", $noColorResult);
    }

    public function test_format_empty_string(): void
    {
        $result = $this->formatter->format('');
        $this->assertStringContainsString("''", $result);
    }

    public function test_format_empty_array(): void
    {
        $result = $this->formatter->format([]);
        $this->assertStringContainsString('array(0)', $result);
        $this->assertStringContainsString('[]', $result);
    }

    public function test_format_simple_array(): void
    {
        $result = $this->formatter->format([1, 2, 3]);
        $this->assertStringContainsString('array(3)', $result);
        $this->assertStringContainsString('1', $result);
        $this->assertStringContainsString('2', $result);
        $this->assertStringContainsString('3', $result);
    }

    public function test_format_associative_array(): void
    {
        $result = $this->formatter->format(['key' => 'value', 'num' => 42]);
        $this->assertStringContainsString('array(2)', $result);
        $this->assertStringContainsString('key', $result);
        $this->assertStringContainsString('value', $result);
    }

    public function test_format_large_array(): void
    {
        $array = [1, 2, 3, 4, 5, 6];
        $result = $this->formatter->format($array);
        $this->assertStringContainsString('array(6)', $result);
        $this->assertStringContainsString('...', $result); // Should truncate
    }

    public function test_format_nested_array(): void
    {
        $array = [[1, 2], [3, 4]];
        $result = $this->formatter->format($array);
        $this->assertStringContainsString('array(2)', $result);
    }

    public function test_format_object(): void
    {
        $object = new \stdClass;
        $result = $this->formatter->format($object);
        $this->assertStringContainsString('object(stdClass)', $result);
        $this->assertStringContainsString("\033", $result);

        $noColorResult = $this->noColorFormatter->format($object);
        $this->assertStringContainsString('object(stdClass)', $noColorResult);
    }

    public function test_format_custom_object(): void
    {
        $object = new class {};
        $result = $this->formatter->format($object);
        $this->assertStringContainsString('object(', $result);
    }

    public function test_format_resource(): void
    {
        $resource = fopen('php://memory', 'r');
        if ($resource === false) {
            $this->markTestSkipped('Could not create resource');
        }

        try {
            $result = $this->formatter->format($resource);
            $this->assertStringContainsString('resource(', $result);
            $this->assertStringContainsString('stream', $result);
        } finally {
            fclose($resource);
        }
    }

    public function test_format_long_string(): void
    {
        $longString = str_repeat('a', 30);
        $result = $this->formatter->format($longString);
        // Should show full string in format, but preview might truncate
        $this->assertStringContainsString("'", $result);
    }

    public function test_format_array_with_string_keys(): void
    {
        $array = ['first' => 1, 'second' => 2];
        $result = $this->formatter->format($array);
        $this->assertStringContainsString("'first'", $result);
        $this->assertStringContainsString("'second'", $result);
    }

    public function test_format_array_with_numeric_keys(): void
    {
        $array = [0 => 'a', 1 => 'b'];
        $result = $this->formatter->format($array);
        $this->assertStringContainsString('0', $result);
        $this->assertStringContainsString('1', $result);
    }

    public function test_format_deeply_nested_array(): void
    {
        $array = [[[1, 2], [3, 4]], [[5, 6], [7, 8]]];
        $result = $this->formatter->format($array);
        $this->assertStringContainsString('array(', $result);
        // Should handle depth limit
        $this->assertStringContainsString('...', $result);
    }

    public function test_format_unsupported_type(): void
    {
        // Create a value with an unsupported type (though PHP doesn't have many)
        // We'll test the default case by using a callable, which might fall through
        $callable = function () {};
        $result = $this->formatter->format($callable);
        // Should convert to string
        $this->assertIsString($result);
    }
}
