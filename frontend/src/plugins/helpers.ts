/**
 * Date/formatting helpers used across the app.
 */

export function formatDateRange(start: string, end: string): string {
  const s = new Date(start)
  const e = new Date(end)
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']

  const sDay = s.getDate()
  const sMon = months[s.getMonth()]
  const sYear = s.getFullYear()

  const eDay = e.getDate()
  const eMon = months[e.getMonth()]
  const eYear = e.getFullYear()

  if (sYear === eYear && sMon === eMon && sDay === eDay) {
    return `${sDay} ${sMon} ${sYear}`
  }
  if (sYear === eYear && sMon === eMon) {
    return `${sDay} – ${eDay} ${sMon} ${sYear}`
  }
  if (sYear === eYear) {
    return `${sDay} ${sMon} – ${eDay} ${eMon} ${sYear}`
  }
  return `${sDay} ${sMon} ${sYear} – ${eDay} ${eMon} ${eYear}`
}

export function formatDate(dateStr: string): string {
  const d = new Date(dateStr)
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
  return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`
}

export function calcDays(start: string, end: string): number {
  if (!start || !end) return 0
  const s = new Date(start)
  const e = new Date(end)
  const diff = e.getTime() - s.getTime()
  if (diff < 0) return 0
  return Math.floor(diff / (1000 * 60 * 60 * 24)) + 1
}

export function todayIso(): string {
  return new Date().toISOString().split('T')[0]
}

/**
 * Extract descriptive error messages from an Axios error response.
 * Returns a flat array of strings for display.
 */
export function extractErrors(error: unknown): string[] {
  const e = error as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
  const data = e?.response?.data
  if (!data) return ['Terjadi kesalahan. Silakan coba lagi.']

  const messages: string[] = []
  if (data.errors) {
    for (const fieldErrors of Object.values(data.errors)) {
      messages.push(...fieldErrors)
    }
  } else if (data.message) {
    messages.push(data.message)
  }
  return messages.length > 0 ? messages : ['Terjadi kesalahan. Silakan coba lagi.']
}

export function extractFieldErrors(error: unknown): Record<string, string> {
  const e = error as { response?: { data?: { errors?: Record<string, string[]> } } }
  const raw = e?.response?.data?.errors ?? {}
  const result: Record<string, string> = {}
  for (const [field, msgs] of Object.entries(raw)) {
    result[field] = msgs[0] ?? ''
  }
  return result
}
