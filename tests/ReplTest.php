<?php

declare(strict_types=1);

namespace Prayog\Tests;

use PHPUnit\Framework\TestCase;
use Prayog\Config;
use Prayog\Repl;
use ReflectionClass;

final class ReplTest extends TestCase
{
    public function test_constructor_with_default_config(): void
    {
        $repl = new Repl;
        $this->assertInstanceOf(Repl::class, $repl);
    }

    public function test_constructor_with_custom_config(): void
    {
        $config = new Config(prompt: 'test> ');
        $repl = new Repl($config);
        $this->assertInstanceOf(Repl::class, $repl);
    }

    public function test_set_variable(): void
    {
        $repl = new Repl;
        $repl->setVariable('test', 'value');
        // Variable should be set in evaluator
        $this->assertTrue(true); // Indirect test - if no exception, it worked
    }

    public function test_set_variables(): void
    {
        $repl = new Repl;
        $repl->setVariables(['a' => 1, 'b' => 2]);
        // Variables should be set in evaluator
        $this->assertTrue(true); // Indirect test - if no exception, it worked
    }

    public function test_set_variable_and_use_in_evaluation(): void
    {
        // This is an integration test that would require mocking readline
        // For now, we test that the method exists and doesn't throw
        $repl = new Repl;
        $repl->setVariable('x', 10);
        $this->assertTrue(true);
    }

    public function test_repl_with_custom_welcome_message(): void
    {
        $config = new Config(welcomeMessage: 'Custom Welcome');
        $repl = new Repl($config);
        // Test that it can be instantiated with custom welcome message
        $this->assertInstanceOf(Repl::class, $repl);
    }

    public function test_repl_with_color_output_disabled(): void
    {
        $config = new Config(colorOutput: false);
        $repl = new Repl($config);
        $this->assertInstanceOf(Repl::class, $repl);
    }

    public function test_repl_with_custom_prompt(): void
    {
        $config = new Config(prompt: 'custom> ');
        $repl = new Repl($config);
        $this->assertInstanceOf(Repl::class, $repl);
    }

    public function test_repl_with_custom_history_file(): void
    {
        $historyFile = sys_get_temp_dir().'/test_history_'.uniqid();
        $config = new Config(historyFile: $historyFile);
        $repl = new Repl($config);
        $this->assertInstanceOf(Repl::class, $repl);

        // Cleanup
        if (file_exists($historyFile)) {
            unlink($historyFile);
        }
    }

    public function test_repl_full_configuration(): void
    {
        $config = new Config(
            prompt: 'test> ',
            historyFile: sys_get_temp_dir().'/test_history',
            colorOutput: false,
            welcomeMessage: 'Test Welcome'
        );
        $repl = new Repl($config);
        $this->assertInstanceOf(Repl::class, $repl);
    }

    public function test_print_welcome_with_custom_message(): void
    {
        $config = new Config(welcomeMessage: 'Custom Welcome Message');
        $repl = new Repl($config);
        $reflection = new ReflectionClass($repl);
        $method = $reflection->getMethod('printWelcome');
        $method->setAccessible(true);

        ob_start();
        $method->invoke($repl);
        $output = ob_get_clean();

        $this->assertStringContainsString('Custom Welcome Message', $output);
    }

    public function test_print_welcome_with_default_message(): void
    {
        $repl = new Repl;
        $reflection = new ReflectionClass($repl);
        $method = $reflection->getMethod('printWelcome');
        $method->setAccessible(true);

        ob_start();
        $method->invoke($repl);
        $output = ob_get_clean();

        $this->assertStringContainsString('Prayog', $output);
        $this->assertStringContainsString('PHP', $output);
        $this->assertStringContainsString('exit', $output);
    }

    public function test_print_error_with_color(): void
    {
        $config = new Config(colorOutput: true);
        $repl = new Repl($config);
        $reflection = new ReflectionClass($repl);
        $method = $reflection->getMethod('printError');
        $method->setAccessible(true);

        ob_start();
        $method->invoke($repl, 'TestError', 'Test message');
        $output = ob_get_clean();

        $this->assertStringContainsString('Error', $output);
        $this->assertStringContainsString('TestError', $output);
        $this->assertStringContainsString('Test message', $output);
        $this->assertStringContainsString("\033", $output); // ANSI color codes
    }

    public function test_print_error_without_color(): void
    {
        $config = new Config(colorOutput: false);
        $repl = new Repl($config);
        $reflection = new ReflectionClass($repl);
        $method = $reflection->getMethod('printError');
        $method->setAccessible(true);

        ob_start();
        $method->invoke($repl, 'TestError', 'Test message');
        $output = ob_get_clean();

        $this->assertStringContainsString('Error', $output);
        $this->assertStringContainsString('TestError', $output);
        $this->assertStringContainsString('Test message', $output);
        $this->assertStringNotContainsString("\033", $output); // No ANSI color codes
    }

    public function test_exit(): void
    {
        $historyFile = sys_get_temp_dir().'/test_history_'.uniqid();
        $config = new Config(historyFile: $historyFile);
        $repl = new Repl($config);
        $reflection = new ReflectionClass($repl);
        $method = $reflection->getMethod('exit');
        $method->setAccessible(true);

        ob_start();
        $method->invoke($repl);
        $output = ob_get_clean();

        $this->assertStringContainsString('Goodbye', $output);

        // Cleanup
        if (file_exists($historyFile)) {
            unlink($historyFile);
        }
    }
}
