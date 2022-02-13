<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Command\Admin;

use EMS\CommonBundle\Common\Admin\AdminHelper;
use EMS\CommonBundle\Common\Command\AbstractCommand;
use EMS\CommonBundle\Contracts\CoreApi\CoreApiInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class JobStatusCommand extends AbstractCommand
{
    public const JOB_ID = 'job-id';
    private AdminHelper $adminHelper;
    private CoreApiInterface $coreApi;
    private string $jobId;

    public function __construct(AdminHelper $adminHelper)
    {
        parent::__construct();
        $this->adminHelper = $adminHelper;
    }

    public function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->jobId = $this->getArgumentString(self::JOB_ID);
    }

    protected function configure(): void
    {
        parent::configure();
        $this->addArgument(self::JOB_ID, InputArgument::REQUIRED, \sprintf('Job\'s ID'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->coreApi = $this->adminHelper->getCoreApi();
        $this->io->title('Admin - Job\'s status');
        $this->io->section(\sprintf('Getting information about Job #%s', $this->jobId));

        if (!$this->coreApi->isAuthenticated()) {
            $this->io->error(\sprintf('Not authenticated for %s, run emsch:local:login', $this->coreApi->getBaseUrl()));

            return self::EXECUTE_ERROR;
        }
        $status = $this->coreApi->admin()->getJobStatus($this->jobId);
        if (!$status['done']) {
            $this->echoStatus($status);
        }
        if (!$status['started']) {
            $this->coreApi->admin()->startJob($this->jobId);
        }
        $this->io->section('Job\'s output:');
        $this->writeOutput($status);

        $this->io->section('Job\'s status:');
        $this->echoStatus($status);

        return self::EXECUTE_SUCCESS;
    }

    /**
     * @param array{id: string, created: string, modified: string, command: string, user: string, started: bool, done: bool, output: ?string} $status
     */
    private function echoStatus(array $status): void
    {
        $this->io->horizontalTable([
            'ID',
            'Created',
            'Modified',
            'Command',
            'User',
            'Started',
            'Done',
        ], [[
            $status['id'],
            $status['created'],
            $status['modified'],
            $status['command'],
            $status['user'],
            $status['started'] ? 'true' : 'false',
            $status['done'] ? 'true' : 'false',
        ]]);
    }

    /**
     * @param array{id: string, created: string, modified: string, command: string, user: string, started: bool, done: bool, output: ?string} $status
     */
    private function writeOutput($status): void
    {
        $currentLine = 0;
        while (true) {
            if (\strlen($status['output'] ?? '') > 0) {
                $counter = 0;
                $lines = \preg_split("/((\r?\n)|(\r\n?))/", $status['output'] ?? '');
                if (false === $lines) {
                    throw new \RuntimeException('Unexpected false split lines');
                }
                foreach ($lines as $line) {
                    if ($counter++ < $currentLine) {
                        continue;
                    }
                    $currentLine = $counter;
                    $this->io->writeln(\sprintf("<fg=yellow>></>\t%s", $line));
                }
            }
            if ($status['done'] ?? false) {
                break;
            }
            \sleep(1);
            $status = $this->coreApi->admin()->getJobStatus($this->jobId);
        }
    }
}
