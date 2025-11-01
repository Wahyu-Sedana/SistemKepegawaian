<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Slip Gaji - {{ $penggajian->user->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            padding: 20px;
        }

        .kop-surat {
            text-align: center;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 10px;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }

        .company-address {
            font-size: 11px;
            color: #666;
            line-height: 1.5;
        }

        .title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0;
            text-transform: uppercase;
        }

        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .info-table td {
            padding: 5px 0;
        }

        .info-table .label {
            width: 150px;
            font-weight: bold;
        }

        .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .salary-table th,
        .salary-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        .salary-table th {
            background-color: #2563eb;
            color: white;
            font-weight: bold;
        }

        .salary-table .total-row {
            background-color: #f3f4f6;
            font-weight: bold;
        }

        .salary-table .net-salary {
            background-color: #10b981;
            color: white;
            font-size: 14px;
        }

        .text-right {
            text-align: right;
        }

        .detail-section {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9fafb;
            border-radius: 5px;
        }

        .detail-section h4 {
            margin-bottom: 10px;
            color: #2563eb;
        }

        .detail-item {
            padding: 5px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .footer {
            margin-top: 40px;
            text-align: right;
        }

        .signature-box {
            display: inline-block;
            text-align: center;
            margin-top: 10px;
        }

        .signature-line {
            width: 200px;
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 5px;
        }
    </style>
</head>

<body>
    <!-- KOP SURAT -->
    <div class="kop-surat">
        <div class="logo">
            <!-- Ganti dengan logo perusahaan Anda -->
            <img src="{{ public_path('images/logo-perusahaan.png') }}" alt="Logo" style="width: 100%; height: 100%;">
        </div>
        <div class="company-name">PT. NAMA PERUSAHAAN ANDA</div>
        <div class="company-address">
            Jl. Contoh Alamat No. 123, Denpasar, Bali 80237<br>
            Telp: (0361) 1234567 | Email: info@perusahaan.com<br>
            Website: www.perusahaan.com
        </div>
    </div>

    <!-- TITLE -->
    <div class="title">Slip Gaji Karyawan</div>

    <!-- INFO KARYAWAN -->
    <table class="info-table">
        <tr>
            <td class="label">Nama Karyawan</td>
            <td>: {{ $penggajian->user->name }}</td>
        </tr>
        <tr>
            <td class="label">Periode</td>
            <td>: {{ \Carbon\Carbon::createFromFormat('Y-m', $penggajian->periode)->isoFormat('MMMM YYYY') }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Pembayaran</td>
            <td>: {{ $penggajian->tanggal_gaji->isoFormat('D MMMM YYYY') }}</td>
        </tr>
        <tr>
            <td class="label">Status</td>
            <td>: {{ $penggajian->status == 'paid' ? 'Sudah Dibayar' : 'Draft' }}</td>
        </tr>
    </table>

    <!-- TABEL GAJI -->
    <table class="salary-table">
        <thead>
            <tr>
                <th>Keterangan</th>
                <th class="text-right">Jumlah (IDR)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Gaji Pokok</td>
                <td class="text-right">{{ number_format($penggajian->gaji_pokok, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Tunjangan</td>
                <td class="text-right">{{ number_format($penggajian->tunjangan, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td>Total Penghasilan</td>
                <td class="text-right">
                    {{ number_format($penggajian->gaji_pokok + $penggajian->tunjangan, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Potongan</td>
                <td class="text-right">({{ number_format($penggajian->potongan, 0, ',', '.') }})</td>
            </tr>
            <tr class="net-salary">
                <td><strong>GAJI BERSIH (Take Home Pay)</strong></td>
                <td class="text-right"><strong>{{ number_format($penggajian->gaji_bersih, 0, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>

    <!-- DETAIL POTONGAN -->
    @if ($penggajian->detail_potongan)
        <div class="detail-section">
            <h4>Detail Potongan</h4>
            @php
                $summary = $penggajian->detail_potongan['summary'] ?? [];
                $keterlambatan = $penggajian->detail_potongan['keterlambatan'] ?? [];
                $tidakHadir = $penggajian->detail_potongan['tidak_hadir'] ?? [];
            @endphp

            <div class="detail-item">
                <strong>Jumlah Hari Kerja:</strong> {{ $summary['jumlah_hari_kerja'] ?? 0 }} hari
            </div>
            <div class="detail-item">
                <strong>Jumlah Hadir:</strong> {{ $summary['jumlah_hadir'] ?? 0 }} hari
            </div>
            <div class="detail-item">
                <strong>Jumlah Keterlambatan:</strong> {{ $summary['jumlah_keterlambatan'] ?? 0 }} kali
            </div>
            <div class="detail-item">
                <strong>Jumlah Tidak Hadir:</strong> {{ $summary['jumlah_tidak_hadir'] ?? 0 }} hari
            </div>
        </div>
    @endif

    <!-- FOOTER & TANDA TANGAN -->
    <div class="footer">
        <div class="signature-box">
            Denpasar, {{ now()->isoFormat('D MMMM YYYY') }}<br>
            HRD Manager
            <div class="signature-line">
                ( _________________________ )
            </div>
        </div>
    </div>

    <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #999;">
        Dokumen ini dicetak secara otomatis dan sah tanpa tanda tangan basah
    </div>
</body>

</html>
