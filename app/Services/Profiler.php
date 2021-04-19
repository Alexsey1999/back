<?php

namespace App\Services;

class Profiler
{
    private $timings = [];
   
    public function start(string $operation_id) 
    {
        $this->timings[$operation_id] = [
            'start' => $this->getMillisecondsTimestamp(),
            'end' => 0
        ];
    }

    public function end(string $operation_id)
    {
        $this->timings[$operation_id]['end'] = $this->getMillisecondsTimestamp();
    }

    public function getReport(): array
    {
        $report = [];

        foreach($this->timings as $key => $item) {
            $report[$key] = [
                'start' => $item['start'],
                'end' => $item['end'],
                'duration' => ($item['end'] - $item['start']) . 'ms'
            ];
        }

        return $report;
    }

    private function getMillisecondsTimestamp()
    {
       return round(microtime(true) * 1000);
    }
}