import { toast } from '~/stores/toast'
import { useLoading } from '~/stores/loading'

export const handleError = (error: any) => {
  const loading = useLoading()
  const useToast = toast()
  const validationMessage = Object.values(error?.response?.data?.errors || {})
    .flat()
    .find(Boolean)
  const message = error?.response?.data?.message
    || validationMessage
    || error?.message
    || 'An unexpected error occurred'

  if (!error?.__toastShown)
    useToast.updateToast('error', `${message}. Try again!`, true)

  loading.isLoading = false
  return (`Error message: ${message}`)
}
