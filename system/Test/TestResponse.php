<?php

declare(strict_types=1);



namespace CodeIgniter\Test;

use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\I18n\Time;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Constraint\IsEqual;


class TestResponse
{
    
    protected $request;

    
    protected $response;

    
    protected $domParser;

    
    public function __construct(ResponseInterface $response)
    {
        $this->setResponse($response);
    }

    
    
    

    
    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;

        return $this;
    }

    
    public function setResponse(ResponseInterface $response)
    {
        $this->response  = $response;
        $this->domParser = new DOMParser();

        $body = $response->getBody();

        if (is_string($body) && $body !== '') {
            $this->domParser->withString($body);
        }

        return $this;
    }

    
    public function request()
    {
        return $this->request;
    }

    
    public function response()
    {
        return $this->response;
    }

    
    
    

    
    public function isOK(): bool
    {
        $status = $this->response->getStatusCode();

        
        
        if ($status >= 400 || $status < 200) {
            return false;
        }

        $body = (string) $this->response->getBody();

        
        return ! ($status < 300 && $body === '');
    }

    
    public function assertStatus(int $code): void
    {
        Assert::assertSame($code, $this->response->getStatusCode());
    }

    
    public function assertOK(): void
    {
        Assert::assertTrue(
            $this->isOK(),
            "{$this->response->getStatusCode()} is not a successful status code, or Response has an empty body.",
        );
    }

    
    public function assertNotOK(): void
    {
        Assert::assertFalse(
            $this->isOK(),
            "{$this->response->getStatusCode()} is an unexpected successful status code, or Response body has content.",
        );
    }

    
    
    

    
    public function isRedirect(): bool
    {
        return $this->response instanceof RedirectResponse
            || $this->response->hasHeader('Location')
            || $this->response->hasHeader('Refresh');
    }

    
    public function assertRedirect(): void
    {
        Assert::assertTrue($this->isRedirect(), 'Response is not a redirect or instance of RedirectResponse.');
    }

    
    public function assertRedirectTo(string $uri): void
    {
        $this->assertRedirect();

        $uri         = trim(strtolower($uri));
        $redirectUri = strtolower($this->getRedirectUrl());

        $matches = $uri === $redirectUri
            || strtolower(site_url($uri)) === $redirectUri
            || $uri === site_url($redirectUri);

        Assert::assertTrue($matches, "Redirect URL '{$uri}' does not match '{$redirectUri}'.");
    }

    
    public function assertNotRedirect(): void
    {
        Assert::assertFalse($this->isRedirect(), 'Response is an unexpected redirect or instance of RedirectResponse.');
    }

    
    public function getRedirectUrl(): ?string
    {
        if (! $this->isRedirect()) {
            return null;
        }

        if ($this->response->hasHeader('Location')) {
            return $this->response->getHeaderLine('Location');
        }

        if ($this->response->hasHeader('Refresh')) {
            return str_replace('0;url=', '', $this->response->getHeaderLine('Refresh'));
        }

        return null;
    }

    
    
    

    
    public function assertSessionHas(string $key, $value = null): void
    {
        Assert::assertArrayHasKey($key, $_SESSION, "Key '{$key}' is not in the current \$_SESSION");

        if ($value === null) {
            return;
        }

        if (is_scalar($value)) {
            Assert::assertSame($value, $_SESSION[$key], "The value of key '{$key}' ({$value}) does not match expected value.");

            return;
        }

        Assert::assertSame($value, $_SESSION[$key], "The value of key '{$key}' does not match expected value.");
    }

    
    public function assertSessionMissing(string $key): void
    {
        Assert::assertArrayNotHasKey($key, $_SESSION, "Key '{$key}' should not be present in \$_SESSION.");
    }

    
    
    

    
    public function assertHeader(string $key, $value = null): void
    {
        Assert::assertTrue($this->response->hasHeader($key), "Header '{$key}' is not a valid Response header.");

        if ($value !== null) {
            Assert::assertSame(
                $value,
                $this->response->getHeaderLine($key),
                "The value of '{$key}' header ({$this->response->getHeaderLine($key)}) does not match expected value.",
            );
        }
    }

    
    public function assertHeaderMissing(string $key): void
    {
        Assert::assertFalse($this->response->hasHeader($key), "Header '{$key}' should not be in the Response headers.");
    }

    
    
    

    
    public function assertCookie(string $key, $value = null, string $prefix = ''): void
    {
        Assert::assertTrue($this->response->hasCookie($key, $value, $prefix), "Cookie named '{$key}' is not found.");
    }

    
    public function assertCookieMissing(string $key): void
    {
        Assert::assertFalse($this->response->hasCookie($key), "Cookie named '{$key}' should not be set.");
    }

    
    public function assertCookieExpired(string $key, string $prefix = ''): void
    {
        Assert::assertTrue($this->response->hasCookie($key, null, $prefix));

        Assert::assertGreaterThan(
            Time::now()->getTimestamp(),
            $this->response->getCookie($key, $prefix)->getExpiresTimestamp(),
        );
    }

    
    
    

    
    public function getJSON()
    {
        $response = $this->response->getJSON();

        if ($response === null) {
            return false;
        }

        return $response;
    }

    
    public function assertJSONFragment(array $fragment, bool $strict = false): void
    {
        $json = json_decode($this->getJSON(), true);
        Assert::assertIsArray($json, 'Response is not a valid JSON.');

        $patched = array_replace_recursive($json, $fragment);

        if ($strict) {
            Assert::assertSame($json, $patched, 'Response does not contain a matching JSON fragment.');

            return;
        }

        Assert::assertThat($patched, new IsEqual($json), 'Response does not contain a matching JSON fragment.');
    }

    
    public function assertJSONExact($test): void
    {
        $json = $this->getJSON();

        if (is_object($test)) {
            $test = method_exists($test, 'toArray') ? $test->toArray() : (array) $test;
        }

        if (is_array($test)) {
            $test = service('format')->getFormatter('application/json')->format($test);
        }

        Assert::assertJsonStringEqualsJsonString($test, $json, 'Response does not contain matching JSON.');
    }

    
    
    

    
    public function getXML()
    {
        return $this->response->getXML();
    }

    
    
    

    
    public function assertSee(?string $search = null, ?string $element = null): void
    {
        Assert::assertTrue(
            $this->domParser->see($search, $element),
            "Text '{$search}' is not seen in response.",
        );
    }

    
    public function assertDontSee(?string $search = null, ?string $element = null): void
    {
        Assert::assertTrue(
            $this->domParser->dontSee($search, $element),
            "Text '{$search}' is unexpectedly seen in response.",
        );
    }

    
    public function assertSeeElement(string $search): void
    {
        Assert::assertTrue(
            $this->domParser->seeElement($search),
            "Element with selector '{$search}' is not seen in response.",
        );
    }

    
    public function assertDontSeeElement(string $search): void
    {
        Assert::assertTrue(
            $this->domParser->dontSeeElement($search),
            "Element with selector '{$search}' is unexpectedly seen in response.'",
        );
    }

    
    public function assertSeeLink(string $text, ?string $details = null): void
    {
        Assert::assertTrue(
            $this->domParser->seeLink($text, $details),
            "Anchor tag with text '{$text}' is not seen in response.",
        );
    }

    
    public function assertSeeInField(string $field, ?string $value = null): void
    {
        Assert::assertTrue(
            $this->domParser->seeInField($field, $value),
            "Input named '{$field}' with value '{$value}' is not seen in response.",
        );
    }

    
    public function __call(string $function, array $params): mixed
    {
        if (method_exists($this->domParser, $function)) {
            return $this->domParser->{$function}(...$params);
        }

        return null;
    }
}
