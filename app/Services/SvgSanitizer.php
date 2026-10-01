<?php

namespace App\Services;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Http\UploadedFile;

/**
 * Sanitizes user-uploaded SVG files before they are stored and served.
 *
 * A strict allowlist of elements and attributes plus a hard rejection of
 * DOCTYPE/entity declarations prevents script execution, external reference
 * exfiltration and entity-expansion attacks. Returns NULL when the document is
 * not a safe, well-formed SVG - callers must refuse to store it then.
 */
final class SvgSanitizer
{
    private const SVG_NAMESPACE = 'http://www.w3.org/2000/svg';

    private const ALLOWED_ELEMENTS = [
        'svg',
        'g',
        'path',
        'rect',
        'circle',
        'ellipse',
        'line',
        'polyline',
        'polygon',
        'text',
        'tspan',
        'defs',
        'linearGradient',
        'radialGradient',
        'stop',
        'clipPath',
        'mask',
        'pattern',
        'symbol',
        'title',
        'desc',
    ];

    /**
     * Determine whether an uploaded file or content string represents an SVG
     * based on detected MIME type and content inspection, never trusting
     * client-supplied filename extensions.
     */
    public function isSvg(mixed $fileOrContent, ?string $mimeType = null): bool
    {
        if ($fileOrContent instanceof UploadedFile) {
            $realPath = $fileOrContent->getRealPath();
            $content = ($realPath && file_exists($realPath))
                ? (string) @file_get_contents($realPath, false, null, 0, 4096)
                : '';
            $mime = strtolower((string) $fileOrContent->getMimeType());
        } elseif (is_string($fileOrContent)) {
            $content = $fileOrContent;
            $mime = $mimeType !== null ? strtolower(trim($mimeType)) : null;
        } else {
            return false;
        }

        if ($content === '') {
            return false;
        }

        // 1. Check for binary raster image magic bytes (PNG, JPEG, GIF, WEBP, ICO).
        // If it has raster magic bytes, it is NEVER an SVG, regardless of extension or reported mime.
        if (
            str_starts_with($content, "\x89PNG\r\n\x1a\n") ||
            str_starts_with($content, "\xFF\xD8\xFF") ||
            str_starts_with($content, 'GIF87a') ||
            str_starts_with($content, 'GIF89a') ||
            (str_starts_with($content, 'RIFF') && substr($content, 8, 4) === 'WEBP') ||
            str_starts_with($content, "\x00\x00\x01\x00") ||
            str_starts_with($content, "\x00\x00\x02\x00")
        ) {
            return false;
        }

        // 2. Detected MIME
        if ($mime === 'image/svg+xml' || $mime === 'image/svg') {
            return true;
        }

        // 3. Content inspection for SVG root/tag
        return (bool) preg_match('/<svg[\s>\/]/i', $content);
    }

    public function sanitize(string $content): ?string
    {
        $content = trim($content);

        if ($content === '') {
            return null;
        }

        $lower = strtolower($content);

        if (str_contains($lower, '<!doctype') || str_contains($lower, '<!entity')) {
            return null;
        }

        $dom = new DOMDocument;

        $internalErrors = libxml_use_internal_errors(true);

        try {
            $loaded = $dom->loadXML($content, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($internalErrors);
        }

        if (! $loaded) {
            return null;
        }

        $root = $dom->documentElement;

        if (! $root instanceof DOMElement || $root->localName !== 'svg') {
            return null;
        }

        if ($root->namespaceURI !== null && $root->namespaceURI !== self::SVG_NAMESPACE) {
            return null;
        }

        if (! $this->elementAllowed($root)) {
            return null;
        }

        foreach (iterator_to_array($root->attributes) as $attribute) {
            if ($this->isBannedAttribute($attribute)) {
                $root->removeAttributeNode($attribute);
            }
        }

        $this->sanitizeChildren($root);

        $output = $dom->saveXML($root);

        return is_string($output) ? $output : null;
    }

    private function sanitizeChildren(DOMNode $node): void
    {
        /** @var list<DOMNode> $children */
        $children = iterator_to_array($node->childNodes);

        foreach ($children as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                if (! $this->elementAllowed($child)) {
                    $node->removeChild($child);

                    continue;
                }

                $attributes = iterator_to_array($child->attributes);

                foreach ($attributes as $attribute) {
                    if ($this->isBannedAttribute($attribute)) {
                        $child->removeAttributeNode($attribute);
                    }
                }

                $this->sanitizeChildren($child);
            } elseif ($child->nodeType === XML_PI_NODE || $child->nodeType === XML_COMMENT_NODE) {
                $node->removeChild($child);
            }
        }
    }

    private function elementAllowed(DOMNode $node): bool
    {
        $name = $node->localName !== null ? strtolower($node->localName) : strtolower($node->nodeName);

        return in_array($name, self::ALLOWED_ELEMENTS, true);
    }

    private function isBannedAttribute(DOMAttr $attribute): bool
    {
        $name = strtolower($attribute->name);
        $value = strtolower(trim($attribute->value));

        if (str_starts_with($name, 'on')) {
            return true;
        }

        if ($name === 'style') {
            return true;
        }

        if (in_array($name, ['href', 'xlink:href', 'src'], true)) {
            // Only in-document fragment references (#...) are allowed.
            return ! str_starts_with($value, '#');
        }

        return str_contains($value, 'javascript:')
            || str_contains($value, 'vbscript:')
            || str_contains($value, 'data:');
    }
}
