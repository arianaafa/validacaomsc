<?php

declare(strict_types=1);

namespace Tests\Unit\Msc;

use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Rules\D1_00039Rule;
use PHPUnit\Framework\TestCase;

final class D1_00039RuleTest extends TestCase
{
    private D1_00039Rule $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new D1_00039Rule();
    }

    public function test_rejeita_despesa_com_fonte_condicionada_segundo_digito_9(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '622130000',
            valor: 1500.0,
            tipoValor: 'ending_balance',
            ics: [
                'IC1' => '1923',
                'TIPO1' => 'FR',
            ],
        ));

        $this->assertNotNull($result);
        $this->assertSame(
            'despesa orçamentária registrada com fonte de recursos condicionada (dígito 9 na segunda posição da fonte).',
            $result,
        );
        $this->assertSame('D1_00039', $this->rule->getCode());
    }

    public function test_aceita_despesa_com_fonte_sem_segundo_digito_9(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '622130000',
            valor: 1500.0,
            tipoValor: 'ending_balance',
            ics: [
                'IC1' => '1234',
                'TIPO1' => 'FR',
            ],
        ));

        $this->assertNull($result);
    }

    public function test_aceita_conta_prefixo_63_com_fonte_valida(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '631100000',
            valor: 800.0,
            tipoValor: 'ending_balance',
            ics: [
                'IC1' => '0100',
                'TIPO1' => 'FR',
            ],
        ));

        $this->assertNull($result);
    }

    public function test_rejeita_conta_prefixo_63_com_fonte_condicionada(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '631100000',
            valor: 800.0,
            tipoValor: 'ending_balance',
            ics: [
                'IC1' => '0900',
                'TIPO1' => 'FR',
            ],
        ));

        $this->assertNotNull($result);
    }

    public function test_ignora_saldo_final_zerado(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '622130000',
            valor: 0.0,
            tipoValor: 'ending_balance',
            ics: [
                'IC1' => '1923',
                'TIPO1' => 'FR',
            ],
        ));

        $this->assertNull($result);
    }

    public function test_ignora_beginning_balance(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '622130000',
            valor: 1500.0,
            tipoValor: 'beginning_balance',
            ics: [
                'IC1' => '1923',
                'TIPO1' => 'FR',
            ],
        ));

        $this->assertNull($result);
    }

    public function test_ignora_conta_fora_dos_prefixos_62_e_63(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '521100000',
            valor: 1500.0,
            tipoValor: 'ending_balance',
            ics: [
                'IC1' => '1923',
                'TIPO1' => 'FR',
            ],
        ));

        $this->assertNull($result);
    }

    public function test_ignora_conta_com_mascara_fora_dos_prefixos_62_e_63(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '6.11.1.0.0.0.0.0.0',
            valor: 1500.0,
            tipoValor: 'ending_balance',
            ics: [
                'IC1' => '1923',
                'TIPO1' => 'FR',
            ],
        ));

        $this->assertNull($result);
    }

    public function test_aceita_conta_com_mascara_prefixo_62(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '6.22.1.3.0.0.0.0.0',
            valor: 1500.0,
            tipoValor: 'ending_balance',
            ics: [
                'IC1' => '1923',
                'TIPO1' => 'FR',
            ],
        ));

        $this->assertNotNull($result);
    }

    public function test_ignora_fonte_ausente(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '622130000',
            valor: 1500.0,
            tipoValor: 'ending_balance',
            ics: [],
        ));

        $this->assertNull($result);
    }

    /**
     * @param array<string, string> $ics
     */
    private function lineData(
        string $conta,
        float $valor,
        string $tipoValor,
        array $ics = [],
    ): MscLineData {
        return new MscLineData(
            linha: 1,
            conta: $conta,
            ics: $ics,
            valor: $valor,
            tipoValor: $tipoValor,
            naturezaValor: 'D',
        );
    }
}
