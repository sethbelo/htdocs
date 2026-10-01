<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Assainissement du HTML produit par l'éditeur de notes (liste blanche).
 *
 *   • balises dangereuses supprimées avec leur contenu (script, style, iframe…) ;
 *   • balises inconnues « déballées » (le texte est conservé) ;
 *   • tous les attributs retirés (on*, style, class…) sauf : href (http, https,
 *     mailto, tel) sur <a>, data-type="checklist" sur <ul>, data-checked sur <li>.
 */
final class HtmlSanitizer
{
    private const ALLOWED = ['p', 'br', 'h1', 'h2', 'h3', 'ul', 'ol', 'li', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'del', 'blockquote', 'hr', 'a', 'code', 'pre', 'div', 'span', 'mark'];

    private const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'link', 'meta', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math', 'template', 'noscript', 'base', 'frame', 'frameset', 'img', 'video', 'audio', 'title', 'head'];

    private const RENAME = ['b' => 'strong', 'i' => 'em', 'strike' => 's', 'del' => 's', 'div' => 'p'];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><html><body><div id="__root">'.$html.'</div></body></html>', LIBXML_NONET | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('__root');
        if (! $root) {
            return '';
        }
        self::walk($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }

    private static function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
                $node->removeChild($child);

                continue;
            }
            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);
            if (in_array($tag, self::DROP, true)) {
                $node->removeChild($child);

                continue;
            }

            self::walk($child);

            if (! in_array($tag, self::ALLOWED, true)) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            $element = isset(self::RENAME[$tag]) ? self::rename($child, self::RENAME[$tag]) : $child;
            self::filterAttributes($element);
        }
    }

    private static function rename(DOMElement $element, string $tag): DOMElement
    {
        $replacement = $element->ownerDocument->createElement($tag);
        foreach ($element->attributes as $attribute) {
            $replacement->setAttribute($attribute->name, $attribute->value);
        }
        while ($element->firstChild) {
            $replacement->appendChild($element->firstChild);
        }
        $element->parentNode->replaceChild($replacement, $element);

        return $replacement;
    }

    private static function filterAttributes(DOMElement $element): void
    {
        $tag = strtolower($element->tagName);
        $keep = [];
        if ($tag === 'a') {
            $href = trim((string) $element->getAttribute('href'));
            if (preg_match('#^(https?://|mailto:|tel:)#i', $href)) {
                $keep['href'] = $href;
                $keep['rel'] = 'noopener noreferrer';
                $keep['target'] = '_blank';
            }
        }
        if ($tag === 'ul' && $element->getAttribute('data-type') === 'checklist') {
            $keep['data-type'] = 'checklist';
        }
        if ($tag === 'li' && $element->hasAttribute('data-checked')) {
            $keep['data-checked'] = $element->getAttribute('data-checked') === 'true' ? 'true' : 'false';
        }

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $element->removeAttribute($attribute->name);
        }
        foreach ($keep as $name => $value) {
            $element->setAttribute($name, $value);
        }
    }
}
