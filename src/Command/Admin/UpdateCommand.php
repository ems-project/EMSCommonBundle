<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Command\Admin;

use EMS\CommonBundle\Common\Admin\AdminHelper;
use EMS\CommonBundle\Common\Command\AbstractCommand;
use EMS\CommonBundle\Common\Standard\Json;
use EMS\CommonBundle\Contracts\CoreApi\Endpoint\Admin\ConfigInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends AbstractCommand
{
    public const CONFIG_TYPE = 'config-type';
    public const ENTITY_NAME = 'entity-name';
    public const JSON_PATH = 'json-path';
    private string $configType;
    private ConfigInterface $config;
    private string $entityName;
    private string $jsonPath;
    private AdminHelper $adminHelper;

    public function __construct(AdminHelper $adminHelper)
    {
        parent::__construct();
        $this->adminHelper = $adminHelper;
    }

    public function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->configType = $this->getArgumentString(self::CONFIG_TYPE);
        $this->entityName = $this->getArgumentString(self::ENTITY_NAME);
        $this->jsonPath = $this->getArgumentString(self::JSON_PATH);
        $this->config = $this->adminHelper->getCoreApi()->admin()->getConfig($this->configType);
    }

    protected function configure(): void
    {
        parent::configure();
        $this->addArgument(self::CONFIG_TYPE, InputArgument::REQUIRED, 'Type of config to update');
        $this->addArgument(self::ENTITY_NAME, InputArgument::REQUIRED, 'Entity\'s name to update');
        $this->addArgument(self::JSON_PATH, InputArgument::REQUIRED, 'Path to the JSON file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Admin - update');
        $this->io->section(\sprintf('Updating %s:%s configuration to %s', $this->configType, $this->entityName, $this->adminHelper->getCoreApi()->getBaseUrl()));
        if (!$this->adminHelper->getCoreApi()->isAuthenticated()) {
            $this->io->error(\sprintf('Not authenticated for %s, run ems:admin:login', $this->adminHelper->getCoreApi()->getBaseUrl()));

            return self::EXECUTE_ERROR;
        }
        $fileContent = \file_get_contents($this->jsonPath);
        if (!\is_string($fileContent)) {
            throw new \RuntimeException('Unexpected non string file content');
        }
        $this->config->update($this->entityName, Json::decode($fileContent));

        return self::EXECUTE_SUCCESS;
    }
}
