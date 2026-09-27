<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Pemilih — {{ $election->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; color: #111827; padding: 14px; }
        .page-title { font-size: 12pt; font-weight: bold; margin-bottom: 4px; }
        .page-meta { font-size: 8pt; color: #6b7280; margin-bottom: 12px; }
        .grid { width: 100%; }
        .grid table { width: 100%; border-collapse: separate; border-spacing: 8px; }
        .grid td { width: 50%; vertical-align: top; }
        .note { font-size: 7.5pt; color: #6b7280; margin-top: 10px; }
    </style>
</head>
<body>
    <p class="page-title">Kartu Pemilih — {{ $election->name }}</p>
    <p class="page-meta">{{ count($cards) }} kartu &middot; dicetak {{ now()->format('d/m/Y H:i') }}</p>

    <div class="grid">
        <table>
            @foreach (array_chunk($cards, 2) as $row)
                <tr>
                    @foreach ($row as $card)
                        <td>@include('admin.voting-events.tokens.partials.card', ['event' => $election, 'card' => $card, 'qrContext' => ['el' => (int) $election->id]])</td>
                    @endforeach
                    @if (count($row) === 1)
                        <td></td>
                    @endif
                </tr>
            @endforeach
        </table>
    </div>

    <p class="note">Token disimpan sebagai hash di sistem (hash-only). PDF ini adalah salinan kartu untuk dibagikan ke pemilih.</p>
</body>
</html>
