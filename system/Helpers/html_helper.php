<?php

declare(strict_types=1);



use CodeIgniter\Files\Exceptions\FileNotFoundException;
use Config\DocTypes;
use Config\Mimes;



if (! function_exists('ul')) {
    
    function ul(array $list, $attributes = ''): string
    {
        return _list('ul', $list, $attributes);
    }
}

if (! function_exists('ol')) {
    
    function ol(array $list, $attributes = ''): string
    {
        return _list('ol', $list, $attributes);
    }
}

if (! function_exists('_list')) {
    
    function _list(string $type = 'ul', $list = [], $attributes = '', int $depth = 0): string
    {
        
        $out = str_repeat(' ', $depth)
                
                . '<' . $type . stringify_attributes($attributes) . ">\n";

        
        

        foreach ($list as $key => $val) {
            $out .= str_repeat(' ', $depth + 2) . '<li>';

            if (! is_array($val)) {
                $out .= $val;
            } else {
                $out .= $key
                    . "\n"
                    . _list($type, $val, '', $depth + 4)
                    . str_repeat(' ', $depth + 2);
            }

            $out .= "</li>\n";
        }

        
        return $out . str_repeat(' ', $depth) . '</' . $type . ">\n";
    }
}

if (! function_exists('img')) {
    
    function img($src = '', bool $indexPage = false, $attributes = ''): string
    {
        if (! is_array($src)) {
            $src = ['src' => $src];
        }
        if (! isset($src['src'])) {
            $src['src'] = $attributes['src'] ?? '';
        }
        if (! isset($src['alt'])) {
            $src['alt'] = $attributes['alt'] ?? '';
        }

        $img = '<img';

        
        if (preg_match('#^([a-z]+:)?//#i', $src['src']) !== 1 && ! str_starts_with($src['src'], 'data:')) {
            if ($indexPage) {
                $img .= ' src="' . site_url($src['src']) . '"';
            } else {
                $img .= ' src="' . slash_item('baseURL') . $src['src'] . '"';
            }

            unset($src['src']);
        }

        
        foreach ($src as $key => $value) {
            $img .= ' ' . $key . '="' . $value . '"';
        }

        
        if (is_array($attributes)) {
            unset($attributes['alt'], $attributes['src']);
        }

        return $img . stringify_attributes($attributes) . _solidus() . '>';
    }
}

if (! function_exists('img_data')) {
    
    function img_data(string $path, ?string $mime = null): string
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw FileNotFoundException::forFileNotFound($path);
        }

        
        $handle = fopen($path, 'rb');
        $data   = fread($handle, filesize($path));
        fclose($handle);

        
        $data = base64_encode($data);

        
        $mime ??= Mimes::guessTypeFromExtension(pathinfo($path, PATHINFO_EXTENSION)) ?? 'image/jpg';

        return 'data:' . $mime . ';base64,' . $data;
    }
}

if (! function_exists('doctype')) {
    
    function doctype(string $type = 'html5'): string
    {
        $config   = new DocTypes();
        $doctypes = $config->list;

        return $doctypes[$type] ?? '';
    }
}

if (! function_exists('script_tag')) {
    
    function script_tag($src = '', bool $indexPage = false): string
    {
        $cspNonce = csp_script_nonce();
        $cspNonce = $cspNonce !== '' ? ' ' . $cspNonce : $cspNonce;
        $script   = '<script' . $cspNonce . ' ';
        if (! is_array($src)) {
            $src = ['src' => $src];
        }

        foreach ($src as $k => $v) {
            if ($k === 'src' && preg_match('#^([a-z]+:)?//#i', $v) !== 1) {
                if ($indexPage) {
                    $script .= 'src="' . site_url($v) . '" ';
                } else {
                    $script .= 'src="' . slash_item('baseURL') . $v . '" ';
                }
            } else {
                
                $script .= $k . (null === $v ? ' ' : '="' . $v . '" ');
            }
        }

        return rtrim($script) . '></script>';
    }
}

if (! function_exists('link_tag')) {
    
    function link_tag(
        $href = '',
        string $rel = 'stylesheet',
        string $type = 'text/css',
        string $title = '',
        string $media = '',
        bool $indexPage = false,
        string $hreflang = '',
    ): string {
        $attributes = [];
        
        if (is_array($href)) {
            $rel       = $href['rel'] ?? $rel;
            $type      = $href['type'] ?? $type;
            $title     = $href['title'] ?? $title;
            $media     = $href['media'] ?? $media;
            $hreflang  = $href['hreflang'] ?? '';
            $indexPage = $href['indexPage'] ?? $indexPage;
            $href      = $href['href'] ?? '';
        }

        if (preg_match('#^([a-z]+:)?//#i', $href) !== 1) {
            $attributes['href'] = $indexPage ? site_url($href) : slash_item('baseURL') . $href;
        } else {
            $attributes['href'] = $href;
        }

        if ($hreflang !== '') {
            $attributes['hreflang'] = $hreflang;
        }

        $attributes['rel'] = $rel;

        if ($type !== '' && $rel !== 'canonical' && $hreflang === '' && ! ($rel === 'alternate' && $media !== '')) {
            $attributes['type'] = $type;
        }

        if ($media !== '') {
            $attributes['media'] = $media;
        }

        if ($title !== '') {
            $attributes['title'] = $title;
        }

        return '<link' . stringify_attributes($attributes) . _solidus() . '>';
    }
}

