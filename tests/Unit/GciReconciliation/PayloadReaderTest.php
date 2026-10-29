<?php
namespace Tests\Unit\GciReconciliation;

use App\Services\GciReconciliation\PayloadReader;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PayloadReaderTest extends TestCase
{
    public static function row(): array
    {
        return ['modeInheritance' => 'Autosomal dominant inheritance (HP:0000006)', 'PK' => '0defa473-b0d5-453a-8a45-8b1937a2899f',
            'affiliation' => '10104', 'disease' => 'MONDO_0015470', 'gene' => 'TMPO'];
    }
    public function test_accepts_both_types_and_arbitrary_key_order(): void
    {
        $reader = new PayloadReader();
        $row = self::row();
        $this->assertSame($row, $reader->read(json_encode(['Items' => [$row]]), PayloadReader::NO)['Items'][0]);
        $row['precurationId'] = '10532';
        $this->assertSame($row, $reader->read(json_encode(['Items' => [$row]]), PayloadReader::HAS)['Items'][0]);
    }
    #[DataProvider('invalidInputs')]
    public function test_rejects_invalid_inputs(string $json, string $type): void
    {
        // ValidationException::withMessages uses the validator facade.
        $container = new \Illuminate\Container\Container();
        $translator = new \Illuminate\Translation\Translator(new \Illuminate\Translation\ArrayLoader(), 'en');
        $container->instance('validator', new \Illuminate\Validation\Factory($translator, $container));
        \Illuminate\Support\Facades\Facade::setFacadeApplication($container);
        $this->expectException(ValidationException::class);
        (new PayloadReader())->read($json, $type);
    }
    public static function invalidInputs(): array
    {
        $row = self::row();
        return [
            ['{', PayloadReader::NO], ['{}', PayloadReader::NO], ['{"Items":{}}', PayloadReader::NO],
            [json_encode(['Items' => [$row]]), PayloadReader::HAS],
            [json_encode(['Items' => [$row + ['precurationId' => '7']]]), PayloadReader::NO],
            [json_encode(['Items' => [array_diff_key($row, ['gene' => true])]]), PayloadReader::NO],
            [json_encode(['Items' => [$row + ['precurationId' => '0']]]), PayloadReader::HAS],
        ];
    }
    protected function tearDown(): void
    {
        \Illuminate\Support\Facades\Facade::clearResolvedInstances();
        \Illuminate\Support\Facades\Facade::setFacadeApplication(null);
        parent::tearDown();
    }
}
