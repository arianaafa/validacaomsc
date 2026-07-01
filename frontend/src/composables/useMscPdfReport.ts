import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'
import auraTechIconSrc from '@/assets/aura-tech-icon.png'
import {
  formatEnteLabel,
  getMunicipioByCode,
  hasEnteData,
} from '@/services/ibgeApi'
import type { MunicipioEnte, MscValidationError, MscValidationErrorTipo } from '@/types/msc'

type PdfDocument = InstanceType<typeof jsPDF>

const BRAND_COLOR: [number, number, number] = [30, 58, 138]
const ZEBRA_COLOR: [number, number, number] = [248, 250, 252]
const ERROR_TEXT_COLOR: [number, number, number] = [127, 29, 29]
const ALERT_TEXT_COLOR: [number, number, number] = [120, 83, 14]
const MUTED_TEXT_COLOR: [number, number, number] = [100, 116, 139]
const FOOTER_BORDER_COLOR: [number, number, number] = [226, 232, 240]

const PAGE_MARGIN_X = 14
const PAGE_CHROME_TOP_MARGIN = 18
const FOOTER_RESERVED_HEIGHT = 12
const FOOTER_TEXT = 'Audita MSC — Um produto Aura Tech Solutions'

const BRAND_BAR_HEIGHT = 6
const HEADER_LOCKUP_ICON_HEIGHT = 8
const CONTINUATION_LOCKUP_ICON_HEIGHT = 5
const CONTENT_START_Y = BRAND_BAR_HEIGHT + 3 + HEADER_LOCKUP_ICON_HEIGHT + 5

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

interface TipoCellParseHookData {
  section: 'head' | 'body' | 'foot'
  column: { index: number }
  cell: {
    raw: unknown
    styles: {
      textColor?: number | [number, number, number]
      fontStyle?: string
    }
  }
}

interface BrandIconAssets {
  dataUrl: string
  aspectRatio: number
}

let cachedBrandIconAssets: BrandIconAssets | null = null

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

async function loadImageDataUrl(url: string): Promise<string> {
  const response = await fetch(url)

  if (!response.ok) {
    throw new Error('Não foi possível carregar o ícone da marca para o PDF.')
  }

  const blob = await response.blob()

  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = (): void => {
      if (typeof reader.result !== 'string') {
        reject(new Error('Formato de imagem inválido para o PDF.'))
        return
      }

      resolve(reader.result)
    }
    reader.onerror = (): void => {
      reject(new Error('Falha ao processar o ícone da marca para o PDF.'))
    }
    reader.readAsDataURL(blob)
  })
}

async function loadImageAspectRatio(dataUrl: string): Promise<number> {
  return new Promise((resolve, reject) => {
    const image = new Image()
    image.onload = (): void => {
      resolve(image.naturalWidth / image.naturalHeight)
    }
    image.onerror = (): void => {
      reject(new Error('Não foi possível determinar as dimensões do ícone da marca.'))
    }
    image.src = dataUrl
  })
}

async function resolveBrandIconAssets(): Promise<BrandIconAssets> {
  if (cachedBrandIconAssets !== null) {
    return cachedBrandIconAssets
  }

  const dataUrl = await loadImageDataUrl(auraTechIconSrc)
  const aspectRatio = await loadImageAspectRatio(dataUrl)

  cachedBrandIconAssets = { dataUrl, aspectRatio }

  return cachedBrandIconAssets
}

function drawBrandBar(doc: PdfDocument): void {
  const pageWidth = doc.internal.pageSize.getWidth()

  doc.setFillColor(...BRAND_COLOR)
  doc.rect(0, 0, pageWidth, BRAND_BAR_HEIGHT, 'F')
}

function measureBrandLockupWidth(
  iconHeight: number,
  aspectRatio: number,
  compact: boolean,
): number {
  const iconWidth = iconHeight * aspectRatio
  const textWidth = compact ? 28 : 36

  return iconWidth + 2 + textWidth
}

function drawBrandLockup(
  doc: PdfDocument,
  assets: BrandIconAssets,
  options: {
    iconHeight: number
    align: 'left' | 'right'
    compact: boolean
  },
): void {
  const pageWidth = doc.internal.pageSize.getWidth()
  const iconWidth = options.iconHeight * assets.aspectRatio
  const lockupWidth = measureBrandLockupWidth(
    options.iconHeight,
    assets.aspectRatio,
    options.compact,
  )
  const lockupX = options.align === 'right'
    ? pageWidth - PAGE_MARGIN_X - lockupWidth
    : PAGE_MARGIN_X
  const iconY = BRAND_BAR_HEIGHT + (options.compact ? 2 : 3)
  const iconX = lockupX

  doc.addImage(
    assets.dataUrl,
    'PNG',
    iconX,
    iconY,
    iconWidth,
    options.iconHeight,
  )

  const textX = iconX + iconWidth + 2
  const brandFontSize = options.compact ? 8 : 11
  const subtitleFontSize = options.compact ? 6 : 7
  const brandBaselineY = iconY + options.iconHeight * 0.42
  const subtitleBaselineY = brandBaselineY + (options.compact ? 3.2 : 4)

  doc.setFont('helvetica', 'bold')
  doc.setFontSize(brandFontSize)
  doc.setTextColor(...BRAND_COLOR)
  doc.text('AURA TECH', textX, brandBaselineY)

  doc.setFont('helvetica', 'normal')
  doc.setFontSize(subtitleFontSize)
  doc.setTextColor(...MUTED_TEXT_COLOR)
  doc.text('Solutions', textX, subtitleBaselineY)
}

