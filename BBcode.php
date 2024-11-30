<?php /*
MIT License

Copyright (c) 2024 Qin2007

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/
$whatis = [
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

function h($bool, $exmen): string
{
    return "&lt;&quot;" . ($bool ? 'true' : 'false') . "&quot;::&quot;$exmen&quot;&gt;\n\n";
}

function _lexer(string $string, array $parseModes): array
{
    $string2 = '';
    $CDATA = false;
    $parseModes_count = count($parseModes);
    foreach (str_split(_normalize_newlines__($string)) as $char) {
        $string2_temp = "$string2$char";
        if (preg_match('/<!\\[CDATA\\[$/D', $string2_temp) && !$CDATA) {
            $CDATA = true;
            $string2 = preg_replace('/<!\\[CDATA\\[$/D', '', $string2_temp);
            continue;
        }
        if (!$CDATA && $parseModes_count > 0) {
            $just_matched = false;
            foreach ($parseModes as $parseMode => $closure) {
                if (preg_match('/^pre:([a-z_A-Z][a-z_A-Z0-9]*)$/D', "$parseMode", $matches)) {
                    $regex = "/<!\\[$matches[1]\\[$/D";
                    if (preg_match($regex, $string2_temp)) {
                        $string2 = preg_replace($regex, '', $string2_temp);
                        $just_matched = true;
                        $CDATA = $closure;
                        break;
                    }
                }
            }
            if ($just_matched) {
                continue;
            }
        }
        if (preg_match('/\\\\?]\\\\?]>$/D', $string2_temp) && $CDATA) {
            $CDATA = false;
            $string2 = preg_replace('/\\\\?]\\\\?]>$/D', '', $string2_temp);
            continue;
        }
        if ($CDATA === true) {
            $string2 = match ($char) {
                '[', ']' => "$string2\\$char",
                default => "$string2$char",
            };
        } elseif ($CDATA instanceof Closure) {
            $string2 = $CDATA($string2, $char);
        } else {
            $string2 = "$string2$char";
        }
    }
    $string1 = $string2;
    $cache = '';
    $return = [];
    $literal = true;
    $backslashed = false;
    $collected_letters = '';
    foreach (str_split($string2) as $char) {
        if ($char == '\\' && $backslashed) {
            $collected_letters = "$collected_letters\\";
            $backslashed = false;
            continue;
        }
        if ($char == '\\') {
            $backslashed = true;
            continue;
        }
        if ($literal) {
            if ($char === '[' && $backslashed === false) {
                $literal = false;
                if (strlen($collected_letters) > 0) {
                    $return[] = ['Text' => $collected_letters, 'type' => 'TEXT'];
                }
                $collected_letters = '';
                $cache = '[';
            } else {
                $collected_letters = "$collected_letters$char";
            }
        } else {
            $cache = "$cache$char";
            if ($char === ']' && $backslashed === false) {
                $literal = true;
                $return[] = ['Text' => $cache, 'type' => 'tag1'];
            }
        }
        $backslashed = false;
    }
    if (strlen($collected_letters) > 0)
        $return[] = ['Text' => $collected_letters, 'type' => 'TEXT'];
    return ['$return' => $return, '$string1' => $string1];
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
            $val_reached = false;
            $preg_result = preg_replace('/^\\[\\/?/', '', "{$t['Text']}");
            foreach (_explode2__('', "$preg_result") as $explode) {
                switch ($PHASE) {
                    case'NAME':
                        if (preg_match('/[a-zA-Z\\-_0-9*]/', "$explode")) {
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
                            $val_reached = true;
                            $quoted = true;
                            break;
                        }
                        if (preg_match('/[a-zA-Z0-9]/', $explode)) {
                            $val_reached = true;
                        }
                        if ($quoted) {
                            if ($explode == '"') {
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
                            if ($explode === ']') {
                                $PHASE = 'tween';
                                $tag['attrs']["$key"] = $val;
                                $val_reached = false;
                                $quoted = false;
                                $key = '';
                                $val = '';
                                break;
                            }
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
                if ("{$tag['name']}" === '*') {
                    $tag['name'] = 'li';
                    $PHASE = 'tween';
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
    $str = _html_encode__("$value");
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

    public function toString($_, $__, $options = null): string
    {
        /*if ($options['htmlencode']) {
            return _html_encode__("$this->string");
        }
        return "$this->string";*/
        return match ($options['htmlencode']) {
            EncodeMode::innerText => _return_that__("$this->string"),
            EncodeMode::BBCode => _bbml_encode__("$this->string"),
            default => _html_encode__("$this->string"),
        };
    }

    public function __toString(): string
    {
        return ("$this->string");
        //return $this->toString();
    }

    public function jsonSerialize(): array
    {
        return ['string' => "$this->string", 'type' => '_AST2_TEXT'];
    }
}

