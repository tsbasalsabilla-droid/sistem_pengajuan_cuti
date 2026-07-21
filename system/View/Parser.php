<?php



namespace CodeIgniter\View;

use CodeIgniter\Autoloader\FileLocatorInterface;
use CodeIgniter\View\Exceptions\ViewException;
use Config\View as ViewConfig;
use ParseError;
use Psr\Log\LoggerInterface;


class Parser extends View
{
    use ViewDecoratorTrait;

    
    public $leftDelimiter = '{';

    
    public $rightDelimiter = '}';

    
    protected string $leftConditionalDelimiter = '{';

    
    protected string $rightConditionalDelimiter = '}';

    
    protected $noparseBlocks = [];

    
    protected $plugins = [];

    
    protected $dataContexts = [];

    
    public function __construct(
        ViewConfig $config,
        ?string $viewPath = null,
        $loader = null,
        ?bool $debug = null,
        ?LoggerInterface $logger = null,
    ) {
        
        $this->plugins = $config->plugins;

        parent::__construct($config, $viewPath, $loader, $debug, $logger);
    }

    
    public function render(string $view, ?array $options = null, ?bool $saveData = null): string
    {
        $start = microtime(true);
        if ($saveData === null) {
            $saveData = $this->config->saveData;
        }

        $fileExt = pathinfo($view, PATHINFO_EXTENSION);
        $view    = ($fileExt === '') ? $view . '.php' : $view; 

        $cacheName = $options['cache_name'] ?? str_replace('.php', '', $view);

        
        if (isset($options['cache'])) {
            $output = cache($cacheName);

            if (is_string($output) && $output !== '') {
                $this->logPerformance($start, microtime(true), $view);

                return $output;
            }
        }

        $file = $this->viewPath . $view;

        if (! is_file($file)) {
            $fileOrig = $file;
            $file     = $this->loader->locateFile($view, 'Views');

            
            if ($file === false) {
                throw ViewException::forInvalidFile($fileOrig);
            }
        }

        if ($this->tempData === null) {
            $this->tempData = $this->data;
        }

        $template = file_get_contents($file);
        $output   = $this->parse($template, $this->tempData, $options);
        $this->logPerformance($start, microtime(true), $view);

        if ($saveData) {
            $this->data = $this->tempData;
        }

        $output = $this->decorateOutput($output);

        
        if (isset($options['cache'])) {
            cache()->save($cacheName, $output, (int) $options['cache']);
        }
        $this->tempData = null;

        return $output;
    }

    
    public function renderString(string $template, ?array $options = null, ?bool $saveData = null): string
    {
        $start = microtime(true);
        if ($saveData === null) {
            $saveData = $this->config->saveData;
        }

        if ($this->tempData === null) {
            $this->tempData = $this->data;
        }

        $output = $this->parse($template, $this->tempData, $options);

        $this->logPerformance($start, microtime(true), $this->excerpt($template));

        if ($saveData) {
            $this->data = $this->tempData;
        }

        $this->tempData = null;

        return $output;
    }

    
    public function setData(array $data = [], ?string $context = null): RendererInterface
    {
        if ($context !== null && $context !== '') {
            foreach ($data as $key => &$value) {
                if (is_array($value)) {
                    foreach ($value as &$obj) {
                        $obj = $this->objectToArray($obj);
                    }
                } else {
                    $value = $this->objectToArray($value);
                }

                $this->dataContexts[$key] = $context;
            }
        }

        $this->tempData ??= $this->data;
        $this->tempData = array_merge($this->tempData, $data);

        return $this;
    }

    
    protected function parse(string $template, array $data = [], ?array $options = null): string
    {
        if ($template === '') {
            return '';
        }

        
        
        $template = str_replace(['<?', '?>'], ['&lt;?', '?&gt;'], $template);

        $template = $this->parseComments($template);
        $template = $this->extractNoparse($template);

        
        $template = $this->parseConditionals($template);

        
        
        $template = $this->parsePlugins($template);

        
        $replaceSingleStack = [];
        $replacePairsStack  = [];

        
        
        foreach ($data as $key => $val) {
            $escape = true;

            if (is_array($val)) {
                $escape              = false;
                $replacePairsStack[] = [
                    'replace' => $this->parsePair($key, $val, $template),
                    'escape'  => $escape,
                ];
            } else {
                $replaceSingleStack[] = [
                    'replace' => $this->parseSingle($key, (string) $val),
                    'escape'  => $escape,
                ];
            }
        }

        
        
        $replace = array_merge($replacePairsStack, $replaceSingleStack);

        
        
        foreach ($replace as $replaceItem) {
            
            foreach ($replaceItem['replace'] as $pattern => $content) {
                $template = $this->replaceSingle($pattern, $content, $template, $replaceItem['escape']);
            }
        }

        return $this->insertNoparse($template);
    }

    
    protected function parseSingle(string $key, string $val): array
    {
        $pattern = '#' . $this->leftDelimiter . '!?\s*' . preg_quote($key, '#')
            . '(?(?=\s*\|\s*)(\s*\|*\s*([|\w<>=\(\),:.\-\s\+\\\\/]+)*\s*))(\s*)!?'
            . $this->rightDelimiter . '#ums';

        return [$pattern => $val];
    }

    
    protected function parsePair(string $variable, array $data, string $template): array
    {
        
        
        $replace = [];

        
        
        preg_match_all(
            '#' . $this->leftDelimiter . '\s*' . preg_quote($variable, '#') . '\s*' . $this->rightDelimiter . '(.+?)' .
            $this->leftDelimiter . '\s*/' . preg_quote($variable, '#') . '\s*' . $this->rightDelimiter . '#us',
            $template,
            $matches,
            PREG_SET_ORDER,
        );

        
        foreach ($matches as $match) {
            
            
            $str = '';  

            foreach ($data as $row) {
                
                
                if (is_object($row) && method_exists($row, 'toArray')) {
                    $row = $row->toArray();
                }
                
                elseif (is_object($row)) {
                    $row = (array) $row;
                }

                $temp  = [];
                $pairs = [];
                $out   = $match[1];

                foreach ($row as $key => $val) {
                    
                    if (is_array($val)) {
                        $pair = $this->parsePair($key, $val, $match[1]);

                        if ($pair !== []) {
                            $pairs[array_keys($pair)[0]] = true;

                            $temp = array_merge($temp, $pair);
                        }

                        continue;
                    }

                    if (is_object($val)) {
                        $val = 'Class: ' . $val::class;
                    } elseif (is_resource($val)) {
                        $val = 'Resource';
                    }

                    $temp['#' . $this->leftDelimiter . '!?\s*' . preg_quote($key, '#') . '(?(?=\s*\|\s*)(\s*\|*\s*([|\w<>=\(\),:.\-\s\+\\\\/]+)*\s*))(\s*)!?' . $this->rightDelimiter . '#us'] = $val;
                }

                
                foreach ($temp as $pattern => $content) {
                    $out = $this->replaceSingle($pattern, $content, $out, ! isset($pairs[$pattern]));
                }

                $str .= $out;
            }

            $escapedMatch = preg_quote($match[0], '#');

            $replace['#' . $escapedMatch . '#us'] = $str;
        }

        return $replace;
    }

    
    protected function parseComments(string $template): string
    {
        return preg_replace('/\{#.*?#\}/us', '', $template);
    }

    
    protected function extractNoparse(string $template): string
    {
        $pattern = '/\{\s*noparse\s*\}(.*?)\{\s*\/noparse\s*\}/ums';

        
        if (preg_match_all($pattern, $template, $matches, PREG_SET_ORDER) >= 1) {
            foreach ($matches as $match) {
                
                $hash                       = md5($match[1]);
                $this->noparseBlocks[$hash] = $match[1];
                $template                   = str_replace($match[0], "noparse_{$hash}", $template);
            }
        }

        return $template;
    }

    
    public function insertNoparse(string $template): string
    {
        foreach ($this->noparseBlocks as $hash => $replace) {
            $template = str_replace("noparse_{$hash}", $replace, $template);
            unset($this->noparseBlocks[$hash]);
        }

        return $template;
    }

    
    protected function parseConditionals(string $template): string
    {
        $leftDelimiter  = preg_quote($this->leftConditionalDelimiter, '/');
        $rightDelimiter = preg_quote($this->rightConditionalDelimiter, '/');

        $pattern = '/'
            . $leftDelimiter
            . '\s*(if|elseif)\s*((?:\()?(.*?)(?:\))?)\s*'
            . $rightDelimiter
            . '/ums';

        
        preg_match_all($pattern, $template, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            
            $condition = $match[2];

            $statement = $match[1] === 'elseif' ? '<?php elseif (' . $condition . '): ?>' : '<?php if (' . $condition . '): ?>';
            $template  = str_replace($match[0], $statement, $template);
        }

        $template = preg_replace(
            '/' . $leftDelimiter . '\s*else\s*' . $rightDelimiter . '/ums',
            '<?php else: ?>',
            $template,
        );
        $template = preg_replace(
            '/' . $leftDelimiter . '\s*endif\s*' . $rightDelimiter . '/ums',
            '<?php endif; ?>',
            $template,
        );

        
        ob_start();

        if ($this->tempData === null) {
            $this->tempData = $this->data;
        }

        extract($this->tempData);

        try {
            eval('?>' . $template . '<?php ');
        } catch (ParseError) {
            ob_end_clean();

            throw ViewException::forTagSyntaxError(str_replace(['?>', '<?php '], '', $template));
        }

        return ob_get_clean();
    }

    
    public function setDelimiters($leftDelimiter = '{', $rightDelimiter = '}'): RendererInterface
    {
        $this->leftDelimiter  = $leftDelimiter;
        $this->rightDelimiter = $rightDelimiter;

        return $this;
    }

    
    public function setConditionalDelimiters($leftDelimiter = '{', $rightDelimiter = '}'): RendererInterface
    {
        $this->leftConditionalDelimiter  = $leftDelimiter;
        $this->rightConditionalDelimiter = $rightDelimiter;

        return $this;
    }

    
    protected function replaceSingle($pattern, $content, $template, bool $escape = false): string
    {
        $content = (string) $content;

        
        return preg_replace_callback($pattern, function ($matches) use ($content, $escape): string {
            
            if (
                str_starts_with($matches[0], $this->leftDelimiter . '!')
                && substr($matches[0], -1 - strlen($this->rightDelimiter)) === '!' . $this->rightDelimiter
            ) {
                $escape = false;
            }

            return $this->prepareReplacement($matches, $content, $escape);
        }, (string) $template);
    }

    
    protected function prepareReplacement(array $matches, string $replace, bool $escape = true): string
    {
        $orig = array_shift($matches);

        
        
        $filters = (isset($matches[1]) && $matches[1] !== '') ? explode('|', $matches[1]) : [];

        if ($escape && $filters === [] && ($context = $this->shouldAddEscaping($orig))) {
            $filters[] = "esc({$context})";
        }

        return $this->applyFilters($replace, $filters);
    }

    
    public function shouldAddEscaping(string $key)
    {
        $escape = false;

        $key = trim(str_replace(['{', '}'], '', $key));

        
        
        if (array_key_exists($key, $this->dataContexts)) {
            if ($this->dataContexts[$key] !== 'raw') {
                return $this->dataContexts[$key];
            }
        }
        
        elseif (! str_contains($key, '|')) {
            $escape = 'html';
        }
        
        elseif (str_contains($key, 'noescape')) {
            $escape = false;
        }
        
        elseif (preg_match('/\s+esc/u', $key) !== 1) {
            $escape = 'html';
        }

        return $escape;
    }

    
    protected function applyFilters(string $replace, array $filters): string
    {
        
        foreach ($filters as $filter) {
            
            preg_match('/\([\w<>=\/\\\,:.\-\s\+]+\)/u', $filter, $param);

            
            $param = ($param !== []) ? trim($param[0], '() ') : null;

            
            if ($param !== null && $param !== '') {
                $param = explode(',', $param);

                
                foreach ($param as &$p) {
                    $p = trim($p, ' "');
                }
            } else {
                $param = [];
            }

            
            $filter = $param !== [] ? trim(strtolower(substr($filter, 0, strpos($filter, '(')))) : trim($filter);

            if (! array_key_exists($filter, $this->config->filters)) {
                continue;
            }

            
            
            $replace = $this->config->filters[$filter]($replace, ...$param);
        }

        return (string) $replace;
    }

    

    
    protected function parsePlugins(string $template)
    {
        foreach ($this->plugins as $plugin => $callable) {
            
            $isPair   = is_array($callable);
            $callable = $isPair ? array_shift($callable) : $callable;

            
            $pattern = $isPair
                ? '#\{\+\s*' . $plugin . '([\w=\-_:\+\s\(\)/"@.]*)?\s*\+\}(.+?)\{\+\s*/' . $plugin . '\s*\+\}#uims'
                : '#\{\+\s*' . $plugin . '([\w=\-_:\+\s\(\)/"@.]*)?\s*\+\}#uims';

            
            if (preg_match_all($pattern, $template, $matches, PREG_SET_ORDER) < 1) {
                continue;
            }

            foreach ($matches as $match) {
                $params = [];

                preg_match_all('/([\w-]+=\"[^"]+\")|([\w-]+=[^\"\s=]+)|(\"[^"]+\")|(\S+)/u', trim($match[1]), $matchesParams);

                foreach ($matchesParams[0] as $item) {
                    $keyVal = explode('=', $item);

                    if (count($keyVal) === 2) {
                        $params[$keyVal[0]] = str_replace('"', '', $keyVal[1]);
                    } else {
                        $params[] = str_replace('"', '', $item);
                    }
                }

                $template = $isPair
                    ? str_replace($match[0], $callable($match[2], $params), $template)
                    : str_replace($match[0], $callable($params), $template);
            }
        }

        return $template;
    }

    
    public function addPlugin(string $alias, callable $callback, bool $isPair = false)
    {
        $this->plugins[$alias] = $isPair ? [$callback] : $callback;

        return $this;
    }

    
    public function removePlugin(string $alias)
    {
        unset($this->plugins[$alias]);

        return $this;
    }

    
    protected function objectToArray($value)
    {
        
        
        if (is_object($value) && method_exists($value, 'toArray')) {
            $value = $value->toArray();
        }
        
        elseif (is_object($value)) {
            $value = (array) $value;
        }

        return $value;
    }
}
