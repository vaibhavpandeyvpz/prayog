<?php

declare(strict_types=1);

namespace Prayog\Tests;

use PHPUnit\Framework\TestCase;
use Prayog\Evaluator;
use Prayog\ExitSignal;
use Prayog\Output\Formatter;

final class EvaluatorTest extends TestCase
{
    private Evaluator $evaluator;

    protected function setUp(): void
    {
        $this->evaluator = new Evaluator(new Formatter(colorize: false));
    }

    public function test_evaluate_empty_string(): void
    {
        $result = $this->evaluator->evaluate('');
        $this->assertNull($result);
    }

    public function test_evaluate_whitespace_only(): void
    {
        $result = $this->evaluator->evaluate('   ');
        $this->assertNull($result);
    }

    public function test_evaluate_exit_command(): void
    {
        $result = $this->evaluator->evaluate('exit');
        $this->assertInstanceOf(ExitSignal::class, $result);
    }

    public function test_evaluate_exit_with_parentheses(): void
    {
        $result = $this->evaluator->evaluate('exit()');
        $this->assertInstanceOf(ExitSignal::class, $result);
    }

    public function test_evaluate_quit_command(): void
    {
        $result = $this->evaluator->evaluate('quit');
        $this->assertInstanceOf(ExitSignal::class, $result);
    }

    public function test_evaluate_quit_with_parentheses(): void
    {
        $result = $this->evaluator->evaluate('quit()');
        $this->assertInstanceOf(ExitSignal::class, $result);
    }

    public function test_evaluate_simple_expression(): void
    {
        $result = $this->evaluator->evaluate('42');
        $this->assertSame(42, $result);
    }

    public function test_evaluate_expression_with_semicolon(): void
    {
        $result = $this->evaluator->evaluate('42;');
        $this->assertSame(42, $result);
    }

    public function test_evaluate_arithmetic_expression(): void
    {
        $result = $this->evaluator->evaluate('2 + 2');
        $this->assertSame(4, $result);
    }

    public function test_evaluate_string_expression(): void
    {
        $result = $this->evaluator->evaluate('"hello"');
        $this->assertSame('hello', $result);
    }

    public function test_evaluate_boolean_expression(): void
    {
        $result = $this->evaluator->evaluate('true');
        $this->assertTrue($result);
    }

    public function test_evaluate_array_expression(): void
    {
        $result = $this->evaluator->evaluate('[1, 2, 3]');
        $this->assertSame([1, 2, 3], $result);
    }

    public function test_evaluate_with_return_statement(): void
    {
        $result = $this->evaluator->evaluate('return 100;');
        $this->assertSame(100, $result);
    }

    public function test_evaluate_variable_assignment(): void
    {
        $this->evaluator->evaluate('$x = 42;');
        $variables = $this->evaluator->getVariables();
        $this->assertArrayHasKey('x', $variables);
        $this->assertSame(42, $variables['x']);
    }

    public function test_evaluate_variable_usage(): void
    {
        $this->evaluator->setVariable('x', 10);
        $result = $this->evaluator->evaluate('$x + 5');
        $this->assertSame(15, $result);
    }

    public function test_evaluate_variable_modification(): void
    {
        $this->evaluator->setVariable('x', 10);
        $this->evaluator->evaluate('$x = 20;');
        $variables = $this->evaluator->getVariables();
        $this->assertSame(20, $variables['x']);
    }

    public function test_evaluate_multiple_variables(): void
    {
        $this->evaluator->evaluate('$a = 1;');
        $this->evaluator->evaluate('$b = 2;');
        $this->evaluator->evaluate('$c = $a + $b;');
        $variables = $this->evaluator->getVariables();
        $this->assertSame(1, $variables['a']);
        $this->assertSame(2, $variables['b']);
        $this->assertSame(3, $variables['c']);
    }

    public function test_evaluate_statement_without_return(): void
    {
        ob_start();
        $result = $this->evaluator->evaluate('echo "test";');
        $output = ob_get_clean();
        // Output should end with newline
        $this->assertSame('test'.PHP_EOL, $output);
        $this->assertNull($result);
    }

    public function test_evaluate_function_call(): void
    {
        $result = $this->evaluator->evaluate('strlen("hello")');
        $this->assertSame(5, $result);
    }

    public function test_evaluate_class_definition(): void
    {
        $className = 'TestClass'.uniqid();
        $code = "class $className { public function test() { return \"ok\"; } }";
        $result = $this->evaluator->evaluate($code);
        $this->assertNull($result);
        $this->assertTrue(class_exists($className));
    }

    public function test_evaluate_class_instantiation(): void
    {
        $className = 'TestClass'.uniqid();
        $this->evaluator->evaluate("class $className {}");
        $result = $this->evaluator->evaluate("new $className()");
        $this->assertInstanceOf($className, $result);
    }

    public function test_evaluate_if_statement(): void
    {
        $code = 'if (true) { $testResult = "yes"; } else { $testResult = "no"; }';
        $result = $this->evaluator->evaluate($code);
        $variables = $this->evaluator->getVariables();
        $this->assertSame('yes', $variables['testResult']);
        // Statement doesn't return a value
        $this->assertNull($result);
    }

    public function test_evaluate_for_loop(): void
    {
        // Split into two statements to properly test variable tracking
        $this->evaluator->evaluate('$testSum = 0;');
        $code = 'for ($i = 1; $i <= 3; $i++) { $testSum += $i; }';
        $result = $this->evaluator->evaluate($code);
        $variables = $this->evaluator->getVariables();
        $this->assertSame(6, $variables['testSum']);
        // Statement doesn't return a value
        $this->assertNull($result);
    }

