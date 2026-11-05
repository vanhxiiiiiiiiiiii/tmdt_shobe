<script setup>
import { money } from '~/utils/checkout'
import { fallbackProductImage, getResources } from '~/utils/resources'

const props = defineProps({
  card: { type: Object, required: true },
})

const models = computed(() => props.card.models ?? [])
const price = computed(() => {
  const prices = models.value.map(model => Number(model.price))
  if (!prices.length)
    return '—'
  const min = Math.min(...prices)
  const max = Math.max(...prices)
  return min === max ? money(min) : `${money(min)} – ${money(max)}`
})
const stock = computed(() => models.value.reduce((total, model) => total + Number(model.stock), 0))
const productPrice = price
const productStock = stock
const router = useRouter()
const onVisitProduct = id => router.push(`/product/${id}`)
const sliceText = value => value.slice(0, 70)
</script>

<template>
  <div class="split third rounded-lg shadow-md z-0" @click="onVisitProduct(props.card.id, props.card.shop_id)">
    <div class="cover">
      <img class="first-img rounded-t-lg w-full max-h-80" :src="getResources(props.card.images?.[0])" loading="lazy" :alt="props.card.name" @error="fallbackProductImage">
    </div>
  </div>
  <div class="product-description text-left p-2">
    <p class="card-title cursor-pointer duration-200 ease-linear hover:text-[#FF6600] dark:text-gray-300 text-sm" @click="onVisitProduct(props.card.id, props.card.shop_id)">
      {{ sliceText(props.card.name) }}...
    </p>
    <div class="flex items-center justify-between my-2">
      <h6 class="card-price font-bold tracking-tighter text-red-500">
        {{ productPrice }}
      </h6>
      <h6 class="text-xs text-green-600">
        Tồn kho {{ productStock }}
      </h6>
    </div>
    <div class="flex justify-between">
      <div class="star-rating flex justify-start">
        <img v-for="i in 5" :key="i" src="https://img.icons8.com/fluency/48/ffffff/star.png" class="max-w-4 max-h-4">
      </div>
      <h6 class="text-xs dark:text-gray-300">
        Đã bán {{ props.card.sold }}
      </h6>
    </div>
  </div>
</template>

<style scoped>
.split.third {
    display: block;
    overflow: hidden;
    cursor: pointer;
}
.split.third img {
    width: 100%;
    height: auto;
    -webkit-transition: -webkit-transform 0.8s ease;
            transition: -webkit-transform 0.8s ease;
            transition: transform 0.8s ease;
            transition: transform 0.8s ease, -webkit-transform 1s ease;
}
.split.third:hover img {
    -webkit-transform: scale(1.15);
            transform: scale(1.15);
}
</style>
