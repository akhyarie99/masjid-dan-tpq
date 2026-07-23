import moment from 'moment-hijri'

// moment-hijri's bundled locale ('iMMMM') defaults to Arabic script month names,
// which visually reorders when mixed with Latin digits (BIDI). Format manually
// with the standard Indonesian Latin transliteration instead.
const HIJRI_MONTHS_ID = [
  'Muharram', 'Safar', 'Rabiul Awal', 'Rabiul Akhir',
  'Jumadil Awal', 'Jumadil Akhir', 'Rajab', 'Syakban',
  'Ramadan', 'Syawal', 'Zulkaidah', 'Zulhijah',
]

export function formatHijriDate(date) {
  const m = moment(date)
  return `${m.iDate()} ${HIJRI_MONTHS_ID[m.iMonth()]} ${m.iYear()} H`
}
