<?php

declare(strict_types=1);



namespace CodeIgniter\Validation;

use Closure;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Exceptions\InvalidArgumentException;
use CodeIgniter\Exceptions\LogicException;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\Method;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\Validation\Exceptions\ValidationException;
use CodeIgniter\View\RendererInterface;


class Validation implements ValidationInterface
{
    
    protected $ruleSetFiles;

    
    protected $ruleSetInstances = [];

    
    protected $rules = [];

    
    protected $data = [];

    
    protected $validated = [];

    
    protected $errors = [];

    
    protected $customErrors = [];

    
    protected $config;

    
    protected $view;

    
    public function __construct($config, RendererInterface $view)
    {
        $this->ruleSetFiles = $config->ruleSets;

        $this->config = $config;

        $this->view = $view;

        $this->loadRuleSets();
    }

    
    public function run(?array $data = null, ?string $group = null, $dbGroup = null): bool
    {
        if ($data === null) {
            $data = $this->data;
        } else {
            
            $this->data = $data;
        }

        
        $data['DBGroup'] = $dbGroup;

        $this->loadRuleGroup($group);

        
        
        if ($this->rules === []) {
            return false;
        }

        
        
        $this->rules = $this->fillPlaceholders($this->rules, $data);

        
        helper('array');

        
        
        foreach ($this->rules as $field => $setup) {
            
            $field = (string) $field;

            $rules = $setup['rules'];

            if (is_string($rules)) {
                $rules = $this->splitRules($rules);
            }

            if (str_contains($field, '*')) {
                $flattenedArray = array_flatten_with_dots($data);

                $values = array_filter(
                    $flattenedArray,
                    static fn ($key): bool => preg_match(self::getRegex($field), $key) === 1,
                    ARRAY_FILTER_USE_KEY,
                );

                
                
                
                
                
                foreach ($this->walkForAllPossiblePaths(explode('.', $field), $data, '') as $path) {
                    $values[$path] = null;
                }

                
                $values = $values !== [] ? $values : [$field => null];
            } else {
                $values = dot_array_search($field, $data);
            }

            if ($values === []) {
                
                $this->processRules($field, $setup['label'] ?? $field, $values, $rules, $data, $field);

                continue;
            }

            if (str_contains($field, '*')) {
                
                foreach ($values as $dotField => $value) {
                    $this->processRules($dotField, $setup['label'] ?? $field, $value, $rules, $data, $field);
                }
            } else {
                
                $this->processRules($field, $setup['label'] ?? $field, $values, $rules, $data, $field);
            }
        }

        if ($this->getErrors() === []) {
            
            $this->validated = DotArrayFilter::run(
                array_keys($this->rules),
                $this->data,
            );

            return true;
        }

        return false;
    }

    
    private static function getRegex(string $field): string
    {
        return '/\A'
            . str_replace(
                ['\.\*', '\*\.'],
                ['\.[^.]+', '[^.]+\.'],
                preg_quote($field, '/'),
            )
            . '\z/';
    }

    
    public function check($value, $rules, array $errors = [], $dbGroup = null): bool
    {
        $this->reset();

        return $this->setRule(
            'check',
            null,
            $rules,
            $errors,
        )->run(
            ['check' => $value],
            null,
            $dbGroup,
        );
    }

    
    public function getValidated(): array
    {
        return $this->validated;
    }

    
    protected function processRules(
        string $field,
        ?string $label,
        $value,
        $rules = null,       
        ?array $data = null, 
        ?string $originalField = null,
    ): bool {
        if ($data === null) {
            throw new InvalidArgumentException('You must supply the parameter: data.');
        }

        $rules = $this->processIfExist($field, $rules, $data);
        if ($rules === true) {
            return true;
        }

        $rules = $this->processPermitEmpty($value, $rules, $data);
        if ($rules === true) {
            return true;
        }

        foreach ($rules as $i => $rule) {
            $isCallable     = is_callable($rule);
            $stringCallable = $isCallable && is_string($rule);
            $arrayCallable  = $isCallable && is_array($rule);

            $passed = false;
            
            $param = null;

            if (! $isCallable && preg_match('/(.*?)\[(.*)\]/', $rule, $match)) {
                $rule  = $match[1];
                $param = $match[2];
            }

            
            $error = null;

            
            if ($this->isClosure($rule)) {
                $passed = $rule($value, $data, $error, $field);
            } elseif ($isCallable) {
                $passed = $stringCallable ? $rule($value) : $rule($value, $data, $error, $field);
            } else {
                $found = false;

                
                foreach ($this->ruleSetInstances as $set) {
                    if (! method_exists($set, $rule)) {
                        continue;
                    }

                    $found = true;

                    if ($rule === 'field_exists') {
                        $passed = $set->{$rule}($value, $param, $data, $error, $originalField);
                    } else {
                        $passed = ($param === null)
                            ? $set->{$rule}($value, $error)
                            : $set->{$rule}($value, $param, $data, $error, $field);
                    }

                    break;
                }

                
                
                if (! $found) {
                    throw ValidationException::forRuleNotFound($rule);
                }
            }

            
            if ($passed === false) {
                
                if (is_array($value)) {
                    $value = $this->isStringList($value)
                        ? '[' . implode(', ', $value) . ']'
                        : json_encode($value);
                } elseif (is_object($value)) {
                    $value = json_encode($value);
                }

                $fieldForErrors = ($rule === 'field_exists') ? $originalField : $field;

                
                $this->errors[$fieldForErrors] = $error ?? $this->getErrorMessage(
                    ($this->isClosure($rule) || $arrayCallable) ? (string) $i : $rule,
                    $field,
                    $label,
                    $param,
                    (string) $value,
                    $originalField,
                );

                return false;
            }
        }

        return true;
    }

    
    private function processIfExist(string $field, array $rules, array $data)
    {
        if (in_array('if_exist', $rules, true)) {
            $flattenedData = array_flatten_with_dots($data);
            $ifExistField  = $field;

            if (str_contains($field, '.*')) {
                
                $ifExistField   = str_replace('\.\*', '\.(?:[^\.]+)', preg_quote($field, '/'));
                $dataIsExisting = false;
                $pattern        = sprintf('/%s/u', $ifExistField);

                foreach (array_keys($flattenedData) as $item) {
                    if (preg_match($pattern, $item) === 1) {
                        $dataIsExisting = true;
                        break;
                    }
                }
            } elseif (str_contains($field, '.')) {
                $dataIsExisting = array_key_exists($ifExistField, $flattenedData);
            } else {
                $dataIsExisting = array_key_exists($ifExistField, $data);
            }

            if (! $dataIsExisting) {
                
                return true;
            }

            
            $rules = array_filter($rules, static fn ($rule): bool => $rule instanceof Closure || $rule !== 'if_exist');
        }

        return $rules;
    }

    
    private function processPermitEmpty($value, array $rules, array $data)
    {
        if (in_array('permit_empty', $rules, true)) {
            if (
                ! in_array('required', $rules, true)
                && (is_array($value) ? $value === [] : trim((string) $value) === '')
            ) {
                $passed = true;

                foreach ($rules as $rule) {
                    if (! $this->isClosure($rule) && preg_match('/(.*?)\[(.*)\]/', $rule, $match)) {
                        $rule  = $match[1];
                        $param = $match[2];

                        if (! in_array($rule, ['required_with', 'required_without'], true)) {
                            continue;
                        }

                        
                        foreach ($this->ruleSetInstances as $set) {
                            if (! method_exists($set, $rule)) {
                                continue;
                            }

                            $passed = $passed && $set->{$rule}($value, $param, $data);
                            break;
                        }
                    }
                }

                if ($passed) {
                    return true;
                }
            }

            $rules = array_filter($rules, static fn ($rule): bool => $rule instanceof Closure || $rule !== 'permit_empty');
        }

        return $rules;
    }

    
    private function isClosure($rule): bool
    {
        return $rule instanceof Closure;
    }

    
    private function isStringList(array $array): bool
    {
        $expectedKey = 0;

        foreach ($array as $key => $val) {
            
            if (! is_int($key)) {
                return false;
            }

            if ($key !== $expectedKey) {
                return false;
            }
            $expectedKey++;

            if (! is_string($val)) {
                return false;
            }
        }

        return true;
    }

    
    public function withRequest(RequestInterface $request): ValidationInterface
    {
        
        if (str_contains($request->getHeaderLine('Content-Type'), 'application/json')) {
            $this->data = $request->getJSON(true);

            if (! is_array($this->data)) {
                throw HTTPException::forUnsupportedJSONFormat();
            }

            return $this;
        }

        if (in_array($request->getMethod(), [Method::PUT, Method::PATCH, Method::DELETE], true)
            && ! str_contains($request->getHeaderLine('Content-Type'), 'multipart/form-data')
        ) {
            $this->data = $request->getRawInput();
        } else {
            $this->data = $request->getVar() ?? [];
        }

        return $this;
    }

    
    public function setRule(string $field, ?string $label, $rules, array $errors = [])
    {
        if (! is_array($rules) && ! is_string($rules)) {
            throw new InvalidArgumentException('$rules must be of type string|array');
        }

        $ruleSet = [
            $field => [
                'label' => $label,
                'rules' => $rules,
            ],
        ];

        if ($errors !== []) {
            $ruleSet[$field]['errors'] = $errors;
        }

        $this->setRules(array_merge($this->getRules(), $ruleSet), $this->customErrors);

        return $this;
    }

    
    public function setRules(array $rules, array $errors = []): ValidationInterface
    {
        $this->customErrors = $errors;

        foreach ($rules as $field => &$rule) {
            if (is_array($rule)) {
                if (array_key_exists('errors', $rule)) {
                    $this->customErrors[$field] = $rule['errors'];
                    unset($rule['errors']);
                }

                
                
                if (! array_key_exists('rules', $rule)) {
                    $rule = ['rules' => $rule];
                }
            }

            if (isset($rule['rules']) && is_string($rule['rules'])) {
                $rule['rules'] = $this->splitRules($rule['rules']);
            }

            if (is_string($rule)) {
                $rule = ['rules' => $this->splitRules($rule)];
            }
        }

        $this->rules = $rules;

        return $this;
    }

    
    public function getRules(): array
    {
        return $this->rules;
    }

    
    public function hasRule(string $field): bool
    {
        return array_key_exists($field, $this->rules);
    }

    
    public function getRuleGroup(string $group): array
    {
        if (! isset($this->config->{$group})) {
            throw ValidationException::forGroupNotFound($group);
        }

        if (! is_array($this->config->{$group})) {
            throw ValidationException::forGroupNotArray($group);
        }

        return $this->config->{$group};
    }

    
    public function setRuleGroup(string $group)
    {
        $rules = $this->getRuleGroup($group);
        $this->setRules($rules);

        $errorName = $group . '_errors';
        if (isset($this->config->{$errorName})) {
            $this->customErrors = $this->config->{$errorName};
        }
    }

    
    public function listErrors(string $template = 'list'): string
    {
        if (! array_key_exists($template, $this->config->templates)) {
            throw ValidationException::forInvalidTemplate($template);
        }

        return $this->view
            ->setVar('errors', $this->getErrors())
            ->render($this->config->templates[$template]);
    }

    
    public function showError(string $field, string $template = 'single'): string
    {
        if (! array_key_exists($field, $this->getErrors())) {
            return '';
        }

        if (! array_key_exists($template, $this->config->templates)) {
            throw ValidationException::forInvalidTemplate($template);
        }

        return $this->view
            ->setVar('error', $this->getError($field))
            ->render($this->config->templates[$template]);
    }

    
    protected function loadRuleSets()
    {
        if ($this->ruleSetFiles === [] || $this->ruleSetFiles === null) {
            throw ValidationException::forNoRuleSets();
        }

        foreach ($this->ruleSetFiles as $file) {
            $this->ruleSetInstances[] = new $file();
        }
    }

    
    public function loadRuleGroup(?string $group = null)
    {
        if ($group === null || $group === '') {
            return [];
        }

        if (! isset($this->config->{$group})) {
            throw ValidationException::forGroupNotFound($group);
        }

        if (! is_array($this->config->{$group})) {
            throw ValidationException::forGroupNotArray($group);
        }

        $this->setRules($this->config->{$group});

        
        
        $errorName = $group . '_errors';

        if (isset($this->config->{$errorName})) {
            $this->customErrors = $this->config->{$errorName};
        }

        return [$this->rules, $this->customErrors];
    }

    
    protected function fillPlaceholders(array $rules, array $data): array
    {
        foreach ($rules as &$rule) {
            $ruleSet = $rule['rules'];

            foreach ($ruleSet as &$row) {
                if (is_string($row)) {
                    $placeholderFields = $this->retrievePlaceholders($row, $data);

                    foreach ($placeholderFields as $field) {
                        $validator ??= service('validation', null, false);
                        assert($validator instanceof Validation);

                        $placeholderRules = $rules[$field]['rules'] ?? null;

                        
                        if ($placeholderRules === null) {
                            throw new LogicException(
                                'No validation rules for the placeholder: "' . $field
                                . '". You must set the validation rules for the field.'
                                . ' See <https://codeigniter4.github.io/userguide/libraries/validation.html#validation-placeholders>.',
                            );
                        }

                        
                        foreach ($placeholderRules as $placeholderRule) {
                            if ($this->retrievePlaceholders($placeholderRule, $data) !== []) {
                                throw new LogicException(
                                    'The placeholder field cannot use placeholder: ' . $field,
                                );
                            }
                        }

                        
                        $dbGroup = $data['DBGroup'] ?? null;
                        if (! $validator->check($data[$field], $placeholderRules, [], $dbGroup)) {
                            
                            continue;
                        }

                        
                        if (str_starts_with($row, 'regex_match[')) {
                            $row = str_replace('{{' . $field . '}}', (string) $data[$field], $row);
                        } else {
                            $row = str_replace('{' . $field . '}', (string) $data[$field], $row);
                        }
                    }
                }
            }

            $rule['rules'] = $ruleSet;
        }

        return $rules;
    }

    
    private function retrievePlaceholders(string $rule, array $data): array
    {
        if (str_starts_with($rule, 'regex_match[')) {
            
            preg_match_all('/\{\{((?:(?![{}]).)+?)\}\}/', $rule, $matches);
        } else {
            
            preg_match_all('/{(.+?)}/', $rule, $matches);
        }

        return array_intersect($matches[1], array_keys($data));
    }

    
    public function hasError(string $field): bool
    {
        return (bool) preg_grep(self::getRegex($field), array_keys($this->getErrors()));
    }

    
    public function getError(?string $field = null): string
    {
        if ($field === null && count($this->rules) === 1) {
            $field = array_key_first($this->rules);
        }

        $errors = array_filter(
            $this->getErrors(),
            static fn ($key): bool => preg_match(self::getRegex($field), $key) === 1,
            ARRAY_FILTER_USE_KEY,
        );

        return implode("\n", $errors);
    }

    
    public function getErrors(): array
    {
        return $this->errors;
    }

    
    public function setError(string $field, string $error): ValidationInterface
    {
        $this->errors[$field] = $error;

        return $this;
    }

    
    protected function getErrorMessage(
        string $rule,
        string $field,
        ?string $label = null,
        ?string $param = null,
        ?string $value = null,
        ?string $originalField = null,
    ): string {
        $param ??= '';

        $args = [
            'field' => ($label === null || $label === '') ? $field : lang($label),
            'param' => isset($this->rules[$param]['label']) ? lang($this->rules[$param]['label']) : $param,
            'value' => $value ?? '',
        ];

        
        if (isset($this->customErrors[$field][$rule])) {
            return lang($this->customErrors[$field][$rule], $args);
        }
        if (null !== $originalField && isset($this->customErrors[$originalField][$rule])) {
            return lang($this->customErrors[$originalField][$rule], $args);
        }

        
        
        
        return lang('Validation.' . $rule, $args);
    }

    
    protected function splitRules(string $rules): array
    {
        if (! str_contains($rules, '|')) {
            return [$rules];
        }

        $string = $rules;
        $rules  = [];
        $length = strlen($string);
        $cursor = 0;

        while ($cursor < $length) {
            $pos = strpos($string, '|', $cursor);

            if ($pos === false) {
                
                $pos = $length;
            }

            $rule = substr($string, $cursor, $pos - $cursor);

            while (
                (substr_count($rule, '[') - substr_count($rule, '\['))
                !== (substr_count($rule, ']') - substr_count($rule, '\]'))
            ) {
                
                
                $pos  = strpos($string, '|', $cursor + strlen($rule) + 1) ?: $length;
                $rule = substr($string, $cursor, $pos - $cursor);
            }

            $rules[] = $rule;
            $cursor += strlen($rule) + 1; 
        }

        return array_unique($rules);
    }

    
    private function walkForAllPossiblePaths(array $segments, mixed $current, string $prefix): array
    {
        $result = [];
        $this->collectMissingPaths($segments, 0, count($segments), $current, $prefix, $result);

        return $result;
    }

    
    private function collectMissingPaths(
        array $segments,
        int $index,
        int $segmentCount,
        mixed $current,
        string $prefix,
        array &$result,
    ): void {
        if ($index >= $segmentCount) {
            
            return;
        }

        $segment   = $segments[$index];
        $nextIndex = $index + 1;

        if ($segment === '*') {
            if (! is_array($current)) {
                return;
            }

            foreach ($current as $key => $value) {
                $keyPrefix = $prefix !== '' ? $prefix . '.' . $key : (string) $key;

                
                
                if (! is_array($value) && $nextIndex < $segmentCount) {
                    continue;
                }

                $this->collectMissingPaths($segments, $nextIndex, $segmentCount, $value, $keyPrefix, $result);
            }

            return;
        }

        $newPrefix = $prefix !== '' ? $prefix . '.' . $segment : $segment;

        if (! is_array($current) || ! array_key_exists($segment, $current)) {
            
            
            
            if ($nextIndex === $segmentCount) {
                $result[] = $newPrefix;
            }

            return;
        }

        $this->collectMissingPaths($segments, $nextIndex, $segmentCount, $current[$segment], $newPrefix, $result);
    }

    
    public function reset(): ValidationInterface
    {
        $this->data         = [];
        $this->validated    = [];
        $this->rules        = [];
        $this->errors       = [];
        $this->customErrors = [];

        return $this;
    }
}
