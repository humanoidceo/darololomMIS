<?php
$linkedUser = $linkedUser ?? null;
$studentData = is_array($student ?? null) ? $student : [];
$oldOr = static fn (string $key, mixed $fallback = ''): mixed => old($key, $studentData[$key] ?? $fallback);

$selectedSemestersInput = old('semester_ids', $selectedSemesters ?? []);
if (!is_array($selectedSemestersInput)) {
    $selectedSemestersInput = [];
}
$selectedSemesters = array_map('intval', $selectedSemestersInput);

$selectedPeriodsInput = old('period_ids', $selectedPeriods ?? []);
if (!is_array($selectedPeriodsInput)) {
    $selectedPeriodsInput = [];
}
$selectedPeriods = array_map('intval', $selectedPeriodsInput);

$selectedSemesterId = (int) old('semester_id', $selectedSemesters[0] ?? 0);
$selectedPeriodId = (int) old('period_id', $selectedPeriods[0] ?? 0);
$selectedClassId = (int) $oldOr('school_class_id', 0);

$selectedClassLabel = '';
foreach ($classes as $class) {
    if ((int) $class['id'] === $selectedClassId) {
        $selectedClassLabel = (string) $class['name'];
        break;
    }
}
$selectedEnrollmentYear = (int) $oldOr('enrollment_year', 0);
if ($selectedEnrollmentYear < 1350 || $selectedEnrollmentYear > 1500) {
    $selectedEnrollmentYear = 0;
}
$selectedEnrollmentYearLabel = $selectedEnrollmentYear > 0
    ? to_persian_number((string) $selectedEnrollmentYear)
    : 'انتخاب سال شمولیت';

$classCatalog = [];
foreach ($classes as $class) {
    $classCatalog[] = [
        'id' => (int) $class['id'],
        'name' => (string) $class['name'],
        'level_id' => (int) ($class['level_id'] ?? 0),
        'level_name' => (string) ($class['level_name'] ?? ''),
    ];
}

$hasExistingCertificate = !empty($studentData['certificate_file']);
$accountEmail = (string) old('account_email', (string) ($linkedUser['email'] ?? ''));
$mustSetPassword = empty($linkedUser['id']);
$birthDateValue = (string) $oldOr('birth_date');
$jalaliMonths = ['حمل', 'ثور', 'جوزا', 'سرطان', 'اسد', 'سنبله', 'میزان', 'عقرب', 'قوس', 'جدی', 'دلو', 'حوت'];
$isEditMode = !empty($studentData['id']);
?>

<div class="section-title">
    <h2><?= e($student ? 'ویرایش دانش‌آموز' : 'ثبت دانش‌آموز جدید') ?></h2>
</div>

