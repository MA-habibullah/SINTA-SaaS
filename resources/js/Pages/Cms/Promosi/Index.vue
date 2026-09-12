<template>
  <AppLayout title="CMS & Promosi Aplikasi">
    <div class="space-y-6">
      <!-- Header Section -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
              Super Admin Control
            </span>
            <span class="text-slate-400">&bull;</span>
            <span class="text-xs font-semibold text-slate-500">Landing Page & Marketing Builder</span>
          </div>
          <h1 class="text-2xl font-black text-slate-900 tracking-tight">CMS & Promosi Aplikasi SINTA</h1>
          <p class="text-xs text-slate-500 mt-0.5">Kelola materi promosi, banner hero, paket penawaran free trial, dan showcase fitur untuk landing page publik.</p>
        </div>

        <div class="flex items-center gap-2.5">
          <a href="/" target="_blank" class="btn btn-sm btn-light border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 flex items-center gap-2 transition">
            <i class="bi bi-box-arrow-up-right text-blue-600"></i>
            <span>Lihat Landing Page Publik</span>
          </a>
          <button @click="openCreateModal" class="btn btn-sm bg-blue-600 hover:bg-blue-700 text-white rounded-xl px-4 py-2.5 text-xs font-bold shadow-md shadow-blue-500/20 flex items-center gap-2 transition">
            <i class="bi bi-plus-lg"></i>
            <span>Tambah Konten Promosi</span>
          </button>
        </div>
      </div>

      <!-- Quick Stats Row -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
          <span class="text-xs text-slate-500 font-semibold">Total Konten</span>
          <div class="text-2xl font-black text-slate-900 mt-1">{{ stats.totalItems }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
          <span class="text-xs text-slate-500 font-semibold">Konten Aktif</span>
          <div class="text-2xl font-black text-emerald-600 mt-1">{{ stats.activeItems }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
          <span class="text-xs text-slate-500 font-semibold">Paket Promosi / Trial</span>
          <div class="text-2xl font-black text-blue-600 mt-1">{{ stats.pricingCount }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
          <span class="text-xs text-slate-500 font-semibold">Feature Cards</span>
          <div class="text-2xl font-black text-indigo-600 mt-1">{{ stats.featureCount }}</div>
        </div>
      </div>

      <!-- Horizontal Tabs Navigation -->
      <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-2 relative">
        <div class="flex items-center relative">
          <button type="button" 
                  class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs me-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px]" 
                  onclick="document.getElementById('promoNavTabs')?.scrollBy({ left: -220, behavior: 'smooth' })"
                  title="Geser ke Kiri">
            <i class="bi bi-chevron-left"></i>
          </button>

          <div class="nav-tabs-wrapper grow overflow-hidden relative">
            <ul class="flex border-0 flex-nowrap overflow-x-auto whitespace-nowrap scrollable-nav-tabs gap-1.5 px-1 select-none no-scrollbar" id="promoNavTabs">
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2 rounded-xl text-xs transition flex items-center gap-2" 
                        :class="activeSection === 'all' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeSection = 'all'">
                  <i class="bi bi-grid-fill"></i> Semua Konten ({{ stats.totalItems }})
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2 rounded-xl text-xs transition flex items-center gap-2" 
                        :class="activeSection === 'hero' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeSection = 'hero'">
                  <i class="bi bi-stars"></i> Banner Hero ({{ grouped.hero.length }})
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2 rounded-xl text-xs transition flex items-center gap-2" 
                        :class="activeSection === 'pricing' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeSection = 'pricing'">
                  <i class="bi bi-gift-fill text-yellow-500"></i> Paket & Free Trial ({{ grouped.pricing.length }})
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2 rounded-xl text-xs transition flex items-center gap-2" 
                        :class="activeSection === 'features' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeSection = 'features'">
                  <i class="bi bi-columns-gap"></i> Fitur Unggulan ({{ grouped.features.length }})
                </button>
              </li>
              <li class="nav-item">
                <button class="border-0 font-bold px-4 py-2 rounded-xl text-xs transition flex items-center gap-2" 
                        :class="activeSection === 'faq' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                        @click="activeSection = 'faq'">
                  <i class="bi bi-question-circle"></i> FAQ ({{ grouped.faq.length }})
                </button>
              </li>
            </ul>
          </div>

          <button type="button" 
                  class="btn btn-sm btn-light border border-slate-200/80 rounded-xl shadow-2xs ms-1.5 hidden md:flex items-center justify-center shrink-0 text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition w-[34px] h-[34px]" 
                  onclick="document.getElementById('promoNavTabs')?.scrollBy({ left: 220, behavior: 'smooth' })"
                  title="Geser ke Kanan">
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>

      <!-- Content Cards Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <div v-for="item in filteredPromotions" :key="item.id" 
             class="bg-white rounded-3xl border border-slate-200/80 shadow-xs hover:shadow-md transition-all p-5 flex flex-col justify-between">
          
          <div>
            <!-- Section Badge & Status Toggle -->
            <div class="flex items-center justify-between gap-2 mb-3">
              <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider',
                             item.section_key === 'hero' ? 'bg-purple-100 text-purple-700' :
                             (item.section_key === 'pricing' ? 'bg-emerald-100 text-emerald-700' :
                             (item.section_key === 'features' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-700'))]">
                {{ item.section_key }}
              </span>

              <button @click="toggleItemStatus(item)" 
                      :class="['px-2.5 py-1 rounded-xl text-xs font-bold flex items-center gap-1.5 transition',
                               item.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200']"
                      :title="item.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'">
                <i :class="['bi', item.is_active ? 'bi-check-circle-fill text-emerald-600' : 'bi-dash-circle']"></i>
                <span>{{ item.is_active ? 'Aktif' : 'Draft' }}</span>
              </button>
            </div>

            <!-- Icon & Title -->
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-200 text-blue-600 flex items-center justify-center text-lg shrink-0">
                <i :class="['bi', item.icon_class || 'bi-stars']"></i>
              </div>
              <div>
                <h3 class="font-extrabold text-sm text-slate-900 leading-snug">{{ item.title }}</h3>
                <p v-if="item.badge_text" class="text-[11px] font-bold text-blue-600 mt-0.5">{{ item.badge_text }}</p>
              </div>
            </div>

            <!-- Subtitle / Content Snippet -->
            <p class="text-xs text-slate-500 mt-3 line-clamp-3 leading-relaxed">
              {{ item.subtitle || item.content || 'Tidak ada deskripsi tambahan.' }}
            </p>

            <!-- CTA Link Preview -->
            <div v-if="item.cta_text" class="mt-3 p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-[11px] text-slate-600 flex items-center justify-between">
              <span class="font-bold text-slate-700">{{ item.cta_text }}</span>
              <span class="text-blue-600 truncate max-w-[150px]">{{ item.cta_link }}</span>
            </div>
          </div>

          <!-- Card Action Buttons -->
          <div class="flex items-center justify-end gap-2 pt-4 mt-4 border-t border-slate-100">
            <button @click="openEditModal(item)" 
                    class="btn btn-sm btn-light border border-slate-200 text-slate-700 hover:text-blue-600 rounded-xl px-3 py-1.5 text-xs font-bold flex items-center gap-1.5 transition">
              <i class="bi bi-pencil"></i>
              <span>Edit</span>
            </button>
            <button @click="deleteItem(item)" 
                    class="btn btn-sm btn-light border border-slate-200 text-red-600 hover:bg-red-50 rounded-xl px-3 py-1.5 text-xs font-bold flex items-center gap-1.5 transition">
              <i class="bi bi-trash"></i>
              <span>Hapus</span>
            </button>
          </div>

        </div>
      </div>

      <!-- Empty State -->
      <div v-if="filteredPromotions.length === 0" class="bg-white rounded-3xl p-12 text-center border border-slate-200/80">
        <i class="bi bi-stars text-4xl text-slate-300"></i>
        <h3 class="text-base font-bold text-slate-700 mt-2">Belum Ada Konten di Kategori Ini</h3>
        <p class="text-xs text-slate-400 mt-1">Tambahkan materi promosi untuk kategori ini agar tampil di landing page publik.</p>
        <button @click="openCreateModal" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-xl text-xs font-bold shadow-md">
          Tambah Sekarang
        </button>
      </div>

      <!-- MODAL FORM (Teleport to Body) -->
      <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
          <div class="relative bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl border border-slate-100 text-slate-800">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
              <div>
                <h3 class="text-lg font-black text-slate-900">{{ isEditing ? 'Edit Konten Promosi' : 'Tambah Konten Promosi Baru' }}</h3>
                <p class="text-xs text-slate-500">Materi ini akan langsung dirender pada landing page publik SINTA.</p>
              </div>
              <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-sm font-bold">
                &times;
              </button>
            </div>

            <!-- Modal Form -->
            <form @submit.prevent="savePromoItem" class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Section Key -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Kategori / Penempatan <span class="text-red-500">*</span></label>
                  <select v-model="form.section_key" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white">
                    <option value="hero">Hero Banner (Utama)</option>
                    <option value="pricing">Paket & Free Trial</option>
                    <option value="features">Fitur Unggulan (Feature Showcase)</option>
                    <option value="faq">FAQ (Pertanyaan Umum)</option>
                    <option value="testimonials">Testimoni Sekolah</option>
                    <option value="cta">Promotional CTA</option>
                  </select>
                </div>

                <!-- Badge Text -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Teks Badge / Tagline Kecil</label>
                  <input v-model="form.badge_text" type="text" placeholder="Misal: Paling Populer / Free Trial 3 Bulan"
                         class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white" />
                </div>

                <!-- Title -->
                <div class="md:col-span-2">
                  <label class="block text-xs font-bold text-slate-700 mb-1">Judul / Headline Utama <span class="text-red-500">*</span></label>
                  <input v-model="form.title" type="text" required placeholder="Judul promosi atau headline..."
                         class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white" />
                </div>

                <!-- Subtitle / Deskripsi -->
                <div class="md:col-span-2">
                  <label class="block text-xs font-bold text-slate-700 mb-1">Subjudul / Deskripsi Lengkap</label>
                  <textarea v-model="form.subtitle" rows="3" placeholder="Penjelasan detail penawaran promosi..."
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white"></textarea>
                </div>

                <!-- Icon Class -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Bootstrap Icon Class</label>
                  <input v-model="form.icon_class" type="text" placeholder="bi-gift-fill / bi-stars"
                         class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white" />
                </div>

                <!-- Order Num -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Urutan Tampil (Order)</label>
                  <input v-model="form.order_num" type="number" min="0" placeholder="0"
                         class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white" />
                </div>

                <!-- CTA Text -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Teks Tombol Aksi (CTA)</label>
                  <input v-model="form.cta_text" type="text" placeholder="Misal: Coba Gratis Sekarang"
                         class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white" />
                </div>

                <!-- CTA Link -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Target Link Tombol (URL)</label>
                  <input v-model="form.cta_link" type="text" placeholder="/daftar-sekolah"
                         class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white" />
                </div>

                <!-- Active Toggle -->
                <div class="md:col-span-2 pt-2">
                  <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-700">
                    <input v-model="form.is_active" type="checkbox" class="rounded text-blue-600 focus:ring-blue-500" />
                    <span>Aktifkan langsung di Landing Page</span>
                  </label>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex items-center justify-end gap-3 pt-5 border-t border-slate-100">
                <button type="button" @click="showModal = false" class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50">
                  Batal
                </button>
                <button type="submit" :disabled="form.processing"
                        class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-500/20 disabled:opacity-50 flex items-center gap-2">
                  <span v-if="form.processing">Menyimpan...</span>
                  <span v-else>Simpan Konten</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </Teleport>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  promotions: Array,
  grouped: Object,
  stats: Object,
});

const activeSection = ref('all');
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({
  section_key: 'hero',
  title: '',
  subtitle: '',
  content: '',
  badge_text: '',
  icon_class: 'bi bi-stars',
  image_url: '',
  cta_text: 'Daftarkan Sekolah',
  cta_link: '/daftar-sekolah',
  is_active: true,
  order_num: 0,
});

const filteredPromotions = computed(() => {
  if (activeSection.value === 'all') {
    return props.promotions || [];
  }
  return (props.promotions || []).filter(p => p.section_key === activeSection.value);
});

const openCreateModal = () => {
  isEditing.value = false;
  editingId.value = null;
  form.reset();
  form.section_key = activeSection.value === 'all' ? 'hero' : activeSection.value;
  form.is_active = true;
  form.order_num = 0;
  showModal.value = true;
};

const openEditModal = (item) => {
  isEditing.value = true;
  editingId.value = item.id;
  form.section_key = item.section_key;
  form.title = item.title;
  form.subtitle = item.subtitle || '';
  form.content = item.content || '';
  form.badge_text = item.badge_text || '';
  form.icon_class = item.icon_class || 'bi bi-stars';
  form.image_url = item.image_url || '';
  form.cta_text = item.cta_text || '';
  form.cta_link = item.cta_link || '';
  form.is_active = Boolean(item.is_active);
  form.order_num = item.order_num || 0;
  showModal.value = true;
};

const savePromoItem = () => {
  if (isEditing.value && editingId.value) {
    form.put(`/super-admin/cms-promosi/${editingId.value}`, {
      onSuccess: () => {
        showModal.value = false;
      },
    });
  } else {
    form.post('/super-admin/cms-promosi', {
      onSuccess: () => {
        showModal.value = false;
      },
    });
  }
};

const toggleItemStatus = async (item) => {
  try {
    const res = await axios.post(`/super-admin/cms-promosi/${item.id}/toggle`);
    if (res.data.success) {
      item.is_active = res.data.is_active;
    }
  } catch (err) {
    alert('Gagal mengubah status konten: ' + (err.response?.data?.error || err.message));
  }
};

const deleteItem = (item) => {
  if (confirm(`Hapus konten promosi "${item.title}"?`)) {
    router.delete(`/super-admin/cms-promosi/${item.id}`);
  }
};
</script>
