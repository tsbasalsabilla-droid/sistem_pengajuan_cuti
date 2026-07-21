<?php

declare(strict_types=1);



namespace CodeIgniter\View;

use CodeIgniter\Database\BaseResult;


class Table
{
    
    public $rows = [];

    
    public $heading = [];

    
    public $footing = [];

    
    public $autoHeading = true;

    
    public $caption;

    
    public $template;

    
    public $newline = "\n";

    
    public $emptyCells = '';

    
    public $function;

    
    private bool $syncRowsWithHeading = false;

    
    public function __construct($config = [])
    {
        
        foreach ($config as $key => $val) {
            $this->template[$key] = $val;
        }
    }

    
    public function setTemplate($template)
    {
        if (! is_array($template)) {
            return false;
        }

        $this->template = $template;

        return true;
    }

    
    public function setHeading()
    {
        $this->heading = $this->_prepArgs(func_get_args());

        return $this;
    }

    
    public function setFooting()
    {
        $this->footing = $this->_prepArgs(func_get_args());

        return $this;
    }

    
    public function makeColumns($array = [], $columnLimit = 0)
    {
        if (! is_array($array) || $array === [] || ! is_int($columnLimit)) {
            return false;
        }

        
        
        $this->autoHeading         = false;
        $this->syncRowsWithHeading = false;

        if ($columnLimit === 0) {
            return $array;
        }

        $new = [];

        do {
            $temp = array_splice($array, 0, $columnLimit);

            if (count($temp) < $columnLimit) {
                for ($i = count($temp); $i < $columnLimit; $i++) {
                    $temp[] = '&nbsp;';
                }
            }

            $new[] = $temp;
        } while ($array !== []);

        return $new;
    }

    
    public function setEmpty($value)
    {
        $this->emptyCells = $value;

        return $this;
    }

    
    public function addRow()
    {
        $tmpRow = $this->_prepArgs(func_get_args());

        if ($this->syncRowsWithHeading && $this->heading !== []) {
            
            $keyIndex = array_flip(array_keys($this->heading));

            
            $missingKeys = array_diff_key($keyIndex, $tmpRow);

            
            $tmpRow = array_filter($tmpRow, static fn ($k): bool => array_key_exists($k, $keyIndex), ARRAY_FILTER_USE_KEY);

            
            $tmpRow = array_merge($tmpRow, array_map(fn ($v): array => ['data' => $this->emptyCells], $missingKeys));

            
            uksort($tmpRow, static fn ($k1, $k2): int => $keyIndex[$k1] <=> $keyIndex[$k2]);
        }
        $this->rows[] = $tmpRow;

        return $this;
    }

    
    public function setSyncRowsWithHeading(bool $orderByKey)
    {
        $this->syncRowsWithHeading = $orderByKey;

        return $this;
    }

    
    protected function _prepArgs(array $args)
    {
        
        
        
        if (isset($args[0]) && count($args) === 1 && is_array($args[0])) {
            $args = $args[0];
        }

        foreach ($args as $key => $val) {
            if (! is_array($val)) {
                $args[$key] = ['data' => $val];
            }
        }

        return $args;
    }

    
    public function setCaption($caption)
    {
        $this->caption = $caption;

        return $this;
    }

    
    public function generate($tableData = null)
    {
        
        
        if ($tableData !== null && $tableData !== []) {
            if ($tableData instanceof BaseResult) {
                $this->_setFromDBResult($tableData);
            } elseif (is_array($tableData)) {
                $this->_setFromArray($tableData);
            }
        }

        
        if ($this->heading === [] && $this->rows === []) {
            return 'Undefined table data';
        }

        
        $this->_compileTemplate();

        
        if (isset($this->function) && ! is_callable($this->function)) {
            $this->function = null;
        }

        
        $out = $this->template['table_open'] . $this->newline;

        
        if (isset($this->caption) && $this->caption !== '') {
            $out .= '<caption>' . $this->caption . '</caption>' . $this->newline;
        }

        
        if ($this->heading !== []) {
            $headerTag = null;

            if (preg_match('/(<)(td|th)(?=\h|>)/i', $this->template['heading_cell_start'], $matches) === 1) {
                $headerTag = $matches[0];
            }

            $out .= $this->template['thead_open'] . $this->newline . $this->template['heading_row_start'] . $this->newline;

            foreach ($this->heading as $heading) {
                $temp = $this->template['heading_cell_start'];

                foreach ($heading as $key => $val) {
                    if ($key !== 'data' && $headerTag !== null) {
                        $temp = str_replace($headerTag, $headerTag . ' ' . $key . '="' . $val . '"', $temp);
                    }
                }

                $out .= $temp . ($heading['data'] ?? '') . $this->template['heading_cell_end'];
            }

            $out .= $this->template['heading_row_end'] . $this->newline . $this->template['thead_close'] . $this->newline;
        }

        
        if ($this->rows !== []) {
            $out .= $this->template['tbody_open'] . $this->newline;

            $i = 1;

            foreach ($this->rows as $row) {
                
                $name = fmod($i++, 2) !== 0.0 ? '' : 'alt_';

                $out .= $this->template['row_' . $name . 'start'] . $this->newline;

                foreach ($row as $cell) {
                    $temp = $this->template['cell_' . $name . 'start'];

                    foreach ($cell as $key => $val) {
                        if ($key !== 'data') {
                            $temp = str_replace('<td', '<td ' . $key . '="' . $val . '"', $temp);
                        }
                    }

                    $cell = $cell['data'] ?? '';
                    $out .= $temp;

                    if ($cell === '') {
                        $out .= $this->emptyCells;
                    } elseif (isset($this->function)) {
                        $out .= ($this->function)($cell);
                    } else {
                        $out .= $cell;
                    }

                    $out .= $this->template['cell_' . $name . 'end'];
                }

                $out .= $this->template['row_' . $name . 'end'] . $this->newline;
            }

            $out .= $this->template['tbody_close'] . $this->newline;
        }

        
        if ($this->footing !== []) {
            $footerTag = null;

            if (preg_match('/(<)(td|th)(?=\h|>)/i', $this->template['footing_cell_start'], $matches)) {
                $footerTag = $matches[0];
            }

            $out .= $this->template['tfoot_open'] . $this->newline . $this->template['footing_row_start'] . $this->newline;

            foreach ($this->footing as $footing) {
                $temp = $this->template['footing_cell_start'];

                foreach ($footing as $key => $val) {
                    if ($key !== 'data' && $footerTag !== null) {
                        $temp = str_replace($footerTag, $footerTag . ' ' . $key . '="' . $val . '"', $temp);
                    }
                }

                $out .= $temp . ($footing['data'] ?? '') . $this->template['footing_cell_end'];
            }

            $out .= $this->template['footing_row_end'] . $this->newline . $this->template['tfoot_close'] . $this->newline;
        }

        
        $out .= $this->template['table_close'];

        
        $this->clear();

        return $out;
    }

    
    public function clear()
    {
        $this->rows        = [];
        $this->heading     = [];
        $this->footing     = [];
        $this->autoHeading = true;
        $this->caption     = null;

        return $this;
    }

    
    protected function _setFromDBResult($object)
    {
        
        if ($this->autoHeading && $this->heading === []) {
            $this->heading = $this->_prepArgs($object->getFieldNames());
        }

        foreach ($object->getResultArray() as $row) {
            $this->rows[] = $this->_prepArgs($row);
        }
    }

    
    protected function _setFromArray($data)
    {
        if ($this->autoHeading && $this->heading === []) {
            $this->heading = $this->_prepArgs(array_shift($data));
        }

        foreach ($data as &$row) {
            $this->addRow($row);
        }
    }

    
    protected function _compileTemplate()
    {
        if ($this->template === null) {
            $this->template = $this->_defaultTemplate();

            return;
        }

        foreach ($this->_defaultTemplate() as $field => $template) {
            if (! isset($this->template[$field])) {
                $this->template[$field] = $template;
            }
        }
    }

    
    protected function _defaultTemplate()
    {
        return [
            'table_open'         => '<table border="0" cellpadding="4" cellspacing="0">',
            'thead_open'         => '<thead>',
            'thead_close'        => '</thead>',
            'heading_row_start'  => '<tr>',
            'heading_row_end'    => '</tr>',
            'heading_cell_start' => '<th>',
            'heading_cell_end'   => '</th>',
            'tfoot_open'         => '<tfoot>',
            'tfoot_close'        => '</tfoot>',
            'footing_row_start'  => '<tr>',
            'footing_row_end'    => '</tr>',
            'footing_cell_start' => '<td>',
            'footing_cell_end'   => '</td>',
            'tbody_open'         => '<tbody>',
            'tbody_close'        => '</tbody>',
            'row_start'          => '<tr>',
            'row_end'            => '</tr>',
            'cell_start'         => '<td>',
            'cell_end'           => '</td>',
            'row_alt_start'      => '<tr>',
            'row_alt_end'        => '</tr>',
            'cell_alt_start'     => '<td>',
            'cell_alt_end'       => '</td>',
            'table_close'        => '</table>',
        ];
    }
}
