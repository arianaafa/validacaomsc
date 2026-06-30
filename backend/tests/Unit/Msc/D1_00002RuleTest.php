<?php

declare(strict_types=1);

namespace Tests\Unit\Msc;

use App\Services\Msc\Clients\SiconfiClient;
use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Rules\D1_00002Rule;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class D1_00002RuleTest extends TestCase
{
    public function test_ignora_validacao_fora_do_periodo_de_encerramento(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient->expects($this->never())->method('getExtratoEntregas');

        $rule = new D1_00002Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 6, 'MSCC');

        $this->assertNull($rule->finalizeFile());
    }

    public function test_aceita_dca_homologada_no_periodo_13(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->expects($this->once())
            ->method('getExtratoEntregas')
            ->with('3550308', 2024)
            ->willReturn([
                $this->registroDca('HO'),
            ]);

        $rule = new D1_00002Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 13, 'MSCC');

        $this->assertNull($rule->finalizeFile());
        $this->assertSame('D1_00002', $rule->getCode());
    }

    public function test_rejeita_quando_dca_nao_e_encontrada(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getExtratoEntregas')
            ->willReturn([]);

        $rule = new D1_00002Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 13, 'MSCC');

        $result = $rule->finalizeFile();

        $this->assertNotNull($result);
        $this->assertSame(
            'ente federativo possui pendências na homologação da declaração de contas anuais (DCA) no Siconfi.',
            $result,
        );
    }

    public function test_rejeita_dca_com_status_diferente_de_homologado(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getExtratoEntregas')
            ->willReturn([
                $this->registroDca('EN'),
            ]);

        $rule = new D1_00002Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 13, 'MSCC');

        $this->assertNotNull($rule->finalizeFile());
    }

    public function test_rejeita_quando_alguma_dca_do_exercicio_nao_esta_homologada(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getExtratoEntregas')
            ->willReturn([
                $this->registroDca('HO'),
                $this->registroDca('RE'),
            ]);

        $rule = new D1_00002Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 13, 'MSCC');

        $this->assertNotNull($rule->finalizeFile());
    }

    public function test_rejeita_quando_api_siconfi_falha(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getExtratoEntregas')
            ->willThrowException(new RuntimeException('Falha na API.'));

        $rule = new D1_00002Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 13, 'MSCC');

        $this->assertNotNull($rule->finalizeFile());
    }

    public function test_ignora_linha_do_arquivo(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $rule = new D1_00002Rule($siconfiClient);

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
     * @return array<string, int|string>
     */
    private function registroDca(string $statusRelatorio): array
    {
        return [
            'entregavel' => 'Declaração de Contas Anuais',
            'exercicio' => 2024,
            'status_relatorio' => $statusRelatorio,
        ];
    }
}
