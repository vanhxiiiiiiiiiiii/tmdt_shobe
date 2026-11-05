<route lang="yaml">
meta:
  layout: seller/LSDashBoard
</route>

<script setup>
import { useRoute, useRouter } from 'vue-router'
import ProductRequest from '~/services/product-request'
import ResourceRequest from '~/services/resource-request'
import { useProduct } from '~/stores/product'

const route = useRoute()
const router = useRouter()
const product = useProduct()
const categories = ref([])
const category = ref('')
const attributes = ref([])
const attributeValues = ref({})
const files = ref([])
const saving = ref(false)
const loading = ref(true)
const loadingAttributes = ref(false)
const error = ref('')
const attributeError = ref('')
const fields = reactive({
  name: product.productName,
  description: '',
  weight: '',
  sku: '',
  price: '',
  stock: 0,
  is_published: true,
})

function leaves(nodes, parents = [], names = []) {
  return nodes.flatMap((node) => {
    const path = [...parents, node.id]
    const label = [...names, node.name]
    return node.children?.length ? leaves(node.children, path, label) : [{ id: node.id, path, label: label.join(' / ') }]
  })
}

onMounted(async() => {
  if (!localStorage.getItem('token')) {
    await router.replace('/buyer/login')
    return
  }
  try {
    const { data } = await ProductRequest.getCategoriesChildrenById()
    categories.value = leaves(data)
    if (categories.value.some(item => String(item.id) === route.query.category))
      category.value = String(route.query.category)
  }
  catch {
    error.value = 'Không tải được danh mục.'
  }
  finally {
    loading.value = false
  }
})

const selected = computed(() => categories.value.find(item => String(item.id) === category.value))
watch(selected, async(item, _, onCleanup) => {
  let active = true
  onCleanup(() => { active = false })
  attributes.value = []
  attributeValues.value = {}
  attributeError.value = ''
  loadingAttributes.value = Boolean(item)
  if (!item)
    return
  try {
    const { data } = await ProductRequest.getAllCategoriesAttributesSeller({ params: { 'category_ids[]': item.path } })
    if (active) {
      attributes.value = data
      attributeValues.value = Object.fromEntries(data.map(attribute => [attribute.id, { value: '', unit: attribute.units?.[0] ?? null }]))
    }
  }
  catch {
    if (active)
      attributeError.value = 'Không tải được thuộc tính danh mục.'
  }
  finally {
    if (active)
      loadingAttributes.value = false
  }
})

async function save() {
  if (saving.value || !selected.value || loadingAttributes.value || attributeError.value)
    return
  if (!files.value.length || files.value.length > 9) {
    error.value = 'Chọn từ 1 đến 9 ảnh sản phẩm.'
    return
  }

  if (files.value.some(file => file.size > 1024 * 1024)) {
    error.value = 'Mỗi ảnh phải nhỏ hơn hoặc bằng 1 MB.'
    return
  }
  saving.value = true
  error.value = ''
  try {
    const images = []
    for (const file of files.value) {
      const form = new FormData()
      form.append('image', file)
      form.append('ratio', '0')
      const { data } = await ResourceRequest.createResourcesImages(form)
      images.push(data.id)
    }

    await ProductRequest.createProducts({
      brand_id: 1,
      name: fields.name.trim(),
      description: fields.description.trim() || undefined,
      weight: fields.weight,
      is_published: fields.is_published,
      category_path: selected.value.path,
      images,
      variations: [],
      models: [{ sku: fields.sku.trim() || null, price: fields.price, stock: fields.stock, variation_index: [] }],
      attributes: attributes.value.flatMap(({ id }) => {
        const entry = attributeValues.value[id]
        return entry.value.trim() ? [{ attribute_id: id, value: entry.value.trim(), unit: entry.unit }] : []
      }),
    })
    product.productName = ''
    await router.push('/seller/product/all')
  }
  catch (failure) {
    const data = failure.response?.data
    error.value = Object.values(data?.errors ?? data?.error ?? {}).flat()[0] || data?.message || 'Không thể lưu sản phẩm.'
  }
  finally {
    saving.value = false
  }
}
</script>

<template>
  <section class="max-w-254 w-full p-5 bg-white dark:bg-gray-800 rounded-xl">
    <h1 class="text-2xl font-semibold mb-4">
      Thêm sản phẩm
    </h1>
    <p v-if="loading" role="status">
      Đang tải danh mục…
    </p>
    <p v-if="error || attributeError" role="alert" class="text-red-700 mb-3">
      {{ error || attributeError }}
    </p>
    <form v-if="!loading" class="grid gap-4" @submit.prevent="save">
      <fieldset :disabled="saving" class="grid gap-4">
        <label>Tên sản phẩm <input v-model="fields.name" minlength="10" maxlength="255" required class="block border rounded p-2 w-full dark:bg-gray-700"></label>
        <label>Mô tả (ít nhất 50 ký tự nếu có) <textarea v-model="fields.description" minlength="50" class="block border rounded p-2 w-full dark:bg-gray-700" /></label>
        <label>Danh mục
          <select v-model="category" required class="block border rounded p-2 max-w-full dark:bg-gray-700">
            <option value="" disabled>Chọn danh mục</option>
            <option v-for="item in categories" :key="item.id" :value="String(item.id)">{{ item.label }}</option>
          </select>
        </label>
        <p v-if="loadingAttributes" role="status">
          Đang tải thuộc tính…
        </p>
        <div v-for="attribute in attributes" :key="attribute.id" class="flex gap-3">
          <label>{{ attribute.name }} <input v-model="attributeValues[attribute.id].value" :required="Boolean(attribute.is_required)" class="border rounded p-2 dark:bg-gray-700"></label>
          <label v-if="attribute.units?.length">Đơn vị <select v-model="attributeValues[attribute.id].unit" class="border rounded p-2 dark:bg-gray-700">
            <option v-for="unit in attribute.units" :key="unit">{{ unit }}</option>
          </select></label>
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
          <label>SKU <input v-model="fields.sku" class="block border rounded p-2 w-full dark:bg-gray-700"></label>
          <label>Giá <input v-model.number="fields.price" type="number" min="0.5" max="999999.99" step="0.01" required class="block border rounded p-2 w-full dark:bg-gray-700"></label>
          <label>Tồn kho <input v-model.number="fields.stock" type="number" min="0" step="1" required class="block border rounded p-2 w-full dark:bg-gray-700"></label>
          <label>Khối lượng (g) <input v-model.number="fields.weight" type="number" min="0.1" max="999999" step="any" required class="block border rounded p-2 w-full dark:bg-gray-700"></label>
        </div>
        <label>Ảnh (JPG/PNG, tối đa 1 MB mỗi ảnh, từ 360 × 180 px)
          <input type="file" accept="image/jpeg,image/png" multiple required class="block" @change="files = Array.from($event.target.files)">
        </label>
        <label><input v-model="fields.is_published" type="checkbox"> Đăng bán</label>
      </fieldset>
      <div class="flex gap-4 items-center">
        <button type="submit" class="bg-red-600 text-white rounded px-4 py-2" :disabled="saving || loadingAttributes || !!attributeError || !selected">
          {{ saving ? 'Đang lưu…' : 'Lưu sản phẩm' }}
        </button>
        <RouterLink to="/seller/product/all">
          Quay lại danh sách
        </RouterLink>
      </div>
    </form>
  </section>
</template>
