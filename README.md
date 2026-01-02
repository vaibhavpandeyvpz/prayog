# Prayog (प्रयोग)

[![Tests](https://github.com/vaibhavpandeyvpz/prayog/workflows/Tests/badge.svg)](https://github.com/vaibhavpandeyvpz/prayog/actions)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-777BB4.svg?logo=php)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Code Coverage](https://img.shields.io/badge/coverage-79%25-green.svg)](https://github.com/vaibhavpandeyvpz/prayog)

A dead simple, minimal REPL (Read-Evaluate-Print-Loop) for PHP 8.2+.

## Features

- 🎯 **Dead Simple** - Minimal codebase with clear, focused classes
- 🧩 **Modular** - Clean separation of concerns
- 🎨 **Modern** - Built with PHP 8.2+ features (typed properties, enums, match expressions)
- 🎨 **Colorful** - Syntax-highlighted output
- 📝 **History** - Command history support via readline
- 📄 **Multiline Support** - Automatic detection and explicit backslash continuation
- 🔧 **Extensible** - Easy to customize and extend

## Requirements

- PHP 8.2 or higher
- `ext-readline` extension

## Installation

Install via Composer:

```bash
composer require vaibhavpandeyvpz/prayog
```

Or add to your `composer.json`:

```json
{
    "require": {
        "vaibhavpandeyvpz/prayog": "^1.0"
    }
}
```

Then run:

```bash
composer install
```

## Usage

### Basic Usage

```bash
./bin/prayog
```

Or if installed via Composer:

```bash
vendor/bin/prayog
```

### Programmatic Usage

```php
<?php

use Prayog\Config;
use Prayog\Repl;

// Create a custom configuration
$config = new Config(
    prompt: 'myapp> ',
    historyFile: '/path/to/history',
    colorOutput: true,
    welcomeMessage: "Welcome to MyApp REPL!\nPHP " . PHP_VERSION,
);

// Create and start the REPL
$repl = new Repl($config);

// Optionally set initial variables
$repl->setVariable('app', $myApp);
$repl->setVariables(['user' => $user, 'db' => $db]);

// Start the REPL
$repl->start();
```

## Examples

```php
prayog> $x = 42
int(42)

prayog> $name = "Prayog"
string(6) "Prayog"

prayog> $numbers = [1, 2, 3, 4, 5]
array(5) [0 => 1, 1 => 2, 2 => 3, ...]

prayog> array_sum($numbers)
int(15)

prayog> class Calculator {
*>     public function add($a, $b) {
*>         return $a + $b;
*>     }
*> }

prayog> $calc = new Calculator()
object(Calculator)

prayog> $calc->add(10, 20)
int(30)

prayog> exit
```

## Multiline Input

Prayog supports multiline input in two ways:

1. **Automatic Detection** - The REPL automatically detects incomplete statements (unbalanced braces, brackets, parentheses) and continues reading:

```php
prayog> if ($x > 0) {
*>     echo "Positive";
*> }
```

2. **Backslash Continuation** - Use a backslash (`\`) at the end of a line to explicitly continue on the next line:

```php
prayog> $result = $a + \
*>     $b + \
*>     $c
int(15)
```

## Configuration

The `Config` class allows you to customize the REPL behavior:

- **`prompt`** (string, default: `'prayog> '`) - The prompt string displayed before each input
- **`historyFile`** (string|null, default: `null`) - Path to history file. If `null`, uses `~/.prayog_history`
- **`colorOutput`** (bool, default: `true`) - Enable/disable colored output
- **`welcomeMessage`** (string|null, default: `null`) - Custom welcome message. If `null`, shows default message

Example with custom welcome message:

```php
$config = new Config(
    prompt: 'myapp> ',
    welcomeMessage: "MyApp Interactive Shell\nVersion 1.0.0\n",
);
$repl = new Repl($config);
```

## Testing

The library includes comprehensive test coverage. To run the tests:

```bash
# Install development dependencies
composer install

# Run tests
vendor/bin/phpunit

# Run tests with coverage (requires xdebug)
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text
```

### Test Coverage

The test suite includes:

- **110 tests** covering all components
- **79% code coverage** (core logic at 100%)
- Tests for Config, Evaluator, Formatter, ReadlineInput, and Repl classes
- Integration tests and edge case handling

## Architecture

The library is organized into clean, focused modules:

- **`Repl`** - Main REPL class that orchestrates the read-eval-print loop
- **`Config`** - Configuration with sensible defaults
- **`Input\ReadlineInput`** - Handles readline input and history
- **`Evaluator`** - Evaluates PHP code and manages variable scope
- **`Output\Formatter`** - Formats output with syntax highlighting

All classes are designed to be extensible - you can extend any class to customize behavior.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

MIT License - see [LICENSE](LICENSE) file for details.
