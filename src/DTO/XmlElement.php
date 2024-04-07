<?php

declare(strict_types=1);

namespace App\DTO;

use LogicException;

class XmlElement
{
    public string $name;

    /** @var ?array<string, string|int> $attributes */
    public ?array $attributes;

    public ?string $content;

    /** @var self[] $children */
    public array $children;

    public static function fromString(string $xml): self
    {
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, $xml, $tags);
        xml_parser_free($parser);

        $elements = [];  // the currently filling [child] XmlElement array
        $stack = [];
        foreach ($tags as $tag) {
            $index = count($elements);
            if ($tag['type'] == "complete" || $tag['type'] == "open") {
                $elements[$index] = new self();
                $elements[$index]->name = $tag['tag'];
                $elements[$index]->attributes = $tag['attributes'] ?? null;
                $elements[$index]->content = $tag['value'] ?? null;
                if ($tag['type'] == "open") {  // push
                    $elements[$index]->children = [];
                    $stack[count($stack)] = &$elements;
                    $elements = &$elements[$index]->children;
                }
            }
            if ($tag['type'] == "close") {  // pop
                $elements = &$stack[count($stack) - 1];
                unset($stack[count($stack) - 1]);
            }
        }

        if (count($elements) > 1) {
            throw new LogicException('Got 2 top-level elements');
        }

        return $elements[0];  // the single top-level element
    }

    public function findFirstChildWithName(string $name): ?self
    {
        foreach ($this->children as $child) {
            if ($child->name === $name) {
                return $child;
            }
        }

        return null;
    }
}
