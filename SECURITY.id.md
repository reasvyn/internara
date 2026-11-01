# Kebijakan Keamanan

## Melaporkan Kerentanan

Jika Anda menemukan kerentanan keamanan di Internara, laporkan secara privat. **Jangan membuka issue
publik.**

Kirim detail ke **[reasvyn@gmail.com](mailto:reasvyn@gmail.com)** dengan baris subjek
`[INTERNARA SECURITY]` dan sertakan:

- Jenis kerentanan
- Langkah reproduksi
- Versi yang terdampak
- Detail konfigurasi atau lingkungan yang relevan

Anda seharusnya menerima respons dalam **48 jam**. Jika tidak, tindak lanjuti untuk memastikan pesan
telah diterima.

Bahasa yang diutamakan: **Inggris** (Indonesia diterima).

> Terjemahan bahasa Indonesia dari [`SECURITY.md`](SECURITY.md).

## Pengungkapan

Kami mengikuti **jendela pengungkapan 90 hari** — kami berupaya merilis perbaikan dalam 90 hari
setelah menerima laporan. Setelah perbaikan dirilis, kami akan mencantumkan nama pelapor (jika
diinginkan) di catatan rilis.

## Cakupan

Berikut **termasuk cakupan** laporan keamanan:

- Bypass autentikasi atau eskalasi hak istimewa
- Injeksi SQL, XSS, CSRF, SSRF
- Paparan data sensitif (kebocoran PII, kontrol akses yang tidak semestinya)
- Eksekusi kode jarak jauh
- Kelemahan kriptografis pada pembuatan sertifikat atau alur pemulihan

Berikut **di luar cakupan**:

- Laporan pada endpoint tanpa autentikasi yang memang sengaja publik
- Header keamanan yang hilang tanpa kerentanan yang dapat didemonstrasikan
- Bypass pembatasan laju tanpa dampak yang terbukti
- Pengungkapan versi tanpa jalur eksploitasi

## Versi yang Didukung

| Versi | Didukung                                                |
| ----- | ------------------------------------------------------- |
| 0.x   | Ya (hanya terbaru; perbaikan keamanan di-backport atas permintaan) |

## Praktik Keamanan

Internara mengikuti praktik keamanan berikut secara desain:

- **Isolasi PII** — kredensial dan data pribadi berada di tabel terpisah
- **Auth berlapis** — middleware rute + otorisasi Livewire + Policy
- **Tanpa mass assignment** — whitelist `#[Fillable]` pada setiap model, jangan pernah `$request->all()`
- **CSP ditegakkan** — header Content-Security-Policy ketat via middleware global
- **Unggahan berkas** — hanya melalui Spatie MediaLibrary (jangan pernah `Storage::put()`)
- **Pembatasan laju** — pada endpoint auth, alur pemulihan, dan validasi token penyiapan
- **Pencegahan injeksi SQL** — hanya kueri berparameter; SQL mentah dilarang tanpa binding
