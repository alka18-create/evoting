<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kartu Pemilih — {{ $eventVoter->voter->name }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=JetBrains+Mono:wght@700;800&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
    background: #eef2ff;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 32px 16px;
    color: #0f172a;
}
.no-print {
    margin-bottom: 20px;
    display: flex;
    gap: 12px;
    align-items: center;
}
.btn-print {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, #1e40af, #2563eb);
    color: #fff;
    font-size: 14px;
    font-weight: 700;
    border-radius: 9999px;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(37,99,235,0.35);
    transition: all 0.2s;
    text-decoration: none;
}
.btn-print:hover {
    background: linear-gradient(135deg, #1e3a8a, #1d4ed8);
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(37,99,235,0.45);
}
.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 12px 20px;
    background: #ffffff;
    color: #475569;
    font-size: 14px;
    font-weight: 600;
    border-radius: 9999px;
    border: 1px solid #cbd5e1;
    cursor: pointer;
    text-decoration: none;
}
.btn-back:hover { background: #f8fafc; color: #0f172a; }

/* CARD CONTAINER */
.card-wrapper {
    width: 100%;
    max-width: 820px;
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 20px 40px -15px rgba(15,23,42,0.12), 0 0 1px 1px rgba(15,23,42,0.05);
    position: relative;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* HEADER */
.card-header {
    background: linear-gradient(135deg, #102a9c 0%, #1e40af 50%, #2563eb 100%);
    padding: 22px 28px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #ffffff;
    position: relative;
    overflow: hidden;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.card-header::after {
    content: '';
    position: absolute;
    right: -40px;
    top: -40px;
    width: 220px;
    height: 220px;
    background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, rgba(255,255,255,0) 70%);
    border-radius: 50%;
    pointer-events: none;
}
.header-left {
    display: flex;
    align-items: center;
    gap: 16px;
    position: relative;
    z-index: 1;
}
.ballot-icon-box {
    width: 52px;
    height: 52px;
    border: 2px solid rgba(255,255,255,0.9);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.08);
    flex-shrink: 0;
}
.header-text {
    display: flex;
    flex-direction: column;
}
.header-kicker {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: rgba(255,255,255,0.9);
}
.header-title {
    font-size: 21px;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: 0.3px;
    line-height: 1.2;
    margin: 2px 0 3px 0;
    text-transform: uppercase;
}
.header-sub {
    font-size: 13px;
    font-weight: 600;
    color: rgba(255,255,255,0.85);
    letter-spacing: 0.3px;
}
.header-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    position: relative;
    z-index: 1;
    flex-shrink: 0;
}
.badge-single-use {
    background: #dcfce7;
    color: #15803d;
    border-radius: 9999px;
    padding: 7px 15px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 800;
    font-size: 12px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.header-hint {
    font-size: 11px;
    color: rgba(255,255,255,0.88);
    margin-top: 6px;
    text-align: right;
    max-width: 175px;
    line-height: 1.3;
}

/* BODY */
.card-body {
    padding: 24px 28px 16px 28px;
    display: flex;
    gap: 22px;
    background: #ffffff;
}
.body-left {
    flex: 1.42;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.body-right {
    flex: 1;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 18px;
    padding: 16px 14px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    text-align: center;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* VOTER IDENTITY */
.voter-profile {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
}
.voter-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #e0e7ff;
    color: #3730a3;
    font-size: 26px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.voter-name {
    font-size: 22px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.15;
}
.voter-meta {
    font-size: 14px;
    font-weight: 600;
    color: #64748b;
    margin-top: 4px;
}

/* DATA ROW (NIS & KELAS) */
.data-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 16px;
}
.data-box {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    padding: 12px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.data-lbl {
    font-size: 10px;
    font-weight: 800;
    color: #1e40af;
    letter-spacing: 0.6px;
    text-transform: uppercase;
}
.data-val {
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    margin-top: 2px;
}
.data-val.mono {
    font-family: 'JetBrains Mono', monospace;
    letter-spacing: 0.5px;
}
.data-icon {
    color: #a5b4fc;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* TOKEN PANEL */
.token-panel {
    background: linear-gradient(135deg, #102a9c 0%, #1e40af 100%);
    border-radius: 16px;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: #ffffff;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.token-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    min-width: 0;
}
.lock-circle {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: rgba(255,255,255,0.18);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.token-title {
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    color: #ffffff;
}
.token-desc {
    font-size: 10px;
    color: rgba(255,255,255,0.85);
    margin-top: 3px;
    line-height: 1.3;
}
.token-divider {
    border-right: 1.5px dashed rgba(255,255,255,0.35);
    height: 42px;
    margin: 0 14px;
}
.token-digits {
    display: flex;
    gap: 4px;
    flex-shrink: 0;
}
.digit-box {
    width: 32px;
    height: 42px;
    background: #ffffff;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    font-family: 'JetBrains Mono', monospace;
    box-shadow: 0 2px 5px rgba(0,0,0,0.15);
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* RIGHT QR SECTION */
.qr-header-title {
    font-size: 11.5px;
    font-weight: 800;
    color: #1e40af;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.qr-subtext {
    font-size: 10.5px;
    color: #64748b;
    margin-top: 4px;
    line-height: 1.35;
    max-width: 220px;
}
.qr-image-wrapper {
    margin: 8px 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.qr-image-wrapper svg,
.qr-image-wrapper img {
    width: 140px;
    height: 140px;
    display: block;
}
.qr-footer-hint {
    background: #f1f5f9;
    border-radius: 10px;
    padding: 8px 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 10px;
    color: #475569;
    width: 100%;
    justify-content: center;
    line-height: 1.3;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* FOOTER BAR */
.card-footer {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 16px;
    padding: 12px 20px;
    margin: 0 28px 10px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.status-section {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}
.status-badge-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #ecfdf5;
    border: 1.5px solid #a7f3d0;
    color: #16a34a;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.status-text-block .lbl {
    font-size: 9px;
    font-weight: 800;
    color: #15803d;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
.status-text-block .val {
    font-size: 14px;
    font-weight: 800;
    color: #16a34a;
    line-height: 1.1;
}
.solid-sep {
    width: 1.5px;
    height: 30px;
    background: #e2e8f0;
    margin: 0 12px;
}
.status-desc {
    font-size: 10.5px;
    color: #475569;
    max-width: 165px;
    line-height: 1.3;
}
.dashed-sep {
    border-right: 1.5px dashed #cbd5e1;
    height: 34px;
    margin: 0 14px;
}
.usage-section {
    flex: 1;
    min-width: 0;
}
.usage-title {
    font-size: 9px;
    font-weight: 800;
    color: #1e40af;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 5px;
}
.steps-flex {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 10px;
    color: #334155;
    font-weight: 600;
}
.step-pill {
    display: flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
}
.step-num {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #102a9c;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 800;
    flex-shrink: 0;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.step-arrow {
    color: #94a3b8;
    font-size: 13px;
    font-weight: 700;
}

/* SECURITY NOTE */
.confidential-note {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 10.5px;
    color: #64748b;
    padding-bottom: 14px;
    text-align: center;
}

/* PRINT MEDIA QUERIES */
@media print {
    body {
        background: #ffffff !important;
        padding: 0 !important;
        min-height: auto !important;
        display: block !important;
    }
    .no-print {
        display: none !important;
    }
    .card-wrapper {
        border: 1.5px solid #cbd5e1 !important;
        box-shadow: none !important;
        max-width: 190mm !important;
        margin: 0 auto !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
}
</style>
</head>
<body>

<div class="no-print">
    <a href="{{ route('admin.voting-events.tokens.index', $votingEvent) }}" class="btn-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Kembali
    </a>
    <button onclick="window.print()" class="btn-print">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        Cetak Kartu
    </button>
</div>

<div class="card-wrapper">
    <!-- HEADER -->
    <div class="card-header">
        <div class="header-left">
            <div class="ballot-icon-box">
                <svg width="34" height="34" viewBox="0 0 48 48" fill="none">
                    <rect x="6" y="16" width="36" height="26" rx="4" stroke="#ffffff" stroke-width="2.5" fill="rgba(255,255,255,0.05)"/>
                    <path d="M16 16 L22 6 L32 6 L32 16" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M16 29 L21 34 L32 23" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="header-text">
                <span class="header-kicker">KARTU PEMILIH</span>
                <h1 class="header-title">{{ strtoupper($votingEvent->name) }}</h1>
                <span class="header-sub">MAN 3 NGAWI • {{ $votingEvent->starts_at?->format('Y') ?? date('Y') }}/{{ $votingEvent->ends_at?->format('Y') ?? (date('Y') + 1) }}</span>
            </div>
        </div>
        <div class="header-right">
            <div class="badge-single-use">
                <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.99-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
                SEKALI PAKAI
            </div>
            <div class="header-hint">Gunakan hanya satu kali untuk memberikan suara</div>
        </div>
    </div>

    <!-- BODY -->
    <div class="card-body">
        <!-- LEFT COLUMN -->
        <div class="body-left">
            <!-- Profile -->
            <div class="voter-profile">
                <div class="voter-avatar">{{ strtoupper(substr($eventVoter->voter->name, 0, 1)) }}</div>
                <div>
                    <h2 class="voter-name">{{ $eventVoter->voter->name }}</h2>
                    <div class="voter-meta">{{ $eventVoter->voter->class_name }} • NIS {{ $eventVoter->voter->student_id }}</div>
                </div>
            </div>

            <!-- NIS & KELAS -->
            <div class="data-grid">
                <div class="data-box">
                    <div>
                        <div class="data-lbl">NIS</div>
                        <div class="data-val mono">{{ $eventVoter->voter->student_id }}</div>
                    </div>
                    <div class="data-icon">
                        <svg width="34" height="34" viewBox="0 0 36 36" fill="none">
                            <rect x="4" y="7" width="28" height="22" rx="4" stroke="#a5b4fc" stroke-width="1.8"/>
                            <circle cx="12" cy="15" r="3.5" fill="#c7d2fe"/>
                            <path d="M7 25c1.5-2.5 3.5-3.5 6.5-3.5s5 1 6.5 3.5" stroke="#c7d2fe" stroke-width="1.6" stroke-linecap="round"/>
                            <path d="M21 13h7M21 17h7M21 21h5" stroke="#c7d2fe" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>
                <div class="data-box">
                    <div>
                        <div class="data-lbl">KELAS</div>
                        <div class="data-val">{{ $eventVoter->voter->class_name }}</div>
                    </div>
                    <div class="data-icon">
                        <svg width="34" height="34" viewBox="0 0 36 36" fill="none">
                            <path d="M18 9 L4 16 L18 23 L32 16 Z" stroke="#a5b4fc" stroke-width="1.8" stroke-linejoin="round"/>
                            <path d="M8 18.5 L8 24 C8 27 18 29 18 29 C18 29 28 27 28 24 L28 18.5" stroke="#c7d2fe" stroke-width="1.8" stroke-linejoin="round"/>
                            <path d="M32 16 L32 25" stroke="#c7d2fe" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- TOKEN PEMILIH -->
            <div class="token-panel">
                <div class="token-info">
                    <div class="lock-circle">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </div>
                    <div>
                        <div class="token-title">TOKEN PEMILIH</div>
                    </div>
                </div>
                <div class="token-divider"></div>
                <div class="token-digits">
                    @foreach(str_split($plainToken ?? $eventVoter->plainToken() ?? '') as $digit)
                        <div class="digit-box">{{ $digit }}</div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN (QR) -->
        <div class="body-right">
            <div>
                <div class="qr-header-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/>
                    </svg>
                    SCAN UNTUK VERIFIKASI
                </div>
                <div class="qr-subtext">Pindai QR Code saat proses verifikasi atau pemilihan suara</div>
            </div>

            <div class="qr-image-wrapper">
                {{-- P1-01 QR v2 opaque: tanpa NIS plain. Scan resolve by token_hash dalam event. --}}
                {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(140)->margin(0)->generate(json_encode([
                    'v' => 2,
                    'eid' => $votingEvent->id,
                    't' => $plainToken ?? $eventVoter->plainToken(),
                ])) !!}
            </div>

            <div class="qr-footer-hint">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                    <line x1="12" y1="18" x2="12.01" y2="18"/>
                </svg>
                <span>Buka kamera atau aplikasi QR scanner untuk memindai</span>
            </div>
        </div>
    </div>

    <!-- FOOTER BAR -->
    <div class="card-footer">
        <div class="status-section">
            <div class="status-badge-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <path d="M9 12l2 2 4-4"/>
                </svg>
            </div>
            <div class="status-text-block">
                <div class="lbl">STATUS KARTU</div>
                <div class="val">AKTIF</div>
            </div>
        </div>

        <div class="solid-sep"></div>
        <div class="status-desc">Kartu ini masih dapat digunakan untuk memberikan suara.</div>
        <div class="dashed-sep"></div>

        <div class="usage-section">
            <div class="usage-title">CARA MENGGUNAKAN</div>
            <div class="steps-flex">
                <div class="step-pill">
                    <span class="step-num">1</span>
                    <span>Scan QR Code</span>
                </div>
                <span class="step-arrow">&rsaquo;</span>
                <div class="step-pill">
                    <span class="step-num">2</span>
                    <span>Masukkan token pemilih</span>
                </div>
                <span class="step-arrow">&rsaquo;</span>
                <div class="step-pill">
                    <span class="step-num">3</span>
                    <span>Pilih calon &amp; konfirmasi suara</span>
                </div>
            </div>
        </div>
    </div>

    <!-- CONFIDENTIALITY NOTE -->
    <div class="confidential-note">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            <path d="M12 8v4M12 16h.01"/>
        </svg>
        <span>Jaga kerahasiaan token Anda. Jangan berikan kepada siapapun.</span>
    </div>
</div>

</body>
</html>
