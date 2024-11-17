<?php $whatis = [
    'name' => '\\[the tag\'s name]',
    'innerText' => '\\[the text inside the tag', 'attrs]' => [
        '\\[the attribute key]' => '\\[its\'s value]'],
    'Text' => '\\[the text of the tag, only this tag]',
    '\\[outerText]' => '\\[the full tag]',
    "close-ment-type" => '\\[how closing works]',
    'valid' => true//is this tag valid>
];
function _htmlspecialchars12__(string $value): string
{
    $html = str_replace('"', '&quot;',
        str_replace('>', '&gt;',
            str_replace('<', '&lt;',
                str_replace('\'', '&#39;',
                    str_replace('&', '&amp;',
                        "$value")))));
    return ($html);
}

function _normalize_newlines__(string $string): string
{
    return str_replace("\r", "\n", str_replace("\r\n", "\n", "$string"));
}

function _json_fromArray__(mixed $json, bool|int $JSON_PRETTY_PRINT = true): false|string
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

enum ExplodeType
{
    case PREG_SPLIT;
    case EXPLODE;
    case INFER;
    case SPLIT;
}

function _explode2__(string $seperator, string $string, ExplodeType $ExplodeType = ExplodeType::INFER): array
{
    $is_infer = $ExplodeType === ExplodeType::INFER;
    if (($seperator === '' && $is_infer) || $ExplodeType === ExplodeType::SPLIT) {
        return str_split("$string");
    }
    if ((preg_match('/^\\/.+\\/[mixUuAJD]*$/D', $seperator)
            && $is_infer) || $ExplodeType === ExplodeType::PREG_SPLIT) {
        $preg_result = preg_split($seperator, $string);
        if (($preg_result && $is_infer) || $ExplodeType === ExplodeType::PREG_SPLIT) {
            return $preg_result;
        }
    }
    return explode($seperator, $string);
}

function _implode2__(string $seperator, array $array): string
{
    return implode($seperator, $array);
}

function _lexer(string $string): array
{
    $cache = '';
    $return = [];
    $literal = true;
    $backslashed = false;
    $supercache = '';
    foreach (str_split(_normalize_newlines__($string)) as $char) {
        if ($char == '\\') {
            $backslashed = true;
            continue;
        }
        if ($literal) {
            if ($char == '[' && $backslashed === false) {
                $literal = false;
                if (strlen($supercache) > 0) {
                    $return[] = ['Text' => $supercache, 'type' => 'TEXT'];
                }
                $supercache = '';
                $cache = '[';
            } else {
                $supercache = "$supercache$char";
            }
        } else {
            $cache = "$cache$char";
            if ($char === ']') {
                $literal = true;
                $return[] = ['Text' => $cache, 'type' => 'tag1'];
            }
        }
        $backslashed = false;
    }
    if (strlen($supercache) > 0)
        $return[] = ['Text' => $supercache, 'type' => 'TEXT'];
    return ['$return' => $return];
}

