<route lang="yaml">
meta:
  layout: buyer/account/LBDashboard
</route>

<script setup>
import OrderRequest from '~/services/order-request'
import { money, orderStatuses, requestError } from '~/utils/checkout'

const router = useRouter()
const status = ref('')
const page = ref(1)
const result = ref(null)
const loading = ref(true)
const error = ref('')

onMounted(() => {
  if (!localStorage.getItem('token')) {
    router.replace('/buyer/login')
    return
  }
  watch([status, page], async(_, __, onCleanup) => {
    let active = true
    onCleanup(() => { active = false })
    loading.value = true
    error.value = ''
    try {
      const { data } = await OrderRequest.getOrders({ params: { limit: 10, page: page.value, status_id: status.value || undefined } })
      if (active)
        result.value = data
    }
    catch (failure) {
      if (active)
        error.value = requestError(failure, 'Không tải được lịch sử đơn hàng.')
    }
    finally {
      if (active)
        loading.value = false
    }
  }, { immediate: true })
})
const { t } = useI18n()
</script>

<template>
  <div class="orders-container border-1 border-solid border-light-700 rounded-lg p-5 bg-[#EBF6FC] dark:bg-cool-gray-800">
    <div class="border-b-1 border-b-solid border-b-light-700 py-3 font-medium flex items-center gap-1">
      <IBOrder />
      <h3 class="text-2xl">
        {{ t('account.order-title') }}
      </h3>
    </div>

    <label>Trạng thái <select v-model="status" @change="page = 1"><option value="">Tất cả</option><option v-for="(label, id) in orderStatuses" :key="id" :value="id">{{ label }}</option></select></label>
    <p v-if="loading || error" role="status">{{ error || 'Đang tải đơn hàng…' }}</p>
    <div class="orders-table text-center bg-[#A7F3D0] rounded-xl mt-5">
      <table class="w-full">
        <thead class="text-[#05967A]">
          <tr>
            <th>{{ t('account.order-no') }}</th>
            <th>{{ t('account.order-name') }}</th>
            <th>{{ t('account.order-total') }}</th>
            <th>{{ t('account.order-action') }}</th>
          </tr>
        </thead>

        <tbody class="text-sm text-gray-500 bg-white dark:bg-blue-gray-600 dark:text-gray-200">
          <tr v-for="(item, index) in (result?.data ?? [])" :key="item.id">
            <td>{{ index+1 }}</td>
            <td>{{ item.items?.map(row => row.product_name).join(", ") || item.name }}</td>
            <td class="text-red-500 font-medium">
              {{ money(item.payment_total ?? item.grand_total) }}
            </td>
            <td>
              <RouterLink :to="`/buyer/order/${item.id}`" class="text-gray-700 bg-gradient-to-r from-teal-200 to-lime-200 hover:(bg-gradient-to-l from-teal-200 to-lime-200) focus:(ring-2 focus:ring-lime-200) dark:focus:ring-teal-700 font-medium rounded-lg text-sm px-5 py-1.75 text-center mr-2 mb-2 flex items-center gap-1">
                <IBTrackOrder /> Theo dõi
              </RouterLink>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <CPagination v-if="result?.last_page > 1" :index="page" @on-prev="page = Math.max(1, page - 1)" @on-next="page = Math.min(result.last_page, page + 1)" />
  </div>
</template>

<style scoped>
thead > tr, tbody > tr:not(:last-child){
  border-bottom: 1px solid #e9e9e9;
}
th, td{
  border-right: 1px solid #e9e9e9;
  padding: 0.75rem;
}
</style>
