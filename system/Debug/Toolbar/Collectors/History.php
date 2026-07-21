<?php

declare(strict_types=1);



namespace CodeIgniter\Debug\Toolbar\Collectors;

use DateTime;


class History extends BaseCollector
{
    
    protected $hasTimeline = false;

    
    protected $hasTabContent = true;

    
    protected $hasLabel = true;

    
    protected $title = 'History';

    
    protected $files = [];

    
    public function setFiles(string $current, int $limit = 20)
    {
        $filenames = glob(WRITEPATH . 'debugbar/debugbar_*.json');

        $files   = [];
        $counter = 0;

        foreach (array_reverse($filenames) as $filename) {
            $counter++;

            
            if ($limit >= 0 && $counter > $limit) {
                unlink($filename);

                continue;
            }

            
            $contents = file_get_contents($filename);

            $contents = @json_decode($contents);
            if (json_last_error() === JSON_ERROR_NONE) {
                preg_match('/debugbar_(.*)\.json$/s', $filename, $time);
                $time = sprintf('%.6F', $time[1] ?? 0);

                
                $files[] = [
                    'time'        => $time,
                    'datetime'    => DateTime::createFromFormat('U.u', $time)->format('Y-m-d H:i:s.u'),
                    'active'      => $time === $current,
                    'status'      => $contents->vars->response->statusCode,
                    'method'      => $contents->method,
                    'url'         => $contents->url,
                    'isAJAX'      => $contents->isAJAX ? 'Yes' : 'No',
                    'contentType' => $contents->vars->response->contentType,
                ];
            }
        }

        $this->files = $files;
    }

    
    public function display(): array
    {
        return ['files' => $this->files];
    }

    
    public function getBadgeValue(): int
    {
        return count($this->files);
    }

    
    public function isEmpty(): bool
    {
        return $this->files === [];
    }

    
    public function icon(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAJySURBVEhL3ZU7aJNhGIVTpV6i4qCIgkIHxcXLErS4FBwUFNwiCKGhuTYJGaIgnRoo4qRu6iCiiIuIXXTTIkIpuqoFwaGgonUQlC5KafU5ycmNP0lTdPLA4fu+8573/a4/f6hXpFKpwUwmc9fDfweKbk+n07fgEv33TLSbtt/hvwNFT1PsG/zdTE0Gp+GFfD6/2fbVIxqNrqPIRbjg4t/hY8aztcngfDabHXbKyiiXy2vcrcPH8oDCry2FKDrA+Ar6L01E/ypyXzXaARjDGGcoeNxSDZXE0dHRA5VRE5LJ5CFy5jzJuOX2wHRHRnjbklZ6isQ3tIctBaAd4vlK3jLtkOVWqABBXd47jGHLmjTmSScttQV5J+SjfcUweFQEbsjAas5aqoCLXutJl7vtQsAzpRowYqkBinyCC8Vicb2lOih8zoldd0F8RD7qTFiqAnGrAy8stUAvi/hbqDM+YzkAFrLPdR5ZqoLXsd+Bh5YCIH7JniVdquUWxOPxDfboHhrI5XJ7HHhiqQXox+APe/Qk64+gGYVCYZs8cMpSFQj9JOoFzVqqo7k4HIvFYpscCoAjOmLffUsNUGRaQUwDlmofUa34ecsdgXdcXo4wbakBgiUFafXJV8A4DJ/2UrxUKm3E95H8RbjLcgOJRGILhnmCP+FBy5XvwN2uIPcy1AJvWgqC4xm2aU4Xb3lF4I+Tpyf8hRe5w3J7YLymSeA8Z3nSclv4WLRyFdfOjzrUFX0klJUEtZtntCNc+F69cz/FiDzEPtjzmcUMOr83kDQEX6pAJxJfpL3OX22n01YN7SZCoQnaSdoZ+Jz+PZihH3wt/xlCoT9M6nEtmRSPCQAAAABJRU5ErkJggg==';
    }
}
