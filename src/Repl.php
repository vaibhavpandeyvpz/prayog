<?php

declare(strict_types=1);

namespace Prayog;

use Prayog\Input\ReadlineInput;
use Prayog\Output\Formatter;

/**
 * Main REPL class - Read, Evaluate, Print, Loop.
 */
class Repl
{
    private readonly ReadlineInput $input;

    private readonly Evaluator $evaluator;

    private readonly Formatter $formatter;

    public function __construct(
        private readonly Config $config = new Config,
    ) {
        $this->formatter = new Formatter($config->colorOutput);
        $this->evaluator = new Evaluator($this->formatter);
        $this->input = new ReadlineInput(
            $config->prompt,
            $config->getHistoryFile(),
        );
    }

    /**
     * Start the REPL loop.
     */
    public function start(): void
    {
        $this->printWelcome();

        while (true) {
            try {
                $code = $this->input->read();

                if ($code === null) {
                    // Ctrl+D pressed - check if there's pending buffer content
                    if ($this->input->hasPendingBuffer()) {
                        $pendingCode = $this->input->getPendingBuffer();
                        // If there's pending code, try to evaluate it (will likely error)
                        try {
                            $this->evaluator->evaluate($pendingCode);
                        } catch (\Throwable $e) {
                            $this->handleError($e);
                        }
                    }

                    // Reset buffer and exit
                    $this->input->reset();
                    $this->exit();

                    return;
                }

                $result = $this->evaluator->evaluate($code);

                // Check for exit signal
                if ($result instanceof ExitSignal) {
                    $this->exit();

                    return;
                }

                // Print result if not null
                if ($result !== null) {
                    echo $this->formatter->format($result).PHP_EOL;
                    $this->flushOutput();
                }
            } catch (\Throwable $e) {
                $this->handleError($e);
            }
        }
    }

    /**
     * Set a variable in the REPL context.
     */
    public function setVariable(string $name, mixed $value): void
    {
        $this->evaluator->setVariable($name, $value);
    }

    /**
     * Set multiple variables in the REPL context.
     */
    public function setVariables(array $variables): void
    {
        $this->evaluator->setVariables($variables);
    }

    private function printWelcome(): void
    {
        if ($this->config->welcomeMessage !== null) {
            echo $this->config->welcomeMessage.PHP_EOL;

            return;
        }

        // Default welcome message
        echo 'Prayog (प्रयोग) - PHP REPL'.PHP_EOL;
        echo 'PHP '.PHP_VERSION.PHP_EOL;
        echo "Type 'exit' or press Ctrl+D to quit.".PHP_EOL.PHP_EOL;
    }

    private function printError(string $type, string $message): void
    {
        $color = $this->config->colorOutput ? "\033[31m" : '';
        $reset = $this->config->colorOutput ? "\033[0m" : '';
        // Error message already includes newline at the end
        echo "{$color}Error ({$type}): {$message}{$reset}".PHP_EOL;
    }

    private function exit(): void
    {
        $this->input->saveHistory();
        echo PHP_EOL.'Goodbye!'.PHP_EOL;
    }

    private function clearOutputBuffers(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    private function flushOutput(): void
    {
        if (ob_get_level() === 0) {
            flush();
        }
    }

    private function handleError(\Throwable $e): void
    {
        $this->input->reset();
        $this->clearOutputBuffers();
        $errorType = $e instanceof \ParseError ? 'Parse error' : $e::class;
        $this->printError($errorType, $e->getMessage());
        $this->flushOutput();
    }
}
