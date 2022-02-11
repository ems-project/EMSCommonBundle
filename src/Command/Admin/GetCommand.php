<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Command\Admin;

use EMS\CommonBundle\Common\Admin\AdminHelper;
use EMS\CommonBundle\Common\Admin\ConfigHelper;
use EMS\CommonBundle\Common\Command\AbstractCommand;
use EMS\CommonBundle\Contracts\CoreApi\CoreApiInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GetCommand extends AbstractCommand
{
    public const CONFIG_TYPE = 'config-type';
    public const EXPORT = 'export';
    public const FOLDER = 'folder';
    private AdminHelper $adminHelper;
    private string $configType;
    private bool $export;
    private string $folder = 'admin';
    private CoreApiInterface $coreApi;

    public function __construct(AdminHelper $adminHelper, string $projectFolder)
    {
        parent::__construct();
        $this->adminHelper = $adminHelper;
        $this->folder = $projectFolder.DIRECTORY_SEPARATOR.$this->folder;
    }

    public function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->configType = $this->getArgumentString(self::CONFIG_TYPE);
        $this->export = $this->getOptionBool(self::EXPORT);
        $this->folder = $this->getOptionString(self::FOLDER);
        $this->coreApi = $this->adminHelper->getCoreApi();
    }

    protected function configure(): void
    {
        parent::configure();
        $this->addArgument(self::CONFIG_TYPE, InputArgument::REQUIRED, \sprintf('Type of configs to get'));
        $this->addOption(self::EXPORT, null, InputOption::VALUE_NONE, 'Export configs in JSON files');
        $this->addOption(self::FOLDER, null, InputOption::VALUE_OPTIONAL, 'Export folder', $this->folder);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Admin - get');
        $this->io->section(\sprintf('Getting %s\'s configurations from %s', $this->configType, $this->coreApi->getBaseUrl()));

        if (!$this->coreApi->isAuthenticated()) {
            $this->io->error(\sprintf('Not authenticated for %s, run emsch:local:login', $this->coreApi->getBaseUrl()));

            return self::EXECUTE_ERROR;
        }
        $configApi = $this->coreApi->admin()->getConfig($this->configType);
        $configHelper = new ConfigHelper($configApi, $this->folder);

        if ($this->export) {
            $configHelper->update();
        }

        $rows = [];
        foreach ($configApi->index() as $key => $name) {
            $rows[] = [$key, $name];
        }

        $this->io->table(['#', 'Name'], $rows);

        return self::EXECUTE_SUCCESS;
    }
}
