<?php

declare(strict_types=1);



namespace CodeIgniter\HTTP;

use CodeIgniter\Exceptions\DownloadException;
use CodeIgniter\Files\File;
use Config\App;
use Config\Mimes;


class DownloadResponse extends Response
{
    
    private string $filename;

    
    private ?File $file = null;

    
    private  bool $setMime;

    
    private ?string $binary = null;

    
    private string $charset = 'UTF-8';

    
    protected $reason = 'OK';

    
    protected $statusCode = 200;

    
    public function __construct(string $filename, bool $setMime)
    {
        parent::__construct(config(App::class));

        $this->filename = $filename;
        $this->setMime  = $setMime;

        
        $this->removeHeader('Content-Type');
    }

    
    public function setBinary(string $binary)
    {
        if ($this->file instanceof File) {
            throw DownloadException::forCannotSetBinary();
        }

        $this->binary = $binary;
    }

    
    public function setFilePath(string $filepath)
    {
        if ($this->binary !== null) {
            throw DownloadException::forCannotSetFilePath($filepath);
        }

        $this->file = new File($filepath, true);
    }

    
    public function setFileName(string $filename)
    {
        $this->filename = $filename;

        return $this;
    }

    
    public function getContentLength(): int
    {
        if (is_string($this->binary)) {
            return strlen($this->binary);
        }

        if ($this->file instanceof File) {
            return $this->file->getSize();
        }

        return 0;
    }

    
    private function setContentTypeByMimeType(): void
    {
        $mime    = null;
        $charset = '';

        if ($this->setMime && ($lastDotPosition = strrpos($this->filename, '.')) !== false) {
            $mime    = Mimes::guessTypeFromExtension(substr($this->filename, $lastDotPosition + 1));
            $charset = $this->charset;
        }

        if (! is_string($mime)) {
            
            $mime    = 'application/octet-stream';
            $charset = '';
        }

        $this->setContentType($mime, $charset);
    }

    
    private function getDownloadFileName(): string
    {
        $filename  = $this->filename;
        $x         = explode('.', $this->filename);
        $extension = end($x);

        
        $userAgent = service('superglobals')->server('HTTP_USER_AGENT');
        if (count($x) !== 1 && $userAgent !== null
                && preg_match('/Android\s(1|2\.[01])/', $userAgent)) {
            $x[count($x) - 1] = strtoupper($extension);
            $filename         = implode('.', $x);
        }

        return $filename;
    }

    
    private function getContentDisposition(bool $inline = false): string
    {
        $downloadFilename = $utf8Filename = $this->getDownloadFileName();
        $disposition      = $inline ? 'inline' : 'attachment';

        if (strtoupper($this->charset) !== 'UTF-8') {
            $utf8Filename = mb_convert_encoding($downloadFilename, 'UTF-8', $this->charset);
        }

        $result = sprintf('%s; filename="%s"', $disposition, addslashes($downloadFilename));

        if ($utf8Filename !== '') {
            $result .= sprintf('; filename*=UTF-8\'\'%s', rawurlencode($utf8Filename));
        }

        return $result;
    }

    
    public function setStatusCode(int $code, string $reason = '')
    {
        throw DownloadException::forCannotSetStatusCode($code, $reason);
    }

    
    public function setContentType(string $mime, string $charset = 'UTF-8')
    {
        parent::setContentType($mime, $charset);

        if ($charset !== '') {
            $this->charset = $charset;
        }

        return $this;
    }

    
    public function noCache(): self
    {
        $this->removeHeader('Cache-Control');
        $this->setHeader('Cache-Control', ['private', 'no-transform', 'no-store', 'must-revalidate']);

        return $this;
    }

    
    public function send()
    {
        
        if (ENVIRONMENT !== 'testing') {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }

        $this->buildHeaders();
        $this->sendHeaders();
        $this->sendBody();

        return $this;
    }

    
    public function buildHeaders()
    {
        if (! $this->hasHeader('Content-Type')) {
            $this->setContentTypeByMimeType();
        }

        if (! $this->hasHeader('Content-Disposition')) {
            $this->setHeader('Content-Disposition', $this->getContentDisposition());
        }

        $this->setHeader('Content-Transfer-Encoding', 'binary');
        $this->setHeader('Content-Length', (string) $this->getContentLength());
    }

    
    public function sendBody()
    {
        if ($this->binary !== null) {
            return $this->sendBodyByBinary();
        }

        if ($this->file instanceof File) {
            return $this->sendBodyByFilePath();
        }

        throw DownloadException::forNotFoundDownloadSource();
    }

    
    private function sendBodyByFilePath()
    {
        $splFileObject = $this->file->openFile('rb');

        
        while (! $splFileObject->eof() && ($data = $splFileObject->fread(1_048_576)) !== false) {
            echo $data;
            unset($data);
        }

        return $this;
    }

    
    private function sendBodyByBinary()
    {
        echo $this->binary;

        return $this;
    }

    
    public function inline()
    {
        $this->setHeader('Content-Disposition', $this->getContentDisposition(true));

        return $this;
    }
}
