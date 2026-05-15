<?php

declare(strict_types=1);

use Reqxide\Emulation\ChromeSecChUa;

it('generates correct Sec-CH-UA for Chrome versions', function (int $major, string $expected): void {
    expect(ChromeSecChUa::generate($major))->toBe($expected);
})->with([
    'v105' => [105, '"Google Chrome";v="105", "Not)A;Brand";v="8", "Chromium";v="105"'],
    'v107' => [107, '"Google Chrome";v="107", "Chromium";v="107", "Not=A?Brand";v="24"'],
    'v110' => [110, '"Chromium";v="110", "Not A(Brand";v="24", "Google Chrome";v="110"'],
    'v116' => [116, '"Chromium";v="116", "Not)A;Brand";v="24", "Google Chrome";v="116"'],
    'v119' => [119, '"Google Chrome";v="119", "Chromium";v="119", "Not?A_Brand";v="24"'],
    'v120' => [120, '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"'],
    'v123' => [123, '"Google Chrome";v="123", "Not:A-Brand";v="8", "Chromium";v="123"'],
    'v124' => [124, '"Chromium";v="124", "Google Chrome";v="124", "Not-A.Brand";v="99"'],
    'v128' => [128, '"Chromium";v="128", "Not;A=Brand";v="24", "Google Chrome";v="128"'],
    'v129' => [129, '"Google Chrome";v="129", "Not=A?Brand";v="8", "Chromium";v="129"'],
    'v130' => [130, '"Chromium";v="130", "Google Chrome";v="130", "Not?A_Brand";v="99"'],
    'v131' => [131, '"Google Chrome";v="131", "Chromium";v="131", "Not_A Brand";v="24"'],
    'v133' => [133, '"Not(A:Brand";v="99", "Google Chrome";v="133", "Chromium";v="133"'],
    'v135' => [135, '"Google Chrome";v="135", "Not-A.Brand";v="8", "Chromium";v="135"'],
    'v136' => [136, '"Chromium";v="136", "Google Chrome";v="136", "Not.A/Brand";v="99"'],
    'v142' => [142, '"Chromium";v="142", "Google Chrome";v="142", "Not_A Brand";v="99"'],
    'v145' => [145, '"Not:A-Brand";v="99", "Google Chrome";v="145", "Chromium";v="145"'],
    'v146' => [146, '"Chromium";v="146", "Not-A.Brand";v="24", "Google Chrome";v="146"'],
    'v147' => [147, '"Google Chrome";v="147", "Not.A/Brand";v="8", "Chromium";v="147"'],
]);

it('generates correct Sec-CH-UA for Edge versions', function (int $major, string $expected): void {
    expect(ChromeSecChUa::generate($major, 'Microsoft Edge'))->toBe($expected);
})->with([
    'v131' => [131, '"Microsoft Edge";v="131", "Chromium";v="131", "Not_A Brand";v="24"'],
    'v146' => [146, '"Chromium";v="146", "Not-A.Brand";v="24", "Microsoft Edge";v="146"'],
    'v147' => [147, '"Microsoft Edge";v="147", "Not.A/Brand";v="8", "Chromium";v="147"'],
]);

it('produces deterministic output for the same version', function (): void {
    $first = ChromeSecChUa::generate(145);
    $second = ChromeSecChUa::generate(145);

    expect($first)->toBe($second);
});

it('covers all permutation indices via major mod 6', function (): void {
    $seen = [];
    for ($major = 105; $major <= 110; $major++) {
        $result = ChromeSecChUa::generate($major);
        $seen[$major % 6] = $result;
        expect($result)->toBeString()->not->toBeEmpty();
    }

    expect($seen)->toHaveCount(6);
});
