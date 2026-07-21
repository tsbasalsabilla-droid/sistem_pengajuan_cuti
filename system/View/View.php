<?php

declare(strict_types=1);



namespace CodeIgniter\View;

use CodeIgniter\Autoloader\FileLocatorInterface;
use CodeIgniter\Debug\Toolbar\Collectors\Views;
use CodeIgniter\Exceptions\RuntimeException;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\View\Exceptions\ViewException;
use Config\Toolbar;
use Config\View as ViewConfig;
use Psr\Log\LoggerInterface;


class View implements RendererInterface
{
    use ViewDecoratorTrait;

    
    protected $data = [];

    
    protected $tempData;

    
    protected $viewPath;

    
    protected $renderVars = [];

    
    protected $loader;

    
    protected $logger;

    
    protected $debug = false;

    
    protected $performanceData = [];

    
    protected $config;

    
    protected $saveData;

    
    protected $viewsCount = 0;

    
    protected $layout;

    
    protected $sections = [];

    
    protected $sectionStack = [];

    public function __construct(
        ViewConfig $config,
        ?string $viewPath = null,
        ?FileLocatorInterface $loader = null,
        ?bool $debug = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->config   = $config;
        $this->viewPath = rtrim($viewPath, '\\/ ') . DIRECTORY_SEPARATOR;
        $this->loader   = $loader ?? service('locator');
        $this->logger   = $logger ?? service('logger');
        $this->debug    = $debug ?? CI_DEBUG;
        $this->saveData = (bool) $config->saveData;
    }

    
    public function render(string $view, ?array $options = null, ?bool $saveData = null): string
    {
        $this->renderVars['start'] = microtime(true);

        
        
        
        $saveData ??= $this->saveData;

        $fileExt = pathinfo($view, PATHINFO_EXTENSION);
        
        $this->renderVars['view'] = ($fileExt === '') ? $view . '.php' : $view;

        $this->renderVars['options'] = $options ?? [];

        
        if (isset($this->renderVars['options']['cache'])) {
            $cacheName = $this->renderVars['options']['cache_name']
                ?? str_replace('.php', '', $this->renderVars['view']);
            $cacheName = str_replace(['\\', '/'], '', $cacheName);

            $this->renderVars['cacheName'] = $cacheName;

            $output = cache($this->renderVars['cacheName']);

            if (is_string($output) && $output !== '') {
                $this->logPerformance(
                    $this->renderVars['start'],
                    microtime(true),
                    $this->renderVars['view'],
                );

                return $output;
            }
        }

        $this->renderVars['file'] = $this->viewPath . $this->renderVars['view'];

        if (str_contains($this->renderVars['view'], '\\')) {
            $appOverridesFolder = $this->config->appOverridesFolder ?? 'overrides';

            $overrideFolder = $appOverridesFolder !== ''
                 ? trim($appOverridesFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
                 : '';

            $this->renderVars['file'] = $this->viewPath
            . $overrideFolder
            . ltrim(str_replace('\\', DIRECTORY_SEPARATOR, $this->renderVars['view']), DIRECTORY_SEPARATOR);
        } else {
            $this->renderVars['file'] = $this->viewPath . $this->renderVars['view'];
        }

        if (! is_file($this->renderVars['file'])) {
            $this->renderVars['file'] = $this->loader->locateFile(
                $this->renderVars['view'],
                'Views',
                ($fileExt === '') ? 'php' : $fileExt,
            );
        }

        
        if ($this->renderVars['file'] === false) {
            throw ViewException::forInvalidFile($this->renderVars['view']);
        }

        
        $this->prepareTemplateData($saveData);

        
        $renderVars = $this->renderVars;

        $output = (function (): string {
            extract($this->tempData);
            ob_start();
            include $this->renderVars['file'];

            return ob_get_clean() ?: '';
        })();

        
        $this->renderVars = $renderVars;

        
        
        
        if ($this->layout !== null && $this->sectionStack === []) {
            $layoutView   = $this->layout;
            $this->layout = null;
            
            $renderVars = $this->renderVars;
            $output     = $this->render($layoutView, $options, $saveData);
            
            $this->renderVars = $renderVars;
        }

        $output = $this->decorateOutput($output);

        $this->logPerformance(
            $this->renderVars['start'],
            microtime(true),
            $this->renderVars['view'],
        );

        
        $filters              = service('filters');
        $requiredAfterFilters = $filters->getRequiredFilters('after')[0];
        if (in_array('toolbar', $requiredAfterFilters, true)) {
            $debugBarEnabled = true;
        } else {
            $afterFilters    = $filters->getFiltersClass()['after'];
            $debugBarEnabled = in_array(DebugToolbar::class, $afterFilters, true);
        }

        if (
            $this->debug && $debugBarEnabled
            && (! isset($options['debug']) || $options['debug'] === true)
        ) {
            $toolbarCollectors = config(Toolbar::class)->collectors;

            if (in_array(Views::class, $toolbarCollectors, true)) {
                
                $this->renderVars['file'] = clean_path($this->renderVars['file']);
                $this->renderVars['file'] = ++$this->viewsCount . ' ' . $this->renderVars['file'];

                $output = '<!-- DEBUG-VIEW START ' . $this->renderVars['file'] . ' -->' . PHP_EOL
                    . $output . PHP_EOL
                    . '<!-- DEBUG-VIEW ENDED ' . $this->renderVars['file'] . ' -->' . PHP_EOL;
            }
        }

        
        if (isset($this->renderVars['options']['cache'])) {
            cache()->save(
                $this->renderVars['cacheName'],
                $output,
                (int) $this->renderVars['options']['cache'],
            );
        }

        $this->tempData = null;

        return $output;
    }

    
    public function renderString(string $view, ?array $options = null, ?bool $saveData = null): string
    {
        $start = microtime(true);
        $saveData ??= $this->saveData;
        $this->prepareTemplateData($saveData);

        $output = (function (string $view): string {
            extract($this->tempData);
            ob_start();
            eval('?>' . $view);

            return ob_get_clean() ?: '';
        })($view);

        $this->logPerformance($start, microtime(true), $this->excerpt($view));
        $this->tempData = null;

        return $output;
    }

    
    public function excerpt(string $string, int $length = 20): string
    {
        return (mb_strlen($string) > $length) ? mb_substr($string, 0, $length - 3) . '...' : $string;
    }

    
    public function setData(array $data = [], ?string $context = null): RendererInterface
    {
        if ($context !== null) {
            $data = \esc($data, $context);
        }

        $this->tempData ??= $this->data;
        $this->tempData = array_merge($this->tempData, $data);

        return $this;
    }

    
    public function setVar(string $name, $value = null, ?string $context = null): RendererInterface
    {
        if ($context !== null) {
            $value = esc($value, $context);
        }

        $this->tempData ??= $this->data;
        $this->tempData[$name] = $value;

        return $this;
    }

    
    public function resetData(): RendererInterface
    {
        $this->data = [];

        return $this;
    }

    
    public function getData(): array
    {
        return $this->tempData ?? $this->data;
    }

    
    public function extend(string $layout)
    {
        $this->layout = $layout;
    }

    
    public function section(string $name)
    {
        $this->sectionStack[] = $name;

        ob_start();
    }

    
    public function endSection()
    {
        $contents = ob_get_clean();

        if ($this->sectionStack === []) {
            throw new RuntimeException('View themes, no current section.');
        }

        $section = array_pop($this->sectionStack);

        
        if (! array_key_exists($section, $this->sections)) {
            $this->sections[$section] = [];
        }

        $this->sections[$section][] = $contents;
    }

    
    public function renderSection(string $sectionName, bool $saveData = false): string
    {
        if (! isset($this->sections[$sectionName])) {
            return '';
        }

        $output = '';

        foreach ($this->sections[$sectionName] as $key => $contents) {
            $output .= $contents;
            if ($saveData === false) {
                unset($this->sections[$sectionName][$key]);
            }
        }

        return $output;
    }

    
    public function include(string $view, ?array $options = null, $saveData = true): string
    {
        return $this->render($view, $options, $saveData);
    }

    
    public function getPerformanceData(): array
    {
        return $this->performanceData;
    }

    
    protected function logPerformance(float $start, float $end, string $view)
    {
        if ($this->debug) {
            $this->performanceData[] = [
                'start' => $start,
                'end'   => $end,
                'view'  => $view,
            ];
        }
    }

    protected function prepareTemplateData(bool $saveData): void
    {
        $this->tempData ??= $this->data;

        if ($saveData) {
            $this->data = $this->tempData;
        }
    }
}
