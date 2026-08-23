<?php

namespace Tests\Unit\Models;

use App\Gene;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use App\Events\Genes\GeneSymbolChanged;

/**
 * @group hgnc
 */
#[\PHPUnit\Framework\Attributes\Group('hgnc')]
class GeneTest extends TestCase
{
    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function fires_GeneSymbolChanged_when_updated_and_gene_symbol_has_changed()
    {
        $gene = factory(Gene::class)->create(['gene_symbol' => 'BIRDC']);
        
        Event::fake(GeneSymbolChanged::class);

        $gene->update(['gene_symbol' => 'MLTN1']);

        Event::assertDispatched(GeneSymbolChanged::class, function ($event) use ($gene) {
            return $event->gene == $gene  && $event->previousSymbol == 'BIRDC';
        });
    }
}
