<?php
// Token halaman untuk AJAX call satu kali pakai
$token = $pageToken ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Dokumen Rapor / Transkrip - SINTA-SaaS</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="/SINTA-SaaS/assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="/SINTA-SaaS/assets/css/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
            min-height: 100vh;
        }
        .hero-banner {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #fff;
            padding: 40px 0;
            border-bottom-left-radius: 24px;
            border-bottom-right-radius: 24px;
            margin-bottom: -60px;
        }
        .verify-card {
            background-color: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(226, 232, 240, 0.8);
            margin-bottom: 40px;
        }
        .badge-verified {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
            font-weight: 700;
            padding: 10px 20px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            box-shadow: 0 4px 6px -1px rgba(22, 163, 74, 0.1);
        }
        .detail-label {
            font-weight: 600;
            color: #64748b;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .detail-value {
            font-weight: 700;
            color: #0f172a;
            font-size: 1.05rem;
        }
        .table-grades {
            font-size: 0.85rem;
        }
        .table-grades th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 700;
            border-bottom: 2px solid #e2e8f0;
        }
        .divider-dashed {
            border-top: 2px dashed #e2e8f0;
            margin: 24px 0;
        }
    </style>
</head>
<body>

    <!-- Hero Banner Header -->
    <div class="hero-banner text-center">
        <div class="container">
            <h1 class="fw-extrabold text-white mb-2 fs-3"><i class="bi bi-shield-check text-warning-emphasis me-2"></i>SINTA-SaaS</h1>
            <p class="text-sky-100 fs-7 mb-0">Sistem Verifikasi Keaslian Dokumen Akademik Digital</p>
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="container" style="margin-top: 80px;">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                
                <div class="verify-card p-4 p-md-5">
                    
                    <!-- Loading View -->
                    <div id="loadingView" class="text-center py-5">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Memuat...</span>
                        </div>
                        <h5 class="fw-semibold">Memuat Data Verifikasi...</h5>
                        <p class="text-muted fs-7">Mohon tunggu, sistem sedang memverifikasi token keaslian dokumen secara aman.</p>
                    </div>

                    <!-- Error View -->
                    <div id="errorView" class="text-center py-5 d-none">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-1 mb-3"></i>
                        <h4 class="fw-bold" id="errorTitle">Verifikasi Gagal</h4>
                        <p class="text-muted fs-7" id="errorText">Dokumen tidak dapat diverifikasi atau token telah kedaluwarsa.</p>
                        <a href="javascript:location.reload()" class="btn btn-primary rounded-pill mt-3 px-4">
                            <i class="bi bi-arrow-clockwise me-1"></i> Coba Lagi
                        </a>
                    </div>

                    <!-- Main Data View (Akan dimasukkan secara dinamis oleh JavaScript jika verifikasi sukses) -->
                    <div id="dataViewContainer"></div>

                </div>
                
                <!-- Bottom Copyright -->
                <div class="text-center text-muted fs-9 mb-5">
                    &copy; <span id="valYear"></span> SINTA-SaaS. All rights reserved. | Security Verified Legality Page.
                </div>

            </div>
        </div>
    </div>

    <!-- Script to fetch dynamically with security -->
    <script>
        (function() {
            document.getElementById('valYear').innerText = new Date().getFullYear();
            
            fetch('/SINTA-SaaS/api/v1/verify-transkrip/data')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Response error dengan status ' + response.status);
                    }
                    return response.json();
                })
                .then(res => {
                    if (!res.success || !res.data) {
                        throw new Error(res.error || 'Gagal memuat data verifikasi.');
                    }
                    renderData(res.data);
                })
                .catch(err => {
                    showError('Verifikasi Gagal', err.message || 'Terjadi kesalahan saat menghubungi server.');
                });

            function showError(title, message) {
                document.getElementById('loadingView').classList.add('d-none');
                document.getElementById('dataViewContainer').innerHTML = '';
                document.getElementById('errorTitle').innerText = title;
                document.getElementById('errorText').innerText = message;
                document.getElementById('errorView').classList.remove('d-none');
            }

            function renderData(siswa) {
                let ttl = '-';
                if (siswa.tempat_lahir) {
                    ttl = siswa.tempat_lahir;
                    if (siswa.tanggal_lahir) {
                        const parts = siswa.tanggal_lahir.split('-');
                        if (parts.length === 3) {
                            ttl += ', ' + parts[2] + '-' + parts[1] + '-' + parts[0];
                        } else {
                            ttl += ', ' + siswa.tanggal_lahir;
                        }
                    }
                }
                
                const jk = siswa.jenis_kelamin === 'L' ? 'Laki-laki' : (siswa.jenis_kelamin === 'P' ? 'Perempuan' : '-');
                const tenant = siswa.tenant_info || {};

                // Process Grades
                const transkrip = {};
                const taMapping = [];
                
                if (siswa.transkrip_grades && siswa.transkrip_grades.length > 0) {
                    siswa.transkrip_grades.forEach(g => {
                        const mName = g.nama_mapel;
                        if (!transkrip[mName]) {
                            transkrip[mName] = {
                                s1: '-', s2: '-', s3: '-', s4: '-', s5: '-', s6: '-',
                                us: '-', ns: '-'
                            };
                        }
                        const sem = (g.semester || '').toLowerCase();
                        const ta = g.tahun_ajaran;
                        
                        if (!taMapping.includes(ta)) {
                            taMapping.push(ta);
                            taMapping.sort();
                        }
                        const taIndex = taMapping.indexOf(ta);
                        const baseSmt = (taIndex * 2) + 1;
                        
                        if (sem === 'ujian sekolah') {
                            transkrip[mName].us = Math.round(parseFloat(g.nilai_akhir));
                        } else {
                            const smtNum = (sem.includes('genap')) ? baseSmt + 1 : baseSmt;
                            if (smtNum >= 1 && smtNum <= 6) {
                                transkrip[mName]['s' + smtNum] = Math.round(parseFloat(g.nilai_akhir));
                            }
                        }
                    });
                }

                // Calculate NS (Nilai Sekolah)
                const subjectNames = Object.keys(transkrip);
                subjectNames.forEach(k => {
                    let sum = 0;
                    let count = 0;
                    for (let i = 1; i <= 6; i++) {
                        if (transkrip[k]['s' + i] !== '-') {
                            sum += transkrip[k]['s' + i];
                            count++;
                        }
                    }
                    if (count > 0) {
                        const rata = sum / count;
                        if (transkrip[k].us !== '-') {
                            transkrip[k].ns = Math.round((0.6 * rata) + (0.4 * transkrip[k].us));
                        } else {
                            transkrip[k].ns = Math.round(rata);
                        }
                    }
                });

                // Generate table rows HTML
                let tableRowsHtml = '';
                if (subjectNames.length > 0) {
                    let no = 1;
                    subjectNames.forEach(name => {
                        const d = transkrip[name];
                        tableRowsHtml += `
                            <tr>
                                <td class="text-center text-muted">${no++}</td>
                                <td class="fw-semibold text-dark">${escapeHtml(name)}</td>
                                <td class="text-center">${d.s1}</td>
                                <td class="text-center">${d.s2}</td>
                                <td class="text-center">${d.s3}</td>
                                <td class="text-center">${d.s4}</td>
                                <td class="text-center">${d.s5}</td>
                                <td class="text-center">${d.s6}</td>
                                <td class="text-center text-primary fw-semibold">${d.us}</td>
                                <td class="text-center bg-light fw-bold text-success fs-7">${d.ns}</td>
                            </tr>
                        `;
                    });
                } else {
                    tableRowsHtml = `
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">Tidak ada data transkrip nilai terdaftar.</td>
                        </tr>
                    `;
                }

                // Construct full HTML
                const fullHtml = `
                    <div id="dataView">
                        <!-- Verification Status Header -->
                        <div class="text-center mb-5">
                            <div class="badge-verified mb-3">
                                <i class="bi bi-patch-check-fill fs-5"></i>
                                <span>DOKUMEN TERVERIFIKASI ASLI</span>
                            </div>
                            <h2 class="fw-bold text-dark fs-4">Hasil Verifikasi Data Siswa</h2>
                            <p class="text-muted fs-7">Seluruh data yang ditampilkan di bawah ini telah dicocokkan langsung secara real-time dengan database server sekolah SINTA-SaaS.</p>
                        </div>

                        <!-- Student Profile Section -->
                        <h4 class="fw-bold text-dark border-bottom pb-2 mb-4 fs-6">
                            <i class="bi bi-person-badge text-primary me-2"></i>Identitas Peserta Didik
                        </h4>

                        <div class="row g-4 mb-4">
                            <div class="col-12 col-md-6">
                                <div class="detail-label">Nama Lengkap Siswa</div>
                                <div class="detail-value text-primary">${escapeHtml(siswa.nama_lengkap || '-')}</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="detail-label">NISN / NIS</div>
                                <div class="detail-value">${escapeHtml(siswa.nisn || '-')} / ${escapeHtml(siswa.nis || '-')}</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="detail-label">Tempat, Tanggal Lahir</div>
                                <div class="detail-value">${escapeHtml(ttl)}</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="detail-label">Jenis Kelamin / Agama</div>
                                <div class="detail-value">${escapeHtml(jk)} / ${escapeHtml(siswa.agama || '-')}</div>
                            </div>
                            <div class="col-12">
                                <div class="detail-label">Sekolah Penerbit Dokumen</div>
                                <div class="detail-value text-success">${escapeHtml(tenant.nama_sekolah || '-')} (NPSN: ${escapeHtml(tenant.npsn || '-')})</div>
                            </div>
                        </div>

                        <div class="divider-dashed"></div>

                        <!-- Grades Transcript Summary Section -->
                        <h4 class="fw-bold text-dark border-bottom pb-2 mb-4 fs-6">
                            <i class="bi bi-journal-bookmark-fill text-primary me-2"></i>Ringkasan Transkrip Nilai Akademik
                        </h4>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle table-grades">
                                <thead>
                                    <tr class="text-center">
                                        <th rowspan="2" width="5%" class="align-middle">No</th>
                                        <th rowspan="2" class="text-start align-middle">Mata Pelajaran</th>
                                        <th colspan="6">Nilai Rapor Smt</th>
                                        <th rowspan="2" width="10%" class="align-middle">US</th>
                                        <th rowspan="2" width="12%" class="align-middle">Nilai Sekolah</th>
                                    </tr>
                                    <tr class="text-center">
                                        <th width="7%">1</th>
                                        <th width="7%">2</th>
                                        <th width="7%">3</th>
                                        <th width="7%">4</th>
                                        <th width="7%">5</th>
                                        <th width="7%">6</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    \${tableRowsHtml}
                                </tbody>
                            </table>
                        </div>

                        <div class="divider-dashed"></div>

                        <!-- Footer Info -->
                        <div class="row align-items-center">
                            <div class="col-12 col-md-8 text-center text-md-start mb-3 mb-md-0">
                                <p class="text-muted fs-8 mb-0">
                                    <i class="bi bi-info-circle me-1"></i>Halaman ini diterbitkan sebagai sarana legalitas digital untuk memvalidasi kesesuaian data fisik dengan database SINTA-SaaS secara langsung.
                                </p>
                            </div>
                            <div class="col-12 col-md-4 text-center text-md-end">
                                <a href="javascript:window.print()" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                    <i class="bi bi-printer me-1"></i> Cetak Halaman Ini
                                </a>
                            </div>
                        </div>
                    </div>
                `;

                document.getElementById('dataViewContainer').innerHTML = fullHtml;
                document.getElementById('loadingView').classList.add('d-none');
            }

            function escapeHtml(str) {
                if (!str) return '';
                return str
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }
        })();
    </script>
</body>
</html>
