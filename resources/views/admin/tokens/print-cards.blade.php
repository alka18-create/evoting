<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cetak Semua Kartu — {{ $election->name }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=JetBrains+Mono:wght@700;800&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
    background: #eef2ff;
    padding: 24px 20px;
    color: #0f172a;
    -webkit-font-smoothing: antialiased;
}
.no-print {
    max-width: 1280px;
    margin: 0 auto 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #ffffff;
    padding: 16px 24px;
    border-radius: 16px;
    border: 1px solid #cbd5e1;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
}
.no-print-left h1 {
    font-size: 18px;
    font-weight: 800;
    color: #1e1b4b;
}
.no-print-left p {
    font-size: 12px;
    color: #64748b;
    margin-top: 3px;
}
.no-print-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}
.btn-print {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 22px;
    background: linear-gradient(135deg, #1e40af, #2563eb);
    color: #fff;
    font-size: 13px;
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
    box-shadow: 0 6px 18px rgba(37,99,235,0.45);
}
.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    background: #ffffff;
    color: #475569;
    font-size: 13px;
    font-weight: 600;
    border-radius: 9999px;
    border: 1px solid #cbd5e1;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}
.btn-back:hover { background: #f8fafc; color: #0f172a; }

/* GRID CONTAINER — SCREEN */
.cards-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
    max-width: 1280px;
    margin: 0 auto;
}

/* CARD WRAPPER — SCREEN */
.card-wrapper {
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(15,23,42,0.06);
    position: relative;
    display: flex;
    flex-direction: column;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* HEADER */
.card-header {
    background: linear-gradient(135deg, #102a9c 0%, #1e40af 50%, #2563eb 100%);
    padding: 14px 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #ffffff;
    position: relative;
    overflow: hidden;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.header-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    flex: 1;
}
.ballot-icon-box {
    width: 36px;
    height: 36px;
    border: 1.5px solid rgba(255,255,255,0.9);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.12);
    flex-shrink: 0;
}
.header-text {
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.header-kicker {
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: rgba(255,255,255,0.9);
}
.header-title {
    font-size: 14px;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: 0.2px;
    line-height: 1.2;
    margin: 1px 0;
    text-transform: uppercase;
}
.header-sub {
    font-size: 10.5px;
    font-weight: 600;
    color: rgba(255,255,255,0.85);
    letter-spacing: 0.2px;
}
.header-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    flex-shrink: 0;
    margin-left: 12px;
}
.badge-single-use {
    background: #dcfce7;
    color: #15803d;
    border-radius: 9999px;
    padding: 4px 10px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-weight: 800;
    font-size: 9.5px;
    letter-spacing: 0.3px;
    text-transform: uppercase;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.header-hint {
    font-size: 8.5px;
    color: rgba(255,255,255,0.85);
    margin-top: 2px;
    text-align: right;
    white-space: nowrap;
}

/* BODY */
.card-body {
    padding: 16px 18px 12px 18px;
    display: flex;
    gap: 16px;
    background: #ffffff;
    flex: 1;
}
.body-left {
    flex: 1.25;
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-width: 0;
}
.body-right {
    flex: 0.95;
    background: #f8fafc;
    border: 1.2px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-align: center;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* VOTER IDENTITY */
.voter-profile {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}
.voter-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #e0e7ff;
    color: #3730a3;
    font-size: 18px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.voter-profile-text {
    min-width: 0;
    flex: 1;
}
.voter-name {
    font-size: 15.5px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
}
.voter-meta {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    margin-top: 1px;
}

/* DATA ROW (NIS & KELAS) */
.data-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.data-box {
    background: #f8fafc;
    border: 1.2px solid #e2e8f0;
    border-radius: 8px;
    padding: 8px 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    min-width: 0;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.data-lbl {
    font-size: 8.5px;
    font-weight: 800;
    color: #1e40af;
    letter-spacing: 0.4px;
    text-transform: uppercase;
}
.data-val {
    font-size: 14px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
    margin-top: 2px;
}
.data-val.mono {
    font-family: 'JetBrains Mono', monospace;
}
.data-icon {
    color: #a5b4fc;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-left: 4px;
}

/* TOKEN PANEL */
.token-panel {
    background: linear-gradient(135deg, #102a9c 0%, #1e40af 100%);
    border-radius: 10px;
    padding: 7px 10px;
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
    gap: 6px;
    flex-shrink: 0;
}
.lock-circle {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: rgba(255,255,255,0.18);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.lock-circle svg {
    width: 11px;
    height: 11px;
}
.token-title {
    font-size: 8.5px;
    font-weight: 800;
    letter-spacing: 0.3px;
    text-transform: uppercase;
    color: #ffffff;
    white-space: nowrap;
    margin-right: 2px;
}
.token-divider {
    border-right: 1.2px dashed rgba(255,255,255,0.35);
    height: 22px;
    margin: 0 6px;
    flex-shrink: 0;
}
.token-digits {
    display: flex;
    gap: 3px;
    flex-shrink: 0;
}
.digit-box {
    width: 18px;
    height: 25px;
    background: #ffffff;
    border-radius: 3.5px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13.5px;
    font-weight: 800;
    color: #0f172a;
    font-family: 'JetBrains Mono', monospace;
    box-shadow: 0 1px 2px rgba(0,0,0,0.15);
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* RIGHT QR SECTION */
.qr-header-title {
    font-size: 10px;
    font-weight: 800;
    color: #1e40af;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}
.qr-subtext {
    font-size: 8.5px;
    color: #64748b;
    margin-top: 1px;
    line-height: 1.1;
}
.qr-image-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
}
.qr-image-wrapper svg,
.qr-image-wrapper img {
    width: 96px;
    height: 96px;
    display: block;
}
.qr-footer-hint {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 4px 8px;
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 8.5px;
    font-weight: 600;
    color: #475569;
    width: 100%;
    justify-content: center;
    line-height: 1.1;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* FOOTER BAR (INSIDE BODY-LEFT) */
.card-footer {
    background: #f8fafc;
    border: 1.2px solid #e2e8f0;
    border-radius: 8px;
    padding: 8px 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.status-section {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
}
.status-badge-icon {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #16a34a;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.status-text-block .lbl {
    font-size: 7.5px;
    font-weight: 800;
    color: #15803d;
    letter-spacing: 0.3px;
    text-transform: uppercase;
}
.status-text-block .val {
    font-size: 10px;
    font-weight: 800;
    color: #16a34a;
    line-height: 1;
}
.solid-sep {
    width: 1px;
    height: 38px;
    background: #cbd5e1;
    margin: 0 8px;
    flex-shrink: 0;
}
.usage-section {
    display: flex;
    flex-direction: column;
    gap: 3px;
    flex: 1;
    min-width: 0;
}
.usage-title {
    font-size: 7.5px;
    font-weight: 800;
    color: #1e40af;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}
.steps-list {
    display: flex;
    flex-direction: column;
    gap: 2.5px;
}
.step-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 8px;
    color: #334155;
    font-weight: 600;
    line-height: 1.1;
    white-space: nowrap;
}
.step-num {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #102a9c;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 7px;
    font-weight: 800;
    flex-shrink: 0;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* SECURITY NOTE */
.confidential-note {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    font-size: 8.5px;
    color: #64748b;
    padding-bottom: 8px;
    text-align: center;
}

/* ============================================================
   PRINT MEDIA QUERIES — Presisi 8 Kartu per Lembar A4 (2 Kolom x 4 Baris)
   Menggunakan Flex-Wrap agar tidak terjadi penumpukan kartu di browser print engine
   ============================================================ */
@media print {
    body {
        background: #ffffff !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .no-print {
        display: none !important;
    }
    @page {
        size: A4 portrait;
        margin: 5mm 5mm 4mm 5mm;
    }
    .cards-grid {
        display: flex !important;
        flex-wrap: wrap !important;
        justify-content: space-between !important;
        align-content: flex-start !important;
        width: 198mm !important;
        max-width: 198mm !important;
        margin: 0 auto !important;
        gap: 0 !important;
    }
    .card-wrapper {
        width: 97.5mm !important;
        max-width: 97.5mm !important;
        min-width: 97.5mm !important;
        height: 67mm !important;
        max-height: 67mm !important;
        margin-bottom: 2.8mm !important;
        border: 1px solid #64748b !important;
        box-shadow: none !important;
        border-radius: 2.5mm !important;
        overflow: hidden !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        display: flex !important;
        flex-direction: column !important;
        background: #ffffff !important;
        box-sizing: border-box !important;
    }
    .card-wrapper:nth-child(8n) {
        page-break-after: always !important;
        break-after: page !important;
    }

    /* HEADER PRINT */
    .card-header {
        padding: 2.2mm 3mm !important;
        gap: 2mm !important;
        background: linear-gradient(135deg, #0f277a 0%, #1e40af 60%, #2563eb 100%) !important;
        flex-shrink: 0 !important;
    }
    .header-left { gap: 2.5mm !important; }
    .ballot-icon-box {
        width: 24px !important;
        height: 24px !important;
        border-radius: 4px !important;
        border-width: 1.2px !important;
        background: rgba(255,255,255,0.18) !important;
    }
    .ballot-icon-box svg { width: 14px !important; height: 14px !important; }
    .header-kicker { font-size: 6px !important; letter-spacing: 0.8px !important; }
    .header-title {
        font-size: 8.8px !important;
        margin: 0.5px 0 !important;
        line-height: 1.2 !important;
        white-space: normal !important;
        text-overflow: clip !important;
        overflow: visible !important;
    }
    .header-sub { font-size: 6.2px !important; margin-top: 0.3px !important; }
    .badge-single-use {
        font-size: 6px !important;
        padding: 1.5px 5.5px !important;
        gap: 2px !important;
        border-radius: 99px !important;
        background: #dcfce7 !important;
        color: #15803d !important;
    }
    .badge-single-use svg { width: 6px !important; height: 6px !important; }
    .header-hint { display: none !important; }

    /* BODY PRINT — DENSE, BALANCED & FULL-HEIGHT */
    .card-body {
        padding: 2mm 2.8mm 1.5mm 2.8mm !important;
        gap: 2.5mm !important;
        flex: 1 !important;
        display: flex !important;
        align-items: stretch !important;
        box-sizing: border-box !important;
    }
    .body-left {
        flex: 1.25 !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
        gap: 0 !important;
        min-width: 0 !important;
    }
    .body-right {
        flex: 0.95 !important;
        padding: 1.8mm 1.8mm !important;
        border-radius: 2mm !important;
        border: 0.8px solid #cbd5e1 !important;
        background: #f8fafc !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 0 !important;
        box-sizing: border-box !important;
    }
    .qr-image-wrapper {
        margin: 0 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex: 1 !important;
    }
    .qr-image-wrapper svg,
    .qr-image-wrapper img {
        width: 31mm !important;
        height: 31mm !important;
        max-width: 31mm !important;
        max-height: 31mm !important;
        display: block !important;
        margin: 0 auto !important;
    }

    /* VOTER PRINT */
    .voter-profile { gap: 2.2mm !important; margin-bottom: 0 !important; align-items: center !important; }
    .voter-avatar {
        width: 24px !important;
        height: 24px !important;
        font-size: 11px !important;
        background: #e0e7ff !important;
        color: #3730a3 !important;
        border-radius: 50% !important;
    }
    .voter-name {
        font-size: 11px !important;
        font-weight: 800 !important;
        line-height: 1.15 !important;
        color: #0f172a !important;
    }
    .voter-meta { font-size: 7.2px !important; margin-top: 0.5px !important; color: #475569 !important; font-weight: 700 !important; }

    /* DATA GRID PRINT */
    .data-grid { gap: 1.8mm !important; margin: 0 !important; }
    .data-box {
        padding: 1.6mm 2.2mm !important;
        border-radius: 1.5mm !important;
        border: 0.8px solid #cbd5e1 !important;
        background: #f8fafc !important;
    }
    .data-lbl { font-size: 5.8px !important; color: #1e40af !important; font-weight: 800 !important; }
    .data-val { font-size: 9.5px !important; margin-top: 0.5px !important; color: #0f172a !important; font-weight: 800 !important; }
    .data-icon { display: none !important; }

    /* TOKEN PRINT */
    .token-panel {
        padding: 1.3mm 2mm !important;
        border-radius: 1.6mm !important;
        background: linear-gradient(135deg, #0f277a 0%, #1e40af 100%) !important;
    }
    .token-info { gap: 1.2mm !important; flex-shrink: 0 !important; }
    .lock-circle { width: 13px !important; height: 13px !important; background: rgba(255,255,255,0.22) !important; }
    .lock-circle svg { width: 7px !important; height: 7px !important; }
    .token-title { font-size: 6px !important; font-weight: 800 !important; letter-spacing: 0.2px !important; margin-right: 1mm !important; }
    .token-divider { height: 14px !important; margin: 0 1.2mm !important; flex-shrink: 0 !important; border-color: rgba(255,255,255,0.4) !important; }
    .token-digits { gap: 1.4px !important; flex-shrink: 0 !important; }
    .digit-box {
        width: 10.5px !important;
        height: 15px !important;
        font-size: 9px !important;
        border-radius: 1.2px !important;
        background: #ffffff !important;
        color: #0f172a !important;
        border: 0.5px solid rgba(0,0,0,0.15) !important;
    }

    /* QR LABELS PRINT */
    .qr-header-title { font-size: 6.8px !important; gap: 1.5px !important; color: #1e40af !important; font-weight: 800 !important; }
    .qr-header-title svg { width: 6.5px !important; height: 6.5px !important; }
    .qr-subtext { font-size: 5.5px !important; margin-top: 0.3px !important; color: #64748b !important; }
    .qr-footer-hint {
        font-size: 5.2px !important;
        padding: 1mm 2mm !important;
        border-radius: 1.5mm !important;
        gap: 1.5px !important;
        border: 0.5px solid #cbd5e1 !important;
        background: #ffffff !important;
        color: #475569 !important;
    }
    .qr-footer-hint svg { width: 5px !important; height: 5px !important; }

    /* FOOTER PRINT (INSIDE BODY-LEFT) */
    .card-footer {
        padding: 1.4mm 2mm !important;
        margin: 0 !important;
        border-radius: 1.8mm !important;
        gap: 1.5mm !important;
        border: 0.8px solid #cbd5e1 !important;
        background: #f8fafc !important;
        flex-shrink: 0 !important;
    }
    .status-badge-icon { width: 16px !important; height: 16px !important; background: #ecfdf5 !important; border-color: #a7f3d0 !important; color: #16a34a !important; }
    .status-badge-icon svg { width: 8.5px !important; height: 8.5px !important; }
    .status-text-block .lbl { font-size: 4.8px !important; color: #15803d !important; font-weight: 800 !important; }
    .status-text-block .val { font-size: 7px !important; color: #16a34a !important; font-weight: 800 !important; }
    .solid-sep { height: 14mm !important; margin: 0 1.5mm !important; background: #cbd5e1 !important; }
    .usage-section { gap: 0.5mm !important; }
    .usage-title { font-size: 5.2px !important; color: #1e40af !important; font-weight: 800 !important; }
    .steps-list { gap: 0.6mm !important; }
    .step-item { gap: 1.5px !important; font-size: 5.8px !important; color: #334155 !important; font-weight: 600 !important; }
    .step-num { width: 8.5px !important; height: 8.5px !important; font-size: 5px !important; background: #102a9c !important; }

    /* CONFIDENTIAL PRINT */
    .confidential-note {
        font-size: 5.5px !important;
        padding: 1.2mm 0 1.2mm 0 !important;
        gap: 1.5px !important;
        color: #64748b !important;
        flex-shrink: 0 !important;
    }
    .confidential-note svg { width: 5.5px !important; height: 5.5px !important; }
}

@media (max-width: 1024px) {
    .cards-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>

<div class="no-print">
    <div class="no-print-left">
        <h1>Cetak Massal Kartu Pemilih — {{ $election->name }}</h1>
        <p>Total: {{ $eligibilities->count() }} kartu siap cetak &bull; 8 kartu per lembar A4 (2×4)</p>
    </div>
    <div class="no-print-actions">
        <a href="{{ route('admin.elections.tokens.index', $election) }}" class="btn-back">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
        <button onclick="window.print()" class="btn-print">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Cetak Semua ({{ $eligibilities->count() }} Kartu)
        </button>
    </div>
</div>

<div class="cards-grid">
@foreach($eligibilities as $eligibility)
    <div class="card-wrapper">
        <!-- HEADER -->
        <div class="card-header">
            <div class="header-left">
                <div class="ballot-icon-box">
                    <svg width="22" height="22" viewBox="0 0 48 48" fill="none">
                        <rect x="6" y="16" width="36" height="26" rx="4" stroke="#ffffff" stroke-width="2.5" fill="rgba(255,255,255,0.05)"/>
                        <path d="M16 16 L22 6 L32 6 L32 16" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M16 29 L21 34 L32 23" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="header-text">
                    <span class="header-kicker">KARTU PEMILIH</span>
                    <h2 class="header-title">{{ strtoupper($election->name) }}</h2>
                    <span class="header-sub">MAN 3 NGAWI • {{ $election->academic_year ?? (date('Y').'/'.(date('Y')+1)) }}</span>
                </div>
            </div>
            <div class="header-right">
                <div class="badge-single-use">
                    <svg width="8" height="8" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.99-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
                    SEKALI PAKAI
                </div>
                <div class="header-hint">Gunakan hanya satu kali</div>
            </div>
        </div>

        <!-- BODY -->
        <div class="card-body">
            <!-- LEFT COLUMN -->
            <div class="body-left">
                <!-- Profile -->
                <div class="voter-profile">
                    <div class="voter-avatar">{{ strtoupper(substr($eligibility->voter->name, 0, 1)) }}</div>
                    <div class="voter-profile-text">
                        <h3 class="voter-name" title="{{ $eligibility->voter->name }}">{{ $eligibility->voter->name }}</h3>
                        <div class="voter-meta">{{ $eligibility->voter->class_name }} • NIS {{ $eligibility->voter->student_id }}</div>
                    </div>
                </div>

                <!-- NIS & KELAS -->
                <div class="data-grid">
                    <div class="data-box">
                        <div>
                            <div class="data-lbl">NIS</div>
                            <div class="data-val mono">{{ $eligibility->voter->student_id }}</div>
                        </div>
                        <div class="data-icon">
                            <svg width="22" height="22" viewBox="0 0 36 36" fill="none">
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
                            <div class="data-val">{{ $eligibility->voter->class_name }}</div>
                        </div>
                        <div class="data-icon">
                            <svg width="22" height="22" viewBox="0 0 36 36" fill="none">
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
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </div>
                        <div class="token-title">TOKEN PEMILIH</div>
                    </div>
                    <div class="token-divider"></div>
                    <div class="token-digits">
                        @foreach(str_split($eligibility->plainToken() ?? '') as $digit)
                            <div class="digit-box">{{ $digit }}</div>
                        @endforeach
                    </div>
                </div>

                <!-- STATUS & CARA PAKAI (SUSUN KE BAWAH) -->
                <div class="card-footer">
                    <div class="status-section">
                        <div class="status-badge-icon">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                <path d="M9 12l2 2 4-4"/>
                            </svg>
                        </div>
                        <div class="status-text-block">
                            <div class="lbl">STATUS</div>
                            <div class="val">AKTIF</div>
                        </div>
                    </div>

                    <div class="solid-sep"></div>

                    <div class="usage-section">
                        <div class="usage-title">CARA PAKAI</div>
                        <div class="steps-list">
                            <div class="step-item">
                                <span class="step-num">1</span>
                                <span>Scan QR Code</span>
                            </div>
                            <div class="step-item">
                                <span class="step-num">2</span>
                                <span>Masukkan Token</span>
                            </div>
                            <div class="step-item">
                                <span class="step-num">3</span>
                                <span>Pilih Suara</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN (QR) -->
            <div class="body-right">
                <div>
                    <div class="qr-header-title">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/>
                        </svg>
                        SCAN VERIFIKASI
                    </div>
                    <div class="qr-subtext">Pindai saat verifikasi / voting</div>
                </div>

                <div class="qr-image-wrapper">
                    {{-- P1-01 QR v2 opaque: tanpa NIS plain. --}}
                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(100)->margin(0)->generate(json_encode([
                        'v' => 2,
                        'el' => $election->id,
                        't' => $eligibility->plainToken(),
                    ])) !!}
                </div>

                <div class="qr-footer-hint">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                        <line x1="12" y1="18" x2="12.01" y2="18"/>
                    </svg>
                    <span>Buka kamera atau QR scanner</span>
                </div>
            </div>
        </div>

        <!-- CONFIDENTIALITY NOTE -->
        <div class="confidential-note">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                <path d="M12 8v4M12 16h.01"/>
            </svg>
            <span>Jaga kerahasiaan token Anda. Jangan berikan kepada siapapun.</span>
        </div>
    </div>
@endforeach
</div>

</body>
</html>
