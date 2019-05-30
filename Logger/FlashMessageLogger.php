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


    protected function write(array $record)
    {
        if ($record['level'] >= Logger::NOTICE) {
            $parameters = [];
            foreach ($record['context'] as $key => &$value) {
                $parameters['%'.$key.'%'] = $value;
            }

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