if (! function_exists('video')) {
    
    function video($src, string $unsupportedMessage = '', string $attributes = '', array $tracks = [], bool $indexPage = false): string
    {
        if (is_array($src)) {
            return _media('video', $src, $unsupportedMessage, $attributes, $tracks);
        }

        $video = '<video';

        if (_has_protocol($src)) {
            $video .= ' src="' . $src . '"';
        } elseif ($indexPage) {
            $video .= ' src="' . site_url($src) . '"';
        } else {
            $video .= ' src="' . slash_item('baseURL') . $src . '"';
        }

        if ($attributes !== '') {
            $video .= ' ' . $attributes;
        }

        $video .= ">\n";

        foreach ($tracks as $track) {
            $video .= _space_indent() . $track . "\n";
        }

        if ($unsupportedMessage !== '') {
            $video .= _space_indent()
                    . $unsupportedMessage
                    . "\n";
        }

        return $video . "</video>\n";
    }
}

if (! function_exists('audio')) {
    
    function audio($src, string $unsupportedMessage = '', string $attributes = '', array $tracks = [], bool $indexPage = false): string
    {
        if (is_array($src)) {
            return _media('audio', $src, $unsupportedMessage, $attributes, $tracks);
        }

        $audio = '<audio';

        if (_has_protocol($src)) {
            $audio .= ' src="' . $src . '"';
        } elseif ($indexPage) {
            $audio .= ' src="' . site_url($src) . '"';
        } else {
            $audio .= ' src="' . slash_item('baseURL') . $src . '"';
        }

        if ($attributes !== '') {
            $audio .= ' ' . $attributes;
        }

        $audio .= '>';

        foreach ($tracks as $track) {
            $audio .= "\n" . _space_indent() . $track;
        }

        if ($unsupportedMessage !== '') {
            $audio .= "\n" . _space_indent() . $unsupportedMessage . "\n";
        }

        return $audio . "</audio>\n";
    }
}

if (! function_exists('_media')) {
    
    function _media(string $name, array $types = [], string $unsupportedMessage = '', string $attributes = '', array $tracks = []): string
    {
        $media = '<' . $name;

        if ($attributes === '') {
            $media .= '>';
        } else {
            $media .= ' ' . $attributes . '>';
        }

        $media .= "\n";

        foreach ($types as $option) {
            $media .= _space_indent() . $option . "\n";
        }

        foreach ($tracks as $track) {
            $media .= _space_indent() . $track . "\n";
        }

        if ($unsupportedMessage !== '') {
            $media .= _space_indent() . $unsupportedMessage . "\n";
        }

        return $media . ('</' . $name . ">\n");
    }
}

if (! function_exists('source')) {
    
    function source(string $src, string $type = 'unknown', string $attributes = '', bool $indexPage = false): string
    {
        if (! _has_protocol($src)) {
            $src = $indexPage ? site_url($src) : slash_item('baseURL') . $src;
        }

        $source = '<source src="' . $src
                . '" type="' . $type . '"';

        if ($attributes !== '') {
            $source .= ' ' . $attributes;
        }

        return $source . _solidus() . '>';
    }
}

if (! function_exists('track')) {
    
    function track(string $src, string $kind, string $srcLanguage, string $label): string
    {
        return '<track src="' . $src
                . '" kind="' . $kind
                . '" srclang="' . $srcLanguage
                . '" label="' . $label
                . '"' . _solidus() . '>';
    }
}

if (! function_exists('object')) {
    
    function object(string $data, string $type = 'unknown', string $attributes = '', array $params = [], bool $indexPage = false): string
    {
        if (! _has_protocol($data)) {
            $data = $indexPage ? site_url($data) : slash_item('baseURL') . $data;
        }

        $object = '<object data="' . $data . '" '
                . $attributes . '>';

        if ($params !== []) {
            $object .= "\n";
        }

        foreach ($params as $param) {
            $object .= _space_indent() . $param . "\n";
        }

        return $object . "</object>\n";
    }
}

if (! function_exists('param')) {
    
    function param(string $name, string $value, string $type = 'ref', string $attributes = ''): string
    {
        return '<param name="' . $name
                . '" type="' . $type
                . '" value="' . $value
                . '" ' . $attributes . _solidus() . '>';
    }
}

if (! function_exists('embed')) {
    
    function embed(string $src, string $type = 'unknown', string $attributes = '', bool $indexPage = false): string
    {
        if (! _has_protocol($src)) {
            $src = $indexPage ? site_url($src) : slash_item('baseURL') . $src;
        }

        return '<embed src="' . $src
                . '" type="' . $type . '" '
                . $attributes . _solidus() . ">\n";
    }
}

if (! function_exists('_has_protocol')) {
    
    function _has_protocol(string $url)
    {
        return preg_match('#^([a-z]+:)?//#i', $url);
    }
}

if (! function_exists('_space_indent')) {
    
    function _space_indent(int $depth = 2): string
    {
        return str_repeat(' ', $depth);
    }
}
