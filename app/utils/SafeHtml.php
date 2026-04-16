<?php

namespace Akti\Utils;

/**
 * Classe para geração segura de HTML.
 */
class SafeHtml
{
    private const DROP_WITH_CONTENT = [
        'script',
        'style',
        'iframe',
        'object',
        'embed',
        'link',
        'meta',
        'base',
        'form',
        'input',
        'button',
        'textarea',
        'select',
        'option',
    ];

    /**
     * Sanitiza dados de entrada.
     *
     * @param string $html Html
     * @param array $allowedTags Allowed tags
     * @param array $allowedAttributes Allowed attributes
     * @return string
     */
    public static function sanitizeFragment(string $html, array $allowedTags, array $allowedAttributes = []): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        if (!class_exists('\DOMDocument')) {
            return trim(strip_tags($html, self::buildAllowedTagList($allowedTags)));
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previousState = libxml_use_internal_errors(true);
        $wrapper = '<!DOCTYPE html><html><body><div>' . $html . '</div></body></html>';
        $payload = function_exists('mb_convert_encoding')
            ? mb_convert_encoding($wrapper, 'HTML-ENTITIES', 'UTF-8')
            : $wrapper;

        $dom->loadHTML($payload, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        libxml_clear_errors();
        libxml_use_internal_errors($previousState);

        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body instanceof \DOMElement || !$body->firstChild instanceof \DOMNode) {
            return trim(strip_tags($html, self::buildAllowedTagList($allowedTags)));
        }

        $root = $body->firstChild;
        self::sanitizeChildren($root, $allowedTags, $allowedAttributes);

        $output = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $output .= $dom->saveHTML($child);
        }

        return trim($output);
    }

    /**
     * Sanitiza dados de entrada.
     *
     * @param \DOMNode $parent Parent
     * @param array $allowedTags Allowed tags
     * @param array $allowedAttributes Allowed attributes
     * @return void
     */
    private static function sanitizeChildren(\DOMNode $parent, array $allowedTags, array $allowedAttributes): void
    {
        foreach (iterator_to_array($parent->childNodes) as $child) {
            self::sanitizeNode($child, $allowedTags, $allowedAttributes);
        }
    }

    /**
     * Sanitiza dados de entrada.
     *
     * @param \DOMNode $node Node
     * @param array $allowedTags Allowed tags
     * @param array $allowedAttributes Allowed attributes
     * @return void
     */
    private static function sanitizeNode(\DOMNode $node, array $allowedTags, array $allowedAttributes): void
    {
        $parent = $node->parentNode;
        if (!$parent instanceof \DOMNode) {
            return;
        }

        if ($node instanceof \DOMComment) {
            $parent->removeChild($node);
            return;
        }

        if ($node instanceof \DOMText) {
            return;
        }

        if (!$node instanceof \DOMElement) {
            $parent->removeChild($node);
            return;
        }

        $tag = strtolower($node->tagName);
        if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
            $parent->removeChild($node);
            return;
        }

        if (!in_array($tag, $allowedTags, true)) {
            self::unwrapNode($node);
            return;
        }

        self::sanitizeAttributes($node, $allowedAttributes);
        self::sanitizeChildren($node, $allowedTags, $allowedAttributes);

        if ($tag === 'a' && strtolower($node->getAttribute('target')) === '_blank') {
            $rels = preg_split('/\s+/', strtolower(trim($node->getAttribute('rel')))) ?: [];
            $rels = array_values(array_unique(array_filter(array_merge($rels, ['noopener', 'noreferrer']))));
            $node->setAttribute('rel', implode(' ', $rels));
        }
    }

    /**
     * Sanitiza dados de entrada.
     *
     * @param \DOMElement $element Element
     * @param array $allowedAttributes Allowed attributes
     * @return void
     */
    private static function sanitizeAttributes(\DOMElement $element, array $allowedAttributes): void
    {
        $tag = strtolower($element->tagName);
        $globalAllowed = $allowedAttributes['*'] ?? [];
        $tagAllowed = $allowedAttributes[$tag] ?? [];
        $allowed = array_values(array_unique(array_merge($globalAllowed, $tagAllowed)));

        $attributeNames = [];
        if ($element->hasAttributes()) {
            for ($index = 0; $index < $element->attributes->length; $index++) {
                $attributeNames[] = $element->attributes->item($index)->nodeName;
            }
        }

        foreach ($attributeNames as $attributeName) {
            $attributeKey = strtolower($attributeName);
            if (!in_array($attributeKey, $allowed, true)) {
                $element->removeAttribute($attributeName);
                continue;
            }

            $value = trim($element->getAttribute($attributeName));
            if ($value === '') {
                $element->removeAttribute($attributeName);
                continue;
            }

            switch ($attributeKey) {
                case 'href':
                case 'src':
                    if (!self::isSafeUrl($value)) {
                        $element->removeAttribute($attributeName);
                        continue 2;
                    }
                    $element->setAttribute($attributeName, self::stripControlChars($value));
                    break;

                case 'target':
                    $target = strtolower($value);
                    if (!in_array($target, ['_blank', '_self', '_parent', '_top'], true)) {
                        $element->removeAttribute($attributeName);
                        continue 2;
                    }
                    $element->setAttribute($attributeName, $target);
                    break;

                case 'class':
                    $cleanClass = preg_replace('/[^a-zA-Z0-9_\-\s]/', '', $value);
                    $cleanClass = trim(preg_replace('/\s+/', ' ', $cleanClass ?? ''));
                    if ($cleanClass === '') {
                        $element->removeAttribute($attributeName);
                        continue 2;
                    }
                    $element->setAttribute($attributeName, $cleanClass);
                    break;

                case 'width':
                case 'height':
                    if (!preg_match('/^\d{1,4}%?$/', $value)) {
                        $element->removeAttribute($attributeName);
                        continue 2;
                    }
                    $element->setAttribute($attributeName, $value);
                    break;

                default:
                    $cleanValue = self::stripControlChars($value);
                    if ($cleanValue === '') {
                        $element->removeAttribute($attributeName);
                        continue 2;
                    }
                    $element->setAttribute($attributeName, $cleanValue);
            }
        }
    }

    /**
     * Unwrap node.
     *
     * @param \DOMElement $element Element
     * @return void
     */
    private static function unwrapNode(\DOMElement $element): void
    {
        $parent = $element->parentNode;
        if (!$parent instanceof \DOMNode) {
            return;
        }

        while ($element->firstChild instanceof \DOMNode) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    /**
     * Constrói dados ou estrutura.
     *
     * @param array $allowedTags Allowed tags
     * @return string
     */
    private static function buildAllowedTagList(array $allowedTags): string
    {
        $allowed = '';
        foreach ($allowedTags as $tag) {
            $allowed .= '<' . $tag . '>';
        }

        return $allowed;
    }

    /**
     * Strip control chars.
     *
     * @param string $value Valor
     * @return string
     */
    private static function stripControlChars(string $value): string
    {
        return preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '';
    }

    /**
     * Verifica uma condição booleana.
     *
     * @param string $url Url
     * @return bool
     */
    private static function isSafeUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '' || strpos($url, '#') === 0) {
            return true;
        }

        $normalized = strtolower($url);
        if (strpos($normalized, 'javascript:') === 0 || strpos($normalized, 'vbscript:') === 0) {
            return false;
        }

        if (strpos($normalized, 'data:') === 0) {
            return false;
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
            return preg_match('#^(https?:|mailto:|tel:)#i', $url) === 1;
        }

        return true;
    }
}