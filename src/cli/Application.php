<?php

namespace App\Cli;

use App\Cli\Command\BuildCommand;
use App\Cli\Command\CaptchaCleanupCommand;
use App\Cli\Command\CvBuildCommand;
use App\Cli\Command\CvUploadCommand;
use App\Cli\Command\EnvExportCommand;
use App\Cli\Command\EnvGetCommand;
use App\Cli\Command\RunCommand;
use App\Cli\Command\SetupCommand;
use App\Cli\Command\TokenRotateCommand;
use Symfony\Component\Console\Application as SymfonyApplication;

final class Application extends SymfonyApplication
{
    public function __construct()
    {
        parent::__construct('lebenslauf-cli', '1.0.0');
        $this->registerCommands();
    }

    private function registerCommands(): void
    {
        $this->add(new SetupCommand());
        $this->add(new BuildCommand());
        $this->add(new RunCommand());
        $this->add(new CvBuildCommand());
        $this->add(new CvUploadCommand());
        $this->add(new TokenRotateCommand());
        $this->add(new CaptchaCleanupCommand());
        $this->add(new EnvGetCommand());
        $this->add(new EnvExportCommand());
    }
}
