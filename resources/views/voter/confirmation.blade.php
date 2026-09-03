<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <title>Suara Berhasil Dikirim</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#111827;min-height:100vh;display:flex;align-items:center;justify-content:center}
        .ov{position:fixed;inset:0;background:rgba(0,0,0,.5)}
        .cd{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;z-index:50;padding:16px}
        .cn{background:#fff;border-radius:24px;box-shadow:0 25px 50px -12px rgba(0,0,0,.25);width:100%;max-width:380px;overflow:hidden}
        .hd{background:linear-gradient(135deg,#10b981,#059669);padding:40px 32px 48px;text-align:center}
        .ci{width:80px;height:80px;background:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 10px 25px rgba(0,0,0,.15)}
        .ci svg{width:40px;height:40px;color:#10b981}
        .hd h1{color:#fff;font-size:24px;font-weight:700}
        .hd p{color:#d1fae5;font-size:14px;margin-top:4px}
        .bd{padding:24px 32px 32px}
        .hb{background:#f9fafb;border-radius:12px;padding:12px 16px;text-align:center;margin-bottom:20px}
        .hb .lb{font-size:11px;color:#9ca3af;margin-bottom:4px}
        .hb .vc{font-family:monospace;font-weight:700;color:#374151;font-size:13px;letter-spacing:.5px;word-break:break-all}
        .cs{text-align:center;margin-bottom:20px}
        .cl{font-size:12px;color:#9ca3af;margin-bottom:12px}
        .cw{display:inline-flex;align-items:center;gap:12px;background:#f9fafb;border-radius:16px;padding:12px 24px}
        .tm{position:relative;width:56px;height:56px}
        .tm svg{width:56px;height:56px;transform:rotate(-90deg)}
        .tm circle{fill:none;stroke-width:4}
        .tm .bg{stroke:#e5e7eb}
        .tm .fg{stroke:#10b981;stroke-linecap:round;transition:stroke-dashoffset 1s linear}
        .tm .nm{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;color:#1f2937}
        .ct{font-size:14px;color:#6b7280}
        .btn{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;background:#1f2937;color:#fff;font-size:15px;font-weight:600;padding:14px;border-radius:12px;border:none;cursor:pointer;text-decoration:none;transition:background .2s}
        .btn:hover{background:#111827}
        .btn svg{width:18px;height:18px}
    </style>
</head>
<body>
    <div class="ov"></div>
    <div class="cd">
        <div class="cn">
            <div class="hd">
                <div class="ci">
                    <svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h1>Suara Terkirim!</h1>
                <p>Pilihan Anda berhasil direkam</p>
            </div>
            <div class="bd">
                @if (request('hash') || request('hashes'))
                @php $allHashes = request('hashes') ? explode(',', request('hashes')) : [request('hash')]; @endphp
                @foreach($allHashes as $h)
                <div class="hb">
                    <div class="lb">Kode Verifikasi {{ count($allHashes) > 1 ? '(' . ($loop->iteration) . '/' . count($allHashes) . ')' : '' }}</div>
                    <div class="vc">{{ $h }}</div>
                </div>
                @endforeach
                @endif
                <div class="cs">
                    <div class="cl">Anda akan logout otomatis</div>
                    <div class="cw">
                        <div class="tm">
                            <svg viewBox="0 0 60 60">
                                <circle class="bg" cx="30" cy="30" r="26"/>
                                <circle class="fg" id="ring" cx="30" cy="30" r="26" stroke-dasharray="163.36" stroke-dashoffset="0"/>
                            </svg>
                            <div class="nm" id="num">10</div>
                        </div>
                        <span class="ct">detik</span>
                    </div>
                </div>
                <form method="POST" action="{{ route('vote.logout') }}">
                    @csrf
                    <button type="submit" class="btn">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Logout Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>
    <script>
        (function(){
            var t=10;
            var c=163.36;
            var ring=document.getElementById('ring');
            var num=document.getElementById('num');
            var iv=setInterval(function(){
                t--;
                num.textContent=t;
                ring.style.strokeDashoffset=c*(1-t/10);
                if(t<=0){
                    clearInterval(iv);
                    document.querySelector('form').submit();
                }
            },1000);
        })();
    </script>
</body>
</html>
