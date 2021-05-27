<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\Command;

use EMS\CommonBundle\Command\CommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

abstract class AbstractCommand extends Command implements CommandInterface
{
    protected SymfonyStyle $io;
    protected InputInterface $input;
    protected OutputInterface $output;
    protected ProcessHelper $processHelper;

    public function __construct()
    {
        parent::__construct();
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->input = $input;
        $this->output = $output;
        $this->processHelper = $this->getHelper('process');
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

    /**
     * Execute command in real php sub process.
     *
     * @param string[] $args
     */
    protected function executeCommand(string $command, array $args): int
    {
        $process = new Process(['php', 'bin/console', $command, ...$args]);
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $this->io->write(\implode(' ', [$command, ...$args]).': ');

        $this->processHelper->run($this->output, $process, 'Something went wrong!', function () {
            $this->io->write('*');
        });

        if ($process->isSuccessful()) {
            $this->io->write(' SUCCESS');
        } else {
            $this->io->write(' ERROR');
        }

        $this->io->newLine();

        return $process->getExitCode() ?? 0;
    }

    /**
     * Run command in same php process.
     *
     * @param array<string, mixed> $input
     */
    protected function runCommand(string $command, array $input): int
    {
        try {
            if (null === $application = $this->getApplication()) {
                throw new \RuntimeException('could not find application');
            }

            $cmd = $application->find($command);

            return $cmd->run(new ArrayInput($input), $this->output);
        } catch (\Throwable $e) {
            $this->io->error(\sprintf('Run command failed! (%s)', $e->getMessage()));

            return 0;
        }
    }
}
