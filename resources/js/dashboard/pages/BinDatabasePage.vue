<template>
    <div>
        <div class="mb-6 flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-bold font-heading" :style="{ color: 'var(--admin-text)' }">قاعدة بيانات BIN</h1>
                <p class="text-sm mt-1" :style="{ color: 'var(--admin-text-muted)' }">
                    إدارة نطاقات BIN — البنك، الشبكة، نوع البطاقة، فئتها
                </p>
            </div>
            <button
                class="px-4 py-2 rounded-xl text-sm font-bold text-white"
                style="background: #b91c1c"
                @click="openCreateModal"
            >
                + نطاق BIN جديد
            </button>
        </div>

        <!-- Live PAN Lookup -->
        <div
            class="rounded-2xl p-5 mb-6"
            :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)' }"
        >
            <h2 class="text-base font-bold mb-3" :style="{ color: 'var(--admin-text)' }">
                فحص رقم بطاقة (Live)
            </h2>
            <div class="flex gap-2">
                <input
                    v-model="lookupPan"
                    dir="ltr"
                    placeholder="4847 8313 0473 9458"
                    class="flex-1 rounded-xl px-4 py-2.5 text-sm outline-none font-mono"
                    :style="{ backgroundColor: 'var(--admin-input-bg)', borderWidth: '1px', borderColor: 'var(--admin-input-border)', color: 'var(--admin-input-text)' }"
                    @keyup.enter="runLookup"
                />
                <button
                    class="px-5 py-2.5 rounded-xl text-sm font-bold text-white"
                    style="background: #0f172a"
                    :disabled="lookupBusy"
                    @click="runLookup"
                >
                    {{ lookupBusy ? '...' : 'فحص' }}
                </button>
            </div>

            <div
                v-if="lookupResult"
                class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3 text-xs"
            >
                <div><b>البنك:</b> {{ lookupResult.bank_name_ar || '—' }}</div>
                <div><b>الشبكة:</b> {{ lookupResult.network || '—' }}</div>
                <div><b>الشبكة الثانوية:</b> {{ lookupResult.secondary_network || '—' }}</div>
                <div><b>النوع:</b> {{ lookupResult.card_type || '—' }}</div>
                <div><b>الفئة:</b> {{ lookupResult.card_level || '—' }}</div>
                <div><b>BIN8:</b> <span class="font-mono">{{ lookupResult.bin_8 }}</span></div>
                <div><b>التطابق:</b> {{ lookupResult.match_type }}</div>
                <div><b>الثقة:</b> {{ lookupResult.confidence }}%</div>
                <div><b>Luhn:</b> {{ lookupResult.is_valid_luhn ? 'صالح' : 'غير صالح' }}</div>
            </div>

            <div v-if="lookupResult" class="mt-5 flex justify-center">
                <PaymentCardVisual
                    :pan-display="lookupPan"
                    :bank-name="lookupResult.bank_name_ar"
                    :bank-logo="lookupResult.logo_path"
                    :primary-network="lookupResult.network"
                    :card-type="lookupResult.card_type"
                    :card-level="lookupResult.card_level"
                    :currency="lookupResult.currency"
                />
            </div>
        </div>

        <!-- Filter bar -->
        <div class="flex flex-wrap gap-2 mb-4">
            <input
                v-model="filters.search"
                placeholder="بحث BIN أو بنك..."
                class="rounded-xl px-3 py-2 text-sm outline-none"
                :style="filterStyle"
                @keyup.enter="reload"
            />
            <select v-model="filters.bank_key" class="rounded-xl px-3 py-2 text-sm" :style="filterStyle" @change="reload">
                <option value="">كل البنوك</option>
                <option v-for="b in banks" :key="b.key" :value="b.key">{{ b.name_ar }}</option>
            </select>
            <select v-model="filters.network" class="rounded-xl px-3 py-2 text-sm" :style="filterStyle" @change="reload">
                <option value="">كل الشبكات</option>
                <option value="visa">Visa</option>
                <option value="mastercard">Mastercard</option>
                <option value="mada">mada</option>
                <option value="amex">Amex</option>
            </select>
            <button class="rounded-xl px-3 py-2 text-sm" :style="filterStyle" @click="reload">تحديث</button>
        </div>

        <!-- BIN ranges table -->
        <div
            class="rounded-2xl overflow-hidden"
            :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)' }"
        >
            <table class="w-full text-sm">
                <thead :style="{ backgroundColor: 'var(--admin-input-bg)' }">
                    <tr class="text-right">
                        <th class="px-3 py-2">النطاق</th>
                        <th class="px-3 py-2">طول</th>
                        <th class="px-3 py-2">البنك</th>
                        <th class="px-3 py-2">الشبكة</th>
                        <th class="px-3 py-2">النوع</th>
                        <th class="px-3 py-2">الفئة</th>
                        <th class="px-3 py-2">الثقة</th>
                        <th class="px-3 py-2">المصدر</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in rows.data || []"
                        :key="row.id"
                        class="border-t"
                        :style="{ borderColor: 'var(--admin-card-border)' }"
                    >
                        <td class="px-3 py-2 font-mono">{{ row.bin_start }} – {{ row.bin_end }}</td>
                        <td class="px-3 py-2">{{ row.bin_length }}</td>
                        <td class="px-3 py-2">{{ row.issuer_bank?.name_ar || row.issuer_bank_key || '—' }}</td>
                        <td class="px-3 py-2">{{ row.primary_network }}<span v-if="row.secondary_network"> / {{ row.secondary_network }}</span></td>
                        <td class="px-3 py-2">{{ row.card_type || '—' }}</td>
                        <td class="px-3 py-2">{{ row.card_level || '—' }}</td>
                        <td class="px-3 py-2">{{ row.confidence }}%</td>
                        <td class="px-3 py-2 text-xs opacity-70">{{ row.source }}</td>
                        <td class="px-3 py-2 text-left">
                            <button class="text-blue-600 px-2" @click="openEditModal(row)">تعديل</button>
                            <button class="text-red-600 px-2" @click="destroyRow(row)">حذف</button>
                        </td>
                    </tr>
                    <tr v-if="!rows.data?.length">
                        <td colspan="9" class="px-3 py-10 text-center opacity-60">لا توجد نطاقات.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <div v-if="modal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="modal.open = false">
            <div
                class="rounded-2xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto"
                :style="{ backgroundColor: 'var(--admin-card-bg)' }"
            >
                <h3 class="text-lg font-bold mb-4" :style="{ color: 'var(--admin-text)' }">
                    {{ modal.id ? 'تعديل نطاق BIN' : 'نطاق BIN جديد' }}
                </h3>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <label for="bin-start">
                        <span class="block mb-1">BIN Start</span>
                        <input id="bin-start" v-model.number="modal.form.bin_start" name="bin_start" type="number" class="w-full rounded-xl px-3 py-2 font-mono" :style="filterStyle" />
                    </label>
                    <label for="bin-end">
                        <span class="block mb-1">BIN End</span>
                        <input id="bin-end" v-model.number="modal.form.bin_end" name="bin_end" type="number" class="w-full rounded-xl px-3 py-2 font-mono" :style="filterStyle" />
                    </label>
                    <label for="bin-length">
                        <span class="block mb-1">Length</span>
                        <select id="bin-length" v-model.number="modal.form.bin_length" name="bin_length" class="w-full rounded-xl px-3 py-2" :style="filterStyle">
                            <option :value="4">4</option>
                            <option :value="6">6</option>
                            <option :value="8">8</option>
                        </select>
                    </label>
                    <label for="bin-issuer">
                        <span class="block mb-1">البنك</span>
                        <select id="bin-issuer" v-model="modal.form.issuer_bank_key" name="issuer_bank_key" class="w-full rounded-xl px-3 py-2" :style="filterStyle">
                            <option value="">— غير معروف —</option>
                            <option v-for="b in banks" :key="b.key" :value="b.key">{{ b.name_ar }}</option>
                        </select>
                    </label>
                    <label for="bin-primary-network">
                        <span class="block mb-1">الشبكة</span>
                        <select id="bin-primary-network" v-model="modal.form.primary_network" name="primary_network" class="w-full rounded-xl px-3 py-2" :style="filterStyle">
                            <option value="">—</option>
                            <option value="visa">Visa</option>
                            <option value="mastercard">Mastercard</option>
                            <option value="mada">mada</option>
                            <option value="amex">Amex</option>
                            <option value="discover">Discover</option>
                            <option value="unionpay">UnionPay</option>
                        </select>
                    </label>
                    <label for="bin-secondary-network">
                        <span class="block mb-1">الشبكة الثانوية</span>
                        <select id="bin-secondary-network" v-model="modal.form.secondary_network" name="secondary_network" class="w-full rounded-xl px-3 py-2" :style="filterStyle">
                            <option value="">—</option>
                            <option value="visa">Visa</option>
                            <option value="mastercard">Mastercard</option>
                            <option value="mada">mada</option>
                        </select>
                    </label>
                    <label for="bin-card-type">
                        <span class="block mb-1">النوع</span>
                        <select id="bin-card-type" v-model="modal.form.card_type" name="card_type" class="w-full rounded-xl px-3 py-2" :style="filterStyle">
                            <option value="">—</option>
                            <option value="debit">debit</option>
                            <option value="credit">credit</option>
                            <option value="prepaid">prepaid</option>
                            <option value="charge">charge</option>
                        </select>
                    </label>
                    <label for="bin-card-level">
                        <span class="block mb-1">الفئة</span>
                        <input id="bin-card-level" v-model="modal.form.card_level" name="card_level" placeholder="standard / gold / platinum / ..." class="w-full rounded-xl px-3 py-2" :style="filterStyle" />
                    </label>
                    <label for="bin-product-name">
                        <span class="block mb-1">المنتج</span>
                        <input id="bin-product-name" v-model="modal.form.product_name" name="product_name" class="w-full rounded-xl px-3 py-2" :style="filterStyle" />
                    </label>
                    <label for="bin-confidence">
                        <span class="block mb-1">الثقة (0–100)</span>
                        <input id="bin-confidence" v-model.number="modal.form.confidence" name="confidence" type="number" min="0" max="100" class="w-full rounded-xl px-3 py-2" :style="filterStyle" />
                    </label>
                    <label class="col-span-2 flex items-center gap-2" for="bin-is-active">
                        <input id="bin-is-active" v-model="modal.form.is_active" name="is_active" type="checkbox" />
                        <span>مفعّل</span>
                    </label>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button class="px-4 py-2 rounded-xl text-sm" :style="filterStyle" @click="modal.open = false">إلغاء</button>
                    <button class="px-4 py-2 rounded-xl text-sm text-white" style="background:#0f172a" :disabled="modal.busy" @click="saveModal">
                        {{ modal.busy ? '...' : 'حفظ' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref, computed } from 'vue';
import request from '@/api/request';
import PaymentCardVisual from '../components/PaymentCardVisual.vue';

const filterStyle = computed( () => ( {
    backgroundColor: 'var(--admin-input-bg)',
    borderWidth: '1px',
    borderColor: 'var(--admin-input-border)',
    color: 'var(--admin-input-text)',
} ) );

const banks = ref( [] );
const rows = ref( { data: [] } );

const filters = reactive( {
    search: '',
    bank_key: '',
    network: '',
} );

const lookupPan = ref( '' );
const lookupResult = ref( null );
const lookupBusy = ref( false );

async function runLookup ()
{
    if ( !lookupPan.value ) return;
    lookupBusy.value = true;
    try
    {
        const { data } = await request.get( '/admin/bin/lookup', {
            params: { pan: lookupPan.value },
        } );
        lookupResult.value = data;
    } finally
    {
        lookupBusy.value = false;
    }
}

async function loadBanks ()
{
    const { data } = await request.get( '/admin/bin/banks' );
    banks.value = data;
}

async function reload ()
{
    const { data } = await request.get( '/admin/bin/ranges', {
        params: { ...filters, per_page: 100 },
    } );
    rows.value = data;
}

const modal = reactive( {
    open: false,
    busy: false,
    id: null,
    form: emptyForm(),
} );

function emptyForm ()
{
    return {
        bin_start: 0,
        bin_end: 0,
        bin_length: 6,
        issuer_bank_key: '',
        primary_network: '',
        secondary_network: '',
        card_type: '',
        card_level: '',
        product_name: '',
        confidence: 75,
        is_active: true,
    };
}

function openCreateModal ()
{
    modal.id = null;
    modal.form = emptyForm();
    modal.open = true;
}

function openEditModal ( row )
{
    modal.id = row.id;
    modal.form = {
        bin_start: row.bin_start,
        bin_end: row.bin_end,
        bin_length: row.bin_length,
        issuer_bank_key: row.issuer_bank_key || '',
        primary_network: row.primary_network || '',
        secondary_network: row.secondary_network || '',
        card_type: row.card_type || '',
        card_level: row.card_level || '',
        product_name: row.product_name || '',
        confidence: row.confidence,
        is_active: !!row.is_active,
    };
    modal.open = true;
}

async function saveModal ()
{
    modal.busy = true;
    try
    {
        const payload = { ...modal.form };
        // Drop empty strings — backend accepts nullable but rejects empty enum values.
        for ( const k of Object.keys( payload ) )
        {
            if ( payload[ k ] === '' ) payload[ k ] = null;
        }

        if ( modal.id )
        {
            await request.put( `/admin/bin/ranges/${ modal.id }`, payload );
        } else
        {
            await request.post( '/admin/bin/ranges', payload );
        }
        modal.open = false;
        await reload();
    } finally
    {
        modal.busy = false;
    }
}

async function destroyRow ( row )
{
    if ( !confirm( `حذف النطاق ${ row.bin_start }–${ row.bin_end }؟` ) ) return;
    await request.delete( `/admin/bin/ranges/${ row.id }` );
    await reload();
}

onMounted( async () =>
{
    await Promise.all( [ loadBanks(), reload() ] );
} );
</script>
