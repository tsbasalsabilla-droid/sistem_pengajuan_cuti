<?php

declare(strict_types=1);



namespace CodeIgniter\Debug;

use CodeIgniter\CodeIgniter;
use CodeIgniter\Debug\Toolbar\Collectors\BaseCollector;
use CodeIgniter\Debug\Toolbar\Collectors\Config;
use CodeIgniter\Debug\Toolbar\Collectors\History;
use CodeIgniter\Format\JSONFormatter;
use CodeIgniter\Format\XMLFormatter;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\Header;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\I18n\Time;
use Config\Toolbar as ToolbarConfig;
use Kint\Kint;


class Toolbar
{
    
    protected $config;

    
    protected $collectors = [];

    public function __construct(ToolbarConfig $config)
    {
        $this->config = $config;

        foreach ($config->collectors as $collector) {
            if (! class_exists($collector)) {
                log_message(
                    'critical',
                    'Toolbar collector does not exist (' . $collector . ').'
                    . ' Please check $collectors in the app/Config/Toolbar.php file.',
                );

                continue;
            }

            $this->collectors[] = new $collector();
        }
    }

    
    public function run(float $startTime, float $totalTime, RequestInterface $request, ResponseInterface $response): string
    {
        $data = [];
        
        $data['url']             = current_url();
        $data['method']          = $request->getMethod();
        $data['isAJAX']          = $request->isAJAX();
        $data['startTime']       = $startTime;
        $data['totalTime']       = $totalTime * 1000;
        $data['totalMemory']     = number_format(memory_get_peak_usage() / 1024 / 1024, 3);
        $data['segmentDuration'] = $this->roundTo($data['totalTime'] / 7);
        $data['segmentCount']    = (int) ceil($data['totalTime'] / $data['segmentDuration']);
        $data['CI_VERSION']      = CodeIgniter::CI_VERSION;
        $data['collectors']      = [];

        foreach ($this->collectors as $collector) {
            $data['collectors'][] = $collector->getAsArray();
        }

        foreach ($this->collectVarData() as $heading => $items) {
            $varData = [];

            if (is_array($items)) {
                foreach ($items as $key => $value) {
                    if (is_string($value)) {
                        $varData[esc($key)] = esc($value);
                    } else {
                        $oldKintMode       = Kint::$mode_default;
                        $oldKintCalledFrom = Kint::$display_called_from;

                        Kint::$mode_default        = Kint::MODE_RICH;
                        Kint::$display_called_from = false;

                        $kint = @Kint::dump($value);
                        $kint = substr($kint, strpos($kint, '</style>') + 8);

                        Kint::$mode_default        = $oldKintMode;
                        Kint::$display_called_from = $oldKintCalledFrom;

                        $varData[esc($key)] = $kint;
                    }
                }
            }

            $data['vars']['varData'][esc($heading)] = $varData;
        }

        if (isset($_SESSION)) {
            foreach ($_SESSION as $key => $value) {
                
                if (is_string($value) && preg_match('~[^\x20-\x7E\t\r\n]~', $value)) {
                    $value = 'binary data';
                }

                $data['vars']['session'][esc($key)] = is_string($value) ? esc($value) : '<pre>' . esc(print_r($value, true)) . '</pre>';
            }
        }

        foreach ($request->getGet() as $name => $value) {
            $data['vars']['get'][esc($name)] = is_array($value) ? '<pre>' . esc(print_r($value, true)) . '</pre>' : esc($value);
        }

        foreach ($request->getPost() as $name => $value) {
            $data['vars']['post'][esc($name)] = is_array($value) ? '<pre>' . esc(print_r($value, true)) . '</pre>' : esc($value);
        }

        foreach ($request->headers() as $name => $value) {
            if ($value instanceof Header) {
                $data['vars']['headers'][esc($name)] = esc($value->getValueLine());
            } else {
                foreach ($value as $i => $header) {
                    $index = $i + 1;
                    $data['vars']['headers'][esc($name)] ??= '';
                    $data['vars']['headers'][esc($name)] .= ' (' . $index . ') '
                        . esc($header->getValueLine());
                }
            }
        }

        foreach ($request->getCookie() as $name => $value) {
            $data['vars']['cookies'][esc($name)] = esc($value);
        }

        $data['vars']['request'] = ($request->isSecure() ? 'HTTPS' : 'HTTP') . '/' . $request->getProtocolVersion();

        $data['vars']['response'] = [
            'statusCode'  => $response->getStatusCode(),
            'reason'      => esc($response->getReasonPhrase()),
            'contentType' => esc($response->getHeaderLine('content-type')),
            'headers'     => [],
        ];

        foreach ($response->headers() as $name => $value) {
            if ($value instanceof Header) {
                $data['vars']['response']['headers'][esc($name)] = esc($value->getValueLine());
            } else {
                foreach ($value as $i => $header) {
                    $index = $i + 1;
                    $data['vars']['response']['headers'][esc($name)] ??= '';
                    $data['vars']['response']['headers'][esc($name)] .= ' (' . $index . ') '
                        . esc($header->getValueLine());
                }
            }
        }

        $data['config'] = Config::display();

        $response->getCSP()->addImageSrc('data:');

        return json_encode($data);
    }

    
    protected function renderTimeline(array $collectors, float $startTime, int $segmentCount, int $segmentDuration, array &$styles): string
    {
        $rows       = $this->collectTimelineData($collectors);
        $styleCount = 0;

        
        return $this->renderTimelineRecursive($rows, $startTime, $segmentCount, $segmentDuration, $styles, $styleCount);
    }

    
    protected function renderTimelineRecursive(array $rows, float $startTime, int $segmentCount, int $segmentDuration, array &$styles, int &$styleCount, int $level = 0, bool $isChild = false): string
    {
        $displayTime = $segmentCount * $segmentDuration;

        $output = '';

        foreach ($rows as $row) {
            $hasChildren = isset($row['children']) && ! empty($row['children']);
            $isQuery     = isset($row['query']) && ! empty($row['query']);

            
            $open = $row['name'] === 'Controller';

            if ($hasChildren || $isQuery) {
                $output .= '<tr class="timeline-parent' . ($open ? ' timeline-parent-open' : '') . '" id="timeline-' . $styleCount . '_parent" data-toggle="childrows" data-child="timeline-' . $styleCount . '">';
            } else {
                $output .= '<tr>';
            }

            $output .= '<td class="' . ($isChild ? 'debug-bar-width30' : '') . ' debug-bar-level-' . $level . '" >' . ($hasChildren || $isQuery ? '<nav></nav>' : '') . $row['name'] . '</td>';
            $output .= '<td class="' . ($isChild ? 'debug-bar-width10' : '') . '">' . $row['component'] . '</td>';
            $output .= '<td class="' . ($isChild ? 'debug-bar-width10 ' : '') . 'debug-bar-alignRight">' . number_format($row['duration'] * 1000, 2) . ' ms</td>';
            $output .= "<td class='debug-bar-noverflow' colspan='{$segmentCount}'>";

            $offset = ((((float) $row['start'] - $startTime) * 1000) / $displayTime) * 100;
            $length = (((float) $row['duration'] * 1000) / $displayTime) * 100;

            $styles['debug-bar-timeline-' . $styleCount] = "left: {$offset}%; width: {$length}%;";

            $output .= "<span class='timer debug-bar-timeline-{$styleCount}' title='" . number_format($length, 2) . "%'></span>";
            $output .= '</td>';
            $output .= '</tr>';

            $styleCount++;

            
            if ($hasChildren || $isQuery) {
                $output .= '<tr class="child-row ' . ($open ? '' : ' debug-bar-ndisplay') . '" id="timeline-' . ($styleCount - 1) . '_children" >';
                $output .= '<td colspan="' . ($segmentCount + 3) . '" class="child-container">';
                $output .= '<table class="timeline">';
                $output .= '<tbody>';

                if ($isQuery) {
                    
                    $output .= '<tr>';
                    $output .= '<td class="query-container debug-bar-level-' . ($level + 1) . '" >' . $row['query'] . '</td>';
                    $output .= '</tr>';
                } else {
                    
                    $output .= $this->renderTimelineRecursive($row['children'], $startTime, $segmentCount, $segmentDuration, $styles, $styleCount, $level + 1, true);
                }

                $output .= '</tbody>';
                $output .= '</table>';
                $output .= '</td>';
                $output .= '</tr>';
            }
        }

        return $output;
    }

    
    protected function collectTimelineData($collectors): array
    {
        $data = [];

        
        foreach ($collectors as $collector) {
            if (! $collector['hasTimelineData']) {
                continue;
            }

            $data = array_merge($data, $collector['timelineData']);
        }

        
        $sortArray = [
            array_column($data, 'start'), SORT_NUMERIC, SORT_ASC,
            array_column($data, 'duration'), SORT_NUMERIC, SORT_DESC,
            &$data,
        ];

        array_multisort(...$sortArray);

        
        array_walk($data, static function (&$row): void {
            $row['end'] = $row['start'] + $row['duration'];
        });

        
        $data = $this->structureTimelineData($data);

        return $data;
    }

    
    protected function structureTimelineData(array $elements): array
    {
        
        $element = array_shift($elements);

        
        while ($elements !== [] && $elements[array_key_first($elements)]['end'] <= $element['end']) {
            $element['children'][] = array_shift($elements);
        }

        
        if (isset($element['children'])) {
            $element['children'] = $this->structureTimelineData($element['children']);
        }

        
        if ($elements === []) {
            return [$element];
        }

        
        return array_merge([$element], $this->structureTimelineData($elements));
    }

    
    protected function collectVarData(): array
    {
        if (! ($this->config->collectVarData ?? true)) {
            return [];
        }

        $data = [];

        foreach ($this->collectors as $collector) {
            if (! $collector->hasVarData()) {
                continue;
            }

            $data = array_merge($data, $collector->getVarData());
        }

        return $data;
    }

    
    protected function roundTo(float $number, int $increments = 5): float
    {
        $increments = 1 / $increments;

        return ceil($number * $increments) / $increments;
    }

    
    public function prepare(?RequestInterface $request = null, ?ResponseInterface $response = null): void
    {
        
        if (CI_DEBUG && ! is_cli()) {
            if ($this->hasNativeHeaderConflict()) {
                return;
            }

            $app = service('codeigniter');

            $request ??= service('request');
            
            $response ??= service('response');

            
            if ($response instanceof DownloadResponse) {
                return;
            }

            $toolbar = service('toolbar', $this->config);
            $stats   = $app->getPerformanceStats();
            $data    = $toolbar->run(
                $stats['startTime'],
                $stats['totalTime'],
                $request,
                $response,
            );

            helper('filesystem');

            
            $time = sprintf('%.6F', Time::now()->format('U.u'));

            if (! is_dir(WRITEPATH . 'debugbar')) {
                mkdir(WRITEPATH . 'debugbar', 0777);
            }

            write_file(WRITEPATH . 'debugbar/debugbar_' . $time . '.json', $data, 'w+');

            $format = $response->getHeaderLine('content-type');

            
            
            
            if ($this->shouldDisableToolbar($request) || ! str_contains($format, 'html')) {
                $response->setHeader('Debugbar-Time', "{$time}")
                    ->setHeader('Debugbar-Link', site_url("?debugbar_time={$time}"));

                return;
            }

            $oldKintMode        = Kint::$mode_default;
            Kint::$mode_default = Kint::MODE_RICH;
            $kintScript         = @Kint::dump('');
            Kint::$mode_default = $oldKintMode;
            $kintScript         = substr($kintScript, 0, strpos($kintScript, '</style>') + 8);
            $kintScript         = ($kintScript === '0') ? '' : $kintScript;

            $script = PHP_EOL
                . '<script ' . csp_script_nonce() . ' id="debugbar_loader" '
                . 'data-time="' . $time . '" '
                . 'src="' . site_url() . '?debugbar"></script>'
                . '<script ' . csp_script_nonce() . ' id="debugbar_dynamic_script"></script>'
                . '<style ' . csp_style_nonce() . ' id="debugbar_dynamic_style"></style>'
                . $kintScript
                . PHP_EOL;

            if (str_contains((string) $response->getBody(), '<head>')) {
                $response->setBody(
                    preg_replace(
                        '/<head>/',
                        '<head>' . $script,
                        $response->getBody(),
                        1,
                    ),
                );

                return;
            }

            $response->appendBody($script);
        }
    }

    
    public function respond(): void
    {
        if (ENVIRONMENT === 'testing') {
            return;
        }

        $request = service('request');

        
        
        if ($request->getGet('debugbar') !== null) {
            header('Content-Type: application/javascript');

            ob_start();
            include $this->config->viewsPath . 'toolbarloader.js';
            $output = ob_get_clean();
            $output = str_replace('{url}', rtrim(site_url(), '/'), $output);
            echo $output;

            exit;
        }

        
        
        if ($request->getGet('debugbar_time')) {
            helper('security');

            
            $format = $request->negotiate('media', ['text/html', 'application/json', 'application/xml']);
            $format = explode('/', $format)[1];

            $filename = sanitize_filename('debugbar_' . $request->getGet('debugbar_time'));
            $filename = WRITEPATH . 'debugbar/' . $filename . '.json';

            if (is_file($filename)) {
                
                echo $this->format(file_get_contents($filename), $format);

                exit;
            }

            
            http_response_code(404);

            exit; 
        }
    }

    
    protected function format(string $data, string $format = 'html'): string
    {
        $data = json_decode($data, true);

        if (preg_match('/\d+\.\d{6}/s', (string) service('request')->getGet('debugbar_time'), $debugbarTime)) {
            $history = new History();
            $history->setFiles(
                $debugbarTime[0],
                $this->config->maxHistory,
            );

            $data['collectors'][] = $history->getAsArray();
        }

        $output = '';

        switch ($format) {
            case 'html':
                $data['styles'] = [];
                extract($data);
                $parser = service('parser', $this->config->viewsPath, null, false);
                ob_start();
                include $this->config->viewsPath . 'toolbar.tpl.php';
                $output = ob_get_clean();
                break;

            case 'json':
                $formatter = new JSONFormatter();
                $output    = $formatter->format($data);
                break;

            case 'xml':
                $formatter = new XMLFormatter();
                $output    = $formatter->format($data);
                break;
        }

        return $output;
    }

    
    protected function hasNativeHeaderConflict(): bool
    {
        
        if (headers_sent()) {
            return true;
        }

        
        foreach (headers_list() as $header) {
            $lowerHeader = strtolower($header);

            $isNonHtmlContent = str_starts_with($lowerHeader, 'content-type:') && ! str_contains($lowerHeader, 'text/html');
            $isAttachment     = str_starts_with($lowerHeader, 'content-disposition:') && str_contains($lowerHeader, 'attachment');

            if ($isNonHtmlContent || $isAttachment) {
                return true;
            }
        }

        return false;
    }

    
    private function shouldDisableToolbar(IncomingRequest $request): bool
    {
        
        $headers = $this->config->disableOnHeaders ?? ['X-Requested-With' => 'xmlhttprequest'];

        foreach ($headers as $headerName => $expectedValue) {
            if (! $request->hasHeader($headerName)) {
                continue; 
            }

            
            if ($expectedValue === null) {
                return true;
            }

            $headerValue = strtolower($request->getHeaderLine($headerName));

            if ($headerValue === strtolower($expectedValue)) {
                return true;
            }
        }

        return false;
    }

    
    public function reset(): void
    {
        foreach ($this->collectors as $collector) {
            if (method_exists($collector, 'reset')) {
                $collector->reset();
            }
        }
    }
}
