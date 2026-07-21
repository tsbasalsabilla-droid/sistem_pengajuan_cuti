<?php

declare(strict_types=1);



namespace CodeIgniter\Test\Mock;

use CodeIgniter\Language\Language;


class MockLanguage extends Language
{
    
    protected $data;

    
    public function setData(string $file, array $data, ?string $locale = null)
    {
        $this->language[$locale ?? $this->locale][$file] = $data;

        $this->data = $data;

        return $this;
    }

    
    protected function requireFile(string $path): array
    {
        return $this->data ?? [];
    }

    
    public function disableIntlSupport()
    {
        $this->intlSupport = false;
    }
}
