<?php

namespace App\Domain\Voting\Services;

use App\Models\VotingEvent;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Kartu pemilih (identitas + token) untuk event voting.
 *
 * Hash-only: plaintext token TIDAK disimpan di DB. Kartu dicetak dari
 * $issued (flash sekali), tetapi hasil PDF-nya disimpan ke disk PRIVATE
 * (storage/app/private/token-cards) agar bisa diunduh ulang — aman dari
 * akses URL publik dan tetap terpisah dari backup database.
 */
class TokenCardService
{
    private const BASE_DIR = 'token-cards';

    private const FILE_PATTERN = '/^kartu-[A-Za-z0-9\-]+\.pdf$/';

    /**
     * QR v2 opaque — format yang diterima ScanController::verify.
     *
     * Konteks: ['eid' => voting_event_id] atau ['el' => election_id].
     * Selalu SVG: format png butuh ekstensi imagick (langka), SVG dirender
     * native oleh browser maupun DomPDF tanpa dependensi ekstensi.
     */
    public function qrDataUri(int $eventId, string $token): string
    {
        return $this->qrContextUri(['eid' => $eventId], $token);
    }

    public function qrContextUri(array $context, string $token): string
    {
        $payload = json_encode(['v' => 2] + $context + ['t' => $token], JSON_UNESCAPED_SLASHES);

        // generate() mengembalikan HtmlString di lingkungan Laravel.
        $svg = (string) QrCode::format('svg')->size(150)->margin(1)->generate($payload);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Render kartu baru ke PDF lalu simpan ke disk private.
     *
     * @param  string  $scope  kunci direktori — VotingEvent: "(string) $id", Election: "el-{id}"
     * @param  array  $qrContext  ['eid' => …] atau ['el' => …]
     * @param  Collection<int, array{student_id: string, name: string, class_name: string, token: string}>  $issued
     * @param  array  $viewData  data tambahan view PDF (mis. ['votingEvent' => …] / ['election' => …])
     * @return string|null nama file PDF, null bila kosong/gagal (tidak memblokir penerbitan token)
     */
    public function savePdfFor(string $scope, array $qrContext, Collection $issued, string $view, array $viewData = []): ?string
    {
        if ($issued->isEmpty()) {
            return null;
        }

        try {
            $cards = $issued->map(fn (array $t) => [
                'name' => $t['name'],
                'student_id' => $t['student_id'],
                'class_name' => $t['class_name'],
                'token' => $t['token'],
                'qr' => $this->qrContextUri($qrContext, $t['token']),
            ])->all();

            $pdf = Pdf::loadView($view, $viewData + ['cards' => $cards])
                ->setPaper('a4', 'portrait');

            $file = 'kartu-' . now()->format('Ymd-His') . '-' . Str::lower(Str::random(8)) . '.pdf';
            $written = Storage::disk('local')->put(self::BASE_DIR . '/' . $scope . '/' . $file, $pdf->output());

            return $written ? $file : null;
        } catch (\Throwable $e) {
            // PDF pelengkap: kegagalan render tidak boleh membatalkan token.
            report($e);

            return null;
        }
    }

    public function savePdf(VotingEvent $event, Collection $issued): ?string
    {
        return $this->savePdfFor(
            (string) $event->id,
            ['eid' => $event->id],
            $issued,
            'admin.voting-events.tokens.pdf.cards',
            ['votingEvent' => $event],
        );
    }

    /**
     * Daftar PDF kartu milik sebuah scope.
     *
     * @return array<int, array{file: string, size: int, modified: int}>
     */
    public function listPdfFor(string $scope): array
    {
        return collect(Storage::disk('local')->files(self::BASE_DIR . '/' . $scope))
            ->map(fn (string $path) => [
                'file' => basename($path),
                'size' => (int) Storage::disk('local')->size($path),
                'modified' => (int) Storage::disk('local')->lastModified($path),
            ])
            ->sortByDesc('modified')
            ->values()
            ->all();
    }

    public function listPdf(VotingEvent $event): array
    {
        return $this->listPdfFor((string) $event->id);
    }

    /**
     * Validasi nama file agar tidak bisa keluar direktori scope (path traversal).
     */
    public function pathPdfFor(string $scope, string $file): ?string
    {
        if (! preg_match(self::FILE_PATTERN, $file)) {
            return null;
        }

        $relative = self::BASE_DIR . '/' . $scope . '/' . $file;

        return Storage::disk('local')->exists($relative) ? $relative : null;
    }

    public function pathPdf(VotingEvent $event, string $file): ?string
    {
        return $this->pathPdfFor((string) $event->id, $file);
    }

    public function deletePdfFor(string $scope, string $file): bool
    {
        $relative = $this->pathPdfFor($scope, $file);

        return $relative !== null && Storage::disk('local')->delete($relative);
    }

    public function deletePdf(VotingEvent $event, string $file): bool
    {
        return $this->deletePdfFor((string) $event->id, $file);
    }
}
