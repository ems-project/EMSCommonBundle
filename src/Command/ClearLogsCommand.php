<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Command;

use EMS\CommonBundle\Commands;
use EMS\CommonBundle\Common\Command\AbstractCommand;
use EMS\CommonBundle\Repository\LogRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearLogsCommand extends AbstractCommand
{
    protected static $defaultName = Commands::CLEAR_LOGS;
    private LogRepository $logRepository;

    public function __construct(LogRepository $logRepository)
    {
        parent::__construct();
        $this->logRepository = $logRepository;
    }

    protected function configure(): void
    {
        $this->setDescription('Clear doctrine logs');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->logRepository->clear()) {
            $this->io->writeln('Doctrine\'s logs have been cleared');
        } else {
            $this->io->writeln('Doctrine\'s logs have not been cleared');
        }

        return parent::EXECUTE_SUCCESS;
    }
}
