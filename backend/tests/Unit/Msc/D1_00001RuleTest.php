<?php

declare(strict_types=1);

namespace Tests\Unit\Msc;

use App\Services\Msc\Clients\SiconfiClient;
use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Rules\D1_00001Rule;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class D1_00001RuleTest extends TestCase
{
    public function test_ignora_validacao_em_janeiro(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient->expects($this->never())->method('getExtratoEntregas');

        $rule = new D1_00001Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 1, 'MSCC');

        $this->assertNull($rule->finalizeFile());
    }

    public function test_aceita_bimestres_obrigatorios_homologados_em_marco(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->expects($this->once())
            ->method('getExtratoEntregas')
            ->with('3550308', 2024)
            ->willReturn([
                $this->registroRreo(1, 'HO'),
            ]);

        $rule = new D1_00001Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 3, 'MSCC');

        $this->assertNull($rule->finalizeFile());
    }

    public function test_rejeita_bimestre_obrigatorio_ausente_em_marco(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getExtratoEntregas')
            ->willReturn([]);

        $rule = new D1_00001Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 3, 'MSCC');

        $result = $rule->finalizeFile();

        $this->assertNotNull($result);
        $this->assertSame(
            'ente federativo possui pendências na homologação de relatórios resumidos de execução orçamentária (RREO) no Siconfi.',
            $result,
        );
        $this->assertSame('D1_00001', $rule->getCode());
    }

    public function test_rejeita_bimestre_com_status_diferente_de_homologado(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getExtratoEntregas')
            ->willReturn([
                $this->registroRreo(1, 'EN'),
            ]);

        $rule = new D1_00001Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 3, 'MSCC');

        $this->assertNotNull($rule->finalizeFile());
    }

    public function test_rejeita_quando_faltam_bimestres_obrigatorios_em_maio(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getExtratoEntregas')
            ->willReturn([
                $this->registroRreo(1, 'HO'),
            ]);

        $rule = new D1_00001Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 5, 'MSCC');

        $this->assertNotNull($rule->finalizeFile());
    }

    public function test_aceita_primeiro_e_segundo_bimestres_homologados_em_maio(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getExtratoEntregas')
            ->willReturn([
                $this->registroRreo(1, 'HO'),
                $this->registroRreo(2, 'HO'),
            ]);

        $rule = new D1_00001Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 5, 'MSCC');

        $this->assertNull($rule->finalizeFile());
    }

    public function test_rejeita_quando_api_siconfi_falha(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getExtratoEntregas')
            ->willThrowException(new RuntimeException('Falha na API.'));

        $rule = new D1_00001Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 3, 'MSCC');

        $this->assertNotNull($rule->finalizeFile());
    }

    public function test_ignora_linha_do_arquivo(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $rule = new D1_00001Rule($siconfiClient);

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
    private function registroRreo(int $periodo, string $statusRelatorio): array
    {
        return [
            'entregavel' => 'Relatório Resumido de Execução Orçamentária',
            'periodicidade' => 'B',
            'periodo' => $periodo,
            'status_relatorio' => $statusRelatorio,
        ];
    }
}
