<?php

namespace App\Services\Mondo;

final class MondoNtrParsing
{
    public static function splitCsv(?string $s): array
    {
        if (!$s) { return []; }

        return array_values(array_filter(array_map(
            fn ($x) => trim($x),
            explode(',', $s)
        ), fn ($x) => $x !== ''));
    }

    /**
     * Parse "MONDO:0009026 Costello syndrome" into ['id' => 'MONDO:0009026', 'label' => 'Costello syndrome']
     * If only ID provided ("MONDO:0009026"), label becomes ''.
     */
    public static function parseMondoIdLabel(string $input): array
    {
        $input = trim($input);

        if ($input === '') {
            return ['id' => '', 'label' => ''];
        }

        // Find MONDO:######## pattern anywhere
        if (preg_match('/\b(MONDO:\d{7})\b/', $input, $m)) {
            $id = $m[1];

            // Remove the ID from the string to get the label
            $label = trim(str_replace($id, '', $input));
            // Also remove leading separators if present
            $label = ltrim($label, "-: ");

            return ['id' => $id, 'label' => $label];
        }

        // Fallback: treat whole string as label if no MONDO id found
        return ['id' => '', 'label' => $input];
    }

    /**
     * Parse comma-separated list of mondo terms into [{id,label}, ...]
     */
    public static function parseMondoList(?string $input): array
    {
        $items = self::splitCsv($input);

        return array_values(array_filter(array_map(function ($item) {
            $parsed = self::parseMondoIdLabel($item);
            // Keep only if we got an ID (optional: allow label-only children)
            return $parsed['id'] !== '' ? $parsed : null;
        }, $items)));
    }

    public static function normalizePmids($pmids): array
    {
        // supports array or string
        $list = is_array($pmids) ? $pmids : self::splitCsv((string)$pmids);

        $out = [];
        foreach ($list as $p) {
            $p = trim((string)$p);
            if ($p === '') { continue; }

            // Accept "PMID:123", "123", "pmid:123"
            if (preg_match('/^pmid:\s*(\d+)$/i', $p, $m)) {
                $out[] = 'PMID:'.$m[1];
            } elseif (preg_match('/^\d+$/', $p)) {
                $out[] = 'PMID:'.$p;
            } else {  // keep as-is if it looks like a ref string
                $out[] = $p;
            }
        }

        return array_values(array_unique($out));
    }
}