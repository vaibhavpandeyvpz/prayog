<?php

declare(strict_types=1);

namespace Prayog;

/**
 * Configuration for the REPL.
 */
class Config
{
    public function __construct(
        public readonly string $prompt = 'prayog> ',
        public readonly ?string $historyFile = null,
        public readonly bool $colorOutput = true,
        public readonly ?string $welcomeMessage = null,
    ) {}

    public function getHistoryFile(): string
    {
        return $this->historyFile ?? $this->getDefaultHistoryFile();
    }

    private function getDefaultHistoryFile(): string
    {
        $home = getenv('HOME') ?: getenv('USERPROFILE') ?: '';

        return $home ? "$home/.prayog_history" : sys_get_temp_dir().'/.prayog_history';
    }
}
