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
                    // Ctrl+D pressed
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
                    echo $this->formatter->format($result)."\n";
                }
            } catch (\ParseError $e) {
                // Parse errors might indicate incomplete input, but we'll show it anyway
                $this->printError('Parse error', $e->getMessage());
            } catch (\Throwable $e) {
                $this->printError($e::class, $e->getMessage());
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
            echo $this->config->welcomeMessage."\n";

            return;
        }

        // Default welcome message
        echo "Prayog (प्रयोग) - PHP REPL\n";
        echo 'PHP '.PHP_VERSION."\n";
        echo "Type 'exit' or press Ctrl+D to quit.\n\n";
    }

    private function printError(string $type, string $message): void
    {
        $color = $this->config->colorOutput ? "\033[31m" : '';
        $reset = $this->config->colorOutput ? "\033[0m" : '';
        echo "{$color}Error ({$type}): {$message}{$reset}\n";
    }

    private function exit(): void
    {
        $this->input->saveHistory();
        echo "\nGoodbye!\n";
    }
}