function drawPageFooter(
  doc: PdfDocument,
  pageNumber: number,
  totalPages: number,
): void {
  const pageWidth = doc.internal.pageSize.getWidth()
  const pageHeight = doc.internal.pageSize.getHeight()
  const separatorY = pageHeight - FOOTER_RESERVED_HEIGHT

  doc.setDrawColor(...FOOTER_BORDER_COLOR)
  doc.setLineWidth(0.25)
  doc.line(PAGE_MARGIN_X, separatorY, pageWidth - PAGE_MARGIN_X, separatorY)

  doc.setFont('helvetica', 'normal')
  doc.setFontSize(8)
  doc.setTextColor(...MUTED_TEXT_COLOR)

  const textY = separatorY + 5
  doc.text(FOOTER_TEXT, PAGE_MARGIN_X, textY)
  doc.text(
    `Página ${pageNumber} de ${totalPages}`,
    pageWidth - PAGE_MARGIN_X,
    textY,
    { align: 'right' },
  )
}

function applyPageChrome(doc: PdfDocument, assets: BrandIconAssets): void {
  const totalPages = doc.getNumberOfPages()

  for (let page = 1; page <= totalPages; page += 1) {
    doc.setPage(page)
    drawBrandBar(doc)

    if (page === 1) {
      drawBrandLockup(doc, assets, {
        iconHeight: HEADER_LOCKUP_ICON_HEIGHT,
        align: 'left',
        compact: false,
      })
    } else {
      drawBrandLockup(doc, assets, {
        iconHeight: CONTINUATION_LOCKUP_ICON_HEIGHT,
        align: 'right',
        compact: true,
      })
    }

    drawPageFooter(doc, page, totalPages)
  }
}

function drawExecutiveHeader(
  doc: PdfDocument,
  analyzedFilename: string,
  periodo: string,
  generatedAt: Date,
  ente: MunicipioEnte,
  startY: number,
): number {
  const pageWidth = doc.internal.pageSize.getWidth()
  let cursorY = startY

  doc.setFont('helvetica', 'bold')
  doc.setFontSize(16)
  doc.setTextColor(...BRAND_COLOR)
  doc.text('Audita MSC - Relatório de Inconsistências', PAGE_MARGIN_X, cursorY)

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
  doc: PdfDocument,
  startY: number,
  summary: SummaryCounts,
): number {
  const pageWidth = doc.internal.pageSize.getWidth()
  const gap = 8
  const cardWidth = (pageWidth - PAGE_MARGIN_X * 2 - gap) / 2
  const cardHeight = 22
  const leftX = PAGE_MARGIN_X
  const rightX = leftX + cardWidth + gap

  doc.setDrawColor(...FOOTER_BORDER_COLOR)
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

function applyTipoCellStyle(data: TipoCellParseHookData): void {
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

function resolveDescriptionColumnWidth(doc: PdfDocument): number {
  const pageWidth = doc.internal.pageSize.getWidth()
  const fixedColumnsWidth = 18 + 30 + 26 + 18

  return pageWidth - PAGE_MARGIN_X * 2 - fixedColumnsWidth
}

function resolveOutputFilename(
  analyzedFilename: string,
  outputFilename?: string,
): string {
  const customName = outputFilename?.trim()

  if (customName === undefined || customName === '') {
    return sanitizeOutputFilename(analyzedFilename)
  }

  return customName.endsWith('.pdf') ? customName : `${customName}.pdf`
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
    const brandIconAssets = await resolveBrandIconAssets()

    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' })

    const tableStartY = drawSummaryCards(
      doc,
      drawExecutiveHeader(
        doc,
        analyzedFilename,
        periodo,
        generatedAt,
        ente,
        CONTENT_START_Y,
      ),
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
          top: PAGE_CHROME_TOP_MARGIN,
          right: PAGE_MARGIN_X,
          bottom: FOOTER_RESERVED_HEIGHT,
          left: PAGE_MARGIN_X,
        },
        styles: {
          font: 'helvetica',
          fontSize: TABLE_FONT_SIZE,
          overflow: 'linebreak',
          cellPadding: 2.5,
          lineColor: [...FOOTER_BORDER_COLOR],
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
        didParseCell: (data: TipoCellParseHookData): void => {
          applyTipoCellStyle(data)
        },
      })
    }

    applyPageChrome(doc, brandIconAssets)

    doc.save(resolveOutputFilename(analyzedFilename, options?.outputFilename))
  }

  return { exportToPdf }
}
