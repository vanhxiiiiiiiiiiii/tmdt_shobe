<route lang="yaml">
meta:
  layout: buyer/LBOrder
</route>

<script setup>
import OrderRequest from '~/services/order-request'
import { addressLine, localDate, money, orderStatuses, paymentStatuses, requestError } from '~/utils/checkout'
import { fallbackProductImage, getResources } from '~/utils/resources'

const route = useRoute()
const router = useRouter()
const order = ref(null)
const loading = ref(true)
const error = ref('')
const cancelling = ref(false)
const confirmCancel = ref(false)
const recipient = computed(() => order.value?.recipient_snapshot ?? order.value?.received_address)

function items(record) {
  return record.items?.length
    ? record.items
    : [{
      id: record.id,
      product: record.product,
      product_name: record.name,
      quantity: record.quantity,
      line_total: record.total,
    }]
}

async function cancel() {
  const id = order.value.id
  cancelling.value = true
  error.value = ''
  try {
    await OrderRequest.markOrdersCancelUser(id, {})
    if (String(route.params.order) === String(id))
      order.value = (await OrderRequest.getOrdersById(id)).data
  }
  catch (failure) {
    error.value = requestError(failure, 'Không thể hủy đơn hàng.')
  }
  finally {
    cancelling.value = false
    confirmCancel.value = false
  }
}

onMounted(() => {
  if (!localStorage.getItem('token')) {
    router.replace('/buyer/login')
    return
  }
  watch(() => route.params.order, async(id, _, onCleanup) => {
    let active = true
    onCleanup(() => { active = false })
    order.value = null
    confirmCancel.value = false
    loading.value = true
    error.value = ''
    try {
      const { data } = await OrderRequest.getOrdersById(id)
      if (active)
        order.value = data
    }
    catch (failure) {
      if (active)
        error.value = failure.response?.status === 404 ? 'Không tìm thấy đơn hàng.' : requestError(failure, 'Không tải được đơn hàng.')
    }
    finally {
      if (active)
        loading.value = false
    }
  }, { immediate: true })
})
const payget = computed(() => order.value)
const orderImg = computed(() => getResources(order.value?.items?.[0]?.product?.images?.[0] ?? order.value?.product?.images?.[0]))
const statusPercent = id => ({ 1: 10, 2: 29, 3: 51, 4: 75, 5: 0, 6: 75 })[id] ?? 0
const onVisitProduct = id => { if (id) router.push(`/product/${id}`) }
const { t } = useI18n()
</script>

