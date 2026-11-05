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
const { t } = useI18n()
const sliceText = value => String(value ?? '').slice(0, 220)
</script>

<template>
  <div>
    <div class="relative z-1">
      <div class="split third rounded-lg shadow-md card-img max-w-80 min-w-80 max-h-80 p-1" @click="onVisitProduct(props.card.id, props.card.shop_id)">
        <div class="cover">
          <img class="first-img rounded-xl w-full" :src="getResources(props.card.images?.[0])" loading="lazy" :alt="props.card.name" @error="fallbackProductImage">
        </div>
        <!-- <img class="first-img rounded-xl w-full" :src="getResources(props.card.images?.[0])" loading="lazy" :alt="props.card.name" @error="fallbackProductImage"> -->
      </div>
      <!-- <div class="split third rounded-lg shadow-md" @click="onVisitProduct(props.card.id, props.card.shop_id)">
    <div class="cover">
      <img class="first-img rounded-t-lg w-full max-h-80" :src="getResources(props.card.images?.[0])" loading="lazy" :alt="props.card.name" @error="fallbackProductImage">
    </div>
  </div> -->
    </div>
    <div class="product-description text-left p-2 w-full">
      <div class="px-2">
        <p class="card-title cursor-pointer duration-200 ease-linear hover:text-[#FF6600]" @click="onVisitProduct(props.card.id, props.card.shop_id)">
          {{ props.card.name }}
        </p>
        <div class="flex justify-between">
          <div class="flex items-center justify-start">
            <h6 class="font-bold tracking-tighter text-red-500">
              {{ productPrice }}
            </h6>
          </div>
          <div class="star-rating flex justify-start py-2">
            <img v-for="i in 5" :key="i" src="https://img.icons8.com/fluency/48/ffffff/star.png" class="max-w-4 max-h-4">
          </div>
        </div>
      </div>

      <div class="product-infor py-5 text-sm leading-6">
        <p v-for="(line, i) in sliceText(props.card.description)?.split('\\n')" :key="i">
          {{ line }}
        </p>
      </div>

      <div class="product-quantity">
        <p>{{ t('search.availability') }}: <span class="text-[#10A391]">{{ productStock }} sản phẩm</span></p>
      </div>

      <div class="py-5 flex justify-start">
        <h3 class="uppercase py-2.5 px-10 rounded-md bg-black text-white font-medium text-sm cursor-pointer hover:bg-[#F33535] dark:bg-[#F33535] duration-200" @click="onVisitProduct(props.card.id)">
          Xem sản phẩm
        </h3>
      </div>

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
    height: 100%;
    -webkit-transition: -webkit-transform 0.8s ease;
            transition: -webkit-transform 0.8s ease;
            transition: transform 0.8s ease;
            transition: transform 0.8s ease, -webkit-transform 1s ease;
}
.split.third:hover img {
    -webkit-transform: scale(1.15);
            transform: scale(1.15);
}
/* ************************ POPUP MODAL **************************** */
.popup-modal{
  animation: popup-animate 0.1s linear;
}
</style>
