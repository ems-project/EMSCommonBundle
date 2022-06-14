<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\Log;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LocalizedLogger extends AbstractLogger
{
    private const PATTERN = '/%(?<parameter>(_|)[[:alnum:]_]*)%/m';

    private LoggerInterface $logger;
    private TranslatorInterface $translator;

    public function __construct(LoggerInterface $logger, TranslatorInterface $translator)
    {
        $this->logger = $logger;
        $this->translator = $translator;
    }

    public function log($level, $message, array $context = []): void
    {
        $this->logger->log($level, $this->translateMessage($message, $context), $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function translateMessage(string $message, array &$context): string
    {
        $context['translation_message'] = $message;
        $translation = $this->translator->trans($message, [], 'ems_logger');

        return \preg_replace_callback(self::PATTERN, function ($match) use ($context) {
            return $context[$match['parameter']] ?? $match['parameter'];
        }, $translation) ?? $message;
    }
}
