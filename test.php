<?= '<!DOCTYPE html><pre>';
require_once 'BBcode.php';
$bbcode = (new BBCode(<<<BBCODE
[parent="im dad"]
    [child="son"]
        content
    [/child]
[/parent]
BBCODE. "\n"));
$bbcode->addparseModes([
    'child' => (function () {
    }),
]);
$bbcode->parse();
echo '<pre><code>' . _htmlspecialchars12__("{$bbcode->toHTML()}") . '</code></pre>';
//echo "{$bbcode->toJSON_HTML()}";
