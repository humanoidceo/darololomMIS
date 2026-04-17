<?php
$page = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$total = max(0, (int) ($total ?? 0));
$contactNotice = 'برای به دست آوردن فایل پی دی اف این پایان نامه با اداره دارالعلوم به تماس شوید.';
?>

<div class="section-title wow fadeInUp" data-wow-delay="0.1s">
    <h2>پایان‌نامه‌ها</h2>
</div>

<div class="news-thumb thesis-public-shell">
    <div class="news-info">
        <div class="thesis-public-head">
            <h3>آرشیف عمومی پایان‌نامه‌ها</h3>
            <p>در این صفحه پایان‌نامه‌های ثبت‌شده به‌صورت کارت‌های منظم و صفحه‌بندی ۱۰تایی نمایش داده می‌شوند و هر مورد صفحه‌ی جدا برای مطالعه چکیده دارد.</p>
        </div>

        <?php if (!empty($theses)): ?>
            <div class="row thesis-public-grid">
                <?php foreach ($theses as $thesis): ?>
                    <?php
                    $thesisId = (int) ($thesis['id'] ?? 0);
                    $abstractText = trim((string) ($thesis['abstract_text'] ?? ''));
                    $abstractPreview = mb_strlen($abstractText) > 180
                        ? mb_substr($abstractText, 0, 180) . '...'
                        : $abstractText;
                    ?>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <div class="thesis-public-card">
                            <div class="thesis-public-body">
                                <div class="article-public-badge thesis-public-badge">پایان‌نامه</div>
                                <h4 class="thesis-public-title">
                                    <a href="<?= e(url('/theses/' . $thesisId)) ?>"><?= e((string) ($thesis['student_name'] ?? '—')) ?></a>
                                </h4>

                                <div class="thesis-public-meta">
                                    <div><strong>استاد رهنما:</strong> <?= e((string) ($thesis['advisor_name'] ?? '—')) ?></div>
                                    <div><strong>سال:</strong> <?= e(to_persian_number((string) ((int) ($thesis['completion_year'] ?? 0)))) ?></div>
                                </div>

                                <p class="thesis-public-excerpt">
                                    <?= e($abstractPreview !== '' ? $abstractPreview : 'چکیده‌ای برای این پایان‌نامه ثبت نشده است.') ?>
                                </p>

                                <div class="thesis-public-note">
                                    <?= e($contactNotice) ?>
                                </div>

                                <div class="thesis-public-actions">
                                    <a class="btn btn-default thesis-read-btn" href="<?= e(url('/theses/' . $thesisId)) ?>">خواندن چکیده</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="pagination-wrap thesis-public-pagination">
                <span>صفحه <?= e(to_persian_number((string) $page)) ?> از <?= e(to_persian_number((string) $totalPages)) ?> | مجموع <?= e(to_persian_number((string) $total)) ?> پایان‌نامه</span>
                <div>
                    <?php if ($page > 1): ?>
                        <a class="btn btn-default btn-sm" href="<?= e(url('/theses?page=' . ($page - 1))) ?>">قبلی</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a class="btn btn-default btn-sm" href="<?= e(url('/theses?page=' . ($page + 1))) ?>">بعدی</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="article-empty-state">
                هنوز هیچ پایان‌نامه‌ای برای نمایش عمومی ثبت نشده است.
            </div>
        <?php endif; ?>
    </div>
</div>
