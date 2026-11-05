<script setup>
const route = useRoute()
const router = useRouter()
const keyword = ref('')
watch(() => route.query.q, (value) => { keyword.value = String(value ?? '') }, { immediate: true })
function search() {
  router.push({ path: '/buyer/filter', query: { q: keyword.value.trim() || undefined, category: route.query.category } })
}
</script>

<template>
  <header class="es-header">
    <div class="es-page">
      <RouterLink class="es-brand" to="/">
        e-shopee
      </RouterLink>
      <form class="es-search" role="search" @submit.prevent="search">
        <input v-model="keyword" aria-label="Tìm kiếm sản phẩm" placeholder="Bạn đang tìm sản phẩm gì?" maxlength="120">
        <button type="submit" class="es-primary">
          Tìm kiếm
        </button>
      </form>
      <nav class="es-nav" aria-label="Điều hướng mua sắm">
        <RouterLink to="/buyer/filter">
          Danh mục
        </RouterLink>
        <RouterLink to="/buyer/cart">
          Giỏ hàng
        </RouterLink>
        <RouterLink to="/buyer/account/orders">
          Đơn hàng
        </RouterLink>
        <RouterLink to="/buyer/account/dashboard">
          Tài khoản
        </RouterLink>
      </nav>
    </div>
  </header>
</template>
