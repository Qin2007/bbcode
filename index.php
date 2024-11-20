<?= '<!DOCTYPE html><title>BBCode parser</title><style>body{font-family: monospace}</style>' .
'<style>a:visited,a:link{color:blue;}a:hover{color:orangered;}a:active{color:black;}</style>';
require_once 'BBcode.php';
// this file is for backwards compatibility with my site, you may copy it,
// but not edit it to the repository, otherwise feel free to use this too.
$COMPOUND = <<<'BBCODE'
[h1]BBCode parser[/h1]

BBCODE;
for ($i = 0; $i < 124; $i++) {
    $COMPOUND = "{$COMPOUND}[*]$i\n";
}
$bbcode = new BBCode("$COMPOUND");
$bbcode->parse();//->addparseModes([]);
echo "{$bbcode->toHTML()}";
