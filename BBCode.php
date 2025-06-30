<?php namespace BBCode;

use DateTimeImmutable;
use JsonSerializable;

/**
 * Escapes special HTML characters in a string.
 * Converts &, ', <, >, and " to their respective HTML entities.
 */
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

/**
 * Normalizes all types of newlines (\r\n, \r) to \n in a string.
 */
function normalize_newlines(string $string): string
{
    return str_replace("\r", "\n", str_replace("\r\n", "\n", "$string"));
}

/**
 * Converts an array to a JSON string, with optional pretty print formatting.
 * @param mixed $json - The input array or object to encode.
 * @param bool|int $JSON_PRETTY_PRINT - True, false, or an int for pretty print indentation.
 * @return false|string - JSON encoded string or false on failure.
 */
function json_fromArray(mixed $json, bool|int $JSON_PRETTY_PRINT = true): false|string
{
    $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
    if (is_int($JSON_PRETTY_PRINT) && $JSON_PRETTY_PRINT >= 0) {
        $options |= JSON_PRETTY_PRINT;
        $json = json_encode($json, $options);
        // Adjust indentation based on $JSON_PRETTY_PRINT value
        return preg_replace_callback('/^ +/m', (function (array $matches)
        use ($JSON_PRETTY_PRINT): string {
            // Replace indentation by multiplying its length by $JSON_PRETTY_PRINT
            return str_repeat(' ', (strlen($matches[0]) / 4) * $JSON_PRETTY_PRINT);
        }), $json);
    } elseif (is_bool($JSON_PRETTY_PRINT) && $JSON_PRETTY_PRINT) {
        $options |= JSON_PRETTY_PRINT;
    }
    return json_encode($json, $options);
}

/**
 * Represents a text node in the BBCode AST (Abstract Syntax Tree).
 */
class _AST2_TEXT implements JsonSerializable
{
    private string $string;

    public function __construct(string $string)
    {
        // Stores the text content for this AST node
        $this->string = $string;
    }

    /**
     * Returns the text content.
     */
    public function toString(): string
    {
        // Returns the string stored in this node
        return ("$this->string");
    }

    /**
     * Returns the text content (magic method for string casting).
     */
    public function __toString(): string
    {
        // Allows the object to be used as a string
        return ("$this->string");
    }

    /**
     * Serializes the object as an array for JSON.
     */
    public function jsonSerialize(): array
    {
        // Returns an array describing this node for JSON encoding
        return ['string' => "$this->string", 'type' => '_AST2_TEXT'];
    }

    /**
     * Marks the node as invalid (in this implementation, no-op).
     */
    public function invalidate(): self
    {
        // No invalidation logic for text node; returns itself
        return $this;
    }
}

/**
 * Helper for generating an anchor (link) HTML element or just outputting the children.
 */
function href(string $href, string $children): string
{
    // If $href is not empty, return it as an anchor/link, otherwise just the children
    if (strlen($href) > 0) {
        return "<a href=\"$href\">$children</a>";
    } else {
        return "$children";
    }
}

/**
 * Represents a BBCode AST node (tag and its children).
 */
class _AST2 implements JsonSerializable
{
    private string $name;      // Tag name, e.g. "b", "i", "url", etc.
    private array $attrs;      // Tag attributes as key=>value
    private array $children = []; // Nested children nodes (_AST2 or _AST2_TEXT)
    private bool $invalidated = false; // If true, node is considered invalid

    /**
     * Constructor for an AST node.
     */
    public function __construct(string $tag_name, array $attrs = [])
    {
        // Set the tag name and attributes for this node
        $this->name = $tag_name;
        $this->attrs = $attrs;
    }

    /**
     * Returns the tag name.
     */
    public function name(): string
    {
        // Returns the name of the tag
        return $this->name;
    }

    /**
     * Appends a child node to this node.
     */
    public function appendChild(self|_AST2_TEXT $child): self
    {
        // Adds a child node to the children array
        $this->children[] = $child;
        return $this;
    }

    /**
     * Returns the string representation (HTML or BBCode) of this node and its children.
     */
    public function __toString(): string
    {
        // Delegates to the toString method for string conversion
        return $this->toString();
    }

    /**
     * Sets an attribute on this tag.
     */
    public function setAttr(string $name, string $value): self
    {
        // Sets or overrides an attribute for this tag
        $this->attrs[$name] = $value;
        return $this;
    }

    /**
     * Gets all children nodes.
     */
    public function getChildren(): array
    {
        // Returns the array of children nodes
        return $this->children;
    }

    /**
     * Marks this node as invalid.
     */
    public function invalidate(): self
    {
        // Sets the invalidated flag to true
        $this->invalidated = true;
        return $this;
    }

