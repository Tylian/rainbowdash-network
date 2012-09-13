<?php

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

class NoSwearPlugin extends Plugin
{

    function onStartNoticeSave($notice) {
        $s = '\!\@\#\$\%\^\&\*';
        $wordlist = <<<HERE
d[i$s][c$s]?khead
dik
[^s]c[u$s]nt
s[h$s][i$s]te?(head)?
\bvag(ina)?\b
(m[ou]th(er|uh|a))?f[r$s]?[uo$s][c$s]?k
\bf[r$s]?e[c$s]?k\b
(da)?fuq
b[ie$s]a?[t$s][c$s][h$s]
\b(candy.?)?ass(hole|hat)?\b
c[o$s][c$s]?k.?suc?k
nigg[^l]
\bcum\b
fag(g.t)?
p[ui$s][s$s]s
p[e3]?n[i1][5s]
ahole
d[o$s]?[u$s]che?(bag)?
b[aeiou$s]s?t[aeiou$s]rd
boner
fellat
jizz?
testicle
testes
w[ea$s]n[kg]
\bdbag
\btit[stzi](llat)?
\ban[ui]s
\banal\b
masturbat
fap
q[u$s][e$s][e$s]f
HERE;
        $wordlist = '/((' . str_replace("\n", ")|(", $wordlist) . '))/i';
        $notice->content = preg_replace($wordlist, '****', $notice->content);
        $notice->rendered = preg_replace($wordlist, '****', $notice->rendered);
        return true;
    }

}
?>
