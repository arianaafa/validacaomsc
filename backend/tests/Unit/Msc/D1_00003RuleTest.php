<?php

declare(strict_types=1);

namespace Tests\Unit\Msc;

use App\Services\Msc\Clients\SiconfiClient;
use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Rules\D1_00003Rule;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class D1_00003RuleTest extends TestCase
{
    public function test_ignora_validacao_de_janeiro_a_abril(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient->expects($this->never())->method('getExtratoEntregas');

        $rule = new D1_00003Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 4, 'MSCC');

        $this->assertNull($rule->finalizeFile());
    }

    public function test_aceita_primeiro_quadrimestre_homologado_em_maio(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->expects($this->once())
            ->method('getExtratoEntregas')
            ->with('3550308', 2024)
            ->willReturn([
                $this->registroRgfExecutivo(1, 'HO'),
            ]);

        $rule = new D1_00003Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 5, 'MSCC');

        $this->assertNull($rule->finalizeFile());
        $this->assertSame('D1_00003', $rule->getCode());
    }

    public function test_rejeita_quadrimestre_obrigatorio_ausente_em_maio(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getExtratoEntregas')
            ->willReturn([]);

        $rule = new D1_00003Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 5, 'MSCC');

        $result = $rule->finalizeFile();

        $this->assertNotNull($result);
        $this->assertSame(
            'poder executivo do ente federativo possui pendências na homologação de relatórios de gestão fiscal (RGF) no Siconfi.',
            $result,
        );
    }

    public function test_rejeita_quadrimestre_com_status_diferente_de_homologado(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getExtratoEntregas')
            ->willReturn([
                $this->registroRgfExecutivo(1, 'EN'),
            ]);

        $rule = new D1_00003Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 5, 'MSCC');

        $this->assertNotNull($rule->finalizeFile());
    }

    public function test_rejeita_quando_faltam_quadrimestres_obrigatorios_em_setembro(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getExtratoEntregas')
            ->willReturn([
                $this->registroRgfExecutivo(1, 'HO'),
            ]);

        $rule = new D1_00003Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 9, 'MSCC');

        $this->assertNotNull($rule->finalizeFile());
    }

    public function test_aceita_primeiro_e_segundo_quadrimestres_homologados_em_setembro(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getExtratoEntregas')
            ->willReturn([
                $this->registroRgfExecutivo(1, 'HO'),
                $this->registroRgfExecutivo(2, 'HO'),
            ]);

        $rule = new D1_00003Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 9, 'MSCC');

        $this->assertNull($rule->finalizeFile());
    }

    public function test_aceita_tres_quadrimestres_homologados_no_periodo_de_encerramento(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getExtratoEntregas')
            ->willReturn([
                $this->registroRgfExecutivo(1, 'HO'),
                $this->registroRgfExecutivo(2, 'HO'),
                $this->registroRgfExecutivo(3, 'HO'),
            ]);

        $rule = new D1_00003Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 13, 'MSCC');

        $this->assertNull($rule->finalizeFile());
    }

    public function test_rejeita_quando_faltam_quadrimestres_no_periodo_de_encerramento(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getExtratoEntregas')
            ->willReturn([
                $this->registroRgfExecutivo(1, 'HO'),
                $this->registroRgfExecutivo(2, 'HO'),
            ]);

        $rule = new D1_00003Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 13, 'MSCC');

        $this->assertNotNull($rule->finalizeFile());
    }

    public function test_ignora_registros_de_outros_poderes(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getExtratoEntregas')
            ->willReturn([
                $this->registroRgfLegislativo(1, 'HO'),
            ]);

        $rule = new D1_00003Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 5, 'MSCC');

        $this->assertNotNull($rule->finalizeFile());
    }

    public function test_rejeita_quando_api_siconfi_falha(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $siconfiClient
            ->method('getExtratoEntregas')
            ->willThrowException(new RuntimeException('Falha na API.'));

        $rule = new D1_00003Rule($siconfiClient);
        $rule->prepare('3550308', 2024, 5, 'MSCC');

        $this->assertNotNull($rule->finalizeFile());
    }

    public function test_ignora_linha_do_arquivo(): void
    {
        $siconfiClient = $this->createMock(SiconfiClient::class);
        $rule = new D1_00003Rule($siconfiClient);

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
    private function registroRgfExecutivo(int $periodo, string $statusRelatorio): array
    {
        return [
            'entregavel' => 'Relatório de Gestão Fiscal',
            'periodicidade' => 'Q',
            'periodo' => $periodo,
            'status_relatorio' => $statusRelatorio,
            'instituicao' => 'Prefeitura Municipal de São Paulo - SP',
        ];
    }

    /**
     * @return array<string, int|string>
     */
    private function registroRgfLegislativo(int $periodo, string $statusRelatorio): array
    {
        return [
            'entregavel' => 'Relatório de Gestão Fiscal',
            'periodicidade' => 'Q',
            'periodo' => $periodo,
            'status_relatorio' => $statusRelatorio,
            'instituicao' => 'Câmara de Vereadores de São Paulo - SP',
        ];
    }
}
