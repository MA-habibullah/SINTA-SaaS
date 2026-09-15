<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INVOICE #{{ $invoice->invoice_number }} — SINTA SaaS</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #1e293b;
            line-height: 1.5;
            margin: 0;
            padding: 30px;
            background: #f8fafc;
        }
        .invoice-card {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 24px;
            margin-bottom: 28px;
        }
        .brand h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 900;
            color: #2563eb;
            letter-spacing: -0.5px;
        }
        .brand p {
            margin: 4px 0 0 0;
            font-size: 12px;
            color: #64748b;
        }
        .invoice-meta {
            text-align: right;
        }
        .invoice-meta h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
        }
        .invoice-meta p {
            margin: 4px 0 0 0;
            font-size: 13px;
            color: #64748b;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 8px;
        }
        .status-unpaid { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .status-paid { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .grid-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }
        .info-box h4 {
            margin: 0 0 8px 0;
            font-size: 12px;
            text-transform: uppercase;
            color: #94a3b8;
            letter-spacing: 0.5px;
        }
        .info-box p {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: #334155;
        }
        .table-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 32px;
        }
        .table-items th {
            background: #f8fafc;
            padding: 12px 16px;
            font-size: 12px;
            font-weight: 700;
            text-align: left;
            color: #475569;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }
        .table-items td {
            padding: 16px;
            font-size: 13px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }
        .total-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 32px;
        }
        .total-box {
            width: 280px;
            background: #f8fafc;
            border-radius: 12px;
            padding: 16px;
            border: 1px solid #e2e8f0;
        }
        .total-box .row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #64748b;
            margin-bottom: 8px;
        }
        .total-box .row.grand {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            margin-bottom: 0;
        }
        .payment-instructions {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 20px;
            font-size: 12px;
            color: #1e40af;
        }
        .payment-instructions h4 {
            margin: 0 0 8px 0;
            font-size: 13px;
            font-weight: 800;
        }
        .footer {
            margin-top: 32px;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
        }
        .print-btn {
            display: block;
            margin: 0 auto 20px auto;
            padding: 10px 24px;
            background: #2563eb;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
        }
        @media print {
            body { padding: 0; background: #ffffff; }
            .invoice-card { border: none; box-shadow: none; padding: 0; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">🖨️ Cetak / Unduh PDF</button>

    <div class="invoice-card">
        <div class="header">
            <div class="brand">
                <h1>SINTA SaaS</h1>
                <p>Sistem Inti Akademik Terpadu Multi-Tenant</p>
                <p>PT SINTA EDUKASI TEKNOLOGI INDONESIA</p>
            </div>
            <div class="invoice-meta">
                <h2>INVOICE TAGIHAN</h2>
                <p>No: <strong>{{ $invoice->invoice_number }}</strong></p>
                <p>Tanggal: {{ date('d M Y', strtotime($invoice->created_at)) }}</p>
                <span class="status-badge {{ $invoice->status === 'PAID' ? 'status-paid' : 'status-unpaid' }}">
                    {{ $invoice->status === 'PAID' ? 'LUNAS / PAID' : 'BELUM DIBAYAR (UNPAID)' }}
                </span>
            </div>
        </div>

        <div class="grid-info">
            <div class="info-box">
                <h4>Ditagihkan Kepada:</h4>
                <p>{{ $tenant->nama_sekolah ?? 'Sekolah Pelanggan' }}</p>
                <p style="font-size: 12px; color: #64748b; font-weight: 400; margin-top: 4px;">NPSN: {{ $tenant->npsn ?? '-' }}</p>
                <p style="font-size: 12px; color: #64748b; font-weight: 400;">{{ $tenant->alamat ?? '-' }}</p>
            </div>
            <div class="info-box" style="text-align: right;">
                <h4>Jatuh Tempo Pembayaran:</h4>
                <p style="color: #dc2626; font-weight: 800;">{{ date('d F Y', strtotime($invoice->due_date)) }}</p>
                <p style="font-size: 12px; color: #64748b; font-weight: 400; margin-top: 4px;">Siklus: {{ ucfirst($tenant->billing_cycle ?? 'Monthly') }}</p>
            </div>
        </div>

        <table class="table-items">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Deskripsi Layanan SaaS</th>
                    <th style="text-align: center; width: 120px;">Periode</th>
                    <th style="text-align: right; width: 150px;">Total (IDR)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>
                        <strong>Lisensi Paket {{ $invoice->nama_paket }}</strong><br>
                        <span style="font-size: 11px; color: #64748b;">Akses modul Akademik, Presensi GPS, Keuangan, BK, Perpustakaan, CBT & Multi-Schema Database.</span>
                    </td>
                    <td style="text-align: center;">{{ $invoice->periode }}</td>
                    <td style="text-align: right; font-weight: 700;">Rp {{ number_format($invoice->nominal, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="total-row">
            <div class="total-box">
                <div class="row">
                    <span>Subtotal</span>
                    <span>Rp {{ number_format($invoice->nominal, 0, ',', '.') }}</span>
                </div>
                <div class="row">
                    <span>PPN (0% Bebas Pajak Edukasi)</span>
                    <span>Rp 0</span>
                </div>
                <div class="row grand">
                    <span>Total Tagihan</span>
                    <span>Rp {{ number_format($invoice->nominal, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="payment-instructions">
            <h4>Petunjuk Pembayaran Transfer Bank:</h4>
            <p style="margin: 0 0 4px 0;">Silakan transfer tepat sesuai nominal ke rekening resmi pengelola SaaS:</p>
            <p style="margin: 0; font-weight: 700;">• Bank Central Asia (BCA): 8830-1928-3900 a.n PT SINTA EDUKASI TEKNOLOGI</p>
            <p style="margin: 4px 0 0 0; font-size: 11px;">Setelah melakukan pembayaran, unggah bukti bayar melalui menu <strong>Sistem & Manajemen &gt; Billing Langganan</strong> atau konfirmasi melalui WhatsApp Admin: <strong>0812-3456-7890</strong>.</p>
        </div>

        <div class="footer">
            Invoice ini diterbitkan secara otomatis oleh Sistem Inti Akademik SINTA dan sah tanpa tanda tangan basah.
        </div>
    </div>
</body>
</html>
