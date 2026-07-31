<?php

use App\Support\DocumentNumberGenerator;

test('document numbers are sequential and gap-free within a scope', function () {
    $generator = app(DocumentNumberGenerator::class);

    $numbers = collect(range(1, 20))->map(fn () => $generator->next('po', 'warehouse', 1, 2026));

    expect($numbers->unique())->toHaveCount(20);

    $sequence = $numbers->map(fn (string $no) => (int) substr($no, -6))->values()->all();

    expect($sequence)->toBe(range(1, 20));
});

test('different warehouse scopes get independent sequences', function () {
    $generator = app(DocumentNumberGenerator::class);

    $warehouse1 = $generator->next('po', 'warehouse', 1, 2026);
    $warehouse2 = $generator->next('po', 'warehouse', 2, 2026);

    expect($warehouse1)->toEndWith('000001')
        ->and($warehouse2)->toEndWith('000001')
        ->and($warehouse1)->not->toBe($warehouse2);
});

test('different document types within the same scope get independent sequences', function () {
    $generator = app(DocumentNumberGenerator::class);

    $po = $generator->next('po', 'warehouse', 1, 2026);
    $grn = $generator->next('grn', 'warehouse', 1, 2026);

    expect($po)->toEndWith('000001')
        ->and($grn)->toEndWith('000001');
});

test('different fiscal years within the same scope get independent sequences', function () {
    $generator = app(DocumentNumberGenerator::class);

    $y2026 = $generator->next('po', 'warehouse', 1, 2026);
    $y2027 = $generator->next('po', 'warehouse', 1, 2027);

    expect($y2026)->toEndWith('000001')
        ->and($y2027)->toEndWith('000001');
});

test('company-wide sequences default to scope id zero', function () {
    $generator = app(DocumentNumberGenerator::class);

    $first = $generator->next('journal', 'company');
    $second = $generator->next('journal', 'company');

    expect($first)->toEndWith('000001')
        ->and($second)->toEndWith('000002');
});
