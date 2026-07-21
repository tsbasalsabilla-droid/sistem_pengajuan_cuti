<?php

declare(strict_types=1);



namespace Kint;

use InvalidArgumentException;
use Kint\Parser\ConstructablePluginInterface;
use Kint\Parser\Parser;
use Kint\Parser\PluginInterface;
use Kint\Renderer\ConstructableRendererInterface;
use Kint\Renderer\RendererInterface;
use Kint\Renderer\TextRenderer;
use Kint\Value\Context\BaseContext;
use Kint\Value\Context\ContextInterface;
use Kint\Value\TraceFrameValue;
use Kint\Value\UninitializedValue;


class Kint implements FacadeInterface
{
    public const MODE_RICH = 'r';
    public const MODE_TEXT = 't';
    public const MODE_CLI = 'c';
    public const MODE_PLAIN = 'p';

    
    public static $enabled_mode = true;

    
    public static $mode_default = self::MODE_RICH;

    
    public static $mode_default_cli = self::MODE_CLI;

    
    public static bool $cli_detection = true;

    
    public static bool $return = false;

    
    public static int $depth_limit = 7;

    
    public static bool $expanded = false;

    
    public static bool $display_called_from = true;

    
    public static array $aliases = [
        [self::class, 'dump'],
        [self::class, 'trace'],
        [self::class, 'dumpAll'],
    ];

    
    public static array $renderers = [
        self::MODE_RICH => Renderer\RichRenderer::class,
        self::MODE_PLAIN => Renderer\PlainRenderer::class,
        self::MODE_TEXT => TextRenderer::class,
        self::MODE_CLI => Renderer\CliRenderer::class,
    ];

    
    public static array $plugins = [
        \Kint\Parser\ArrayLimitPlugin::class,
        \Kint\Parser\ArrayObjectPlugin::class,
        \Kint\Parser\Base64Plugin::class,
        \Kint\Parser\BinaryPlugin::class,
        \Kint\Parser\BlacklistPlugin::class,
        \Kint\Parser\ClassHooksPlugin::class,
        \Kint\Parser\ClassMethodsPlugin::class,
        \Kint\Parser\ClassStaticsPlugin::class,
        \Kint\Parser\ClassStringsPlugin::class,
        \Kint\Parser\ClosurePlugin::class,
        \Kint\Parser\ColorPlugin::class,
        \Kint\Parser\DateTimePlugin::class,
        \Kint\Parser\DomPlugin::class,
        \Kint\Parser\EnumPlugin::class,
        \Kint\Parser\FsPathPlugin::class,
        \Kint\Parser\HtmlPlugin::class,
        \Kint\Parser\IteratorPlugin::class,
        \Kint\Parser\JsonPlugin::class,
        \Kint\Parser\MicrotimePlugin::class,
        \Kint\Parser\MysqliPlugin::class,
        
        \Kint\Parser\SimpleXMLElementPlugin::class,
        \Kint\Parser\SplFileInfoPlugin::class,
        \Kint\Parser\StreamPlugin::class,
        \Kint\Parser\TablePlugin::class,
        \Kint\Parser\ThrowablePlugin::class,
        \Kint\Parser\TimestampPlugin::class,
        \Kint\Parser\ToStringPlugin::class,
        \Kint\Parser\TracePlugin::class,
        \Kint\Parser\XmlPlugin::class,
    ];

    protected Parser $parser;
    protected RendererInterface $renderer;

    public function __construct(Parser $p, RendererInterface $r)
    {
        $this->parser = $p;
        $this->renderer = $r;
    }

    public function setParser(Parser $p): void
    {
        $this->parser = $p;
    }

    public function getParser(): Parser
    {
        return $this->parser;
    }

    public function setRenderer(RendererInterface $r): void
    {
        $this->renderer = $r;
    }

    public function getRenderer(): RendererInterface
    {
        return $this->renderer;
    }

    public function setStatesFromStatics(array $statics): void
    {
        $this->renderer->setStatics($statics);

        $this->parser->setDepthLimit($statics['depth_limit'] ?? 0);
        $this->parser->clearPlugins();

        if (!isset($statics['plugins'])) {
            return;
        }

        $plugins = [];

        foreach ($statics['plugins'] as $plugin) {
            if ($plugin instanceof PluginInterface) {
                $plugins[] = $plugin;
            } elseif (\is_string($plugin) && \is_a($plugin, ConstructablePluginInterface::class, true)) {
                $plugins[] = new $plugin($this->parser);
            }
        }

        $plugins = $this->renderer->filterParserPlugins($plugins);

        foreach ($plugins as $plugin) {
            try {
                $this->parser->addPlugin($plugin);
            } catch (InvalidArgumentException $e) {
                \trigger_error(
                    'Plugin '.Utils::errorSanitizeString(\get_class($plugin)).' could not be added to a Kint parser: '.Utils::errorSanitizeString($e->getMessage()),
                    E_USER_WARNING
                );
            }
        }
    }

