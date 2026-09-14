<?php
/** Rebuild editorial HTML using an explicit element and attribute allowlist. */
function clean_article_html(string $html): string
{
    $doc = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>', LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $allowed = ['p','br','strong','b','em','i','u','s','h2','h3','h4','ul','ol','li','blockquote','pre','code','a','img'];
    $render = function ($node) use (&$render, $allowed): string {
        if ($node instanceof DOMText) return htmlspecialchars($node->textContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        if (!($node instanceof DOMElement)) return '';
        $tag = strtolower($node->tagName);
        if (in_array($tag, ['script','style','iframe','object','embed','svg','math','form','input','button','template'], true)) return '';
        $inner = '';
        foreach ($node->childNodes as $child) $inner .= $render($child);
        if (!in_array($tag, $allowed, true)) return $inner;
        $attrs = '';
        if ($tag === 'a' || $tag === 'img') {
            $key = $tag === 'a' ? 'href' : 'src';
            $url = trim($node->getAttribute($key));
            $safe = preg_match('~^https?://~i', $url) || (str_starts_with($url, '/') && !str_starts_with($url, '//'));
            if ($safe && !preg_match('/[\x00-\x20\\\\]/', $url)) $attrs .= ' ' . $key . '="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"';
            elseif ($tag === 'img') return '';
            if ($tag === 'a') $attrs .= ' rel="noopener noreferrer"';
            if ($tag === 'img') $attrs .= ' loading="lazy" alt="' . htmlspecialchars($node->getAttribute('alt'), ENT_QUOTES, 'UTF-8') . '"';
        }
        return '<' . $tag . $attrs . '>' . (in_array($tag, ['br','img'], true) ? '' : $inner . '</' . $tag . '>');
    };
    $result = '';
    foreach ($doc->getElementsByTagName('body')->item(0)->childNodes as $child) $result .= $render($child);
    return $result;
}
