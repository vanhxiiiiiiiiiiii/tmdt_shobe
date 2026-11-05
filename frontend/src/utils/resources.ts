export const productPlaceholder = '/img/product/placeholder.svg'

export function getResources(resource?: string) {
  if (!resource)
    return productPlaceholder
  if (/^https?:\/\//i.test(resource))
    return resource
  if (/^(?:demo-[a-z0-9-]+|[a-f0-9]{32})(?:_tn)?$/.test(resource))
    return `${import.meta.env.VITE_BASE_DOMAIN?.replace(/\/$/, '') || ''}/resources/images/${encodeURIComponent(resource)}`
  return productPlaceholder
}

export function fallbackProductImage(event: Event) {
  const image = event.currentTarget as HTMLImageElement
  if (image.getAttribute('src') !== productPlaceholder)
    image.src = productPlaceholder
}
