# bbcode

My public BBCode parser

feel free to contribute

## Basic Syntax

example:

```bbcode
[parent="im dad"]
    [child="son"]
        content
        [pre/]
    [/child]
[/parent]
```

note that `[href href=URL]` is equal to `[href=URL]`.

## Basic supported tags

- `[h1]` to `[h6]`: replaces the tag with the `<h1></h1>` tags. doesnt accept attributes.
- `[br]` inserts a newline `<br/>`. doesnt accept attributes.
- `[hidden]` removes all children by retuning nothing `''`
- `[url=URL]` OR `[url url=URL]` OR `[a url=URL]` OR `[a href=URL]` OR `[href=URL]` WHERE `URL` is your url, even if
  invalid we just paste it into the `a` tag, if not present left not formatted
- `[color=COLOR]` WHERE `COLOR` is a html valid color, if invalid then idk. if not present then left as is
- `[p color=COLOR]` WHERE `COLOR` is a html valid color, if invalid then idk. if not present then just the p tag
- `[ol]` OR `[ol start=INT reversed=""]` OR `[ul]` inserts an ol or ul. if ol then INT is an integer for where to start.
  reversed="" just needed to be added and its attributed. ul doesnt accept attributes.
- `[li]` doesnt accept attributes.
- `[b]` OR `[i]` OR `[s]` OR `[i]`: Basic BBCode tags
  with `<strong>`, `<em>`, `<span style=\"text-decoration: line-through;\">` doesnt accept attributes.
- `[u]` : Basic BBCode tag with `<span style='text-decoration: underline;'>`.
- `[sub]` OR `[sup]` inserts `<sub>` OR `<sup>` doesnt accept attributes.
- `[code]` inserts a pre tag and a code tag, white space is preserved
- `[img]URL[/img]` OR `[img url="URL"/]` WHERE `URL` is the url of the image. inserts an image. alt="" can be added for
  alt text
- `[root]` disappears (has to do with a hack i designed for thing to work)

## $this Library

anything with an `_` in front of its name shouldnt be used outside the library

### usage

first create a new Object by doing `new BBCode($string)` and put your BBCode string in the $string variable

then you can add custom parse modes, custom parse modes can be added by `addparseModes` which takes an associative array
where the key is the tag name and the value a function with the signature below

```php
function (string $name, array $attributes, string $children, string $else):string
```
then call the `parse` method and the `toHTML` method to extract the html.


