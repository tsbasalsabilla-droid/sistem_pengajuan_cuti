<?php

declare(strict_types=1);



namespace Kint\Renderer;

use Kint\Renderer\Rich\TabPluginInterface;
use Kint\Renderer\Rich\ValuePluginInterface;
use Kint\Utils;
use Kint\Value\AbstractValue;
use Kint\Value\Context\ClassDeclaredContext;
use Kint\Value\Context\ContextInterface;
use Kint\Value\Context\PropertyContext;
use Kint\Value\InstanceValue;
use Kint\Value\Representation;
use Kint\Value\Representation\ContainerRepresentation;
use Kint\Value\Representation\RepresentationInterface;
use Kint\Value\Representation\StringRepresentation;
use Kint\Value\Representation\ValueRepresentation;
use Kint\Value\StringValue;


class RichRenderer extends AbstractRenderer
{
    use AssetRendererTrait;

    
    public static array $value_plugins = [
        'array_limit' => Rich\LockPlugin::class,
        'blacklist' => Rich\LockPlugin::class,
        'callable' => Rich\CallablePlugin::class,
        'color' => Rich\ColorPlugin::class,
        'depth_limit' => Rich\LockPlugin::class,
        'recursion' => Rich\LockPlugin::class,
        'trace_frame' => Rich\TraceFramePlugin::class,
    ];

    
    public static array $tab_plugins = [
        'binary' => Rich\BinaryPlugin::class,
        'callable' => Rich\CallableDefinitionPlugin::class,
        'color' => Rich\ColorPlugin::class,
        'microtime' => Rich\MicrotimePlugin::class,
        'profiling' => Rich\ProfilePlugin::class,
        'source' => Rich\SourcePlugin::class,
        'table' => Rich\TablePlugin::class,
    ];

    public static array $pre_render_sources = [
        'script' => [
            [self::class, 'renderJs'],
        ],
        'style' => [
            [self::class, 'renderCss'],
        ],
        'raw' => [],
    ];

    
    public static int $strlen_max = 80;

    
    public static ?string $timestamp = null;

    
    public static bool $access_paths = true;

    
    public static bool $escape_types = false;

    
    public static bool $folder = false;

    public static bool $needs_pre_render = true;
    public static bool $always_pre_render = false;

    protected array $plugin_objs = [];
    protected bool $expand = false;
    protected bool $force_pre_render = false;
    protected bool $use_folder = false;

    public function __construct()
    {
        parent::__construct();
        self::$theme ??= 'original.css';
        $this->use_folder = self::$folder;
        $this->force_pre_render = self::$always_pre_render;
    }

    public function setCallInfo(array $info): void
    {
        parent::setCallInfo($info);

        if (\in_array('!', $info['modifiers'], true)) {
            $this->expand = true;
            $this->use_folder = false;
        }

        if (\in_array('@', $info['modifiers'], true)) {
            $this->force_pre_render = true;
        }
    }

    public function setStatics(array $statics): void
    {
        parent::setStatics($statics);

        if (!empty($statics['expanded'])) {
            $this->expand = true;
        }

        if (!empty($statics['return'])) {
            $this->force_pre_render = true;
        }
    }

    public function shouldPreRender(): bool
    {
        return $this->force_pre_render || self::$needs_pre_render;
    }

    public function render(AbstractValue $v): string
    {
        $render_spl_ids_stash = $this->render_spl_ids;

        if ($this->render_spl_ids && $v->flags & AbstractValue::FLAG_GENERATED) {
            $this->render_spl_ids = false;
        }

        if ($plugin = $this->getValuePlugin($v)) {
            $output = $plugin->renderValue($v);
            if (null !== $output && \strlen($output)) {
                if (!$this->render_spl_ids && $render_spl_ids_stash) {
                    $this->render_spl_ids = true;
                }

                return $output;
            }
        }

        $children = $this->renderChildren($v);
        $header = $this->renderHeaderWrapper($v->getContext(), (bool) \strlen($children), $this->renderHeader($v));

        if (!$this->render_spl_ids && $render_spl_ids_stash) {
            $this->render_spl_ids = true;
        }

        return '<dl>'.$header.$children.'</dl>';
    }

    public function renderHeaderWrapper(ContextInterface $c, bool $has_children, string $contents): string
    {
        $out = '<dt';

        if ($has_children) {
            $out .= ' class="kint-parent';

            if ($this->expand) {
                $out .= ' kint-show';
            }

            $out .= '"';
        }

        $out .= '>';

        if (self::$access_paths && $c->getDepth() > 0 && null !== ($ap = $c->getAccessPath())) {
            $out .= '<span class="kint-access-path-trigger" title="Show access path"></span>';
        }

        if ($has_children) {
            if (0 === $c->getDepth()) {
                if (!$this->use_folder) {
                    $out .= '<span class="kint-folder-trigger" title="Move to folder"></span>';
                }
                $out .= '<span class="kint-search-trigger" title="Show search box"></span>';
                $out .= '<input type="text" class="kint-search" value="">';
            }

            $out .= '<nav></nav>';
        }

        $out .= $contents;

        if (!empty($ap)) {
            $out .= '<div class="access-path">'.$this->escape($ap).'</div>';
        }

        return $out.'</dt>';
    }

