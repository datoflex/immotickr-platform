<?php

declare(strict_types=1);

namespace App\Support\ListingIngest;

use DOMDocument;
use DOMXPath;

final class EmailParser
{
    public static function extractImmoItems(string $rawEmailBody): array
    {
        if (!preg_match('~Content-Type:\s*text/html[^;]*;?.*?\r?\n\r?\n(.*)~is', $rawEmailBody, $m)) {
            return [];
        }

        $html = quoted_printable_decode($m[1]);

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        $xp = new DOMXPath($dom);

        $clean = static function (string $value): string {
            return trim(str_replace("\xc2\xa0", ' ', $value));
        };

        $cleanUrl = static function (string $url) use ($clean): string {
            $url = $clean($url);
            if ($url === '') {
                return '';
            }
            if (stripos($url, 'ttps://') === 0) {
                $url = 'h' . $url;
            }

            $stripParams = ['source', 'tabId', 'tId', 'e', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'favorite', 'id'];

            $rebuild = static function (array $parts, array $query) {
                $scheme = $parts['scheme'] ?? 'https';
                $host = $parts['host'] ?? '';
                $port = isset($parts['port']) ? ':' . $parts['port'] : '';
                $path = $parts['path'] ?? '';
                $qs = $query ? '?' . http_build_query($query) : '';
                $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
                if ($host === '') {
                    return '';
                }
                return $scheme . '://' . $host . $port . $path . $qs . $fragment;
            };

            $parts = parse_url($url);
            if ($parts && isset($parts['host']) && stripos($parts['host'], 'api.thinkimmo.com') !== false && isset($parts['query'])) {
                parse_str($parts['query'], $query);
                if (!empty($query['q'])) {
                    $url = urldecode((string) $query['q']);
                    $parts = parse_url($url);
                }
            }

            if ($parts && isset($parts['query'])) {
                parse_str($parts['query'], $query);
                foreach ($stripParams as $key) {
                    unset($query[$key]);
                }
                $url = $rebuild($parts, $query);
            }

            return $url ?: $clean($url);
        };

        $items = [];
        foreach ($xp->query('//table[@width="390" and @bgcolor="#FFFFFF"]') as $card) {
            $title = $clean($xp->evaluate('string(.//a[1])', $card));
            $detailUrl = $cleanUrl($xp->evaluate('string(.//a[1]/@href)', $card));
            $address = $clean($xp->evaluate('string(.//span[contains(., ", Deutschland")][1])', $card));
            $preisbew = $clean($xp->evaluate('string(.//span[contains(.,"Preisbewertung")]/following::img[1]/@alt)', $card));
            $standort = $clean($xp->evaluate('string(.//span[contains(.,"Standortbewertung")]/following::img[1]/@alt)', $card));

            $price = $clean($xp->evaluate('string(.//span[contains(normalize-space(.),"Preis:")]/ancestor::td/following-sibling::td[1]//span[1])', $card));
            $priceM2 = $clean($xp->evaluate('string(.//span[contains(normalize-space(.),"Preis/m")]/ancestor::td/following-sibling::td[1]//span[1])', $card));
            $flaeche = $clean($xp->evaluate('string(.//span[contains(normalize-space(.),"Fl")]/ancestor::td/following-sibling::td[1]//span[1])', $card));
            $zimmer = $clean($xp->evaluate('string(.//span[contains(normalize-space(.),"Zimmer:")]/ancestor::td/following-sibling::td[1]//span[1])', $card));
            $renditePot = $clean($xp->evaluate('string(.//span[contains(normalize-space(.),"Rendite (pot.):")]/ancestor::td/following-sibling::td[1]//span[1])', $card));
            $renditeIst = $clean($xp->evaluate('string(.//span[contains(normalize-space(.),"Rendite (ist):")]/ancestor::td/following-sibling::td[1]//span[1])', $card));
            $mietePotM2 = $clean($xp->evaluate('string(.//span[contains(normalize-space(.),"Miete/m") and contains(normalize-space(.),"(pot.):")]/ancestor::td/following-sibling::td[1]//span[1])', $card));
            $mieteIstM2 = $clean($xp->evaluate('string(.//span[contains(normalize-space(.),"Miete/m") and contains(normalize-space(.),"(ist):")]/ancestor::td/following-sibling::td[1]//span[1])', $card));
            $baujahr = $clean($xp->evaluate('string(.//span[contains(normalize-space(.),"Baujahr:")]/ancestor::td/following-sibling::td[1]//span[1])', $card));
            $erbbaurecht = $clean($xp->evaluate('string(.//span[contains(normalize-space(.),"Erbbaurecht:")]/ancestor::td/following-sibling::td[1]//span[1])', $card));
            $zv = $clean($xp->evaluate('string(.//span[contains(normalize-space(.),"ZV:")]/ancestor::td/following-sibling::td[1]//span[1])', $card));
            $vermietet = $clean($xp->evaluate('string(.//span[contains(normalize-space(.),"Vermietet:")]/ancestor::td/following-sibling::td[1]//span[1])', $card));

            $sourceUrl = $cleanUrl($xp->evaluate('string(.//span[contains(normalize-space(.),"Auf diesen Plattformen gefunden")]/following::a[1]/@href)', $card));
            if ($sourceUrl === '') {
                $sourceUrl = $detailUrl;
            }

            $isSourceRow = stripos($title, 'kleinanzeigen') !== false;
            $isEmptyRow = ($address === '' && $price === '' && $priceM2 === '' && $flaeche === '' && $zimmer === '');
            if ($isSourceRow || $isEmptyRow) {
                continue;
            }

            $key = $title . '|' . $address . '|' . $price;
            if (isset($items[$key])) {
                continue;
            }

            $items[$key] = [
                'title' => $title,
                'detail_url' => $detailUrl,
                'source_url' => $sourceUrl,
                'address' => $address,
                'preisbewertung' => $preisbew,
                'standortbewertung' => $standort,
                'price' => $price,
                'price_m2' => $priceM2,
                'flaeche' => $flaeche,
                'zimmer' => $zimmer,
                'rendite_pot' => $renditePot,
                'rendite_ist' => $renditeIst,
                'miete_pot_m2' => $mietePotM2,
                'miete_ist_m2' => $mieteIstM2,
                'baujahr' => $baujahr,
                'erbbaurecht' => $erbbaurecht,
                'zv' => $zv,
                'vermietet' => $vermietet,
            ];
        }

        return array_values($items);
    }
}
