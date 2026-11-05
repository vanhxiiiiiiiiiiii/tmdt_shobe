<route lang="yaml">
meta:
  layout: buyer/LBOrder
</route>

<script setup>
import OrderRequest from '~/services/order-request'
import { money, orderStatuses, requestError } from '~/utils/checkout'

const route = useRoute()
const router = useRouter()
const orders = ref([])
const loading = ref(true)
const error = ref('')

onMounted(async() => {
  if (!localStorage.getItem('token')) {
    await router.replace('/buyer/login')
    return
  }
  try {
    const ids = [...new Set(String(route.query.orders ?? '').split(',').filter(id => /^[1-9]\d*$/.test(id)))].slice(0, 100)
    if (!ids.length) {
      error.value = 'Không tìm thấy mã đơn hàng.'
      return
    }
    orders.value = await Promise.all(ids.map(async id => (await OrderRequest.getOrdersById(id)).data))
  }
  catch (failure) {
    error.value = requestError(failure, 'Không tải được xác nhận đơn hàng.')
  }
  finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="max-w-xl mx-auto p-5 my-6 bg-white dark:bg-gray-800 rounded-xl">
    <p v-if="loading" role="status">
      Đang kiểm tra đơn hàng…
    </p>
    <p v-else-if="error" role="alert">
      {{ error }}
    </p>
    <template v-else>
      <h1 class="font-semibold text-2xl mb-4">
        Đã đặt hàng
      </h1>
      <ul class="divide-y">
        <li v-for="order in orders" :key="order.id" class="py-3">
          <RouterLink :to="`/buyer/order/${order.id}`" class="font-semibold underline">
            Theo dõi {{ order.order_code || `#${order.id}` }}
          </RouterLink>
          <p>{{ orderStatuses[order.status_id] }} · {{ money(order.payment_total ?? order.grand_total) }}</p>
        </li>
      </ul>
    </template>
    <RouterLink to="/buyer/account/orders" class="underline">
      Lịch sử đơn hàng
    </RouterLink>
  </section>
</template>
