<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pemilihan - {{ $election->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11pt;
            color: #333;
            line-height: 1.6;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #4f46e5;
        }
        .header h1 {
            font-size: 20pt;
            color: #4f46e5;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 10pt;
            color: #666;
        }
        .info-section {
            margin-bottom: 25px;
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 8px;
        }
        .info-section h2 {
            font-size: 14pt;
            color: #4f46e5;
            margin-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 150px;
            padding: 5px 10px 5px 0;
            color: #555;
        }
        .info-value {
            display: table-cell;
            padding: 5px 0;
            color: #333;
        }
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }
        .stat-box {
            display: table-cell;
            width: 33.33%;
            padding: 15px;
            text-align: center;
            background-color: #f0f9ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
        }
        .stat-box + .stat-box {
            margin-left: 10px;
        }
        .stat-box .label {
            font-size: 9pt;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-box .value {
            font-size: 24pt;
            font-weight: bold;
            color: #1e40af;
            margin: 5px 0;
        }
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .results-table th {
            background-color: #4f46e5;
            color: white;
            padding: 12px;
            text-align: left;
            font-size: 11pt;
            font-weight: 600;
        }
        .results-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .results-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .results-table tr:hover {
            background-color: #f3f4f6;
        }
        .rank-1 {
            background-color: #fef3c7 !important;
            font-weight: bold;
        }
        .winner-badge {
            display: inline-block;
            background-color: #fbbf24;
            color: #78350f;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: bold;
            margin-left: 10px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }
        .signature-section {
            margin-top: 40px;
            text-align: right;
        }
        .signature-box {
            display: inline-block;
            text-align: center;
            width: 200px;
        }
        .signature-box .line {
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>HASIL PEMILIHAN</h1>
        <p>{{ $election->name }}</p>
    </div>

    <div class="info-section">
        <h2>Informasi Pemilihan</h2>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nama Pemilihan:</div>
                <div class="info-value">{{ $election->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Deskripsi:</div>
                <div class="info-value">{{ $election->description ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">{{ $election->status->value }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Periode:</div>
                <div class="info-value">
                    @if ($election->starts_at && $election->ends_at)
                        {{ $election->starts_at->format('d/m/Y H:i') }} - {{ $election->ends_at->format('d/m/Y H:i') }}
                    @else
                        -
                    @endif
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Tanggal Export:</div>
                <div class="info-value">{{ now()->format('d/m/Y H:i') }} WIB</div>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-box">
            <div class="label">Total Hak Pilih</div>
            <div class="value">{{ $results['total_eligible'] }}</div>
        </div>
        <div class="stat-box">
            <div class="label">Total Suara</div>
            <div class="value">{{ $results['total_votes'] }}</div>
        </div>
        <div class="stat-box">
            <div class="label">Partisipasi</div>
            <div class="value">{{ $results['turnout'] }}%</div>
        </div>
    </div>

    <div class="info-section">
        <h2>Hasil Perolehan Suara</h2>
        <table class="results-table">
            <thead>
                <tr>
                    <th style="width: 10%;">Peringkat</th>
                    <th style="width: 15%;">No. Urut</th>
                    <th style="width: 40%;">Nama Kandidat</th>
                    <th style="width: 20%;">Jumlah Suara</th>
                    <th style="width: 15%;">Persentase</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($results['results'] as $index => $result)
                    <tr class="{{ $index === 0 ? 'rank-1' : '' }}">
                        <td style="text-align: center;">{{ $index + 1 }}</td>
                        <td style="text-align: center;">{{ $result['candidate_number'] }}</td>
                        <td>
                            {{ $result['candidate_name'] }}
                            @if ($index === 0)
                                <span class="winner-badge">PEMENANG</span>
                            @endif
                        </td>
                        <td style="text-align: center;">{{ $result['vote_count'] }}</td>
                        <td style="text-align: center;">{{ $result['percentage'] }}%</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="font-weight: bold; background-color: #f3f4f6;">
                    <td colspan="3" style="text-align: right; padding: 12px;">TOTAL:</td>
                    <td style="text-align: center;">{{ $results['total_votes'] }}</td>
                    <td style="text-align: center;">100%</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <div>Panitia Pemilihan</div>
            <div class="line">
                (..................................)
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Dokumen ini digenerate secara otomatis oleh Sistem E-Voting</p>
        <p>{{ now()->format('d F Y, H:i') }} WIB</p>
    </div>
</body>
</html>
