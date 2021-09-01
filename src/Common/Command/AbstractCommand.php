<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\Command;

use EMS\CommonBundle\Command\CommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
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
        return self::EXECUTE_SUCCESS;
    }

    /**
     * @param array<mixed> $choices
     *
     * @return mixed
     */
    protected function askChoiceQuestion(string $question, array $choices)
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion($question, $choices);
        $question->setErrorMessage('Choice %s is invalid.');

        return $helper->ask($this->input, $this->output, $question);
    }

    protected function getArgumentBool(string $name): bool
    {
        if (null === $arg = $this->input->getArgument($name)) {
            throw new \RuntimeException(\sprintf('Missing argument "%s"', $name));
        }

        return \boolval($arg);
    }

    protected function getArgumentString(string $name): string
    {
        if (null === $arg = $this->input->getArgument($name)) {
            throw new \RuntimeException(\sprintf('Missing argument "%s"', $name));
        }

        return \strval($arg);
    }

    /**
     * @param string[] $choices
     */
    protected function choiceArgumentString(string $name, string $question, array $choices): void
    {
        if (null !== $this->input->getArgument($name)) {
            return;
        }

        $this->input->setArgument($name, $this->askChoiceQuestion($question, $choices));
    }

    protected function getArgumentInt(string $name): int
    {
        if (null === $arg = $this->input->getArgument($name)) {
            throw new \RuntimeException(\sprintf('Missing argument "%s"', $name));
        }

        return \intval($arg);
    }

    protected function getOptionBool(string $name): bool
    {
        return true === $this->input->getOption($name);
    }

    protected function getOptionInt(string $name, ?int $default = null): int
    {
        if (null !== $option = $this->input->getOption($name)) {
            return \intval($this->input->getOption($name));
        }

        if ($default) {
            return $default;
        }

        throw new \RuntimeException(\sprintf('Missing option "%s"', $option));
    }

    protected function getOptionIntNull(string $name): ?int
    {
        $option = $this->input->getOption($name);

        return $option ? $this->getOptionInt($name) : null;
    }

    protected function getOptionString(string $name, ?string $default = null): string
    {
        if (null !== $option = $this->input->getOption($name)) {
            return \strval($this->input->getOption($name));
        }

        if ($default) {
            return $default;
        }

        throw new \RuntimeException(\sprintf('Missing option "%s"', $option));
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
        $emsProcessCommand = $_SERVER['EMS_PROCESS_COMMAND'] ?? 'php bin/console';
        $processCommand = \array_merge(\explode(' ', $emsProcessCommand), [$command, ...$args]);

        $process = new Process($processCommand);
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $this->io->write(\implode(' ', [$command, ...$args]).': ');

        $this->processHelper->run($this->output, $process, 'Something went wrong!', function () {
            $this->io->write('*');
        });

        if ($process->isSuccessful()) {
            $this->io->write(' <fg=green>SUCCESS</>');
            $this->io->newLine();

            return 0;
        }

        throw new \RuntimeException($process->getErrorOutput());
    }

    /**
     * Run command in same php process.
     *
     * @param array<string, mixed> $args
     * @param array<string, mixed> $options
     */
    protected function runCommand(string $command, array $args = [], array $options = []): int
    {
        try {
            if (null === $application = $this->getApplication()) {
                throw new \RuntimeException('could not find application');
            }

            $cmd = $application->find($command);
            $input = $args;
            foreach ($options as $name => $value) {
                $input['--'.$name] = $value;
            }

            return $cmd->run(new ArrayInput($input), $this->output);
        } catch (\Throwable $e) {
            $this->io->error(\sprintf('Run command failed! (%s)', $e->getMessage()));

            return 0;
        }
    }
}
