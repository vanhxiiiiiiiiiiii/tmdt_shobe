<route lang="yaml">
meta:
  layout: buyer/LBProductDetails
</route>

<script setup>
import { useRoute, useRouter } from 'vue-router'
import { money } from '~/utils/checkout'
import { toast } from '~/stores/toast'
import { handleDate } from '~/utils/date'
import { productStatus } from '~/utils/status'
import { fallbackProductImage, getResources } from '~/utils/resources'
import ProductRequest from '~/services/product-request'
import CartRequest from '~/services/cart-request'
import { useCart } from '~/stores/cart'
import provinceNames from '~/shared/provinces'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const useToast = toast()
const cart = useCart()
const productResponseData = ref(null)
const productReview = ref([])
const imageIndex = ref(0)
const selection = ref([])
const quantity = ref(1)
const pending = ref(true)
const submitting = ref(false)
const errorMessage = ref('')
const productImg = computed(() => productResponseData.value?.images ?? [])
const models = computed(() => productResponseData.value?.models ?? [])
const selectedModel = computed(() => models.value.find(model =>
  (model.variation_index ?? []).length === selection.value.length
  && (model.variation_index ?? []).every((option, index) => option === selection.value[index]),
))
const productStock = computed(() => Number(selectedModel.value?.stock ?? 0))
const productPrice = computed(() => selectedModel.value?.price ?? '—')
const seller = computed(() => ({
  payget: productResponseData.value?.shop ?? {},
  statics: productResponseData.value?.shop?.statistic ?? {},
}))
const payloadCart = computed(() => ({ product_model_id: selectedModel.value?.id, quantity: quantity.value }))
const isAdd = ref(false)

onMounted(() => watch(() => route.params.product, async(id, _, onCleanup) => {
  let active = true
  onCleanup(() => { active = false })
  pending.value = true
  errorMessage.value = ''
  productResponseData.value = null
  productReview.value = []
  quantity.value = 1
  imageIndex.value = 0
  isAdd.value = false
  try {
    const { data } = await ProductRequest.getProductsById(id)
    if (!active)
      return
    productResponseData.value = data
    selection.value = [...(data.models?.find(model => model.stock > 0)?.variation_index
      ?? data.models?.[0]?.variation_index ?? [])]
  }
  catch (error) {
    if (active)
      errorMessage.value = error.response?.status === 404 ? 'Không tìm thấy sản phẩm.' : 'Không thể tải sản phẩm. Vui lòng thử lại.'
  }
  finally {
    if (active)
      pending.value = false
  }

  if (active && productResponseData.value) {
    try {
      const { data } = await ProductRequest.getReviewsProductsById(id, { params: { limit: 10 } })
      if (active)
        productReview.value = data?.data ?? []
    }
    catch {
      // A review request can fail independently of the product.
    }
  }
}, { immediate: true }))

function getModelStock(index, option) {
  selection.value[index] = option
  quantity.value = 1
}

async function submitOrder(buyNow) {
  if (!localStorage.getItem('token')) {
    await router.push('/buyer/login')
    return
  }
  if (submitting.value || !selectedModel.value || quantity.value > productStock.value)
    return

  submitting.value = true
  try {
    await CartRequest.addCart(payloadCart.value)
    if (buyNow) {
      await router.push('/buyer/cart')
      return
    }
    isAdd.value = true
    cart.result = (await CartRequest.getCart()).data
    useToast.updateToast('success', 'Đã cập nhật giỏ hàng.', true)
  }
  catch {
    // The API interceptor displays the response error.
  }
  finally {
    submitting.value = false
  }
}

const handleAdd = () => submitOrder(false)
const handleOrder = () => submitOrder(true)
const onvisitShop = () => router.push(`/shop/${productResponseData.value.shop_id}`)
</script>

