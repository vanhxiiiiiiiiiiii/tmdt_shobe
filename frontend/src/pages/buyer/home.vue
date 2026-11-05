<route lang="yaml">
meta:
  layout: buyer/LBHome
</route>

<script setup lang="ts">
useHead({
  title: 'buyer | home',
})
</script>

<template>
  <div>
    <CBSwipper />
    <CBCategory />
    <CBBanner />
    <!-- <CBFlashSales />
    <CBDemo1 />
    <CBOurProducts />
    <CBTrending />
    <CBTopProducts />
    <CBMonthSale />
    <CBDealArrivals />
    <CBDemo3 /> -->
    <!-- <div class="flex justify-center">
      <CBToday />
    </div> -->
    <div class="flex justify-center">
      <CBFeature />
    </div>
    <!-- <CSlick /> -->
    <CBDemo2 />
    <CBService />
  </div>
</template>
