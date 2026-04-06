<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقدیرنامه استاد</title>
    <link rel="stylesheet" href="<?= e(url('/assets/css/modules/teacher_appreciation_print.css')) ?>">
</head>
<body>
<div class="page">
    <div class="watermark">
        <img src="<?= e(url('/assets/images/logo.jpg')) ?>" alt="لوگو" onerror="this.style.display='none';">
    </div>
    <div class="islamic-frame">
        <div class="edge edge-top"></div>
        <div class="edge edge-bottom"></div>
        <div class="edge edge-left"></div>
        <div class="edge edge-right"></div>
        <div class="corner corner-tl">
            <svg viewBox="0 0 120 120" fill="none" stroke-width="2.5">
                <defs>
                    <linearGradient id="cornerGrad" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#f59e0b"/>
                        <stop offset="50%" stop-color="#10b981"/>
                        <stop offset="100%" stop-color="#0f172a"/>
                    </linearGradient>
                </defs>
                <path d="M10 110 A100 100 0 0 1 110 10" stroke="url(#cornerGrad)" stroke-linecap="round"/>
                <path d="M20 100 A80 80 0 0 1 100 20" stroke="url(#cornerGrad)" stroke-linecap="round"/>
                <path d="M58 18 L70 44 L98 56 L70 68 L58 94 L46 68 L18 56 L46 44 Z" fill="url(#cornerGrad)" opacity="0.25" stroke="url(#cornerGrad)"/>
            </svg>
        </div>
        <div class="corner corner-tr">
            <svg viewBox="0 0 120 120" fill="none" stroke-width="2.5">
                <defs>
                    <linearGradient id="cornerGrad" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#f59e0b"/>
                        <stop offset="50%" stop-color="#10b981"/>
                        <stop offset="100%" stop-color="#0f172a"/>
                    </linearGradient>
                </defs>
                <path d="M10 110 A100 100 0 0 1 110 10" stroke="url(#cornerGrad)" stroke-linecap="round"/>
                <path d="M20 100 A80 80 0 0 1 100 20" stroke="url(#cornerGrad)" stroke-linecap="round"/>
                <path d="M58 18 L70 44 L98 56 L70 68 L58 94 L46 68 L18 56 L46 44 Z" fill="url(#cornerGrad)" opacity="0.25" stroke="url(#cornerGrad)"/>
            </svg>
        </div>
        <div class="corner corner-bl">
            <svg viewBox="0 0 120 120" fill="none" stroke-width="2.5">
                <defs>
                    <linearGradient id="cornerGrad" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#f59e0b"/>
                        <stop offset="50%" stop-color="#10b981"/>
                        <stop offset="100%" stop-color="#0f172a"/>
                    </linearGradient>
                </defs>
                <path d="M10 110 A100 100 0 0 1 110 10" stroke="url(#cornerGrad)" stroke-linecap="round"/>
                <path d="M20 100 A80 80 0 0 1 100 20" stroke="url(#cornerGrad)" stroke-linecap="round"/>
                <path d="M58 18 L70 44 L98 56 L70 68 L58 94 L46 68 L18 56 L46 44 Z" fill="url(#cornerGrad)" opacity="0.25" stroke="url(#cornerGrad)"/>
            </svg>
        </div>
        <div class="corner corner-br">
            <svg viewBox="0 0 120 120" fill="none" stroke-width="2.5">
                <defs>
                    <linearGradient id="cornerGrad" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#f59e0b"/>
                        <stop offset="50%" stop-color="#10b981"/>
                        <stop offset="100%" stop-color="#0f172a"/>
                    </linearGradient>
                </defs>
                <path d="M10 110 A100 100 0 0 1 110 10" stroke="url(#cornerGrad)" stroke-linecap="round"/>
                <path d="M20 100 A80 80 0 0 1 100 20" stroke="url(#cornerGrad)" stroke-linecap="round"/>
                <path d="M58 18 L70 44 L98 56 L70 68 L58 94 L46 68 L18 56 L46 44 Z" fill="url(#cornerGrad)" opacity="0.25" stroke="url(#cornerGrad)"/>
            </svg>
        </div>
    </div>
    <div class="page-inner">
        <div class="header">
            <div class="logo">
                <img src="<?= e(url('/assets/images/logo.jpg')) ?>" alt="لوگو" onerror="this.style.display='none';">
            </div>
            <div class="org">
                <h1>دارالعلوم عالی الحاج سید منصور نادری</h1>
                <p>تقدیر از اساتید برتر</p>
            </div>
            <div style="width:90px;"></div>
        </div>
        <div class="title">
            تقدیرنامه
            <div class="title-divider"></div>
        </div>
        <div class="content">
            <p>
                بدینوسیله گواهی می‌گردد که استاد گرامی
                <span class="name"><?= e((string) ($teacher['name'] ?? '—')) ?></span>
                با تلاش پیگیر و روحیه‌ی آموزشی ممتاز، نقشی برجسته در پیشرفت شاگردان داشته‌اند.
            </p>
            <p>
                ایشان با تعهد، دقت و اخلاق نیکو در تدریس، همواره باعث ارتقای کیفیت آموزشی گردیده‌اند.
            </p>
            <p>
                بنا برین این تقدیرنامه به پاس خدمات ارزنده و کارکرد نیکشان به ایشان اهدا می‌گردد.
            </p>
        </div>
        <div class="footer">
            <div>تاریخ: <?= e((string) ($jalaliDate ?? date('Y-m-d'))) ?></div>
            <div class="signature">
                <div class="signature-line"></div>
                <div class="signature-title">امضای آمر دارالعلوم</div>
            </div>
        </div>
    </div>
</div>
<script>
window.addEventListener('load', function () {
    window.print();
});
</script>
</body>
</html>
