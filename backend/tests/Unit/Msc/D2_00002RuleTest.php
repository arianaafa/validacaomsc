<?php

declare(strict_types=1);

namespace Tests\Unit\Msc;

use App\Services\Msc\Clients\SiconfiClient;
use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Rules\D2_00002Rule;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class D2_00002RuleTest extends TestCase
{
    public function test_ignora_validacao_fora_do_periodo_de_encerramento(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient->expects($this->never())->method('getDcaAnexo');

        $rule = new D2_00002Rule($siconfiClient);
        $rule->prepare('3550308', 2023, 6, 'MSCC');

        $this->assertNull($rule->finalizeFile());
    }

    public function test_aceita_valor_vpd_fundeb_maior_que_zero_no_periodo_13(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->expects($this->once())
            ->method('getDcaAnexo')
            ->with('3550308', 2023, 'DCA-Anexo I-HI')
            ->willReturn([
                $this->registroVpdFundeb(2834765424.75),
            ]);

        $rule = new D2_00002Rule($siconfiClient);
        $rule->prepare('3550308', 2023, 13, 'MSCC');

        $this->assertNull($rule->finalizeFile());
        $this->assertSame('D2_00002', $rule->getCode());
    }

    public function test_rejeita_quando_anexo_retorna_array_vazio(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getDcaAnexo')
            ->willReturn([]);

        $rule = new D2_00002Rule($siconfiClient);
        $rule->prepare('3550308', 2023, 13, 'MSCC');

        $result = $rule->finalizeFile();

        $this->assertNotNull($result);
        $this->assertSame(
            'não foi informado, no Anexo I-HI da DCA, valor da variação patrimonial diminutiva com o FUNDEB.',
            $result,
        );
    }

    public function test_rejeita_quando_linha_fundeb_nao_e_encontrada(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getDcaAnexo')
            ->willReturn([
                [
                    'cod_conta' => 'P3.0.0.0.0.00.00',
                    'conta' => '3.0.0.0.0.00.00 - Variação Patrimonial Diminutiva',
                    'valor' => 100.0,
                ],
            ]);

        $rule = new D2_00002Rule($siconfiClient);
        $rule->prepare('3550308', 2023, 13, 'MSCC');

        $this->assertNotNull($rule->finalizeFile());
    }

    public function test_rejeita_quando_valor_vpd_fundeb_e_zero(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getDcaAnexo')
            ->willReturn([
                $this->registroVpdFundeb(0.0),
            ]);

        $rule = new D2_00002Rule($siconfiClient);
        $rule->prepare('3550308', 2023, 13, 'MSCC');

        $this->assertNotNull($rule->finalizeFile());
    }

    public function test_rejeita_quando_valor_vpd_fundeb_e_ausente(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getDcaAnexo')
            ->willReturn([
                [
                    'cod_conta' => 'P3.5.2.2.0.00.00',
                    'conta' => '3.5.2.2.0.00.00 - Transferências ao FUNDEB',
                ],
            ]);

        $rule = new D2_00002Rule($siconfiClient);
        $rule->prepare('3550308', 2023, 13, 'MSCC');

        $this->assertNotNull($rule->finalizeFile());
    }

    public function test_rejeita_quando_api_siconfi_falha(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getDcaAnexo')
            ->willThrowException(new RuntimeException('Falha na API.'));

        $rule = new D2_00002Rule($siconfiClient);
        $rule->prepare('3550308', 2023, 13, 'MSCC');

        $this->assertNotNull($rule->finalizeFile());
    }

    public function test_ignora_linha_do_arquivo(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $rule = new D2_00002Rule($siconfiClient);

        $this->assertNull($rule->validate(new MscLineData(
            linha: 1,
            conta: '622130000',
            ics: [],
            valor: 100.0,
            tipoValor: 'ending_balance',
            naturezaValor: 'D',
        )));
    }

    /**
     * @return array<string, float|string>
     */
    private function registroVpdFundeb(float $valor): array
    {
        return [
            'cod_conta' => 'P3.5.2.2.0.00.00',
            'conta' => '3.5.2.2.0.00.00 - Transferências ao FUNDEB',
            'valor' => $valor,
        ];
    }
}
