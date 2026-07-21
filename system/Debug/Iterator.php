<?php

declare(strict_types=1);



namespace CodeIgniter\Debug;

use Closure;


class Iterator
{
    
    protected $tests = [];

    
    protected $results = [];

    
    public function add(string $name, Closure $closure)
    {
        $name = strtolower($name);

        $this->tests[$name] = $closure;

        return $this;
    }

    
    public function run(int $iterations = 1000, bool $output = true)
    {
        foreach ($this->tests as $name => $test) {
            
            gc_collect_cycles();

            $start    = microtime(true);
            $startMem = $maxMemory = memory_get_usage(true);

            for ($i = 0; $i < $iterations; $i++) {
                $result    = $test();
                $maxMemory = max($maxMemory, memory_get_usage(true));

                unset($result);
            }

            $this->results[$name] = [
                'time'   => microtime(true) - $start,
                'memory' => $maxMemory - $startMem,
                'n'      => $iterations,
            ];
        }

        if ($output) {
            return $this->getReport();
        }

        return null;
    }

    
    public function getReport(): string
    {
        if ($this->results === []) {
            return 'No results to display.';
        }

        helper('number');

        
        $tpl = '<table>
			<thead>
				<tr>
					<td>Test</td>
					<td>Time</td>
					<td>Memory</td>
				</tr>
			</thead>
			<tbody>
				{rows}
			</tbody>
		</table>';

        $rows = '';

        foreach ($this->results as $name => $result) {
            $memory = number_to_size($result['memory'], 4);

            $rows .= "<tr>
				<td>{$name}</td>
				<td>" . number_format($result['time'], 4) . "</td>
				<td>{$memory}</td>
			</tr>";
        }

        $tpl = str_replace('{rows}', $rows, $tpl);

        return $tpl . '<br/>';
    }
}
