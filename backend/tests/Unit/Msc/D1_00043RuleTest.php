<?php

declare(strict_types=1);

namespace Tests\Unit\Msc;

use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Rules\D1_00043Rule;
use PHPUnit\Framework\TestCase;

final class D1_00043RuleTest extends TestCase
{
    private D1_00043Rule $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new D1_00043Rule();
    }

    public function test_rejeita_despesa_fundeb_sem_co(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '622130000',
            valor: 1500.0,
            tipoValor: 'ending_balance',
            ics: [
                'IC1' => '1540',
                'TIPO1' => 'FR',
            ],
        ));

        $this->assertNotNull($result);
        $this->assertSame(
            'despesa com remuneração dos profissionais da educação básica com recursos do Fundeb sem o código de acompanhamento orçamentário (CO) específico.',
            $result,
        );
        $this->assertSame('D1_00043', $this->rule->getCode());
    }

    public function test_rejeita_despesa_fundeb_com_co_generico_0000(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '631100000',
            valor: 800.0,
            tipoValor: 'ending_balance',
            ics: [
                'IC1' => '2541',
                'TIPO1' => 'FR',
                'IC2' => '0000',
                'TIPO2' => 'CO',
            ],
        ));

        $this->assertNotNull($result);
    }

    public function test_rejeita_despesa_fundeb_com_co_incompativel(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '622130000',
            valor: 1500.0,
            tipoValor: 'ending_balance',
            ics: [
                'IC1' => '1540',
                'TIPO1' => 'FR',
                'IC2' => '1070',
                'TIPO2' => 'CO',
            ],
        ));

        $this->assertNotNull($result);
    }

    public function test_aceita_despesa_fundeb_com_co_2012(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '622130000',
            valor: 1500.0,
            tipoValor: 'ending_balance',
            ics: [
                'IC1' => '1540',
                'TIPO1' => 'FR',
                'IC2' => '2012',
                'TIPO2' => 'CO',
            ],
        ));

        $this->assertNull($result);
    }

    public function test_aceita_fontes_fundeb_542_e_543(): void
    {
        foreach (['1542', '3543'] as $fonte) {
            $result = $this->rule->validate($this->lineData(
                conta: '622130000',
                valor: 1500.0,
                tipoValor: 'ending_balance',
                ics: [
                    'IC1' => $fonte,
                    'TIPO1' => 'FR',
                    'IC2' => '2012',
                    'TIPO2' => 'CO',
                ],
            ));

            $this->assertNull($result, "Fonte {$fonte} deveria ser aceita com CO 2012.");
        }
    }

    public function test_ignora_fonte_nao_fundeb(): void
    {
        $result = $this->rule->validate($this->lineData(
            conta: '622130000',
            valor: 1500.0,
            tipoValor: 'ending_balance',
            ics: [
                'IC1' => '1500',
                'TIPO1' => 'FR',
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
                'IC1' => '1540',
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
                'IC1' => '1540',
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
                'IC1' => '1540',
                'TIPO1' => 'FR',
            ],
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
