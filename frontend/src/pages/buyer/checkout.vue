<route lang="yaml">
meta:
  layout: buyer/LBCheckout
</route>

<script setup>
import AccountRequest from '~/services/account-request'
import CartRequest from '~/services/cart-request'
import OrderRequest from '~/services/order-request'
import { useCart } from '~/stores/cart'
import { addressLine, cartOptions, money, requestError } from '~/utils/checkout'
import { fallbackProductImage, getResources } from '~/utils/resources'

const router = useRouter()
const route = useRoute()
const modelIds = [...new Set(String(route.query.items ?? '').split(',').filter(id => /^[1-9]\d*$/.test(id)).map(Number))]
const cart = useCart()
const addresses = ref([])
const addressId = ref('')
const newAddress = ref(false)
const recipient = reactive({ full_name: '', phone: '', address: '', town: '', city: '', state: '' })
const addressFields = [
  { name: 'full_name', label: 'Họ tên người nhận', max: 64 },
  { name: 'phone', label: 'Số điện thoại', max: 11, pattern: '[0-9]{10,11}' },
  { name: 'address', label: 'Số nhà và tên đường', max: 255 },
  { name: 'town', label: 'Phường / xã', max: 50 },
  { name: 'city', label: 'Quận / huyện', max: 50 },
  { name: 'state', label: 'Tỉnh / thành phố', max: 50 },
]
const savingAddress = ref(false)
const voucher = ref('')
const appliedVoucher = ref(null)
const totals = ref(null)
const loading = ref(true)
const quoting = ref(false)
const submitting = ref(false)
const error = ref('')
const pending = ref(null)
let pendingKey

const selectedAddress = computed(() => addresses.value.find(address => address.id === addressId.value))
const chosen = computed(() => cart.result.filter(item => modelIds.includes(item.product_model_id)))
const quantity = computed(() => chosen.value.reduce((sum, item) => sum + Number(item.quantity), 0))
const cartAvailable = computed(() => chosen.value.length > 0 && chosen.value.length === modelIds.length && !chosen.value.some(item => item.unavailable_reason))

async function quote(code = null) {
  quoting.value = true
  error.value = ''
  try {
    const { data } = await OrderRequest.quoteCart(code, modelIds)
    totals.value = data
    appliedVoucher.value = code
  }
  catch (failure) {
    error.value = requestError(failure, 'Không tính được tổng tiền.')
  }
  finally {
    quoting.value = false
  }
}

onMounted(async() => {
  if (!localStorage.getItem('token')) {
    await router.replace('/buyer/login')
    return
  }
  try {
    const user = JSON.parse(localStorage.getItem('user') ?? '{}')
    pendingKey = `e-shopee:checkout:${user.data?.id ?? user.id ?? ''}`
    pending.value = JSON.parse(sessionStorage.getItem(pendingKey) ?? 'null')
    const [{ data: items }, { data: savedAddresses }] = await Promise.all([CartRequest.getCart(), AccountRequest.getAddress()])
    cart.result = items
    addresses.value = savedAddresses
    addressId.value = savedAddresses[0]?.id ?? ''
    newAddress.value = !savedAddresses.length
    if (cartAvailable.value && !pending.value)
      await quote()
  }
  catch (failure) {
    error.value = requestError(failure, 'Không tải được thông tin thanh toán.')
  }
  finally {
    loading.value = false
  }
})

async function saveAddress() {
  if (savingAddress.value)
    return
  savingAddress.value = true
  error.value = ''
  try {
    const { data } = await AccountRequest.createAddress(Object.fromEntries(
      Object.entries(recipient).map(([field, value]) => [field, value.trim()]),
    ))
    addresses.value.push(data)
    addressId.value = data.id
    newAddress.value = false
  }
  catch (failure) {
    error.value = requestError(failure, 'Không lưu được địa chỉ giao hàng.')
  }
  finally {
    savingAddress.value = false
  }
}

