<?php

declare(strict_types=1);



namespace CodeIgniter\Config;

use CodeIgniter\View\ViewDecoratorInterface;


class View extends BaseConfig
{
    
    public $saveData = true;

    
    public $filters = [];

    
    public $plugins = [];

    
    protected $coreFilters = [
        'abs'            => '\abs',
        'capitalize'     => '\CodeIgniter\View\Filters::capitalize',
        'date'           => '\CodeIgniter\View\Filters::date',
        'date_modify'    => '\CodeIgniter\View\Filters::date_modify',
        'default'        => '\CodeIgniter\View\Filters::default',
        'esc'            => '\CodeIgniter\View\Filters::esc',
        'excerpt'        => '\CodeIgniter\View\Filters::excerpt',
        'highlight'      => '\CodeIgniter\View\Filters::highlight',
        'highlight_code' => '\CodeIgniter\View\Filters::highlight_code',
        'limit_words'    => '\CodeIgniter\View\Filters::limit_words',
        'limit_chars'    => '\CodeIgniter\View\Filters::limit_chars',
        'local_currency' => '\CodeIgniter\View\Filters::local_currency',
        'local_number'   => '\CodeIgniter\View\Filters::local_number',
        'lower'          => '\strtolower',
        'nl2br'          => '\CodeIgniter\View\Filters::nl2br',
        'number_format'  => '\number_format',
        'prose'          => '\CodeIgniter\View\Filters::prose',
        'round'          => '\CodeIgniter\View\Filters::round',
        'strip_tags'     => '\strip_tags',
        'title'          => '\CodeIgniter\View\Filters::title',
        'upper'          => '\strtoupper',
    ];

    
    protected $corePlugins = [
        'csp_script_nonce'  => '\CodeIgniter\View\Plugins::cspScriptNonce',
        'csp_style_nonce'   => '\CodeIgniter\View\Plugins::cspStyleNonce',
        'current_url'       => '\CodeIgniter\View\Plugins::currentURL',
        'previous_url'      => '\CodeIgniter\View\Plugins::previousURL',
        'mailto'            => '\CodeIgniter\View\Plugins::mailto',
        'safe_mailto'       => '\CodeIgniter\View\Plugins::safeMailto',
        'lang'              => '\CodeIgniter\View\Plugins::lang',
        'validation_errors' => '\CodeIgniter\View\Plugins::validationErrors',
        'route'             => '\CodeIgniter\View\Plugins::route',
        'siteURL'           => '\CodeIgniter\View\Plugins::siteURL',
    ];

    
    public array $decorators = [];

    
    public function __construct()
    {
        $this->filters = array_merge($this->coreFilters, $this->filters);
        $this->plugins = array_merge($this->corePlugins, $this->plugins);

        parent::__construct();
    }
}
