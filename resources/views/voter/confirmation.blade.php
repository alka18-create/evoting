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
        .cd{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;z-index:50;padding:16px;overflow-y:auto}
        .cn{background:#fff;border-radius:24px;box-shadow:0 25px 50px -12px rgba(0,0,0,.25);width:100%;max-width:380px;overflow:hidden;margin:auto}
        .hd{background:linear-gradient(135deg,#10b981,#059669);padding:40px 32px 48px;text-align:center}
        .ci{width:80px;height:80px;background:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 10px 25px rgba(0,0,0,.15)}
        .ci svg{width:40px;height:40px;color:#10b981}
        .hd .hd-title{color:#fff;font-size:24px;font-weight:700}
        .hd p{color:#d1fae5;font-size:14px;margin-top:4px}
        .bd{padding:24px 32px 32px}
        .hb{background:#f9fafb;border-radius:12px;padding:12px 16px;text-align:center;margin-bottom:12px}
        .hb .lb{font-size:11px;color:#9ca3af;margin-bottom:4px}
        .hb .vc{font-family:monospace;font-weight:700;color:#374151;font-size:13px;letter-spacing:.5px;word-break:break-all}
        .copybtn{margin-top:8px;font-size:12px;font-weight:600;color:#059669;background:#d1fae5;border:none;border-radius:8px;padding:6px 14px;cursor:pointer}
        .copybtn:hover{background:#a7f3d0}
        .empty{background:#fef3c7;border:1px solid #fcd34d;border-radius:12px;padding:14px 16px;text-align:center;margin-bottom:20px;font-size:13px;color:#92400e}
        .empty a{color:#059669;font-weight:700}
        .note{font-size:11px;color:#9ca3af;text-align:center;margin-bottom:20px}
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
        .staybtn{font-size:12px;color:#6b7280;background:none;border:none;text-decoration:underline;cursor:pointer;margin-top:10px}
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
                @php $allHashes = $hashes ?? []; @endphp
                @if(count($allHashes) > 0)
                    <h1 class="hd-title">Suara Terkirim!</h1>
                    <p>Pilihan Anda berhasil direkam</p>
                @else
                    <p><strong>Kode verifikasi tidak tersedia</strong></p>
                    <p>Tautan sudah dipakai atau kedaluwarsa</p>
                @endif
            </div>
            <div class="bd">
                @if(count($allHashes) > 0)
                    @foreach($allHashes as $h)
                    <div class="hb">
                        <div class="lb">Kode Verifikasi {{ count($allHashes) > 1 ? '(' . ($loop->iteration) . '/' . count($allHashes) . ')' : '' }}</div>
                        <div class="vc" id="hash-{{ $loop->index }}">{{ $h }}</div>
                        <button type="button" class="copybtn" data-copy="hash-{{ $loop->index }}">Salin Kode</button>
                    </div>
                    @endforeach
                    @if(count($allHashes) > 1)
                        <div style="text-align:center;margin-bottom:20px"><button type="button" class="copybtn" id="copyAll">Salin Semua Kode</button></div>
                    @endif
                    <p class="note">Simpan kode ini sebagai bukti. Kode hanya tampil sekali dan tidak akan muncul lagi.</p>
                @else
                    <div class="empty">
                        Kode verifikasi sudah ditampilkan sebelumnya atau tautan kedaluwarsa.<br>Suara Anda tetap tercatat sah di sistem.<br><a href="{{ route('vote.login') }}">Kembali ke halaman login</a>
                    </div>
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
                    <div><button type="button" class="staybtn" id="stayBtn">Tetap di halaman ini</button></div>
                </div>
                <form method="POST" action="{{ route('vote.logout') }}" id="logoutForm">
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
            var stopped=false;
            document.getElementById('stayBtn').addEventListener('click',function(){
                stopped=true;
                clearInterval(iv);
                this.textContent='Auto-logout dibatalkan';
                this.disabled=true;
            });
            var iv=setInterval(function(){
                if(stopped) return;
                t--;
                num.textContent=t;
                ring.style.strokeDashoffset=c*(1-t/10);
                if(t<=0){
                    clearInterval(iv);
                    document.getElementById('logoutForm').submit();
                }
            },1000);

            function flash(btn,txt){
                var o=btn.textContent;
                btn.textContent=txt;
                setTimeout(function(){btn.textContent=o;},1500);
            }
            document.querySelectorAll('[data-copy]').forEach(function(btn){
                btn.addEventListener('click',function(){
                    var el=document.getElementById(btn.getAttribute('data-copy'));
                    var done=function(){flash(btn,'Tersalin!');};
                    if(navigator.clipboard&&navigator.clipboard.writeText){
                        navigator.clipboard.writeText(el.textContent.trim()).then(done,done);
                    }else{
                        var r=document.createRange();
                        r.selectNodeContents(el);
                        var s=getSelection();
                        s.removeAllRanges();s.addRange(r);
                        try{document.execCommand('copy');}catch(e){}
                        s.removeAllRanges();done();
                    }
                });
            });
            var copyAll=document.getElementById('copyAll');
            if(copyAll){
                copyAll.addEventListener('click',function(){
                    var vals=[].map.call(document.querySelectorAll('.vc'),function(e){return e.textContent.trim();});
                    var done=function(){flash(copyAll,'Semua tersalin!');};
                    if(navigator.clipboard&&navigator.clipboard.writeText){
                        navigator.clipboard.writeText(vals.join('\n')).then(done,done);
                    }else{done();}
                });
            }
        })();
    </script>
</body>
</html>