<template>
  <p v-if="loading || error" role="status" class="p-5">{{ error || 'Đang tải đơn hàng…' }}</p>
  <div v-if="payget" class="order_detail_view flex p-10 bg-[#CFDBEA] dark:bg-gray-900">
    <div class="container bg-white dark:bg-gray-800 rounded-2xl shadow-md shadow-gray-300 dark:shadow-gray-600 w-300 max-w-300 grid grid-cols-5 m-auto p-10 h-max">
      <div class="col-span-2">
        <div class="text-2xl text-red-400 font-medium text-center">
          <h1>Theo dõi đơn hàng</h1>
        </div>
        <div class="hero-img-container flex justify-center items-center relative py-5">
          <div class="triangle1" />
          <div class="arc inline-block border-1 border-solid border-[#000] rounded-full border-t-transparent border-r-transparent border-b-transparent opacity-40 absolute min-w-50 min-h-50 left-0 top-7" style="transform: rotate(-15deg);" />
          <div class="pattern" />
          <img src="https://drive.google.com/uc?id=15iXUI6DkRr5Zcp0yH5uF2U47ycr-WzUY" class="hero-img w-2/3">
        </div>
      </div>

      <div class="col-span-3 grid">
        <div class="order-status-container flex justify-between items-start relative text-sm font-medium">
          <div class="status-item first before:(content-\00a0 h-1 bg-[#5858EF] absolute top-3 left-0 rounded-lg)" :style="{ '--progress': statusPercent(payget.status_id) + '%', opacity: statusPercent(payget.status_id) >= 10 ? 1 : 0.6 }">
            <div class="status-circle" />
            <p>Chờ xác nhận</p>
          </div>
          <div class="status-item second before:(content-\00a0 h-1 w-full top-3 left-0 opacity-20 absolute bg-[#5858EF] rounded-lg)" :style="[statusPercent(payget.status_id)>= 29 ? {'opacity':'1'} : {'opacity': '0.7'}]">
            <div class="status-circle" />
            <p>Đang chuẩn bị</p>
          </div>
          <div class="status-item" :style="[statusPercent(payget.status_id)>= 51 ? {'opacity':'1'} : {'opacity': '0.6'}]">
            <div class="status-circle" />
            <p>Đang giao</p>
          </div>
          <div class="status-item" :style="[statusPercent(payget.status_id)>= 75 ? {'opacity':'1'} : {'opacity': '0.6'}]">
            <div class="status-circle" />
            <p>Đã giao</p>
          </div>
          <div class="status-item" :style="[Number(payget.status_id) === 5 ? {'opacity':'1'} : {'opacity': '0.6'}]">
            <div class="status-circle" />
            <p>Đã hủy</p>
          </div>
        </div>

        <div class="mt-10">
          <div class="flex justify-between gap-2">
            <div class="grid">
              <div class="flex justify-between">
                <div>
                  <p class="text-green-500 text-sm">
                    {{ t('account.order-date') }}: {{ localDate(payget.created_at) }}
                  </p>
                  <p class="text-gray-400 text-xs">
                    Mã đơn: {{ payget.order_code || payget.id }}
                  </p>
                  <p class="text-gray-400 text-xs">
                    Product ID: {{ payget.product_id }}
                  </p>
                </div>
                <div class="text-right">
                  <p class="text-red-500 font-medium">
                    {{ t('account.order-total') }}: {{ money(payget.payment_total ?? payget.grand_total) }}
                  </p>
                  <p class="text-xs">
                    Quantity: {{ payget.quantity }}
                  </p>
                  <p class="text-xs">
                    {{ orderStatuses[payget.status_id] }}
                  </p>
                </div>
              </div>
              <div class="font-medium text-base">
                <h5 v-for="item in items(payget)" :key="item.id">{{ item.product_name }} × {{ item.quantity }}</h5>
                <p v-for="payment in payget.payments" :key="payment.id">{{ paymentStatuses[payment.status] ?? payment.status }}</p>
                <button v-if="payget.status_id === 1 && !confirmCancel" type="button" @click="confirmCancel = true">Hủy đơn hàng</button>
                <button v-if="confirmCancel" type="button" :disabled="cancelling" @click="cancel">Xác nhận hủy</button>
              </div>
            </div>
            <div>
              <img :src="orderImg" @error="fallbackProductImage" alt="product_order_img" class="max-w-45 max-h-45 rounded-xl cursor-pointer border-1" @click="onVisitProduct(payget.product_id, payget.shop_id)">
            </div>
          </div>
          <div class="flex justify-between">
            <div>
              <p class="text-xs text-red-300">
                Address
              </p>
              <div class="text-sm text-gray-500">
                <p>
                  Name: {{ recipient?.full_name }}
                </p>
                <p>Phone: {{ recipient?.phone }}</p>
                <p>Address: {{ recipient?.address }}, {{ recipient?.town }}, {{ recipient?.city }}, {{ recipient?.state }}</p>
              </div>
            </div>
            <div class="flex justify-center items-end">
              <button type="button" class="text-white bg-gradient-to-r from-purple-500 via-purple-600 to-purple-700 hover:bg-gradient-to-br focus:(ring-2 ring-purple-300) dark:focus:ring-purple-800 font-medium rounded-lg text-sm px-5 py-2 text-center mr-2 flex items-center gap-1" @click="onVisitProduct(payget.product_id, payget.shop_id)">
                <IBOrderArrowRight /> Xem sản phẩm
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.status-item.first::before { width: var(--progress); }
@import url('https://fonts.googleapis.com/css?family=Roboto:400,500,700');
.order_detail_view {
    font-family: 'Roboto', sans-serif;
    font-weight: 400;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

.hero-img-container::before {
    height: 1.5rem;
    width: 1.5rem;
    background-color: #0268EE;
    position: absolute;
    top: 5%;
    right: 35%;
    content: '';
    border-radius: 50%;
}

.pattern {
  width: 50px;
  height: 50px;
  background-image: url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0naHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmcnIHdpZHRoPScxMCcgaGVpZ2h0PScxMCc+CiAgPHJlY3Qgd2lkdGg9JzEwJyBoZWlnaHQ9JzEwJyBmaWxsPSd3aGl0ZScgLz4KICA8Y2lyY2xlIGN4PSc0JyBjeT0nNCcgcj0nNCcgZmlsbD0nYmxhY2snLz4KPC9zdmc+");
  opacity: 0.1;
  position: absolute;
  right: 2rem;
  top: 2rem;
}

.triangle1 {
  width: 0;
  height: 0;
  border-style: solid;
  border-width: 0 10px 20px 10px;
  border-color: transparent transparent #767EEF transparent;
  position: absolute;
  top: 3.8rem;
  left: 0.7rem;
  transform: rotate(36deg);
}
.status-item {
  display:flex;
  flex-direction:column;
  justify-content: flex-start;
  align-items: end;
  height: fit-content;
}

.status-circle {
  height: 1.5rem;
  width: 1.5rem;
  background-color: #665CF5;
  border-radius: 50%;
  border: 5px solid white;
  box-shadow: 0 10px 20px rgba(0,0,0,0.19), 0 6px 6px rgba(0,0,0,0.23);
  z-index: 2;
  transition: all 0.2s;
  cursor: pointer;
  margin-bottom: 0.5rem;
}

.status-circle:hover {
  transform: scale(1.2);
  box-shadow: 0 14px 28px rgba(0,0,0,0.25), 0 10px 10px rgba(0,0,0,0.22);
}

</style>
