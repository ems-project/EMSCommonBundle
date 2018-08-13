<?php

namespace EMS\CommonBundle\EventListener;

use EMS\CommonBundle\Command\CommandInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class CommandListener implements EventSubscriberInterface
{
    /**
     * @var Stopwatch
     */
    private $stopwatch;
    
    public function __construct()
    {
        $this->stopwatch = new Stopwatch();
    }
    
    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => ['onCommand'],
            ConsoleEvents::TERMINATE => ['onTerminate'],
        ];
    }

    /**
     * @param ConsoleCommandEvent $event
     *
     * @return void
     */
    public function onCommand(ConsoleCommandEvent $event)
    {
        $command = $event->getCommand();

        if (!$command instanceof CommandInterface) {
            return;
        }
        
        $this->stopwatch->start($command->getName());
    }
    
    /**
     * @param ConsoleTerminateEvent $event
     *
     * @return void
     */
    public function onTerminate(ConsoleTerminateEvent $event)
    {
        $command = $event->getCommand();

        if (!$command instanceof CommandInterface) {
            return;
        }
        
        $stopwatch = $this->stopwatch->stop($command->getName());
        
        $io = new SymfonyStyle($event->getInput(), $event->getOutput());
        $io->listing([
            sprintf('Duration: %d s', $stopwatch->getDuration() / 1000),
            sprintf('Memory: %s',  $this->formatBytes($stopwatch->getMemory()))
        ]);
    }
    
    /**
     * @param int $bytes
     * @param int $precision
     *
     * @return string
     */
    private function formatBytes($bytes, $precision = 2) 
    { 
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0); 
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
        $pow = min($pow, count($units) - 1); 

        $bytes /= (1 << (10 * $pow)); 

        return round($bytes, $precision) . ' ' . $units[$pow]; 
    } 
}