<route lang="yaml">
meta:
  layout: seller/LSDashBoard
</route>

<script setup>
import OrderRequest from '~/services/order-request'
import { localDate, money, orderStatuses, paymentStatuses, requestError } from '~/utils/checkout'

const router = useRouter()
const page = ref(1)
const status = ref('')
const data = ref(null)
const loading = ref(true)
const busy = ref(false)
const error = ref('')
const confirming = ref(null)
const actions = {
  1: [[2, 'Xác nhận đơn'], [5, 'Hủy đơn']],
  2: [[3, 'Bắt đầu giao'], [5, 'Hủy đơn']],
  3: [[4, 'Xác nhận đã giao và thu COD'], [5, 'Hủy đơn']],
  4: [[6, 'Xác nhận đã nhận hàng trả']],
}
let sequence = 0

async function load() {
  const id = ++sequence
  loading.value = true
  error.value = ''
  try {
    const response = await OrderRequest.getOrdersStatus({ params: { limit: 10, page: page.value, status_id: status.value || undefined } })
    if (id === sequence) {
      data.value = response.data
      if (page.value > response.data.last_page)
        page.value = response.data.last_page
    }
  }
  catch (failure) {
    if (id === sequence)
      error.value = requestError(failure, 'Không tải được đơn hàng của cửa hàng.')
  }
  finally {
    if (id === sequence)
      loading.value = false
  }
}

onMounted(() => {
  if (!localStorage.getItem('token')) {
    router.replace('/buyer/login')
    return
  }
  watch([page, status], load, { immediate: true })
})

async function update() {
  if (!confirming.value || busy.value)
    return
  const { orderId, next } = confirming.value
  busy.value = true
  error.value = ''
  try {
    await OrderRequest.updateShopOrderStatus(orderId, next)
    confirming.value = null
    await load()
  }
  catch (failure) {
    error.value = requestError(failure, 'Không cập nhật được trạng thái.')
  }
  finally {
    busy.value = false
  }
}
</script>

<template>
  <section class="bg-white dark:bg-gray-800 max-w-300 w-full p-5 rounded-xl">
    <h1 class="text-xl font-semibold mb-4">
      Đơn hàng của cửa hàng
    </h1>
    <label>Trạng thái
      <select v-model="status" class="border rounded p-2 dark:bg-gray-700" :disabled="busy" @change="page = 1; confirming = null">
        <option value="">Tất cả</option>
        <option v-for="(label, id) in orderStatuses" :key="id" :value="id">{{ label }}</option>
      </select>
    </label>
    <p v-if="error" class="text-red-700 mt-2" role="alert">
      {{ error }}
    </p>
    <p v-if="loading" class="mt-2" role="status">
      Đang tải đơn hàng…
    </p>
    <template v-else-if="data">
      <p class="mt-2">
        {{ data.total }} đơn hàng
      </p>
      <div v-if="!data.data.length" class="mt-2">
        Chưa có đơn hàng phù hợp.
      </div>
      <ul v-else class="divide-y">
        <li v-for="order in data.data" :key="order.id" class="py-4 grid gap-1">
          <strong>{{ order.order_code || `#${order.id}` }} · {{ orderStatuses[order.status_id] }}</strong>
          <p>{{ order.items?.map(item => item.product_name).join(', ') || order.name }}</p>
          <p>{{ money(order.payment_total ?? order.grand_total) }} · {{ localDate(order.created_at) }}</p>
          <p v-for="payment in order.payments" :key="payment.id">
            {{ payment.method === 'cod' ? 'COD' : payment.method }} · {{ paymentStatuses[payment.status] ?? payment.status }}
          </p>
          <div v-if="confirming?.orderId === order.id" class="flex flex-wrap gap-3 mt-2">
            <span>{{ confirming.label }}?</span>
            <button type="button" class="underline" :disabled="busy" @click="update">
              Đồng ý
            </button>
            <button type="button" :disabled="busy" @click="confirming = null">
              Quay lại
            </button>
          </div>
          <div v-else class="flex flex-wrap gap-3 mt-2">
            <button v-for="[next, label] in actions[order.status_id]" :key="next" type="button" class="underline" :disabled="busy" @click="confirming = { orderId: order.id, next, label }">
              {{ label }}
            </button>
          </div>
        </li>
      </ul>
      <div v-if="data.last_page > 1" class="flex gap-3 mt-3">
        <button type="button" :disabled="page <= 1 || busy" @click="page--; confirming = null">
          Trang trước
        </button>
        <span>{{ page }} / {{ data.last_page }}</span>
        <button type="button" :disabled="page >= data.last_page || busy" @click="page++; confirming = null">
          Trang sau
        </button>
      </div>
    </template>
  </section>
</template>
