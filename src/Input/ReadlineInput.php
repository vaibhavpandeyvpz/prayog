<?php

declare(strict_types=1);

namespace Prayog\Input;

/**
 * Handles readline input for the REPL.
 */
class ReadlineInput
{
    private string $buffer = '';

    private int $lineNumber = 1;

    public function __construct(
        private readonly string $prompt,
        private readonly string $historyFile,
    ) {
        $this->loadHistory();
    }

    public function read(): ?string
    {
        $continuationPrompt = str_pad('*> ', strlen($this->prompt), ' ', STR_PAD_LEFT);
        $currentPrompt = $this->buffer === '' ? $this->prompt : $continuationPrompt;

        $line = readline(sprintf('[%d] %s', $this->lineNumber, $currentPrompt));

        if ($line === false) {
            // Ctrl+D pressed
            return null;
        }

        if ($line !== '') {
            readline_add_history($line);
        }

        // Check for backslash continuation
        $trimmedLine = rtrim($line);
        $hasBackslashContinuation = $trimmedLine !== '' && $trimmedLine[-1] === '\\';

        if ($hasBackslashContinuation) {
            // Remove the backslash and add the line (without newline yet)
            $this->buffer .= substr($trimmedLine, 0, -1);

            // Continue reading next line
            return $this->read();
        }

        $this->buffer .= $line.PHP_EOL;

        if ($this->isCompleteStatement($this->buffer)) {
            $code = $this->buffer;
            $this->buffer = '';
            $this->lineNumber++;

            return $code;
        }

        return $this->read();
    }

    public function saveHistory(): void
    {
        readline_write_history($this->historyFile);
    }

    public function reset(): void
    {
        $this->buffer = '';
    }

    public function hasPendingBuffer(): bool
    {
        return $this->buffer !== '';
    }

    public function getPendingBuffer(): string
    {
        return $this->buffer;
    }

    private function loadHistory(): void
    {
        if (file_exists($this->historyFile)) {
            readline_read_history($this->historyFile);
        }
    }

    private function isCompleteStatement(string $code): bool
    {
        $code = trim($code);
        if ($code === '') {
            return false;
        }

        // Check for balanced braces, brackets, and parentheses
        $openBraces = substr_count($code, '{') - substr_count($code, '}');
        $openBrackets = substr_count($code, '[') - substr_count($code, ']');
        $openParens = substr_count($code, '(') - substr_count($code, ')');

        // If braces/brackets/parens are unbalanced, definitely incomplete
        if ($openBraces !== 0 || $openBrackets !== 0 || $openParens !== 0) {
            return false;
        }

        // Check if ends with semicolon (simple statement)
        if (preg_match('/;\s*$/', $code)) {
            return true;
        }

        // Check for control structures that might not need semicolons
        // but need balanced braces
        if (preg_match('/\b(if|else|elseif|for|foreach|while|do|switch|function|class|interface|trait|namespace|use|declare)\b/', $code)) {
            // If it has these keywords and braces are balanced, likely complete
            return true;
        }

        // Single expression without semicolon - likely complete
        // (will be handled by evaluator)
        return true;
    }
}
