<script setup>
import { money } from '~/utils/checkout'

defineProps({
  quantity: { type: Number, required: true },
  totals: { type: Object, required: true },
})
</script>

<template>
  <dl class="grid grid-cols-[1fr_auto] gap-x-8 gap-y-2">
    <dt>Số lượng sản phẩm</dt><dd>{{ quantity }}</dd>
    <dt>Tổng tiền hàng</dt><dd>{{ money(totals.subtotal) }}</dd>
    <dt>Phí vận chuyển</dt><dd>{{ money(totals.shipping_fee) }}</dd>
    <dt>Giảm giá</dt><dd>{{ money(totals.discount_amount) }}</dd>
    <dt class="font-semibold">
      Tổng thanh toán
    </dt><dd class="font-semibold">
      {{ money(totals.payment_total) }}
    </dd>
  </dl>
</template>
