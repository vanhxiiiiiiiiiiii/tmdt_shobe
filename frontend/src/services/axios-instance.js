import queryString from 'query-string'
import axios from 'axios'
import { toast } from '../stores/toast'

const AxiosInstance = axios.create({
  baseURL: `${import.meta.env.VITE_BASE_DOMAIN}/api/v2`,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    // 'Authorization': localStorage.getItem('token'),
  },
  paramsSerializer: params => queryString.stringify(params),
  responseEncoding: 'utf8',
})
AxiosInstance.interceptors.request.use(
  async(request) => {
    const token = typeof window === 'undefined' ? null : localStorage.getItem('token')
    request.headers.Authorization = token ? `Bearer ${token}` : ''
    return request
  },
  (error) => {
    const message = error?.response?.data?.message || error?.message || 'Unable to send request'
    toast().updateToast('error', message, true)
    error.__toastShown = true
    return Promise.reject(error)
  },
)
AxiosInstance.interceptors.response.use(
  (response) => {
    if (response && response.data)
      return response.data

    return response
  },
  (error) => {
    const validationMessage = Object.values(error?.response?.data?.errors || error?.response?.data?.error || {})
      .flat()
      .find(Boolean)
    const message = error?.response?.data?.message
      || validationMessage
      || error?.message
      || 'An unexpected error occurred'

    toast().updateToast('error', `Error: ${message}. Try again!`, true)
    error.__toastShown = true
    return Promise.reject(error)
  },
)

export default AxiosInstance
