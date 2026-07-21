<?php

declare(strict_types=1);



namespace CodeIgniter\Validation;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\RequestInterface;


interface ValidationInterface
{
    
    public function run(?array $data = null, ?string $group = null, $dbGroup = null): bool;

    
    public function check($value, $rules, array $errors = [], $dbGroup = null): bool;

    
    public function withRequest(RequestInterface $request): ValidationInterface;

    
    public function setRule(string $field, ?string $label, $rules, array $errors = []);

    
    public function setRules(array $rules, array $messages = []): ValidationInterface;

    
    public function getRules(): array;

    
    public function hasRule(string $field): bool;

    
    public function getRuleGroup(string $group): array;

    
    public function setRuleGroup(string $group);

    
    public function getError(string $field): string;

    
    public function getErrors(): array;

    
    public function setError(string $alias, string $error): ValidationInterface;

    
    public function reset(): ValidationInterface;

    
    public function loadRuleGroup(?string $group = null);

    
    public function hasError(string $field): bool;

    
    public function listErrors(string $template = 'list'): string;

    
    public function showError(string $field, string $template = 'single'): string;

    
    public function getValidated(): array;
}
