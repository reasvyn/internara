<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <title>{{ $handbook->title }}</title>
    <style>
        @page {
            margin: 2cm 2cm 2.5cm 2cm;
            footer: page-footer;
        }
        body {
            font-family: 'Times New Roman', serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #1a1a1a;
        }
        .header {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 8px;
            margin-bottom: 20px;
        }
        .header .agency {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .header .school {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 3px 0;
            color: #1e3a8a;
        }
        .header .meta {
            font-size: 9pt;
            color: #4b5563;
            margin: 0;
            line-height: 1.4;
        }
        .doc-title {
            text-align: center;
            margin: 25px 0 20px 0;
        }
        .doc-title h1 {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 5px 0;
            text-decoration: underline;
        }
        .doc-title .code {
            font-size: 10pt;
            color: #4b5563;
            margin: 0;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10pt;
        }
        .meta-table td {
            padding: 4px 6px;
            vertical-align: top;
        }
        .meta-table .label {
            width: 25%;
            font-weight: bold;
            color: #374151;
        }
        .meta-table .separator {
            width: 2%;
        }
        .content-body {
            text-align: justify;
            font-size: 10.5pt;
            line-height: 1.6;
        }
        .content-body h2 {
            font-size: 12pt;
            font-weight: bold;
            margin-top: 18px;
            margin-bottom: 6px;
            color: #1f2937;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 3px;
        }
        .content-body h3 {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 12px;
            margin-bottom: 4px;
            color: #374151;
        }
        .content-body p {
            margin: 0 0 8px 0;
        }
        .content-body ul,
        .content-body ol {
            margin: 4px 0 10px 20px;
            padding: 0;
        }
        .content-body li {
            margin-bottom: 4px;
        }
        .signatures {
            margin-top: 40px;
            width: 100%;
        }
        .signatures table {
            width: 100%;
            border: none;
        }
        .signatures td {
            border: none;
            text-align: center;
            vertical-align: top;
            width: 50%;
            font-size: 10pt;
        }
        .sign-space {
            height: 60px;
        }
        .footer-note {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px dashed #9ca3af;
            font-size: 8.5pt;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <p class="agency">Pemerintah Provinsi Jawa Tengah &bull; Dinas Pendidikan dan Kebudayaan</p>
        <p class="school">{{ $school->name() ?: 'SMK NEGERI 2 KLATEN' }}</p>
        <p class="meta">
            {{ $school->address() ?: 'Jl. Sekarsuli - Senden, Kec. Ngawen, Kabupaten Klaten, Jawa Tengah 57466' }}<br />
            Telepon: {{ $school->phone() ?: '(0272) 3354021' }} &bull; Website: {{ $school->website() ?: 'https://www.smkn2klaten.sch.id' }} &bull;
            Email: {{ $school->email() ?: 'smkn2@smkn2klaten.sch.id' }}
        </p>
    </div>

    <div class="doc-title">
        <h1>{{ $handbook->title }}</h1>
        <p class="code">
            Dokumen Resmi Panduan Vokasi &bull; Versi {{ $handbook->version ?? 1 }}.0 &bull; Tahun Ajaran 2026/2027
        </p>
    </div>

    <table class="meta-table">
        <tr>
            <td class="label">Klasifikasi Dokumen</td>
            <td class="separator">:</td>
            <td>Pedoman Pelaksanaan Resmi Praktik Kerja Lapangan (PKL)</td>
        </tr>
        <tr>
            <td class="label">Status Berkas</td>
            <td class="separator">:</td>
            <td>Dokumen Resmi Terverifikasi Tim Pokja PKL</td>
        </tr>
        <tr>
            <td class="label">Lingkup Penerapan</td>
            <td class="separator">:</td>
            <td>Seluruh Peserta Didik & DUDI Mitra SMK Negeri 2 Klaten</td>
        </tr>
    </table>

    <div class="content-body">
        @if (! empty($handbook->content))
            {!! nl2br(e($handbook->content)) !!}
        @else
            <h2>BAB I: KETENTUAN UMUM</h2>
            <p>
                Praktik Kerja Lapangan (PKL) merupakan program pembelajaran intrakurikuler wajib bagi seluruh peserta
                didik SMK Negeri 2 Klaten yang diselenggarakan di dunia usaha, dunia industri, dan dunia kerja (DUDIKAS)
                guna menginternalisasi budaya kerja profesional dan mengasah kompetensi teknis kejuruan nyata.
            </p>

            <h2>BAB II: HAK DAN KEWAJIBAN SISWA</h2>
            <p>Selama menjalankan masa praktik kerja lapangan di industri mitra:</p>
            <ul>
                <li>
                    <strong>Kewajiban:</strong> Menghormati dan mematuhi seluruh peraturan kedisiplinan dan SOP internal
                    perusahaan mitra, hadir tepat waktu sesuai jam kerja operasional industri, dan mengenakan seragam
                    praktik kerja resmi serta Alat Pelindung Diri (APD) standar K3.
                </li>
                <li>
                    <strong>Kewajiban Presensi & Jurnal:</strong> Melakukan rekam presensi harian berbasis aplikasi dan
                    mengisi jurnal logbook kegiatan teknis setiap hari kerja secara jujur dan faktual.
                </li>
                <li>
                    <strong>Hak:</strong> Memperoleh bimbingan teknis profesional dari instruktur/mentor industri dan
                    guru pembimbing sekolah, serta mendapatkan sertifikat kelulusan PKL resmi setelah dinyatakan
                    kompeten.
                </li>
            </ul>

            <h2>BAB III: KESELAMATAN & KESEHATAN KERJA (K3) SERTA 5R</h2>
            <p>
                Peserta didik wajib mematuhi protokol K3 industri tanpa toleransi: dilarang mengoperasikan mesin
                perkakas tanpa pengawasan instruktur, wajib menggunakan kacamata pelindung, sepatu safety, dan sarung
                tangan tahan panas/mekanik, serta menerapkan budaya 5R (Ringkas, Rapi, Resik, Rawat, Rajin) pada area
                kerja.
            </p>

            <h2>BAB IV: PELAPORAN DAN PENILAIAN KOMPETENSI</h2>
            <p>
                Setiap siswa diwajibkan menyusun laporan pertanggungjawaban ilmiah PKL pada akhir periode magang.
                Penilaian akhir diterbitkan berdasarkan kolaborasi evaluasi mentor industri (aspek teknis dan etika
                kerja) serta ujian verifikasi kompetensi oleh dewan guru pembimbing sekolah.
            </p>
        @endif
    </div>

    <div class="signatures">
        <table>
            <tr>
                <td>
                    Mengetahui,<br />
                    Koordinator Pokja PKL & Humas,<br />
                    <div class="sign-space"></div>
                    <strong>Drs. H. Sukamto, M.Pd.</strong><br />
                    NIP. 197504122000031001
                </td>
                <td>
                    Klaten, 1 Juli 2026<br />
                    Kepala SMK Negeri 2 Klaten,<br />
                    <div class="sign-space"></div>
                    <strong>{{ $school->principalName() ?: 'Muhammad Woro Nugroho, S.Pd., M.Eng.' }}</strong><br />
                    NIP. 197010261997021001
                </td>
            </tr>
        </table>
    </div>

    <div class="footer-note">
        Dokumen ini diterbitkan secara resmi melalui Sistem Informasi Manajemen Praktik Kerja Lapangan Internara SMK
        Negeri 2 Klaten.
    </div>
</body>
</html>