function _AbstractSyntaxTree($semitokens): array
{
    $return = [];
    foreach ($semitokens as $t) {
        if ($t['type'] === 'tag1') {
            $tag = ['name' => '', 'attrs' => [], 'type' => '_AbstractSyntaxTree',
                'tagtext' => "{$t['Text']}", 'innerText' => ''
            ];
            if (str_starts_with($t['Text'], '[/')) {
                $tag['close-ment-type'] = 'CLOSING';
            } elseif (str_ends_with($t['Text'], '/]')) {
                $tag['close-ment-type'] = 'SELF-CLOSING';
            } else {
                $tag['close-ment-type'] = 'OPENING';
            }
            $key = '';
            $val = '';
            $PHASE = 'NAME';
            $quoted = false;
            $preg_result = preg_replace('/^\\[\\/?/', '', "{$t['Text']}");
            foreach (_explode2__('', "$preg_result") as $explode) {
                switch ($PHASE) {
                    case'NAME':
                        if (preg_match('/[a-zA-Z\\-_0-9]/', "$explode")) {
                            $tag['name'] = "{$tag['name']}$explode";
                        } else {
                            if ($tag['close-ment-type'] == 'CLOSING') {
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
                            $quoted = true;
                            break;
                        }
                        if ($quoted) {
                            if ($explode == '"') {
                                $PHASE = 'attrKey';
                                $tag['attrs']["$key"] = $val;
                                $quoted = false;
                                $key = '';
                                $val = '';
                                break;
                            }
                        } else {
                            if (preg_match('/\\s/', $explode)) {
                                $PHASE = 'attrKey';
                                $tag['attrs']["$key"] = $val;
                                $quoted = false;
                                $key = '';
                                $val = '';
                                break;
                            }
                            if ($explode === ']') {
                                $PHASE = 'attrKey';
                                $tag['attrs']["$key"] = $val;
                                $quoted = false;
                                $key = '';
                                $val = '';
                                break;
                            }
                        }
                        $val = "$val$explode";
                        break;
                    case'attrKey':
                        if (preg_match('/\\s/', $explode)) {
                            break;
                        }
                        if ($explode === '=') {
                            $PHASE = 'attrVal';
                            break;
                        }
                        if ($explode === ']') {
                            //$tag['attrs']["$key"] = "\$undefined";
                            break;
                        }
                        $key = "$key$explode";
                    default:
                }
            }
            $return[] = $tag;
        } else {
            $t['close-ment-type'] = 'TEXT';
            $return[] = $t;
        }
    }
    return ['$return' => $return];
}

function _array_insert(array &$array, mixed $new): void
{
    array_splice($array, 0, 0, $new);
}

function _bbml_encode__(string $value): string
{
    $str = _htmlspecialchars12__("$value");
    $str = str_replace('[', "\\[", "$str");
    return str_replace(']', "\\]", "$str");
}

function _html_encode__(string $value): string
{
    return _htmlspecialchars12__("$value");
}

function _return_that__(mixed $midex): mixed
{
    return $midex;
}

enum EncodeMode: string
{
    case HTML = '_html_encode__';
    case BBCode = '_bbml_encode__';
    case innerText = '_return_that__';
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
        return $this->string;
        //return _bbml_encode__("$this->string");
    }

    public function __toString(): string
    {
        return _bbml_encode__("$this->string");
        //return $this->toString();
    }

    public function jsonSerialize(): array
    {
        return ['string' => _bbml_encode__("$this->string"), 'type' => '_AST2_TEXT'];
    }

}

class _AST2 implements JsonSerializable
{
    private string $name;
    private array $attrs;
    private array $children = [];

    public function __construct(string $tag_name, array $attrs = [])
    {
        $this->name = $tag_name;
        $this->attrs = $attrs;
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
        return $this->toString(EncodeMode::BBCode);
    }