    /**
     * Converts this AST node and its children to a string (HTML or fallback BBCode).
     * @param array $parsemodes - Custom rendering callbacks for tag names.
     * @param array|null $options - Options, e.g. rawText.
     */
    public function toString(array $parsemodes = [], ?array $options = null): string
    {
        // Build HTML-safe attribute string for this tag
        $attrs_str = '';
        foreach ($this->attrs as $key => $val) {
            $key = htmlspecialchars12($key);
            $val = htmlspecialchars12($val);
            $attrs_str = "$attrs_str $key=\"$val\"";
        }
        $children = '';
        $options = $options ?? [];
        // If rawText option is set, or node is invalid, just render children as text
        if (array_key_exists('rawText', $options) || $this->invalidated) {
            if ($options['rawText'] || $this->invalidated) {
                $options['rawText'] = true;
                $outerHTML = [];
                foreach ($this->children as $child) {
                    // Recursively convert children to string with rawText option
                    $outerHTML[] = $child->toString($parsemodes, $options);
                }
                // Concatenate all child outputs
                return implode('', $outerHTML);
            }
        }
        // Build children string recursively
        foreach ($this->children as $child) {
            $children = "$children{$child->toString($parsemodes, $options)}";
        }
        // Default fallback BBCode representation
        $else = "[$this->name$attrs_str]{$children}[/$this->name]";
        // Tag-specific rendering logic or use fallback
        return (function (self $self) use ($parsemodes, $else, $children) {
            $attrs = $self->attrs;
            // Custom parseModes for user-defined rendering
            if (array_key_exists("$self->name", $parsemodes)) {
                // Call user-provided rendering function for this tag
                return "{$parsemodes["$self->name"]("$self->name", $attrs, $children, $else)}";
            }
            // Default HTML rendering for common tags
            switch (strtolower("$self->name")) {
                case'h1':
                case'h2':
                case'h3':
                case'h4':
                case'h5':
                case'h6':
                    // HTML heading tags
                    return "<$self->name>$children</$self->name>";
                case'time':
                    $datetime = htmlspecialchars12("{$attrs['time']}");
                    if (!preg_match('/^\\d{4}(?:-\\d{2}(?:-\\d{2}(?:T\\d{2}(?::\\d{2}(?::\\d{2})?)?)?)?)?Z$/D', $datetime)) {
                        return "$else";
                    }
                    $datetimeImmutable = (new DateTimeImmutable($datetime))->format('D, Y-M-d H:i:s \\U\\T\\CO (e)');
                    return "<time datetime=\"$datetime\" class=bbcodeTime>$datetimeImmutable</time>";
                case'br':
                    // Line break
                    return '<br/>';
                case'hidden':
                    // HTML comment for hidden content
                    return "<!--$children--->";
                case'url':
                    // URL BBCode, convert to anchor
                    $href = '';
                    if (array_key_exists('url', $attrs)) {
                        $href = htmlspecialchars12(urlencode("{$attrs['url']}"));
                    }
                    return href($href, $children);
                case'href':
                case'a':
                    // Anchor tag
                    $href = '';
                    if (array_key_exists('href', $attrs)) {
                        $href = htmlspecialchars12(urlencode("{$attrs['href']}"));
                    }
                    return href($href, $children);
                case'color':
                    // Span with color style
                    $style = '';
                    if (array_key_exists('color', $attrs)) {
                        $color = htmlspecialchars12("{$attrs['color']}");
                        if (preg_match('/^#?[a-zA-Z0-9]+/', "$color")) {
                            $style = "{$style}style=\"color:$color;\"";
                        } else {
                            // If color is invalid, fallback to BBCode
                            return $else;
                        }
                    }
                    return "<span $style>$children</span>";
                case'p':
                    // Paragraph, optionally styled with color
                    $style = '';
                    if (array_key_exists('color', $attrs)) {
                        $color = htmlspecialchars12("{$attrs['color']}");
                        if (preg_match('/^#?[a-zA-Z0-9]+/', "$color")) {
                            $style = "{$style}style=\"color:$color;\"";
                        }
                    }
                    return "<p style=\"$style\">$children</p>";
                case'ol':
                    // Ordered list, with optional start and reversed attributes
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
                    // Remove extra spaces in <ol>
                    return preg_replace('/^<ol +>/', '<ol>', "<ol $attributes>$children</ol>");
                case'ul':
                    // Unordered list
                    return "<ul>$children</ul>";
                case'li':
                    // List item
                    return "<li>$children</li>";
                case'b':
                    // Bold text
                    return "<strong>$children</strong>";
                case'i':
                    // Italic text
                    return "<em>$children</em>";
                case's':
                    // Strikethrough
                    return "<span style='text-decoration: line-through;'>$children</span>";
                case'u':
                    // Underline
                    return "<span style='text-decoration: underline;'>$children</span>";
                case'sup':
                    // Superscript
                    return "<sup>$children</sup>";
                case'sub':
                    // Subscript
                    return "<sub>$children</sub>";
                case'code':
                    // Code block
                    return "<pre><code>$children</code></pre>";
                case'img':
                    // Image tag, with src and alt attributes
                    $size = '';
                    $url = '';
                    $style = '';
                    $alt = htmlspecialchars12("{$attrs['alt']}");
                    if (array_key_exists('src', $attrs)) {
                        $src = htmlspecialchars12("{$attrs['src']}");
                    } else {
                        // Encode image source from children if no src attribute
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
                    // Root node: just output children
                    return "$children";
                default:
            }
            // Fallback: return as BBCode
            return "$else";
        })($this);
    }

    /**
     * Serializes this node as an array for JSON encoding.
     */
    public function jsonSerialize(): array
    {
        // Returns an array describing this node, including children, for JSON encoding
        return [
            'name' => $this->name,
            'attrs' => $this->attrs,
            'invalidated' => $this->invalidated,
            'type' => '_AST2', 'children' => $this->children,
        ];
    }
}

/**
 * BBCode parser class - parses BBCode strings into AST and generates HTML or other output.
 */
class BBCode implements JsonSerializable
{
    private array $parseModes = []; // User-defined tag rendering callbacks
    private array $array = array(); // Holds raw and lexed content
    private ?_AST2 $parsed = null;  // Root AST node (parsed structure)

