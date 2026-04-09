<?php
$page = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$total = max(0, (int) ($total ?? 0));
?>

<div class="section-title wow fadeInUp" data-wow-delay="0.1s">
    <h2>مقالات</h2>
</div>

<div class="news-thumb article-public-shell">
    <div class="news-info">
        <div class="article-public-head">
            <h3>آرشیف عمومی مقالات</h3>
            <p>در این صفحه تمام مقالات اپلودشده برای مطالعه و دانلود در دسترس عموم قرار دارد.</p>
        </div>

        <?php if (!empty($articles)): ?>
            <div class="row article-public-grid">
                <?php foreach ($articles as $article): ?>
                    <?php
                    $authorName = trim((string) ($article['author_name'] ?? ''));
                    $authorFatherName = trim((string) ($article['author_father_name'] ?? ''));
                    $authorLabel = $authorName !== '' ? $authorName : '—';
                    if ($authorFatherName !== '') {
                        $authorLabel .= ' (' . $authorFatherName . ')';
                    }
                    $filePath = trim((string) ($article['file_path'] ?? ''));
                    $filename = basename($filePath);
                    ?>
                    <div class="col-md-6 col-sm-12">
                        <div class="article-public-card">
                            <div class="article-public-badge">مقاله</div>
                            <h4 class="article-public-title"><?= e($filename !== '' ? $filename : 'فایل مقاله') ?></h4>

                            <div class="article-public-meta">
                                <div><strong>مولف:</strong> <?= e($authorLabel) ?></div>
                                <div><strong>سال تالیف:</strong> <?= e(to_persian_number((string) ((int) ($article['publication_year'] ?? 0)))) ?></div>
                                <div><strong>تاریخ ثبت:</strong> <?= e((string) ($article['created_at'] ?? '—')) ?></div>
                            </div>

                            <div class="article-public-actions">
                                <a class="btn btn-default article-read-btn" href="<?= e(url($filePath)) ?>" target="_blank">خواندن مقاله</a>
                                <a class="btn btn-default article-download-btn" href="<?= e(url($filePath)) ?>" download="<?= e($filename) ?>">دانلود</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="pagination-wrap article-public-pagination">
                <span>صفحه <?= e(to_persian_number((string) $page)) ?> از <?= e(to_persian_number((string) $totalPages)) ?> | مجموع <?= e(to_persian_number((string) $total)) ?> مقاله</span>
                <div>
                    <?php if ($page > 1): ?>
                        <a class="btn btn-default btn-sm" href="<?= e(url('/articles?page=' . ($page - 1))) ?>">قبلی</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a class="btn btn-default btn-sm" href="<?= e(url('/articles?page=' . ($page + 1))) ?>">بعدی</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="article-empty-state">
                هنوز مقاله‌ای برای نمایش عمومی ثبت نشده است.
            </div>
        <?php endif; ?>
    </div>
</div>
