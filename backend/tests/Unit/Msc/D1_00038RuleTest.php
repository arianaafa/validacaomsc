<?php

declare(strict_types=1);

namespace Tests\Unit\Msc;

use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Rules\D1_00038Rule;
use PHPUnit\Framework\TestCase;

final class D1_00038RuleTest extends TestCase
{
    private D1_00038Rule $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new D1_00038Rule();
    }

    public function test_rejeita_grupo_62_com_natureza_credora_em_saldo_final(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '621100000',
            valor: 1500.0,
            tipoValor: 'ending_balance',
            naturezaValor: 'C',
        ));

        $this->assertNotNull($result);
        $this->assertSame('D1_00038', $this->rule->getCode());
    }

    public function test_aceita_grupo_61_com_natureza_credora_em_saldo_final(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '611100000',
            valor: 1500.0,
            tipoValor: 'ending_balance',
            naturezaValor: 'C',
        ));

        $this->assertNull($result);
    }

    public function test_aceita_grupo_62_com_natureza_devedora_em_saldo_final(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '622130000',
            valor: 800.0,
            tipoValor: 'ending_balance',
            naturezaValor: 'D',
        ));

        $this->assertNull($result);
    }

    public function test_ignora_saldo_final_zerado(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '621100000',
            valor: 0.0,
            tipoValor: 'ending_balance',
            naturezaValor: 'C',
        ));

        $this->assertNull($result);
    }

    public function test_ignora_beginning_balance(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '621100000',
            valor: 1500.0,
            tipoValor: 'beginning_balance',
            naturezaValor: 'C',
        ));

        $this->assertNull($result);
    }

    public function test_ignora_conta_fora_das_classes_5_e_6(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '711100000',
            valor: 1500.0,
            tipoValor: 'ending_balance',
            naturezaValor: 'C',
        ));

        $this->assertNull($result);
    }

    public function test_rejeita_grupo_52_com_natureza_devedora_em_saldo_final(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '521100000',
            valor: 200.0,
            tipoValor: 'ending_balance',
            naturezaValor: 'D',
        ));

        $this->assertNotNull($result);
    }

    private function lineData(
        string $conta,
        float $valor,
        string $tipoValor,
        string $naturezaValor,
    ): MscLineData {
        return new MscLineData(
            linha: 1,
            conta: $conta,
            ics: [],
            valor: $valor,
            tipoValor: $tipoValor,
            naturezaValor: $naturezaValor,
        );
    }
}
