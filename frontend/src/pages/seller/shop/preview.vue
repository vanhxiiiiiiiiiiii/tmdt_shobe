<route lang="yaml">
meta:
  layout: seller/LSDashBoard
</route>

<script setup>
import ShopRequest from '~/services/shop-request'

const router = useRouter()
const error = ref(false)
onMounted(async() => {
  if (!localStorage.getItem('token')) {
    await router.replace('/buyer/login')
    return
  }
  try {
    const { data } = await ShopRequest.getShops()
    await router.replace(`/shop/${data.id}`)
  }
  catch {
    error.value = true
  }
})
</script>

<template>
  <p v-if="error" role="alert">
    Không tải được cửa hàng.
  </p>
  <p v-else role="status">
    Đang mở cửa hàng…
  </p>
</template>
