<?php

declare(strict_types=1);

namespace Tests\Unit\Msc;

use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Rules\D1_00041Rule;
use PHPUnit\Framework\TestCase;

final class D1_00041RuleTest extends TestCase
{
    private D1_00041Rule $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new D1_00041Rule();
    }

    public function test_rejeita_despesa_saude_sem_co(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '622130000',
            valor: 1500.0,
            tipoValor: 'ending_balance',
            ics: [
                'IC1' => '10301',
                'TIPO1' => 'FS',
            ],
        ));

        $this->assertNotNull($result);
        $this->assertSame(
            'despesa com ações e serviços públicos de saúde sem o código de acompanhamento orçamentário (CO) específico.',
            $result,
        );
        $this->assertSame('D1_00041', $this->rule->getCode());
    }

    public function test_rejeita_despesa_saude_com_co_generico_0000(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '631100000',
            valor: 800.0,
            tipoValor: 'ending_balance',
            ics: [
                'IC1' => '10301',
                'TIPO1' => 'FS',
                'IC2' => '0000',
                'TIPO2' => 'CO',
            ],
        ));

        $this->assertNotNull($result);
    }

    public function test_aceita_despesa_saude_com_co_valido(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '622130000',
            valor: 1500.0,
            tipoValor: 'ending_balance',
            ics: [
                'IC1' => '10301',
                'TIPO1' => 'FS',
                'IC2' => '1234',
                'TIPO2' => 'CO',
            ],
        ));

        $this->assertNull($result);
    }

    public function test_ignora_despesa_com_funcao_diferente_de_saude(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '622130000',
            valor: 1500.0,
            tipoValor: 'ending_balance',
            ics: [
                'IC1' => '12301',
                'TIPO1' => 'FS',
            ],
        ));

        $this->assertNull($result);
    }

    public function test_ignora_saldo_final_zerado(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '622130000',
            valor: 0.0,
            tipoValor: 'ending_balance',
            ics: [
                'IC1' => '10301',
                'TIPO1' => 'FS',
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
                'IC1' => '10301',
                'TIPO1' => 'FS',
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
                'IC1' => '10301',
                'TIPO1' => 'FS',
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
                'IC1' => '10301',
                'TIPO1' => 'FS',
            ],
        ));

        $this->assertNotNull($result);
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
