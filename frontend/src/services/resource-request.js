import AxiosInstance from './axios-instance'

class ResourceRequest {
  createResourcesImages(data) {
    const url = '/resource/images'
    return AxiosInstance.post(url, data, { headers: { 'Content-Type': 'multipart/form-data' } })
  }
}
export default new ResourceRequest()
