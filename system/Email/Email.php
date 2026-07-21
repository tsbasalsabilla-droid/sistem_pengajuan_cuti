<?php

declare(strict_types=1);



namespace CodeIgniter\Email;

use CodeIgniter\Events\Events;
use CodeIgniter\I18n\Time;
use Config\Mimes;
use ErrorException;


class Email
{
    
    public $archive;

    
    protected $tmpArchive = [];

    
    public $fromEmail;

    
    public $fromName;

    
    public $userAgent = 'CodeIgniter';

    
    public $mailPath = '/usr/sbin/sendmail';

    
    public $protocol = 'mail';

    
    public $SMTPHost = '';

    
    public $SMTPUser = '';

    
    public $SMTPPass = '';

    
    public $SMTPPort = 25;

    
    public $SMTPTimeout = 5;

    
    public $SMTPKeepAlive = false;

    
    public $SMTPCrypto = '';

    
    public $wordWrap = true;

    
    public $wrapChars = 76;

    
    public $mailType = 'text';

    
    public $charset = 'UTF-8';

    
    public $altMessage = '';

    
    public $validate = true;

    
    public $priority = 3;

    
    public $newline = "\r\n";

    
    public $CRLF = "\r\n";

    
    public $DSN = false;

    
    public $sendMultipart = true;

    
    public $BCCBatchMode = false;

    
    public $BCCBatchSize = 200;

    
    protected $subject = '';

    
    protected $body = '';

    
    protected $finalBody = '';

    
    protected $headerStr = '';

    
    protected $SMTPConnect;

    
    protected $encoding = '8bit';

    
    protected $SMTPAuth = false;

    
    protected string $SMTPAuthMethod = 'login';

    
    protected $replyToFlag = false;

    
    protected $debugMessage = [];

    
    private array $debugMessageRaw = [];

    
    protected $recipients = [];

    
    protected $CCArray = [];

    
    protected $BCCArray = [];

    
    protected $headers = [];

    
    protected $attachments = [];

    
    protected $protocols = [
        'mail',
        'sendmail',
        'smtp',
    ];

    
    protected $baseCharsets = [
        'us-ascii',
        'iso-2022-',
    ];

    
    protected $bitDepths = [
        '7bit',
        '8bit',
    ];

    
    protected $priorities = [
        1 => '1 (Highest)',
        2 => '2 (High)',
        3 => '3 (Normal)',
        4 => '4 (Low)',
        5 => '5 (Lowest)',
    ];

    
    protected static $func_overload;

    
    public function __construct($config = null)
    {
        $this->initialize($config);

        if (! isset(static::$func_overload)) {
            static::$func_overload = extension_loaded('mbstring') && ini_get('mbstring.func_overload');
        }
    }

    
    public function initialize($config)
    {
        $this->clear();

        if ($config instanceof \Config\Email) {
            $config = get_object_vars($config);
        }

        foreach (array_keys(get_class_vars(static::class)) as $key) {
            if (property_exists($this, $key) && isset($config[$key])) {
                $method = 'set' . ucfirst($key);

                if (method_exists($this, $method)) {
                    $this->{$method}($config[$key]);
                } else {
                    $this->{$key} = $config[$key];
                }
            }
        }

        $this->charset  = strtoupper($this->charset);
        $this->SMTPAuth = isset($this->SMTPUser[0], $this->SMTPPass[0]);

        return $this;
    }

    
    public function clear($clearAttachments = false)
    {
        $this->subject         = '';
        $this->body            = '';
        $this->finalBody       = '';
        $this->headerStr       = '';
        $this->replyToFlag     = false;
        $this->recipients      = [];
        $this->CCArray         = [];
        $this->BCCArray        = [];
        $this->headers         = [];
        $this->debugMessage    = [];
        $this->debugMessageRaw = [];

        $this->setHeader('Date', $this->setDate());

        if ($clearAttachments) {
            $this->attachments = [];
        }

        return $this;
    }

    
    public function setFrom($from, $name = '', $returnPath = null)
    {
        if (preg_match('/\<(.*)\>/', $from, $match) === 1) {
            $from = $match[1];
        }

        if ($this->validate) {
            $this->validateEmail($this->stringToArray($from));

            if ($returnPath !== null) {
                $this->validateEmail($this->stringToArray($returnPath));
            }
        }

        $this->tmpArchive['fromEmail'] = $from;
        $this->tmpArchive['fromName']  = $name;

        if ($name !== '') {
            
            if (preg_match('/[\200-\377]/', $name) !== 1) {
                $name = '"' . addcslashes($name, "\0..\37\177'\"\\") . '"';
            } else {
                $name = $this->prepQEncoding($name);
            }
        }

        $this->setHeader('From', $name . ' <' . $from . '>');
        $returnPath ??= $from;

        $this->setHeader('Return-Path', '<' . $returnPath . '>');
        $this->tmpArchive['returnPath'] = $returnPath;

        return $this;
    }

    
    public function setReplyTo($replyto, $name = '')
    {
        if (preg_match('/\<(.*)\>/', $replyto, $match) === 1) {
            $replyto = $match[1];
        }

        if ($this->validate) {
            $this->validateEmail($this->stringToArray($replyto));
        }

        if ($name !== '') {
            $this->tmpArchive['replyName'] = $name;

            
            if (preg_match('/[\200-\377]/', $name) !== 1) {
                $name = '"' . addcslashes($name, "\0..\37\177'\"\\") . '"';
            } else {
                $name = $this->prepQEncoding($name);
            }
        }

        $this->setHeader('Reply-To', $name . ' <' . $replyto . '>');
        $this->replyToFlag           = true;
        $this->tmpArchive['replyTo'] = $replyto;

        return $this;
    }

    
    public function setTo($to)
    {
        $to = $this->stringToArray($to);
        $to = $this->cleanEmail($to);

        if ($this->validate) {
            $this->validateEmail($to);
        }

        if ($this->getProtocol() !== 'mail') {
            $this->setHeader('To', implode(', ', $to));
        }

        $this->recipients = $to;

        return $this;
    }

    
    public function setCC($cc)
    {
        $cc = $this->cleanEmail($this->stringToArray($cc));

        if ($this->validate) {
            $this->validateEmail($cc);
        }

        $this->setHeader('Cc', implode(', ', $cc));

        if ($this->getProtocol() === 'smtp') {
            $this->CCArray = $cc;
        }

        $this->tmpArchive['CCArray'] = $cc;

        return $this;
    }

    
    public function setBCC($bcc, $limit = '')
    {
        if ($limit !== '' && is_numeric($limit)) {
            $this->BCCBatchMode = true;
            $this->BCCBatchSize = $limit;
        }

        $bcc = $this->cleanEmail($this->stringToArray($bcc));

        if ($this->validate) {
            $this->validateEmail($bcc);
        }

        if ($this->getProtocol() === 'smtp' || ($this->BCCBatchMode && count($bcc) > $this->BCCBatchSize)) {
            $this->BCCArray = $bcc;
        } else {
            $this->setHeader('Bcc', implode(', ', $bcc));
            $this->tmpArchive['BCCArray'] = $bcc;
        }

        return $this;
    }

    
    public function setSubject($subject)
    {
        $this->tmpArchive['subject'] = $subject;

        $subject = $this->prepQEncoding($subject);
        $this->setHeader('Subject', $subject);

        return $this;
    }

    
    public function setMessage($body)
    {
        $this->body = rtrim(str_replace("\r", '', $body));

        return $this;
    }

    
    public function attach($file, $disposition = '', $newname = null, $mime = '')
    {
        if ($mime === '') {
            if (! str_contains($file, '://') && ! is_file($file)) {
                $this->setErrorMessage(lang('Email.attachmentMissing', [$file]));

                return false;
            }

            if (! $fp = @fopen($file, 'rb')) {
                $this->setErrorMessage(lang('Email.attachmentUnreadable', [$file]));

                return false;
            }

            $fileContent = stream_get_contents($fp);

            $mime = $this->mimeTypes(pathinfo($file, PATHINFO_EXTENSION));

            fclose($fp);
        } else {
            $fileContent = &$file; 
        }

        
        $namesAttached = [$file, $newname];

        $this->attachments[] = [
            'name'        => $namesAttached,
            'disposition' => empty($disposition) ? 'attachment' : $disposition,
            
            'type'      => $mime,
            'content'   => chunk_split(base64_encode($fileContent)),
            'multipart' => 'mixed',
        ];

        return $this;
    }

    
    public function setAttachmentCID($filename)
    {
        foreach ($this->attachments as $i => $attachment) {
            
            if ($attachment['name'][0] === $filename) {
                $this->attachments[$i]['multipart'] = 'related';

                $this->attachments[$i]['cid'] = uniqid(basename($attachment['name'][0]) . '@', true);

                return $this->attachments[$i]['cid'];
            }

            
            if ($attachment['name'][1] === $filename) {
                $this->attachments[$i]['multipart'] = 'related';

                $this->attachments[$i]['cid'] = uniqid(basename($attachment['name'][1]) . '@', true);

                return $this->attachments[$i]['cid'];
            }
        }

        return false;
    }

    
    public function setHeader($header, $value)
    {
        $this->headers[$header] = str_replace(["\n", "\r"], '', $value);

        return $this;
    }

    
    protected function stringToArray($email)
    {
        if (! is_array($email)) {
            return str_contains($email, ',')
                ? preg_split('/[\s,]/', $email, -1, PREG_SPLIT_NO_EMPTY)
                : (array) trim($email);
        }

        return $email;
    }

    
    public function setAltMessage($str)
    {
        $this->altMessage = (string) $str;

        return $this;
    }

    
    public function setMailType($type = 'text')
    {
        $this->mailType = $type === 'html' ? 'html' : 'text';

        return $this;
    }

    
    public function setWordWrap($wordWrap = true)
    {
        $this->wordWrap = (bool) $wordWrap;

        return $this;
    }

    
    public function setProtocol($protocol = 'mail')
    {
        $this->protocol = in_array($protocol, $this->protocols, true) ? strtolower($protocol) : 'mail';

        return $this;
    }

    
    public function setPriority($n = 3)
    {
        $this->priority = preg_match('/^[1-5]$/', (string) $n) ? (int) $n : 3;

        return $this;
    }

    
    public function setNewline($newline = "\n")
    {
        $this->newline = in_array($newline, ["\n", "\r\n", "\r"], true) ? $newline : "\n";

        return $this;
    }

    
    public function setCRLF($CRLF = "\n")
    {
        $this->CRLF = in_array($CRLF, ["\n", "\r\n", "\r"], true) ? $CRLF : "\n";

        return $this;
    }

    
    protected function getMessageID()
    {
        $from = str_replace(['>', '<'], '', $this->headers['Return-Path']);

        return '<' . uniqid('', true) . strstr($from, '@') . '>';
    }

    
    protected function getProtocol()
    {
        $this->protocol = strtolower($this->protocol);

        if (! in_array($this->protocol, $this->protocols, true)) {
            $this->protocol = 'mail';
        }

        return $this->protocol;
    }

    
    protected function getEncoding()
    {
        if (! in_array($this->encoding, $this->bitDepths, true)) {
            $this->encoding = '8bit';
        }

        foreach ($this->baseCharsets as $charset) {
            if (str_starts_with($this->charset, $charset)) {
                $this->encoding = '7bit';

                break;
            }
        }

        return $this->encoding;
    }

    
    protected function getContentType()
    {
        if ($this->mailType === 'html') {
            return $this->attachments === [] ? 'html' : 'html-attach';
        }

        if ($this->mailType === 'text' && $this->attachments !== []) {
            return 'plain-attach';
        }

        return 'plain';
    }

    
    protected function setDate()
    {
        $timezone = date('Z');
        $operator = ($timezone[0] === '-') ? '-' : '+';
        $timezone = abs((int) $timezone);
        $timezone = floor($timezone / 3600) * 100 + ($timezone % 3600) / 60;

        return sprintf('%s %s%04d', date('D, j M Y H:i:s'), $operator, $timezone);
    }

    
    protected function getMimeMessage()
    {
        return 'This is a multi-part message in MIME format.' . $this->newline . 'Your email application may not support this format.';
    }

    
    public function validateEmail($email)
    {
        if (! is_array($email)) {
            $this->setErrorMessage(lang('Email.mustBeArray'));

            return false;
        }

        foreach ($email as $val) {
            if (! $this->isValidEmail($val)) {
                $this->setErrorMessage(lang('Email.invalidAddress', [$val]));

                return false;
            }
        }

        return true;
    }

    
    public function isValidEmail($email)
    {
        if (function_exists('idn_to_ascii') && defined('INTL_IDNA_VARIANT_UTS46') && $atpos = strpos($email, '@')) {
            $email = static::substr($email, 0, ++$atpos)
                . idn_to_ascii(static::substr($email, $atpos), 0, INTL_IDNA_VARIANT_UTS46);
        }

        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    
    public function cleanEmail($email)
    {
        if (! is_array($email)) {
            return preg_match('/\<(.*)\>/', $email, $match) ? $match[1] : $email;
        }

        $cleanEmail = [];

        foreach ($email as $addy) {
            $cleanEmail[] = preg_match('/\<(.*)\>/', $addy, $match) ? $match[1] : $addy;
        }

        return $cleanEmail;
    }

    
    protected function getAltMessage()
    {
        if ($this->altMessage !== '') {
            return $this->wordWrap ? $this->wordWrap($this->altMessage, 76) : $this->altMessage;
        }

        $body = preg_match('/\<body.*?\>(.*)\<\/body\>/si', $this->body, $match) ? $match[1] : $this->body;
        $body = str_replace("\t", '', preg_replace('#<!--(.*)--\>#', '', trim(strip_tags($body))));

        for ($i = 20; $i >= 3; $i--) {
            $body = str_replace(str_repeat("\n", $i), "\n\n", $body);
        }

        $body = preg_replace('| +|', ' ', $body);

        return $this->wordWrap ? $this->wordWrap($body, 76) : $body;
    }

    
    public function wordWrap($str, $charlim = null)
    {
        $charlim ??= 0;

        if ($charlim === 0) {
            $charlim = $this->wrapChars === 0 ? 76 : $this->wrapChars;
        }

        if (str_contains($str, "\r")) {
            $str = str_replace(["\r\n", "\r"], "\n", $str);
        }

        $str = preg_replace('| +\n|', "\n", $str);

        $unwrap = [];

        if (preg_match_all('|\{unwrap\}(.+?)\{/unwrap\}|s', $str, $matches) >= 1) {
            for ($i = 0, $c = count($matches[0]); $i < $c; $i++) {
                $unwrap[] = $matches[1][$i];
                $str      = str_replace($matches[0][$i], '{{unwrapped' . $i . '}}', $str);
            }
        }

        
        
        
        $str = wordwrap($str, $charlim, "\n", false);

        
        $output = '';

        foreach (explode("\n", $str) as $line) {
            if (static::strlen($line) <= $charlim) {
                $output .= $line . $this->newline;

                continue;
            }

            $temp = '';

            do {
                if (preg_match('!\[url.+\]|://|www\.!', $line)) {
                    break;
                }

                $temp .= static::substr($line, 0, $charlim - 1);
                $line = static::substr($line, $charlim - 1);
            } while (static::strlen($line) > $charlim);

            if ($temp !== '') {
                $output .= $temp . $this->newline;
            }

            $output .= $line . $this->newline;
        }

        foreach ($unwrap as $key => $val) {
            $output = str_replace('{{unwrapped' . $key . '}}', $val, $output);
        }

        return $output;
    }

    
    protected function buildHeaders()
    {
        $this->setHeader('User-Agent', $this->userAgent);
        $this->setHeader('X-Sender', $this->cleanEmail($this->headers['From']));
        $this->setHeader('X-Mailer', $this->userAgent);
        $this->setHeader('X-Priority', $this->priorities[$this->priority]);
        $this->setHeader('Message-ID', $this->getMessageID());
        $this->setHeader('Mime-Version', '1.0');
    }

    
    protected function writeHeaders()
    {
        if ($this->protocol === 'mail' && isset($this->headers['Subject'])) {
            $this->subject = $this->headers['Subject'];
            unset($this->headers['Subject']);
        }

        reset($this->headers);
        $this->headerStr = '';

        foreach ($this->headers as $key => $val) {
            $val = trim($val);

            if ($val !== '') {
                $this->headerStr .= $key . ': ' . $val . $this->newline;
            }
        }

        if ($this->getProtocol() === 'mail') {
            $this->headerStr = rtrim($this->headerStr);
        }
    }

    
    protected function buildMessage()
    {
        if ($this->wordWrap === true && $this->mailType !== 'html') {
            $this->body = $this->wordWrap($this->body);
        }

        $this->writeHeaders();
        $hdr  = ($this->getProtocol() === 'mail') ? $this->newline : '';
        $body = '';

        switch ($this->getContentType()) {
            case 'plain':
                $hdr .= 'Content-Type: text/plain; charset='
                    . $this->charset
                    . $this->newline
                    . 'Content-Transfer-Encoding: '
                    . $this->getEncoding();

                if ($this->getProtocol() === 'mail') {
                    $this->headerStr .= $hdr;
                    $this->finalBody = $this->body;
                } else {
                    $this->finalBody = $hdr . $this->newline . $this->newline . $this->body;
                }

                return;

            case 'html':
                $boundary = uniqid('B_ALT_', true);

                if ($this->sendMultipart === false) {
                    $hdr .= 'Content-Type: text/html; charset='
                        . $this->charset . $this->newline
                        . 'Content-Transfer-Encoding: quoted-printable';
                } else {
                    $hdr  .= 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
                    $body .= $this->getMimeMessage() . $this->newline . $this->newline
                        . '--' . $boundary . $this->newline
                        . 'Content-Type: text/plain; charset=' . $this->charset . $this->newline
                        . 'Content-Transfer-Encoding: ' . $this->getEncoding() . $this->newline . $this->newline
                        . $this->getAltMessage() . $this->newline . $this->newline
                        . '--' . $boundary . $this->newline
                        . 'Content-Type: text/html; charset=' . $this->charset . $this->newline
                        . 'Content-Transfer-Encoding: quoted-printable' . $this->newline . $this->newline;
                }

                $this->finalBody = $body . $this->prepQuotedPrintable($this->body) . $this->newline . $this->newline;

                if ($this->getProtocol() === 'mail') {
                    $this->headerStr .= $hdr;
                } else {
                    $this->finalBody = $hdr . $this->newline . $this->newline . $this->finalBody;
                }

                if ($this->sendMultipart !== false) {
                    $this->finalBody .= '--' . $boundary . '--';
                }

                return;

            case 'plain-attach':
                $boundary = uniqid('B_ATC_', true);
                $hdr .= 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';

                if ($this->getProtocol() === 'mail') {
                    $this->headerStr .= $hdr;
                }

                $body .= $this->getMimeMessage() . $this->newline
                    . $this->newline
                    . '--' . $boundary . $this->newline
                    . 'Content-Type: text/plain; charset=' . $this->charset . $this->newline
                    . 'Content-Transfer-Encoding: ' . $this->getEncoding() . $this->newline
                    . $this->newline
                    . $this->body . $this->newline . $this->newline;

                $this->appendAttachments($body, $boundary);
                break;

            case 'html-attach':
                $altBoundary  = uniqid('B_ALT_', true);
                $lastBoundary = null;

                if ($this->attachmentsHaveMultipart('mixed')) {
                    $atcBoundary = uniqid('B_ATC_', true);
                    $hdr .= 'Content-Type: multipart/mixed; boundary="' . $atcBoundary . '"';
                    $lastBoundary = $atcBoundary;
                }

                if ($this->attachmentsHaveMultipart('related')) {
                    $relBoundary = uniqid('B_REL_', true);

                    $relBoundaryHeader = 'Content-Type: multipart/related; boundary="' . $relBoundary . '"';

                    if (isset($lastBoundary)) {
                        $body .= '--' . $lastBoundary . $this->newline . $relBoundaryHeader;
                    } else {
                        $hdr .= $relBoundaryHeader;
                    }

                    $lastBoundary = $relBoundary;
                }

                if ($this->getProtocol() === 'mail') {
                    $this->headerStr .= $hdr;
                }

                if (static::strlen($body) > 0) {
                    $body .= $this->newline . $this->newline;
                }

                $body .= $this->getMimeMessage() . $this->newline . $this->newline
                    . '--' . $lastBoundary . $this->newline
                    . 'Content-Type: multipart/alternative; boundary="' . $altBoundary . '"' . $this->newline . $this->newline
                    . '--' . $altBoundary . $this->newline
                    . 'Content-Type: text/plain; charset=' . $this->charset . $this->newline
                    . 'Content-Transfer-Encoding: ' . $this->getEncoding() . $this->newline . $this->newline
                    . $this->getAltMessage() . $this->newline . $this->newline
                    . '--' . $altBoundary . $this->newline
                    . 'Content-Type: text/html; charset=' . $this->charset . $this->newline
                    . 'Content-Transfer-Encoding: quoted-printable' . $this->newline . $this->newline
                    . $this->prepQuotedPrintable($this->body) . $this->newline . $this->newline
                    . '--' . $altBoundary . '--' . $this->newline . $this->newline;

                if (isset($relBoundary)) {
                    $body .= $this->newline . $this->newline;
                    $this->appendAttachments($body, $relBoundary, 'related');
                }

                
                if (isset($atcBoundary)) {
                    $body .= $this->newline . $this->newline;
                    $this->appendAttachments($body, $atcBoundary, 'mixed');
                }

                break;
        }

        $this->finalBody = ($this->getProtocol() === 'mail') ? $body : $hdr . $this->newline . $this->newline . $body;
    }

    
    protected function attachmentsHaveMultipart($type)
    {
        foreach ($this->attachments as $attachment) {
            if ($attachment['multipart'] === $type) {
                return true;
            }
        }

        return false;
    }

    
    protected function appendAttachments(&$body, $boundary, $multipart = null)
    {
        foreach ($this->attachments as $attachment) {
            if (isset($multipart) && $attachment['multipart'] !== $multipart) {
                continue;
            }

            $name = $attachment['name'][1] ?? basename($attachment['name'][0]);
            $body .= '--' . $boundary . $this->newline
                . 'Content-Type: ' . $attachment['type'] . '; name="' . $name . '"' . $this->newline
                . 'Content-Disposition: ' . $attachment['disposition'] . ';' . $this->newline
                . 'Content-Transfer-Encoding: base64' . $this->newline
                . (isset($attachment['cid']) && $attachment['cid'] !== '' ? 'Content-ID: <' . $attachment['cid'] . '>' . $this->newline : '')
                . $this->newline
                . $attachment['content'] . $this->newline;
        }

        
        
        if (isset($name)) {
            $body .= '--' . $boundary . '--';
        }
    }

    
    protected function prepQuotedPrintable($str)
    {
        
        
        
        static $asciiSafeChars = [
            
            39,
            40,
            41,
            43,
            44,
            45,
            46,
            47,
            58,
            61,
            63,
            
            48,
            49,
            50,
            51,
            52,
            53,
            54,
            55,
            56,
            57,
            
            65,
            66,
            67,
            68,
            69,
            70,
            71,
            72,
            73,
            74,
            75,
            76,
            77,
            78,
            79,
            80,
            81,
            82,
            83,
            84,
            85,
            86,
            87,
            88,
            89,
            90,
            
            97,
            98,
            99,
            100,
            101,
            102,
            103,
            104,
            105,
            106,
            107,
            108,
            109,
            110,
            111,
            112,
            113,
            114,
            115,
            116,
            117,
            118,
            119,
            120,
            121,
            122,
        ];

        
        
        $str = str_replace(['{unwrap}', '{/unwrap}'], '', $str);

        
        
        
        
        if ($this->CRLF === "\r\n") {
            return quoted_printable_encode($str);
        }

        
        $str = preg_replace(['| +|', '/\x00+/'], [' ', ''], $str);

        
        if (str_contains($str, "\r")) {
            $str = str_replace(["\r\n", "\r"], "\n", $str);
        }

        $escape = '=';
        $output = '';

        foreach (explode("\n", $str) as $line) {
            $length = static::strlen($line);
            $temp   = '';

            
            
            
            for ($i = 0; $i < $length; $i++) {
                
                $char  = $line[$i];
                $ascii = ord($char);

                
                if ($ascii === 32 || $ascii === 9) {
                    if ($i === ($length - 1)) {
                        $char = $escape . sprintf('%02s', dechex($ascii));
                    }
                }
                
                
                
                
                elseif ($ascii === 61) {
                    $char = $escape . strtoupper(sprintf('%02s', dechex($ascii)));  
                } elseif (! in_array($ascii, $asciiSafeChars, true)) {
                    $char = $escape . strtoupper(sprintf('%02s', dechex($ascii)));
                }

                
                
                if ((static::strlen($temp) + static::strlen($char)) >= 76) {
                    $output .= $temp . $escape . $this->CRLF;
                    $temp = '';
                }

                
                $temp .= $char;
            }

            
            $output .= $temp . $this->CRLF;
        }

        
        return static::substr($output, 0, static::strlen($this->CRLF) * -1);
    }

    
    protected function prepQEncoding($str)
    {
        $str = str_replace(["\r", "\n"], '', $str);

        if ($this->charset === 'UTF-8') {
            
            
            
            if (extension_loaded('iconv')) {
                $output = @iconv_mime_encode('', $str, [
                    'scheme'           => 'Q',
                    'line-length'      => 76,
                    'input-charset'    => $this->charset,
                    'output-charset'   => $this->charset,
                    'line-break-chars' => $this->CRLF,
                ]);

                
                if ($output !== false) {
                    
                    
                    
                    return static::substr($output, 2);
                }

                $chars = iconv_strlen($str, 'UTF-8');
            } elseif (extension_loaded('mbstring')) {
                $chars = mb_strlen($str, 'UTF-8');
            }
        }

        
        if (! isset($chars)) {
            $chars = static::strlen($str);
        }

        $output = '=?' . $this->charset . '?Q?';

        for ($i = 0, $length = static::strlen($output); $i < $chars; $i++) {
            $chr = ($this->charset === 'UTF-8' && extension_loaded('iconv')) ? '=' . implode('=', str_split(strtoupper(bin2hex(iconv_substr($str, $i, 1, $this->charset))), 2)) : '=' . strtoupper(bin2hex($str[$i]));

            
            
            if ($length + ($l = static::strlen($chr)) > 74) {
                $output .= '?=' . $this->CRLF 
                    . ' =?' . $this->charset . '?Q?' . $chr; 

                $length = 6 + static::strlen($this->charset) + $l; 
            } else {
                $output .= $chr;
                $length += $l;
            }
        }

        
        return $output . '?=';
    }

    
    public function send($autoClear = true)
    {
        if (! isset($this->headers['From']) && ! empty($this->fromEmail)) {
            $this->setFrom($this->fromEmail, $this->fromName);
        }

        if (! isset($this->headers['From'])) {
            $this->setErrorMessage(lang('Email.noFrom'));

            return false;
        }

        if ($this->replyToFlag === false) {
            $this->setReplyTo($this->headers['From']);
        }

        if (
            empty($this->recipients) && ! isset($this->headers['To'])
            && empty($this->BCCArray) && ! isset($this->headers['Bcc'])
            && ! isset($this->headers['Cc'])
        ) {
            $this->setErrorMessage(lang('Email.noRecipients'));

            return false;
        }

        $this->buildHeaders();

        if ($this->BCCBatchMode && count($this->BCCArray) > $this->BCCBatchSize) {
            $this->batchBCCSend();

            if ($autoClear) {
                $this->clear();
            }

            return true;
        }

        $this->buildMessage();
        $result = $this->spoolEmail();

        if ($result) {
            $this->setArchiveValues();

            if ($autoClear) {
                $this->clear();
            }

            Events::trigger('email', $this->archive);
        }

        return $result;
    }

    
    public function batchBCCSend()
    {
        $float = $this->BCCBatchSize - 1;
        $set   = '';
        $chunk = [];

        for ($i = 0, $c = count($this->BCCArray); $i < $c; $i++) {
            if (isset($this->BCCArray[$i])) {
                $set .= ', ' . $this->BCCArray[$i];
            }

            if ($i === $float) {
                $chunk[] = static::substr($set, 1);
                $float += $this->BCCBatchSize;
                $set = '';
            }

            if ($i === $c - 1) {
                $chunk[] = static::substr($set, 1);
            }
        }

        for ($i = 0, $c = count($chunk); $i < $c; $i++) {
            unset($this->headers['Bcc']);
            $bcc = $this->cleanEmail($this->stringToArray($chunk[$i]));

            if ($this->protocol !== 'smtp') {
                $this->setHeader('Bcc', implode(', ', $bcc));
            } else {
                $this->BCCArray = $bcc;
            }

            $this->buildMessage();
            $this->spoolEmail();
        }

        
        $this->setArchiveValues();
        Events::trigger('email', $this->archive);
    }

    
    protected function unwrapSpecials()
    {
        $this->finalBody = preg_replace_callback(
            '/\{unwrap\}(.*?)\{\/unwrap\}/si',
            $this->removeNLCallback(...),
            $this->finalBody,
        );
    }

    
    protected function removeNLCallback($matches)
    {
        if (str_contains($matches[1], "\r") || str_contains($matches[1], "\n")) {
            $matches[1] = str_replace(["\r\n", "\r", "\n"], '', $matches[1]);
        }

        return $matches[1];
    }

    
    protected function spoolEmail()
    {
        $this->unwrapSpecials();
        $protocol           = $this->getProtocol();
        $upperFirstProtocol = ucfirst($protocol);
        $method             = 'sendWith' . $upperFirstProtocol;

        try {
            $success = $this->{$method}();
        } catch (ErrorException $e) {
            $success = false;
            log_message('error', 'Email: ' . $method . ' threw ' . $e);
        }

        if (! $success) {
            $message = lang('Email.sendFailure' . ($protocol === 'mail' ? 'PHPMail' : $upperFirstProtocol));

            log_message('error', 'Email: ' . $message);
            log_message('error', $this->printDebuggerRaw());

            $this->setErrorMessage($message);

            return false;
        }

        $this->setErrorMessage(lang('Email.sent', [$protocol]));

        return true;
    }

    
    protected function validateEmailForShell(&$email)
    {
        if (function_exists('idn_to_ascii') && $atpos = strpos($email, '@')) {
            $email = static::substr($email, 0, ++$atpos)
                . idn_to_ascii(static::substr($email, $atpos), 0, INTL_IDNA_VARIANT_UTS46);
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) === $email && preg_match('#\A[a-z0-9._+-]+@[a-z0-9.-]{1,253}\z#i', $email);
    }

    
    protected function sendWithMail()
    {
        $recipients = is_array($this->recipients) ? implode(', ', $this->recipients) : $this->recipients;

        
        
        $from = $this->cleanEmail($this->headers['Return-Path']);

        if (! $this->validateEmailForShell($from)) {
            return mail($recipients, $this->subject, $this->finalBody, $this->headerStr);
        }

        
        
        return mail($recipients, $this->subject, $this->finalBody, $this->headerStr, '-f ' . $from);
    }

    
    protected function sendWithSendmail()
    {
        
        
        $from = $this->cleanEmail($this->headers['From']);

        $from = $this->validateEmailForShell($from) ? '-f ' . $from : '';

        if (! function_usable('popen') || false === ($fp = @popen($this->mailPath . ' -oi ' . $from . ' -t', 'w'))) {
            return false;
        }

        fwrite($fp, $this->headerStr);
        fwrite($fp, $this->finalBody);
        $status = pclose($fp);

        if ($status !== 0) {
            $this->setErrorMessage(lang('Email.exitStatus', [$status]));
            $this->setErrorMessage(lang('Email.noSocket'));

            return false;
        }

        return true;
    }

    
    protected function sendWithSmtp()
    {
        if ($this->SMTPHost === '') {
            $this->setErrorMessage(lang('Email.noHostname'));

            return false;
        }

        if (! $this->SMTPConnect() || ! $this->SMTPAuthenticate()) {
            return false;
        }

        if (! $this->sendCommand('from', $this->cleanEmail($this->headers['From']))) {
            $this->SMTPEnd();

            return false;
        }

        foreach ($this->recipients as $val) {
            if (! $this->sendCommand('to', $val)) {
                $this->SMTPEnd();

                return false;
            }
        }

        foreach ($this->CCArray as $val) {
            if ($val !== '' && ! $this->sendCommand('to', $val)) {
                $this->SMTPEnd();

                return false;
            }
        }

        foreach ($this->BCCArray as $val) {
            if ($val !== '' && ! $this->sendCommand('to', $val)) {
                $this->SMTPEnd();

                return false;
            }
        }

        if (! $this->sendCommand('data')) {
            $this->SMTPEnd();

            return false;
        }

        
        $this->sendData($this->headerStr . preg_replace('/^\./m', '..$1', $this->finalBody));
        $this->sendData($this->newline . '.');
        $reply = $this->getSMTPData();
        $this->setErrorMessage($reply);
        $this->SMTPEnd();

        if (! str_starts_with($reply, '250')) {
            $this->setErrorMessage(lang('Email.SMTPError', [$reply]));

            return false;
        }

        return true;
    }

    
    protected function SMTPEnd()
    {
        $this->sendCommand($this->SMTPKeepAlive ? 'reset' : 'quit');
    }

    
    protected function SMTPConnect()
    {
        if ($this->isSMTPConnected()) {
            return true;
        }

        $ssl = '';

        
        
        if ($this->SMTPPort === 465) {
            $ssl = 'tls://';
        }
        
        if ($this->SMTPCrypto === 'ssl') {
            $ssl = 'ssl://';
        }

        $this->SMTPConnect = fsockopen(
            $ssl . $this->SMTPHost,
            $this->SMTPPort,
            $errno,
            $errstr,
            $this->SMTPTimeout,
        );

        if (! $this->isSMTPConnected()) {
            $this->setErrorMessage(lang('Email.SMTPError', [$errno . ' ' . $errstr]));

            return false;
        }

        stream_set_timeout($this->SMTPConnect, $this->SMTPTimeout);
        $this->setErrorMessage($this->getSMTPData());

        if ($this->SMTPCrypto === 'tls') {
            $this->sendCommand('hello');
            $this->sendCommand('starttls');
            $crypto = stream_socket_enable_crypto(
                $this->SMTPConnect,
                true,
                STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT
                | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT
                | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT
                | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT,
            );

            if ($crypto !== true) {
                $this->setErrorMessage(lang('Email.SMTPError', [$this->getSMTPData()]));

                return false;
            }
        }

        return $this->sendCommand('hello');
    }

    
    protected function sendCommand($cmd, $data = '')
    {
        switch ($cmd) {
            case 'hello':
                if ($this->SMTPAuth || $this->getEncoding() === '8bit') {
                    $this->sendData('EHLO ' . $this->getHostname());
                } else {
                    $this->sendData('HELO ' . $this->getHostname());
                }

                $resp = 250;
                break;

            case 'starttls':
                $this->sendData('STARTTLS');
                $resp = 220;
                break;

            case 'from':
                $this->sendData('MAIL FROM:<' . $data . '>');
                $resp = 250;
                break;

            case 'to':
                if ($this->DSN) {
                    $this->sendData('RCPT TO:<' . $data . '> NOTIFY=SUCCESS,DELAY,FAILURE ORCPT=rfc822;' . $data);
                } else {
                    $this->sendData('RCPT TO:<' . $data . '>');
                }
                $resp = 250;
                break;

            case 'data':
                $this->sendData('DATA');
                $resp = 354;
                break;

            case 'reset':
                $this->sendData('RSET');
                $resp = 250;
                break;

            case 'quit':
                $this->sendData('QUIT');
                $resp = 221;
                break;

            default:
                $resp = null;
        }

        $reply = $this->getSMTPData();

        $this->debugMessage[]    = '<pre>' . $cmd . ': ' . $reply . '</pre>';
        $this->debugMessageRaw[] = $cmd . ': ' . $reply;

        if ($resp === null || ((int) static::substr($reply, 0, 3) !== $resp)) {
            $this->setErrorMessage(lang('Email.SMTPError', [$reply]));

            return false;
        }

        if ($cmd === 'quit') {
            fclose($this->SMTPConnect);
        }

        return true;
    }

    
    protected function SMTPAuthenticate()
    {
        if (! $this->SMTPAuth) {
            return true;
        }

        
        if ($this->SMTPUser === '' || $this->SMTPPass === '') {
            $this->setErrorMessage(lang('Email.noSMTPAuth'));

            return false;
        }

        
        $this->SMTPAuthMethod = strtolower($this->SMTPAuthMethod);

        
        if (! in_array($this->SMTPAuthMethod, ['login', 'plain'], true)) {
            $this->setErrorMessage(lang('Email.invalidSMTPAuthMethod', [$this->SMTPAuthMethod]));

            return false;
        }

        $upperAuthMethod = strtoupper($this->SMTPAuthMethod);
        
        $this->sendData('AUTH ' . $upperAuthMethod);
        $reply = $this->getSMTPData();

        if (str_starts_with($reply, '503')) {    
            return true;
        }

        
        if (! str_starts_with($reply, '334')) {
            $this->setErrorMessage(lang('Email.failureSMTPAuthMethod', [$upperAuthMethod]));

            return false;
        }

        switch ($this->SMTPAuthMethod) {
            case 'login':
                $this->sendData(base64_encode($this->SMTPUser));
                $reply = $this->getSMTPData();

                if (! str_starts_with($reply, '334')) {
                    $this->setErrorMessage(lang('Email.SMTPAuthUsername', [$reply]));

                    return false;
                }

                $this->sendData(base64_encode($this->SMTPPass));
                break;

            case 'plain':
                
                $authString = "\0" . $this->SMTPUser . "\0" . $this->SMTPPass;

                $this->sendData(base64_encode($authString));
                break;
        }

        $reply = $this->getSMTPData();
        if (! str_starts_with($reply, '235')) {  
            $errorMessage = $this->SMTPAuthMethod === 'plain' ? 'Email.SMTPAuthCredentials' : 'Email.SMTPAuthPassword';

            $this->setErrorMessage(lang($errorMessage, [$reply]));

            return false;
        }

        if ($this->SMTPKeepAlive) {
            $this->SMTPAuth = false; 
        }

        return true;
    }

    
    protected function sendData($data)
    {
        $data .= $this->newline;

        $result = null;

        for ($written = $timestamp = 0, $length = static::strlen($data); $written < $length; $written += $result) {
            if (($result = fwrite($this->SMTPConnect, static::substr($data, $written))) === false) {
                break;
            }

            
            if ($result === 0) {
                if ($timestamp === 0) {
                    $timestamp = Time::now()->getTimestamp();
                } elseif ($timestamp < (Time::now()->getTimestamp() - $this->SMTPTimeout)) {
                    $result = false;

                    break;
                }

                usleep(250000);

                continue;
            }

            $timestamp = 0;
        }

        if (! is_int($result)) {
            $this->setErrorMessage(lang('Email.SMTPDataFailure', [$data]));

            return false;
        }

        return true;
    }

    
    protected function getSMTPData()
    {
        $data = '';

        while ($str = fgets($this->SMTPConnect, 512)) {
            $data .= $str;

            if ($str[3] === ' ') {
                break;
            }
        }

        return $data;
    }

    
    protected function getHostname()
    {
        $superglobals = service('superglobals');

        $serverName = $superglobals->server('SERVER_NAME');
        if (! in_array($serverName, [null, ''], true)) {
            return $serverName;
        }

        $serverAddr = $superglobals->server('SERVER_ADDR');
        if (! in_array($serverAddr, [null, ''], true)) {
            return '[' . $serverAddr . ']';
        }

        $hostname = gethostname();
        if ($hostname !== false) {
            return $hostname;
        }

        return '[127.0.0.1]';
    }

    
    public function printDebugger($include = ['headers', 'subject', 'body'])
    {
        $msg = implode('', $this->debugMessage);

        
        $rawData = '';

        if (! is_array($include)) {
            $include = [$include];
        }

        if (in_array('headers', $include, true)) {
            $rawData = htmlspecialchars($this->headerStr) . "\n";
        }
        if (in_array('subject', $include, true)) {
            $rawData .= htmlspecialchars($this->subject) . "\n";
        }
        if (in_array('body', $include, true)) {
            $rawData .= htmlspecialchars($this->finalBody);
        }

        return $msg . ($rawData === '' ? '' : '<pre>' . $rawData . '</pre>');
    }

    
    private function printDebuggerRaw(): string
    {
        return implode("\n", $this->debugMessageRaw);
    }

    
    protected function setErrorMessage($msg)
    {
        $this->debugMessage[]    = $msg . '<br>';
        $this->debugMessageRaw[] = $msg;
    }

    
    protected function mimeTypes($ext = '')
    {
        $mime = Mimes::guessTypeFromExtension(strtolower($ext));

        return empty($mime) ? 'application/x-unknown-content-type' : $mime;
    }

    public function __destruct()
    {
        if ($this->isSMTPConnected()) {
            try {
                $this->sendCommand('quit');
            } catch (ErrorException $e) {
                $protocol = $this->getProtocol();
                $method   = 'sendWith' . ucfirst($protocol);
                log_message('error', 'Email: ' . $method . ' threw ' . $e);
            }
        }
    }

    
    protected static function strlen($str)
    {
        return (static::$func_overload) ? mb_strlen($str, '8bit') : strlen($str);
    }

    
    protected static function substr($str, $start, $length = null)
    {
        if (static::$func_overload) {
            return mb_substr($str, $start, $length, '8bit');
        }

        return isset($length) ? substr($str, $start, $length) : substr($str, $start);
    }

    
    protected function setArchiveValues(): array
    {
        
        $this->archive = array_merge(get_object_vars($this), $this->tmpArchive);
        unset($this->archive['archive']);

        
        $this->tmpArchive = [];

        return $this->archive;
    }

    
    protected function isSMTPConnected(): bool
    {
        return $this->SMTPConnect !== null
            && $this->SMTPConnect !== false
            && get_debug_type($this->SMTPConnect) !== 'resource (closed)';
    }
}
