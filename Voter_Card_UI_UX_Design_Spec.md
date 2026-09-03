# UI/UX Design Specification — Kartu Pemilih E-Voting

## 1. Tujuan Dokumen

Dokumen ini adalah **design specification / prompt reference** untuk membangun komponen **Kartu Pemilih** pada aplikasi e-voting sekolah.

Dokumen ini dibuat agar desain dapat direplikasi secara konsisten oleh:
- UI/UX designer
- frontend developer
- AI image/design generator
- sistem template kartu
- generator PDF/print

**Prinsip utama:** jangan mengubah struktur, hierarki, proporsi, atau fungsi elemen tanpa alasan UX yang jelas.

---

# 2. Nama Komponen

**Voter Card / Kartu Pemilih**

Konteks:
- Pemilihan Ketua 4 Organisasi
- MAN 3 Ngawi
- Tahun 2026/2027

Komponen harus terlihat seperti **kartu pemilih resmi untuk sistem e-voting**, bukan kartu identitas umum dan bukan poster.

---

# 3. Ukuran dan Rasio

## Master digital

Gunakan rasio landscape sekitar:

**3:2**

Ukuran referensi:

**1200 × 800 px**

Alternatif implementasi:

**900 × 600 px**

atau menggunakan responsive CSS dengan aspect ratio:

`3 / 2`

## Print

Target utama:

**A6 Landscape — 148 × 105 mm**

Safe area:

**5–7 mm**

Jangan menempatkan teks atau QR terlalu dekat dengan tepi.

---

# 4. Struktur Layout Utama

Gunakan layout **3 zona utama**:

```text
┌──────────────────────────────────────────────────────────┐
│                    HEADER / EVENT                        │
├──────────────────────────────────────────────┬───────────┤
│                                              │           │
│  IDENTITY + PERSONAL DATA                   │    QR     │
│                                              │           │
│  AVATAR                                     │ VERIFIKASI │
│  NAMA PEMILIH                                │           │
│  KELAS + NIS                                │           │
│                                              │           │
│  NIS                KELAS                   │           │
│                                              │           │
│  TOKEN PEMILIH                               │           │
│                                              │           │
├──────────────────────────────────────────────┴───────────┤
│ STATUS KARTU              CARA MENGGUNAKAN              │
├──────────────────────────────────────────────────────────┤
│               CATATAN KEAMANAN / PRIVASI                 │
└──────────────────────────────────────────────────────────┘
```

### Prinsip layout

- Header full width.
- Konten utama menggunakan dua kolom.
- Kolom kiri sekitar **68–70%**.
- Kolom kanan sekitar **30–32%**.
- QR berada di kanan dan memiliki panel sendiri.
- Token berada di area kiri dan merupakan credential paling menonjol.
- Footer digunakan untuk status dan langkah penggunaan.
- Jangan meninggalkan area kosong besar yang tidak memiliki fungsi.

---

# 5. Outer Card

## Background

`#FFFFFF`

## Border

`#CBD5E1`

Ketebalan:

**1–1.5 px**

## Radius

**24 px**

## Shadow

Gunakan sangat halus atau tidak sama sekali.

Jika menggunakan shadow:

```text
0 4px 12px rgba(15, 23, 42, 0.08)
```

Card harus terlihat clean, modern, profesional, dan ringan.

---

# 6. Color System

Gunakan sistem warna berikut.

## Primary

```text
Primary 500: #4F46E5
Primary 600: #4338CA
Primary Soft: #EEF2FF
```

## Text

```text
Text Primary: #0F172A
Text Secondary: #475569
Text Muted: #64748B
Text Light: #94A3B8
```

## Surface

```text
Surface: #FFFFFF
Surface Soft: #F8FAFC
Border: #E2E8F0
```

## Success

```text
Success: #16A34A
Success Soft: #ECFDF5
```

Jangan menggunakan banyak warna tambahan.

Identitas visual utama harus tetap **indigo / violet-blue + white + slate**.

---

# 7. Header

Header memiliki background gradient indigo.

Gunakan:

```text
#4338CA → #4F46E5
```

Gradient boleh horizontal atau diagonal sangat halus.

Jangan membuat gradient terlalu mencolok.

## Isi header

