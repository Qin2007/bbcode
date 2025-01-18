<?= '<!DOCTYPE html><title>BBCode parser</title><style>body{font-family: monospace}</style>' .
'<style>a:visited,a:link{color:blue;}a:hover{color:orangered;}a:active{color:black;}</style>';
require_once 'BBcode.php';
// this file is for backwards compatibility with my site, you may copy it,
// but not edit it to the repository, otherwise feel free to use this too.

$COMPOUND = <<<'BBCODE'
<![pre[name='rotate or dont (rod)'
setup:
    roll the dice
    the car points to the player whos dice is the lowest
    set the roundes
    lay down the map
    give every1 a rotate and dont card
play:
    everyone chooses 1 card of the 'rotate' or 'dont' cards without others knowing
    when everyone is ready reveal the cards
    the Car rotates for every player who has choosen the rotate card
    the car points at a player and then he looses needs to go back 1 space
    the others roll the dice
    and move foward
    RODsTension2:
        when you are attacked 3 times in a row (cars front is you)
        you may draw a BoB carD and play it instead of the 'ROTATE' or 'DONT' carDs

spaces: 'RODsTension' One
    +amount:
        move forward the amount
    -amount:
        move backward the amount
    ROLL:
        roll again
    the 'STOP GATE' cannot be walked past and must be landed on:
        unless you started the turn on that
    Target:
        be shot at and reset back to start
    teleport:
        if landed on go to the other
note_using 'RODsTension': ONE
    First use the 'DONT' cards (RED) +0
    then use the 'ROTATE' carDs (BLUE) +1
    then use the 'ROTATE2' carDs (DARK GREEN) +2
    then use the 'rROTATE' aka (Reverse ROTATE) carDs (ORANGE) -1

    First move like normal
    then use space actions
RODsTension2:
    bombs:
        when using an BasicBomB: (7 ingame)
            rotate the car 1 time OR send() a car 1 space back
        an RareBomb: (4 ingame)
            rotate the car 3 times OR send() a car 5 spaces back
        an QBomB: (3 ingame)
            rotate the car to a player of choice OR send() 2 car 4 spaces back
        an LegendaryBomb: (1 ingame)
            rotate the car to a player of choice and force it to stay for that vote round
            OR send() a car to start
    battery:
        draw 7 BOB carDs
        you can use up to 5 carDs jn any order of the following 'BasicBattery' or a 'RareBattery' or a 'QBattery' or a 'LegendaryBattery'
        when using an  'BasicBattery' add 1 to dice roll (11 ingame)
        when using an  'RareBattery' add 3 to dice roll (5 ingame)
        when using an  'QBattery' add 5 to dice roll and when hiting someone they go back 3 spaces(3 ingame)
        when using an  'LegendaryBattery' add 5 to dice roll and when hiting someone they go back to start (1 ingame)

end:
    the First one who has made 3 rounds WinS

BBCODE;
$bbcode = new BBCode("\n$COMPOUND\n");
$outerHTML = (function (string $name, array $attributes, string $children, string $else): string {
    return $else;
});
$bbcode->addparseModes([
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
])->parse()->setdebugMode(true);
echo "\n{$bbcode->toJSON_HTML(4)}";
