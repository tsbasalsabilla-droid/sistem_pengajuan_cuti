<?php

declare(strict_types=1);



namespace CodeIgniter\Debug\Toolbar\Collectors;


class Files extends BaseCollector
{
    
    protected $hasTimeline = false;

    
    protected $hasTabContent = true;

    
    protected $title = 'Files';

    
    public function getTitleDetails(): string
    {
        return '( ' . count(get_included_files()) . ' )';
    }

    
    public function display(): array
    {
        $rawFiles  = get_included_files();
        $coreFiles = [];
        $userFiles = [];

        foreach ($rawFiles as $file) {
            $path = clean_path($file);

            if (str_contains($path, 'SYSTEMPATH')) {
                $coreFiles[] = [
                    'path' => $path,
                    'name' => basename($file),
                ];
            } else {
                $userFiles[] = [
                    'path' => $path,
                    'name' => basename($file),
                ];
            }
        }

        sort($userFiles);
        sort($coreFiles);

        return [
            'coreFiles' => $coreFiles,
            'userFiles' => $userFiles,
        ];
    }

    
    public function getBadgeValue(): int
    {
        return count(get_included_files());
    }

    
    public function icon(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAGBSURBVEhL7ZQ9S8NQGIVTBQUncfMfCO4uLgoKbuKQOWg+OkXERRE1IAXrIHbVDrqIDuLiJgj+gro7S3dnpfq88b1FMTE3VZx64HBzzvvZWxKnj15QCcPwCD5HUfSWR+JtzgmtsUcQBEva5IIm9SwSu+95CAWbUuy67qBa32ByZEDpIaZYZSZMjjQuPcQUq8yEyYEb8FSerYeQVGbAFzJkX1PyQWLhgCz0BxTCekC1Wp0hsa6yokzhed4oje6Iz6rlJEkyIKfUEFtITVtQdAibn5rMyaYsMS+a5wTv8qeXMhcU16QZbKgl3hbs+L4/pnpdc87MElZgq10p5DxGdq8I7xrvUWUKvG3NbSK7ubngYzdJwSsF7TiOh9VOgfcEz1UayNe3JUPM1RWC5GXYgTfc75B4NBmXJnAtTfpABX0iPvEd9ezALwkplCFXcr9styiNOKc1RRZpaPM9tcqBwlWzGY1qPL9wjqRBgF5BH6j8HWh2S7MHlX8PrmbK+k/8PzjOOzx1D3i1pKTTAAAAAElFTkSuQmCC';
    }
}
