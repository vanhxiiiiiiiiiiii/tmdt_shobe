<route lang="yaml">
meta:
  layout: seller/LSDashBoard
</route>

<script setup>
import { useRouter } from 'vue-router'
import { money } from '~/utils/checkout'
import ProductRequest from '~/services/product-request'

const router = useRouter()
const statuses = { 'all': 'Tất cả', 'online': 'Đang bán', 'out-of-stock': 'Hết hàng', 'hidden': 'Đã ẩn' }
const rows = ref([])
const page = ref(1)
const total = ref(0)
const lastPage = ref(1)
const status = ref('all')
const keyword = ref('')
const search = ref('')
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const editing = ref(null)
let requestId = 0

async function load() {
  const id = ++requestId
  error.value = ''
  loading.value = true
  try {
    const { data } = await ProductRequest.getShopProducts({
      params: {
        limit: 10, page: page.value, status: status.value, keyword: search.value || undefined,
      },
    })
    if (id !== requestId)
      return
    rows.value = data.data
    total.value = data.total
    lastPage.value = data.last_page
    if (page.value > lastPage.value)
      page.value = lastPage.value
  }
  catch (failure) {
    if (id === requestId)
      error.value = failure.response?.status === 403 ? 'Tài khoản này chưa có cửa hàng hoặc không có quyền truy cập.' : 'Không tải được sản phẩm.'
  }
  finally {
    if (id === requestId)
      loading.value = false
  }
}

onMounted(() => {
  if (!localStorage.getItem('token'))
    router.replace('/buyer/login')
  else
    watch([page, status, search], load, { immediate: true })
})

function filter(next = status.value) {
  page.value = 1
  status.value = next
  search.value = keyword.value.trim()
}

function edit(row) {
  editing.value = {
    id: row.id,
    name: row.name,
    description: row.description ?? '',
    weight: Number(row.weight),
    is_published: row.status_id === 1,
    models: row.models.map(model => ({ id: model.id, sku: model.sku, price: Number(model.price) })),
  }
}

async function save() {
  saving.value = true
  try {
    const { id, models, ...fields } = editing.value
    await ProductRequest.updateProductListing(id, { ...fields, model_prices: models.map(({ id, price }) => ({ id, price })) })
    editing.value = null
    await load()
  }
  catch {
    // The API interceptor displays the validation error.
  }
  finally {
    saving.value = false
  }
}

