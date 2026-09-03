<x-layouts.admin title="Scan Token QR">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-primary-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="scan-line" class="w-8 h-8 text-primary-600"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800">Scan QR Token Pemilih</h3>
                <p class="text-sm text-gray-500 mt-1">Arahkan kamera ke QR Code pada kartu token</p>
            </div>

            <!-- Camera Selector -->
            <div id="camera-select-wrapper" class="mb-4 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Kamera</label>
                <select id="camera-select" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <option value="">Memuat kamera...</option>
                </select>
            </div>

            <!-- Scanner Container -->
            <div class="relative rounded-xl overflow-hidden bg-gray-900 mb-6" id="scanner-wrapper" style="min-height: 300px;">
                <div id="scanner-reader"></div>
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none" id="scanner-overlay">
                    <div class="w-52 h-52 border-2 border-emerald-400 rounded-2xl shadow-lg" style="box-shadow: 0 0 0 9999px rgba(0,0,0,0.5);"></div>
                </div>
            </div>

            <!-- Status -->
            <div id="scanner-status" class="text-center text-sm text-gray-500 mb-4">
                Menyalakan kamera...
            </div>

            <!-- Scan Result -->
            <div id="scan-result" class="hidden">
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-4">
                    <div class="flex items-center gap-3 mb-3">
                        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
                        <span class="font-semibold text-emerald-800">QR Code Terdeteksi!</span>
                    </div>
                    <div class="bg-white rounded-lg p-3 text-sm space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-500">NIS:</span>
                            <span class="font-mono font-semibold text-gray-800" id="result-student-id">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Token:</span>
                            <span class="font-mono font-semibold text-gray-800" id="result-token">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Event / Election ID:</span>
                            <span class="font-mono font-semibold text-gray-800" id="result-event-id">-</span>
                        </div>
                    </div>
                </div>

                <form action="{{ route('admin.tokens.scan-verify') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="student_id" id="form-student-id">
                    <input type="hidden" name="token" id="form-token">
                    <input type="hidden" name="election_id" id="form-election-id">
                    <input type="hidden" name="voting_event_id" id="form-voting-event-id">

                    <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-emerald-600 text-white rounded-xl font-semibold hover:bg-emerald-700 transition-colors">
                        <i data-lucide="check" class="w-5 h-5"></i>
                        Verifikasi & Tandai Hadir
                    </button>
                </form>
            </div>

            <!-- Error -->
            <div id="scan-error" class="hidden">
                <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <p class="text-sm font-medium text-red-800" id="error-title">Gagal Mengakses Kamera</p>
                            <p class="text-xs text-red-600 mt-1" id="error-message"></p>
                            <div class="mt-3 flex gap-2">
                                <button onclick="startScanner()" class="text-xs font-medium text-red-700 bg-red-100 px-3 py-1.5 rounded-lg hover:bg-red-200 transition-colors">
                                    Coba Lagi
                                </button>
                                <a href="{{ route('admin.scan.show') }}" class="text-xs font-medium text-gray-600 bg-gray-100 px-3 py-1.5 rounded-lg hover:bg-gray-200 transition-colors">
                                    Muat Ulang Halaman
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-4 flex justify-center gap-3">
                <button onclick="startScanner()" id="btn-retry" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-primary-600 bg-primary-50 rounded-xl hover:bg-primary-100 transition-colors hidden">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    Mulai Ulang
                </button>
                <button onclick="stopScanner()" id="btn-stop" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-600 bg-red-50 rounded-xl hover:bg-red-100 transition-colors hidden">
                    <i data-lucide="camera-off" class="w-4 h-4"></i>
                    Hentikan
                </button>
            </div>
        </div>
    </div>

    {{-- P2-03: vendored lokal (public/vendor) — tanpa CDN unpkg, pin versi 2.3.8. --}}
    <script src="{{ asset('vendor/html5-qrcode-2.3.8.min.js') }}" integrity="sha384-c9d8RFSL+u3exBOJ4Yp3HUJXS4znl9f+z66d1y54ig+ea249SpqR+w1wyvXz/lk+" crossorigin="anonymous" defer></script>
    <script>
        let html5QrCode = null;
        let currentCameraId = null;

        function setStatus(msg) {
            document.getElementById('scanner-status').textContent = msg;
        }

        function showError(title, msg) {
            document.getElementById('error-title').textContent = title;
            document.getElementById('error-message').textContent = msg;
            document.getElementById('scan-error').classList.remove('hidden');
            document.getElementById('btn-retry').classList.remove('hidden');
        }

        function hideError() {
            document.getElementById('scan-error').classList.add('hidden');
        }

        function stopScanner() {
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop().then(() => {
                    setStatus('Scanner dihentikan.');
                    document.getElementById('btn-stop').classList.add('hidden');
                    document.getElementById('btn-retry').classList.remove('hidden');
                }).catch(() => {});
            }
        }

        async function enumerateCameras() {
            try {
                // Request permission first
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                stream.getTracks().forEach(t => t.stop());

                const devices = await navigator.mediaDevices.enumerateDevices();
                const cameras = devices.filter(d => d.kind === 'videoinput');

                const select = document.getElementById('camera-select');
                select.innerHTML = '';

                if (cameras.length === 0) {
                    showError('Tidak Ada Kamera', 'Tidak ditemukan kamera pada perangkat ini.');
                    return [];
                }

                cameras.forEach((cam, idx) => {
                    const opt = document.createElement('option');
                    opt.value = cam.deviceId;
                    opt.textContent = cam.label || `Kamera ${idx + 1}`;
                    select.appendChild(opt);
                });

                // Prefer back camera
                const backCam = cameras.find(c => c.label.toLowerCase().includes('back') || c.label.toLowerCase().includes('rear') || c.label.toLowerCase().includes('environment'));
                if (backCam) {
                    select.value = backCam.deviceId;
                }

                document.getElementById('camera-select-wrapper').classList.remove('hidden');
                return cameras;
            } catch (err) {
                showError('Izin Kamera Ditolak', 'Izinkan akses kamera di browser Anda. Klik ikon gembok di address bar.');
                return [];
            }
        }

        async function startScanner() {
            hideError();
            document.getElementById('scan-result').classList.add('hidden');
            document.getElementById('btn-retry').classList.add('hidden');
            document.getElementById('btn-stop').classList.remove('hidden');
            setStatus('Memuat kamera...');

            // Stop previous instance
            if (html5QrCode && html5QrCode.isScanning) {
                try { await html5QrCode.stop(); } catch(e) {}
            }

            // Enumerate cameras if not done
            if (!document.getElementById('camera-select').options.length || document.getElementById('camera-select').options[0].value === '') {
                const cameras = await enumerateCameras();
                if (cameras.length === 0) return;
            }

            const selectedCameraId = document.getElementById('camera-select').value;
            if (!selectedCameraId) {
                showError('Pilih Kamera', 'Silakan pilih kamera terlebih dahulu.');
                return;
            }

            html5QrCode = new Html5Qrcode("scanner-reader");

            html5QrCode.start(
                selectedCameraId,
                {
                    fps: 10,
                    qrbox: { width: 200, height: 200 },
                    aspectRatio: 1.0,
                    disableFlip: false,
                },
                (decodedText) => {
                    // Vibrate on success
                    if (navigator.vibrate) navigator.vibrate(200);

                    html5QrCode.stop().catch(() => {});

                    try {
                        const data = JSON.parse(decodedText);
                        // P1-01: QR v2 opaque {v:2, eid/el, t} tanpa NIS + legacy v1 {student_id, token, ...}
                        const eventId = data.voting_event_id || data.event_id || data.eid || null;
                        const electionId = data.election_id || data.el || null;
                        const token = data.token || data.t || null;
                        // v1 legacy wajib student_id; v2 boleh tanpa student_id (resolve server-side by token)
                        const studentId = data.student_id || '';
                        const isV2 = (data.v === 2);
                        if (token && (eventId || electionId) && (studentId || isV2)) {
                            document.getElementById('result-student-id').textContent = studentId || '(QR v2 — NIS di-resolve server)';
                            document.getElementById('result-token').textContent = '••••••••';
                            document.getElementById('result-event-id').textContent = eventId ? 'Event #' + eventId : 'Election #' + electionId;
                            document.getElementById('form-student-id').value = studentId;
                            document.getElementById('form-token').value = token;
                            document.getElementById('form-election-id').value = electionId || '';
                            document.getElementById('form-voting-event-id').value = eventId || '';
                            document.getElementById('scan-result').classList.remove('hidden');
                            setStatus(isV2 ? 'QR v2 terdeteksi (tanpa NIS) — siap verifikasi.' : 'QR Code berhasil dipindai!');
                            document.getElementById('btn-stop').classList.add('hidden');
                        } else {
                            throw new Error('Invalid QR data');
                        }
                    } catch (e) {
                        // P1-01: jangan echo isi QR (bisa berisi token) ke layar/log.
                        showError('QR Code Tidak Valid', 'Format QR tidak dikenali. Gunakan kartu terbaru (QR v2) atau input manual.');
                        setStatus('QR Code tidak valid. Coba lagi.');
                    }
                },
                (errorMessage) => { /* scanning in progress */ }
            ).then(() => {
                setStatus('Kamera aktif. Arahkan ke QR Code.');
            }).catch(err => {
                let msg = err.toString();
                if (msg.includes('NotAllowedError') || msg.includes('Permission')) {
                    msg = 'Izin kamera ditolak. Izinkan akses kamera di browser.';
                } else if (msg.includes('NotFoundError') || msg.includes('DevicesNotFound')) {
                    msg = 'Kamera tidak ditemukan. Pastikan kamera terhubung.';
                } else if (msg.includes('NotReadableError') || msg.includes('TrackStartError')) {
                    msg = 'Kamera sedang digunakan aplikasi lain. Tutup aplikasi lain terlebih dahulu.';
                }
                showError('Gagal Memulai Kamera', msg);
                document.getElementById('btn-stop').classList.add('hidden');
            });
        }

        // Camera change handler
        document.getElementById('camera-select').addEventListener('change', () => {
            startScanner();
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', startScanner);
    </script>
    <script>lucide.createIcons();</script>
</x-layouts.admin>
