<?php

namespace EMS\CommonBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;
use Symfony\Component\VarDumper\Cloner\Data;

class ElasticsearchDataCollector extends DataCollector implements LateDataCollectorInterface
{
    /**
     * @return array|Data
     */
    public function getData()
    {
        return $this->data;
    }

    public function addData(array $record)
    {
        $this->data[] = $record;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function lateCollect()
    {
        $this->data = $this->sanitizeData();
    }

    /**
     * Divide by 2 because for every elasticsearch call we get 2 log lines (Request/Response).
     *
     * @return int
     */
    public function getTotal()
    {
        return count($this->data) / 2;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'elasticsearch';
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->data = [];
    }

    /**
     * @return array
     */
    private function sanitizeData()
    {
        $result = [];

        foreach ($this->data as $log) {
            $sanitized = [
                'level' => $log['level_name'],
                'message' => $log['message'],
                'datetime' => $log['datetime'],
            ];

            $this->sanitizeContext($log, $sanitized);

            $result[] = $sanitized;
        }

        return $result;
    }

    private function sanitizeContext(array $log, array &$sanitize)
    {
        if (null === $log['context']) {
            return;
        }

        $context = $log['context'];

        if ('Response:' === $log['message']) {
            $sanitize['message'] = vsprintf('Response: %s %s %s', [
               $context['HTTP code'],
               $context['method'],
               $context['uri'],
            ]);
            $sanitize['duration'] = $context['duration'];
            $sanitize['response'] = $this->cloneVar($context['response']);
        }
    }
}
