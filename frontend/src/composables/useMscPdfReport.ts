import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'
import type { CellHookData } from 'jspdf-autotable'
import {
  formatEnteLabel,
  getMunicipioByCode,
  hasEnteData,
} from '@/services/ibgeApi'
import type { MunicipioEnte, MscValidationError, MscValidationErrorTipo } from '@/types/msc'

const BRAND_COLOR: [number, number, number] = [30, 58, 95]
const ZEBRA_COLOR: [number, number, number] = [248, 250, 252]
const ERROR_TEXT_COLOR: [number, number, number] = [127, 29, 29]
const ALERT_TEXT_COLOR: [number, number, number] = [120, 83, 14]
const MUTED_TEXT_COLOR: [number, number, number] = [100, 116, 139]

const PAGE_MARGIN_X = 14
const FOOTER_Y_OFFSET = 10
const TABLE_FONT_SIZE = 8
const TIPO_COLUMN_INDEX = 3
const DESCRIPTION_COLUMN_INDEX = 4

export interface MscPdfReportOptions {
  periodo: string
  ibgeCode?: string | null
  ente?: MunicipioEnte
  outputFilename?: string
}

interface SummaryCounts {
  erros: number
  alertas: number
}

function formatGenerationDate(date: Date): string {
  return new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'short',
    timeStyle: 'medium',
  }).format(date)
}

function formatLinha(linha: number | null): string {
  return linha === null ? '—' : String(linha)
}

function formatConta(contaContabil: string | null): string {
  return contaContabil ?? '—'
}

function formatTipoLabel(tipo: MscValidationErrorTipo): string {
  return tipo === 'erro' ? 'Erro' : 'Alerta'
}

function countByTipo(
  errors: MscValidationError[],
  tipo: MscValidationErrorTipo,
): number {
  return errors.filter((error: MscValidationError): boolean => error.tipo === tipo).length
}

function buildSummaryCounts(errors: MscValidationError[]): SummaryCounts {
  return {
    erros: countByTipo(errors, 'erro'),
    alertas: countByTipo(errors, 'alerta'),
  }
}

function buildTableBody(errors: MscValidationError[]): string[][] {
  return errors.map((error: MscValidationError): string[] => [
    formatLinha(error.linha),
    formatConta(error.conta_contabil),
    error.codigo_regra,
    formatTipoLabel(error.tipo),
    error.descricao,
  ])
}

function sanitizeOutputFilename(filename: string): string {
  const baseName = filename.trim().replace(/\.(csv|pdf)$/i, '')
  const sanitized = baseName.replace(/[^\w.-]+/g, '_').replace(/_+/g, '_')

  if (sanitized === '') {
    return 'validamsc-relatorio-inconsistencias.pdf'
  }

  return `${sanitized}-relatorio.pdf`
}

function drawBrandBar(doc: jsPDF): void {
  const pageWidth = doc.internal.pageSize.getWidth()

  doc.setFillColor(...BRAND_COLOR)
  doc.rect(0, 0, pageWidth, 6, 'F')
}

function drawExecutiveHeader(
  doc: jsPDF,
  analyzedFilename: string,
  periodo: string,
  generatedAt: Date,
  ente: MunicipioEnte,
): number {
  const pageWidth = doc.internal.pageSize.getWidth()
  let cursorY = 16

  doc.setFont('helvetica', 'bold')
  doc.setFontSize(16)
  doc.setTextColor(...BRAND_COLOR)
  doc.text('validaMSC - Relatório de Inconsistências', PAGE_MARGIN_X, cursorY)

  cursorY += 8
  doc.setFont('helvetica', 'normal')
  doc.setFontSize(10)
  doc.setTextColor(51, 65, 85)
  doc.text(`Gerado em: ${formatGenerationDate(generatedAt)}`, PAGE_MARGIN_X, cursorY)

  cursorY += 6
  doc.text(`Arquivo analisado: ${analyzedFilename}`, PAGE_MARGIN_X, cursorY)

  cursorY += 6
  doc.text(`Período de referência: ${periodo}`, PAGE_MARGIN_X, cursorY)

  const enteLabel = formatEnteLabel(ente)

  if (enteLabel !== null) {
    cursorY += 6
    doc.text(`Município: ${enteLabel}`, PAGE_MARGIN_X, cursorY)
  }

  cursorY += 4
  doc.setDrawColor(...BRAND_COLOR)
  doc.setLineWidth(0.6)
  doc.line(PAGE_MARGIN_X, cursorY, pageWidth - PAGE_MARGIN_X, cursorY)

  return cursorY + 8
}

async function resolveEnte(options?: MscPdfReportOptions): Promise<MunicipioEnte> {
  if (options?.ente !== undefined && hasEnteData(options.ente)) {
    return options.ente
  }

  const ibgeCode = options?.ibgeCode?.trim() ?? ''

  if (ibgeCode === '') {
    return { municipio: '', uf: '', estado: '' }
  }

  return getMunicipioByCode(ibgeCode)
}

