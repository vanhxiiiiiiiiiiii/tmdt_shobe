<script setup>
import { cartOptions, money } from '~/utils/checkout'
import { fallbackProductImage, getResources } from '~/utils/resources'

defineProps({ item: { type: Object, required: true }, selected: Boolean, busy: Boolean })
defineEmits(['change', 'toggle'])
</script>

<template>
  <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
    <td class="p-2">
      <div class="flex items-center gap-2">
        <input type="checkbox" :checked="selected" :disabled="busy || !!item.unavailable_reason" aria-label="Chọn sản phẩm" @change="$emit('toggle', $event.target.checked)">
        <img :src="getResources(item.product?.images?.[0])" :alt="item.product?.name ?? ''" class="max-w-35 max-h-35 rounded-md" @error="fallbackProductImage">
      </div>
    </td>
    <th class="p-2 font-medium text-gray-900 dark:text-white max-w-80">
      <span class="whish-title font-semibold">{{ item.product?.name ?? 'Sản phẩm không còn bán' }}</span><p v-if="item.unavailable_reason">{{ item.unavailable_reason }}</p>
    </th>
    <td class="p-2 text-xs text-gray-500">
      <div v-for="(desc, i) in item.product.variations" :key="i">
        <p v-if="i === item.product_model.variation_index[0]">
          {{ desc.options[item.product_model.variation_index[1]] }}
        </p>
      </div>
    </td>
    <th class="p-2 font-medium text-gray-900 dark:text-white max-w-80">
      <span class="whish-list-price font-semibold">
        {{ money(item.unit_price) }}
      </span>
    </th>
    <td class="p-2">
      <div class="uppercase flex items-center rounded-md border-1 border-solid border-gray-300 dark:border-gray-500 text-xs w-min">
        <button type="button" class="p-1.25" aria-label="Giảm số lượng" :disabled="busy || item.quantity <= 1 || !item.available_quantity" @click="$emit('change', Math.min(item.quantity - 1, item.available_quantity))">
          <IBMinus />
        </button>

        <input :value="item.quantity" type="number" min="1" max="50" step="1" class="dark:bg-transparent appearance-none text-center  pointer-events-none border-l-1 border-l-solid border-l-gray-300 border-r-1 border-r-solid border-r-gray-300 font-medium py-1.25 text-black dark:(text-gray-200 border-gray-500)">
        <button type="button" class="p-1.25 cursor-pointer hover:bg-[#FAFAFA]" aria-label="+" :disabled="busy || item.quantity >= item.available_quantity" @click="$emit('change', item.quantity + 1)">=item.product_model.stock}" @click="increase(item.product_model_id, item.quantity)">
          <IBPlus />
        </button>
      </div>
    </td>
    <th class="p-2 font-medium text-red-500 dark:text-white max-w-80">
      <span class="whish-title font-semibold">{{ money(item.final_total_price) }}</span>
    </th>
    <td class="p-2">
      <div class="flex justify-center items-center">
        <button type="button" aria-label="Xóa sản phẩm" :disabled="busy" @click="$emit('change', 0)"><span class="trash flex justify-center"><ITrash /></span></button>
      </div>
    </td>
  </tr>
</template>
