export const orderStatuses = {
  1: 'Chờ xác nhận',
  2: 'Đang chuẩn bị',
  3: 'Đang giao',
  4: 'Đã giao',
  5: 'Đã hủy',
  6: 'Đã trả hàng',
}

export const paymentStatuses = { pending: 'Chờ thu tiền', paid: 'Đã thu tiền', cancelled: 'Đã hủy' }

// Legacy prices were displayed as USD. Configure VND only after confirming the stored unit.
const currency = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: import.meta.env.VITE_CATALOG_CURRENCY === 'VND' ? 'VND' : 'USD' })
export const money = value => currency.format(Number(value ?? 0))
export function localDate(value) {
  const date = value ? new Date(String(value).replace(' ', 'T')) : null
  return date && Number.isFinite(date.getTime()) ? new Intl.DateTimeFormat('vi-VN', { dateStyle: 'short', timeStyle: 'short' }).format(date) : '—'
}
export const addressLine = address => [address?.address, address?.town, address?.city, address?.state].filter(Boolean).join(', ')

export function cartOptions(item) {
  return (item.product_model?.variation_index ?? []).map((index, dimension) => {
    const variation = item.product?.variations?.[dimension]
    return variation?.options?.[index] ?? index
  }).join(' / ')
}

export function requestError(error, fallback) {
  const data = error.response?.data
  return Object.values(data?.errors ?? data?.error ?? {}).flat()[0] || data?.message || fallback
}
