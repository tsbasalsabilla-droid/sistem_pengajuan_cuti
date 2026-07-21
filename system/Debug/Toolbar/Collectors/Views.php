<?php

declare(strict_types=1);



namespace CodeIgniter\Debug\Toolbar\Collectors;

use CodeIgniter\View\RendererInterface;


class Views extends BaseCollector
{
    
    protected $hasTimeline = true;

    
    protected $hasTabContent = false;

    
    protected $hasLabel = true;

    
    protected $hasVarData = true;

    
    protected $title = 'Views';

    
    protected $viewer;

    
    protected $views = [];

    private function initViewer(): void
    {
        $this->viewer ??= service('renderer');
    }

    
    protected function formatTimelineData(): array
    {
        $this->initViewer();

        $data = [];

        $rows = $this->viewer->getPerformanceData();

        foreach ($rows as $info) {
            $data[] = [
                'name'      => 'View: ' . $info['view'],
                'component' => 'Views',
                'start'     => $info['start'],
                'duration'  => $info['end'] - $info['start'],
            ];
        }

        return $data;
    }

    
    public function getVarData(): array
    {
        $this->initViewer();

        return [
            'View Data' => $this->viewer->getData(),
        ];
    }

    
    public function getBadgeValue(): int
    {
        $this->initViewer();

        return count($this->viewer->getPerformanceData());
    }

    
    public function icon(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAADeSURBVEhL7ZSxDcIwEEWNYA0YgGmgyAaJLTcUaaBzQQEVjMEabBQxAdw53zTHiThEovGTfnE/9rsoRUxhKLOmaa6Uh7X2+UvguLCzVxN1XW9x4EYHzik033Hp3X0LO+DaQG8MDQcuq6qao4qkHuMgQggLvkPLjqh00ZgFDBacMJYFkuwFlH1mshdkZ5JPJERA9JpI6xNCBESvibQ+IURA9JpI6xNCBESvibQ+IURA9DTsuHTOrVFFxixgB/eUFlU8uKJ0eDBFOu/9EvoeKnlJS2/08Tc8NOwQ8sIfMeYFjqKDjdU2sp4AAAAASUVORK5CYII=';
    }
}
