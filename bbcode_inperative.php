<?= '<!DOCTYPE html><pre>';
/*
 * this is a word in progresss file, if you have any edits to make please discuss it
 */
require_once 'BBcode.php';
$bbstring = <<<'BBCODE'
[xfunction name=main]
    [echo]<!DOCTYPE html>[/echo]
    [xfunction name=parser]
        [echo=vardump /]
    [/xfunction]
[/xfunction]
BBCODE;

//function create_function($array): void
//{
//    $vars = array();
//    foreach ($array['children'] as $command) {
//        if ($command['type'] !== '_AST2') continue;
//
//        echo _htmlspecialchars12__(_json_fromArray__(['$command' => $command]));
//    }
//}
//
//function BBcompile(BBCode $BBCode): string
//{
//    $bbarray = $BBCode->parse()->toArray();
//    _run($bbarray['children'][0]);
//    return '';//_implode2__("\n", $outp);}
/*class _BBFunction
{
    public function __construct($BBFunction)
    {
        foreach ($BBFunction as $command) {
            echo _htmlspecialchars12__(_json_fromArray__($command));
        }
    }
}

class BBcompile
{
    private array $commands = array();

    public function __construct(BBCode $code)
    {
        $this->commands = new _BBFunction($code->parse()->toArray()['children'][0]);

    }

    public function __toString(): string
    {
    }
}*/

$bb = (new BBCode($bbstring))->parse();
//(new BBcompile($bb));
echo "\n\n---\n\n" . _htmlspecialchars12__(_json_fromArray__($bb->toArray()));
