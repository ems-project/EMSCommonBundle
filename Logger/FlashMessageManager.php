<?php


namespace EMS\CommonBundle\Logger;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

class FlashMessageManager extends AbstractProcessingHandler
{
    /** @var Session */
    private $session;

    /** @var TranslatorInterface */
    private $translator;

    /** @var string */
    private $translationDomain;

    public function __construct(Session $session, TranslatorInterface $translator, string $translationDomain)
    {
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
            $this->session->getFlashBag()->add(
                strtolower($record['level_name']),
                $this->translator->trans($record['message'], $parameters, $this->translationDomain)
            );
        }
    }
}
