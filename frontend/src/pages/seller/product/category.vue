<route lang="yaml">
meta:
  layout: seller/LSDashBoard
</route>

<script setup>
const router = useRouter()
onMounted(() => router.replace('/seller/product/new'))
</script>

<template>
  <RouterLink to="/seller/product/new">
    Chọn danh mục và tạo sản phẩm
  </RouterLink>
</template>
