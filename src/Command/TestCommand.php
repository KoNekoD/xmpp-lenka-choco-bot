<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:test')]
class TestCommand
    extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $unicode_string = "\u0430 \u0435\u0441\u043b\u0438 \u0442\u0443\u0442?";

        $utf8_string = json_decode('"'.$unicode_string.'"');

        $output->writeln($utf8_string);

        return 0;
    }
}
