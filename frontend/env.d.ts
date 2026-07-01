/// <reference types="vite/client" />

declare module 'jspdf' {
  export class jsPDF {
    constructor(options?: {
      orientation?: 'portrait' | 'landscape' | 'p' | 'l'
      unit?: 'pt' | 'px' | 'in' | 'mm' | 'cm' | 'ex' | 'em' | 'pc'
      format?: string | [number, number]
    })

    internal: {
      pageSize: {
        getWidth(): number
        getHeight(): number
      }
    }

    setFillColor(r: number, g: number, b: number): void
    setDrawColor(r: number, g: number, b: number): void
    setTextColor(r: number, g: number, b: number): void
    setFont(fontName: string, fontStyle?: string): void
    setFontSize(size: number): void
    setLineWidth(width: number): void
    rect(x: number, y: number, w: number, h: number, style?: string): void
    roundedRect(
      x: number,
      y: number,
      w: number,
      h: number,
      rx: number,
      ry: number,
      style?: string,
    ): void
    line(x1: number, y1: number, x2: number, y2: number): void
    text(
      text: string | string[],
      x: number,
      y: number,
      options?: { align?: 'left' | 'center' | 'right' | 'justify' },
    ): void
    setPage(page: number): void
    getNumberOfPages(): number
    save(filename: string): void
    addImage(
      imageData: string,
      format: string,
      x: number,
      y: number,
      width: number,
      height: number,
    ): void
  }
}

declare module 'jspdf-autotable' {
  import type { jsPDF } from 'jspdf'

  type PdfDocument = InstanceType<typeof jsPDF>

  interface AutoTableOptions {
    startY?: number
    head?: string[][]
    body?: string[][]
    margin?: {
      top?: number
      right?: number
      bottom?: number
      left?: number
    }
    styles?: Record<string, unknown>
    headStyles?: Record<string, unknown>
    alternateRowStyles?: Record<string, unknown>
    columnStyles?: Record<number, Record<string, unknown>>
    didParseCell?: (data: {
      section: 'head' | 'body' | 'foot'
      column: { index: number }
      cell: {
        raw: unknown
        styles: {
          textColor?: number | [number, number, number]
          fontStyle?: string
        }
      }
    }) => void
  }

  export default function autoTable(doc: PdfDocument, options: AutoTableOptions): void
}
