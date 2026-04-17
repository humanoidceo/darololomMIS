<?php
$page = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$total = max(0, (int) ($total ?? 0));
?>

<div class="section-title wow fadeInUp" data-wow-delay="0.1s">
    <h2>کتابخانه الکترونیکی</h2>
</div>

<div class="news-thumb book-public-shell">
    <div class="news-info">
        <div class="book-public-head">
            <h3>آرشیف عمومی کتاب‌ها</h3>
            <p>در این صفحه کتاب‌های ثبت‌شده با پوش کتاب، اطلاعات مولف، مطالعه آنلاین و دانلود در دسترس عموم قرار دارد.</p>
        </div>

        <?php if (!empty($books)): ?>
            <div class="row book-public-grid">
                <?php foreach ($books as $book): ?>
                    <?php
                    $bookId = (int) ($book['id'] ?? 0);
                    $coverPath = trim((string) ($book['cover_image_path'] ?? ''));
                    $pdfPath = trim((string) ($book['pdf_file_path'] ?? ''));
                    $filename = basename($pdfPath);
                    ?>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <div class="book-public-card">
                            <a href="<?= e(url('/library/' . $bookId)) ?>" class="book-public-cover-link">
                                <div class="book-public-cover">
                                    <img src="<?= e(file_url($coverPath)) ?>" alt="<?= e((string) ($book['title'] ?? 'کتاب')) ?>">
                                </div>
                            </a>

                            <div class="book-public-body">
                                <div class="article-public-badge book-public-badge">کتاب</div>
                                <h4 class="book-public-title">
                                    <a href="<?= e(url('/library/' . $bookId)) ?>"><?= e((string) ($book['title'] ?? '—')) ?></a>
                                </h4>

                                <div class="book-public-meta">
                                    <div><strong>مولف:</strong> <?= e((string) ($book['author'] ?? '—')) ?></div>
                                    <div><strong>سال تالیف:</strong> <?= e(to_persian_number((string) ((int) ($book['publication_year'] ?? 0)))) ?></div>
                                </div>

                                <div class="book-public-actions">
                                    <a class="btn btn-default book-read-btn" href="<?= e(url('/library/' . $bookId)) ?>">مطالعه کتاب</a>
                                    <a class="btn btn-default book-download-btn" href="<?= e(file_url($pdfPath)) ?>" download="<?= e($filename) ?>">دانلود</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="pagination-wrap book-public-pagination">
                <span>صفحه <?= e(to_persian_number((string) $page)) ?> از <?= e(to_persian_number((string) $totalPages)) ?> | مجموع <?= e(to_persian_number((string) $total)) ?> کتاب</span>
                <div>
                    <?php if ($page > 1): ?>
                        <a class="btn btn-default btn-sm" href="<?= e(url('/library?page=' . ($page - 1))) ?>">قبلی</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a class="btn btn-default btn-sm" href="<?= e(url('/library?page=' . ($page + 1))) ?>">بعدی</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="article-empty-state">
                هنوز هیچ کتابی برای نمایش عمومی ثبت نشده است.
            </div>
        <?php endif; ?>
    </div>
</div>
