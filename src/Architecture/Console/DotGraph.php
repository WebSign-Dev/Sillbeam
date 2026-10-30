<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture\Console;

/** @internal Graphviz DOT from the same nodes and edges as Mermaid; never parses Mermaid. */
final class DotGraph
{
    public function render(GraphView $view): string
    {
        $caption = array_filter($view->notes, static fn (string $note): bool => !preg_match('/^[a-z_]+\.[a-z_.]+:/', $note));
        $lines = ['digraph architecture {', '    graph [rankdir=LR, bgcolor="white", pad=0.4, nodesep=0.45, ranksep=0.85, fontname="sans-serif", fontsize=12, labelloc=t, labeljust=l, label='.$this->quote(implode("\n", $caption)).', tooltip='.$this->quote(implode("\n", $view->notes)).'];',
            '    node [shape=box, style="rounded,filled", fillcolor="#eff6ff", color="#64748b", fontname="sans-serif", fontsize=12, margin="0.18,0.12"];',
            '    edge [color="#64748b", arrowsize=0.75, fontname="sans-serif", fontsize=10];'];
        foreach ($view->groups as $group => $title) {
            $nodes = array_filter($view->nodes, static fn (array $node): bool => $node['group'] === $group);
            if ([] === $nodes) {
                continue;
            }
            $lines[] = '    subgraph cluster_'.$group.' {';
            $lines[] = '        label='.$this->quote($title).'; color="#cbd5e1"; style=rounded;';
            foreach ($nodes as $node) {
                $label = $node['label'].($node['external'] ? "\nexternal to Project context" : '');
                $lines[] = '        '.$node['id'].' [label='.$this->quote($label).', tooltip='.$this->quote($node['class']).', fillcolor="'.($node['disabled'] ? '#eeeeee' : '#eff6ff').'", color="'.($node['cycle'] ? '#c22' : '#64748b').'", penwidth='.($node['cycle'] ? '3' : '1').', style="rounded,filled'.('unknown' === $node['state'] ? ',dashed' : '').'"];';
            }
            $lines[] = '    }';
        }
        foreach ($view->edges as $edge) {
            $lines[] = '    '.$view->nodes[$edge['source']]['id'].' -> '.$view->nodes[$edge['target']]['id'].($edge['observed'] ? ' [style=dashed, color="#b45309", tooltip="observed undeclared"]' : '').';';
        }
        $lines[] = '}';

        return implode("\n", $lines)."\n";
    }

    /** External text stays a quoted string, never an HTML label, attribute or escape sequence. */
    private function quote(string $text): string
    {
        $lines = explode("\n", $text);
        $lines = array_map(static function (string $line): string {
            $line = preg_replace('/[\p{C}\p{Zl}\p{Zp}]/u', ' ', $line) ?? 'invalid UTF-8 label';

            return str_replace(['\\', '"'], ['\\\\', '\\"'], $line);
        }, $lines);

        return '"'.implode('\\n', $lines).'"';
    }
}