Urutan:

```text
KARTU PEMILIH

PEMILIHAN KETUA 4 ORGANISASI

MAN 3 NGAWI • 2026/2027
```

### Typography

#### KARTU PEMILIH

- 11–12 px
- Bold / 700
- Uppercase
- Letter spacing: 0.8–1 px
- Warna putih

#### PEMILIHAN KETUA 4 ORGANISASI

- 22–28 px pada master digital
- Bold / 700
- Warna putih
- Merupakan judul utama event

#### MAN 3 NGAWI • 2026/2027

- 13–16 px
- Medium / 500
- Warna putih dengan opacity sedikit lebih rendah

---

# 8. Badge Sekali Pakai

Posisi:

**kanan atas header**

Text:

**SEKALI PAKAI**

Tambahkan icon shield/check.

Makna:

Token hanya dapat digunakan satu kali.

Jangan gunakan tulisan ambigu:

**"1 Token"**

karena tidak menjelaskan fungsi atau status token.

Badge:

- Background: `#ECFDF5`
- Text: `#166534`
- Radius: 999 px / pill
- Font: 11–12 px
- Weight: 600–700

Di bawah badge dapat terdapat helper text:

**Gunakan hanya satu kali untuk memberikan suara**

---

# 9. Identity Section

Bagian kiri atas.

Struktur:

```text
[ AVATAR ]   Budi Santoso
             XII RPL 1 • NIS 12345
```

## Avatar

Gunakan avatar berbentuk rounded square atau circle.

Ukuran:

**40–48 px**

Background:

`#EEF2FF`

Text:

`#4338CA`

Font:

20–24 px, bold.

Contoh:

**B**

Jangan menggunakan foto siswa jika tidak diperlukan.

---

# 10. Nama Pemilih

Contoh:

**Budi Santoso**

Typography:

- 18–22 px
- Weight 700
- Color `#0F172A`

Nama harus menjadi elemen teks terbesar di area identitas.

---

# 11. Informasi Sekunder

Contoh:

**XII RPL 1 • NIS 12345**

Typography:

- 12–14 px
- Weight 500
- Color `#475569`

Jangan membuat informasi sekunder lebih dominan daripada nama.

---

# 12. Data Card — NIS

Buat panel informasi:

```text
NIS

12345
```

Background:

`#F8FAFC`

Border:

`#E2E8F0`

Radius:

**12–14 px**

Padding:

**16 px**

Label:

- 10–11 px
- uppercase
- medium/semibold
- color `#64748B`

Value:

- 16–18 px
- semibold
- color `#0F172A`

---

# 13. Data Card — Kelas

Struktur sama seperti NIS:

```text
KELAS

XII RPL 1
```

NIS dan KELAS harus berada dalam satu row.

Proporsi:

**50% : 50%**

Gap:

**12–16 px**

---

# 14. Token Section

Token merupakan credential utama kartu.

Gunakan panel besar berwarna primary.

Background:

`#4338CA`

atau gradient sangat halus:

`#4338CA → #4F46E5`

Radius:

**14–16 px**

## Struktur

```text
TOKEN PEMILIH

Gunakan token ini untuk autentikasi
pada sistem e-voting

[ 2 ][ 4 ][ 0 ][ 3 ][ 7 ][ 7 ]
```

---

# 15. Token Typography

Token:

**240377**

atau secara visual:

**2 4 0 3 7 7**

Gunakan font monospace.

Ukuran:

**22–28 px**

Weight:

**700**

Letter spacing:

**4–6 px**

Tujuan:

- mudah dibaca
- mudah diketik ulang
- mudah diverifikasi
- berbeda secara visual dari NIS

Jangan membuat token terlihat seperti nama atau data biasa.

---

# 16. Token Security

Tambahkan icon lock/shield.

Helper text:

**Gunakan token ini untuk autentikasi pada sistem e-voting**

Tambahkan catatan keamanan:

**Jaga kerahasiaan token Anda. Jangan berikan kepada siapapun.**

Token harus diperlakukan sebagai credential rahasia.

---

# 17. QR Verification Panel

Panel berada di sisi kanan.

Background:

`#F8FAFC`

Border:

`#E2E8F0`

Radius:

**16 px**