    public function renderHeader(AbstractValue $v): string
    {
        $c = $v->getContext();

        $output = '';

        if ($c instanceof ClassDeclaredContext) {
            $output .= '<var>'.$c->getModifiers().'</var> ';
        }

        $output .= '<dfn>'.$this->escape($v->getDisplayName()).'</dfn> ';

        if ($c instanceof PropertyContext && null !== ($s = $c->getHooks())) {
            $output .= '<var>'.$this->escape($s).'</var> ';
        }

        if (null !== ($s = $c->getOperator())) {
            $output .= $this->escape($s, 'ASCII').' ';
        }

        $s = $v->getDisplayType();
        if (self::$escape_types) {
            $s = $this->escape($s);
        }

        if ($c->isRef()) {
            $s = '&amp;'.$s;
        }

        $output .= '<var>'.$s.'</var>';

        if ($v instanceof InstanceValue && $this->shouldRenderObjectIds()) {
            $output .= '#'.$v->getSplObjectId();
        }

        $output .= ' ';

        if (null !== ($s = $v->getDisplaySize())) {
            if (self::$escape_types) {
                $s = $this->escape($s);
            }
            $output .= '('.$s.') ';
        }

        if (null !== ($s = $v->getDisplayValue())) {
            $s = (string) \preg_replace('/\\s+/', ' ', $s);

            if (self::$strlen_max) {
                $s = Utils::truncateString($s, self::$strlen_max);
            }

            $output .= $this->escape($s);
        }

        return \trim($output);
    }

    public function renderChildren(AbstractValue $v): string
    {
        $contents = [];
        $tabs = [];

        foreach ($v->getRepresentations() as $rep) {
            $result = $this->renderTab($v, $rep);
            if (\strlen($result)) {
                $contents[] = $result;
                $tabs[] = $rep;
            }
        }

        if (empty($tabs)) {
            return '';
        }

        $output = '<dd>';

        if (1 === \count($tabs) && $tabs[0]->labelIsImplicit()) {
            $output .= (string) \reset($contents);
        } else {
            $output .= '<ul class="kint-tabs">';

            foreach ($tabs as $i => $tab) {
                if (0 === $i) {
                    $output .= '<li class="kint-active-tab">';
                } else {
                    $output .= '<li>';
                }

                $output .= $this->escape($tab->getLabel()).'</li>';
            }

            $output .= '</ul><ul class="kint-tab-contents">';

            foreach ($contents as $i => $tab) {
                if (0 === $i) {
                    $output .= '<li class="kint-show">';
                } else {
                    $output .= '<li>';
                }

                $output .= $tab.'</li>';
            }

            $output .= '</ul>';
        }

        return $output.'</dd>';
    }

    public function preRender(): string
    {
        $output = '';

        if ($this->shouldPreRender()) {
            foreach (self::$pre_render_sources as $type => $values) {
                $contents = '';
                foreach ($values as $v) {
                    $contents .= \call_user_func($v, $this);
                }

                if (!\strlen($contents)) {
                    continue;
                }

                switch ($type) {
                    case 'script':
                        $output .= '<script class="kint-rich-script"';
                        if (null !== self::$js_nonce) {
                            $output .= ' nonce="'.\htmlspecialchars(self::$js_nonce).'"';
                        }
                        $output .= '>'.$contents.'</script>';
                        break;
                    case 'style':
                        $output .= '<style class="kint-rich-style"';
                        if (null !== self::$css_nonce) {
                            $output .= ' nonce="'.\htmlspecialchars(self::$css_nonce).'"';
                        }
                        $output .= '>'.$contents.'</style>';
                        break;
                    default:
                        $output .= $contents;
                }
            }

            
            if (!$this->force_pre_render) {
                self::$needs_pre_render = false;
            }
        }

        $output .= '<div class="kint-rich';

        if ($this->use_folder) {
            $output .= ' kint-file';
        }

        $output .= '">';

        return $output;
    }