<div class="news-thumb student-wizard-wrap">
    <div class="news-info">
        <div class="wizard-header">
            <button type="button" class="wizard-step is-active" data-step-target="1">۱) مشخصات فردی</button>
            <button type="button" class="wizard-step" data-step-target="2">۲) مشخصات آموزشی</button>
            <button type="button" class="wizard-step" data-step-target="3">۳) سکونت فعلی و زمان</button>
            <button type="button" class="wizard-step" data-step-target="4">۴) فایل‌ها، حساب و ثبت</button>
        </div>

        <form id="studentWizardForm" method="post" action="<?= e($formAction) ?>" enctype="multipart/form-data" class="module-form student-form-grid" novalidate>
            <?= csrf_field() ?>

            <section class="wizard-panel is-active" data-step-panel="1">
                <div class="form-group">
                    <label>نام دانش‌آموز</label>
                    <input class="form-control" type="text" name="name" value="<?= e((string) $oldOr('name')) ?>" placeholder="مثال: عبدالرحمن نادری" minlength="3" maxlength="255" required>
                    <small class="field-help">نام کامل را حداقل با ۳ حرف وارد کنید.</small>
                </div>

                <div class="form-group">
                    <label>نام پدر</label>
                    <input class="form-control" type="text" name="father_name" value="<?= e((string) $oldOr('father_name')) ?>" placeholder="مثال: محمدکریم" minlength="3" maxlength="255" required>
                    <small class="field-help">نام پدر الزامی است.</small>
                </div>

                <div class="form-group">
                    <label>نام پدرکلان</label>
                    <input class="form-control" type="text" name="grandfather_name" value="<?= e((string) $oldOr('grandfather_name')) ?>" placeholder="مثال: غلام‌نبی" maxlength="255">
                    <small class="field-help">اختیاری؛ در صورت نیاز وارد شود.</small>
                </div>

                <div class="form-group">
                    <label>تاریخ تولد (هجری شمسی)</label>
                    <div class="jalali-date-grid">
                        <select class="form-control" id="birth_date_day" required>
                            <option value="">روز</option>
                            <?php for ($d = 1; $d <= 31; $d++): ?>
                                <option value="<?= e((string) $d) ?>"><?= e((string) $d) ?></option>
                            <?php endfor; ?>
                        </select>
                        <select class="form-control" id="birth_date_month" required>
                            <option value="">ماه</option>
                            <?php foreach ($jalaliMonths as $idx => $monthName): ?>
                                <option value="<?= e((string) ($idx + 1)) ?>"><?= e($monthName) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select class="form-control" id="birth_date_year" required>
                            <option value="">سال</option>
                            <?php for ($y = 1460; $y >= 1330; $y--): ?>
                                <option value="<?= e((string) $y) ?>"><?= e((string) $y) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <input type="hidden" name="birth_date" id="birth_date" value="<?= e($birthDateValue) ?>">
                    <small class="field-help">تاریخ را به جنتری هجری شمسی (ماه‌های افغانی) انتخاب کنید.</small>
                </div>

                <div class="form-group">
                    <label>جنسیت</label>
                    <select class="form-control" name="gender" required>
                        <option value="male" <?= (string) $oldOr('gender', 'male') === 'male' ? 'selected' : '' ?>>مذکر</option>
                        <option value="female" <?= (string) $oldOr('gender') === 'female' ? 'selected' : '' ?>>مونث</option>
                    </select>
                    <small class="field-help">یکی از گزینه‌ها را انتخاب کنید.</small>
                </div>

                <div class="form-group">
                    <label>نمبر تذکره</label>
                    <input class="form-control" type="text" name="id_number" value="<?= e((string) $oldOr('id_number')) ?>" placeholder="مثال: 12345-67890" pattern="[0-9A-Za-z\-\/\s]+" maxlength="100" required>
                    <small class="field-help">اعداد/حروف انگلیسی، خط تیره یا اسلش مجاز است.</small>
                </div>

                <div class="form-group">
                    <label>شماره تماس</label>
                    <input class="form-control" type="text" name="mobile_number" value="<?= e((string) $oldOr('mobile_number')) ?>" placeholder="مثال: 0700123456" pattern="[0-9+\-\s]{7,20}" maxlength="20" required>
                    <small class="field-help">حداقل ۷ رقم، فقط عدد و + و - مجاز است.</small>
                </div>

                <div class="form-group">
                    <label>ولایت سکونت اصلی</label>
                    <input class="form-control" type="text" name="permanent_address" value="<?= e((string) $oldOr('permanent_address')) ?>" placeholder="مثال: بغلان" minlength="2" required>
                    <small class="field-help">این بخش مربوط سکونت اصلی است.</small>
                </div>

                <div class="form-group">
                    <label>ولسوالی سکونت اصلی</label>
                    <input class="form-control" type="text" name="district" value="<?= e((string) $oldOr('district')) ?>" placeholder="مثال: خنجان" maxlength="150" required>
                    <small class="field-help">نام ولسوالی سکونت اصلی را وارد کنید.</small>
                </div>

                <div class="form-group">
                    <label>قریه سکونت اصلی</label>
                    <input class="form-control" type="text" name="village" value="<?= e((string) $oldOr('village')) ?>" placeholder="مثال: نوآباد" maxlength="150" required>
                    <small class="field-help">نام قریه سکونت اصلی را وارد کنید.</small>
                </div>
            </section>

            <section class="wizard-panel" data-step-panel="2">
                <div class="form-group">
                    <label>سطح آموزشی</label>
                    <select class="form-control" name="level_id" id="level_id" required>
                        <option value="">انتخاب کنید</option>
                        <?php foreach ($levels as $level): ?>
                            <option value="<?= e((string) $level['id']) ?>" data-level-code="<?= e((string) ($level['code'] ?? '')) ?>" <?= (string) $oldOr('level_id') === (string) $level['id'] ? 'selected' : '' ?>>
                                <?= e((string) $level['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="field-help">اول سطح را انتخاب کنید تا گزینه‌های مربوطه فعال شود.</small>
                </div>

                <div class="form-group">
                    <label>صنف (جستجو شونده)</label>
                    <input class="form-control" type="text" id="school_class_search" list="school_classes_list" value="<?= e($selectedClassLabel) ?>" placeholder="نام صنف را جستجو کنید...">
                    <datalist id="school_classes_list"></datalist>
                    <input type="hidden" name="school_class_id" id="school_class_id" value="<?= e((string) $selectedClassId) ?>">
                    <small class="field-help">فقط همان صنفی که جستجو و انتخاب می‌کنید ثبت می‌شود.</small>
                </div>

                <div class="form-group">
                    <label>سال شمولیت</label>
                    <input type="hidden" name="enrollment_year" id="enrollment_year" value="<?= e($selectedEnrollmentYear > 0 ? (string) $selectedEnrollmentYear : '') ?>">
                    <div class="student-year-combo" id="studentEnrollmentYearCombo">
                        <button type="button" class="form-control student-year-trigger" id="studentEnrollmentYearTrigger" aria-haspopup="listbox" aria-expanded="false">
                            <span id="studentEnrollmentYearTriggerText"><?= e($selectedEnrollmentYearLabel) ?></span>
                            <span class="student-year-arrow" aria-hidden="true">▾</span>
                        </button>
                        <div class="student-year-dropdown" id="studentEnrollmentYearDropdown" hidden>
                            <input type="text" id="studentEnrollmentYearSearch" class="form-control" placeholder="جستجوی سال..." autocomplete="off">
                            <div class="student-year-list" id="studentEnrollmentYearList" role="listbox"></div>
                            <div class="student-year-status" id="studentEnrollmentYearStatus"></div>
                        </div>
                    </div>
                    <small class="field-help">از سال ۱۳۵۰ تا ۱۵۰۰ قابل انتخاب است.</small>
                </div>

                <div class="form-group" id="exam_number_block">
                    <label>نمبر امتحان کانکور</label>
                    <input class="form-control" type="text" name="exam_number" id="exam_number" value="<?= e((string) $oldOr('exam_number')) ?>" placeholder="مثال: KANKOR-2026-145" pattern="[0-9A-Za-z\-\/\s]*" maxlength="100">
                    <small class="field-help">این فیلد فقط برای سطح عالی نمایش داده می‌شود.</small>
                </div>

                <div class="form-group" id="semester_block">
                    <label>صنف (فقط برای سطح عالی)</label>
                    <select class="form-control" name="semester_id" id="semester_id">
                        <option value="">یکی را انتخاب کنید</option>
                        <?php foreach ($semesters as $item): ?>
                            <option value="<?= e((string) $item['id']) ?>" <?= $selectedSemesterId === (int) $item['id'] ? 'selected' : '' ?>>
                                <?= e('صنف ' . (string) $item['number']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="field-help">فقط یکی از صنف‌های ۱۳ یا ۱۴ قابل انتخاب است.</small>
                </div>

                <div class="form-group" id="period_block">
                    <label>دوره (ابتداییه/متوسطه)</label>
                    <select class="form-control" name="period_id" id="period_id">
                        <option value="">یکی را انتخاب کنید</option>
                        <?php foreach ($periods as $item): ?>
                            <option value="<?= e((string) $item['id']) ?>" <?= $selectedPeriodId === (int) $item['id'] ? 'selected' : '' ?>>
                                <?= e('دوره ' . (string) $item['number']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="field-help">در هر زمان فقط یک دوره قابل انتخاب است.</small>
                </div>

                <div class="form-group" id="certificate_block">
                    <label>آپلود شهادت‌نامه (PDF)</label>
                    <input class="form-control" type="file" name="certificate_file" id="certificate_file" accept=".pdf,application/pdf">
                    <small class="field-help">این فیلد فقط برای سطح عالی نمایش داده می‌شود.</small>
                    <?php if (!empty($studentData['certificate_file'])): ?><a href="<?= e(url((string) $studentData['certificate_file'])) ?>" target="_blank">نمایش فایل فعلی</a><?php endif; ?>
                </div>
            </section>

            <section class="wizard-panel" data-step-panel="3">
                <div class="form-group">
                    <label>ولایت سکونت فعلی</label>
                    <input class="form-control" type="text" name="current_address" value="<?= e((string) $oldOr('current_address')) ?>" placeholder="مثال: کابل" minlength="2" required>
                    <small class="field-help">ولایت محل سکونت فعلی را وارد کنید.</small>
                </div>

                <div class="form-group">
                    <label>ناحیه سکونت فعلی</label>
                    <input class="form-control" type="text" name="area" value="<?= e((string) $oldOr('area')) ?>" placeholder="مثال: ناحیه ۸" maxlength="150" required>
                    <small class="field-help">ناحیه سکونت فعلی را وارد کنید.</small>
                </div>

                <div class="form-group">
                    <label>کوچه سکونت فعلی</label>
                    <input class="form-control" type="text" name="current_street" value="<?= e((string) $oldOr('current_street')) ?>" placeholder="مثال: کوچه گلستان" maxlength="150" required>
                    <small class="field-help">نام کوچه سکونت فعلی را وارد کنید.</small>
                </div>

                <div class="form-group">
                    <label>تایم آغاز</label>
                    <input class="form-control" type="time" name="time_start" id="time_start" value="<?= e((string) $oldOr('time_start')) ?>">
                    <small class="field-help">مثال: 08:00</small>
                </div>

                <div class="form-group">
                    <label>تایم ختم</label>
                    <input class="form-control" type="time" name="time_end" id="time_end" value="<?= e((string) $oldOr('time_end')) ?>">
                    <small class="field-help">باید بعد از تایم آغاز باشد.</small>
                </div>
            </section>

            <section class="wizard-panel" data-step-panel="4">
                <div class="form-group">
                    <label>عکس</label>
                    <input class="form-control" type="file" name="image" accept=".jpg,.jpeg,.png,.webp,image/*">
                    <small class="field-help">فرمت مجاز: JPG/PNG/WEBP | حداکثر 2MB</small>
                    <?php if (!empty($student['image_path'])): ?><a href="<?= e(url((string) $student['image_path'])) ?>" target="_blank">نمایش فایل فعلی</a><?php endif; ?>
                </div>

                <div class="form-group">
                    <label>ایمیل حساب شاگرد</label>
                    <input class="form-control" type="email" name="account_email" id="account_email" value="<?= e($accountEmail) ?>" placeholder="مثال: student@example.com" required>
                    <small class="field-help">شاگرد با همین ایمیل وارد حساب خود می‌شود.</small>
                </div>

                <div class="form-group">
                    <label>رمز عبور حساب شاگرد</label>
                    <input class="form-control" type="password" name="account_password" id="account_password" placeholder="<?= $mustSetPassword ? 'حداقل ۸ کاراکتر (الزامی)' : 'اگر تغییر نمی‌دهید خالی بگذارید' ?>" minlength="8" <?= $mustSetPassword ? 'required' : '' ?>>
                    <small class="field-help"><?= $mustSetPassword ? 'برای ایجاد حساب شاگرد، رمز عبور الزامی است.' : 'در ویرایش، فقط در صورت نیاز رمز جدید وارد کنید.' ?></small>
                </div>

                <div class="form-group">
                    <label>تکرار رمز عبور</label>
                    <input class="form-control" type="password" name="account_password_confirmation" id="account_password_confirmation" placeholder="تکرار رمز عبور" minlength="8" <?= $mustSetPassword ? 'required' : '' ?>>
                    <small class="field-help">باید دقیقاً با رمز عبور یکسان باشد.</small>
                </div>

                <div class="form-group full">
                    <div class="summary-note">
                        قبل از ثبت نهایی، تمام اطلاعات را مرور کنید. برای سطح عالی، شهادت‌نامه و نمبر کانکور الزامی است.
                    </div>
                </div>
            </section>

            <div class="full wizard-actions">
                <?php if (!$isEditMode): ?>
                    <button type="button" class="btn btn-default" id="prevStepBtn" disabled>مرحله قبلی</button>
                    <button type="button" class="section-btn btn btn-default" id="nextStepBtn">مرحله بعدی</button>
                    <button class="section-btn btn btn-default" type="submit" id="submitBtn" style="display:none;">ذخیره نهایی</button>
                <?php else: ?>
                    <button class="section-btn btn btn-default" type="submit" id="submitBtn">ذخیره نهایی</button>
                <?php endif; ?>
                <a class="btn btn-default" href="<?= e(url('/students')) ?>">انصراف</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById('studentWizardForm');
    if (!form) return;

    const steps = Array.from(document.querySelectorAll('.wizard-step'));
    const panels = Array.from(document.querySelectorAll('.wizard-panel'));
    const prevBtn = document.getElementById('prevStepBtn');
    const nextBtn = document.getElementById('nextStepBtn');
    const submitBtn = document.getElementById('submitBtn');
    const isTabMode = <?= $isEditMode ? 'true' : 'false' ?>;

    const levelSelect = document.getElementById('level_id');
    const schoolClassSearch = document.getElementById('school_class_search');
    const schoolClassIdInput = document.getElementById('school_class_id');
    const schoolClassList = document.getElementById('school_classes_list');
    const enrollmentYearInput = document.getElementById('enrollment_year');
    const enrollmentYearCombo = document.getElementById('studentEnrollmentYearCombo');
    const enrollmentYearTrigger = document.getElementById('studentEnrollmentYearTrigger');
    const enrollmentYearTriggerText = document.getElementById('studentEnrollmentYearTriggerText');
    const enrollmentYearDropdown = document.getElementById('studentEnrollmentYearDropdown');
    const enrollmentYearSearch = document.getElementById('studentEnrollmentYearSearch');
    const enrollmentYearList = document.getElementById('studentEnrollmentYearList');
    const enrollmentYearStatus = document.getElementById('studentEnrollmentYearStatus');

    const examBlock = document.getElementById('exam_number_block');
    const examInput = document.getElementById('exam_number');
    const semesterBlock = document.getElementById('semester_block');
    const semesterSelect = document.getElementById('semester_id');
    const periodBlock = document.getElementById('period_block');
    const periodSelect = document.getElementById('period_id');
    const certificateBlock = document.getElementById('certificate_block');
    const certificateInput = document.getElementById('certificate_file');

    const timeStart = document.getElementById('time_start');
    const timeEnd = document.getElementById('time_end');
    const accountEmail = document.getElementById('account_email');
    const accountPassword = document.getElementById('account_password');
    const accountPasswordConfirm = document.getElementById('account_password_confirmation');

    const birthDateHidden = document.getElementById('birth_date');
    const birthDateDay = document.getElementById('birth_date_day');
    const birthDateMonth = document.getElementById('birth_date_month');
    const birthDateYear = document.getElementById('birth_date_year');

    const hasExistingCertificate = <?= $hasExistingCertificate ? 'true' : 'false' ?>;
    const mustSetPassword = <?= $mustSetPassword ? 'true' : 'false' ?>;
    const classesData = <?= json_encode($classCatalog, JSON_UNESCAPED_UNICODE) ?>;

    let currentStep = 0;
    const allEnrollmentYears = [];
    for (let year = 1500; year >= 1350; year -= 1) {
        allEnrollmentYears.push(year);
    }
    const enrollmentYearState = {
        opened: false,
        loading: false,
        page: 1,
        perPage: 5,
        hasMore: true,
        query: '',
        debounceTimer: null
    };

    function toPersianDigits(value) {
        const en = '0123456789';
        const fa = '۰۱۲۳۴۵۶۷۸۹';
        return String(value || '').replace(/\d/g, (digit) => fa[en.indexOf(digit)] || digit);
    }

    function normalizeDigits(value) {
        const map = {
            '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4',
            '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
            '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
            '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9'
        };
        return String(value || '').replace(/[۰-۹٠-٩]/g, (char) => map[char] || char);
    }

    function selectedEnrollmentYear() {
        return parseInt((enrollmentYearInput && enrollmentYearInput.value) || '0', 10) || 0;
    }

    function setEnrollmentYearStatus(message) {
        if (enrollmentYearStatus) {
            enrollmentYearStatus.textContent = message;
        }
    }

    function updateEnrollmentYearTriggerText() {
        if (!enrollmentYearTriggerText) return;
        const year = selectedEnrollmentYear();
        enrollmentYearTriggerText.textContent = year > 0 ? toPersianDigits(year) : 'انتخاب سال شمولیت';
    }

    function filteredEnrollmentYears() {
        if (!enrollmentYearState.query) {
            return allEnrollmentYears.slice();
        }
        return allEnrollmentYears.filter((year) => String(year).includes(enrollmentYearState.query));
    }

    function buildEnrollmentYearOption(year) {
        const option = document.createElement('button');
        option.type = 'button';
        option.className = 'student-year-option';
        option.setAttribute('role', 'option');
        option.setAttribute('data-year', String(year));
        option.textContent = toPersianDigits(year);

        if (year === selectedEnrollmentYear()) {
            option.classList.add('is-selected');
        }

        option.addEventListener('click', () => {
            if (enrollmentYearInput) {
                enrollmentYearInput.value = String(year);
            }
            updateEnrollmentYearTriggerText();
            setEnrollmentYearOpened(false);
        });

        return option;
    }

    function renderEnrollmentYearPage(reset) {
        if (!enrollmentYearList) return;
        if (reset) {
            enrollmentYearList.innerHTML = '';
            enrollmentYearState.page = 1;
        }

        const filtered = filteredEnrollmentYears();
        const start = (enrollmentYearState.page - 1) * enrollmentYearState.perPage;
        const end = start + enrollmentYearState.perPage;
        const chunk = filtered.slice(start, end);

        if (reset && chunk.length === 0) {
            setEnrollmentYearStatus('سال مورد نظر پیدا نشد.');
            enrollmentYearState.hasMore = false;
            return;
        }

        const fragment = document.createDocumentFragment();
        chunk.forEach((year) => fragment.appendChild(buildEnrollmentYearOption(year)));
        enrollmentYearList.appendChild(fragment);

        enrollmentYearState.hasMore = end < filtered.length;
        if (enrollmentYearState.hasMore) {
            setEnrollmentYearStatus('برای مشاهده موارد بیشتر اسکرول کنید.');
        } else {
            setEnrollmentYearStatus('پایان لیست.');
        }
    }

    function loadMoreEnrollmentYears() {
        if (enrollmentYearState.loading || !enrollmentYearState.hasMore) return;
        enrollmentYearState.loading = true;
        enrollmentYearState.page += 1;
        renderEnrollmentYearPage(false);
        enrollmentYearState.loading = false;
    }

    function setEnrollmentYearOpened(opened) {
        enrollmentYearState.opened = opened;
        if (enrollmentYearDropdown) {
            enrollmentYearDropdown.hidden = !opened;
        }
        if (enrollmentYearTrigger) {
            enrollmentYearTrigger.setAttribute('aria-expanded', opened ? 'true' : 'false');
        }
        if (opened) {
            renderEnrollmentYearPage(true);
            if (enrollmentYearSearch) enrollmentYearSearch.focus();
        }
    }

    function selectedLevelCode() {
        const opt = levelSelect ? levelSelect.options[levelSelect.selectedIndex] : null;
        return opt ? (opt.dataset.levelCode || '') : '';
    }

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
            if (field.type === 'hidden' || field.disabled) continue;
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

    function filteredClasses() {
        const levelId = parseInt(levelSelect ? (levelSelect.value || '0') : '0', 10);
        if (!levelId) return classesData.slice();
        return classesData.filter((item) => parseInt(item.level_id || 0, 10) === levelId);
    }

    function renderClassList() {
        if (!schoolClassList) return;
        const seen = new Set();
        const options = filteredClasses()
            .filter((item) => {
                const name = (item.name || '').trim();
                if (!name || seen.has(name)) return false;
                seen.add(name);
                return true;
            })
            .map((item) => `<option value="${String(item.name || '').replace(/"/g, '&quot;')}"></option>`)
            .join('');
        schoolClassList.innerHTML = options;
    }

    function classById(id) {
        const numericId = parseInt(String(id || '0'), 10);
        if (!numericId) return null;
        return classesData.find((item) => parseInt(item.id || 0, 10) === numericId) || null;
    }

    function syncClassFromText() {
        if (!schoolClassSearch || !schoolClassIdInput) return;
        const text = schoolClassSearch.value.trim();
        if (text === '') {
            schoolClassIdInput.value = '';
            return;
        }
        const matches = filteredClasses().find((item) => String(item.name || '').trim() === text);
        schoolClassIdInput.value = matches ? String(matches.id) : '';
    }

    function syncClassFromHiddenId() {
        if (!schoolClassSearch || !schoolClassIdInput) return;
        const match = classById(schoolClassIdInput.value);
        if (match) {
            schoolClassSearch.value = String(match.name || '');
            return;
        }
        if (!schoolClassSearch.value.trim()) {
            schoolClassSearch.value = '';
        }
    }

    function updateConditionalBlocks() {
        const levelCode = selectedLevelCode();
        const hasLevel = !!(levelSelect && levelSelect.value);
        const isAali = levelCode === 'aali';

        if (examBlock) examBlock.style.display = isAali ? '' : 'none';
        if (semesterBlock) semesterBlock.style.display = (hasLevel && isAali) ? '' : 'none';
        if (periodBlock) periodBlock.style.display = (hasLevel && !isAali) ? '' : 'none';
        if (certificateBlock) certificateBlock.style.display = isAali ? '' : 'none';

        if (examInput) {
            examInput.required = isAali;
            examInput.disabled = !isAali;
            if (!isAali) examInput.value = '';
        }
        if (semesterSelect) {
            semesterSelect.required = isAali;
            semesterSelect.disabled = !isAali;
            if (!isAali) semesterSelect.value = '';
        }
        if (periodSelect) {
            periodSelect.required = hasLevel && !isAali;
            periodSelect.disabled = !hasLevel || isAali;
            if (isAali) periodSelect.value = '';
        }
        if (certificateInput) {
            certificateInput.required = isAali && !hasExistingCertificate;
            certificateInput.disabled = !isAali;
        }

        const selectedClass = classById(schoolClassIdInput ? schoolClassIdInput.value : '');
        if (selectedClass && levelSelect && levelSelect.value) {
            if (parseInt(selectedClass.level_id || 0, 10) !== parseInt(levelSelect.value || '0', 10)) {
                if (schoolClassIdInput) schoolClassIdInput.value = '';
                if (schoolClassSearch) schoolClassSearch.value = '';
            }
        }

        renderClassList();
    }

    function customValidate(stepIndex) {
        if (stepIndex === 0) {
            if (!birthDateHidden || !/^\d{4}-\d{2}-\d{2}$/.test(birthDateHidden.value || '')) {
                alert('تاریخ تولد هجری شمسی را درست انتخاب کنید.');
                showStep(0);
                return false;
            }
            return true;
        }

        if (stepIndex === 1) {
            const levelCode = selectedLevelCode();
            if (levelSelect && !levelSelect.value) {
                alert('لطفاً سطح آموزشی را انتخاب کنید.');
                showStep(1);
                return false;
            }
            const selectedYear = selectedEnrollmentYear();
            if (selectedYear < 1350 || selectedYear > 1500) {
                alert('سال شمولیت را بین ۱۳۵۰ تا ۱۵۰۰ انتخاب کنید.');
                showStep(1);
                if (enrollmentYearTrigger) enrollmentYearTrigger.focus();
                return false;
            }

            if (schoolClassSearch && schoolClassSearch.value.trim() !== '' && schoolClassIdInput && !schoolClassIdInput.value) {
                alert('صنف واردشده معتبر نیست. لطفاً از لیست جستجو انتخاب کنید.');
                showStep(1);
                schoolClassSearch.focus();
                return false;
            }

            if (schoolClassIdInput && schoolClassIdInput.value && levelSelect && levelSelect.value) {
                const selectedClass = classById(schoolClassIdInput.value);
                if (selectedClass && parseInt(selectedClass.level_id || 0, 10) !== parseInt(levelSelect.value || '0', 10)) {
                    alert('سطح صنف با سطح آموزشی انتخاب‌شده مطابقت ندارد.');
                    showStep(1);
                    return false;
                }
            }

            if (levelCode === 'aali') {
                if (!examInput || examInput.value.trim() === '') {
                    alert('برای سطح عالی، نمبر امتحان کانکور الزامی است.');
                    showStep(1);
                    if (examInput) examInput.focus();
                    return false;
                }
                if (!semesterSelect || !semesterSelect.value) {
                    alert('برای سطح عالی یکی از صنف‌های ۱۳ یا ۱۴ را انتخاب کنید.');
                    showStep(1);
                    if (semesterSelect) semesterSelect.focus();
                    return false;
                }
                if (periodSelect && periodSelect.value) {
                    alert('برای سطح عالی نباید دوره انتخاب شود.');
                    showStep(1);
                    return false;
                }
                if (certificateInput && certificateInput.files.length === 0 && !hasExistingCertificate) {
                    alert('برای سطح عالی، آپلود شهادت‌نامه الزامی است.');
                    showStep(1);
                    return false;
                }
            } else {
                if (!periodSelect || !periodSelect.value) {
                    alert('برای سطح ابتداییه/متوسطه دقیقاً یک دوره انتخاب کنید.');
                    showStep(1);
                    if (periodSelect) periodSelect.focus();
                    return false;
                }
                if (semesterSelect && semesterSelect.value) {
                    alert('برای سطح ابتداییه/متوسطه نباید صنف ۱۳/۱۴ انتخاب شود.');
                    showStep(1);
                    return false;
                }
            }

            return true;
        }

        if (stepIndex === 2) {
            if (timeStart && timeEnd && timeStart.value && timeEnd.value && timeStart.value >= timeEnd.value) {
                alert('تایم ختم باید بعد از تایم آغاز باشد.');
                showStep(2);
                return false;
            }
            return true;
        }

        if (stepIndex === 3) {
            if (!accountEmail || !accountEmail.value.trim() || !accountEmail.checkValidity()) {
                alert('ایمیل حساب شاگرد معتبر نیست.');
                showStep(3);
                if (accountEmail) accountEmail.focus();
                return false;
            }

            if (mustSetPassword && (!accountPassword || accountPassword.value.trim() === '')) {
                alert('برای ایجاد حساب شاگرد، رمز عبور الزامی است.');
                showStep(3);
                if (accountPassword) accountPassword.focus();
                return false;
            }

            if (accountPassword && accountPassword.value !== '') {
                if (accountPassword.value.length < 8) {
                    alert('رمز عبور حساب شاگرد باید حداقل ۸ کاراکتر باشد.');
                    showStep(3);
                    accountPassword.focus();
                    return false;
                }
                if (!accountPasswordConfirm || accountPassword.value !== accountPasswordConfirm.value) {
                    alert('تکرار رمز عبور حساب شاگرد یکسان نیست.');
                    showStep(3);
                    if (accountPasswordConfirm) accountPasswordConfirm.focus();
                    return false;
                }
            } else if (accountPasswordConfirm && accountPasswordConfirm.value !== '') {
                alert('برای تکرار رمز، ابتدا رمز عبور جدید را وارد کنید.');
                showStep(3);
                if (accountPassword) accountPassword.focus();
                return false;
            }
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
            if (isTabMode || idx <= currentStep) showStep(idx);
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

    if (levelSelect) {
        levelSelect.addEventListener('change', () => {
            updateConditionalBlocks();
            syncClassFromText();
        });
    }

    if (schoolClassSearch) {
        schoolClassSearch.addEventListener('input', syncClassFromText);
        schoolClassSearch.addEventListener('change', syncClassFromText);
        schoolClassSearch.addEventListener('blur', syncClassFromText);
    }
    if (enrollmentYearTrigger) {
        enrollmentYearTrigger.addEventListener('click', () => {
            setEnrollmentYearOpened(!enrollmentYearState.opened);
        });
    }
    if (enrollmentYearSearch) {
        enrollmentYearSearch.addEventListener('input', () => {
            const nextQuery = normalizeDigits((enrollmentYearSearch.value || '').trim());
            if (enrollmentYearState.debounceTimer) {
                clearTimeout(enrollmentYearState.debounceTimer);
            }
            enrollmentYearState.debounceTimer = setTimeout(() => {
                enrollmentYearState.query = nextQuery;
                renderEnrollmentYearPage(true);
            }, 220);
        });
    }
    if (enrollmentYearList) {
        enrollmentYearList.addEventListener('scroll', () => {
            if (!enrollmentYearState.opened || enrollmentYearState.loading || !enrollmentYearState.hasMore) return;
            const threshold = 30;
            const remaining = enrollmentYearList.scrollHeight - enrollmentYearList.scrollTop - enrollmentYearList.clientHeight;
            if (remaining <= threshold) {
                loadMoreEnrollmentYears();
            }
        });
    }

    if (birthDateDay) birthDateDay.addEventListener('change', syncBirthHidden);
    if (birthDateMonth) birthDateMonth.addEventListener('change', syncBirthHidden);
    if (birthDateYear) birthDateYear.addEventListener('change', syncBirthHidden);

    document.addEventListener('click', (event) => {
        if (!enrollmentYearState.opened || !enrollmentYearCombo) return;
        if (!enrollmentYearCombo.contains(event.target)) {
            setEnrollmentYearOpened(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && enrollmentYearState.opened) {
            setEnrollmentYearOpened(false);
        }
    });

    setBirthFromExisting();
    syncBirthHidden();
    syncClassFromHiddenId();
    updateEnrollmentYearTriggerText();
    updateConditionalBlocks();
    showStep(0);
})();
</script>
