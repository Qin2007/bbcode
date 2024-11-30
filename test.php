<?= '<!DOCTYPE html><title>BBCode parser</title><style>body{font-family: monospace}</style>' .
'<style>a:visited,a:link{color:blue;}a:hover{color:orangered;}a:active{color:black;}</style>';
require_once 'BBcode.php';
// this file is for backwards compatibility with my site, you may copy it,
// but not edit it to the repository, otherwise feel free to use this too.

$COMPOUND = <<<'BBCODE'

BBCODE;
$bbcode = new BBCode("\n\n$COMPOUND\n\n");
$outerHTML = (function (string $name, array $attributes, string $children, string $else): string {
    return $else;
});
$bbcode->parse()->setdebugMode(true)->addparseModes([
    'spoiler' => (function (string $name, array $attrs, string $children, string $else): string {
        $open = '';
        if (array_key_exists('spoilerfor', $attrs)) {
            $spoilerfor = "{$attrs['spoilerfor']}";
        } else {
            $spoilerfor = "spoilers";
        }
        if (array_key_exists('open', $attrs)) {
            $open = "open=\"\"";
        }
        return "<details $open><summary>$spoilerfor</summary><div>$children</div></details>";
    }),
]);
echo "\n{$bbcode->toJSON_HTML(4)}";
