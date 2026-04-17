<?php
$book = is_array($book ?? null) ? $book : null;
?>

<?php if (!$book): ?>
    <div class="section-title">
        <h2>کتاب پیدا نشد</h2>
    </div>
    <div class="news-thumb">
        <div class="news-info">
            <div class="article-empty-state">کتاب مورد نظر برای مطالعه پیدا نشد.</div>
        </div>
    </div>
<?php else: ?>
    <?php
    $coverPath = trim((string) ($book['cover_image_path'] ?? ''));
    $pdfPath = trim((string) ($book['pdf_file_path'] ?? ''));
    $filename = basename($pdfPath);
    $pdfUrl = file_url($pdfPath);
    ?>

    <div class="section-title">
        <h2>مطالعه کتاب</h2>
    </div>

    <div class="news-thumb book-reader-shell">
        <div class="news-info">
            <div class="book-reader-top">
                <div class="book-reader-cover">
                    <img src="<?= e(file_url($coverPath)) ?>" alt="<?= e((string) ($book['title'] ?? 'کتاب')) ?>">
                </div>

                <div class="book-reader-copy">
                    <div class="article-public-badge book-public-badge">کتابخانه الکترونیکی</div>
                    <h3><?= e((string) ($book['title'] ?? '—')) ?></h3>

                    <div class="book-reader-meta">
                        <div><strong>مولف:</strong> <?= e((string) ($book['author'] ?? '—')) ?></div>
                        <div><strong>سال تالیف:</strong> <?= e(to_persian_number((string) ((int) ($book['publication_year'] ?? 0)))) ?></div>
                        <div><strong>تاریخ ثبت:</strong> <?= e((string) ($book['created_at'] ?? '—')) ?></div>
                    </div>

                    <div class="book-reader-actions">
                        <a class="btn btn-default book-read-btn" href="<?= e($pdfUrl) ?>" target="_blank">باز کردن PDF</a>
                        <a class="btn btn-default book-download-btn" href="<?= e($pdfUrl) ?>" download="<?= e($filename) ?>">دانلود کتاب</a>
                    </div>
                </div>
            </div>

            <div class="book-reader-frame-wrap">
                <iframe
                    src="<?= e($pdfUrl . '#toolbar=1&navpanes=0&view=FitH') ?>"
                    title="<?= e((string) ($book['title'] ?? 'مطالعه کتاب')) ?>"
                    class="book-reader-frame">
                </iframe>
            </div>

            <p class="field-help book-reader-help">
                اگر PDF در داخل صفحه باز نشد، از دکمه‌های بالا برای باز کردن مستقیم یا دانلود استفاده کنید.
            </p>
        </div>
    </div>
<?php endif; ?>
