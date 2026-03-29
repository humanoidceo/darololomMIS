<?php
$linkedUser = $linkedUser ?? null;
$oldOr = static fn(string $key, mixed $fallback = ''): mixed => old($key, $teacher[$key] ?? $fallback);

$selectedPeriodIdsInput = old('period_ids', $selectedPeriodIds ?? []);
if (!is_array($selectedPeriodIdsInput)) {
    $selectedPeriodIdsInput = [];
}
$selectedPeriodIds = array_map('intval', $selectedPeriodIdsInput);
$birthDateValue = (string) $oldOr('birth_date');
$jalaliMonths = ['حمل', 'ثور', 'جوزا', 'سرطان', 'اسد', 'سنبله', 'میزان', 'عقرب', 'قوس', 'جدی', 'دلو', 'حوت'];
$accountEmail = (string) old('account_email', (string) ($linkedUser['email'] ?? ''));
$mustSetPassword = empty($linkedUser['id']);
$isEditMode = !empty($teacher['id']);
?>

<div class="section-title">
    <h2><?= e($teacher ? 'ویرایش استاد' : 'ثبت استاد جدید') ?></h2>
</div>

<div class="news-thumb teacher-wizard-wrap">
    <div class="news-info">
        <div class="wizard-header">
            <button type="button" class="wizard-step is-active" data-step-target="1">۱) اطلاعات فردی و سکونت اصلی</button>
            <button type="button" class="wizard-step" data-step-target="2">۲) سکونت فعلی</button>
            <button type="button" class="wizard-step" data-step-target="3">۳) اسناد</button>
            <button type="button" class="wizard-step" data-step-target="4">۴) تدریس و حساب</button>
        </div>

        <form id="teacherWizardForm" method="post" action="<?= e($formAction) ?>" enctype="multipart/form-data" class="module-form teacher-form-grid" novalidate>
            <?= csrf_field() ?>

            <section class="wizard-panel is-active" data-step-panel="1">
                <div class="form-group">
                    <label>نام و تخلص استاد</label>
                    <input class="form-control" name="name" value="<?= e((string) $oldOr('name')) ?>" placeholder="مثال: مولوی عبدالرحیم عابدی" minlength="3" maxlength="255" required>
                    <small class="field-help">نام کامل استاد را وارد کنید (حداقل ۳ حرف).</small>
                </div>

                <div class="form-group">
                    <label>نام پدر</label>
                    <input class="form-control" name="father_name" value="<?= e((string) $oldOr('father_name')) ?>" placeholder="مثال: عبدالکریم" minlength="3" maxlength="255" required>
                    <small class="field-help">نام پدر استاد الزامی است.</small>
                </div>

                <div class="form-group">
                    <label>تاریخ تولد (هجری شمسی)</label>
                    <div class="jalali-date-grid">
                        <select class="form-control" id="teacher_birth_date_day" required>
                            <option value="">روز</option>
                            <?php for ($d = 1; $d <= 31; $d++): ?>
                                <option value="<?= e((string) $d) ?>"><?= e((string) $d) ?></option>
                            <?php endfor; ?>
                        </select>
                        <select class="form-control" id="teacher_birth_date_month" required>
                            <option value="">ماه</option>
                            <?php foreach ($jalaliMonths as $idx => $monthName): ?>
                                <option value="<?= e((string) ($idx + 1)) ?>"><?= e($monthName) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select class="form-control" id="teacher_birth_date_year" required>
                            <option value="">سال</option>
                            <?php for ($y = 1460; $y >= 1330; $y--): ?>
                                <option value="<?= e((string) $y) ?>"><?= e((string) $y) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <input type="hidden" name="birth_date" id="teacher_birth_date" value="<?= e($birthDateValue) ?>">
                    <small class="field-help">تاریخ را به جنتری هجری شمسی با ماه‌های افغانی انتخاب کنید.</small>
                </div>

                <div class="form-group">
                    <label>جنسیت</label>
                    <select class="form-control" name="gender" required>
                        <option value="male" <?= (string) $oldOr('gender', 'male') === 'male' ? 'selected' : '' ?>>مرد</option>
                        <option value="female" <?= (string) $oldOr('gender') === 'female' ? 'selected' : '' ?>>زن</option>
                    </select>
                    <small class="field-help">یکی از گزینه‌ها را انتخاب کنید.</small>
                </div>

                <div class="form-group">
                    <label>سویه تحصیلی</label>
                    <select class="form-control" name="education_level" required>
                        <option value="p" <?= (string) $oldOr('education_level', 'p') === 'p' ? 'selected' : '' ?>>چهارده پاس</option>
                        <option value="b" <?= (string) $oldOr('education_level') === 'b' ? 'selected' : '' ?>>لیسانس</option>
                        <option value="m" <?= (string) $oldOr('education_level') === 'm' ? 'selected' : '' ?>>ماستر</option>
                        <option value="d" <?= (string) $oldOr('education_level') === 'd' ? 'selected' : '' ?>>دوکتور</option>
                    </select>
                    <small class="field-help">آخرین درجه تحصیلی استاد.</small>
                </div>

                <div class="form-group">
                    <label>نمبر تذکره</label>
                    <input class="form-control" name="id_number" value="<?= e((string) $oldOr('id_number')) ?>" placeholder="مثال: 12345-98765" pattern="[0-9A-Za-z\-\/\s]+" maxlength="100" required>
                    <small class="field-help">اعداد/حروف انگلیسی، خط تیره و اسلش مجاز است.</small>
                </div>

                <div class="form-group">
                    <label>ولایت سکونت اصلی</label>
                    <input class="form-control" name="permanent_address" value="<?= e((string) $oldOr('permanent_address')) ?>" placeholder="مثال: بغلان" minlength="2" required>
                    <small class="field-help">این بخش مربوط سکونت اصلی است.</small>
                </div>

                <div class="form-group">
                    <label>ولسوالی سکونت اصلی</label>
                    <input class="form-control" name="district" value="<?= e((string) $oldOr('district')) ?>" placeholder="مثال: خنجان" maxlength="150" required>
                    <small class="field-help">نام ولسوالی سکونت اصلی را وارد کنید.</small>
                </div>

                <div class="form-group">
                    <label>قریه سکونت اصلی</label>
                    <input class="form-control" name="village" value="<?= e((string) $oldOr('village')) ?>" placeholder="مثال: نوآباد" maxlength="150" required>
                    <small class="field-help">نام قریه سکونت اصلی را وارد کنید.</small>
                </div>
            </section>

            <section class="wizard-panel" data-step-panel="2">
                <div class="form-group">
                    <label>ولایت سکونت فعلی</label>
                    <input class="form-control" name="current_address" value="<?= e((string) $oldOr('current_address')) ?>" placeholder="مثال: کابل" minlength="2" required>
                    <small class="field-help">ولایت محل سکونت فعلی را وارد کنید.</small>
                </div>

                <div class="form-group">
                    <label>ناحیه سکونت فعلی</label>
                    <input class="form-control" name="area" value="<?= e((string) $oldOr('area')) ?>" placeholder="مثال: ناحیه ۴" maxlength="150" required>
                    <small class="field-help">ناحیه سکونت فعلی را وارد کنید.</small>
                </div>

                <div class="form-group">
                    <label>کوچه سکونت فعلی</label>
                    <input class="form-control" name="current_street" value="<?= e((string) $oldOr('current_street')) ?>" placeholder="مثال: کوچه گلستان" maxlength="150" required>
                    <small class="field-help">نام کوچه سکونت فعلی را وارد کنید.</small>
                </div>
            </section>

            <section class="wizard-panel" data-step-panel="3">
                <div class="form-group">
                    <label>عکس استاد</label>
                    <input type="file" class="form-control" name="image" accept=".jpg,.jpeg,.png,.webp,image/*">
                    <small class="field-help">فرمت مجاز: JPG/PNG/WEBP | حداکثر 2MB</small>
                    <?php if (!empty($teacher['image_path'])): ?><a href="<?= e(url($teacher['image_path'])) ?>" target="_blank">نمایش فایل فعلی</a><?php endif; ?>
                </div>

                <div class="form-group">
                    <label>سند تحصیلی</label>
                    <input type="file" class="form-control" name="education_document" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/*">
                    <small class="field-help">PDF/JPG/PNG | حداکثر 5MB</small>
                    <?php if (!empty($teacher['education_document'])): ?><a href="<?= e(url($teacher['education_document'])) ?>" target="_blank">نمایش فایل فعلی</a><?php endif; ?>
                </div>

                <div class="form-group">
                    <label>سند تجربه کاری</label>
                    <input type="file" class="form-control" name="experience_document" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/*">
                    <small class="field-help">PDF/JPG/PNG | حداکثر 5MB</small>
                    <?php if (!empty($teacher['experience_document'])): ?><a href="<?= e(url($teacher['experience_document'])) ?>" target="_blank">نمایش فایل فعلی</a><?php endif; ?>
                </div>
            </section>

            <section class="wizard-panel" data-step-panel="4">
                <div class="form-group full picker-group" id="teacher_period_block">
                    <label>دوره‌ها</label>
                    <div class="inline-checks">
                        <?php foreach ($periods as $item): ?>
                            <label><input type="checkbox" name="period_ids[]" value="<?= e((string) $item['id']) ?>" <?= in_array((int) $item['id'], $selectedPeriodIds, true) ? 'checked' : '' ?>> <?= e((string) $item['number']) ?></label>
                        <?php endforeach; ?>
                    </div>
                    <small class="field-help">در صورت نیاز دوره‌ها را انتخاب کنید.</small>
                </div>

                <div class="form-group">
                    <label>ایمیل حساب استاد</label>
                    <input class="form-control" type="email" name="account_email" id="teacher_account_email" value="<?= e($accountEmail) ?>" placeholder="مثال: teacher@example.com" required>
                    <small class="field-help">استاد با همین ایمیل وارد حساب خود می‌شود.</small>
                </div>

                <div class="form-group">
                    <label>رمز عبور حساب استاد</label>
                    <input class="form-control" type="password" name="account_password" id="teacher_account_password" placeholder="<?= $mustSetPassword ? 'حداقل ۸ کاراکتر (الزامی)' : 'اگر تغییر نمی‌دهید خالی بگذارید' ?>" minlength="8" <?= $mustSetPassword ? 'required' : '' ?>>
                    <small class="field-help"><?= $mustSetPassword ? 'برای ایجاد حساب استاد، رمز عبور الزامی است.' : 'در ویرایش، فقط در صورت نیاز رمز جدید وارد کنید.' ?></small>
                </div>

                <div class="form-group">
                    <label>تکرار رمز عبور</label>
                    <input class="form-control" type="password" name="account_password_confirmation" id="teacher_account_password_confirmation" placeholder="تکرار رمز عبور" minlength="8" <?= $mustSetPassword ? 'required' : '' ?>>
                    <small class="field-help">باید دقیقاً با رمز عبور یکسان باشد.</small>
                </div>
            </section>

            <div class="full wizard-actions">
                <?php if (!$isEditMode): ?>
                    <button type="button" class="btn btn-default" id="teacherPrevStepBtn" disabled>مرحله قبلی</button>
                    <button type="button" class="section-btn btn btn-default" id="teacherNextStepBtn">مرحله بعدی</button>
                    <button class="section-btn btn btn-default" type="submit" id="teacherSubmitBtn" style="display:none;">ذخیره نهایی</button>
                <?php else: ?>
                    <button class="section-btn btn btn-default" type="submit" id="teacherSubmitBtn">ذخیره نهایی</button>
                <?php endif; ?>
                <a class="btn btn-default" href="<?= e(url('/teachers')) ?>">انصراف</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById('teacherWizardForm');
    if (!form) return;

    const steps = Array.from(document.querySelectorAll('.wizard-step'));
    const panels = Array.from(document.querySelectorAll('.wizard-panel'));
    const prevBtn = document.getElementById('teacherPrevStepBtn');
    const nextBtn = document.getElementById('teacherNextStepBtn');
    const submitBtn = document.getElementById('teacherSubmitBtn');
    const isTabMode = <?= $isEditMode ? 'true' : 'false' ?>;
    const accountEmail = document.getElementById('teacher_account_email');
    const accountPassword = document.getElementById('teacher_account_password');
    const accountPasswordConfirm = document.getElementById('teacher_account_password_confirmation');
    const birthDateHidden = document.getElementById('teacher_birth_date');
    const birthDateDay = document.getElementById('teacher_birth_date_day');
    const birthDateMonth = document.getElementById('teacher_birth_date_month');
    const birthDateYear = document.getElementById('teacher_birth_date_year');
    const mustSetPassword = <?= $mustSetPassword ? 'true' : 'false' ?>;

    let currentStep = 0;

    function showStep(index) {
        currentStep = Math.max(0, Math.min(index, panels.length - 1));

        panels.forEach((panel, i) => panel.classList.toggle('is-active', i === currentStep));
        steps.forEach((step, i) => {
            step.classList.toggle('is-active', i === currentStep);
            step.classList.toggle('is-done', !isTabMode && i < currentStep);
        });

        if (isTabMode) {
            if (prevBtn) prevBtn.style.display = 'none';
            if (nextBtn) nextBtn.style.display = 'none';
            if (submitBtn) submitBtn.style.display = '';
            return;
        }

        if (prevBtn) prevBtn.disabled = currentStep === 0;
        const isLast = currentStep === panels.length - 1;
        if (nextBtn) nextBtn.style.display = isLast ? 'none' : '';
        if (submitBtn) submitBtn.style.display = isLast ? '' : 'none';
    }

    function validateNative(panel) {
        const fields = Array.from(panel.querySelectorAll('input, select, textarea'));
        for (const field of fields) {
            if (field.type === 'hidden' || field.disabled || field.type === 'checkbox') continue;
            if (!field.checkValidity()) {
                field.reportValidity();
                return false;
            }
        }
        return true;
    }

    function parseGregorian(value) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(value || '')) return null;
        const parts = value.split('-').map((n) => parseInt(n, 10));
        if (parts.length !== 3) return null;
        return parts;
    }

    function pad2(value) {
        return String(value).padStart(2, '0');
    }

    function gregorianToJalali(gy, gm, gd) {
        const g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        const gy2 = (gm > 2) ? (gy + 1) : gy;
        let days = 355666 + (365 * gy) + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100) + Math.floor((gy2 + 399) / 400) + gd + g_d_m[gm - 1];
        let jy = -1595 + 33 * Math.floor(days / 12053);
        days %= 12053;
        jy += 4 * Math.floor(days / 1461);
        days %= 1461;
        if (days > 365) {
            jy += Math.floor((days - 1) / 365);
            days = (days - 1) % 365;
        }
        const jm = (days < 186) ? 1 + Math.floor(days / 31) : 7 + Math.floor((days - 186) / 30);
        const jd = 1 + (days < 186 ? (days % 31) : ((days - 186) % 30));
        return [jy, jm, jd];
    }

    function jalaliToGregorian(jy, jm, jd) {
        jy = parseInt(jy, 10);
        jm = parseInt(jm, 10);
        jd = parseInt(jd, 10);
        jy += 1595;
        let days = -355668 + (365 * jy) + Math.floor(jy / 33) * 8 + Math.floor(((jy % 33) + 3) / 4) + jd;
        if (jm < 7) {
            days += (jm - 1) * 31;
        } else {
            days += ((jm - 7) * 30) + 186;
        }
        let gy = 400 * Math.floor(days / 146097);
        days %= 146097;
        if (days > 36524) {
            gy += 100 * Math.floor(--days / 36524);
            days %= 36524;
            if (days >= 365) days++;
        }
        gy += 4 * Math.floor(days / 1461);
        days %= 1461;
        if (days > 365) {
            gy += Math.floor((days - 1) / 365);
            days = (days - 1) % 365;
        }
        let gd = days + 1;
        const sal_a = [0, 31, ((gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        let gm;
        for (gm = 1; gm <= 12 && gd > sal_a[gm]; gm++) {
            gd -= sal_a[gm];
        }
        return [gy, gm, gd];
    }

    function monthDays(jalaliMonth) {
        const m = parseInt(jalaliMonth || '0', 10);
        if (m >= 1 && m <= 6) return 31;
        if (m >= 7 && m <= 11) return 30;
        if (m === 12) return 30;
        return 31;
    }

    function syncBirthDayOptions() {
        if (!birthDateMonth || !birthDateDay) return;
        const max = monthDays(birthDateMonth.value);
        const current = parseInt(birthDateDay.value || '0', 10);
        Array.from(birthDateDay.options).forEach((option) => {
            if (!option.value) return;
            option.disabled = parseInt(option.value, 10) > max;
        });
        if (current > max) {
            birthDateDay.value = '';
        }
    }

    function syncBirthHidden() {
        syncBirthDayOptions();
        if (!birthDateHidden || !birthDateYear || !birthDateMonth || !birthDateDay) return;
        const y = parseInt(birthDateYear.value || '0', 10);
        const m = parseInt(birthDateMonth.value || '0', 10);
        const d = parseInt(birthDateDay.value || '0', 10);
        if (!y || !m || !d) {
            birthDateHidden.value = '';
            return;
        }
        const [gy, gm, gd] = jalaliToGregorian(y, m, d);
        birthDateHidden.value = `${gy}-${pad2(gm)}-${pad2(gd)}`;
    }

    function setBirthFromExisting() {
        const parsed = parseGregorian(birthDateHidden ? birthDateHidden.value : '');
        if (!parsed || !birthDateYear || !birthDateMonth || !birthDateDay) return;
        const [gy, gm, gd] = parsed;
        const [jy, jm, jd] = gregorianToJalali(gy, gm, gd);
        birthDateYear.value = String(jy);
        birthDateMonth.value = String(jm);
        syncBirthDayOptions();
        birthDateDay.value = String(jd);
    }

    function customValidateTeaching() {
        if (!accountEmail || !accountEmail.value.trim() || !accountEmail.checkValidity()) {
            alert('ایمیل حساب استاد معتبر نیست.');
            if (accountEmail) accountEmail.focus();
            return false;
        }

        if (mustSetPassword && (!accountPassword || accountPassword.value.trim() === '')) {
            alert('برای ایجاد حساب استاد، رمز عبور الزامی است.');
            if (accountPassword) accountPassword.focus();
            return false;
        }

        if (accountPassword && accountPassword.value !== '') {
            if (accountPassword.value.length < 8) {
                alert('رمز عبور حساب استاد باید حداقل ۸ کاراکتر باشد.');
                accountPassword.focus();
                return false;
            }
            if (!accountPasswordConfirm || accountPassword.value !== accountPasswordConfirm.value) {
                alert('تکرار رمز عبور حساب استاد یکسان نیست.');
                if (accountPasswordConfirm) accountPasswordConfirm.focus();
                return false;
            }
        } else if (accountPasswordConfirm && accountPasswordConfirm.value !== '') {
            alert('برای تکرار رمز، ابتدا رمز عبور جدید استاد را وارد کنید.');
            if (accountPassword) accountPassword.focus();
            return false;
        }

        return true;
    }

    function customValidate(stepIndex) {
        if (stepIndex === 0) {
            if (!birthDateHidden || !/^\d{4}-\d{2}-\d{2}$/.test(birthDateHidden.value || '')) {
                alert('تاریخ تولد هجری شمسی را درست انتخاب کنید.');
                showStep(0);
                return false;
            }
        }

        if (stepIndex === 3) {
            return customValidateTeaching();
        }

        return true;
    }

    function validateCurrentStep() {
        const panel = panels[currentStep];
        if (!panel) return false;
        if (!validateNative(panel)) return false;
        return customValidate(currentStep);
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            if (!validateCurrentStep()) return;
            showStep(currentStep + 1);
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', () => showStep(currentStep - 1));
    }

    steps.forEach((step, idx) => {
        step.addEventListener('click', () => {
            if (isTabMode || idx <= currentStep) {
                showStep(idx);
            }
        });
    });

    form.addEventListener('submit', (e) => {
        syncBirthHidden();
        for (let i = 0; i < panels.length; i += 1) {
            showStep(i);
            if (!validateCurrentStep()) {
                e.preventDefault();
                return;
            }
        }
    });

    if (birthDateDay) birthDateDay.addEventListener('change', syncBirthHidden);
    if (birthDateMonth) birthDateMonth.addEventListener('change', syncBirthHidden);
    if (birthDateYear) birthDateYear.addEventListener('change', syncBirthHidden);

    setBirthFromExisting();
    syncBirthHidden();
    showStep(0);
})();
</script>
