<?php
$page = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$total = max(0, (int) ($total ?? 0));
$oldStudentName = trim((string) ($oldStudentName ?? ''));
$oldAdvisorName = trim((string) ($oldAdvisorName ?? ''));
$oldYear = trim((string) ($oldYear ?? ''));
$oldAbstract = trim((string) ($oldAbstract ?? ''));
$contactNotice = 'برای به دست آوردن فایل پی دی اف این پایان نامه با اداره دارالعلوم به تماس شوید.';
?>

<div class="section-title">
    <h2>پایان‌نامه‌ها</h2>
</div>

<div class="news-thumb thesis-admin-shell">
    <div class="news-info">
        <form method="post" action="<?= e((string) $formAction) ?>" class="module-form thesis-form-grid">
            <?= csrf_field() ?>

            <div class="form-group">
                <label>نام محصل</label>
                <input type="text" name="student_name" class="form-control" value="<?= e($oldStudentName) ?>" placeholder="مثال: عبدالرحمن نادری" required>
            </div>

            <div class="form-group">
                <label>استاد رهنما</label>
                <input type="text" name="advisor_name" class="form-control" value="<?= e($oldAdvisorName) ?>" placeholder="مثال: شیخ محمد صابر" required>
            </div>

            <div class="form-group">
                <label>سال</label>
                <input type="number" name="completion_year" class="form-control" value="<?= e($oldYear) ?>" min="1" max="2500" placeholder="مثال: ۱۴۰۴" required>
            </div>

            <div class="form-group full">
                <label>چکیده پایان‌نامه</label>
                <textarea name="abstract_text" class="form-control thesis-abstract-input" rows="8" placeholder="خلاصه‌ی کامل و خوانای پایان‌نامه را در اینجا بنویسید..." required><?= e($oldAbstract) ?></textarea>
                <small class="field-help">این متن در صفحه عمومی برای مطالعه نمایش داده می‌شود.</small>
            </div>

            <div class="full thesis-form-note">
                <strong>یادداشت ثابت هر پایان‌نامه:</strong>
                <span><?= e($contactNotice) ?></span>
            </div>

            <div class="form-actions full thesis-form-actions">
                <button class="section-btn btn btn-default thesis-save-btn" type="submit">ذخیره پایان‌نامه</button>
                <a class="btn btn-default thesis-cancel-btn" href="<?= e(url('/theses')) ?>">مشاهده صفحه عمومی</a>
            </div>
        </form>
    </div>
</div>

<div class="news-thumb thesis-list-shell">
    <div class="news-info">
        <div class="thesis-list-head">
            <h3>فهرست پایان‌نامه‌ها</h3>
            <p>تمام پایان‌نامه‌های ثبت‌شده در این بخش با صفحه‌بندی ۱۰تایی نمایش داده می‌شوند و از همین‌جا قابل مشاهده یا حذف‌اند.</p>
        </div>

        <?php if (!empty($theses)): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover thesis-admin-table">
                    <thead>
                    <tr>
                        <th>نام محصل</th>
                        <th>استاد رهنما</th>
                        <th>سال</th>
                        <th>چکیده</th>
                        <th>تاریخ ثبت</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($theses as $thesis): ?>
                        <?php
                        $thesisId = (int) ($thesis['id'] ?? 0);
                        $abstractText = trim((string) ($thesis['abstract_text'] ?? ''));
                        $abstractPreview = mb_strlen($abstractText) > 110
                            ? mb_substr($abstractText, 0, 110) . '...'
                            : $abstractText;
                        ?>
                        <tr>
                            <td><?= e((string) ($thesis['student_name'] ?? '—')) ?></td>
                            <td><?= e((string) ($thesis['advisor_name'] ?? '—')) ?></td>
                            <td><?= e(to_persian_number((string) ((int) ($thesis['completion_year'] ?? 0)))) ?></td>
                            <td class="thesis-abstract-cell">
                                <div class="thesis-preview"><?= e($abstractPreview !== '' ? $abstractPreview : '—') ?></div>
                            </td>
                            <td><?= e((string) ($thesis['created_at'] ?? '—')) ?></td>
                            <td class="thesis-admin-actions">
                                <a class="btn btn-xs btn-primary" href="<?= e(url('/theses/' . $thesisId)) ?>" target="_blank">مشاهده</a>
                                <form method="post" action="<?= e(url('/theses/' . $thesisId . '/delete')) ?>" class="thesis-delete-form" onsubmit="return confirm('این پایان‌نامه حذف شود؟');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-xs btn-danger">حذف</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination-wrap thesis-pagination">
                <span>صفحه <?= e(to_persian_number((string) $page)) ?> از <?= e(to_persian_number((string) $totalPages)) ?> | مجموع <?= e(to_persian_number((string) $total)) ?> پایان‌نامه</span>
                <div>
                    <?php if ($page > 1): ?>
                        <a class="btn btn-default btn-sm" href="<?= e(url('/theses/manage?page=' . ($page - 1))) ?>">قبلی</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a class="btn btn-default btn-sm" href="<?= e(url('/theses/manage?page=' . ($page + 1))) ?>">بعدی</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="article-empty-state">
                هنوز هیچ پایان‌نامه‌ای ثبت نشده است.
            </div>
        <?php endif; ?>
    </div>
</div>
