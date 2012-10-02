<?php

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

class NoSwearPlugin extends Plugin
{
    public function _filter($content) {
        $s = '\!\@\#\$\%\^\&\*';
        $wordlist = <<<HERE
d[i$s][c$s]?khead
\bdik
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
\bw[ea$s]n[kg]
\bdbag
\btit[stzi](llat)?
\ban[ui]s
\banal\b
masturbat
fap
q[u$s][e$s][e$s]f
d[i$s][l$s][d$s]o
HERE;
        $wordlist = '/((' . str_replace("\n", ")|(", $wordlist) . '))/i';
        $content = preg_replace($wordlist, '****', $content);
        return $content;
    }

    function onStartNoticeSave($notice) {
        $notice->content = $this->_filter($notice->content);
        $notice->rendered = $this->_filter($notice->rendered);
        return true;
    }

    function onStartRegistrationTry($action)
    {
        $action->args['bio'] = $this->_filter($action->trimmed('bio'));

        return true;
    }

    function onStartProfileSaveForm($action)
    {
        $action->args['bio'] = $this->_filter($action->trimmed('bio'));

        return true;
    }
}
?>