    public function setStatesFromCallInfo(array $info): void
    {
        $this->renderer->setCallInfo($info);

        if (isset($info['modifiers']) && \is_array($info['modifiers']) && \in_array('+', $info['modifiers'], true)) {
            $this->parser->setDepthLimit(0);
        }

        $this->parser->setCallerClass($info['caller']['class'] ?? null);
    }

    public function dumpAll(array $vars, array $base): string
    {
        if (\array_keys($vars) !== \array_keys($base)) {
            throw new InvalidArgumentException('Kint::dumpAll requires arrays of identical size and keys as arguments');
        }

        if ([] === $vars) {
            return $this->dumpNothing();
        }

        $output = $this->renderer->preRender();

        foreach ($vars as $key => $_) {
            if (!$base[$key] instanceof ContextInterface) {
                throw new InvalidArgumentException('Kint::dumpAll requires all elements of the second argument to be ContextInterface instances');
            }
            $output .= $this->dumpVar($vars[$key], $base[$key]);
        }

        $output .= $this->renderer->postRender();

        return $output;
    }

    protected function dumpNothing(): string
    {
        $output = $this->renderer->preRender();
        $output .= $this->renderer->render(new UninitializedValue(new BaseContext('No argument')));
        $output .= $this->renderer->postRender();

        return $output;
    }

    
    protected function dumpVar(&$var, ContextInterface $c): string
    {
        return $this->renderer->render(
            $this->parser->parse($var, $c)
        );
    }

    
    public static function getStatics(): array
    {
        return [
            'aliases' => static::$aliases,
            'cli_detection' => static::$cli_detection,
            'depth_limit' => static::$depth_limit,
            'display_called_from' => static::$display_called_from,
            'enabled_mode' => static::$enabled_mode,
            'expanded' => static::$expanded,
            'mode_default' => static::$mode_default,
            'mode_default_cli' => static::$mode_default_cli,
            'plugins' => static::$plugins,
            'renderers' => static::$renderers,
            'return' => static::$return,
        ];
    }

    
    public static function createFromStatics(array $statics): ?FacadeInterface
    {
        $mode = false;

        if (isset($statics['enabled_mode'])) {
            $mode = $statics['enabled_mode'];

            if (true === $mode && isset($statics['mode_default'])) {
                $mode = $statics['mode_default'];

                if (PHP_SAPI === 'cli' && !empty($statics['cli_detection']) && isset($statics['mode_default_cli'])) {
                    $mode = $statics['mode_default_cli'];
                }
            }
        }

        if (false === $mode) {
            return null;
        }

        $renderer = null;
        if (isset($statics['renderers'][$mode])) {
            if ($statics['renderers'][$mode] instanceof RendererInterface) {
                $renderer = $statics['renderers'][$mode];
            }

            if (\is_a($statics['renderers'][$mode], ConstructableRendererInterface::class, true)) {
                $renderer = new $statics['renderers'][$mode]();
            }
        }

        $renderer ??= new TextRenderer();

        return new static(new Parser(), $renderer);
    }

    
    public static function getBasesFromParamInfo(array $params, int $argc): array
    {
        $bases = [];

        for ($i = 0; $i < $argc; ++$i) {
            $param = $params[$i] ?? null;

            if (!empty($param['literal'])) {
                $name = 'literal';
            } else {
                $name = $param['name'] ?? '$'.$i;
            }

            if (isset($param['path'])) {
                $access_path = $param['path'];

                if ($param['expression']) {
                    $access_path = '('.$access_path.')';
                } elseif ($param['new_without_parens']) {
                    $access_path .= '()';
                }
            } else {
                $access_path = '$'.$i;
            }

            $base = new BaseContext($name);
            $base->access_path = $access_path;
            $bases[] = $base;
        }

        return $bases;
    }

    
    public static function getCallInfo(array $aliases, array $trace, array $args): array
    {
        $found = false;
        $callee = null;
        $caller = null;
        $miniTrace = [];

        foreach ($trace as $frame) {
            if (Utils::traceFrameIsListed($frame, $aliases)) {
                $found = true;
                $miniTrace = [];
            }

            if (!Utils::traceFrameIsListed($frame, ['spl_autoload_call'])) {
                $miniTrace[] = $frame;
            }
        }

        if ($found) {
            $callee = \reset($miniTrace) ?: null;
            $caller = \next($miniTrace) ?: null;
        }

        foreach ($miniTrace as $index => $frame) {
            if ((0 === $index && $callee === $frame) || isset($frame['file'], $frame['line'])) {
                unset($frame['object'], $frame['args']);
                $miniTrace[$index] = $frame;
            } else {
                unset($miniTrace[$index]);
            }
        }

        $miniTrace = \array_values($miniTrace);

        $call = static::getSingleCall($callee ?: [], $args);

        $ret = [
            'params' => null,
            'modifiers' => [],
            'callee' => $callee,
            'caller' => $caller,
            'trace' => $miniTrace,
        ];

        if (null !== $call) {
            $ret['params'] = $call['parameters'];
            $ret['modifiers'] = $call['modifiers'];
        }

        return $ret;
    }

    
    public static function trace()
    {
        if (false === static::$enabled_mode) {
            return 0;
        }

        static::$aliases = Utils::normalizeAliases(static::$aliases);

        $call_info = static::getCallInfo(static::$aliases, \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), []);

