import AxiosInstance from './axios-instance.js'

class OrderRequest {
  getOrders(data) {
    const url = '/user/orders/'
    return AxiosInstance.get(url, data)
  }

  quoteCart(voucherCode, modelIds) {
    return AxiosInstance.post('/user/orders/quote', { voucher_code: voucherCode, model_ids: modelIds })
  }

  updateShopOrderStatus(id, statusId) {
    return AxiosInstance.put(`/shop/orders/${id}/status`, { status_id: statusId })
  }

  createOrders(data) {
    const url = '/user/orders'
    return AxiosInstance.post(url, data)
  }

  getOrdersById(id) {
    const url = `/user/orders/${id}`
    return AxiosInstance.get(url)
  }

  markOrdersCancelUser(id, data) {
    const url = `/user/orders/${id}/cancel`
    return AxiosInstance.post(url, data)
  }

  getOrdersStatus(data) {
    const url = '/shop/orders'
    return AxiosInstance.get(url, data)
  }

  markOrdersReadySeller(id, data) {
    const url = `/shop/orders/${id}/ready`
    return AxiosInstance.post(url, data)
  }

  markOrdersCancelSeller(id, data) {
    const url = `/shop/orders/${id}/cancel`
    return AxiosInstance.post(url, data)
  }

  markOrdersCancelAdmin(id, data) {
    const url = `/admin/orders/${id}/cancel`
    return AxiosInstance.post(url, data)
  }
}

export default new OrderRequest()
