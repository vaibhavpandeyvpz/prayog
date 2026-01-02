<?php

declare(strict_types=1);

namespace Prayog\Tests;

use PHPUnit\Framework\TestCase;
use Prayog\ExitSignal;

final class ExitSignalTest extends TestCase
{
    public function test_exit_signal_instantiation(): void
    {
        $signal = new ExitSignal;
        $this->assertInstanceOf(ExitSignal::class, $signal);
    }
}
