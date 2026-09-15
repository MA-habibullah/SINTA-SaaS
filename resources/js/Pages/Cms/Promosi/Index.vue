<template>
  <AppLayout title="CMS Landing Page & Marketing Builder">
    <div class="space-y-6">
      <!-- Header Section -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
              Super Admin Control
            </span>
            <span class="text-slate-400">&bull;</span>
            <span class="text-xs font-semibold text-slate-500">Landing Page & Portal Publik</span>
          </div>
          <h1 class="text-2xl font-black text-slate-900 tracking-tight">Manajemen CMS Landing Page</h1>
          <p class="text-xs text-slate-500 mt-0.5">Kelola navigasi menu, banner hero, showcase fitur unggulan, keuntungan aplikasi, paket harga, dan FAQ untuk landing page utama (<code class="text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded font-mono text-[11px]">http://sinta.test:8080/</code>).</p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap sm:flex-nowrap">
          <a href="/" target="_blank" class="btn btn-sm btn-light border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 flex items-center gap-2 transition">
            <i class="bi bi-box-arrow-up-right text-blue-600"></i>
            <span>Lihat Landing Page Publik</span>
          </a>
          <button @click="openCreateModal" class="btn btn-sm bg-blue-600 hover:bg-blue-700 text-white rounded-xl px-4 py-2.5 text-xs font-bold shadow-md shadow-blue-500/20 flex items-center gap-2 transition">
            <i class="bi bi-plus-lg"></i>
            <span>Tambah Konten CMS</span>
          </button>
        </div>
      </div>

      <!-- Quick Stats Row -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
          <span class="text-[11px] text-slate-500 font-semibold flex items-center gap-1.5">
            <i class="bi bi-layers text-slate-400"></i> Total Konten
          </span>
          <div class="text-2xl font-black text-slate-900 mt-1">{{ stats.totalItems || 0 }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
          <span class="text-[11px] text-slate-500 font-semibold flex items-center gap-1.5">
            <i class="bi bi-compass text-blue-500"></i> Menu Navigasi
          </span>
          <div class="text-2xl font-black text-blue-600 mt-1">{{ stats.navCount || grouped.nav_menu?.length || 0 }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
          <span class="text-[11px] text-slate-500 font-semibold flex items-center gap-1.5">
            <i class="bi bi-grid-fill text-indigo-500"></i> Fitur Unggulan
          </span>
          <div class="text-2xl font-black text-indigo-600 mt-1">{{ stats.featureCount || grouped.features?.length || 0 }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
          <span class="text-[11px] text-slate-500 font-semibold flex items-center gap-1.5">
            <i class="bi bi-shield-check text-purple-500"></i> Keuntungan
          </span>
          <div class="text-2xl font-black text-purple-600 mt-1">{{ stats.benefitsCount || grouped.benefits?.length || 0 }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
          <span class="text-[11px] text-slate-500 font-semibold flex items-center gap-1.5">
            <i class="bi bi-box-seam text-emerald-500"></i> Paket & Pricing
          </span>
          <div class="text-2xl font-black text-emerald-600 mt-1">{{ stats.pricingCount || grouped.pricing?.length || 0 }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
          <span class="text-[11px] text-slate-500 font-semibold flex items-center gap-1.5">
            <i class="bi bi-check-circle-fill text-emerald-500"></i> Konten Aktif
          </span>
          <div class="text-2xl font-black text-emerald-600 mt-1">{{ stats.activeItems || 0 }}</div>
        </div>
      </div>

      <!-- Horizontal Tabs Navigation & Toolbar -->
      <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-3 space-y-3">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
          <!-- Scrollable Navtabs -->
          <div class="flex items-center overflow-x-auto gap-1.5 pb-1 select-none no-scrollbar">
            <button class="font-bold px-3.5 py-2 rounded-xl text-xs transition shrink-0 flex items-center gap-2" 
                    :class="activeSection === 'all' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                    @click="activeSection = 'all'">
              <i class="bi bi-grid-fill"></i> Semua ({{ stats.totalItems || 0 }})
            </button>
            <button class="font-bold px-3.5 py-2 rounded-xl text-xs transition shrink-0 flex items-center gap-2" 
                    :class="activeSection === 'nav_menu' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                    @click="activeSection = 'nav_menu'">
              <i class="bi bi-compass-fill text-blue-400"></i> Menu Navigasi ({{ grouped.nav_menu?.length || 0 }})
            </button>
            <button class="font-bold px-3.5 py-2 rounded-xl text-xs transition shrink-0 flex items-center gap-2" 
                    :class="activeSection === 'hero' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                    @click="activeSection = 'hero'">
              <i class="bi bi-stars text-amber-400"></i> Hero Banner ({{ grouped.hero?.length || 0 }})
            </button>
            <button class="font-bold px-3.5 py-2 rounded-xl text-xs transition shrink-0 flex items-center gap-2" 
                    :class="activeSection === 'features' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                    @click="activeSection = 'features'">
              <i class="bi bi-grid-3x3-gap-fill text-indigo-400"></i> Fitur Unggulan ({{ grouped.features?.length || 0 }})
            </button>
            <button class="font-bold px-3.5 py-2 rounded-xl text-xs transition shrink-0 flex items-center gap-2" 
                    :class="activeSection === 'benefits' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                    @click="activeSection = 'benefits'">
              <i class="bi bi-shield-check text-purple-400"></i> Keuntungan Aplikasi ({{ grouped.benefits?.length || 0 }})
            </button>
            <button class="font-bold px-3.5 py-2 rounded-xl text-xs transition shrink-0 flex items-center gap-2" 
                    :class="activeSection === 'pricing' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                    @click="activeSection = 'pricing'">
              <i class="bi bi-gift-fill text-emerald-400"></i> Paket & Trial ({{ grouped.pricing?.length || 0 }})
            </button>
            <button class="font-bold px-3.5 py-2 rounded-xl text-xs transition shrink-0 flex items-center gap-2" 
                    :class="activeSection === 'faq' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'" 
                    @click="activeSection = 'faq'">
              <i class="bi bi-question-circle text-amber-500"></i> FAQ ({{ grouped.faq?.length || 0 }})
            </button>
          </div>

          <!-- Search Filter in Toolbar -->
          <div class="relative w-full lg:w-64 shrink-0">
            <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input v-model="searchQuery" type="text" placeholder="Cari judul/konten..."
                   class="w-full pl-9 pr-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white" />
          </div>
        </div>
      </div>

      <!-- SECTION: MENU NAVIGASI KHUSUS (Tabel Terstruktur) jika activeSection === 'nav_menu' -->
      <div v-if="activeSection === 'nav_menu'" class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
          <div>
            <h3 class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
              <i class="bi bi-compass text-blue-600"></i> Struktur Menu Navigasi Landing Page
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">Daftar tautan menu yang tampil di header dan drawer mobile landing page.</p>
          </div>
          <button @click="openCreateModalWithSection('nav_menu')" class="btn btn-sm bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 rounded-xl px-3 py-1.5 text-xs font-bold flex items-center gap-1.5">
            <i class="bi bi-plus-lg"></i> Tambah Menu
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-extrabold text-slate-600 uppercase tracking-wider">
                <th class="py-3 px-4 w-16 text-center">Urutan</th>
                <th class="py-3 px-4">Label Menu</th>
                <th class="py-3 px-4">Tautan / Target (URL)</th>
                <th class="py-3 px-4">Ikon & Badge</th>
                <th class="py-3 px-4 text-center">Status</th>
                <th class="py-3 px-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
              <tr v-for="(item, idx) in filteredPromotions" :key="item.id" class="hover:bg-slate-50/60 transition">
                <td class="py-3 px-4 text-center">
                  <div class="flex items-center justify-center gap-1">
                    <button @click="moveOrder(item, 'up')" :disabled="idx === 0" class="w-6 h-6 rounded bg-slate-100 hover:bg-slate-200 text-slate-600 disabled:opacity-30 flex items-center justify-center text-[10px]" title="Geser ke Atas">
                      <i class="bi bi-chevron-up"></i>
                    </button>
                    <span class="font-bold text-slate-700 w-5">{{ item.order_num }}</span>
                    <button @click="moveOrder(item, 'down')" :disabled="idx === filteredPromotions.length - 1" class="w-6 h-6 rounded bg-slate-100 hover:bg-slate-200 text-slate-600 disabled:opacity-30 flex items-center justify-center text-[10px]" title="Geser ke Bawah">
                      <i class="bi bi-chevron-down"></i>
                    </button>
                  </div>
                </td>
                <td class="py-3 px-4 font-bold text-slate-900">
                  <div class="flex items-center gap-2">
                    <i :class="['bi', item.icon_class || 'bi-link-45deg', 'text-blue-600 text-sm']"></i>
                    <span>{{ item.title }}</span>
                  </div>
                  <p v-if="item.subtitle" class="text-[11px] text-slate-400 font-normal mt-0.5">{{ item.subtitle }}</p>
                </td>
                <td class="py-3 px-4">
                  <code class="px-2 py-1 rounded bg-slate-100 font-mono text-[11px] text-slate-700">{{ item.cta_link || item.content || '#fitur' }}</code>
                  <span v-if="item.content_json?.target === '_blank'" class="ml-1.5 px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                    Tab Baru
                  </span>
                </td>
                <td class="py-3 px-4">
                  <span v-if="item.badge_text" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                    {{ item.badge_text }}
                  </span>
                  <span v-else class="text-slate-400">-</span>
                </td>
                <td class="py-3 px-4 text-center">
                  <button @click="toggleItemStatus(item)" 
                          :class="['px-2.5 py-1 rounded-xl text-[11px] font-bold inline-flex items-center gap-1.5 transition',
                                   item.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200']">
                    <i :class="['bi', item.is_active ? 'bi-check-circle-fill text-emerald-600' : 'bi-dash-circle']"></i>
                    <span>{{ item.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                  </button>
                </td>
                <td class="py-3 px-4 text-right space-x-1.5">
                  <button @click="openEditModal(item)" class="p-1.5 rounded-lg text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition" title="Edit">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button @click="deleteItem(item)" class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition" title="Hapus">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- SECTION: GRID KONTEN UTAMA (Features, Benefits, Hero, Pricing, FAQ, dll) -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <div v-for="item in filteredPromotions" :key="item.id" 
             class="bg-white rounded-3xl border border-slate-200/80 shadow-xs hover:shadow-md transition-all p-5 flex flex-col justify-between group">
          
          <div>
            <!-- Section Badge & Status Toggle -->
            <div class="flex items-center justify-between gap-2 mb-3">
              <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider',
                             item.section_key === 'hero' ? 'bg-amber-100 text-amber-800' :
                             (item.section_key === 'nav_menu' ? 'bg-sky-100 text-sky-800' :
                             (item.section_key === 'features' ? 'bg-indigo-100 text-indigo-800' :
                             (item.section_key === 'benefits' ? 'bg-purple-100 text-purple-800' :
                             (item.section_key === 'pricing' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700'))))]">
                {{ getSectionLabel(item.section_key) }}
              </span>

              <div class="flex items-center gap-1.5">
                <span class="text-[10px] text-slate-400 font-bold">#{{ item.order_num }}</span>
                <button @click="toggleItemStatus(item)" 
                        :class="['px-2 py-0.5 rounded-lg text-[11px] font-bold flex items-center gap-1 transition',
                                 item.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200']"
                        :title="item.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'">
                  <i :class="['bi', item.is_active ? 'bi-check-circle-fill text-emerald-600' : 'bi-dash-circle']"></i>
                  <span>{{ item.is_active ? 'Aktif' : 'Draft' }}</span>
                </button>
              </div>
            </div>

            <!-- Icon & Title -->
            <div class="flex items-start gap-3">
              <div :class="['w-10 h-10 rounded-2xl flex items-center justify-center text-lg shrink-0 transition transform group-hover:scale-105',
                           item.section_key === 'benefits' ? 'bg-purple-50 border border-purple-200 text-purple-600' :
                           (item.section_key === 'features' ? 'bg-indigo-50 border border-indigo-200 text-indigo-600' : 'bg-blue-50 border border-blue-200 text-blue-600')]">
                <i :class="['bi', item.icon_class || 'bi-stars']"></i>
              </div>
              <div class="grow min-w-0">
                <h3 class="font-extrabold text-sm text-slate-900 leading-snug truncate">{{ item.title }}</h3>
                <p v-if="item.badge_text" class="text-[11px] font-bold text-blue-600 mt-0.5">{{ item.badge_text }}</p>
              </div>
            </div>

            <!-- Subtitle / Content Snippet -->
            <p class="text-xs text-slate-500 mt-3 line-clamp-3 leading-relaxed">
              {{ item.subtitle || item.content || 'Tidak ada deskripsi tambahan.' }}
            </p>

            <!-- Benefits Highlights Pill List (if benefits) -->
            <div v-if="item.content_json?.highlights && item.content_json.highlights.length > 0" class="mt-3 space-y-1">
              <div v-for="(h, hIdx) in item.content_json.highlights.slice(0, 3)" :key="hIdx" class="text-[11px] text-slate-600 flex items-center gap-1.5">
                <i class="bi bi-check2 text-emerald-500 font-bold"></i>
                <span class="truncate">{{ h }}</span>
              </div>
            </div>

            <!-- CTA Link Preview -->
            <div v-if="item.cta_link || item.cta_text" class="mt-3 p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-[11px] text-slate-600 flex items-center justify-between">
              <span class="font-bold text-slate-700 truncate max-w-[120px]">{{ item.cta_text || 'Tautan' }}</span>
              <span class="text-blue-600 truncate max-w-[150px] font-mono">{{ item.cta_link }}</span>
            </div>
          </div>

          <!-- Card Action Buttons -->
          <div class="flex items-center justify-between pt-4 mt-4 border-t border-slate-100">
            <div class="flex items-center gap-1">
              <button @click="moveOrder(item, 'up')" class="w-6 h-6 rounded bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-[10px]" title="Geser ke Atas">
                <i class="bi bi-arrow-up"></i>
              </button>
              <button @click="moveOrder(item, 'down')" class="w-6 h-6 rounded bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-[10px]" title="Geser ke Bawah">
                <i class="bi bi-arrow-down"></i>
              </button>
            </div>
            <div class="flex items-center gap-2">
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
      </div>

      <!-- Empty State -->
      <div v-if="filteredPromotions.length === 0" class="bg-white rounded-3xl p-12 text-center border border-slate-200/80">
        <i class="bi bi-stars text-4xl text-slate-300"></i>
        <h3 class="text-base font-bold text-slate-700 mt-2">Belum Ada Konten di Kategori Ini</h3>
        <p class="text-xs text-slate-400 mt-1">Tambahkan konten promosi untuk kategori ini agar tampil di landing page publik.</p>
        <button @click="openCreateModal" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-xl text-xs font-bold shadow-md">
          Tambah Sekarang
        </button>
      </div>

      <!-- MODAL FORM (Teleport to Body) -->
      <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
          <div class="relative bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl border border-slate-100 text-slate-800 my-8">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
              <div>
                <h3 class="text-lg font-black text-slate-900">{{ isEditing ? 'Edit Konten Landing Page' : 'Tambah Konten Landing Page Baru' }}</h3>
                <p class="text-xs text-slate-500">Materi ini akan langsung terhubung dan dirender pada landing page publik SINTA.</p>
              </div>
              <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-sm font-bold">
                &times;
              </button>
            </div>

            <!-- Modal Form -->
            <form @submit.prevent="savePromoItem" class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <!-- Section Key (SearchableSelect) -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Kategori / Penempatan <span class="text-red-500">*</span></label>
                  <SearchableSelect 
                    v-model="form.section_key" 
                    :options="sectionCategoryOptions"
                    placeholder="-- Pilih Kategori Konten --"
                    search-placeholder="Cari kategori..."
                  />
                </div>

                <!-- Badge Text -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Teks Badge / Tagline Kecil</label>
                  <input v-model="form.badge_text" type="text" placeholder="Misal: 16 Modul / Keamanan Data / Populer"
                         class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white" />
                </div>

                <!-- Title -->
                <div class="md:col-span-2">
                  <label class="block text-xs font-bold text-slate-700 mb-1">
                    {{ form.section_key === 'nav_menu' ? 'Label Menu Navigasi' : (form.section_key === 'faq' ? 'Pertanyaan FAQ' : 'Judul / Headline Utama') }} <span class="text-red-500">*</span>
                  </label>
                  <input v-model="form.title" type="text" required 
                         :placeholder="form.section_key === 'nav_menu' ? 'Misal: Fitur Unggulan' : 'Judul konten atau headline...'"
                         class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white" />
                </div>

                <!-- Subtitle / Deskripsi Singkat -->
                <div class="md:col-span-2">
                  <label class="block text-xs font-bold text-slate-700 mb-1">
                    {{ form.section_key === 'faq' ? 'Jawaban FAQ' : 'Subjudul / Deskripsi Singkat' }}
                  </label>
                  <textarea v-model="form.subtitle" rows="3" placeholder="Penjelasan singkat konten..."
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white"></textarea>
                </div>

                <!-- Special: Benefits Highlights (if section_key === 'benefits') -->
                <div v-if="form.section_key === 'benefits'" class="md:col-span-2">
                  <label class="block text-xs font-bold text-slate-700 mb-1">Poin Keunggulan (Pisahkan dengan Koma)</label>
                  <input v-model="highlightsInput" type="text" placeholder="Misal: Dedicated Schema PostgreSQL, Zero Leakage, Enkripsi AES-256"
                         class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white" />
                  <span class="text-[10px] text-slate-400 mt-0.5 block">Poin-poin ini akan ditampilkan sebagai daftar checklist pada kartu keuntungan.</span>
                </div>

                <!-- Icon Class -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Bootstrap Icon Class</label>
                  <div class="flex items-center gap-2">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-blue-600 shrink-0">
                      <i :class="['bi', form.icon_class || 'bi-stars', 'text-base']"></i>
                    </div>
                    <input v-model="form.icon_class" type="text" placeholder="bi-grid-fill / bi-shield-check"
                           class="grow px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white" />
                  </div>
                </div>

                <!-- Order Num -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Urutan Tampil (Order)</label>
                  <input v-model="form.order_num" type="number" min="0" placeholder="1"
                         class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white" />
                </div>

                <!-- CTA Text (if applicable) -->
                <div v-if="form.section_key !== 'faq'">
                  <label class="block text-xs font-bold text-slate-700 mb-1">Teks Tombol Aksi (CTA)</label>
                  <input v-model="form.cta_text" type="text" placeholder="Misal: Coba Gratis / Pelajari"
                         class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white" />
                </div>

                <!-- CTA Link (URL / Anchor) -->
                <div :class="form.section_key === 'faq' ? 'md:col-span-2' : ''">
                  <label class="block text-xs font-bold text-slate-700 mb-1">
                    {{ form.section_key === 'nav_menu' ? 'Tautan Menu (Anchor / URL)' : 'Target Link Tombol (URL)' }}
                  </label>
                  <input v-model="form.cta_link" type="text" 
                         :placeholder="form.section_key === 'nav_menu' ? '#fitur / #keuntungan / #paket' : '/daftar-sekolah'"
                         class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none focus:bg-white font-mono" />
                </div>

                <!-- Active Toggle -->
                <div class="md:col-span-2 pt-2">
                  <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-700">
                    <input v-model="form.is_active" type="checkbox" class="rounded text-blue-600 focus:ring-blue-500 w-4 h-4" />
                    <span>Aktifkan langsung di Landing Page Publik</span>
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
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useForm, router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  promotions: Array,
  grouped: Object,
  stats: Object,
  sectionCategories: Array,
});

const activeSection = ref('all');
const searchQuery = ref('');
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const highlightsInput = ref('');

const sectionCategoryOptions = computed(() => {
  if (props.sectionCategories && props.sectionCategories.length > 0) {
    return props.sectionCategories;
  }
  return [
    { id: 'nav_menu',     nama: 'Menu Navigasi Header',      subLabel: 'Menu & Tautan pada Bar Navigasi' },
    { id: 'hero',         nama: 'Hero Banner Utama',         subLabel: 'Headline, Subtitle & CTA' },
    { id: 'features',     nama: 'Fitur Unggulan (Modul)',    subLabel: 'Showcase 16 Modul Aplikasi' },
    { id: 'benefits',     nama: 'Keuntungan Aplikasi',       subLabel: 'Keunggulan Arsitektur & Manfaat' },
    { id: 'pricing',      nama: 'Paket & Free Trial',        subLabel: 'Paket Berlangganan & Uji Coba' },
    { id: 'faq',          nama: 'FAQ (Tanya Jawab)',         subLabel: 'Pertanyaan Umum' },
    { id: 'testimonials', nama: 'Testimoni Sekolah',        subLabel: 'Ulasan Pengguna' },
    { id: 'cta',          nama: 'Promotional CTA Banner',    subLabel: 'Ajakan Daftar Bawah Halaman' },
  ];
});

const form = useForm({
  section_key: 'nav_menu',
  title: '',
  subtitle: '',
  content: '',
  content_json: null,
  badge_text: '',
  icon_class: 'bi bi-stars',
  image_url: '',
  cta_text: '',
  cta_link: '',
  is_active: true,
  order_num: 1,
});

const getSectionLabel = (key) => {
  const match = sectionCategoryOptions.value.find(c => c.id === key);
  return match ? match.nama : key;
};

const filteredPromotions = computed(() => {
  let list = props.promotions || [];
  if (activeSection.value !== 'all') {
    list = list.filter(p => p.section_key === activeSection.value);
  }
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(p => 
      (p.title && p.title.toLowerCase().includes(q)) ||
      (p.subtitle && p.subtitle.toLowerCase().includes(q)) ||
      (p.badge_text && p.badge_text.toLowerCase().includes(q))
    );
  }
  return list;
});

const openCreateModal = () => {
  isEditing.value = false;
  editingId.value = null;
  form.reset();
  form.section_key = activeSection.value === 'all' ? 'features' : activeSection.value;
  form.is_active = true;
  form.order_num = (props.grouped?.[form.section_key]?.length || 0) + 1;
  highlightsInput.value = '';
  showModal.value = true;
};

const openCreateModalWithSection = (sectionKey) => {
  activeSection.value = sectionKey;
  openCreateModal();
};

const openEditModal = (item) => {
  isEditing.value = true;
  editingId.value = item.id;
  form.section_key = item.section_key;
  form.title = item.title;
  form.subtitle = item.subtitle || '';
  form.content = item.content || '';
  form.content_json = item.content_json || null;
  form.badge_text = item.badge_text || '';
  form.icon_class = item.icon_class || 'bi bi-stars';
  form.image_url = item.image_url || '';
  form.cta_text = item.cta_text || '';
  form.cta_link = item.cta_link || '';
  form.is_active = Boolean(item.is_active);
  form.order_num = item.order_num || 1;

  if (item.content_json?.highlights && Array.isArray(item.content_json.highlights)) {
    highlightsInput.value = item.content_json.highlights.join(', ');
  } else {
    highlightsInput.value = '';
  }

  showModal.value = true;
};

const savePromoItem = () => {
  // If highlights input filled for benefits
  if (form.section_key === 'benefits' && highlightsInput.value.trim()) {
    const parts = highlightsInput.value.split(',').map(s => s.trim()).filter(Boolean);
    form.content_json = {
      ...(form.content_json || {}),
      highlights: parts,
    };
  }

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

const moveOrder = async (item, direction) => {
  const currentList = [...(props.promotions || [])].filter(p => p.section_key === item.section_key);
  const currentIdx = currentList.findIndex(p => p.id === item.id);
  if (currentIdx < 0) return;

  const targetIdx = direction === 'up' ? currentIdx - 1 : currentIdx + 1;
  if (targetIdx < 0 || targetIdx >= currentList.length) return;

  const otherItem = currentList[targetIdx];
  const tempOrder = item.order_num;
  item.order_num = otherItem.order_num;
  otherItem.order_num = tempOrder;

  try {
    await axios.post('/super-admin/cms-promosi/reorder', {
      items: [
        { id: item.id, order: item.order_num },
        { id: otherItem.id, order: otherItem.order_num },
      ],
    });
    router.reload({ only: ['promotions', 'grouped'] });
  } catch (err) {
    alert('Gagal memperbarui urutan: ' + (err.response?.data?.error || err.message));
  }
};

const deleteItem = (item) => {
  if (confirm(`Hapus konten promosi "${item.title}"?`)) {
    router.delete(`/super-admin/cms-promosi/${item.id}`);
  }
};
</script>