    /**
     * Constructor. Loads and normalizes newlines in the string.
     */
    public function __construct(string $string)
    {
        // Store the input string with normalized newlines in the internal array
        $this->array['raw'] = normalize_newlines($string);
    }

    /**
     * Parses the BBCode string into an AST. Returns $this for chaining.
     */
    public function parse(): self
    {
        // If not already parsed, lex the string and join tags into an AST
        if ($this->parsed === null) {
            $this->parsed = $this->tag_joiner(($this->array['lexed'] = $this->lexer())['$return']);
        }
        return $this;
    }

    /**
     * Converts a flat tag/token array (from lexer) into a tree of AST nodes.
     * Handles tag nesting and closure logic.
     */
    private function tag_joiner(array $tags): _AST2
    {
        $index = 0 - 1;
        $openingtags = [];
        // Create a root node for the tree
        $root = $openingtag = new _AST2('root', ['class' => 'bbcode_car']);
        // Iterate over each tag
        while (array_key_exists(++$index, $tags)) {
            $li = $tags[$index];
            // Determine if the tag should be self-closing
            if (in_array("{$li['close-ment-type']}", explode(',', 'OPENING,CLOSING'))) {
                if (in_array(strtolower("{$li['name']}"), explode(',', 'br,hr'))) {
                    $li['close-ment-type'] = 'SELF-CLOSING';
                }
            }
            // Handle tag type: opening, closing, self-closing, or text
            switch ($li['close-ment-type']) {
                case'OPENING':
                    // Collapse consecutive <p> tags
                    if ($li['name'] === 'p' && $openingtag?->name() === 'p') {
                        array_pop($openingtags);
                        $openingtag = $openingtags[count($openingtags) - 1] ?? $root;
                    }
                    // Create a new node and make it the current opening tag
                    $cache = new _AST2($li['name'], $li['attrs']);
                    $openingtag->appendChild($cache);
                    ($openingtag = $cache)->name();
                    $openingtags[] = $openingtag;
                    break;
                case'SELF-CLOSING':
                    // Add a self-closing tag as a child
                    $openingtag->appendChild(new _AST2($li['name'], $li['attrs']));
                    break;
                case'CLOSING':
                    // Pop the last opening tag off the stack
                    array_pop($openingtags);
                    $openingtag = $openingtags[count($openingtags) - 1] ?? $root;
                    break;
                case'TEXT':
                    // Add text content as a child node
                    $openingtag->appendChild(new _AST2_TEXT("{$li['Text']}"));
                    break;
                default:
            }
        }
        return $root;
    }

