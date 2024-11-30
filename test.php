<?= '<!DOCTYPE html><title>BBCode parser</title><style>body{font-family: monospace}</style>' .
'<style>a:visited,a:link{color:blue;}a:hover{color:orangered;}a:active{color:black;}</style>';
require_once 'BBcode.php';
// this file is for backwards compatibility with my site, you may copy it,
// but not edit it to the repository, otherwise feel free to use this too.

$COMPOUND = <<<'BBCODE'
[h1]Scraper boy.spec[/h1]
[spoiler spoilerfor="ai first draft"]
[p]If someone were bitten by a radioactive web scraper, their superpowers might include:
[ol]
    [li]
        [h3]Instant Data Extraction[/h3]
        [p]The ability to "scrape" information from any source just by looking at it, whether it's a physical book,
            screen, or even someone's thoughts.
    [li]
        [h3]Pattern Recognition[/h3]
        [p]A heightened ability to recognize patterns, structures, and relationships in data, making them a genius at
            solving puzzles or navigating complex systems.
    [li]
        [h3]API Manipulation[/h3]
        [p]The power to interface directly with digital systems, extracting or injecting data into APIs and databases
            by sheer thought or touch.
    [li]
        [h3]Infinite Multitasking[/h3]
        [p]Like a scraper crawling multiple pages at once, they could focus on and process many tasks or conversations
            simultaneously.
    [li]
        [h3]Digital Camouflage[/h3]
        [p]The ability to "blend in" with digital systems, becoming invisible to surveillance or cybersecurity
            measures.
    [li]
        [h3]HTML Vision[/h3]
        [p]Seeing the "source code" of reality, akin to spotting the underlying framework or hidden structures of the
            world around them.
    [li]
        [h3]Unlimited Bandwidth[/h3]
        [p]Super-speed when absorbing knowledge or communicating, almost like downloading entire books or encyclopedias
            in seconds.
    [li]
        [h3]Error Recovery[/h3]
        [p]Resilience to mental "crashes" and the ability to adapt rapidly when encountering unexpected challenges,
            akin to an auto-retry mechanism.
    [li]
        [h3]Link Generation[/h3]
        [p]The capacity to create connections between ideas, people, or places, effectively linking disparate elements
            of the world like hyperlinks.
    [li]
        [h3]Crawler Limbs[/h3]
        [p]Physical adaptations like extendable "crawler" appendages for climbing or multitasking with multiple limbs.[/p]
    [/li]
[/ol]
[p]What other features would you like to imagine for this unique superhero?
[p]A superhero bitten by a radioactive web scraper would also inherit several weaknesses reminiscent of web scrapers'
    vulnerabilities, such as:
[ol]
    [li]
        [h3]Rate Limits and Burnout[/h3]
        [p]Overusing their powers too quickly could lead to exhaustion or temporary shutdown, much like a scraper
            hitting a website's rate limit.
    [li]
        [h3]CAPTCHA Vulnerability[/h3]
        [p]Struggles with challenges designed to differentiate humans from bots, such as solving puzzles or
            understanding emotional nuances.
    [li]
        [h3]Blocked by Firewalls[/h3]
        [p]Inability to access certain places or systems protected by strong security measures, whether physical,
            digital, or mental barriers.
    [li]
        [h3]Dependency on Structured Data[/h3]
        [p]Difficulty functioning in chaotic or unstructured environments where patterns and frameworks are absent or
            unclear.
    [li]
        [h3]Prone to Overloading[/h3]
        [p]Receiving too much information at once could cause their "mental buffer" to overload, leading to confusion
            or even unconsciousness.
    [li]
        [h3]Banned by Robots.txt[/h3]
        [p]Ethical dilemmas or supernatural restrictions preventing them from accessing places or people who have
            explicitly "opted out" of interaction.
    [li]
        [h3]DDoS Susceptibility[/h3]
        [p]Overwhelmed when too many tasks or requests are thrown at them simultaneously, akin to a denial-of-service
            attack.
    [li]
        [h3]Parsing Errors[/h3]
        [p]Misinterpreting complex or ambiguous situations, leading to poor decisions or embarrassing
            misunderstandings.
    [li]
        [h3]Outdated Technology[/h3]
        [p]Vulnerable to newer, more advanced systems or frameworks, requiring constant adaptation to stay relevant and
            effective.
    [li]
        [h3]Vulnerability to Anti-Scraping Tools[/h3]
        [p]Weak against individuals or entities employing countermeasures like fake data, honeypots, or obfuscation to
            confuse or trap them.[/p]
[/ol][/spoiler]
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
