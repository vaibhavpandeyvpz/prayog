<?php

declare(strict_types=1);

namespace Prayog\Tests;

use PHPUnit\Framework\TestCase;
use Prayog\Config;

final class ConfigTest extends TestCase
{
    public function test_default_configuration(): void
    {
        $config = new Config;

        $this->assertSame('prayog> ', $config->prompt);
        $this->assertNull($config->historyFile);
        $this->assertTrue($config->colorOutput);
        $this->assertNull($config->welcomeMessage);
    }

    public function test_custom_prompt(): void
    {
        $config = new Config(prompt: 'custom> ');

        $this->assertSame('custom> ', $config->prompt);
    }

    public function test_custom_history_file(): void
    {
        $historyFile = '/tmp/test_history';
        $config = new Config(historyFile: $historyFile);

        $this->assertSame($historyFile, $config->historyFile);
        $this->assertSame($historyFile, $config->getHistoryFile());
    }

    public function test_color_output_disabled(): void
    {
        $config = new Config(colorOutput: false);

        $this->assertFalse($config->colorOutput);
    }

    public function test_custom_welcome_message(): void
    {
        $welcomeMessage = 'Welcome to Test REPL';
        $config = new Config(welcomeMessage: $welcomeMessage);

        $this->assertSame($welcomeMessage, $config->welcomeMessage);
    }

    public function test_get_history_file_with_null_uses_default(): void
    {
        $config = new Config(historyFile: null);
        $historyFile = $config->getHistoryFile();

        $this->assertIsString($historyFile);
        $this->assertNotEmpty($historyFile);
    }

    public function test_get_history_file_with_home_directory(): void
    {
        $originalHome = getenv('HOME');
        putenv('HOME=/test/home');

        try {
            $config = new Config(historyFile: null);
            $historyFile = $config->getHistoryFile();

            $this->assertSame('/test/home/.prayog_history', $historyFile);
        } finally {
            if ($originalHome !== false) {
                putenv("HOME=$originalHome");
            } else {
                putenv('HOME');
            }
        }
    }

    public function test_get_history_file_with_user_profile(): void
    {
        $originalHome = getenv('HOME');
        $originalUserProfile = getenv('USERPROFILE');
        putenv('HOME');
        putenv('USERPROFILE=/test/profile');

        try {
            $config = new Config(historyFile: null);
            $historyFile = $config->getHistoryFile();

            $this->assertSame('/test/profile/.prayog_history', $historyFile);
        } finally {
            if ($originalHome !== false) {
                putenv("HOME=$originalHome");
            } else {
                putenv('HOME');
            }
            if ($originalUserProfile !== false) {
                putenv("USERPROFILE=$originalUserProfile");
            } else {
                putenv('USERPROFILE');
            }
        }
    }

    public function test_get_history_file_falls_back_to_temp_dir(): void
    {
        $originalHome = getenv('HOME');
        $originalUserProfile = getenv('USERPROFILE');
        putenv('HOME');
        putenv('USERPROFILE');

        try {
            $config = new Config(historyFile: null);
            $historyFile = $config->getHistoryFile();

            $this->assertStringStartsWith(sys_get_temp_dir(), $historyFile);
            $this->assertStringEndsWith('.prayog_history', $historyFile);
        } finally {
            if ($originalHome !== false) {
                putenv("HOME=$originalHome");
            } else {
                putenv('HOME');
            }
            if ($originalUserProfile !== false) {
                putenv("USERPROFILE=$originalUserProfile");
            } else {
                putenv('USERPROFILE');
            }
        }
    }

    public function test_full_custom_configuration(): void
    {
        $config = new Config(
            prompt: 'test> ',
            historyFile: '/tmp/test_history',
            colorOutput: false,
            welcomeMessage: 'Test Welcome'
        );

        $this->assertSame('test> ', $config->prompt);
        $this->assertSame('/tmp/test_history', $config->historyFile);
        $this->assertFalse($config->colorOutput);
        $this->assertSame('Test Welcome', $config->welcomeMessage);
    }
}