Struktur:

```text
SCAN UNTUK VERIFIKASI

Pindai QR Code saat proses
verifikasi atau pemilihan suara

┌────────────────────┐
│                    │
│       QR CODE      │
│                    │
└────────────────────┘

Buka kamera atau aplikasi
QR scanner untuk memindai
```

---

# 18. QR Code Rules

QR harus:

- hitam putih
- memiliki quiet zone
- tidak menggunakan gradient
- tidak menggunakan dekorasi di atas QR
- tidak diputar
- tidak dipotong
- tidak terlalu kecil

Untuk digital:

**108–140 px minimum**

Untuk print:

**sekitar 25–30 mm minimum**, menyesuaikan kepadatan data dan kualitas printer.

QR harus memiliki margin putih yang cukup.

---

# 19. QR Caption

Judul:

**SCAN UNTUK VERIFIKASI**

Typography:

- 14–16 px
- semibold/bold
- Primary 600

Helper:

**Pindai QR Code saat proses verifikasi atau pemilihan suara**

Typography:

- 11–13 px
- color `#475569`

Footer QR:

**Buka kamera atau aplikasi QR scanner untuk memindai**

---

# 20. Status Card

Footer utama harus menampilkan status kartu.

Contoh:

```text
STATUS KARTU

● AKTIF

Kartu ini masih dapat digunakan
untuk memberikan suara.
```

Status aktif:

- Icon shield/check
- Color `#16A34A`
- Background soft green
- Text semibold

State lain:

### Belum digunakan

`AKTIF`

### Sudah digunakan

`SUDAH DIGUNAKAN`

### Kedaluwarsa

`KEDALUWARSA`

### Tidak valid

`TIDAK VALID`

Jangan pernah menampilkan kandidat yang dipilih pada kartu.

---

# 21. Cara Menggunakan

Gunakan pola 3 langkah.

```text
1  Scan QR Code
       →
2  Masukkan token pemilih
       →
3  Pilih calon dan konfirmasi suara
```

Gunakan numbered circles.

Nomor:

**1 / 2 / 3**

Warna:

Primary 600.

Typography:

- Step number: 12–14 px bold
- Description: 11–13 px

Jaga agar instruksi tetap singkat.

---

# 22. Footer Security Notice

Bagian paling bawah:

**Jaga kerahasiaan token Anda. Jangan berikan kepada siapapun.**

Tambahkan icon shield kecil.

Color:

`#64748B`

Font:

11–12 px.

Footer bukan area utama, sehingga tidak boleh lebih dominan daripada token.

---

# 23. Spacing System

Gunakan 8-point grid.

```text
4 px   micro spacing
8 px   small
12 px  component gap
16 px  normal
24 px  section gap
32 px  major section gap
```

Outer padding:

**24 px**

Gap antar komponen:

**12–16 px**

Jangan menggunakan spacing acak.

---

# 24. Typography System

Font yang direkomendasikan:

**Plus Jakarta Sans**

Fallback:

```text
Inter, system-ui, sans-serif
```

## Scale

```text
Event Title       24–28 px / 700
Voter Name        18–22 px / 700
Token             22–28 px / 700
Data Value        16–18 px / 600
Section Title     14–16 px / 600
Body              12–14 px / 400–500
Label             10–11 px / 600
Caption           11–12 px / 400
```

---

# 25. Component States

Komponen harus mendukung beberapa state.

## State 1 — ACTIVE

```text
Status: AKTIF
Token: visible
QR: active
```

## State 2 — USED

```text
Status: SUDAH DIGUNAKAN
Token: boleh disamarkan
QR: disabled jika sistem membutuhkan
```

## State 3 — EXPIRED

```text
Status: KEDALUWARSA
Token: disabled
QR: disabled
```

## State 4 — INVALID

```text
Status: TIDAK VALID
```

Gunakan opacity dan status indicator untuk menunjukkan kondisi.

---

# 26. Responsive Behavior

## Desktop

Gunakan dua kolom:

```text
70% Identity
30% QR
```

## Tablet

Tetap dua kolom jika ruang mencukupi.

## Mobile

Stack:

```text
Header

Identity

NIS + Kelas

Token

QR

Status

Instructions
```

