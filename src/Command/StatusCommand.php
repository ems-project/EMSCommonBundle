<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Command;

use EMS\CommonBundle\Service\ElasticaService;
use EMS\CommonBundle\Storage\StorageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class StatusCommand extends Command
{
    /** @var string  */
    const ARGUMENT_TIMEOUT = 'timeout';

    /** @var string  */
    const ARGUMENT_SILENT = 'silent';

    /** @var string  */
    const ARGUMENT_WAIT_FOR_STATUS = 'wait-for-status';

    /** @var ElasticaService */
    private $elasticaService;

    /** @var SymfonyStyle */
    private $io;

    /** @var StorageManager */
    private $storageManager;

    public function __construct(ElasticaService $elasticaService, StorageManager $storageManager)
    {
        parent::__construct();
        $this->elasticaService = $elasticaService;
        $this->storageManager = $storageManager;
    }

    protected function configure(): void
    {
        $this
            ->setName('ems:status')
            ->setDescription('Returns the health status of the elasticsearch cluster and of the different storage services.')
            ->addOption(
                self::ARGUMENT_SILENT,
                null,
                InputOption::VALUE_NONE,
                'Shows only warning and error messages'
            )
            ->addOption(
                self::ARGUMENT_WAIT_FOR_STATUS,
                null,
                InputOption::VALUE_OPTIONAL,
                'One of green, yellow or red. Will wait (until the timeout provided) until the status of the cluster changes to the one provided or better, i.e. green > yellow > red.',
                null
            )
            ->addOption(
                self::ARGUMENT_TIMEOUT,
                null,
                InputOption::VALUE_OPTIONAL,
                'Time units. Specifies the period of time to wait for a response. If no response is received before the timeout expires, the request will returns the status red.',
                '10s'
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $silent = $input->getOption(self::ARGUMENT_SILENT) === true;
        if (!$silent) {
            $this->io->section('Start health check');
        }

        $timeout = $input->getOption(self::ARGUMENT_TIMEOUT);
        if (!\is_string($timeout)) {
            throw new \RuntimeException('Unexpected timeout argument');
        }
        $waitForStatus = $input->getOption(self::ARGUMENT_WAIT_FOR_STATUS);
        if ($waitForStatus !== null && !\is_string($waitForStatus)) {
            throw new \RuntimeException('Unexpected wait-for-status argument');
        }

        $status = $this->elasticaService->getHealthStatus($waitForStatus, $timeout);
        $returnCode = 0;
        switch ($status) {
            case 'green':
                if (!$silent) {
                    $this->io->success('Cluster is healthy (green)');
                }
                break;
            case 'yellow':
                $returnCode -= 1;
                $this->io->warning('Replicas shard are not allocated (yellow)');
                break;
            default:
                $returnCode -= 2;
                $this->io->error('The cluster is not healthy (red)');
        }


        $healthyStorages = 0;
        $unhealthyStorages = 0;
        foreach ($this->storageManager->getHealthStatuses() as $name => $status) {
            if ($status) {
                if (!$silent) {
                    $this->io->success(\sprintf('Storage service %s is healthy', $name));
                }
                ++$healthyStorages;
            } else {
                $this->io->warning(\sprintf('Storage service %s is not healthy', $name));
                ++$unhealthyStorages;
            }
        }

        if ($unhealthyStorages === 0 && $healthyStorages === 0) {
            $this->io->warning('There is no storage services defined');
        } elseif ($healthyStorages === 0) {
            $this->io->error('All storage services are failing');
            $returnCode -= 2;
        }

        return $returnCode;
    }
}
