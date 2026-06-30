<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MscRuleValidationType;
use App\Models\MscRule;
use Illuminate\Database\Seeder;

final class MscRuleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->rules() as $rule) {
            MscRule::query()->updateOrCreate(
                ['code' => $rule['code']],
                $rule,
            );
        }
    }

    /**
     * Dados originados da planilha REGRAS_SICONFI.xlsx (SICONFI).
     *
     * @return list<array{
     *     code: string,
     *     name: string,
     *     validation_type: MscRuleValidationType,
     *     target_group: string,
     *     objective: string,
     *     error_message: string,
     *     is_implemented: bool
     * }>
     */
    private function rules(): array
    {
        return [
            [
                'code' => 'D1_00001',
                'name' => 'Homologação de todos os RREOs.',
                'validation_type' => MscRuleValidationType::Global,
                'target_group' => 'Extrato de Entregas — Relatório Resumido de Execução Orçamentária (RREO)',
                'objective' => 'Verifica na API do Siconfi se todos os RREOs obrigatórios até o mês atual do exercício estão devidamente homologados (status HO).',
                'error_message' => 'ente federativo possui pendências na homologação de relatórios resumidos de execução orçamentária (RREO) no Siconfi.',
                'is_implemented' => true,
            ],
            [
                'code' => 'D1_00002',
                'name' => 'Homologação da DCA.',
                'validation_type' => MscRuleValidationType::Global,
                'target_group' => 'Extrato de Entregas — Declaração de Contas Anuais (DCA)',
                'objective' => 'Verifica na API do Siconfi se a DCA do exercício está devidamente homologada (status HO), aplicando-se exclusivamente para matrizes com período de encerramento (13).',
                'error_message' => 'ente federativo possui pendências na homologação da declaração de contas anuais (DCA) no Siconfi.',
                'is_implemented' => true,
            ],
            [
                'code' => 'D1_00003',
                'name' => 'Homologação de todos os RGFs do poder Executivo.',
                'validation_type' => MscRuleValidationType::Global,
                'target_group' => 'Extrato de Entregas — Relatório de Gestão Fiscal (RGF) do Poder Executivo',
                'objective' => 'Verifica na API do Siconfi se todos os RGFs do Poder Executivo obrigatórios até o mês atual do exercício estão devidamente homologados (status HO).',
                'error_message' => 'poder executivo do ente federativo possui pendências na homologação de relatórios de gestão fiscal (RGF) no Siconfi.',
                'is_implemented' => true,
            ],
            [
                'code' => 'D1_00006',
                'name' => 'Tempestividade na homologação dos RREOs.',
                'validation_type' => MscRuleValidationType::Global,
                'target_group' => 'Extrato de Entregas — Relatório Resumido de Execução Orçamentária (RREO)',
                'objective' => 'Verifica se as datas de homologação (data_status) dos RREOs na API do Siconfi respeitam o limite de 30 dias após o fim de cada bimestre estabelecido pela LRF.',
                'error_message' => 'ente federativo enviou ou homologou o relatório resumido de execução orçamentária (RREO) fora do prazo legal previsto na LRF.',
                'is_implemented' => true,
            ],
            [
                'code' => 'D1_00017',
                'name' => 'Envio de MSCs com valores negativos',
                'validation_type' => MscRuleValidationType::Linha,
                'target_group' => 'Todas as contas da MSC',
                'objective' => 'Verifica a quantidade de matrizes com valores negativos. Cada MSC sem valores negativos vale 1/13 da pontuação.',
                'error_message' => 'Valores negativos não são permitidos na MSC.',
                'is_implemented' => true,
            ],
            [
                'code' => 'D1_00018',
                'name' => 'Envio de MSCs com contas com movimentação inconsistente: SI + MOV <> SF',
                'validation_type' => MscRuleValidationType::Agrupamento,
                'target_group' => 'Contas PCASP (classes 1 a 9) com informações complementares',
                'objective' => 'Verifica a quantidade de matrizes com contas com movimentação inconsistente: saldo inicial + movimentação <> saldo final. Cada MSC sem movimentação incorreta vale 1/13 da pontuação.',
                'error_message' => 'Movimentação inconsistente: saldo inicial + movimentação difere do saldo final para o conjunto de informações complementares.',
                'is_implemented' => true,
            ],
            [
                'code' => 'D1_00021',
                'name' => 'Envio de MSCs com contas do ativo com saldo invertido incorretamente',
                'validation_type' => MscRuleValidationType::Linha,
                'target_group' => 'Grupos do Ativo (1111, 1121, 1125, 1231, 1232)',
                'objective' => 'Verifica a quantidade de matrizes com contas dos grupos 1111, 1121, 1125, 1231 e 1232 cujo saldo final está com natureza diferente da padrão do PCASP Estendido. Cada MSC vale 1/13 da pontuação.',
                'error_message' => 'contas do ativo com saldo invertido incorretamente.',
                'is_implemented' => true,
            ],
            [
                'code' => 'D1_00023',
                'name' => 'Envio de MSCs com dados do poder executivo iguais entre meses diferentes',
                'validation_type' => MscRuleValidationType::Global,
                'target_group' => 'Dados do poder executivo entre competências',
                'objective' => 'Verifica a quantidade matrizes com dados do poder executivo repetida em mais de um mês. Cada MSC única vale 1/13 da pontuação.',
                'error_message' => 'Regra pendente de implementação no sistema.',
                'is_implemented' => false,
            ],
            [
                'code' => 'D1_00025',
                'name' => 'Envio de MSCs com contas do passivo com saldo invertido incorretamente',
                'validation_type' => MscRuleValidationType::Linha,
                'target_group' => 'Grupos do Passivo (2111–215, 221–223)',
                'objective' => 'Verifica a quantidade de matrizes com contas dos grupos 2111, 2112, 2113,2114, 2121, 2122, 2123, 2124, 2125,2126, 213, 214, 215, 221, 222, 223 cujo saldo final está com natureza diferente da padrão do PCASP Estendido. Cada MSC vale 1/13 da pontuação.',
                'error_message' => 'contas do passivo com saldo invertido incorretamente.',
                'is_implemented' => true,
            ],
            [
                'code' => 'D1_00026',
                'name' => 'Envio de MSCs com contas de patrimônio líquido com saldo invertido incorretamente',
                'validation_type' => MscRuleValidationType::Linha,
                'target_group' => 'Grupos de Patrimônio Líquido (2311, 2312, 232, 233, 234, 235, 236)',
                'objective' => 'Verifica a quantidade de matrizes com contas dos grupos 2311, 2312, 232, 233, 234, 235, 236 cujo saldo final está com natureza diferente da padrão do PCASP Estendido. Cada MSC vale 1/13 da pontuação.',
                'error_message' => 'contas de patrimônio líquido com saldo invertido incorretamente.',
                'is_implemented' => true,
            ],
            [
                'code' => 'D1_00027',
                'name' => 'Envio de MSCs com contas com atributo F (financeiro) sem detalhamento de fonte ou destinação de recursos',
                'validation_type' => MscRuleValidationType::Linha,
                'target_group' => 'Contas com atributo financeiro (FP = F)',
                'objective' => 'Verifica a quantidade matrizes com contas contábeis com informação complementar de atributo do superávit financeiro igual a F que não tenham informação complementar de FR - fonte ou destinação de recurso - associada. Cada MSC vale 1/13 da pontuação.',
                'error_message' => 'contas com atributo F (financeiro) sem detalhamento de fonte ou destinação de recursos.',
                'is_implemented' => true,
            ],
            [
                'code' => 'D1_00028',
                'name' => 'Envio de MSCs com todas as classes de contas (Patrimonial, orçamentária e controle)',
                'validation_type' => MscRuleValidationType::Global,
                'target_group' => 'Classes Patrimonial (1–4), Orçamentária (5–6) e Controle (7–8)',
                'objective' => 'Verifica se foram enviados valores nas Matrizes de Saldos Contábeis, diferentes de zero, em todas as classes de contas da MSC: patrimonial (1, 2, 3 e 4), orçamentária (5 e 6) e controle (7 e 8). Cada MSC vale 1/13 da pontuação.',
                'error_message' => 'Não foram enviados valores diferentes de zero em todas as classes de contas (Patrimonial, orçamentária e controle) na MSC.',
                'is_implemented' => true,
            ],
            [
                'code' => 'D1_00029',
                'name' => 'Envio de MSCs com contas de receita orçamentária e deduções sem detalhamento de fonte ou destinação de recurso',
                'validation_type' => MscRuleValidationType::Linha,
                'target_group' => 'Contas de receita orçamentária e deduções (6211, 6212, 6213)',
                'objective' => 'Verifica a quantidade de MSC com contas dos grupos 6211, 6212, 6213 cujos registros não apresentam detalhamento de fonte ou destinação de recurso. Cada MSC vale 1/13 da pontuação.',
                'error_message' => 'contas de receita orçamentária e deduções sem detalhamento de fonte ou destinação de recurso.',
                'is_implemented' => true,
            ],
            [
                'code' => 'D1_00030',
                'name' => 'Envio de MSCs com contas de receita orçamentária e deduções sem o detalhamento de natureza de receita',
                'validation_type' => MscRuleValidationType::Linha,
                'target_group' => 'Contas de receita orçamentária e deduções (6211, 6212, 6213)',
                'objective' => 'Verifica a quantidade de MSC com contas dos grupos 6211, 6212, 6213 cujos registros não apresentam detalhamento de natureza da receita. Cada MSC vale 1/13 da pontuação.',
                'error_message' => 'contas de receita orçamentária e deduções sem o detalhamento de natureza de receita.',
                'is_implemented' => true,
            ],
            [
                'code' => 'D1_00031',
                'name' => 'Envio de MSCs com contas de despesa orçamentária sem o detalhamento de natureza de despesa',
                'validation_type' => MscRuleValidationType::Linha,
                'target_group' => 'Contas de despesa orçamentária (62213)',
                'objective' => 'Verifica a quantidade de MSC com contas dos grupos 62213 cujos registros não apresentam detalhamento de natureza da despesa. Cada MSC vale 1/13 da pontuação.',
                'error_message' => 'contas de despesa orçamentária sem o detalhamento de natureza de despesa.',
                'is_implemented' => true,
            ],
            [
                'code' => 'D1_00032',
                'name' => 'Envio de MSCs com contas de despesa orçamentária sem o detalhamento de função/subfunção',
                'validation_type' => MscRuleValidationType::Linha,
                'target_group' => 'Contas de despesa orçamentária (62213)',
                'objective' => 'Verifica a quantidade de MSC com contas dos grupos 62213 cujos registros não apresentam detalhamento de função/subfunção. Cada MSC vale 1/13 da pontuação.',
                'error_message' => 'contas de despesa orçamentária sem o detalhamento de função/subfunção.',
                'is_implemented' => true,
            ],
            [
                'code' => 'D1_00033',
                'name' => 'Envio de MSCs com contas de despesa orçamentária sem o detalhamento de fonte ou destinação de recursos',
                'validation_type' => MscRuleValidationType::Linha,
                'target_group' => 'Contas de despesa orçamentária (62213)',
                'objective' => 'Verifica a quantidade de MSC com contas dos grupos 62213 cujos registros não apresentam detalhamento de fonte ou destinação de recursos. Cada MSC vale 1/13 da pontuação.',
                'error_message' => 'contas de despesa orçamentária sem o detalhamento de fonte ou destinação de recursos.',
                'is_implemented' => true,
            ],
            [
                'code' => 'D1_00034',
                'name' => 'Envio de MSC com contas de VPD com saldo invertido incorretamente',
                'validation_type' => MscRuleValidationType::Linha,
                'target_group' => 'Grupos de VPD (311–363)',
                'objective' => 'Verifica a quantidade de matrizes com contas dos grupos 311, 312, 313, 321, 322, 323, 331, 332, 333 , 351, 352, 353, 361, 362 e 363 cujo saldo final está com natureza diferente da padrão do PCASP Estendido. Exceto MSC de encerrament',
                'error_message' => 'contas de VPD com saldo invertido incorretamente.',
                'is_implemented' => true,
            ],
            [
                'code' => 'D1_00035',
                'name' => 'Envio de MSC com contas de VPA com saldo invertido incorretamente',
                'validation_type' => MscRuleValidationType::Linha,
                'target_group' => 'Contas de VPA',
                'objective' => 'Verifica a quantidade de matrizes com contas de VPA cujo saldo final está com natureza diferente da padrão do PCASP Estendido. Exceto MSC de encerramento. Cada MSC vale 1/12 da pontuação.',
                'error_message' => 'contas de VPA com saldo invertido incorretamente.',
                'is_implemented' => true,
            ],
            [
                'code' => 'D1_00036',
                'name' => 'Envio de MSC encerramento com saldo final nas contas VPA e VPD',
                'validation_type' => MscRuleValidationType::Linha,
                'target_group' => 'Classes de resultado – VPD (3) e VPA (4)',
                'objective' => 'Verifica se a MSC de encerramento foi encaminhada com o correto encerramento das contas de VPA e VPD. Ou seja, essas contas não podem apresentar saldo final na MSC de encerramento.',
                'error_message' => 'contas de VPA e VPD com saldo final na MSC de encerramento.',
                'is_implemented' => true,
            ],
            [
                'code' => 'D1_00037',
                'name' => 'Quantidade de MSC de estados e municípios com fontes de recursos da União',
                'validation_type' => MscRuleValidationType::Global,
                'target_group' => 'Fontes de Recursos (FR) de 000 a 499',
                'objective' => 'Verifica se estados e municípios enviaram informações em fontes de recursos da União (de 000 a 499). Cada MSC vale 1/13 da pontuação.',
                'error_message' => 'Não foram enviadas informações em fontes de recursos da União (de 000 a 499) na MSC.',
                'is_implemented' => true,
            ],
            [
                'code' => 'D1_00038',
                'name' => 'Quantidade de MSC com contas de previsão e execução orçamentária com saldos invertidos',
                'validation_type' => MscRuleValidationType::Linha,
                'target_group' => 'Classes 5 e 6 — grupos 5.1/6.2/6.3 (natureza D) e 5.2/5.3/6.1 (natureza C)',
                'objective' => 'Verifica contas das classes 5 e 6 com saldo final (ending_balance) diferente de zero cuja natureza contábil está invertida em relação ao padrão do PCASP Estendido.',
                'error_message' => 'contas de previsão e execução orçamentária com saldo invertido incorretamente.',
                'is_implemented' => true,
            ],
            [
                'code' => 'D1_00039',
                'name' => 'Quantidade de MSC com despesas orçamentárias registradas indevidamente com recursos condicionados na codificação de fontes.',
                'validation_type' => MscRuleValidationType::Linha,
                'target_group' => 'Execução da Despesa Orçamentária (contas com prefixo 62 e 63)',
                'objective' => 'Verifica se há execução de despesas (classes 62 e 63) vinculadas a fontes de recursos condicionados por meio da validação do segundo dígito do código da fonte.',
                'error_message' => 'despesa orçamentária registrada com fonte de recursos condicionada (dígito 9 na segunda posição da fonte).',
                'is_implemented' => true,
            ],
            [
                'code' => 'D1_00040',
                'name' => 'Quantidade de MSC com receitas orçamentárias registradas indevidamente com recursos condicionados na codificação de fontes.',
                'validation_type' => MscRuleValidationType::Linha,
                'target_group' => 'Execução da Receita Orçamentária (contas com prefixo 61)',
                'objective' => 'Verifica se há execução de receitas (classe 61) vinculadas a fontes de recursos condicionados por meio da validação do segundo dígito do código da fonte.',
                'error_message' => 'receita orçamentária registrada com fonte de recursos condicionada (dígito 9 na segunda posição da fonte).',
                'is_implemented' => true,
            ],
            [
                'code' => 'D1_00041',
                'name' => 'Verificação da informação de execução de despesas com ações e serviços públicos de saúde detalhados no Código de Acompanhamento Orçamentário (CO) específico.',
                'validation_type' => MscRuleValidationType::Linha,
                'target_group' => 'Execução da Despesa Orçamentária (contas com prefixo 62 e 63) — Função 10 (Saúde)',
                'objective' => 'Garante a identificação obrigatória do CO nas contas de execução de despesa (62 e 63) cuja função orçamentária seja a de Saúde (10).',
                'error_message' => 'despesa com ações e serviços públicos de saúde sem o código de acompanhamento orçamentário (CO) específico.',
                'is_implemented' => true,
            ],
            [
                'code' => 'D1_00042',
                'name' => 'Verificação da informação de execução de despesas com manutenção e desenvolvimento do ensino detalhados no Código de Acompanhamento Orçamentário (CO) específico.',
                'validation_type' => MscRuleValidationType::Linha,
                'target_group' => 'Execução da Despesa Orçamentária (contas com prefixo 62 e 63) — Função 12 (Educação)',
                'objective' => 'Garante a identificação obrigatória do CO nas contas de execução de despesa (62 e 63) cuja função orçamentária seja a de Educação (12).',
                'error_message' => 'despesa com manutenção e desenvolvimento do ensino sem o código de acompanhamento orçamentário (CO) específico.',
                'is_implemented' => true,
            ],
            [
                'code' => 'D1_00043',
                'name' => 'Verificação da informação de execução de despesas com remuneração dos profissionais da educação básica com recursos do Fundeb detalhados no Código de Acompanhamento Orçamentário (CO) específico.',
                'validation_type' => MscRuleValidationType::Linha,
                'target_group' => 'Execução da Despesa Orçamentária (contas com prefixo 62 e 63) — Fontes FUNDEB (540, 541, 542 e 543)',
                'objective' => 'Garante a identificação obrigatória do CO 2012 nas contas de execução de despesa (62 e 63) financiadas com recursos do FUNDEB para remuneração dos profissionais da educação básica.',
                'error_message' => 'despesa com remuneração dos profissionais da educação básica com recursos do Fundeb sem o código de acompanhamento orçamentário (CO) específico.',
                'is_implemented' => true,
            ],
            [
                'code' => 'D1_00044',
                'name' => 'Verifica se há a informação complementar AI - Ano de Inscrição de Restos a Pagar na MSC do ente.',
                'validation_type' => MscRuleValidationType::Linha,
                'target_group' => 'Contas de Restos a Pagar (531, 532, 631, 632)',
                'objective' => 'São analisadas as contas: 531100000, 531200000, 531600000, 531700000, 532100000, 532200000, 532600000, 532700000, 631100000, 631200000, 631300000, 631400000, 631500000, 631600000, 631710000, 631720000, 631910000, 631990000, 632100000, 632200000, 632600000, 632700000, 632910000, 632990000.',
                'error_message' => 'contas de restos a pagar sem a informação complementar de ano de inscrição (AI).',
                'is_implemented' => true,
            ],
        ];
    }
}