<template>
  <p v-if="pending" role="status">
    Đang tải sản phẩm…
  </p>
  <p v-else-if="errorMessage" role="alert">
    {{ errorMessage }}
  </p>
  <template v-else-if="productResponseData">
    <div class="product-summary-container max-w-300 bg-white dark:bg-gray-800 rounded-xl shadow-md shadow-gray-400/50 p-3 mx-2">
      <div v-cloak class="main-content flex gap-10">
        <div>
          <img :src="getResources(productImg[imageIndex])" :alt="productResponseData.name" class="max-w-112 max-h-112 rounded-2xl shadow-lg shadow-gray-400/50 dark:shadow-gray-700" @error="fallbackProductImage">
          <div class="grid grid-cols-5 max-w-112 mt-3 gap-2">
            <img v-for="(img, i) in productImg" :key="i" :src="getResources(img)" alt="product_preview_img" class="rounded-md border-2 border-solid hover:border-[#EE4D2D] cursor-pointer">
          <!-- <img src="https://cf.shopee.vn/file/12ae221177dcb74dc4b1701afc06b298_tn" alt="product_preview_img" class="rounded-md border-2 border-solid hover:border-[#EE4D2D] cursor-pointer">
          <img src="https://cf.shopee.vn/file/5413e844e5893181b0f33a191af65cd0_tn" alt="product_preview_img" class="rounded-md border-2 border-solid hover:border-[#EE4D2D] cursor-pointer">
          <img src="https://cf.shopee.vn/file/7ed7a314372976ba07c7cc4f083bbf68_tn" alt="product_preview_img" class="rounded-md border-2 border-solid hover:border-[#EE4D2D] cursor-pointer">
          <img src="https://cf.shopee.vn/file/f0ef79bb6556880ebb85baab0e64c8d6" alt="product_preview_img" class="rounded-md border-2 border-solid hover:border-[#EE4D2D]">
          <img src="https://cf.shopee.vn/file/963e9b04751b757bb2a4ead4d1a2f1e9_tn" alt="product_preview_img" class="rounded-md border-2 border-solid hover:border-[#EE4D2D] cursor-pointer"> -->
          </div>
        </div>
        <div class="pr-5 grid content-between min-h-112">
          <h2 class="break-words text-xl font-medium">
            🔥{{ productResponseData.name }}
          </h2>
          <div class="flex divide-1 divide-solid divide-x divide-gray-300">
            <div class="flex items-center gap-2 pr-3">
              <p class="border-b-1 border-b-solid border-b-[#EE4D2D] text-[#EE4D2D] font-medium">
                {{ Number(productResponseData.avg_rating ?? 0).toFixed(1) }}
              </p>
              <div class="flex gap-0.25">
                <div v-for="i in 5" :key="i">
                  <IStars />
                </div>
              </div>
            </div>
            <div class="flex item gap-2 px-3">
              <p class="border-b-1 border-b-solid border-b-black font-medium">
                {{ productResponseData.reviews_count }}
              </p>
              <p class="text-gray-500">
                {{ t('product.evaluate') }}
              </p>
            </div>
            <div class="flex item gap-2 px-3">
              <p class="font-medium">
                {{ productResponseData.sold }}
              </p>
              <p class="text-gray-500">
                {{ t('product.solded') }}
              </p>
            </div>
          </div>
          <div class="py-3 px-5 bg-[#FAFAFA] dark:bg-gray-700 rounded-lg flex items-center gap-5">
            <div class="current-price flex text-[#EE4D2D]">
              <h2 class="font-medium text-3xl">
                {{ productPrice === '—' ? productPrice : money(productPrice) }}
              </h2>
            </div>
            <div class="bg-[#EE4D2D] rounded-sm px-1">
              <p class="text-white font-medium text-xs">
                {{ productStatus(productResponseData.status_id) }}
              </p>
            </div>
          </div>
          <div class="infor">
            <label>{{ t('product.weight') }}</label>
            <p>{{ productResponseData.weight }}gram</p>
            <p class="text-[#EE4D2D] bg-[#FFEEE8] px-1 text-sm ml-5">
              {{ t('product.buy-with-deal') }}
            </p>
          </div>
          <div class="infor">
            <label>{{ t('product.shipping') }}</label>
            <p class="text-sm flex gap-2 items-center">
              <IBShipping /> {{ t('product.transport') }}
            </p>
            <div>
              <select name="province" class="cursor-pointer dark:bg-gray-700 rounded-md px-2 appearance-none border-1 border-dashed border-gray-300">
                <option value="" selected hidden disabled>
                  Choose province
                </option>
                <option v-for="(province, i) in provinceNames" :key="i" :value="province" class="mt-5 bg-[#FFF5F1] dark:bg-gray-700">
                  {{ province }}
                </option>
              </select>
            </div>
          </div>

          <div v-for="(variation, index) in productResponseData.variations" :key="index" class="infor">
            <label>{{ variation.name }}</label>
            <div class="uppercase flex gap-2">
              <p v-for="(option, i) in variation.options" :key="i" class="box-type" :class="{ active: selection[index] === i }" @click="getModelStock(index, i)">
                {{ option }}
              </p>
            </div>
          </div>

          <div class="infor">
            <label>{{ t('product.quantities') }}</label>
            <div class="uppercase flex items-center rounded-md border-1 border-solid border-gray-300 text-sm">
              <p class="px-2 cursor-pointer hover:bg-[#FAFAFA]" :class="{'pointer-events-none opacity-50': payloadCart.quantity<2}" @click="quantity--">
                <IBMinus />
              </p>
              <p class="border-l-1 border-l-solid border-l-gray-300 border-r-1 border-r-solid border-r-gray-300 font-medium px-5 py-1">
                {{ payloadCart.quantity }}
              </p>
              <p class="px-2 cursor-pointer hover:bg-[#FAFAFA]" :class="{'pointer-events-none': payloadCart.quantity >= productStock}" @click="quantity++">
                <IBPlus />
              </p>
            </div>
            <p class="lowercase text-gray-500">
              {{ productStock }} {{ t('product.available-products') }}
            </p>
          </div>
          <div class="flex gap-3">
            <button class="px-5 py-3 rounded-md bg-[#FFEEE8] hover:bg-[#FFF5F1] border-1 border-solid border-[#EE4D2D] text-[#EE4D2D] capitalize flex items-center gap-1" :disabled="submitting || productStock < quantity" @click="handleAdd">
              <ICart class="max-h-6" />{{ t('product.add-to-cart') }}
            </button>
            <button class="px-5 py-3 rounded-md bg-[#EE4D2D] hover:bg-[#F05D40] border-1 border-solid border-[#EE4D2D] text-white capitalize" :disabled="submitting || productStock < quantity" @click="handleOrder">
              {{ t('product.order-now') }}
            </button>
            <button v-if="isAdd" class="px-5 py-3 rounded-md bg-[#FFEEE8] hover:bg-[#FFF5F1] border-1 border-solid border-[#EE4D2D] text-[#EE4D2D] capitalize flex items-center gap-1" @click="router.push('/buyer/cart')">
              <ICart class="max-h-6" />View cart
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="productResponseData.shop" class="shop-product-container max-w-300 bg-white dark:bg-gray-800 rounded-xl shadow-md shadow-gray-400/50 p-5 mx-2 divide-x divide-1 divide-solid divide-gray-300 flex flex-wrap">
      <div class="flex">
        <img v-if="seller.payget.avatar_image" :src="getResources(seller.payget.avatar_image)" alt="shop_avatar" class="max-w-19 max-h-19 rounded-full shadow-md shadow-gray-200 mr-4 object-cover" @error="fallbackProductImage">
        <div class="min-w-85">
          <p class="font-medium text-md">
            {{ seller.payget.name }}
          </p>
          <div class="flex gap-2 text-sm mt-2">
            <button class="px-3 py-1 rounded-md hover:bg-[#FAFAFA] border-2 border-solid border-gray-300 text-[#EE4D2D] capitalize flex items-center gap-1" @click="onvisitShop">
              <IShop />{{ t('product.visit-shop') }}
            </button>
          </div>
        </div>
      </div>
      <div class="flex justify-around items-center w-3/5 text-sm text-gray-400">
        <div>
          <div class="flex gap-2 min-w-27 capitalize">
            <p>{{ t('product.evaluate') }}</p>
            <span>{{ seller.statics.average_raiting }}</span>
          </div>
          <div class="flex gap-2 min-w-27 capitalize">
            <p>{{ t('product.productions') }}</p>
            <span>{{ seller.statics.product_count }}</span>
          </div>
        </div>
        <div>
          <div class="flex gap-2 min-w-27">
            <p>{{ t('shop.joined') }}</p>
            <span>{{ seller.payget.created_at ? handleDate(seller.payget.created_at) : '—' }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="product-details max-w-300 bg-white dark:bg-gray-800 rounded-xl shadow-md shadow-gray-400/50 p-5 mx-2">
      <div class="bg-[#FAFAFA] dark:bg-gray-700 p-3 rounded-xl flex items-center gap-2 uppercase text-xl font-medium">
        <IBDescription />
        <h2>
          {{ t('product.product-describes') }}
        </h2>
      </div>
      <p class="mt-3">
        {{ t('product.category') }}: {{ productResponseData.categories?.map(category => category.name).join(' / ') || '—' }}
      </p>
      <p v-for="(attribute, index) in productResponseData.attributes" :key="index">
        {{ attribute.name }}: {{ attribute.value }} {{ attribute.unit }}
      </p>
      <p class="mt-3 whitespace-pre-line">
        {{ productResponseData.description || 'Chưa có mô tả.' }}
      </p>
    </div>

    <div class="product-evalutions max-w-300 bg-white dark:bg-gray-800 rounded-xl shadow-md shadow-gray-400/50 p-5 mx-2">
      <div class="text-xl uppercase flex items-center gap-2 uppercase text-xl font-medium">
        <IBDescription />
        <h2>{{ t('product.product-evalutions') }}</h2>
      </div>
      <p v-if="!productReview.length">
        Chưa có đánh giá để hiển thị.
      </p>
      <div v-for="review in productReview" :key="review.id" class="border-b py-3">
        <p>{{ review.rating }} / 5</p>
        <p>{{ review.comment }}</p>
        <p v-if="review.reply">
          Phản hồi của cửa hàng: {{ review.reply }}
        </p>
      </div>
    </div>
  </template>
</template>

<style scoped>
.infor{
    display: flex;
    text-transform: capitalize;
    gap: 0.5rem;
}
.infor > label{
    width: 6.8rem;
    font-size: 0.9em;
    color: rgb(107, 114, 128);
    font-weight: 500;
}
.infor .box-type{
  border: 1px solid #e9e9e9;
  cursor: pointer;
  border-radius: 0.3rem;
  padding: 0 0.5rem;
}
.infor .box-type:hover,
.infor .box-type.active{
  color: #EE4D2D;
  border-color: #EE4D2D;
  background: #FFF5F1;
}
.shop-product-container span{
  color: #EE4D2D;
}
.product-details > div:not(:first-child):not(:last-child) {
  display: flex;
  align-items: center;
  font-size: 0.875rem;
  padding: 0.5rem 1rem;
  gap: 1rem;
}

.product-details label{
  min-width: 8.5rem;
  color: rgb(156, 163, 175);
}
.product-details > div:last-child > p:not(:last-child){
  margin: 0.5rem 0 0.5rem 1rem;
  font-size: 0.875rem;
}
.product-evalutions .customer-cmt p{
  margin: 0 0.5rem 0.5rem 0;
}
@media (max-width: 1000px){
  .main-content{
    flex-wrap: wrap;
  }
  .main-content > div:nth-child(2){
    margin-left: 0.75rem;
  }
}
</style>