        $statics = static::getStatics();

        if (\in_array('~', $call_info['modifiers'], true)) {
            $statics['enabled_mode'] = static::MODE_TEXT;
        }

        $kintstance = static::createFromStatics($statics);
        if (!$kintstance) {
            return 0;
        }

        if (\in_array('-', $call_info['modifiers'], true)) {
            while (\ob_get_level()) {
                \ob_end_clean();
            }
        }

        $kintstance->setStatesFromStatics($statics);
        $kintstance->setStatesFromCallInfo($call_info);

        $trimmed_trace = [];
        $trace = \debug_backtrace();

        foreach ($trace as $frame) {
            if (Utils::traceFrameIsListed($frame, static::$aliases)) {
                $trimmed_trace = [];
            }

            $trimmed_trace[] = $frame;
        }

        \array_shift($trimmed_trace);

        $base = new BaseContext('Kint\\Kint::trace()');
        $base->access_path = 'debug_backtrace()';
        $output = $kintstance->dumpAll([$trimmed_trace], [$base]);

        if (static::$return || \in_array('@', $call_info['modifiers'], true)) {
            return $output;
        }

        echo $output;

        if (\in_array('-', $call_info['modifiers'], true)) {
            \flush(); 
        }

        return 0;
    }

    
    public static function dump(...$args)
    {
        if (false === static::$enabled_mode) {
            return 0;
        }

        static::$aliases = Utils::normalizeAliases(static::$aliases);

        $call_info = static::getCallInfo(static::$aliases, \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), $args);

        $statics = static::getStatics();

        if (\in_array('~', $call_info['modifiers'], true)) {
            $statics['enabled_mode'] = static::MODE_TEXT;
        }

        $kintstance = static::createFromStatics($statics);
        if (!$kintstance) {
            return 0;
        }

        if (\in_array('-', $call_info['modifiers'], true)) {
            while (\ob_get_level()) {
                \ob_end_clean();
            }
        }

        $kintstance->setStatesFromStatics($statics);
        $kintstance->setStatesFromCallInfo($call_info);

        $bases = static::getBasesFromParamInfo($call_info['params'] ?? [], \count($args));
        $output = $kintstance->dumpAll(\array_values($args), $bases);

        if (static::$return || \in_array('@', $call_info['modifiers'], true)) {
            return $output;
        }

        echo $output;

        if (\in_array('-', $call_info['modifiers'], true)) {
            \flush(); 
        }

        return 0;
    }

    
    protected static function getSingleCall(array $frame, array $args): ?array
    {
        if (
            !isset($frame['file'], $frame['line'], $frame['function']) ||
            !\is_readable($frame['file']) ||
            false === ($source = \file_get_contents($frame['file']))
        ) {
            return null;
        }

        if (empty($frame['class'])) {
            $callfunc = $frame['function'];
        } else {
            $callfunc = [$frame['class'], $frame['function']];
        }

        $calls = CallFinder::getFunctionCalls($source, $frame['line'], $callfunc);

        $argc = \count($args);

        $return = null;

        foreach ($calls as $call) {
            $is_unpack = false;

            
            foreach ($call['parameters'] as $i => &$param) {
                if (0 === \strpos($param['name'], '...')) {
                    $is_unpack = true;

                    
                    if ($i < $argc && $i === \count($call['parameters']) - 1) {
                        unset($call['parameters'][$i]);

                        if (Utils::isAssoc($args)) {
                            
                            $keys = \array_slice(\array_keys($args), $i);

                            foreach ($keys as $key) {
                                $call['parameters'][] = [
                                    'name' => ((string) \substr($param['name'], 3)).'['.\var_export($key, true).']',
                                    'path' => ((string) \substr($param['path'], 3)).'['.\var_export($key, true).']',
                                    'expression' => false,
                                    'literal' => false,
                                    'new_without_parens' => false,
                                ];
                            }
                        } else {
                            
                            
                            for ($j = 0; $j + $i < $argc; ++$j) {
                                $call['parameters'][] = [
                                    'name' => 'array_values('.((string) \substr($param['name'], 3)).')['.$j.']',
                                    'path' => 'array_values('.((string) \substr($param['path'], 3)).')['.$j.']',
                                    'expression' => false,
                                    'literal' => false,
                                    'new_without_parens' => false,
                                ];
                            }
                        }

                        $call['parameters'] = \array_values($call['parameters']);
                    } else {
                        $call['parameters'] = \array_slice($call['parameters'], 0, $i);
                    }

                    break;
                }

                if ($i >= $argc) {
                    continue 2;
                }
            }

            if ($is_unpack || \count($call['parameters']) === $argc) {
                if (null === $return) {
                    $return = $call;
                } else {
                    
                    
                    return null;
                }
            }
        }

        return $return;
    }
}
