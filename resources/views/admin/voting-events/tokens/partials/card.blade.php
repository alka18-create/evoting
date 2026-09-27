{{--
    Kartu pemilih: identitas + token + QR v2.
    Variabel: $event (VotingEvent|Election), $card (array{name, student_id, class_name, token}[, qr]).
    qr opsional — flash issued_tokens tidak membawanya, maka digenerate on-demand.
    $qrContext opsional: ['eid' => voting_event_id] (default) atau ['el' => election_id].
    Inline style — dipakai di browser (print) dan DomPDF, jangan pakai Tailwind.
--}}
@php
    $qrData = $card['qr'] ?? app(\App\Domain\Voting\Services\TokenCardService::class)
        ->qrContextUri($qrContext ?? ['eid' => (int) $event->id], (string) $card['token']);
@endphp
<div style="border:2px solid #111827;border-radius:14px;overflow:hidden;background:#fff;font-family:DejaVu Sans,Arial,sans-serif;color:#111827;page-break-inside:avoid;break-inside:avoid;">
    <div style="background:linear-gradient(135deg,#0f766e,#0891b2);color:#fff;padding:8px 12px;">
        <p style="margin:0;font-size:8pt;letter-spacing:2px;font-weight:bold;text-transform:uppercase;">Kartu Pemilih</p>
        <p style="margin:2px 0 0;font-size:10pt;font-weight:bold;">{{ $event->name }}</p>
    </div>

    <div style="padding:10px 12px;">
        <table style="width:100%;border-collapse:collapse;font-size:9pt;">
            <tr>
                <td style="padding:2px 0;color:#6b7280;width:52px;">Nama</td>
                <td style="padding:2px 0;font-weight:bold;">{{ $card['name'] }}</td>
            </tr>
            <tr>
                <td style="padding:2px 0;color:#6b7280;">NIS</td>
                <td style="padding:2px 0;font-family:monospace;">{{ $card['student_id'] }}</td>
            </tr>
            <tr>
                <td style="padding:2px 0;color:#6b7280;">Kelas</td>
                <td style="padding:2px 0;">{{ $card['class_name'] }}</td>
            </tr>
        </table>

        <table style="width:100%;border-collapse:collapse;margin-top:8px;">
            <tr>
                <td style="width:84px;vertical-align:middle;text-align:center;">
                    <img src="{{ $qrData }}" alt="QR Token" style="width:78px;height:78px;">
                </td>
                <td style="vertical-align:middle;padding-left:8px;">
                    <p style="margin:0;font-size:7.5pt;color:#6b7280;letter-spacing:1px;text-transform:uppercase;">Token Pemilih</p>
                    <p style="margin:3px 0 0;font-family:monospace;font-size:20pt;font-weight:bold;letter-spacing:4px;color:#0f172a;">{{ $card['token'] }}</p>
                </td>
            </tr>
        </table>

        <p style="margin:8px 0 0;padding-top:6px;border-top:1px dashed #d1d5db;font-size:7pt;color:#6b7280;">
            Rahasia &mdash; jangan dibagikan ke orang lain. Gunakan untuk login pemilihan. Simpan sampai pemilihan selesai.
        </p>
    </div>
</div>
