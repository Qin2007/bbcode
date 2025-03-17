<?php namespace BBCode;

use Error;
use JsonSerializable;

function htmlspecialchars12(string $value): string
{
    $html = str_replace('"', '&quot;',
        str_replace('>', '&gt;',
            str_replace('<', '&lt;',
                str_replace('\'', '&#39;',
                    str_replace('&', '&amp;',
                        "$value")))));
    return ($html);
}

function normalize_newlines(string $string): string
{
    return str_replace("\r", "\n", str_replace("\r\n", "\n", "$string"));
}

function json_fromArray(mixed $json, bool|int $JSON_PRETTY_PRINT = true): false|string
{
    $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
    if (is_int($JSON_PRETTY_PRINT) && $JSON_PRETTY_PRINT >= 0) {
        $options |= JSON_PRETTY_PRINT;
        $json = json_encode($json, $options);
        return preg_replace_callback('/^ +/m', (function (array $matches)
        use ($JSON_PRETTY_PRINT): string {
            return str_repeat(' ', (strlen($matches[0]) / 4) * $JSON_PRETTY_PRINT);
        }), $json);
    } elseif (is_bool($JSON_PRETTY_PRINT) && $JSON_PRETTY_PRINT) {
        $options |= JSON_PRETTY_PRINT;
    }
    return json_encode($json, $options);
}

class _AST2_TEXT implements JsonSerializable
{
    private string $string;

    public function __construct(string $string)
    {
        $this->string = $string;
    }

    public function toString(): string
    {
        return ("$this->string");
    }

    public function __toString(): string
    {
        return ("$this->string");
    }

    public function jsonSerialize(): array
    {
        return ['string' => "$this->string", 'type' => '_AST2_TEXT'];
    }

    public function invalidate(): self
    {
        return $this;
    }
}

function href(string $href, string $children): string
{
    if (strlen($href) > 0) {
        return "<a href=\"$href\">$children</a>";
    } else {
        return "$children";
    }
}

//class headerCounter{private int $counted = 0;public function __construct(private readonly string $name){}public function send(string $value): self{$counted = ++$this->counted;header("$this->name-$counted: $value");return $this;}}

class _AST2 implements JsonSerializable
{
    private string $name;
    private array $attrs;
    private array $children = [];
    private bool $invalidated = false;

    //private string $outerHTML;