    public function postRender(): string
    {
        if (!$this->show_trace) {
            return '</div>';
        }

        $output = '<footer';

        if ($this->expand) {
            $output .= ' class="kint-show"';
        }

        $output .= '>';

        if (!$this->use_folder) {
            $output .= '<span class="kint-folder-trigger" title="Move to folder">&mapstodown;</span>';
        }

        if (!empty($this->trace) && \count($this->trace) > 1) {
            $output .= '<nav></nav>';
        }

        $output .= $this->calledFrom();

        if (!empty($this->trace) && \count($this->trace) > 1) {
            $output .= '<ol>';
            foreach ($this->trace as $index => $step) {
                if (!$index) {
                    continue;
                }

                $output .= '<li>'.$this->ideLink($step['file'], $step['line']); 
                if (isset($step['function']) &&
                    !\in_array($step['function'], ['include', 'include_once', 'require', 'require_once'], true)
                ) {
                    $output .= ' [';
                    $output .= $step['class'] ?? '';
                    $output .= $step['type'] ?? '';
                    $output .= $step['function'].'()]';
                }
            }
            $output .= '</ol>';
        }

        $output .= '</footer></div>';

        return $output;
    }

    
    public function escape(string $string, $encoding = false): string
    {
        if (false === $encoding) {
            $encoding = Utils::detectEncoding($string);
        }

        $original_encoding = $encoding;

        if (false === $encoding || 'ASCII' === $encoding) {
            $encoding = 'UTF-8';
        }

        $string = \htmlspecialchars($string, ENT_NOQUOTES, $encoding);

        
        if (\function_exists('mb_encode_numericentity') && 'ASCII' !== $original_encoding) {
            $string = \mb_encode_numericentity($string, [0x80, 0xFFFF, 0, 0xFFFF], $encoding);
        }

        return $string;
    }

    public function ideLink(string $file, int $line): string
    {
        $path = $this->escape(Utils::shortenPath($file)).':'.$line;
        $ideLink = self::getFileLink($file, $line);

        if (null === $ideLink) {
            return $path;
        }

        return '<a href="'.$this->escape($ideLink).'">'.$path.'</a>';
    }

    protected function calledFrom(): string
    {
        $output = '';

        if (isset($this->callee['file'])) {
            $output .= ' '.$this->ideLink(
                $this->callee['file'],
                $this->callee['line']
            );
        }

        if (
            isset($this->callee['function']) &&
            (
                !empty($this->callee['class']) ||
                !\in_array(
                    $this->callee['function'],
                    ['include', 'include_once', 'require', 'require_once'],
                    true
                )
            )
        ) {
            $output .= ' [';
            $output .= $this->callee['class'] ?? '';
            $output .= $this->callee['type'] ?? '';
            $output .= $this->callee['function'].'()]';
        }

        if ('' !== $output) {
            $output = 'Called from'.$output;
        }

        if (null !== self::$timestamp) {
            $output .= ' '.\date(self::$timestamp);
        }

        return $output;
    }

    protected function renderTab(AbstractValue $v, RepresentationInterface $rep): string
    {
        if ($plugin = $this->getTabPlugin($rep)) {
            $output = $plugin->renderTab($rep, $v);
            if (null !== $output) {
                return $output;
            }
        }

        if ($rep instanceof ValueRepresentation) {
            return $this->render($rep->getValue());
        }

        if ($rep instanceof ContainerRepresentation) {
            $output = '';

            foreach ($rep->getContents() as $obj) {
                $output .= $this->render($obj);
            }

            return $output;
        }

        if ($rep instanceof StringRepresentation) {
            
            if ($v instanceof StringValue && $rep->getValue() === $v->getValue()) {
                
                if (\preg_match('/(:?[\\r\\n\\t\\f\\v]| {2})/', $rep->getValue())) {
                    
                    $show_contents = true;
                } elseif (self::$strlen_max && Utils::strlen($v->getDisplayValue()) > self::$strlen_max) {
                    
                    $show_contents = true;
                } else {
                    $show_contents = false;
                }
            } else {
                $show_contents = true;
            }

            if ($show_contents) {
                return '<pre>'.$this->escape($rep->getValue())."\n</pre>";
            }
        }

        return '';
    }

    protected function getValuePlugin(AbstractValue $v): ?ValuePluginInterface
    {
        $hint = $v->getHint();

        if (null === $hint || !isset(self::$value_plugins[$hint])) {
            return null;
        }

        $plugin = self::$value_plugins[$hint];

        if (!\is_a($plugin, ValuePluginInterface::class, true)) {
            return null;
        }

        if (!isset($this->plugin_objs[$plugin])) {
            $this->plugin_objs[$plugin] = new $plugin($this);
        }

        return $this->plugin_objs[$plugin];
    }

    protected function getTabPlugin(RepresentationInterface $r): ?TabPluginInterface
    {
        $hint = $r->getHint();

        if (null === $hint || !isset(self::$tab_plugins[$hint])) {
            return null;
        }

        $plugin = self::$tab_plugins[$hint];

        if (!\is_a($plugin, TabPluginInterface::class, true)) {
            return null;
        }

        if (!isset($this->plugin_objs[$plugin])) {
            $this->plugin_objs[$plugin] = new $plugin($this);
        }

        return $this->plugin_objs[$plugin];
    }
}
