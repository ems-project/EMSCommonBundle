<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Command;

use EMS\CommonBundle\Commands;
use EMS\CommonBundle\Common\Command\AbstractCommand;
use EMS\CommonBundle\Repository\LogRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ClearLogsCommand extends AbstractCommand
{
    private const OPTION_BEFORE = 'before';
    protected static $defaultName = Commands::CLEAR_LOGS;
    private LogRepository $logRepository;
    private \DateTime $before;

    public function __construct(LogRepository $logRepository)
    {
        parent::__construct();
        $this->logRepository = $logRepository;
    }

    protected function configure(): void
    {
        $this->setDescription('Clear doctrine logs');
        $this->addOption(self::OPTION_BEFORE, null, InputOption::VALUE_OPTIONAL, 'CLear logs older than the strtotime (-1day, -5min, now)', '-1week');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);

        $beforeOption = $input->getOption(self::OPTION_BEFORE);
        if (!\is_string($beforeOption)) {
            throw new \RuntimeException('Unexpected strtotime option');
        }
        if (($time = \strtotime($beforeOption)) === false) {
            throw new \RuntimeException('invalid time');
        }
        $before = new \DateTime();
        $before->setTimestamp($time);
        $this->before = $before;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->logRepository->clearLogs($this->before)) {
            $this->io->writeln('Doctrine\'s logs have been cleared');
        } else {
            $this->io->writeln('Doctrine\'s logs have not been cleared');
        }

        return parent::EXECUTE_SUCCESS;
    }
}
