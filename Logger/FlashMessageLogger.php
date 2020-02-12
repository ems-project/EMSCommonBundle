<?php

namespace EMS\CommonBundle\Logger;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

class FlashMessageLogger extends AbstractProcessingHandler
{
    /** @var Session */
    private $session;

    /** @var TranslatorInterface */
    private $translator;

    /** @var string */
    private $translationDomain;

    public function __construct(Session $session, TranslatorInterface $translator, string $translationDomain)
    {
        parent::__construct(Logger::NOTICE);
        $this->session = $session;
        $this->translator = $translator;
        $this->translationDomain = $translationDomain;
    }


    protected function write(array $record): void
    {
        if ($record['level'] >= Logger::NOTICE) {
            $parameters = [];
            foreach ($record['context'] as $key => &$value) {
                $parameters['%' . $key . '%'] = $value;
            }
            //TODO: should be removed with symfony 4.2 (whene the core will be ready)
            //https://symfony.com/blog/new-in-symfony-4-2-intlmessageformatter
            //Lastly, the new formatter also deprecates the transChoice() method, which is replaced by the trans() method with a parameter called %count%.
            //https://symfony.com/doc/current/translation/message_format.html

            if (isset($record['context']['count']) && $record['context']['count']) {
                $message = $this->translator->transChoice($record['message'], $record['context']['count'], $parameters, $this->translationDomain);
            } else {
                $message = $this->translator->trans($record['message'], $parameters, $this->translationDomain);
            }

            $this->session->getFlashBag()->add(
                strtolower($record['level_name']),
                $message
            );
        }
    }
}