    public function test_evaluate_foreach_loop(): void
    {
        // Split into statements to properly test variable tracking
        $this->evaluator->evaluate('$testArr = [1, 2, 3];');
        $this->evaluator->evaluate('$testSum = 0;');
        $code = 'foreach ($testArr as $val) { $testSum += $val; }';
        $result = $this->evaluator->evaluate($code);
        $variables = $this->evaluator->getVariables();
        $this->assertSame(6, $variables['testSum']);
        // Statement doesn't return a value
        $this->assertNull($result);
    }

    public function test_evaluate_parse_error(): void
    {
        ob_start();
        try {
            $this->evaluator->evaluate('$x = ;');
        } catch (\ParseError $e) {
            ob_end_clean();
            $this->assertInstanceOf(\ParseError::class, $e);

            return;
        }
        ob_end_clean();
        $this->fail('Expected ParseError was not thrown');
    }

    public function test_evaluate_runtime_error(): void
    {
        ob_start();
        try {
            $this->evaluator->evaluate('undefined_function();');
        } catch (\Throwable $e) {
            ob_end_clean();
            $this->assertInstanceOf(\Throwable::class, $e);

            return;
        }
        ob_end_clean();
        $this->fail('Expected Throwable was not thrown');
    }

    public function test_set_variable(): void
    {
        $this->evaluator->setVariable('test', 'value');
        $variables = $this->evaluator->getVariables();
        $this->assertSame('value', $variables['test']);
    }

    public function test_set_variables(): void
    {
        $this->evaluator->setVariables(['a' => 1, 'b' => 2]);
        $variables = $this->evaluator->getVariables();
        $this->assertSame(1, $variables['a']);
        $this->assertSame(2, $variables['b']);
    }

    public function test_set_variables_merges(): void
    {
        $this->evaluator->setVariable('a', 1);
        $this->evaluator->setVariables(['b' => 2, 'c' => 3]);
        $variables = $this->evaluator->getVariables();
        $this->assertSame(1, $variables['a']);
        $this->assertSame(2, $variables['b']);
        $this->assertSame(3, $variables['c']);
    }

    public function test_get_variables(): void
    {
        $this->evaluator->setVariable('x', 10);
        $variables = $this->evaluator->getVariables();
        $this->assertIsArray($variables);
        $this->assertArrayHasKey('x', $variables);
    }

    public function test_evaluate_expression_with_echo(): void
    {
        ob_start();
        $result = $this->evaluator->evaluate('echo "output";');
        $output = ob_get_clean();
        // Output should end with newline
        $this->assertSame('output'.PHP_EOL, $output);
        $this->assertNull($result);
    }

    public function test_evaluate_expression_with_print(): void
    {
        ob_start();
        $result = $this->evaluator->evaluate('print "output";');
        $output = ob_get_clean();
        // Output should end with newline
        $this->assertSame('output'.PHP_EOL, $output);
        // Note: print is a statement, not wrapped with return, so result is null
        $this->assertNull($result);
    }

    public function test_evaluate_complex_expression(): void
    {
        $result = $this->evaluator->evaluate('(2 + 3) * 4');
        $this->assertSame(20, $result);
    }

    public function test_evaluate_string_concatenation(): void
    {
        $result = $this->evaluator->evaluate('"hello" . " " . "world"');
        $this->assertSame('hello world', $result);
    }

    public function test_evaluate_array_access(): void
    {
        $this->evaluator->evaluate('$arr = [1, 2, 3];');
        $result = $this->evaluator->evaluate('$arr[0]');
        $this->assertSame(1, $result);
    }

    public function test_evaluate_array_modification(): void
    {
        $this->evaluator->evaluate('$arr = [1, 2];');
        $this->evaluator->evaluate('$arr[] = 3;');
        $variables = $this->evaluator->getVariables();
        $this->assertSame([1, 2, 3], $variables['arr']);
    }

    public function test_evaluate_with_control_structure_keywords(): void
    {
        // Test that statements starting with control keywords are not wrapped with return
        // But if they contain a return, that return value is used
        $code = 'if (true) { return 1; }';
        $result = $this->evaluator->evaluate($code);
        $this->assertSame(1, $result); // The return statement inside executes
    }

    public function test_evaluate_namespace_declaration(): void
    {
        $code = 'namespace Test\Namespace;';
        $result = $this->evaluator->evaluate($code);
        $this->assertNull($result);
    }

    public function test_evaluate_use_statement(): void
    {
        $code = 'use stdClass;';
        $result = $this->evaluator->evaluate($code);
        $this->assertNull($result);
    }

    public function test_evaluate_variable_with_underscore(): void
    {
        // Variables starting with underscore should be tracked if explicitly set
        $this->evaluator->setVariable('_test', 'value');
        $this->evaluator->evaluate('$_test = "new";');
        $variables = $this->evaluator->getVariables();
        $this->assertSame('new', $variables['_test']);
    }

    public function test_evaluate_internal_variables_not_tracked(): void
    {
        // Internal variables should not be tracked
        $this->evaluator->evaluate('$GLOBALS["test"] = "value";');
        $variables = $this->evaluator->getVariables();
        $this->assertArrayNotHasKey('GLOBALS', $variables);
    }
}