async function placeOrder() {
  if (submitting.value || (!pending.value && (!selectedAddress.value || !cartAvailable.value || !totals.value || newAddress.value)))
    return
  submitting.value = true
  error.value = ''
  try {
    if (!pending.value) {
      pending.value = {
        request_key: crypto.randomUUID(),
        address_id: addressId.value,
        payment_method: 'cod',
        voucher_code: appliedVoucher.value,
        orders: chosen.value.map(item => ({ product_model_id: item.product_model_id, quantity: item.quantity })),
      }
      sessionStorage.setItem(pendingKey, JSON.stringify(pending.value))
    }
    const { data } = await OrderRequest.createOrders(pending.value)
    sessionStorage.removeItem(pendingKey)
    pending.value = null
    cart.result = cart.result.filter(item => !modelIds.includes(item.product_model_id))
    await router.replace({ path: '/buyer/order/confirmation', query: { orders: data.map(order => order.id).join(',') } })
  }
  catch (failure) {
    error.value = requestError(failure, 'Chưa xác nhận được kết quả. Hãy gửi lại cùng yêu cầu đặt hàng.')
    if ([400, 422].includes(failure.response?.status)) {
      sessionStorage.removeItem(pendingKey)
      pending.value = null
    }
  }
  finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="checkout-container grid grid-cols-3 gap-5 p-15 dark:text-white">
    <div class="left col-span-2 text-left">
      <p v-if="loading" role="status">
      Đang tải đơn hàng…
    </p>
    <p v-if="error" class="mb-4 text-red-700" role="alert">
      {{ error }}
    </p>
    <div v-if="pending && !loading" class="hide-content">
      <p>Yêu cầu đặt hàng trước đó đang chờ xác nhận. Bạn có thể gửi lại cùng yêu cầu.</p>
      <button type="button" class="bg-red-600 text-white rounded p-3 mt-3" :disabled="submitting" @click="placeOrder">
        Kiểm tra lại đơn hàng
      </button>
    </div>
    <div v-else-if="!loading && !cartAvailable">
      <p>{{ cart.result.length ? 'Hãy chọn lại sản phẩm khả dụng trong giỏ.' : 'Giỏ hàng trống.' }}</p>
      <RouterLink to="/buyer/cart" class="underline">
        Quay lại giỏ hàng
      </RouterLink>
    </div>
    
      <template v-if="!loading && cartAvailable && !pending">
        <div class="personal"><h5><i class="fas fa-user-shield" /> Sản phẩm đặt mua</h5><div class="hide-content"><ul class="divide-y">
            <li v-for="item in chosen" :key="item.product_model_id" class="flex items-center gap-3 py-3">
              <img :src="getResources(item.product?.images?.[0])" :alt="item.product?.name" class="w-16 h-16 object-contain" @error="fallbackProductImage">
              <div class="flex-1">
                <p>{{ item.product?.name }}</p>
                <small v-if="item.shop?.name">{{ item.shop.name }}</small>
                <p class="text-sm">
                  {{ cartOptions(item) || 'Mặc định' }} · {{ money(item.unit_price) }} × {{ item.quantity }}
                </p>
              </div>
              <strong>{{ money(item.final_total_price) }}</strong>
            </li>
          </ul></div></div>
        <div class="address"><h5><i class="fas fa-map-marker-alt" /> Địa chỉ giao hàng</h5><section class="hide-content">
          
          <label v-if="addresses.length && !newAddress" class="block">
            Địa chỉ đã lưu
            <select v-model="addressId" class="block w-full border rounded p-2 dark:bg-gray-700">
              <option v-for="address in addresses" :key="address.id" :value="address.id">{{ address.full_name }} · {{ address.phone }} · {{ addressLine(address) }}</option>
            </select>
          </label>
          <p v-if="selectedAddress && !newAddress" class="mt-2">
            {{ selectedAddress.full_name }} · {{ selectedAddress.phone }} · {{ addressLine(selectedAddress) }}
          </p>
          <button v-if="!newAddress" type="button" class="underline mt-2" @click="newAddress = true">
            Thêm địa chỉ
          </button>
          <form v-else class="grid gap-3 mt-3" @submit.prevent="saveAddress">
            <label v-for="field in addressFields" :key="field.name">
              {{ field.label }}
              <input v-model.trim="recipient[field.name]" :type="field.name === 'phone' ? 'tel' : 'text'" :maxlength="field.max" :pattern="field.pattern" class="block w-full border rounded p-2 dark:bg-gray-700" required>
            </label>
            <div class="flex gap-4">
              <button type="submit" class="border rounded p-2" :disabled="savingAddress">
                Lưu địa chỉ
              </button>
              <button v-if="addresses.length" type="button" :disabled="savingAddress" @click="newAddress = false">
                Dùng địa chỉ đã lưu
              </button>
            </div>
          </form>
        </section></div>
        <div class="shipping"><h5><i class="fas fa-shipping-fast" /> {{ $t('checkout.shipping-method') }}</h5><div class="hide-content">Phí vận chuyển: {{ money(totals?.shipping_fee) }}</div></div>
        <div class="payment"><h5><i class="far fa-credit-card" /> Thanh toán khi nhận hàng (COD)</h5><div class="hide-content"><form class="flex flex-wrap gap-2 items-end" @submit.prevent="quote(voucher.trim() || null)">
          <label>Voucher <input v-model="voucher" maxlength="50" class="block border rounded p-2 dark:bg-gray-700"></label>
          <button type="submit" class="border rounded p-2" :disabled="quoting">
            Áp dụng
          </button>
        </form>
        <p v-if="appliedVoucher">
          Đã áp dụng: {{ appliedVoucher }}
        </p>
        </div></div>
      </template>
    </div>
    <div v-if="!loading && cartAvailable && !pending" class="right col-span-1">
      <div class="table-checkout row-span-1 grid grid-rows-3 divide-light-700 divide-y">
        <div class="items-checkout row-span-1 grid grid-rows-2 py-3">
          <div class="item row-span-1 flex justify-between items-center">
            <p>{{ quantity }} {{ t('checkout.item') }}</p>
            <p class="font-bold tracking-tighter">
              {{ money(totals?.subtotal) }}
            </p>
          </div>
          <div class="shipping row-span-1 flex justify-between items-center">
            <p>{{ t('checkout.shipping') }}</p>
            <p class="font-bold tracking-tighter">
              {{ money(totals?.shipping_fee) }}
            </p>
          </div>
        </div>

        <div class="total-checkout row-span-1 grid grid-rows-2 py-3">
          <div class="item row-span-1 flex justify-between items-center">
            <p>{{ t('checkout.total') }}</p>
            <p class="font-bold tracking-tighter">
              {{ money(totals?.payment_total) }}
            </p>
          </div>
          <div class="shipping row-span-1 flex justify-between items-center">
            <p>Giảm giá</p>
            <p class="font-bold tracking-tighter">
              {{ money(totals?.discount_amount) }}
            </p>
          </div>
        </div>

        <div class="process flex justify-center items-center">
          <button type="button" :disabled="submitting || quoting || savingAddress || !selectedAddress || !totals || newAddress || (voucher.trim() || null) !== appliedVoucher" @click="placeOrder" class="bg-black text-white font-semibold py-2 px-7 rounded-md hover:bg-[#E14641] duration-200 uppercase tracking-normal cursor-pointer flex items-center gap-1">
            <ISave />{{ t('checkout.process-checkout') }}
          </button>
        </div>
      </div>
      <div class="commit-checkout row-span-1">
        <div class="flex justify-around items-center tracking-tight py-2 text-sm">
          <i class="fas fa-shield-alt" />
          <p>{{ t('checkout.secure-policy') }}</p>
        </div>
        <div class="flex justify-around items-center tracking-tight py-2 text-sm">
          <i class="fas fa-truck" />
          <p>{{ t('checkout.delivery-policy') }}</p>
        </div>
        <div class="flex justify-around items-center tracking-tight py-2 text-sm">
          <i class="fas fa-exchange-alt" />
          <p>{{ t('checkout.return-policy') }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
#more {display: none;}
.left > div,
.right > div{
  padding: 1rem;
  margin: 1rem 0;
  border: 1px solid rgb(212, 212, 212);
  border-radius: 8px;
}
h5{
  cursor: pointer;
  text-decoration: underline;
  font-size: 1.2rem;
  font-weight: 600;
  text-transform: capitalize;
}
form > div {
  display: flex;
  padding: 0.5rem 1rem;
  align-items: center;
}
label{
  width: 30%;
  cursor: pointer;
}
input[type="radio"],
input[type="checkbox"]{
  margin-right: 0.3rem;
}
input[type="text"],
input[type="email"],
input[type="date"],
select[type="text"],
textarea{
    border: 1px solid rgb(209, 209, 209);
    outline: none;
    border-radius: 0.25rem;
    width: 35%;
    padding: 0.25rem 1rem;
    transition: 0.2s linear;
    margin-top: 0.25rem;
}
input[type="text"]:focus,
input[type="email"]:focus,
input[type="password"]:focus,
input[type="date"]:focus,
select[type="text"]:focus,
textarea:focus{
    box-shadow: 1px 1px 3px rgb(59, 175, 252);
}
/* **************** TRANSITiON ******************* */
.nested-enter-active,
.nested-leave-active {
  transition: all 0.3s ease-in-out;
}
.nested-leave-active {
  transition-delay: 0.1s;
}
.nested-enter-from,
.nested-leave-to {
  transform: translateY(30px);
  opacity: 0;
}
/* ******************************************* */
@media (max-width: 991px){
  .checkout-container{
    grid-template-columns: 100%;
    padding: 4rem 2rem;
  }
  .commit-checkout > div{
    justify-content: space-evenly;
  }
  input[type="text"],
  input[type="email"],
  input[type="password"],
  input[type="date"],
  select[type="text"],
  textarea{
    width: 50%;
  }
  .password-personal > div > input{
    width: 100rem;
  }
}
@media (max-width: 640px){
  .checkout-container{
    padding: 4rem 0.5rem;
  }
  .left{
    margin-right: 1rem;
  }
  .check-personal{
    display: grid;
  }
  .password-personal > div > input{
    max-width: 100%;
  }
}
@media (max-width: 537px){
  .personal > div > form > div:nth-child(2),
  .personal > div > form > div:nth-child(3),
  .personal > div > form > div:nth-child(4),
  .personal > div > form > div:nth-child(5),
  .personal > div > form > div:nth-child(6){
    display: grid;
  }
  .checkout-container{
    padding-left: 0.5rem;
    padding-right: 0.5rem;
  }
}
@media (max-width: 379px){
  .password-personal > div > input{
    max-width: 100%;
  }
  input[type="text"],
  input[type="email"],
  input[type="password"],
  input[type="date"],
  select[type="text"],
  textarea{
    width: 62%;
  }
}
</style>
