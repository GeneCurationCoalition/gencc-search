<?php

namespace Tests\Unit;

use App\Traits\NormalizesSearchInput;
use Tests\TestCase;

class NormalizesSearchInputTest extends TestCase
{
    /**
     * Expose the protected trait method for testing.
     */
    private function normalizer()
    {
        return new class {
            use NormalizesSearchInput;

            public function normalize($term): string
            {
                return $this->normalizeSearchTerm($term);
            }
        };
    }

    /**
     * @test
     * @dataProvider searchTermProvider
     */
    public function it_normalizes_pasted_search_terms($input, $expected)
    {
        $this->assertSame($expected, $this->normalizer()->normalize($input));
    }

    public function searchTermProvider(): array
    {
        return [
            'leading space'          => [' GJB2', 'GJB2'],
            'trailing space'         => ['GJB2 ', 'GJB2'],
            'surrounding spaces'     => ['  GJB2  ', 'GJB2'],
            'leading tab'            => ["\tGJB2", 'GJB2'],
            'newline from paste'     => ["GJB2\n", 'GJB2'],
            'non-breaking space'     => ["\u{00A0}GJB2\u{00A0}", 'GJB2'],
            'zero width space'       => ["GJB\u{200B}2", 'GJB2'],
            'internal spaces kept'   => ['hearing loss', 'hearing loss'],
            'internal run collapsed' => ['hearing   loss', 'hearing loss'],
            'leading space, phrase'  => [' hearing loss', 'hearing loss'],
            'only whitespace'        => ['   ', ''],
            'empty string'           => ['', ''],
            'null'                   => [null, ''],
            'untouched term'         => ['GJB2', 'GJB2'],
        ];
    }
}