async function change(row, remove = false) {
  if (remove && !window.confirm(`Xóa sản phẩm “${row.name}”?`))
    return
  saving.value = true
  try {
    if (remove)
      await ProductRequest.deleteProductsById(row.id)
    else
      await ProductRequest.updateProductListing(row.id, { is_published: row.status_id !== 1 })
    await load()
  }
  catch {
    // The API interceptor displays the response error.
  }
  finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="seller-prods-container max-w-300 w-300 p-2">
    <form class="top-container grid gap-3 p-5 bg-white shadow-sm shadow-gray-500/50 rounded-xl" @submit.prevent="filter()">
      <label>Tìm sản phẩm <input v-model="keyword" placeholder="Tên, mã hoặc SKU"></label>
      <button type="submit" class="text-white py-1 px-5 rounded-md bg-[#E54A2B]">Tìm</button>
    </form>

    <div class="bottom-container p-5 my-5 bg-white shadow-sm shadow-gray-500/50 rounded-2xl divide-light-800 divide-y devide-solid">
      <div class="tabs7 mb-5 flex bg-[#eee] p-1.25 rounded-lg">
        <button v-for="(label, key) in statuses" :key="key" type="button" class="tab-item focus:bg-white" :class="{ active: status === key }" @click="filter(key)">{{ label }}</button>
      </div>
      <div class="grid grid-cols-5 py-3 my-1">
        <div class="col-span-2">
          <h3 class="font-semibold text-lg">
            {{ total }} sản phẩm
          </h3>
        </div>
        <!-- <li v-for="item in Names" v-if="!!item.FullName> -->
        <div class="col-span-3 flex justify-end items-end gap-2">
          <RouterLink to="/seller/product/new" class="text-white py-0.75 px-3 rounded-md bg-[#E54A2B]">Thêm sản phẩm</RouterLink>
        </div>
      </div>
      <p v-if="loading || error" role="status">{{ error || 'Đang tải…' }}</p>
      <div v-else class="all-table border-1 border-gray-300 border-solid rounded-md my-1">
        <div class="all-head-table grid grid-cols-16 bg-light-400 p-3">
          <div class="opacity-70 flex justify-center items-center">
            
          </div>
          <div class="col-span-3 opacity-70">
            <h2>Tên sản phẩm</h2>
          </div>
          <div class="col-span-2 opacity-70">
            <h2>SKU</h2>
          </div>
          <div class="col-span-2 opacity-70">
            <h2>Danh mục</h2>
          </div>
          <div class="col-span-2 flex items-center opacity-70">
            <h2>Giá <i class="fas fa-sort" /></h2>
          </div>
          <div class="col-span-2 flex items-center opacity-70">
            <h2>Tồn kho <i class="fas fa-sort" /></h2>
          </div>
          <div class="col-span-2 flex items-center opacity-70">
            <h2>Đã bán <i class="fas fa-sort" /></h2>
          </div>
          <div class="col-span-2 opacity-70">
            <h2>Thao tác</h2>
          </div>
        </div>

        <div class="all-body-table overflow-y-scroll h-100">
          <div v-for="row in rows" :key="row.id" class="all-body-item grid grid-cols-16 p-3">
            <div class="opacity-70 flex justify-center items-center">
              
            </div>
            <div class="col-span-3 opacity-70">
              <h2>{{ row.name }}</h2>
            </div>
            <div class="col-span-2 opacity-70">
              <h2>{{ row.models.map(model => model.sku).filter(Boolean).join(', ') || '—' }}</h2>
            </div>
            <div class="col-span-2 opacity-70">
              <h2>{{ row.categories.map(category => category.name).join(', ') || '—' }}</h2>
            </div>
            <div class="col-span-2 flex items-center opacity-70">
              <h2>{{ row.models.map(model => money(model.price)).join(' / ') || '—' }}</h2>
            </div>
            <div class="col-span-2 flex items-center opacity-70">
              <h2>{{ row.models.reduce((sum, model) => sum + Number(model.stock), 0) }}</h2>
            </div>
            <div class="col-span-2 flex items-center opacity-70">
              <h2>{{ row.sold }}</h2>
            </div>
            <div class="col-span-2 opacity-70">
              <button type="button" class="btn" :disabled="saving" @click="edit(row)">Sửa</button>
              <button type="button" :disabled="saving" @click="change(row)">{{ row.status_id === 1 ? 'Ẩn' : 'Đăng bán' }}</button>
              <button type="button" :disabled="saving" @click="change(row, true)">Xóa</button>
            </div>
          </div>
        </div>
      </div>
      <CPagination v-if="lastPage > 1" :index="page" @on-prev="page = Math.max(1, page - 1)" @on-next="page = Math.min(lastPage, page + 1)" />
      <form v-if="editing" class="grid gap-3 border rounded p-4 mt-4" @submit.prevent="save">
        <h2 class="font-semibold">
          Chỉnh sửa sản phẩm
        </h2>
        <label>Tên <input v-model="editing.name" required maxlength="255" class="border rounded p-2 w-full dark:bg-gray-700"></label>
        <label>Mô tả <textarea v-model="editing.description" class="border rounded p-2 w-full dark:bg-gray-700" /></label>
        <label>Khối lượng (g) <input v-model.number="editing.weight" type="number" min="0.1" max="999999" step="any" required class="border rounded p-2 dark:bg-gray-700"></label>
        <label v-for="model in editing.models" :key="model.id">Giá ({{ model.sku || model.id }}) <input v-model.number="model.price" type="number" min="0.5" step="0.01" required class="border rounded p-2 dark:bg-gray-700"></label>
        <label><input v-model="editing.is_published" type="checkbox"> Đang bán</label>
        <div class="flex gap-3">
          <button type="submit" class="bg-red-600 text-white rounded px-4 py-2" :disabled="saving">
            Lưu
          </button>
          <button type="button" :disabled="saving" @click="editing = null">
            Hủy
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
select, input{
  border: 1px solid rgb(210, 210, 210);
  border-radius: 5px;
  outline: none;
  padding: 0.2rem 0.5rem;
}
select{
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
}

.tabs7 .tab-item.active {
  color: red;
  background-color: white;
}
.tabs7 .tab-item {
  white-space: nowrap;
  padding: 10px;
  cursor: pointer;
  font-weight: 500;
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: inherit;
  transition: 0.2s linear;
}
.toggle {
  border: none;
  outline: none;
  padding: 6px 10px;
  cursor: pointer;
  border-radius: 3px;
}

.toggle:hover {
  background-color: #ddd;
}

.toggle.active {
  background-color: #ddd;
  color: red;
}
::-webkit-scrollbar-thumb {
  background: #ddd;
}
</style>
