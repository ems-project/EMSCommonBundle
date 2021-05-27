<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\Command;

use EMS\CommonBundle\Command\CommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractCommand extends Command implements CommandInterface
{
    protected SymfonyStyle $io;
    protected InputInterface $input;

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->input = $input;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return 1;
    }

    protected function getArgumentBool(string $name): bool
    {
        return \boolval($this->input->getArgument($name));
    }

    protected function getArgumentString(string $name): string
    {
        return \strval($this->input->getArgument($name));
    }

    protected function getArgumentInt(string $name): int
    {
        return \intval($this->input->getArgument($name));
    }

    protected function getOptionBool(string $name): bool
    {
        return true === $this->input->getOption($name);
    }

    protected function getOptionInt(string $name): int
    {
        return \intval($this->input->getOption($name));
    }

    protected function getOptionString(string $name): string
    {
        return \strval($this->input->getOption($name));
    }

    protected function getOptionStringNull(string $name): ?string
    {
        $option = $this->input->getOption($name);

        return $option ? $this->getOptionString($name) : null;
    }
}
