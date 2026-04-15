<?php
$content = is_array($content ?? null) ? $content : [];
$home = is_array($content['home'] ?? null) ? $content['home'] : [];
$about = is_array($content['about'] ?? null) ? $content['about'] : [];
$contact = is_array($content['contact'] ?? null) ? $content['contact'] : [];
$slides = is_array($home['slides'] ?? null) ? $home['slides'] : [];
$aboutPoints = is_array($about['points'] ?? null) ? $about['points'] : [];
$aboutPointsText = implode("\n", array_map(static fn ($item): string => (string) $item, $aboutPoints));
?>

<div class="section-title website-content-page-title">
    <h2>ادیت محتوای وبسایت</h2>
    <p class="home-section-lead">این بخش فقط برای سوپر ادمین است و از همین‌جا می‌توانید متن‌ها و عکس‌های خانه، درباره ما و تماس با ما را خیلی ساده تغییر دهید.</p>
</div>

<div class="news-thumb website-content-shell">
    <div class="news-info">
        <div class="website-content-jump-links">
            <a href="#website-home">خانه</a>
            <a href="#website-about">درباره ما</a>
            <a href="#website-contact">تماس با ما</a>
        </div>

        <form method="post" action="<?= e(url('/website-content/update')) ?>" enctype="multipart/form-data" class="module-form website-content-form">
            <?= csrf_field() ?>

            <div class="full website-section-card" id="website-home">
                <div class="website-section-head">
                    <div>
                        <h3>صفحه خانه</h3>
                        <p>بخش اصلی صفحه خانه و سه کارت تصویری بالای صفحه از همین‌جا مدیریت می‌شود.</p>
                    </div>
                </div>

                <div class="website-content-grid">
                    <div class="form-group">
                        <label>تیتر کوچک</label>
                        <input type="text" name="home_hero_kicker" class="form-control" value="<?= e((string) ($home['hero_kicker'] ?? '')) ?>">
                    </div>

                    <div class="form-group full">
                        <label>عنوان اصلی خانه</label>
                        <input type="text" name="home_hero_title" class="form-control" value="<?= e((string) ($home['hero_title'] ?? '')) ?>">
                    </div>

                    <div class="form-group full">
                        <label>متن معرفی خانه</label>
                        <textarea name="home_hero_text" class="form-control" rows="4"><?= e((string) ($home['hero_text'] ?? '')) ?></textarea>
                    </div>
                </div>

                <div class="website-subsection-list">
                    <?php foreach ($slides as $index => $slide): ?>
                        <?php $item = $index + 1; ?>
                        <section class="website-subcard">
                            <div class="website-subcard-head">
                                <h4>کارت تصویری <?= e((string) $item) ?></h4>
                                <p>عنوان، متن و عکس همین کارت را تغییر دهید.</p>
                            </div>

                            <div class="website-content-grid">
                                <div class="form-group">
                                    <label>عنوان کارت</label>
                                    <input type="text" name="home_slide_<?= e((string) $item) ?>_title" class="form-control" value="<?= e((string) ($slide['title'] ?? '')) ?>">
                                </div>

                                <div class="form-group">
                                    <label>متن Alt عکس</label>
                                    <input type="text" name="home_slide_<?= e((string) $item) ?>_alt" class="form-control" value="<?= e((string) ($slide['alt'] ?? '')) ?>">
                                </div>

                                <div class="form-group full">
                                    <label>متن کارت</label>
                                    <textarea name="home_slide_<?= e((string) $item) ?>_text" class="form-control" rows="3"><?= e((string) ($slide['text'] ?? '')) ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label>آپلود عکس جدید</label>
                                    <input type="file" name="home_slide_<?= e((string) $item) ?>_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                                    <small class="field-help">اگر عکس تازه انتخاب نشود، عکس فعلی حفظ می‌شود.</small>
                                </div>

                                <div class="form-group">
                                    <label>عکس فعلی</label>
                                    <div class="website-image-preview">
                                        <img src="<?= e(file_url((string) ($slide['image'] ?? ''))) ?>" alt="<?= e((string) ($slide['alt'] ?? '')) ?>">
                                    </div>
                                </div>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="full website-section-card" id="website-about">
                <div class="website-section-head">
                    <div>
                        <h3>بخش درباره ما</h3>
                        <p>عنوان، لید، پاراگراف‌ها، نکات کلیدی و دو عکس این بخش از همین‌جا ویرایش می‌شود.</p>
                    </div>
                </div>

                <div class="website-content-grid">
                    <div class="form-group">
                        <label>عنوان بخش</label>
                        <input type="text" name="about_title" class="form-control" value="<?= e((string) ($about['title'] ?? '')) ?>">
                    </div>

                    <div class="form-group full">
                        <label>لید کوتاه</label>
                        <textarea name="about_lead" class="form-control" rows="3"><?= e((string) ($about['lead'] ?? '')) ?></textarea>
                    </div>

                    <div class="form-group full">
                        <label>پاراگراف اول</label>
                        <textarea name="about_paragraph_1" class="form-control" rows="4"><?= e((string) ($about['paragraph_1'] ?? '')) ?></textarea>
                    </div>

                    <div class="form-group full">
                        <label>پاراگراف دوم</label>
                        <textarea name="about_paragraph_2" class="form-control" rows="4"><?= e((string) ($about['paragraph_2'] ?? '')) ?></textarea>
                    </div>

                    <div class="form-group full">
                        <label>نکات کلیدی</label>
                        <textarea name="about_points" class="form-control" rows="5"><?= e($aboutPointsText) ?></textarea>
                        <small class="field-help">هر مورد را در یک خط جدا بنویسید.</small>
                    </div>

                    <div class="form-group">
                        <label>Alt عکس اصلی</label>
                        <input type="text" name="about_main_image_alt" class="form-control" value="<?= e((string) ($about['main_image_alt'] ?? '')) ?>">
                    </div>

                    <div class="form-group">
                        <label>Alt عکس دوم</label>
                        <input type="text" name="about_secondary_image_alt" class="form-control" value="<?= e((string) ($about['secondary_image_alt'] ?? '')) ?>">
                    </div>
                </div>

                <div class="website-double-upload">
                    <div class="website-upload-card">
                        <h4>عکس اصلی درباره ما</h4>
                        <div class="website-image-preview">
                            <img src="<?= e(file_url((string) ($about['main_image'] ?? ''))) ?>" alt="<?= e((string) ($about['main_image_alt'] ?? '')) ?>">
                        </div>
                        <input type="file" name="about_main_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                    </div>

                    <div class="website-upload-card">
                        <h4>عکس دوم درباره ما</h4>
                        <div class="website-image-preview">
                            <img src="<?= e(file_url((string) ($about['secondary_image'] ?? ''))) ?>" alt="<?= e((string) ($about['secondary_image_alt'] ?? '')) ?>">
                        </div>
                        <input type="file" name="about_secondary_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                    </div>
                </div>
            </div>

            <div class="full website-section-card" id="website-contact">
                <div class="website-section-head">
                    <div>
                        <h3>بخش تماس با ما</h3>
                        <p>معلومات تماس، متن معرفی و عنوان‌های این بخش را از اینجا به‌روز کنید.</p>
                    </div>
                </div>

                <div class="website-content-grid">
                    <div class="form-group">
                        <label>عنوان بخش</label>
                        <input type="text" name="contact_title" class="form-control" value="<?= e((string) ($contact['title'] ?? '')) ?>">
                    </div>

                    <div class="form-group">
                        <label>عنوان کارت تماس</label>
                        <input type="text" name="contact_intro_title" class="form-control" value="<?= e((string) ($contact['intro_title'] ?? '')) ?>">
                    </div>

                    <div class="form-group full">
                        <label>لید کوتاه تماس</label>
                        <textarea name="contact_lead" class="form-control" rows="3"><?= e((string) ($contact['lead'] ?? '')) ?></textarea>
                    </div>

                    <div class="form-group full">
                        <label>متن معرفی تماس</label>
                        <textarea name="contact_intro_text" class="form-control" rows="4"><?= e((string) ($contact['intro_text'] ?? '')) ?></textarea>
                    </div>

                    <div class="form-group full">
                        <label>آدرس</label>
                        <textarea name="contact_address" class="form-control" rows="3"><?= e((string) ($contact['address'] ?? '')) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>شماره تماس</label>
                        <input type="text" name="contact_phone" class="form-control" value="<?= e((string) ($contact['phone'] ?? '')) ?>">
                    </div>

                    <div class="form-group">
                        <label>عنوان کارت دسترسی سریع</label>
                        <input type="text" name="contact_quick_title" class="form-control" value="<?= e((string) ($contact['quick_title'] ?? '')) ?>">
                    </div>
                </div>
            </div>

            <div class="full form-actions website-content-actions">
                <button class="section-btn btn btn-default website-content-save" type="submit">ذخیره محتوای وبسایت</button>
                <a class="btn btn-default website-content-cancel" href="<?= e(url('/dashboard')) ?>">بازگشت به داشبورد</a>
            </div>
        </form>
    </div>
</div>
