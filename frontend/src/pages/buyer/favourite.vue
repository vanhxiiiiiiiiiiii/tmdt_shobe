<route lang="yaml">
meta:
  layout: buyer/LBFavourite
</route>

<script setup>
import AxiosInstance from '~/services/axios-instance'

const router = useRouter()
const products = ref([])
const loading = ref(true)
const error = ref(false)
onMounted(async() => {
  if (!localStorage.getItem('token')) {
    await router.replace('/buyer/login')
    return
  }
  try {
    const { data } = await AxiosInstance.get('/user/favorites')
    products.value = data.map(favorite => favorite.product).filter(product => product?.status_id === 1)
  }
  catch {
    error.value = true
  }
  finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="max-w-300 mx-auto px-4 py-8">
    <h1 class="font-semibold text-2xl mb-4">
      Sản phẩm yêu thích
    </h1>
    <p v-if="loading" role="status">
      Đang tải…
    </p>
    <p v-else-if="error" role="alert">
      Không tải được sản phẩm yêu thích.
    </p>
    <p v-else-if="!products.length">
      Chưa có sản phẩm yêu thích.
    </p>
    <div v-else class="grid grid-cols-2 md:grid-cols-3 gap-4">
      <div v-for="product in products" :key="product.id" class="rounded-lg shadow-md">
        <CProductCardGrid :card="product" />
      </div>
    </div>
  </section>
</template>
