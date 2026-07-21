<?php

declare(strict_types=1);



namespace CodeIgniter\CLI;

use Config\Generators;
use Throwable;


trait GeneratorTrait
{
    
    protected $component;

    
    protected $directory;

    
    protected ?string $templatePath = null;

    
    protected $template;

    
    protected $classNameLang = '';

    
    protected ?string $namespace = null;

    
    private $hasClassName = true;

    
    private $sortImports = true;

    
    private $enabledSuffixing = true;

    
    private $params = [];

    
    protected function execute(array $params): void
    {
        $this->generateClass($params);
    }

    
    protected function generateClass(array $params): void
    {
        $this->params = $params;

        
        $class = $this->qualifyClassName();

        
        $target = $this->buildPath($class);

        
        if ($target === '') {
            return;
        }

        $this->generateFile($target, $this->buildContent($class));
    }

    
    protected function generateView(string $view, array $params): void
    {
        $this->params = $params;

        $target = $this->buildPath($view);

        
        if ($target === '') {
            return;
        }

        $this->generateFile($target, $this->buildContent($view));
    }

    
    private function generateFile(string $target, string $content): void
    {
        if ($this->getOption('namespace') === 'CodeIgniter') {
            
            CLI::write(lang('CLI.generator.usingCINamespace'), 'yellow');
            CLI::newLine();

            if (
                CLI::prompt(
                    'Are you sure you want to continue?',
                    ['y', 'n'],
                    'required',
                ) === 'n'
            ) {
                CLI::newLine();
                CLI::write(lang('CLI.generator.cancelOperation'), 'yellow');
                CLI::newLine();

                return;
            }

            CLI::newLine();
            
        }

        $isFile = is_file($target);

        
        
        if (! $this->getOption('force') && $isFile) {
            CLI::error(
                lang('CLI.generator.fileExist', [clean_path($target)]),
                'light_gray',
                'red',
            );
            CLI::newLine();

            return;
        }

        
        $dir = dirname($target);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        helper('filesystem');

        
        
        if (! write_file($target, $content)) {
            
            CLI::error(
                lang('CLI.generator.fileError', [clean_path($target)]),
                'light_gray',
                'red',
            );
            CLI::newLine();

            return;
            
        }

        if ($this->getOption('force') && $isFile) {
            CLI::write(
                lang('CLI.generator.fileOverwrite', [clean_path($target)]),
                'yellow',
            );
            CLI::newLine();

            return;
        }

        CLI::write(
            lang('CLI.generator.fileCreate', [clean_path($target)]),
            'green',
        );
        CLI::newLine();
    }

    
    protected function prepare(string $class): string
    {
        return $this->parseTemplate($class);
    }

    
    protected function basename(string $filename): string
    {
        return basename($filename);
    }

    
    protected function qualifyClassName(): string
    {
        $class = $this->normalizeInputClassName();

        
        $namespace = $this->getNamespace() . '\\';

        if (str_starts_with($class, $namespace)) {
            return $class; 
        }

        $directoryString = ($this->directory !== null) ? $this->directory . '\\' : '';

        return $namespace . $directoryString . str_replace('/', '\\', $class);
    }

    private function normalizeInputClassName(): string
    {
        
        $class = $this->params[0] ?? CLI::getSegment(2);

        if ($class === null && $this->hasClassName) {
            $nameField = $this->classNameLang !== ''
                ? $this->classNameLang
                : 'CLI.generator.className.default';
            $class = CLI::prompt(lang($nameField), null, 'required');

            
            
            $this->params[0] = $class;
            CLI::newLine();
        }

        helper('inflector');

        $component = singular($this->component);

        
        $pattern = sprintf('/([a-z][a-z0-9_\/\\\\]+)(%s)$/i', $component);

        if (preg_match($pattern, $class, $matches) === 1) {
            $class = $matches[1] . ucfirst($matches[2]);
        }

        if (
            $this->enabledSuffixing && $this->getOption('suffix')
            && preg_match($pattern, $class) !== 1
        ) {
            $class .= ucfirst($component);
        }

        
        return ltrim(
            implode(
                '\\',
                array_map(
                    pascalize(...),
                    explode('\\', str_replace('/', '\\', trim($class))),
                ),
            ),
            '\\/',
        );
    }

    
    protected function renderTemplate(array $data = []): string
    {
        try {
            $template = $this->templatePath ?? config(Generators::class)->views[$this->name];

            return view($template, $data, ['debug' => false]);
        } catch (Throwable $e) {
            log_message('error', (string) $e);

            return view(
                "CodeIgniter\\Commands\\Generators\\Views\\{$this->template}",
                $data,
                ['debug' => false],
            );
        }
    }

    
    protected function parseTemplate(
        string $class,
        array $search = [],
        array $replace = [],
        array $data = [],
    ): string {
        
        $namespace = trim(
            implode(
                '\\',
                array_slice(explode('\\', $class), 0, -1),
            ),
            '\\',
        );
        $search[]  = '<@php';
        $search[]  = '{namespace}';
        $search[]  = '{class}';
        $replace[] = '<?php';
        $replace[] = $namespace;
        $replace[] = str_replace($namespace . '\\', '', $class);

        return str_replace($search, $replace, $this->renderTemplate($data));
    }

    
    protected function buildContent(string $class): string
    {
        $template = $this->prepare($class);

        if (
            $this->sortImports
            && preg_match(
                '/(?P<imports>(?:^use [^;]+;$\n?)+)/m',
                $template,
                $match,
            )
        ) {
            $imports = explode("\n", trim($match['imports']));
            sort($imports);

            return str_replace(trim($match['imports']), implode("\n", $imports), $template);
        }

        return $template;
    }

    
    protected function buildPath(string $class): string
    {
        $namespace = $this->getNamespace();

        
        $base = service('autoloader')->getNamespace($namespace);

        if (! $base = reset($base)) {
            CLI::error(
                lang('CLI.namespaceNotDefined', [$namespace]),
                'light_gray',
                'red',
            );
            CLI::newLine();

            return '';
        }

        $realpath = realpath($base);
        $base     = ($realpath !== false) ? $realpath : $base;

        $file = $base . DIRECTORY_SEPARATOR
            . str_replace(
                '\\',
                DIRECTORY_SEPARATOR,
                trim(str_replace($namespace . '\\', '', $class), '\\'),
            ) . '.php';

        return implode(
            DIRECTORY_SEPARATOR,
            array_slice(
                explode(DIRECTORY_SEPARATOR, $file),
                0,
                -1,
            ),
        ) . DIRECTORY_SEPARATOR . $this->basename($file);
    }

    
    protected function getNamespace(): string
    {
        return $this->namespace ?? trim(
            str_replace(
                '/',
                '\\',
                $this->getOption('namespace') ?? APP_NAMESPACE,
            ),
            '\\',
        );
    }

    
    protected function setHasClassName(bool $hasClassName)
    {
        $this->hasClassName = $hasClassName;

        return $this;
    }

    
    protected function setSortImports(bool $sortImports)
    {
        $this->sortImports = $sortImports;

        return $this;
    }

    
    protected function setEnabledSuffixing(bool $enabledSuffixing)
    {
        $this->enabledSuffixing = $enabledSuffixing;

        return $this;
    }

    
    protected function getOption(string $name): bool|string|null
    {
        if (! array_key_exists($name, $this->params)) {
            return CLI::getOption($name);
        }

        return $this->params[$name] ?? true;
    }
}
