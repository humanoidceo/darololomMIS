<?php
$page = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$total = max(0, (int) ($total ?? 0));
$searchQuery = trim((string) ($searchQuery ?? ''));
$isSearching = $searchQuery !== '';
$publicBaseParams = [];
if ($isSearching) {
    $publicBaseParams['q'] = $searchQuery;
}
?>

<div class="section-title wow fadeInUp" data-wow-delay="0.1s">
    <h2>کتابخانه الکترونیکی</h2>
</div>

<div class="news-thumb book-public-shell">
    <div class="news-info">
        <div class="book-public-head">
            <h3>آرشیف عمومی کتاب‌ها</h3>
            <p>در این صفحه کتاب‌های ثبت‌شده با پوش کتاب، اطلاعات مولف، مطالعه آنلاین، دانلود و جستجوی سریع در دسترس عموم قرار دارد.</p>
        </div>

        <div class="book-search-shell book-search-shell-public">
            <form method="get" action="<?= e(url('/library')) ?>" class="book-search-form" role="search">
                <div class="book-search-copy">
                    <h4>جستجو در کتابخانه الکترونیکی</h4>
                    <p>برای پیدا کردن کتاب دلخواه، نام کتاب، مولف یا سال تالیف را وارد کنید.</p>
                </div>

                <div class="book-search-controls">
                    <label class="sr-only" for="publicBookSearch">جستجو در کتابخانه</label>
                    <input
                        id="publicBookSearch"
                        type="search"
                        name="q"
                        class="form-control book-search-input"
                        value="<?= e($searchQuery) ?>"
                        placeholder="مثال: حدیث، فقه، ۱۴۰۱">

                    <div class="book-search-actions">
                        <button type="submit" class="btn btn-default book-search-btn">
                            <i class="fa fa-search" aria-hidden="true"></i>
                            جستجو
                        </button>
                        <?php if ($isSearching): ?>
                            <a class="btn btn-default book-search-reset" href="<?= e(url('/library')) ?>">نمایش همه</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

            <div class="book-search-meta">
                <?php if ($isSearching): ?>
                    <?= e(to_persian_number((string) $total)) ?> نتیجه برای <strong><?= e($searchQuery) ?></strong> پیدا شد.
                <?php else: ?>
                    همه کتاب‌های موجود در کتابخانه برای مطالعه و دانلود آماده‌اند.
                <?php endif; ?>
            </div>
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
                        <?php $previousParams = $publicBaseParams + ['page' => $page - 1]; ?>
                        <a class="btn btn-default btn-sm" href="<?= e(url('/library?' . http_build_query($previousParams))) ?>">قبلی</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <?php $nextParams = $publicBaseParams + ['page' => $page + 1]; ?>
                        <a class="btn btn-default btn-sm" href="<?= e(url('/library?' . http_build_query($nextParams))) ?>">بعدی</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="article-empty-state">
                <?= e($isSearching ? 'برای این جستجو کتابی پیدا نشد.' : 'هنوز هیچ کتابی برای نمایش عمومی ثبت نشده است.') ?>
            </div>
        <?php endif; ?>
    </div>
</div>