QR diposisikan setelah token.

Jangan membuat QR terlalu kecil hanya untuk mempertahankan dua kolom.

---

# 27. Print Specification

Untuk A6 Landscape:

**148 × 105 mm**

Bleed:

**3 mm** jika percetakan membutuhkan bleed.

Safe area:

**5–7 mm**

QR:

**25–30 mm minimum**

Gunakan warna CMYK saat proses final printing jika workflow percetakan memerlukannya.

Jangan mengandalkan warna gradient sebagai satu-satunya pembeda informasi.

---

# 28. Accessibility

Minimum:

- text contrast tinggi
- token sangat kontras
- QR hitam putih
- jangan mengandalkan warna saja untuk status
- status harus memiliki text + icon
- ukuran text penting minimal sekitar 11–12 px pada digital
- hindari terlalu banyak uppercase untuk body text

Contoh:

Jangan:

`KARTU ANDA SUDAH TIDAK DAPAT DIGUNAKAN`

Lebih baik:

`Kartu sudah tidak dapat digunakan.`

---

# 29. UX Principles

Desain harus mengikuti urutan mental pengguna:

**1. Siapa saya?**

→ Budi Santoso

**2. Saya berada di kelas mana?**

→ XII RPL 1

**3. Apa identitas saya?**

→ NIS 12345

**4. Apa credential saya?**

→ Token 240377

**5. Bagaimana saya melakukan verifikasi?**

→ QR Code

**6. Apakah kartu masih aktif?**

→ Status Aktif

**7. Apa yang harus saya lakukan?**

→ 3 langkah penggunaan

---

# 30. Hal yang HARUS dipertahankan

Saat membuat ulang desain, pertahankan:

- landscape orientation
- rounded white card
- indigo/blue header
- header full width
- event title di header
- badge sekali pakai di kanan atas
- avatar initial
- nama pemilih
- kelas + NIS
- NIS card
- kelas card
- token panel berwarna indigo
- QR panel di kanan
- status kartu
- 3-step instruction
- security notice
- clean whitespace
- modern school e-voting aesthetic

---

# 31. Hal yang JANGAN dilakukan

Jangan:

- mengubah menjadi poster
- mengubah menjadi ID card vertikal
- menambahkan foto siswa
- menambahkan banyak ornamen
- menggunakan ilustrasi besar
- menggunakan warna-warni berlebihan
- mengubah QR menjadi dekorasi
- membuat token kecil
- membuat QR terlalu kecil
- menambahkan kandidat ke kartu
- menambahkan foto kandidat
- membuat layout simetris secara paksa
- meninggalkan ruang kosong besar tanpa fungsi
- menggunakan font dekoratif
- menggunakan efek 3D berlebihan
- menggunakan glassmorphism berat
- membuat kartu terlihat seperti tiket konser
- membuat kartu terlihat seperti sertifikat

---

# 32. Data Template

Gunakan data dinamis berikut:

```text
election_title:
Pemilihan Ketua 4 Organisasi

organization:
MAN 3 Ngawi

academic_year:
2026/2027

voter_name:
Budi Santoso

class:
XII RPL 1

nis:
12345

token:
240377

status:
AKTIF

qr_data:
[DYNAMIC_QR_DATA]
```

---

# 33. Template Variable

Untuk aplikasi production, gunakan variable:

```text
{{election_title}}
{{organization_name}}
{{academic_year}}

{{voter_name}}
{{voter_initial}}
{{class_name}}
{{nis}}

{{voter_token}}
{{voter_status}}

{{qr_code}}
{{expires_at}}
```

Contoh:

```text
{{voter_name}} → Budi Santoso
{{voter_initial}} → B
{{class_name}} → XII RPL 1
{{nis}} → 12345
{{voter_token}} → 240377
{{voter_status}} → AKTIF
```

---

# 34. AI Design Prompt

Jika dokumen ini digunakan sebagai prompt untuk AI design generator, gunakan instruksi berikut:

> Create a clean, professional digital voter card UI for a school e-voting application. Use a landscape 3:2 composition and a white rounded card with a subtle border. The top section is a full-width indigo-to-blue gradient header containing the label "KARTU PEMILIH", the main event title "PEMILIHAN KETUA 4 ORGANISASI", and "MAN 3 NGAWI • 2026/2027". Place a small green pill badge "SEKALI PAKAI" at the top-right.
>
> Below the header, create a two-column layout. The left side contains a rounded initial avatar "B", the voter name "Budi Santoso", secondary information "XII RPL 1 • NIS 12345", two compact information cards labeled "NIS" and "KELAS", and a prominent indigo token panel labeled "TOKEN PEMILIH" with the token "240377" displayed in a large monospace style with generous character spacing.
>
> The right side contains a dedicated QR verification panel with the heading "SCAN UNTUK VERIFIKASI", short helper text, a large black-and-white QR code with sufficient quiet zone, and a small instruction "Buka kamera atau aplikasi QR scanner untuk memindai".
>
> At the bottom, include a status section showing "STATUS KARTU — AKTIF" with a green shield/check icon, followed by three concise usage steps: "1 Scan QR Code", "2 Masukkan token pemilih", "3 Pilih calon dan konfirmasi suara". Add a subtle security notice: "Jaga kerahasiaan token Anda. Jangan berikan kepada siapapun."
>
> Use Plus Jakarta Sans or Inter, strong typography hierarchy, an 8-point spacing system, 24px outer padding, 24px card radius, 12–16px component radius, high contrast text, subtle borders, minimal shadows, and restrained indigo/blue/white/slate colors. The design must look like an official school e-voting credential card, not a poster, ticket, certificate, or generic ID card. Preserve the exact information hierarchy and component relationships. Do not add unnecessary illustrations, photographs, decorative objects, candidate portraits, or extra content.

---

# 35. Negative Prompt

Gunakan negative prompt berikut jika generator mendukung:

```text
no poster layout,
no vertical card,
no ID badge style,
no employee badge,
no passport design,
no certificate,
no concert ticket,
no colorful decorations,
no excessive illustrations,
no student photograph,
no candidate photographs,
no large decorative icons,
no excessive gradients,
no glassmorphism,
no 3D effects,
no excessive shadows,
no tiny QR code,
no tiny token,
no ambiguous badge,
no oversized whitespace,
no random text,
no lorem ipsum,
no additional sections,
no candidate information,
no ballot result,
no voting choice information,
no distorted QR code,
no decorative QR code,
no low contrast text
```

---

# 36. Final Design Principle

Prioritas visual:

```text
EVENT
  ↓
VOTER IDENTITY
  ↓
TOKEN / CREDENTIAL
  ↓
QR VERIFICATION
  ↓
STATUS
  ↓
INSTRUCTIONS
```

Kartu harus dapat dipahami pengguna dalam **3–5 detik** tanpa harus membaca seluruh teks.

Tujuan utama desain bukan sekadar estetika, tetapi:

**Identify → Authenticate → Verify → Vote**

---

# 37. Acceptance Criteria

Desain dianggap berhasil jika:

- [ ] Layout landscape
- [ ] Header full width
- [ ] Judul event jelas
- [ ] Nama pemilih paling menonjol di area identitas
- [ ] NIS dan kelas mudah ditemukan
- [ ] Token menjadi credential utama
- [ ] Token mudah dibaca
- [ ] QR mudah dipindai
- [ ] QR memiliki quiet zone
- [ ] Status kartu jelas
- [ ] Instruksi terdiri dari 3 langkah
- [ ] Security notice terlihat tetapi tidak dominan
- [ ] Tidak ada whitespace kosong yang tidak perlu
- [ ] Warna konsisten
- [ ] Typography konsisten
- [ ] Layout dapat diadaptasi ke data pemilih berbeda
- [ ] Tidak bergantung pada foto siswa
- [ ] Tidak menampilkan pilihan kandidat
- [ ] Siap diimplementasikan sebagai reusable component
- [ ] Siap diekspor ke PDF/print

---

# 38. Target Akhir

Hasil akhir harus terasa seperti:

**Modern + Official + Secure + Simple + School-oriented**

Bukan:

**Decorative + Promotional + Generic + Overdesigned**

Kartu ini adalah **credential card untuk proses e-voting**, sehingga fungsi, keterbacaan, keamanan informasi, dan hierarchy harus selalu lebih penting daripada dekorasi.