    public function toString(EncodeMode $mode, array $parsemodes = []): string
    {
        $attrs_str = '';
        foreach ($this->attrs as $key => $val) {
            $key = "$mode->value"($key);
            $val = "$mode->value"($val);
            $attrs_str = "$attrs_str $key=\"$val\"";
        }
        $children = '';
        foreach ($this->children as $child) {
            $children = "$children{$child->toString($mode, $parsemodes)}";
        }
        $else = "[$this->name$attrs_str]{$children}[/$this->name]";
        return match ($mode) {
            EncodeMode::HTML => (function (self $self) use ($parsemodes, $else, $children) {
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
                        return '';
                    case'url': //not breaking is important here
                        if (array_key_exists('url', $attrs)) {
                            $href = _htmlspecialchars12__(urlencode("{$attrs['url']}"));
                            return "<a href=\"$href\">$children</a>";
                        }
                    case'href':
                    case'a':
                        if (array_key_exists('href', $attrs)) {
                            $href = _htmlspecialchars12__(urlencode("{$attrs['href']}"));
                            return "<a href=\"$href\">$children</a>";
                        } else {
                            break;
                        }
                    case'color':
                        $style = '';
                        if (array_key_exists('color', $attrs)) {
                            $color = _htmlspecialchars12__("{$attrs['color']}");
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
                            $color = _htmlspecialchars12__("{$attrs['color']}");
                            if (preg_match('/^#?[a-zA-Z0-9]+/', "$color")) {
                                $style = "{$style}style=\"color:$color;\"";
                            }
                        }
                        if ($style == '') {
                            return "<p>$children</p>";
                        }
                        return "<p style=\"$style\">$children</p>";
                    case'ol':
                        $start = '1';
                        if (array_key_exists('start', $attrs)) {
                            $start = _htmlspecialchars12__("{$attrs['start']}");
                        }
                        $reversed = '';
                        if (array_key_exists('reversed', $attrs) && "{$attrs['reversed']}" === 'true') {
                            $reversed = 'reversed=""';
                        }
                        return "<ol start=\"$start\" $reversed>$children</ol>";
                    case'ul':
                        return "<ul>$children</ul>";
                    case'li':
                        return "<li>$children</li>";
                    case'b':
                        return "<strong>$children</strong>";
                    case'i':
                        return "<em>$children</em>";
                    case's':
                        return "<span style=\"text-decoration: line-through;\">$children</span>";
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
                        $alt = _htmlspecialchars12__("{$attrs['alt']}");
                        if (array_key_exists('src', $attrs)) {
                            $src = _htmlspecialchars12__("{$attrs['src']}");
                        } else {
                            $src = _bbml_encode__("$children");//$innerText;
                        }
                        if (strlen($src) > 0) {
                            $url = "src=\"$src\"";
                        }/*
                        if (array_key_exists('width', $attrs)) {
                            $width = _htmlspecialchars12__("{$attrs['width']}");
                            $size = "$size width=\"$width\"";
                        }
                        if (array_key_exists('width%', $attrs)) {
                            $width = _htmlspecialchars12__("{$attrs['width%']}");
                            if (preg_match('/^\\d+$/D', $width)) {
                                $width = "$width%";
                            }
                            $style = "width:$width;";
                        }
                        if (array_key_exists('height%', $attrs)) {
                            $height = _htmlspecialchars12__("{$attrs['height%']}");
                            if (preg_match('/^\\d+$/D', $height)) {
                                $height = "$height%";
                            }
                            $style = "height:$height;";
                        }
                        if (array_key_exists('height', $attrs)) {
                            $height = _htmlspecialchars12__("{$attrs['height']}");
                            $size = "$size height=\"$height\"";
                        }*/
                        if (strlen("$alt") > 0) $alt = "alt=\"$alt\"";
                        return '<' . "img $url $alt$size style=\"$style\"/>";
                    case'root':
                        return "$children";
                    default:
                }
                return "$else";
            })($this),
            default => "$else",
        };
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'attrs' => $this->attrs,
            'children' => $this->children,
            'type' => '_AST2'
        ];
    }
}

function _AbstractSyntaxTree2(array $AbstractSyntaxTree): array
{
    $root = $openingtag = new _AST2('root');
    $openingtags = array();
    $rtrn = [];
    foreach ($AbstractSyntaxTree as $li) {
        switch ($li['close-ment-type']) {
            case'OPENING':
                $lowercase_name_old = ($openingtags[count($openingtags) - 1] ?? $root)->name();
                $lowercase_name_new = strtolower("{$li['name']}");
                $break = false;
                switch ($lowercase_name_old) {
                    case'p':
                        $break = in_array(
                            $lowercase_name_new,
                            _explode2__(
                                '/[, ]/',
                                'p,ol,ul,li,h1,h2,h3,h4,h5,h6'
                            ));
                        break;
                    case'li':
                        $break = in_array(
                            $lowercase_name_new,
                            _explode2__(
                                '/[, ]/',
                                'ol,ul,li'
                            ));
                    default:
                }
                if ($break) {
                    array_pop($openingtags);
                    $openingtag = $openingtags[count($openingtags) - 1] ?? $root;
                }
                $cache = new _AST2($li['name'], $li['attrs']);
                $openingtag->appendChild($cache);
                $openingtag = $cache;
                $openingtags[] = $openingtag;
                break;
            case'CLOSING':
                //$current_opening_tag = $openingtag;
                $just_closed_tag = array_pop($openingtags);
                $next_opening_tag = $openingtags[count($openingtags) - 1] ?? $root;
                if ($just_closed_tag->name() === 'li' && (
                        $next_opening_tag->name() === 'ol' ||
                        $next_opening_tag->name() === 'ul')) {
                    array_pop($openingtags);
                    $next_opening_tag = $openingtags[count($openingtags) - 1] ?? $root;
                }
                $openingtag = $next_opening_tag;
                break;
            case'SELF-CLOSING':
                $openingtag->appendChild(new _AST2($li['name'], $li['attrs']));
                break;
            case'TEXT':
                $string = "{$li['Text']}";
                $openingtag->appendChild(new _AST2_TEXT($string));
                break;
            default:
        }
    }
    return ['$return' => $root, '$rtrn' => $rtrn];
}

