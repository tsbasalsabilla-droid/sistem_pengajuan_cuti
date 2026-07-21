<?php

declare(strict_types=1);



namespace CodeIgniter\Debug\Toolbar\Collectors;


class Logs extends BaseCollector
{
    
    protected $hasTimeline = false;

    
    protected $hasTabContent = true;

    
    protected $title = 'Logs';

    
    protected $data = [];

    
    public function display(): array
    {
        return [
            'logs' => $this->collectLogs(),
        ];
    }

    
    public function isEmpty(): bool
    {
        $this->collectLogs();

        return $this->data === [];
    }

    
    public function icon(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAACYSURBVEhLYxgFJIHU1FSjtLS0i0D8AYj7gEKMEBkqAaAFF4D4ERCvAFrwH4gDoFIMKSkpFkB+OTEYqgUTACXfA/GqjIwMQyD9H2hRHlQKJFcBEiMGQ7VgAqCBvUgK32dmZspCpagGGNPT0/1BLqeF4bQHQJePpiIwhmrBBEADR1MRfgB0+WgqAmOoFkwANHA0FY0CUgEDAwCQ0PUpNB3kqwAAAABJRU5ErkJggg==';
    }

    
    protected function collectLogs()
    {
        if ($this->data !== []) {
            return $this->data;
        }

        $cache = service('logger')->logCache;

        $this->data = $cache ?? [];

        return $this->data;
    }
}
