<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class HtmlSanitizerService
{
    private readonly HtmlSanitizer $sanitizer;

    public function __construct()
    {
        $config = (new HtmlSanitizerConfig())
            ->withMaxInputLength(500_000)
            ->allowElement('p')
            ->allowElement('h2')
            ->allowElement('h3')
            ->allowElement('h4')
            ->allowElement('strong')
            ->allowElement('b')
            ->allowElement('em')
            ->allowElement('i')
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            ->allowElement('blockquote')
            ->allowElement('br')
            ->allowElement('figure')
            ->allowElement('figcaption')
            ->allowElement('a', ['href', 'title'])
            ->allowLinkSchemes(['http', 'https'])
            ->forceAttribute('a', 'rel', 'noopener noreferrer nofollow')
            ->dropElement('script')
            ->dropElement('style')
            ->dropElement('iframe')
            ->dropElement('object')
            ->dropElement('embed')
            ->dropElement('svg')
            ->dropElement('form')
            ->dropElement('input')
            ->dropElement('button');

        $this->sanitizer = new HtmlSanitizer($config);
    }

    /**
     * Sanitize untrusted or editorial HTML into safe semantic markup.
     */
    public function sanitize(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        return trim($this->sanitizer->sanitize($html));
    }

    /**
     * Sanitize HTML and escape CDATA closing sequences for RSS/XML feeds.
     */
    public function sanitizeForCdata(?string $html): string
    {
        $clean = $this->sanitize($html);

        return str_replace(']]>', ']]]]><![CDATA[>', $clean);
    }
}