    /**
     * Tokenizes the input BBCode string into an array of tag and text tokens.
     * Handles attribute parsing, escaping, and tag structure.
     */
    private function lexer(): array
    {
        $return = [];
        $index = 0 - 1;
        $opened = false;
        $backslashed = false;
        $collected_letters = [];
        $characters = str_split("{$this->array['raw']}");
        // Iterate over each character in the input string
        while (array_key_exists(++$index, $characters)) {
            $char = $characters[$index];
            // Handle backslash escaping
            if ($char == '\\' && $backslashed) {
                $collected_letters[] = "\\";
                $backslashed = false;
                continue;
            } elseif ($char == '\\') {
                $collected_letters[] = "\\";
                $backslashed = true;
                continue;
            }
            // Start of a tag
            if ($char === '[' && !$backslashed) {
                // If there's accumulated text, flush it as a text token
                if (count($collected_letters) > 0) {
                    $return[] = ['Text' => implode('', $collected_letters),
                        'type' => 'TEXT', 'when' => 'left-open', 'close-ment-type' => 'TEXT'];
                }
                $opened = true;
                $collected_letters = ['['];
                // End of a tag
            } elseif ($char == ']' && $opened && !$backslashed) {
                $text = implode('', $collected_letters) . ']';
                // Initialize a tag token
                $tag = ['name' => '', 'attrs' => [], 'type' => '_AbstractSyntaxTree',
                    'tagtext' => $text, 'innerText' => '',
                ];
                // Determine tag type (open, close, self-close)
                if (str_starts_with($text, '[/')) {
                    $tag['close-ment-type'] = 'CLOSING';
                } elseif (str_ends_with($text, '/]')) {
                    $tag['close-ment-type'] = 'SELF-CLOSING';
                } else {
                    $tag['close-ment-type'] = 'OPENING';
                }
                // Attribute parsing state machine
                $key = '';
                $val = '';
                $PHASE = 'NAME';
                $quoted = false;
                $val_reached = false;
                $backslashed2 = false;
                $preg_result = preg_replace('/^\\[\\/?/', '', $text);
                $preg_result = preg_replace('/\\/?]$/D', '', $preg_result);
                // Parse each character in the tag for attributes
                foreach (str_split($preg_result) as $explode) {
                    // Handle backslash escaping for attributes
                    if ($explode == '\\' && $backslashed2) {
                        $backslashed2 = false;
                        continue;
                    } elseif ($explode == '\\') {
                        $backslashed2 = true;
                        continue;
                    }
                    // State machine for parsing tag name and attributes
                    switch ($PHASE) {
                        case'NAME':
                            // Parse tag name characters
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
                            // Parse attribute value (handles quoted and non-quoted values)
                            if ($explode === '"' && !$quoted) {
                                $val_reached = true;
                                $quoted = true;
                                break;
                            }
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
                            }
                            if ($val_reached) {
                                $val = "$val$explode";
                            }
                            break;
                        case'attrKey-spacefound':
                        case'attrKey':
                            // Parse attribute key
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
                            // Intermediate state between attributes
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
                $collected_letters = [];
                $opened = false;
            } else {
                // Accumulate characters for text or tag content
                $collected_letters[] = $char;
            }
            $backslashed = false;
        }
        // Any trailing text after the last tag
        $return[] = ['Text' => implode('', $collected_letters),
            'type' => 'TEXT', 'when' => 'final bytes', 'close-ment-type' => 'TEXT'];
        return ['$return' => $return];
    }

    /**
     * Converts the parsed AST to HTML using parseModes or default rendering.
     */
    public function toHTML(): ?string
    {
        // Only render if parsing has occurred
        if (is_null($this->parsed)) return null;
        return $this->parsed->toString($this->parseModes);
    }

    /**
     * Serializes the object for JSON encoding.
     */
    public function jsonSerialize(): ?array
    {
        // Returns the array representation for JSON serialization
        return $this->toArray();
    }

    /**
     * Returns an array representation of the parsed BBCode and internal data.
     */
    public function toArray(): ?array
    {
        // Output both the parsed AST and the internal string/lexed array
        return ['parsed' => $this->parsed, 'innerArray' => $this->array];
    }

    /**
     * Adds custom parseModes for rendering specific tags.
     * @param array $parseModes - Associative array: tagName => callable
     * @return $this
     */
    public function addparseModes(array $parseModes): self
    {
        // Add/replace parseModes for tag names (skipping 'settings')
        foreach ($parseModes as $parseName => $parseMode) {
            if ('settings' === $parseName) {
                continue;
            }
            $this->parseModes[$parseName] = $parseMode;
        }
        return $this;
    }

    /**
     * Returns the current parseModes array.
     */
    public function getparseModes(): array
    {
        // Returns all parseModes
        return $this->parseModes;
    }

    /**
     * Overwrites all parseModes with a new array.
     */
    public function setparseModes(array $parseModes): self
    {
        // Clear and set new parseModes
        $this->parseModes = [];
        return $this->addparseModes($parseModes);
    }
}

/**
 * Converts a mixed value to a string, handling arrays, null, booleans, etc.
 */
function objectToString(mixed $mixed): string
{
    // Converts null/true/false/array to string, otherwise stringifies the value
    return match ($mixed) {
        null => 'NULL',
        true => 'true',
        false => 'false',
        default => (is_array($mixed) ? implode(',', $mixed) : "$mixed"),
    };
}
