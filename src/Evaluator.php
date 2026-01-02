<?php

declare(strict_types=1);

namespace Prayog;

use Prayog\Output\Formatter;

/**
 * Evaluates PHP code in the REPL context.
 */
class Evaluator
{
    private array $variables = [];

    public function __construct(
        private readonly Formatter $formatter,
    ) {}

    public function evaluate(string $code): mixed
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        // Handle special commands
        if ($code === 'exit' || $code === 'exit()' || $code === 'quit' || $code === 'quit()') {
            return new ExitSignal;
        }

        // Transform code to capture return value
        $wrappedCode = $this->wrapCode($code);

        try {
            // Extract variables into local scope
            extract($this->variables, EXTR_SKIP);

            // Get variables before eval
            $varsBefore = get_defined_vars();

            // Capture output
            ob_start();
            try {
                $result = eval($wrappedCode);
                $output = ob_get_clean();

                // Display captured output (ensure it ends with newline)
                if ($output !== '') {
                    echo $output;
                    // Ensure output ends with newline so prompt appears on next line
                    if (! str_ends_with($output, PHP_EOL)) {
                        echo PHP_EOL;
                    }
                    $this->flushOutput();
                }
            } catch (\Throwable $e) {
                // Discard any output captured before the error
                ob_end_clean();
                throw $e;
            }

            // Update variables from local scope
            $varsAfter = get_defined_vars();
            $this->updateVariables($varsBefore, $varsAfter);

            return $result;
        } catch (\Throwable $e) {
            // Re-throw all exceptions to be handled by Repl
            throw $e;
        }
    }

    public function setVariable(string $name, mixed $value): void
    {
        $this->variables[$name] = $value;
    }

    public function setVariables(array $variables): void
    {
        $this->variables = array_merge($this->variables, $variables);
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    private function wrapCode(string $code): string
    {
        // If code already has a return statement, use it as-is
        if (preg_match('/^\s*return\b/i', $code)) {
            return $code;
        }

        // If code ends with semicolon and is a simple expression, wrap it
        if (preg_match('/;\s*$/', $code)) {
            // Check if it's an expression (not a statement)
            $codeWithoutSemicolon = rtrim($code, ';');
            if ($this->isExpression($codeWithoutSemicolon)) {
                return 'return '.$codeWithoutSemicolon.';';
            }

            return $code;
        }

        // If it's an expression without semicolon, wrap it
        if ($this->isExpression($code)) {
            return 'return '.$code.';';
        }

        return $code;
    }

    private function flushOutput(): void
    {
        if (ob_get_level() === 0) {
            flush();
        }
    }

    private function isExpression(string $code): bool
    {
        $code = trim($code);

        // Simple heuristics: if it doesn't start with control structures, it's likely an expression
        return ! preg_match('/^\s*(if|else|elseif|for|foreach|while|do|switch|function|class|interface|trait|namespace|use|declare|return|echo|print|unset|isset|empty)\b/i', $code);
    }

    private function updateVariables(array $varsBefore, array $varsAfter): void
    {
        // Find new or changed variables
        $newVars = array_diff_key($varsAfter, $varsBefore);

        // Filter out internal/system variables
        $internalVars = [
            'GLOBALS', '_SERVER', '_GET', '_POST', '_FILES',
            '_COOKIE', '_SESSION', '_REQUEST', '_ENV',
            'varsBefore', 'varsAfter', 'wrappedCode', 'result', 'output',
        ];

        foreach ($newVars as $name => $value) {
            // Skip internal variables and variables starting with underscore (except user-defined ones we track)
            if (! in_array($name, $internalVars, true) &&
                (! str_starts_with($name, '_') || array_key_exists($name, $this->variables))) {
                $this->variables[$name] = $value;
            }
        }

        // Also update existing variables that might have changed
        foreach ($this->variables as $name => $oldValue) {
            if (isset($varsAfter[$name]) && $varsAfter[$name] !== $oldValue) {
                $this->variables[$name] = $varsAfter[$name];
            }
        }
    }
}

/**
 * Signal to exit the REPL.
 */
class ExitSignal {}
