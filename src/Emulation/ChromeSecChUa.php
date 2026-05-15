<?php

declare(strict_types=1);

namespace Reqxide\Emulation;

final class ChromeSecChUa
{
    /** @var list<string> */
    private const array GREASE_CHARS = [' ', '(', ':', '-', '.', '/', ')', ';', '=', '?', '_'];

    /** @var list<string> */
    private const array GREASE_VERSIONS = ['8', '99', '24'];

    /**
     * Brand permutation table indexed by major % 6.
     * 0 = Greased brand, 1 = Chromium, 2 = Browser brand (Google Chrome / Microsoft Edge).
     *
     * @var list<list<int>>
     */
    private const array BRAND_PERMUTATIONS = [
        [0, 1, 2], // major%6==0: Greased, Chromium, Browser
        [0, 2, 1], // major%6==1: Greased, Browser, Chromium
        [1, 0, 2], // major%6==2: Chromium, Greased, Browser
        [2, 0, 1], // major%6==3: Browser, Greased, Chromium
        [1, 2, 0], // major%6==4: Chromium, Browser, Greased
        [2, 1, 0], // major%6==5: Browser, Chromium, Greased
    ];

    public static function generate(int $majorVersion, string $browserName = 'Google Chrome'): string
    {
        $char1 = self::GREASE_CHARS[$majorVersion % 11];
        $char2 = self::GREASE_CHARS[($majorVersion + 1) % 11];
        $greasedBrand = "Not{$char1}A{$char2}Brand";
        $greasedVersion = self::GREASE_VERSIONS[$majorVersion % 3];

        $brands = [
            [$greasedBrand, $greasedVersion],
            ['Chromium', (string) $majorVersion],
            [$browserName, (string) $majorVersion],
        ];

        $permutation = self::BRAND_PERMUTATIONS[$majorVersion % 6];

        return sprintf(
            '"%s";v="%s", "%s";v="%s", "%s";v="%s"',
            $brands[$permutation[0]][0],
            $brands[$permutation[0]][1],
            $brands[$permutation[1]][0],
            $brands[$permutation[1]][1],
            $brands[$permutation[2]][0],
            $brands[$permutation[2]][1],
        );
    }
}