class BBCode implements JsonSerializable
{
    private array $parseModes = [];
    private array $array = array();
    private ?_AST2 $parsed = null;
    private bool $debug_mode = false;

    /**
     * parses BBCode to HTML
     *
     * @param string $string the BBCode String
     */
    public function __construct(string $string)
    {
        $this->array['raw'] = $string;
    }

    /**
     * @param array $parseModes an associative array where the keys are the tag name the user needs to use
     * the value needs to be a function with
     * function (string $name, array $attributes, string $children, string $else):string
     * as signature and values are passed
     *
     * string $name: the name of the tag
     *
     * array $attributes: the attributes passed to the tag as an associative array
     *
     * string $children: a string meant to put without modifications, used for tag nesting and in between content
     *
     * string $else: the string used to invike this bbcode command, you can return this to putout it as is
     *
     * the function should output raw html
     * @return $this for method chaining
     */
    public function addparseModes(array $parseModes): self
    {
        foreach ($parseModes as $parseName => $parseMode) {
            $this->parseModes[$parseName] = $parseMode;
        }
        return $this;
    }

    /**
     * @return array
     * @see addparseModes
     */
    public function getparseModes(): array
    {
        return $this->parseModes;
    }


    /**
     * overwrites the array
     *
     * @see addparseModes
     */
    public function setparseModes(array $parseModes): self
    {
        $this->parseModes = $parseModes;
        return $this;
    }

    public function setdebugMode(bool $enabled): self
    {
        $this->debug_mode = $enabled;
        return $this;
    }

    /**
     * parsing doesnt actually happen until here
     * @return $this
     */
    public function parse(): self
    {
        $this->array['lexer'] = $lexer = _lexer("{$this->array['raw']}");
        $this->array['AST1'] = $AbstractSyntaxTree1 = _AbstractSyntaxTree($lexer['$return']);
        $this->array['AST2'] = _AbstractSyntaxTree2($AbstractSyntaxTree1['$return']);
        $this->parsed = $this->array['AST3'] = $this->array['AST2']['$return'];
        return $this;
    }

    /**
     * @return string|null output the raw html
     */
    public function toHTML(): ?string
    {

        return is_null($this->parsed) ? null : $this->parsed->toString(
            EncodeMode::HTML, $this->parseModes);
    }

    public function toJSON_HTML(): ?string
    {
        if (is_null($this->parsed)) return null;
        $bbraws = _htmlspecialchars12__(_json_fromArray__($this->toArray(), 2));
        return "{$this->toHTML()}<pre style='font-family:monospace'><code>$bbraws</code></pre>";
    }

    public function getRaw(): ?string
    {
        return is_null($this->parsed) ? null : "$this->parsed";
    }

    public function __toString(): string
    {
        return $this->getRaw() ?? 'NULL';
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        $parsed = is_null($this->parsed) ?
            null : $this->parsed->jsonSerialize();
        if ($this->debug_mode) {
            return ['parsed' => $parsed,
                'innerArray' => $this->array];
        } else {
            return $parsed;
        }
    }
}