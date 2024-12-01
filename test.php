<?= '<!DOCTYPE html><title>BBCode parser</title><style>body{font-family: monospace}</style>' .
'<style>a:visited,a:link{color:blue;}a:hover{color:orangered;}a:active{color:black;}</style>';
require_once 'BBcode.php';
// this file is for backwards compatibility with my site, you may copy it,
// but not edit it to the repository, otherwise feel free to use this too.

$COMPOUND = <<<'BBCODE'
[time="2023-06-18T13:56:04Z" f="<![CDATA[[pre0-dayNumber] [MonthName] [year4]]]>"/]
BBCODE;
$bbcode = new BBCode("\n\n$COMPOUND\n\n");
$outerHTML = (function (string $name, array $attributes, string $children, string $else): string {
    return $else;
});
$bbcode->parse()->setdebugMode(true)->addparseModes([
]);
echo "\n{$bbcode->toJSON_HTML(4)}";
