<?php
$thesis = is_array($thesis ?? null) ? $thesis : null;
$contactNotice = 'برای به دست آوردن فایل پی دی اف این پایان نامه با اداره دارالعلوم به تماس شوید.';
?>

<?php if (!$thesis): ?>
    <div class="section-title">
        <h2>پایان‌نامه پیدا نشد</h2>
    </div>
    <div class="news-thumb">
        <div class="news-info">
            <div class="article-empty-state">پایان‌نامه مورد نظر برای مطالعه پیدا نشد.</div>
        </div>
    </div>
<?php else: ?>
    <div class="section-title">
        <h2>مطالعه چکیده پایان‌نامه</h2>
    </div>

    <div class="news-thumb thesis-reader-shell">
        <div class="news-info">
            <div class="thesis-reader-head">
                <div class="article-public-badge thesis-public-badge">پایان‌نامه</div>
                <h3><?= e((string) ($thesis['student_name'] ?? '—')) ?></h3>
                <div class="thesis-reader-meta">
                    <div><strong>استاد رهنما:</strong> <?= e((string) ($thesis['advisor_name'] ?? '—')) ?></div>
                    <div><strong>سال:</strong> <?= e(to_persian_number((string) ((int) ($thesis['completion_year'] ?? 0)))) ?></div>
                    <div><strong>تاریخ ثبت:</strong> <?= e((string) ($thesis['created_at'] ?? '—')) ?></div>
                </div>
            </div>

            <div class="thesis-reader-abstract">
                <h4>چکیده</h4>
                <p><?= nl2br(e((string) ($thesis['abstract_text'] ?? '—'))) ?></p>
            </div>

            <div class="thesis-reader-note">
                <strong>یادداشت مهم:</strong>
                <span><?= e($contactNotice) ?></span>
            </div>

            <div class="thesis-reader-actions">
                <a class="btn btn-default thesis-read-btn" href="<?= e(url('/theses')) ?>">بازگشت به فهرست پایان‌نامه‌ها</a>
            </div>
        </div>
    </div>
<?php endif; ?>
