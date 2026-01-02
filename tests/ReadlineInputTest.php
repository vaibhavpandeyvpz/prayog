<?php

declare(strict_types=1);

namespace Prayog\Tests;

use PHPUnit\Framework\TestCase;
use Prayog\Input\ReadlineInput;
use ReflectionClass;
use ReflectionMethod;

final class ReadlineInputTest extends TestCase
{
    private string $testHistoryFile;

    protected function setUp(): void
    {
        $this->testHistoryFile = sys_get_temp_dir().'/test_prayog_history_'.uniqid();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testHistoryFile)) {
            unlink($this->testHistoryFile);
        }
    }

    public function test_constructor(): void
    {
        $input = new ReadlineInput('test> ', $this->testHistoryFile);
        $this->assertInstanceOf(ReadlineInput::class, $input);
    }

    public function test_save_history(): void
    {
        $input = new ReadlineInput('test> ', $this->testHistoryFile);
        $input->saveHistory();
        $this->assertFileExists($this->testHistoryFile);
    }

    public function test_load_history_when_file_exists(): void
    {
        // Create a history file first
        file_put_contents($this->testHistoryFile, "line1\nline2\n");
        $input = new ReadlineInput('test> ', $this->testHistoryFile);
        // If no exception is thrown, loadHistory worked
        $this->assertTrue(true);
    }

    public function test_load_history_when_file_does_not_exist(): void
    {
        $input = new ReadlineInput('test> ', $this->testHistoryFile);
        // Should not throw an error when file doesn't exist
        $this->assertTrue(true);
    }

    public function test_is_complete_statement_with_empty_string(): void
    {
        $input = new ReadlineInput('test> ', $this->testHistoryFile);
        $method = $this->getPrivateMethod($input, 'isCompleteStatement');
        $result = $method->invoke($input, '');
        $this->assertFalse($result);
    }

    public function test_is_complete_statement_with_whitespace(): void
    {
        $input = new ReadlineInput('test> ', $this->testHistoryFile);
        $method = $this->getPrivateMethod($input, 'isCompleteStatement');
        $result = $method->invoke($input, '   ');
        $this->assertFalse($result);
    }

    public function test_is_complete_statement_with_semicolon(): void
    {
        $input = new ReadlineInput('test> ', $this->testHistoryFile);
        $method = $this->getPrivateMethod($input, 'isCompleteStatement');
        $result = $method->invoke($input, '$x = 42;');
        $this->assertTrue($result);
    }

    public function test_is_complete_statement_with_unbalanced_braces(): void
    {
        $input = new ReadlineInput('test> ', $this->testHistoryFile);
        $method = $this->getPrivateMethod($input, 'isCompleteStatement');
        $result = $method->invoke($input, 'if (true) {');
        $this->assertFalse($result);
    }

    public function test_is_complete_statement_with_balanced_braces(): void
    {
        $input = new ReadlineInput('test> ', $this->testHistoryFile);
        $method = $this->getPrivateMethod($input, 'isCompleteStatement');
        $result = $method->invoke($input, 'if (true) { }');
        $this->assertTrue($result);
    }

    public function test_is_complete_statement_with_unbalanced_brackets(): void
    {
        $input = new ReadlineInput('test> ', $this->testHistoryFile);
        $method = $this->getPrivateMethod($input, 'isCompleteStatement');
        $result = $method->invoke($input, '$arr = [1, 2');
        $this->assertFalse($result);
    }

    public function test_is_complete_statement_with_balanced_brackets(): void
    {
        $input = new ReadlineInput('test> ', $this->testHistoryFile);
        $method = $this->getPrivateMethod($input, 'isCompleteStatement');
        $result = $method->invoke($input, '$arr = [1, 2];');
        $this->assertTrue($result);
    }

    public function test_is_complete_statement_with_unbalanced_parentheses(): void
    {
        $input = new ReadlineInput('test> ', $this->testHistoryFile);
        $method = $this->getPrivateMethod($input, 'isCompleteStatement');
        $result = $method->invoke($input, 'func(');
        $this->assertFalse($result);
    }

    public function test_is_complete_statement_with_balanced_parentheses(): void
    {
        $input = new ReadlineInput('test> ', $this->testHistoryFile);
        $method = $this->getPrivateMethod($input, 'isCompleteStatement');
        $result = $method->invoke($input, 'func();');
        $this->assertTrue($result);
    }

    public function test_is_complete_statement_with_if_keyword(): void
    {
        $input = new ReadlineInput('test> ', $this->testHistoryFile);
        $method = $this->getPrivateMethod($input, 'isCompleteStatement');
        $result = $method->invoke($input, 'if (true) { }');
        $this->assertTrue($result);
    }

    public function test_is_complete_statement_with_for_keyword(): void
    {
        $input = new ReadlineInput('test> ', $this->testHistoryFile);
        $method = $this->getPrivateMethod($input, 'isCompleteStatement');
        $result = $method->invoke($input, 'for ($i = 0; $i < 10; $i++) { }');
        $this->assertTrue($result);
    }

    public function test_is_complete_statement_with_function_keyword(): void
    {
        $input = new ReadlineInput('test> ', $this->testHistoryFile);
        $method = $this->getPrivateMethod($input, 'isCompleteStatement');
        $result = $method->invoke($input, 'function test() { }');
        $this->assertTrue($result);
    }

    public function test_is_complete_statement_with_class_keyword(): void
    {
        $input = new ReadlineInput('test> ', $this->testHistoryFile);
        $method = $this->getPrivateMethod($input, 'isCompleteStatement');
        $result = $method->invoke($input, 'class Test { }');
        $this->assertTrue($result);
    }

    public function test_is_complete_statement_with_namespace_keyword(): void
    {
        $input = new ReadlineInput('test> ', $this->testHistoryFile);
        $method = $this->getPrivateMethod($input, 'isCompleteStatement');
        $result = $method->invoke($input, 'namespace Test;');
        $this->assertTrue($result);
    }

    public function test_is_complete_statement_with_simple_expression(): void
    {
        $input = new ReadlineInput('test> ', $this->testHistoryFile);
        $method = $this->getPrivateMethod($input, 'isCompleteStatement');
        $result = $method->invoke($input, '42');
        $this->assertTrue($result);
    }

    public function test_is_complete_statement_with_complex_unbalanced(): void
    {
        $input = new ReadlineInput('test> ', $this->testHistoryFile);
        $method = $this->getPrivateMethod($input, 'isCompleteStatement');
        $code = 'if ($x > 0) { $result = "positive"; } else {';
        $result = $method->invoke($input, $code);
        $this->assertFalse($result);
    }

    public function test_is_complete_statement_with_nested_braces(): void
    {
        $input = new ReadlineInput('test> ', $this->testHistoryFile);
        $method = $this->getPrivateMethod($input, 'isCompleteStatement');
        $code = 'if (true) { if (true) { } }';
        $result = $method->invoke($input, $code);
        $this->assertTrue($result);
    }

    public function test_is_complete_statement_with_mixed_delimiters(): void
    {
        $input = new ReadlineInput('test> ', $this->testHistoryFile);
        $method = $this->getPrivateMethod($input, 'isCompleteStatement');
        $code = '$arr = [func($x)];';
        $result = $method->invoke($input, $code);
        $this->assertTrue($result);
    }

    /**
     * Helper method to get private methods for testing.
     */
    private function getPrivateMethod(object $object, string $methodName): ReflectionMethod
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }
}
