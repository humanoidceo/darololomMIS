<?php
$page = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$total = max(0, (int) ($total ?? 0));
$oldTitle = trim((string) ($oldTitle ?? ''));
$oldAuthor = trim((string) ($oldAuthor ?? ''));
$oldYear = trim((string) ($oldYear ?? ''));
?>

<div class="section-title">
    <h2>کتابخانه الکترونیکی</h2>
</div>

<div class="news-thumb book-admin-shell">
    <div class="news-info">
        <form method="post" action="<?= e((string) $formAction) ?>" enctype="multipart/form-data" class="module-form book-form-grid">
            <?= csrf_field() ?>

            <div class="form-group">
                <label>نام کتاب</label>
                <input type="text" name="title" class="form-control" value="<?= e($oldTitle) ?>" placeholder="مثال: تفسیر نور" required>
            </div>

            <div class="form-group">
                <label>مولف</label>
                <input type="text" name="author" class="form-control" value="<?= e($oldAuthor) ?>" placeholder="مثال: مولانا محمد یوسف" required>
            </div>

            <div class="form-group">
                <label>سال تالیف</label>
                <input type="number" name="publication_year" class="form-control" value="<?= e($oldYear) ?>" min="1" max="2500" placeholder="مثال: 1402" required>
            </div>

            <div class="form-group">
                <label>عکس پوش کتاب</label>
                <input type="file" name="cover_image" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
                <small class="field-help">فقط JPG، PNG و WEBP مجاز است.</small>
            </div>

            <div class="form-group">
                <label>فایل PDF کتاب</label>
                <input type="file" name="book_pdf" class="form-control" accept=".pdf,application/pdf" required>
                <small class="field-help">فقط فایل PDF پذیرفته می‌شود.</small>
            </div>

            <div class="form-actions full book-form-actions">
                <button class="section-btn btn btn-default book-save-btn" type="submit">ذخیره کتاب</button>
                <a class="btn btn-default book-cancel-btn" href="<?= e(url('/library')) ?>">مشاهده صفحه عمومی</a>
            </div>
        </form>
    </div>
</div>

<div class="news-thumb book-list-shell">
    <div class="news-info">
        <div class="book-list-head">
            <h3>فهرست کتاب‌ها</h3>
            <p>در این بخش کتاب‌های ثبت‌شده را با صفحه‌بندی ۱۰تایی می‌بینید و می‌توانید مطالعه، دانلود یا حذف انجام دهید.</p>
        </div>

        <?php if (!empty($books)): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover book-admin-table">
                    <thead>
                    <tr>
                        <th>پوش کتاب</th>
                        <th>نام کتاب</th>
                        <th>مولف</th>
                        <th>سال تالیف</th>
                        <th>فایل</th>
                        <th>تاریخ ثبت</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($books as $book): ?>
                        <?php
                        $coverPath = trim((string) ($book['cover_image_path'] ?? ''));
                        $pdfPath = trim((string) ($book['pdf_file_path'] ?? ''));
                        $filename = basename($pdfPath);
                        $bookId = (int) ($book['id'] ?? 0);
                        ?>
                        <tr>
                            <td class="book-cover-cell">
                                <div class="book-cover-thumb">
                                    <img src="<?= e(file_url($coverPath)) ?>" alt="<?= e((string) ($book['title'] ?? 'کتاب')) ?>">
                                </div>
                            </td>
                            <td><?= e((string) ($book['title'] ?? '—')) ?></td>
                            <td><?= e((string) ($book['author'] ?? '—')) ?></td>
                            <td><?= e(to_persian_number((string) ((int) ($book['publication_year'] ?? 0)))) ?></td>
                            <td>
                                <a class="btn btn-xs btn-info" href="<?= e(file_url($pdfPath)) ?>" target="_blank">PDF</a>
                            </td>
                            <td><?= e((string) ($book['created_at'] ?? '—')) ?></td>
                            <td class="book-action-cell">
                                <a class="btn btn-xs btn-primary" href="<?= e(url('/library/' . $bookId)) ?>" target="_blank">مطالعه</a>
                                <a class="btn btn-xs btn-default" href="<?= e(file_url($pdfPath)) ?>" download="<?= e($filename) ?>">دانلود</a>
                                <form method="post" action="<?= e(url('/library/' . $bookId . '/delete')) ?>" class="book-delete-form" onsubmit="return confirm('این کتاب حذف شود؟');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-xs btn-danger">حذف</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination-wrap book-pagination">
                <span>صفحه <?= e(to_persian_number((string) $page)) ?> از <?= e(to_persian_number((string) $totalPages)) ?> | مجموع <?= e(to_persian_number((string) $total)) ?> کتاب</span>
                <div>
                    <?php if ($page > 1): ?>
                        <a class="btn btn-default btn-sm" href="<?= e(url('/library/manage?page=' . ($page - 1))) ?>">قبلی</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a class="btn btn-default btn-sm" href="<?= e(url('/library/manage?page=' . ($page + 1))) ?>">بعدی</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="article-empty-state">
                هنوز هیچ کتابی در کتابخانه الکترونیکی ثبت نشده است.
            </div>
        <?php endif; ?>
    </div>
</div>