function _href__(string $href, string $children): string
{
    if (strlen($href) > 0) {
        return "<a href=\"$href\">$children</a>";
    } else {
        return "$children";
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

    public function setAttr(string $name, string $value): self
    {
        $this->attrs[$name] = $value;
        return $this;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function toString(EncodeMode $mode, array $parsemodes = [], array $options = null): string
    {
        //$class = '';
        $attrs_str = '';
        foreach ($this->attrs as $key => $val) {
            $key = "$mode->value"($key);
            $val = "$mode->value"($val);
            $attrs_str = "$attrs_str $key=\"$val\"";
            //if ($key === 'class') $class = "class=\"$val\"";
        }
        $children = '';
        foreach ($this->children as $child) {
            $children = "$children{$child->toString($mode, $parsemodes, $options)}";
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
                        return "<!--$children--->";
                    case'url':
                        $href = '';
                        if (array_key_exists('url', $attrs)) {
                            $href = _htmlspecialchars12__(urlencode("{$attrs['url']}"));
                        }
                        return _href__($href, $children);
                    case'href':
                    case'a':
                        $href = '';
                        if (array_key_exists('href', $attrs)) {
                            $href = _htmlspecialchars12__(urlencode("{$attrs['href']}"));
                        }
                        return _href__($href, $children);
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
                        //if ($style == '') {return "<p>$children</p>";}
                        return "<p style=\"$style\">$children</p>";
                    case'ol':
                        $start = '';
                        if (array_key_exists('start', $attrs)) {
                            $start = _htmlspecialchars12__("{$attrs['start']}");
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
                        $alt = _htmlspecialchars12__("{$attrs['alt']}");
                        if (array_key_exists('src', $attrs)) {
                            $src = _htmlspecialchars12__("{$attrs['src']}");
                        } else {
                            $src = _bbml_encode__("$children");
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

function opening_tag_list_toString(array $opening_tags): string
{

    $list = '';
    foreach ($opening_tags as $opening_tag) {
        $list = "$list,{$opening_tag->name()}";
    }
    return preg_replace('/^,/', '', $list);
}

function _AbstractSyntaxTree2(array $AbstractSyntaxTree): array
{
    $root = $openingtag = new _AST2('root', ['class' => 'bbcode_car']);
    $openingtags = [];
    $rtrn = array();
    $properly_closed = false;
    $previously_opened = null;
    //$just_closed_tag_name = null;
    foreach ($AbstractSyntaxTree as $li) {
        if (in_array("{$li['close-ment-type']}", explode(',', 'OPENING,CLOSING'))) {
            if (in_array(strtolower("{$li['name']}"), explode(',', 'br,hr'))) {
                $li['close-ment-type'] = 'SELF-CLOSING';
            }
        }
        switch ($li['close-ment-type']) {
            case'OPENING':
                // in memory of $lowercase_name_old and $lowercase_name_new
                //$parent = ($openingtags[count($openingtags) - 1] ?? $root)->name();
                $thisContext = strtolower("{$li['name']}");
                if (!$properly_closed) {
                    if ($previously_opened === 'p' && in_array(
                            $thisContext, explode(',',
                            'p,ol,ul,li,h1,h2,h3,h4,h5,h6'))) {
                        array_pop($openingtags);
                        $openingtag = $openingtags[count($openingtags) - 1] ?? $root;
                    }
                    if ($thisContext === 'li' && $previously_opened === 'li') {
                        array_pop($openingtags);
                        $openingtag = $openingtags[count($openingtags) - 1] ?? $root;
                    } else {
                        $list = opening_tag_list_toString($openingtags);
                        if (in_array($thisContext, explode(',', 'li,ol,ul'))) {
                            if (($list2 = strrpos($list, 'ol')) !== false) {
                                $list2 = substr($list, $list2);
                            } elseif (($list2 = strrpos($list, 'ul')) !== false) {
                                $list2 = substr($list, $list2);
                            } else {
                                $list2 = $list;
                            }
                            $explodes_list = explode(',', $list2);
                            if (in_array('li', $explodes_list)) {
                                foreach (array_reverse($openingtags) as $option) {
                                    array_pop($openingtags);
                                    $openingtag = $openingtags[count($openingtags) - 1] ?? $root;
                                    if ($option->name() == 'li') {
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
                $properly_closed = false;
                $cache = new _AST2($li['name'], $li['attrs']);
                $openingtag->appendChild($cache);
                $openingtag = $cache;
                $previously_opened = $cache->name();
                $openingtags[] = $openingtag;
                break;
            case'CLOSING':
                //array_pop($openingtags);
                //$openingtag = $openingtags[count($openingtags) - 1] ?? $root;

                //$just_closed_tag = array_pop($openingtags);
                //$next_opening_tag = $openingtags[count($openingtags) - 1] ?? $root;
                //if (!is_null($just_closed_tag)) $just_closed_tag_name = $just_closed_tag->name();
                //$openingtag = $next_opening_tag;

                $index = 0;
                foreach (array_reverse($openingtags) as $item) {
                    $index++;
                    if ($item->name() === $openingtag->name()) {
                        break;
                    }
                }
                if (count($openingtags) >= $index && $index > 0) {
                    for ($i = 0; $i < $index; $i++) {
                        array_pop($openingtags);
                    }
                } // else {array_pop($openingtags);}
                $openingtag = $openingtags[count($openingtags) - 1] ?? $root;
                $properly_closed = true;
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
    private array $options = array('htmlencode' => EncodeMode::HTML);

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
            if ('settings' === $parseName) {
                if (is_array($parseMode)) {
                    if (array_key_exists('htmlencode', $parseMode)) {
                        $this->options['htmlencode'] = $parseMode['htmlencode'];
                    }
                }
                continue;
            }
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
        $this->parseModes = [];
        return $this->addparseModes($parseModes);
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
        $this->array['lexer'] = $lexer = _lexer("{$this->array['raw']}", $this->parseModes);
        $this->array['AST1'] = $AbstractSyntaxTree1 = _AbstractSyntaxTree($lexer['$return']);
        $this->array['AST2'] = _AbstractSyntaxTree2($AbstractSyntaxTree1['$return']);
        $this->parsed = $this->array['AST3'] = $this->array['AST2']['$return'];
        return $this;
    }

    /**
     * @return string|null output the html
     */
    public function toHTML(): ?string
    {
        if (is_null($this->parsed)) return null;
        $parsed = $this->parsed->toString(EncodeMode::HTML, $this->parseModes, $this->options);
        return "<div class=bbcode_car role=none>$parsed</div>";
    }

    public function toJSON_HTML(int $indent = 2): ?string
    {
        if (is_null($this->parsed)) return null;
        $bbraws = _htmlspecialchars12__(_json_fromArray__($this->toArray(), $indent));
        return "{$this->toHTML()}<pre class=bbcode_car style=font-family:monospace><code>$bbraws</code></pre>";
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

    public function toArray(): ?array
    {
        if (is_null($this->parsed)) return null;
        $parsed = json_decode(json_encode($this->parsed->jsonSerialize()), true);
        if ($this->debug_mode) {
            return ['parsed' => $parsed,
                'innerArray' => $this->array];
        } else {
            return $parsed;
        }
    }
}
