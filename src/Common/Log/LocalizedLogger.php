<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\Log;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LocalizedLogger implements LoggerInterface
{
    private const PATTERN = '/%(?<parameter>(_|)[[:alnum:]_]*)%/m';
    private LoggerInterface $logger;
    private TranslatorInterface $translator;

    public function __construct(LoggerInterface $logger, TranslatorInterface $translator)
    {
        $this->logger = $logger;
        $this->translator = $translator;
    }

    public function emergency($message, array $context = []): void
    {
        $this->logger->emergency($this->translateMessage($message, $context), $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->logger->alert($this->translateMessage($message, $context), $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->logger->critical($this->translateMessage($message, $context), $context);
    }

    public function error($message, array $context = []): void
    {
        $this->logger->error($this->translateMessage($message, $context), $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->logger->warning($this->translateMessage($message, $context), $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->logger->notice($this->translateMessage($message, $context), $context);
    }

    public function info($message, array $context = []): void
    {
        $this->logger->info($this->translateMessage($message, $context), $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->logger->debug($this->translateMessage($message, $context), $context);
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
