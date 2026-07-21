<?php

declare(strict_types=1);



namespace CodeIgniter\Debug\Toolbar\Collectors;


class BaseCollector
{
    
    protected $hasTimeline = false;

    
    protected $hasTabContent = false;

    
    protected $hasLabel = false;

    
    protected $hasVarData = false;

    
    protected $title = '';

    
    public function getTitle(bool $safe = false): string
    {
        if ($safe) {
            return str_replace(' ', '-', strtolower($this->title));
        }

        return $this->title;
    }

    
    public function getTitleDetails(): string
    {
        return '';
    }

    
    public function hasTabContent(): bool
    {
        return (bool) $this->hasTabContent;
    }

    
    public function hasLabel(): bool
    {
        return (bool) $this->hasLabel;
    }

    
    public function hasTimelineData(): bool
    {
        return (bool) $this->hasTimeline;
    }

    
    public function timelineData(): array
    {
        if (! $this->hasTimeline) {
            return [];
        }

        return $this->formatTimelineData();
    }

    
    public function hasVarData(): bool
    {
        return (bool) $this->hasVarData;
    }

    
    public function getVarData()
    {
        return null;
    }

    
    protected function formatTimelineData(): array
    {
        return [];
    }

    
    public function display()
    {
        return [];
    }

    
    public function cleanPath(string $file): string
    {
        return clean_path($file);
    }

    
    public function getBadgeValue()
    {
        return null;
    }

    
    public function isEmpty(): bool
    {
        return false;
    }

    
    public function icon(): string
    {
        return '';
    }

    
    public function getAsArray(): array
    {
        return [
            'title'           => $this->getTitle(),
            'titleSafe'       => $this->getTitle(true),
            'titleDetails'    => $this->getTitleDetails(),
            'display'         => $this->display(),
            'badgeValue'      => $this->getBadgeValue(),
            'isEmpty'         => $this->isEmpty(),
            'hasTabContent'   => $this->hasTabContent(),
            'hasLabel'        => $this->hasLabel(),
            'icon'            => $this->icon(),
            'hasTimelineData' => $this->hasTimelineData(),
            'timelineData'    => $this->timelineData(),
        ];
    }
}
