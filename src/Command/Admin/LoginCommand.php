<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Command\Admin;

use EMS\CommonBundle\Common\Admin\AdminHelper;
use EMS\CommonBundle\Common\Command\AbstractCommand;
use EMS\CommonBundle\Contracts\CoreApi\Exception\NotAuthenticatedExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class LoginCommand extends AbstractCommand
{
    private const ARG_BASE_URL = 'base-url';
    private const ARG_USERNAME = 'username';
    private const ARG_PASSWORD = 'password';
    private AdminHelper $adminHelper;

    public function __construct(AdminHelper $adminHelper)
    {
        parent::__construct();
        $this->adminHelper = $adminHelper;
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->addArgument(self::ARG_BASE_URL, InputArgument::REQUIRED, 'Elasticms\'s base url')
            ->addArgument(self::ARG_USERNAME, InputArgument::OPTIONAL, 'username')
            ->addArgument(self::ARG_PASSWORD, InputArgument::OPTIONAL, 'password')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (null === $input->getArgument(self::ARG_USERNAME)) {
            $input->setArgument(self::ARG_USERNAME, $this->io->askQuestion(new Question('Username')));
        }

        if (null === $input->getArgument(self::ARG_PASSWORD)) {
            $input->setArgument(self::ARG_PASSWORD, $this->io->askHidden('Password'));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Admin - login');

        try {
            $coreApi = $this->adminHelper->login(
                $this->getArgumentString(self::ARG_BASE_URL),
                $this->getArgumentString(self::ARG_USERNAME),
                $this->getArgumentString(self::ARG_PASSWORD)
            );
        } catch (NotAuthenticatedExceptionInterface $e) {
            $this->io->error('Invalid credentials!');

            return self::EXECUTE_ERROR;
        } catch (\Throwable $e) {
            $this->io->error($e->getMessage());

            return self::EXECUTE_ERROR;
        }
        $profile = $coreApi->user()->getProfileAuthenticated();
        $this->io->success(\sprintf('Welcome %s on %s', $profile->getUsername(), $this->adminHelper->getCoreApi()->getBaseUrl()));

        return self::EXECUTE_SUCCESS;
    }
}
