<?= '<!DOCTYPE html><title>BBCode parser</title><style>body{font-family: monospace}</style>' .
'<style>a:visited,a:link{color:blue;}a:hover{color:orangered;}a:active{color:black;}</style>';
require_once 'BBcode.php';

use BBCode\BBCode;
use function BBCode\htmlspecialchars12;
use function BBCode\json_fromArray;

// this file is for backwards compatibility with my site, you may copy it,
// but not edit it to the repository, otherwise feel free to use this too.

$COMPOUND = <<<'BBCODE'
before-begin
[h1]Hello.spec[/h1]
[p start=here]Hello.spec[/p]
outerHTML
[no parse
[no parse]

BBCODE. "\nafter-end";
$bbcode = new BBCode("$COMPOUND");
$outerHTML = (function (string $name, array $attributes, string $children, string $else): string {
    return $else;
});/*addparseModes([
    'pre:pre' => [
        'closure' => (function (string $string, string $char): string {
            return match ($char) {
                "\n", "\\n" => "{$string}[br/]",
                '[', ']' => "$string\\$char",
                //' ' => "$string&#32;",
                default => "$string$char",
            };
        }), 'start' => '[code]', 'end' => '[/code]',
    ],
]->)*/
//echo "\n<pre>";
//ob_start('BBCode\\htmlspecialchars12');
echo "\n{$bbcode->parse()->toHTML()}";