function drawSummaryCards(
  doc: jsPDF,
  startY: number,
  summary: SummaryCounts,
): number {
  const pageWidth = doc.internal.pageSize.getWidth()
  const gap = 8
  const cardWidth = (pageWidth - PAGE_MARGIN_X * 2 - gap) / 2
  const cardHeight = 22
  const leftX = PAGE_MARGIN_X
  const rightX = leftX + cardWidth + gap

  doc.setDrawColor(226, 232, 240)
  doc.setLineWidth(0.4)

  doc.setFillColor(254, 242, 242)
  doc.roundedRect(leftX, startY, cardWidth, cardHeight, 2, 2, 'FD')
  doc.setFillColor(255, 251, 235)
  doc.roundedRect(rightX, startY, cardWidth, cardHeight, 2, 2, 'FD')

  doc.setFont('helvetica', 'bold')
  doc.setFontSize(9)
  doc.setTextColor(...ERROR_TEXT_COLOR)
  doc.text('Erros críticos', leftX + 4, startY + 8)
  doc.setFontSize(14)
  doc.text(String(summary.erros), leftX + 4, startY + 17)

  doc.setFont('helvetica', 'bold')
  doc.setFontSize(9)
  doc.setTextColor(...ALERT_TEXT_COLOR)
  doc.text('Alertas', rightX + 4, startY + 8)
  doc.setFontSize(14)
  doc.text(String(summary.alertas), rightX + 4, startY + 17)

  return startY + cardHeight + 10
}

function applyTipoCellStyle(data: CellHookData): void {
  if (data.section !== 'body' || data.column.index !== TIPO_COLUMN_INDEX) {
    return
  }

  const tipoLabel = String(data.cell.raw)

  if (tipoLabel === 'Erro') {
    data.cell.styles.textColor = ERROR_TEXT_COLOR
    data.cell.styles.fontStyle = 'bold'

    return
  }

  if (tipoLabel === 'Alerta') {
    data.cell.styles.textColor = ALERT_TEXT_COLOR
    data.cell.styles.fontStyle = 'bold'
  }
}

function addPageFooters(doc: jsPDF): void {
  const totalPages = doc.getNumberOfPages()
  const pageWidth = doc.internal.pageSize.getWidth()
  const pageHeight = doc.internal.pageSize.getHeight()

  for (let page = 1; page <= totalPages; page += 1) {
    doc.setPage(page)
    doc.setFont('helvetica', 'normal')
    doc.setFontSize(9)
    doc.setTextColor(...MUTED_TEXT_COLOR)
    doc.text(
      `Página ${page} de ${totalPages}`,
      pageWidth / 2,
      pageHeight - FOOTER_Y_OFFSET,
      { align: 'center' },
    )
  }
}

function resolveDescriptionColumnWidth(doc: jsPDF): number {
  const pageWidth = doc.internal.pageSize.getWidth()
  const fixedColumnsWidth = 18 + 30 + 26 + 18

  return pageWidth - PAGE_MARGIN_X * 2 - fixedColumnsWidth
}

export function useMscPdfReport(): {
  exportToPdf: (
    filename: string,
    errors: MscValidationError[],
    options?: MscPdfReportOptions,
  ) => Promise<void>
} {
  async function exportToPdf(
    filename: string,
    errors: MscValidationError[],
    options?: MscPdfReportOptions,
  ): Promise<void> {
    const analyzedFilename = filename.trim() === '' ? '—' : filename.trim()
    const periodo = options?.periodo?.trim() === '' || options?.periodo === undefined
      ? '—'
      : options.periodo.trim()
    const generatedAt = new Date()
    const summary = buildSummaryCounts(errors)
    const ente = await resolveEnte(options)

    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' })

    drawBrandBar(doc)

    const tableStartY = drawSummaryCards(
      doc,
      drawExecutiveHeader(doc, analyzedFilename, periodo, generatedAt, ente),
      summary,
    )

    const descWidth = resolveDescriptionColumnWidth(doc)

    if (errors.length === 0) {
      doc.setFont('helvetica', 'italic')
      doc.setFontSize(10)
      doc.setTextColor(...MUTED_TEXT_COLOR)
      doc.text(
        'Nenhuma inconsistência registrada para este upload.',
        PAGE_MARGIN_X,
        tableStartY + 4,
      )
    } else {
      autoTable(doc, {
        startY: tableStartY,
        head: [['Linha', 'Conta', 'Regra', 'Tipo', 'Descrição']],
        body: buildTableBody(errors),
        margin: {
          top: tableStartY,
          right: PAGE_MARGIN_X,
          bottom: 18,
          left: PAGE_MARGIN_X,
        },
        styles: {
          font: 'helvetica',
          fontSize: TABLE_FONT_SIZE,
          overflow: 'linebreak',
          cellPadding: 2.5,
          lineColor: [226, 232, 240],
          lineWidth: 0.1,
          textColor: [51, 65, 85],
          valign: 'top',
        },
        headStyles: {
          fillColor: BRAND_COLOR,
          textColor: [255, 255, 255],
          fontStyle: 'bold',
          halign: 'left',
        },
        alternateRowStyles: {
          fillColor: ZEBRA_COLOR,
        },
        columnStyles: {
          0: { cellWidth: 18, halign: 'center' },
          1: { cellWidth: 30, fontStyle: 'normal' },
          2: { cellWidth: 26 },
          3: { cellWidth: 18, halign: 'center' },
          [DESCRIPTION_COLUMN_INDEX]: {
            cellWidth: descWidth,
          },
        },
        didParseCell: (data: CellHookData): void => {
          applyTipoCellStyle(data)
        },
      })
    }

    addPageFooters(doc)

    const outputFilename = options?.outputFilename?.trim()
      ? options.outputFilename.trim().endsWith('.pdf')
        ? options.outputFilename.trim()
        : `${options.outputFilename.trim()}.pdf`
      : sanitizeOutputFilename(analyzedFilename)

    doc.save(outputFilename)
  }

  return { exportToPdf }
}
