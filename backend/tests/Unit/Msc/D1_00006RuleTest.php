<?php

declare(strict_types=1);

namespace Tests\Unit\Msc;

use App\Services\Msc\Clients\SiconfiClient;
use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Rules\D1_00006Rule;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class D1_00006RuleTest extends TestCase
{
    public function test_aceita_rreos_homologados_dentro_do_prazo(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->expects($this->once())
            ->method('getExtratoEntregas')
            ->with('3550308', 2024)
            ->willReturn([
                $this->registroRreoHomologado(1, '2024-03-30T22:33:30Z'),
                $this->registroRreoHomologado(2, '2024-05-30T22:38:43Z'),
            ]);

        $rule = new D1_00006Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 6, 'MSCC');

        $this->assertNull($rule->finalizeFile());
        $this->assertSame('D1_00006', $rule->getCode());
    }

    public function test_aceita_homologacao_na_data_limite(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getExtratoEntregas')
            ->willReturn([
                $this->registroRreoHomologado(1, '2024-03-30T23:59:59Z'),
            ]);

        $rule = new D1_00006Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 4, 'MSCC');

        $this->assertNull($rule->finalizeFile());
    }

    public function test_rejeita_rreo_homologado_apos_data_limite(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getExtratoEntregas')
            ->willReturn([
                $this->registroRreoHomologado(1, '2024-03-31T00:00:01Z'),
            ]);

        $rule = new D1_00006Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 4, 'MSCC');

        $result = $rule->finalizeFile();

        $this->assertNotNull($result);
        $this->assertSame(
            'ente federativo enviou ou homologou o relatório resumido de execução orçamentária (RREO) fora do prazo legal previsto na LRF.',
            $result,
        );
    }

    public function test_rejeita_sexto_bimestre_homologado_apos_30_de_janeiro_do_ano_seguinte(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getExtratoEntregas')
            ->willReturn([
                $this->registroRreoHomologado(6, '2025-01-31T10:00:00Z'),
            ]);

        $rule = new D1_00006Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 13, 'MSCC');

        $this->assertNotNull($rule->finalizeFile());
    }

    public function test_aceita_sexto_bimestre_homologado_ate_30_de_janeiro_do_ano_seguinte(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getExtratoEntregas')
            ->willReturn([
                $this->registroRreoHomologado(6, '2025-01-30T22:00:00Z'),
            ]);

        $rule = new D1_00006Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 13, 'MSCC');

        $this->assertNull($rule->finalizeFile());
    }

    public function test_ignora_registros_nao_homologados(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getExtratoEntregas')
            ->willReturn([
                $this->registroRreoComStatus(1, 'EN', '2024-04-01T00:00:00Z'),
            ]);

        $rule = new D1_00006Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 4, 'MSCC');

        $this->assertNull($rule->finalizeFile());
    }

    public function test_rejeita_quando_data_status_e_invalida(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getExtratoEntregas')
            ->willReturn([
                $this->registroRreoHomologado(1, 'data-invalida'),
            ]);

        $rule = new D1_00006Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 4, 'MSCC');

        $this->assertNotNull($rule->finalizeFile());
    }

    public function test_rejeita_quando_api_siconfi_falha(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getExtratoEntregas')
            ->willThrowException(new RuntimeException('Falha na API.'));

        $rule = new D1_00006Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 4, 'MSCC');

        $this->assertNotNull($rule->finalizeFile());
    }

    public function test_ignora_linha_do_arquivo(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $rule = new D1_00006Rule($siconfiClient);

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
    private function registroRreoHomologado(int $periodo, string $dataStatus): array
    {
        return $this->registroRreoComStatus($periodo, 'HO', $dataStatus);
    }

    /**
     * @return array<string, int|string>
     */
    private function registroRreoComStatus(int $periodo, string $statusRelatorio, string $dataStatus): array
    {
        return [
            'entregavel' => 'Relatório Resumido de Execução Orçamentária',
            'periodicidade' => 'B',
            'periodo' => $periodo,
            'status_relatorio' => $statusRelatorio,
            'data_status' => $dataStatus,
        ];
    }
}