    public function __construct(string $tag_name, array $attrs = [])//, string $outerHTML = '</>'
    {
        $this->name = $tag_name;
        $this->attrs = $attrs;
        //$this->outerHTML = $outerHTML;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function appendChild(self|_AST2_TEXT $child): self
    {
        $this->children[] = $child;
        return $this;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function setAttr(string $name, string $value): self
    {
        $this->attrs[$name] = $value;
        return $this;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function invalidate(): self
    {
        $this->invalidated = true;
        return $this;
    }

    public function toString(array $parsemodes = [], ?array $options = null): string
    {
        //$class = '';
        $attrs_str = '';
        foreach ($this->attrs as $key => $val) {
            $key = htmlspecialchars12($key);
            $val = htmlspecialchars12($val);
            $attrs_str = "$attrs_str $key=\"$val\"";
            //if ($key === 'class') $class = "class=\"$val\"";
        }
        $children = '';
        if (array_key_exists('rawText', (array)$options) || $this->invalidated) {
            $options = $options ?? [];
            if ($options['rawText'] || $this->invalidated) {
                $options['rawText'] = true;
                $outerHTML = [];
                foreach ($this->children as $child) {
                    $outerHTML[] = $child->toString($parsemodes, $options);
                }
                return implode('', $outerHTML);
            }
        }
        foreach ($this->children as $child) {
            $children = "$children{$child->toString($parsemodes, $options)}";
        }
        $else = "[$this->name$attrs_str]{$children}[/$this->name]";
        return (function (self $self) use ($parsemodes, $else, $children) {
            $attrs = $self->attrs;
            if (array_key_exists("$self->name", $parsemodes)) {
                return "{$parsemodes["$self->name"]("$self->name", $attrs, $children, $else)}";
            }
            switch (strtolower("$self->name")) {
                case'h1':
                case'h2':
                case'h3':
                case'h4':
                case'h5':
                case'h6':
                    return "<$self->name>$children</$self->name>";
                case'br':
                    return '<br/>';
                case'hidden':
                    return "<!--$children--->";
                case'url':
                    $href = '';
                    if (array_key_exists('url', $attrs)) {
                        $href = htmlspecialchars12(urlencode("{$attrs['url']}"));
                    }
                    return href($href, $children);
                case'href':
                case'a':
                    $href = '';
                    if (array_key_exists('href', $attrs)) {
                        $href = htmlspecialchars12(urlencode("{$attrs['href']}"));
                    }
                    return href($href, $children);
                case'color':
                    $style = '';
                    if (array_key_exists('color', $attrs)) {
                        $color = htmlspecialchars12("{$attrs['color']}");
                        if (preg_match('/^#?[a-zA-Z0-9]+/', "$color")) {
                            $style = "{$style}style=\"color:$color;\"";
                        } else {
                            return $else;
                        }
                    }
                    return "<span $style>$children</span>";
                case'p':
                    $style = '';
                    if (array_key_exists('color', $attrs)) {
                        $color = htmlspecialchars12("{$attrs['color']}");
                        if (preg_match('/^#?[a-zA-Z0-9]+/', "$color")) {
                            $style = "{$style}style=\"color:$color;\"";
                        }
                    }
                    //if ($style == '') {return "<p>$children</p>";}
                    return "<p style=\"$style\">$children</p>";
                case'ol':
                    $start = '';
                    if (array_key_exists('start', $attrs)) {
                        $start = htmlspecialchars12("{$attrs['start']}");
                        $start = "start=\"$start\"";
                    }
                    $reversed = '';
                    if (array_key_exists('reversed', $attrs)) {
                        $reversed = 'reversed=""';
                    }
                    $attributes = trim("$start $reversed");
                    return preg_replace('/^<ol +>/', '<ol>', "<ol $attributes>$children</ol>");
                case'ul':
                    return "<ul>$children</ul>";
                case'li':
                    return "<li>$children</li>";
                case'b':
                    return "<strong>$children</strong>";
                case'i':
                    return "<em>$children</em>";
                case's':
                    return "<span style='text-decoration: line-through;'>$children</span>";
                case'u':
                    return "<span style='text-decoration: underline;'>$children</span>";
                case'sup':
                    return "<sup>$children</sup>";
                case'sub':
                    return "<sub>$children</sub>";
                case'code':
                    return "<pre><code>$children</code></pre>";
                case'img':
                    $size = '';
                    $url = '';
                    $style = '';
                    $alt = htmlspecialchars12("{$attrs['alt']}");
                    if (array_key_exists('src', $attrs)) {
                        $src = htmlspecialchars12("{$attrs['src']}");
                    } else {
                        // bbml_encode
                        $src = (function (string $value): string {
                            $str = htmlspecialchars12("$value");
                            $str = str_replace('[', "\\[", "$str");
                            return str_replace(']', "\\]", "$str");
                        })("$children");
                    }
                    if (strlen($src) > 0) {
                        $url = "src=\"$src\"";
                    }
                    if (strlen("$alt") > 0) $alt = "alt=\"$alt\"";
                    return '<' . "img $url $alt$size style=\"$style\"/>";
                case'root':
                    return "$children";
                default:
            }
            return "$else";
        })($this);
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'attrs' => $this->attrs,
            'children' => $this->children,
            'invalidated' => $this->invalidated,
            'type' => '_AST2',
        ];
    }
}

class BBCode implements JsonSerializable
{
    private array $parseModes = [];
    private array $array = array();
    private ?_AST2 $parsed = null;

    public function __construct(string $string)
    {
        $this->array['raw'] = normalize_newlines($string);
    }

    public function parse(): self
    {
        if ($this->parsed === null) {
            $this->parsed = $this->tag_joiner(($this->array['lexed'] = $this->lexer())['$return']);
        }
        return $this;
    }

    private function tag_joiner(array $tags): _AST2
    {
        $index = 0 - 1;
        $openingtags = [];
        $root = $openingtag = new _AST2('root', ['class' => 'bbcode_car']);
        while (array_key_exists(++$index, $tags)) {
            $li = $tags[$index];
            if (in_array("{$li['close-ment-type']}", explode(',', 'OPENING,CLOSING'))) {
                if (in_array(strtolower("{$li['name']}"), explode(',', 'br,hr'))) {
                    $li['close-ment-type'] = 'SELF-CLOSING';
                }
            }
            switch ($li['close-ment-type']) {
                case'OPENING':
                    $cache = new _AST2($li['name'], $li['attrs'] /*,$li['tagtext']*/);
                    $openingtag->appendChild($cache);
                    /*$previously_opened = */
                    ($openingtag = $cache)->name();
                    $openingtags[] = $openingtag;
                    break;
                case'SELF-CLOSING':
                    $openingtag->appendChild(new _AST2($li['name'], $li['attrs'] /*,$li['tagtext']*/));
                    break;
                case'CLOSING':
                    $cache = array_pop($openingtags);
                    $openingtag = $openingtags[count($openingtags) - 1] ?? $root;
                    if ($cache->name() !== $li['name']) {
                        $cache->invalidate();
                    }
                    break;
                case'TEXT':
                    $openingtag->appendChild(new _AST2_TEXT("{$li['Text']}"));
                    break;
                default:
            }
        }
        if (count($openingtags) > 0) {
            header('opening:true');
            $indexedAttempts = 0;
            while (++$indexedAttempts < 800) {
                $cache = array_pop($openingtags);
                if ($cache === $root || $cache === NULL) break;
                $cache->invalidate();
            }
            if ($indexedAttempts >= 800) {
                throw new Error('precaution Infinite Recursion');
            }
        } else {
            header('opening:false');
        }
        return $root;
    }

    private function lexer(): array
    {
        $return = [];
        $index = 0 - 1;
        $opened = false;
        $backslashed = false;
        $collected_letters = [];
        $characters = str_split("{$this->array['raw']}");
        while (array_key_exists(++$index, $characters)) {
            $char = $characters[$index];
            if ($char == '\\' && $backslashed) {
                $collected_letters[] = "\\";
                $backslashed = false;
                continue;
            } elseif ($char == '\\') {
                $collected_letters[] = "\\";
                $backslashed = true;
                continue;
            }
            if ($char === '[' && !$backslashed) {
                if (count($collected_letters) > 0) {
                    $return[] = ['Text' => implode('', $collected_letters),
                        'type' => 'TEXT', 'when' => 'left-open', 'close-ment-type' => 'TEXT'];
                }
                $opened = true;
                $collected_letters = ['['];
            } elseif ($char == ']' && $opened && !$backslashed) {
                $text = implode('', $collected_letters) . ']';
                $tag = ['name' => '', 'attrs' => [], 'type' => '_AbstractSyntaxTree',
                    'tagtext' => $text, 'innerText' => '',
                ];
                if (str_starts_with($text, '[/')) {
                    $tag['close-ment-type'] = 'CLOSING';
                } elseif (str_ends_with($text, '/]')) {
                    $tag['close-ment-type'] = 'SELF-CLOSING';
                } else {
                    $tag['close-ment-type'] = 'OPENING';
                }
                $key = '';
                $val = '';
                $PHASE = 'NAME';
                $quoted = false;
                $val_reached = false;
                $backslashed2 = false;
                $preg_result = preg_replace('/^\\[\\/?/', '', $text);
                $preg_result = preg_replace('/\\/?]$/D', '', $preg_result);
                foreach (str_split($preg_result) as $explode) {
                    if ($explode == '\\' && $backslashed2) {
                        $backslashed2 = false;
                        continue;
                    } elseif ($explode == '\\') {
                        $backslashed2 = true;
                        continue;
                    }
                    switch ($PHASE) {
                        case'NAME':
                            if (preg_match('/[a-zA-Z\\-_0-9*]/', "$explode")) {
                                $tag['name'] = "{$tag['name']}$explode";
                            } else {
                                if ($tag['close-ment-type'] === 'CLOSING') {
                                    $PHASE = 'END';
                                    break;
                                }
                                if ($explode === '=') {
                                    $PHASE = 'attrVal';
                                    $key = "{$tag['name']}";
                                } else {
                                    $PHASE = 'attrKey';
                                }
                            }
                            break;
                        case'attrVal':
                            if ($explode === '"' && !$quoted) {
                                $val_reached = true;
                                $quoted = true;
                                break;
                            }
                            //if (preg_match('/[a-zA-Z0-9]/', $explode)) {$val_reached = true;}
                            if (preg_match('/[^"\\s]/', $explode)) {
                                $val_reached = true;
                            }
                            if ($quoted) {
                                 if ($explode == '"' && !$backslashed2) {
                                    $PHASE = 'tween';
                                    $tag['attrs']["$key"] = $val;
                                    $val_reached = false;
                                    $quoted = false;
                                    $key = '';
                                    $val = '';
                                    break;
                                }
                            } else {
                                if (preg_match('/\\s/', $explode) && $val_reached) {
                                    $PHASE = 'tween';
                                    $tag['attrs']["$key"] = $val;
                                    $quoted = false;
                                    $key = '';
                                    $val = '';
                                    break;
                                }
                                //if ($explode === ']' && !$backslashed2) {$PHASE = 'tween';$tag['attrs']["$key"] = $val;$val_reached = false;$quoted = false;$key = '';$val = '';break;}
                            }
                            if ($val_reached) {
                                $val = "$val$explode";
                            }
                            break;
                        case'attrKey-spacefound':
                        case'attrKey':
                            if ((preg_match('/[a-zA-Z0-9]/', $explode) && $PHASE === 'attrKey-spacefound')
                                || $explode === '/') {
                                $tag['attrs']["$key"] = "__$key";
                                $PHASE = 'attrKey';
                                $quoted = false;
                                $key = "$explode";
                                $val = '';
                                break;
                            }
                            if (preg_match('/\\s/', $explode)) {
                                $PHASE = 'attrKey-spacefound';
                                break;
                            }
                            if ($explode === '=') {
                                $PHASE = 'attrVal';
                                break;
                            }
                            if ($explode === ']') {
                                $tag['attrs']["$key"] = "__$key";
                                break;
                            }
                            $key = "$key$explode";
                            break;
                        case'tween':
                            if (preg_match('/[a-zA-Z0-9]/', $explode)) {
                                $key = "$key$explode";
                                $PHASE = 'attrKey';
                            }
                            break;
                        default:
                    }
                    $backslashed2 = false;
                }
                $return[] = $tag;
                //
                $collected_letters = [];
                $opened = false;
            } else {
                $collected_letters[] = $char;
            }
            $backslashed = false;
        }
        $return[] = ['Text' => implode('', $collected_letters),
            'type' => 'TEXT', 'when' => 'final bytes', 'close-ment-type' => 'TEXT'];
        return ['$return' => $return];
    }

    public function toHTML(): ?string
    {
        if (is_null($this->parsed)) return null;
        return $this->parsed->toString();
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray(): ?array
    {
        return ['parsed' => $this->parsed, 'innerArray' => $this->array];
    }
}

function objectToString(mixed $mixed): string
{
    return match ($mixed) {
        null => 'NULL',
        true => 'true',
        false => 'false',
        default => (is_array($mixed) ? implode(',', $mixed) : "$mixed"),
    };
}