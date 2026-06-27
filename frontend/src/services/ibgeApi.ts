export interface MunicipioEnte {
  municipio: string
  uf: string
  estado: string
}

const API_BASE_URL = 'https://servicodados.ibge.gov.br/api/v1/localidades/municipios'
const CACHE_PREFIX = 'ibge:municipio:'

function emptyEnte(): MunicipioEnte {
  return {
    municipio: '',
    uf: '',
    estado: '',
  }
}

function readCachedEnte(code: string): MunicipioEnte | null {
  try {
    const raw = localStorage.getItem(`${CACHE_PREFIX}${code}`)

    if (raw === null) {
      return null
    }

    const parsed: unknown = JSON.parse(raw)

    if (
      typeof parsed === 'object'
      && parsed !== null
      && 'municipio' in parsed
      && 'uf' in parsed
      && 'estado' in parsed
      && typeof parsed.municipio === 'string'
      && typeof parsed.uf === 'string'
      && typeof parsed.estado === 'string'
    ) {
      return parsed
    }
  } catch {
    return null
  }

  return null
}

function writeCachedEnte(code: string, ente: MunicipioEnte): void {
  if (ente.municipio === '') {
    return
  }

  try {
    localStorage.setItem(`${CACHE_PREFIX}${code}`, JSON.stringify(ente))
  } catch {
    // Ignora falhas de quota ou modo privado do navegador.
  }
}

function parseIbgeResponse(data: unknown): MunicipioEnte {
  if (typeof data !== 'object' || data === null) {
    return emptyEnte()
  }

  const payload = data as Record<string, unknown>
  const municipio = typeof payload.nome === 'string' ? payload.nome : ''

  const regiaoImediata = payload['regiao-imediata']
  const regiaoIntermediaria = typeof regiaoImediata === 'object' && regiaoImediata !== null
    ? (regiaoImediata as Record<string, unknown>)['regiao-intermediaria']
    : null
  const ufData = typeof regiaoIntermediaria === 'object' && regiaoIntermediaria !== null
    ? (regiaoIntermediaria as Record<string, unknown>).UF
    : null

  if (typeof ufData !== 'object' || ufData === null) {
    return emptyEnte()
  }

  const ufRecord = ufData as Record<string, unknown>
  const uf = typeof ufRecord.sigla === 'string' ? ufRecord.sigla : ''
  const estado = typeof ufRecord.nome === 'string' ? ufRecord.nome : ''

  if (municipio === '' || uf === '' || estado === '') {
    return emptyEnte()
  }

  return { municipio, uf, estado }
}

export async function getMunicipioByCode(code: string): Promise<MunicipioEnte> {
  const normalizedCode = code.trim()

  if (normalizedCode === '') {
    return emptyEnte()
  }

  const cached = readCachedEnte(normalizedCode)

  if (cached !== null) {
    return cached
  }

  try {
    const response = await fetch(`${API_BASE_URL}/${normalizedCode}`, {
      headers: { Accept: 'application/json' },
    })

    if (!response.ok) {
      return emptyEnte()
    }

    const payload: unknown = await response.json()
    const ente = parseIbgeResponse(payload)

    writeCachedEnte(normalizedCode, ente)

    return ente
  } catch {
    return emptyEnte()
  }
}

export function formatEnteLabel(ente: MunicipioEnte): string | null {
  if (ente.municipio === '' && ente.uf === '' && ente.estado === '') {
    return null
  }

  const parts = [ente.municipio, ente.uf, ente.estado].filter((part) => part !== '')

  return parts.join(' · ')
}

export function hasEnteData(ente: MunicipioEnte): boolean {
  return ente.municipio !== '' || ente.uf !== '' || ente.estado !== ''
}
