<?php
$totalPages = max(1, (int) ceil($total / max(1, $pageSize)));
$behaviorJsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
    $behaviorJsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
}
$canOpenGradesModal = auth_role() === 'teacher' || can('manage_grades');
$selectedYear = (int) ($year ?? 0);
if ($selectedYear < 1350 || $selectedYear > 1500) {
    $selectedYear = 0;
}
$selectedSemesterNumbers = array_values(array_filter(
    array_map('intval', (array) ($semesterNumbers ?? [])),
    static fn (int $value): bool => in_array($value, [13, 14], true)
));
sort($selectedSemesterNumbers);
$selectedPeriodNumbers = array_values(array_filter(
    array_map('intval', (array) ($periodNumbers ?? [])),
    static fn (int $value): bool => $value >= 1 && $value <= 6
));
sort($selectedPeriodNumbers);
$selectedYearLabel = $selectedYear > 0 ? to_persian_number((string) $selectedYear) : 'انتخاب سال شمولیت';
$baseFilterQuery = [
    'level' => (string) $level,
    'year' => $selectedYear > 0 ? (string) $selectedYear : '',
    'q' => (string) $q,
    'page_size' => (int) $pageSize,
];
if ((string) $level === 'aali' && $selectedSemesterNumbers !== []) {
    $baseFilterQuery['semester'] = $selectedSemesterNumbers;
}
if ((string) $level !== 'aali' && $selectedPeriodNumbers !== []) {
    $baseFilterQuery['period'] = $selectedPeriodNumbers;
}
$returnToQuery = $baseFilterQuery;
$returnToQuery['page'] = (int) $page;
$returnTo = '/students?' . http_build_query($returnToQuery);
?>

<div class="section-title student-list-head">
    <h2>لیست دانش‌آموزان</h2>
    <?php if (can('register_students')): ?>
        <a class="section-btn btn btn-default student-create-btn" href="<?= e(url('/students/create')) ?>">+ ثبت دانش‌آموز</a>
    <?php endif; ?>
</div>

<div class="toolbar-row student-toolbar-row">
    <form method="get" class="filter-form form-inline student-list-filter" id="studentListFilterForm">
        <input type="hidden" name="level" id="student_filter_level" value="<?= e((string) $level) ?>">
        <input type="hidden" name="year" id="student_filter_year" value="<?= e($selectedYear > 0 ? (string) $selectedYear : '') ?>">

        <div class="student-filter-block student-filter-block--level">
            <div class="student-filter-label">سطح آموزشی</div>
            <div class="student-level-tabs" id="studentLevelTabs">
                <button type="button" class="student-level-chip js-student-level-chip<?= $level === 'aali' ? ' is-active' : '' ?>" data-level="aali">عالی</button>
                <button type="button" class="student-level-chip js-student-level-chip<?= $level === 'moteseta' ? ' is-active' : '' ?>" data-level="moteseta">متوسطه</button>
                <button type="button" class="student-level-chip js-student-level-chip<?= $level === 'ebtedai' ? ' is-active' : '' ?>" data-level="ebtedai">ابتداییه</button>
            </div>
        </div>

        <div class="student-filter-block student-filter-block--year">
            <div class="student-filter-label">سال شمولیت</div>
            <div class="student-year-combo student-list-year-combo" id="studentFilterYearCombo">
                <button type="button" class="form-control student-year-trigger" id="studentFilterYearTrigger" aria-haspopup="listbox" aria-expanded="false">
                    <span id="studentFilterYearTriggerText"><?= e($selectedYearLabel) ?></span>
                    <span class="student-year-arrow" aria-hidden="true">▾</span>
                </button>
                <div class="student-year-dropdown" id="studentFilterYearDropdown" hidden>
                    <input type="text" id="studentFilterYearSearch" class="form-control" placeholder="جستجوی سال..." autocomplete="off">
                    <div class="student-year-list" id="studentFilterYearList" role="listbox"></div>
                    <div class="student-year-status" id="studentFilterYearStatus"></div>
                </div>
            </div>
        </div>

        <div class="student-filter-block student-filter-block--search">
            <div class="student-filter-label">جستجو</div>
            <input class="form-control student-filter-search" type="text" name="q" value="<?= e($q) ?>" placeholder="نام، پدر، موبایل یا تذکره...">
        </div>

        <div class="student-filter-block student-filter-block--size">
            <div class="student-filter-label">تعداد در صفحه</div>
            <select name="page_size" class="form-control student-filter-size">
                <?php foreach ($allowedSizes as $size): ?>
                    <option value="<?= e((string) $size) ?>" <?= (int) $pageSize === (int) $size ? 'selected' : '' ?>><?= e((string) $size) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="student-filter-block student-filter-block--submit">
            <div class="student-filter-label student-filter-label--empty" aria-hidden="true">.</div>
            <button class="section-btn btn btn-default student-filter-submit" type="submit">فیلتر</button>
        </div>

        <div class="student-filter-block student-filter-block--term">
            <div class="student-term-filter <?= $selectedYear > 0 ? '' : 'is-disabled' ?>" id="studentTermFilter">
                <div class="student-term-title" id="studentTermFilterTitle"><?= (string) $level === 'aali' ? 'صنف (چند انتخابی)' : 'دوره (چند انتخابی)' ?></div>
                <div class="student-term-help" id="studentTermFilterHelp" <?= $selectedYear > 0 ? 'hidden' : '' ?>>ابتدا سال شمولیت را انتخاب کنید.</div>

                <div class="student-term-options student-term-options--semester" id="studentSemesterFilterGroup" <?= (string) $level === 'aali' ? '' : 'hidden' ?>>
                    <label class="student-term-option">
                        <input type="checkbox" name="semester[]" value="13" <?= in_array(13, $selectedSemesterNumbers, true) ? 'checked' : '' ?> <?= $selectedYear > 0 ? '' : 'disabled' ?>>
                        <span class="student-term-option-text">صنف <?= e(to_persian_number('13')) ?></span>
                    </label>
                    <label class="student-term-option">
                        <input type="checkbox" name="semester[]" value="14" <?= in_array(14, $selectedSemesterNumbers, true) ? 'checked' : '' ?> <?= $selectedYear > 0 ? '' : 'disabled' ?>>
                        <span class="student-term-option-text">صنف <?= e(to_persian_number('14')) ?></span>
                    </label>
                </div>

                <div class="student-term-options student-term-options--period" id="studentPeriodFilterGroup" <?= (string) $level !== 'aali' ? '' : 'hidden' ?>>
                    <?php for ($periodNumber = 1; $periodNumber <= 6; $periodNumber++): ?>
                        <label class="student-term-option">
                            <input type="checkbox" name="period[]" value="<?= e((string) $periodNumber) ?>" <?= in_array($periodNumber, $selectedPeriodNumbers, true) ? 'checked' : '' ?> <?= $selectedYear > 0 ? '' : 'disabled' ?>>
                            <span class="student-term-option-text">دوره <?= e(to_persian_number((string) $periodNumber)) ?></span>
                        </label>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
(function () {
    var form = document.getElementById('studentListFilterForm');
    if (!form) {
        return;
    }

    var levelInput = document.getElementById('student_filter_level');
    var yearInput = document.getElementById('student_filter_year');
    var levelButtons = form.querySelectorAll('.js-student-level-chip');
    var yearCombo = document.getElementById('studentFilterYearCombo');
    var yearTrigger = document.getElementById('studentFilterYearTrigger');
    var yearTriggerText = document.getElementById('studentFilterYearTriggerText');
    var yearDropdown = document.getElementById('studentFilterYearDropdown');
    var yearSearch = document.getElementById('studentFilterYearSearch');
    var yearList = document.getElementById('studentFilterYearList');
    var yearStatus = document.getElementById('studentFilterYearStatus');
    var termFilter = document.getElementById('studentTermFilter');
    var termTitle = document.getElementById('studentTermFilterTitle');
    var termHelp = document.getElementById('studentTermFilterHelp');
    var semesterGroup = document.getElementById('studentSemesterFilterGroup');
    var periodGroup = document.getElementById('studentPeriodFilterGroup');

    var allYears = [];
    for (var year = 1500; year >= 1350; year -= 1) {
        allYears.push(year);
    }

    var state = {
        opened: false,
        loading: false,
        page: 1,
        perPage: 5,
        hasMore: true,
        query: '',
        debounceTimer: null
    };

    function normalizeDigits(value) {
        var map = {
            '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4',
            '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
            '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
            '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9'
        };
        return String(value || '').replace(/[۰-۹٠-٩]/g, function (char) {
            return map[char] || char;
        });
    }

    function toPersianDigits(value) {
        var en = '0123456789';
        var fa = '۰۱۲۳۴۵۶۷۸۹';
        return String(value || '').replace(/\d/g, function (digit) {
            var index = en.indexOf(digit);
            return index >= 0 ? fa[index] : digit;
        });
    }

    function selectedYear() {
        return parseInt((yearInput && yearInput.value) || '0', 10) || 0;
    }

    function setYearStatus(message) {
        if (yearStatus) {
            yearStatus.textContent = message;
        }
    }

    function updateYearTriggerText() {
        if (!yearTriggerText) {
            return;
        }
        var selected = selectedYear();
        yearTriggerText.textContent = selected > 0 ? toPersianDigits(selected) : 'انتخاب سال شمولیت';
    }

    function refreshLevelChips() {
        for (var i = 0; i < levelButtons.length; i += 1) {
            var button = levelButtons[i];
            button.classList.toggle('is-active', (button.getAttribute('data-level') || '') === (levelInput ? levelInput.value : ''));
        }
    }

    function clearGroupSelections(group) {
        if (!group) {
            return;
        }
        var inputs = group.querySelectorAll('input[type="checkbox"]');
        for (var i = 0; i < inputs.length; i += 1) {
            inputs[i].checked = false;
        }
    }

    function setGroupDisabled(group, disabled) {
        if (!group) {
            return;
        }
        var inputs = group.querySelectorAll('input[type="checkbox"]');
        for (var i = 0; i < inputs.length; i += 1) {
            inputs[i].disabled = disabled;
        }
    }

    function refreshTermFilter() {
        var currentLevel = levelInput ? levelInput.value : 'aali';
        var isAali = currentLevel === 'aali';
        var hasYear = selectedYear() > 0;

        if (termTitle) {
            termTitle.textContent = isAali ? 'صنف (چند انتخابی)' : 'دوره (چند انتخابی)';
        }
        if (semesterGroup) {
            semesterGroup.hidden = !isAali;
        }
        if (periodGroup) {
            periodGroup.hidden = isAali;
        }
        if (termFilter) {
            termFilter.classList.toggle('is-disabled', !hasYear);
        }
        if (termHelp) {
            termHelp.hidden = hasYear;
        }

        setGroupDisabled(semesterGroup, !hasYear || !isAali);
        setGroupDisabled(periodGroup, !hasYear || isAali);
    }

    function filteredYears() {
        if (!state.query) {
            return allYears.slice();
        }

        return allYears.filter(function (item) {
            return String(item).indexOf(state.query) !== -1;
        });
    }

    function closeYearDropdown() {
        state.opened = false;
        if (yearDropdown) {
            yearDropdown.hidden = true;
        }
        if (yearTrigger) {
            yearTrigger.setAttribute('aria-expanded', 'false');
        }
    }

    function openYearDropdown() {
        state.opened = true;
        if (yearDropdown) {
            yearDropdown.hidden = false;
        }
        if (yearTrigger) {
            yearTrigger.setAttribute('aria-expanded', 'true');
        }
        renderYearPage(true);
        if (yearSearch) {
            yearSearch.focus();
        }
    }

    function createResetYearOption() {
        var option = document.createElement('button');
        option.type = 'button';
        option.className = 'student-year-option';
        option.textContent = 'همه سال‌ها';
        option.addEventListener('click', function () {
            if (yearInput) {
                yearInput.value = '';
            }
            clearGroupSelections(semesterGroup);
            clearGroupSelections(periodGroup);
            updateYearTriggerText();
            refreshTermFilter();
            closeYearDropdown();
            form.submit();
        });
        return option;
    }

    function createYearOption(yearValue) {
        var option = document.createElement('button');
        option.type = 'button';
        option.className = 'student-year-option';
        option.setAttribute('role', 'option');
        option.setAttribute('data-year', String(yearValue));
        option.textContent = toPersianDigits(yearValue);

        if (yearValue === selectedYear()) {
            option.classList.add('is-selected');
        }

        option.addEventListener('click', function () {
            if (yearInput) {
                yearInput.value = String(yearValue);
            }
            updateYearTriggerText();
            closeYearDropdown();
            form.submit();
        });

        return option;
    }

    function renderYearPage(reset) {
        if (!yearList) {
            return;
        }
        if (reset) {
            yearList.innerHTML = '';
            state.page = 1;
        }

        var filtered = filteredYears();
        var start = (state.page - 1) * state.perPage;
        var end = start + state.perPage;
        var chunk = filtered.slice(start, end);

        if (reset && state.query === '') {
            yearList.appendChild(createResetYearOption());
        }

        if (reset && chunk.length === 0) {
            setYearStatus('سال مورد نظر پیدا نشد.');
            state.hasMore = false;
            return;
        }

        var fragment = document.createDocumentFragment();
        for (var i = 0; i < chunk.length; i += 1) {
            fragment.appendChild(createYearOption(chunk[i]));
        }
        yearList.appendChild(fragment);

        state.hasMore = end < filtered.length;
        setYearStatus(state.hasMore ? 'برای مشاهده موارد بیشتر اسکرول کنید.' : 'پایان لیست.');
    }

    function loadMoreYearPage() {
        if (state.loading || !state.hasMore) {
            return;
        }
        state.loading = true;
        state.page += 1;
        renderYearPage(false);
        state.loading = false;
    }

    for (var i = 0; i < levelButtons.length; i += 1) {
        (function (button) {
            button.addEventListener('click', function () {
                if (levelInput) {
                    levelInput.value = button.getAttribute('data-level') || 'aali';
                }
                refreshLevelChips();
                if ((levelInput ? levelInput.value : 'aali') === 'aali') {
                    clearGroupSelections(periodGroup);
                } else {
                    clearGroupSelections(semesterGroup);
                }
                refreshTermFilter();

                if (selectedYear() > 0) {
                    form.submit();
                    return;
                }

                openYearDropdown();
            });
        })(levelButtons[i]);
    }

    if (yearTrigger) {
        yearTrigger.addEventListener('click', function () {
            if (state.opened) {
                closeYearDropdown();
            } else {
                openYearDropdown();
            }
        });
    }

    if (yearSearch) {
        yearSearch.addEventListener('input', function () {
            var nextQuery = normalizeDigits((yearSearch.value || '').trim());
            if (state.debounceTimer) {
                clearTimeout(state.debounceTimer);
            }
            state.debounceTimer = setTimeout(function () {
                state.query = nextQuery;
                renderYearPage(true);
            }, 220);
        });
    }

    if (yearList) {
        yearList.addEventListener('scroll', function () {
            if (!state.opened || state.loading || !state.hasMore) {
                return;
            }
            var threshold = 30;
            var remaining = yearList.scrollHeight - yearList.scrollTop - yearList.clientHeight;
            if (remaining <= threshold) {
                loadMoreYearPage();
            }
        });
    }

    document.addEventListener('click', function (event) {
        if (!state.opened || !yearCombo) {
            return;
        }
        if (!yearCombo.contains(event.target)) {
            closeYearDropdown();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && state.opened) {
            closeYearDropdown();
        }
    });

    updateYearTriggerText();
    refreshLevelChips();
    refreshTermFilter();
})();
</script>

<div class="news-thumb">
    <div class="news-info">
        <table class="table table-bordered table-hover student-table">
            <thead>
            <tr>
                <th>نام</th>
                <th>نام پدر</th>
                <th>سطح</th>
                <th>سال شمولیت</th>
                <th>نام صنف</th>
                <th>صنف/دوره</th>
                <th>امتیاز</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($students as $student): ?>
                <?php
                $studentBehaviors = $behaviors[(int) $student['id']] ?? [];
                $studentBehaviorsJson = json_encode(array_values($studentBehaviors), $behaviorJsonFlags);
                if ($studentBehaviorsJson === false) {
                    $studentBehaviorsJson = '[]';
                }
                $meritCount = (int) ($student['merit_count'] ?? 0);
                ?>
                <tr>
                    <td><?= e($student['name']) ?></td>
                    <td><?= e($student['father_name'] ?: '—') ?></td>
                    <td><?= e($student['level_name'] ?: '—') ?></td>
                    <td>
                        <?php if ((int) ($student['enrollment_year'] ?? 0) > 0): ?>
                            <?= e(to_persian_number((string) $student['enrollment_year'])) ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td><?= e($student['class_name'] ?: '—') ?></td>
                    <td>
                        <?php if ((string) ($student['level_code'] ?? '') === 'aali'): ?>
                            <?php if (!empty($student['semesters_display'])): ?>
                                 <?= e($student['semesters_display']) ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if (!empty($student['periods_display'])): ?>
                                دوره: <?= e($student['periods_display']) ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td><?= e((string) ($student['merit_count'] ?? 0)) ?></td>
                    <td class="actions-cell">
                        <?php if (can('manage_students')): ?>
                            <a class="btn btn-xs btn-info" href="<?= e(url('/students/' . $student['id'] . '/edit')) ?>">ویرایش</a>
                            <button
                                type="button"
                                class="btn btn-xs btn-primary js-student-behavior-btn"
                                data-student-id="<?= e((string) $student['id']) ?>"
                                data-student-name="<?= e($student['name']) ?>"
                                data-student-behaviors="<?= e($studentBehaviorsJson) ?>"
                            >
                                ثبت تخلف/امتیاز
                            </button>
                        <?php endif; ?>
                        <?php if ($canOpenGradesModal): ?>
                            <button
                                type="button"
                                class="btn btn-xs btn-warning js-student-grades-btn"
                                data-student-id="<?= e((string) $student['id']) ?>"
                                data-student-name="<?= e($student['name']) ?>"
                            >
                                نمرات
                            </button>
                        <?php endif; ?>
                        <a class="btn btn-xs btn-success" href="<?= e(url('/students/' . $student['id'] . '/results')) ?>">نتایج</a>
                        <?php if ($meritCount >= 3): ?>
                            <a class="btn btn-xs btn-primary" href="<?= e(url('/students/' . $student['id'] . '/appreciation')) ?>" target="_blank">تقدیرنامه</a>
                        <?php endif; ?>
                        <a class="btn btn-xs btn-default" href="<?= e(url('/students/' . $student['id'] . '/id-card')) ?>" target="_blank">ای‌دی کارت</a>
                        <?php if (can('manage_students')): ?>
                            <form method="post" action="<?= e(url('/students/' . $student['id'] . '/delete')) ?>" onsubmit="return confirm('آیا مطمئن هستید؟');">
                                <?= csrf_field() ?>
                                <button class="btn btn-xs btn-danger" type="submit">حذف</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="pagination-wrap">
            <span>صفحه <?= e((string) $page) ?> از <?= e((string) $totalPages) ?></span>
            <div>
                <?php if ($page > 1): ?>
                    <?php
                    $prevQuery = $baseFilterQuery;
                    $prevQuery['page'] = $page - 1;
                    ?>
                    <a class="btn btn-default btn-sm" href="<?= e(url('/students?' . http_build_query($prevQuery))) ?>">قبلی</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <?php
                    $nextQuery = $baseFilterQuery;
                    $nextQuery['page'] = $page + 1;
                    ?>
                    <a class="btn btn-default btn-sm" href="<?= e(url('/students?' . http_build_query($nextQuery))) ?>">بعدی</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($canOpenGradesModal): ?>
    <div
        id="studentGradesModal"
        class="behavior-modal-overlay"
        hidden
        data-fetch-template="<?= e(url('/grades/student/{id}/modal-data')) ?>"
    >
        <div class="behavior-modal-card grades-modal-card" role="dialog" aria-modal="true" aria-labelledby="studentGradesModalTitle">
            <div class="behavior-modal-head">
                <div>
                    <h3 id="studentGradesModalTitle">ثبت نمرات</h3>
                    <p class="behavior-modal-subtitle" id="studentGradesStudentName">—</p>
                </div>
                <button type="button" class="behavior-modal-close js-student-grades-close" aria-label="بستن">×</button>
            </div>
            <div class="behavior-modal-body">
                <form method="post" action="<?= e(url('/grades/store')) ?>" id="studentGradesForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="student_id" id="studentGradesStudentId" value="">
                    <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                    <input type="hidden" name="changed_subject_ids" id="studentGradesChangedSubjectIds" value="">
                    <div class="grades-modal-state js-grades-modal-message" hidden></div>

                    <table class="table table-bordered student-grade-sheet grades-modal-table">
                        <thead>
                            <tr>
                                <th>مضمون</th>
                                <th>نمره (0-100)</th>
                                <th>مضمون</th>
                                <th>نمره (0-100)</th>
                            </tr>
                        </thead>
                        <tbody class="js-grades-modal-body"></tbody>
                    </table>

                    <div class="form-actions grades-modal-actions">
                        <button type="submit" class="section-btn btn btn-default js-grades-modal-submit">ذخیره نمرات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var modal = document.getElementById('studentGradesModal');
        if (!modal) {
            return;
        }
        modal.hidden = true;

        var fetchTemplate = modal.getAttribute('data-fetch-template') || '';
        var nameEl = document.getElementById('studentGradesStudentName');
        var studentIdInput = document.getElementById('studentGradesStudentId');
        var changedSubjectIdsInput = document.getElementById('studentGradesChangedSubjectIds');
        var formEl = document.getElementById('studentGradesForm');
        var tableBody = modal.querySelector('.js-grades-modal-body');
        var messageEl = modal.querySelector('.js-grades-modal-message');
        var submitButton = modal.querySelector('.js-grades-modal-submit');
        var openButtons = document.querySelectorAll('.js-student-grades-btn');
        var closeButtons = modal.querySelectorAll('.js-student-grades-close');
        var dirtySubjectMap = {};

        function closeModal() {
            modal.hidden = true;
            document.body.classList.remove('behavior-modal-open');
        }

        function setMessage(text, type) {
            messageEl.className = 'grades-modal-state js-grades-modal-message';
            if (!text) {
                messageEl.hidden = true;
                messageEl.textContent = '';
                return;
            }

            if (type) {
                messageEl.classList.add(type);
            }
            messageEl.hidden = false;
            messageEl.textContent = text;
        }

        function renderEmptyRow(message) {
            tableBody.innerHTML = '';
            var row = document.createElement('tr');
            var cell = document.createElement('td');
            cell.colSpan = 4;
            cell.className = 'text-center grades-modal-empty-row';
            cell.textContent = message;
            row.appendChild(cell);
            tableBody.appendChild(row);
            submitButton.disabled = true;
        }

        function syncChangedSubjectIds() {
            if (!changedSubjectIdsInput) {
                return;
            }
            changedSubjectIdsInput.value = Object.keys(dirtySubjectMap).join(',');
        }

        function renderSubjects(subjects) {
            tableBody.innerHTML = '';
            dirtySubjectMap = {};
            syncChangedSubjectIds();

            if (!Array.isArray(subjects) || subjects.length === 0) {
                renderEmptyRow('برای این شاگرد مضمون قابل ثبت موجود نیست.');
                return;
            }

            var grouped = {};
            var editableCount = 0;
            var subjectCount = 0;
            subjects.forEach(function (subject) {
                var subjectId = Number(subject.id || 0);
                if (!subjectId) {
                    return;
                }
                subjectCount += 1;

                var isEditable = subject.editable === true || subject.editable === 1 || subject.editable === '1';
                if (isEditable) {
                    editableCount += 1;
                }

                var termLabel = String(subject.term_label || '—');
                var termOrder = Number(subject.term_order || 0) || 0;
                if (!grouped[termLabel]) {
                    grouped[termLabel] = {
                        label: termLabel,
                        order: termOrder,
                        rows: [],
                        sum: 0,
                        max: 0
                    };
                }

                var scoreValue = null;
                if (subject.score !== null && typeof subject.score !== 'undefined' && String(subject.score).trim() !== '') {
                    var parsedScore = parseInt(String(subject.score), 10);
                    if (!Number.isNaN(parsedScore)) {
                        scoreValue = Math.max(0, Math.min(100, parsedScore));
                    }
                }

                grouped[termLabel].rows.push({
                    id: subjectId,
                    name: String(subject.name || ''),
                    score: scoreValue,
                    editable: isEditable
                });

                if (String(subject.name || '').trim() !== '') {
                    grouped[termLabel].max += 100;
                    grouped[termLabel].sum += scoreValue === null ? 0 : scoreValue;
                }
            });

            var terms = Object.keys(grouped).map(function (key) {
                return grouped[key];
            }).sort(function (a, b) {
                if (a.order !== b.order) {
                    return a.order - b.order;
                }
                return String(a.label).localeCompare(String(b.label));
            });

            if (terms.length === 0) {
                renderEmptyRow('برای این شاگرد مضمون قابل ثبت موجود نیست.');
                return;
            }

            function buildSubjectCells(subjectRow) {
                var nameCell = document.createElement('td');
                var scoreCell = document.createElement('td');
                if (!subjectRow) {
                    nameCell.className = 'grade-empty';
                    scoreCell.className = 'grade-empty';
                    return [nameCell, scoreCell];
                }

                nameCell.textContent = subjectRow.name || '—';

                var scoreWrap = document.createElement('div');
                scoreWrap.className = 'grades-modal-score-wrap';

                var input = document.createElement('input');
                input.type = 'number';
                input.min = '0';
                input.max = '100';
                input.className = 'form-control';
                input.name = 'scores[' + subjectRow.id + ']';
                input.value = subjectRow.score === null ? '' : String(subjectRow.score);
                input.setAttribute('data-subject-id', String(subjectRow.id));
                input.setAttribute('autocomplete', 'off');
                if (subjectRow.editable) {
                    input.addEventListener('input', function () {
                        dirtySubjectMap[String(subjectRow.id)] = true;
                        syncChangedSubjectIds();
                    });
                    input.addEventListener('change', function () {
                        dirtySubjectMap[String(subjectRow.id)] = true;
                        syncChangedSubjectIds();
                    });
                    scoreWrap.appendChild(input);
                } else {
                    input.disabled = true;
                    input.classList.add('grades-modal-locked-input');
                    input.title = 'فقط نمرات سمستر/دوره فعلی قابل ویرایش است.';
                    input.tabIndex = -1;

                    var lockedTop = document.createElement('div');
                    lockedTop.className = 'grades-modal-locked-top';
                    lockedTop.appendChild(input);

                    var toggleButton = document.createElement('button');
                    toggleButton.type = 'button';
                    toggleButton.className = 'btn btn-xs btn-default grades-override-toggle';
                    toggleButton.textContent = 'تغییر';
                    toggleButton.setAttribute('aria-expanded', 'false');
                    lockedTop.appendChild(toggleButton);
                    scoreWrap.appendChild(lockedTop);

                    var overridePanel = document.createElement('div');
                    overridePanel.className = 'grades-override-panel';
                    overridePanel.hidden = true;

                    var scoreField = document.createElement('div');
                    scoreField.className = 'grades-override-field';
                    var scoreLabel = document.createElement('label');
                    scoreLabel.className = 'grades-override-label';
                    scoreLabel.textContent = 'نمره جدید';
                    scoreField.appendChild(scoreLabel);

                    var overrideScoreInput = document.createElement('input');
                    overrideScoreInput.type = 'number';
                    overrideScoreInput.min = '0';
                    overrideScoreInput.max = '100';
                    overrideScoreInput.className = 'form-control grades-override-score js-override-score';
                    overrideScoreInput.name = 'override_scores[' + subjectRow.id + ']';
                    overrideScoreInput.setAttribute('autocomplete', 'off');
                    overrideScoreInput.setAttribute('data-subject-id', String(subjectRow.id));
                    scoreField.appendChild(overrideScoreInput);
                    overridePanel.appendChild(scoreField);

                    var reasonField = document.createElement('div');
                    reasonField.className = 'grades-override-field';
                    var reasonLabel = document.createElement('label');
                    reasonLabel.className = 'grades-override-label';
                    reasonLabel.textContent = 'دلیل تغییر';
                    reasonField.appendChild(reasonLabel);

                    var overrideReasonInput = document.createElement('textarea');
                    overrideReasonInput.className = 'form-control grades-override-reason js-override-reason';
                    overrideReasonInput.name = 'override_reasons[' + subjectRow.id + ']';
                    overrideReasonInput.rows = 2;
                    overrideReasonInput.setAttribute('data-subject-id', String(subjectRow.id));
                    reasonField.appendChild(overrideReasonInput);
                    overridePanel.appendChild(reasonField);

                    toggleButton.addEventListener('click', function () {
                        var open = overridePanel.hidden;
                        overridePanel.hidden = !open;
                        toggleButton.classList.toggle('is-open', open);
                        toggleButton.textContent = open ? 'بستن' : 'تغییر';
                        toggleButton.setAttribute('aria-expanded', open ? 'true' : 'false');
                        if (open) {
                            overrideScoreInput.focus();
                        }
                    });

                    scoreWrap.appendChild(overridePanel);
                }
                scoreCell.appendChild(scoreWrap);

                return [nameCell, scoreCell];
            }

            function openOverridePanelForInput(scoreInput) {
                if (!scoreInput) {
                    return;
                }
                var panel = scoreInput.closest('.grades-override-panel');
                if (panel) {
                    panel.hidden = false;
                }
                var cell = scoreInput.closest('td');
                if (!cell) {
                    return;
                }
                var button = cell.querySelector('.grades-override-toggle');
                if (button) {
                    button.classList.add('is-open');
                    button.textContent = 'بستن';
                    button.setAttribute('aria-expanded', 'true');
                }
            }

            function validateOverrideRows() {
                var overrideScoreInputs = modal.querySelectorAll('.js-override-score');
                for (var i = 0; i < overrideScoreInputs.length; i += 1) {
                    var scoreInput = overrideScoreInputs[i];
                    var scoreRaw = String(scoreInput.value || '').trim();
                    if (scoreRaw === '') {
                        continue;
                    }

                    var scoreValue = Number(scoreRaw);
                    if (Number.isNaN(scoreValue) || scoreValue < 0 || scoreValue > 100) {
                        setMessage('نمره جدید باید بین ۰ تا ۱۰۰ باشد.', 'is-error');
                        openOverridePanelForInput(scoreInput);
                        scoreInput.focus();
                        return false;
                    }

                    var subjectId = scoreInput.getAttribute('data-subject-id') || '';
                    var reasonInput = modal.querySelector('.js-override-reason[data-subject-id="' + subjectId + '"]');
                    var reasonText = reasonInput ? String(reasonInput.value || '').trim() : '';
                    if (reasonText.length < 3) {
                        setMessage('برای نمره جدید، دلیل تغییر حداقل ۳ حرف الزامی است.', 'is-error');
                        openOverridePanelForInput(scoreInput);
                        if (reasonInput) {
                            reasonInput.focus();
                        } else {
                            scoreInput.focus();
                        }
                        return false;
                    }
                }

                return true;
            }

            function summaryText(termGroup) {
                if (!termGroup || termGroup.max <= 0) {
                    return 'مجموع: — | فیصدی: —';
                }
                var percent = (termGroup.sum / termGroup.max) * 100;
                var percentText = percent.toFixed(1).replace(/\.0$/, '');
                return 'مجموع: ' + String(termGroup.sum) + ' از ' + String(termGroup.max) + ' | فیصدی: ' + percentText + '%';
            }

            for (var termIndex = 0; termIndex < terms.length; termIndex += 2) {
                var leftTerm = terms[termIndex];
                var rightTerm = terms[termIndex + 1] || null;

                var termHeaderRow = document.createElement('tr');
                termHeaderRow.className = 'grade-term-row';

                var leftTermCell = document.createElement('td');
                leftTermCell.colSpan = 2;
                var leftStrong = document.createElement('strong');
                leftStrong.textContent = leftTerm ? leftTerm.label : '';
                leftTermCell.appendChild(leftStrong);
                termHeaderRow.appendChild(leftTermCell);

                var rightTermCell = document.createElement('td');
                rightTermCell.colSpan = 2;
                var rightStrong = document.createElement('strong');
                rightStrong.textContent = rightTerm ? rightTerm.label : '';
                rightTermCell.appendChild(rightStrong);
                termHeaderRow.appendChild(rightTermCell);

                tableBody.appendChild(termHeaderRow);

                var leftRows = leftTerm ? leftTerm.rows : [];
                var rightRows = rightTerm ? rightTerm.rows : [];
                var maxRows = Math.max(leftRows.length, rightRows.length);

                for (var rowIndex = 0; rowIndex < maxRows; rowIndex += 1) {
                    var line = document.createElement('tr');
                    line.className = 'grades-modal-row';

                    var leftCells = buildSubjectCells(leftRows[rowIndex] || null);
                    var rightCells = buildSubjectCells(rightRows[rowIndex] || null);

                    line.appendChild(leftCells[0]);
                    line.appendChild(leftCells[1]);
                    line.appendChild(rightCells[0]);
                    line.appendChild(rightCells[1]);
                    tableBody.appendChild(line);
                }

                var summaryRow = document.createElement('tr');
                summaryRow.className = 'grade-summary-row';

                var leftSummaryCell = document.createElement('td');
                leftSummaryCell.colSpan = 2;
                leftSummaryCell.textContent = summaryText(leftTerm);
                summaryRow.appendChild(leftSummaryCell);

                var rightSummaryCell = document.createElement('td');
                rightSummaryCell.colSpan = 2;
                rightSummaryCell.textContent = rightTerm ? summaryText(rightTerm) : '';
                summaryRow.appendChild(rightSummaryCell);

                tableBody.appendChild(summaryRow);
            }

            submitButton.disabled = subjectCount === 0;
            if (editableCount > 0) {
                setMessage('فقط سطرهای مربوط به سمستر/دوره فعلی قابل ویرایش است.', '');
            } else {
                setMessage('سطرها قفل است؛ برای تغییر، دکمه «تغییر» هر مضمون را بزنید و نمره جدید با دلیل بنویسید.', '');
            }

            if (formEl) {
                formEl.onsubmit = function (event) {
                    if (!validateOverrideRows()) {
                        event.preventDefault();
                    }
                };
            }
        }

        function loadStudentSubjects(studentId) {
            var endpoint = fetchTemplate.replace('{id}', String(studentId));
            setMessage('در حال بارگذاری مضامین...', 'is-loading');
            renderEmptyRow('در حال بارگذاری...');

            return fetch(endpoint, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        if (!response.ok || !data || data.ok !== true) {
                            var message = (data && data.message) ? data.message : 'بارگذاری مضامین ناموفق بود.';
                            throw new Error(message);
                        }
                        return data;
                    });
                })
                .then(function (data) {
                    setMessage('', '');
                    renderSubjects(data.subjects || []);
                })
                .catch(function (error) {
                    setMessage(error.message || 'بارگذاری مضامین ناموفق بود.', 'is-error');
                    renderEmptyRow('امکان نمایش مضامین وجود ندارد.');
                });
        }

        function openModal(button) {
            var studentId = button.getAttribute('data-student-id') || '';
            var studentName = button.getAttribute('data-student-name') || '—';

            nameEl.textContent = 'دانش‌آموز: ' + studentName;
            studentIdInput.value = studentId;
            dirtySubjectMap = {};
            syncChangedSubjectIds();
            modal.hidden = false;
            document.body.classList.add('behavior-modal-open');

            if (!studentId) {
                setMessage('شناسه دانش‌آموز نامعتبر است.', 'is-error');
                renderEmptyRow('امکان نمایش مضامین وجود ندارد.');
                return;
            }

            loadStudentSubjects(studentId);
        }

        openButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                openModal(button);
            });
        });

        closeButtons.forEach(function (button) {
            button.addEventListener('click', closeModal);
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.hidden) {
                closeModal();
            }
        });
    })();
    </script>
<?php endif; ?>

<?php if (can('manage_students')): ?>
    <div
        id="studentBehaviorModal"
        class="behavior-modal-overlay"
        hidden
        data-action-template="<?= e(url('/students/{id}/behavior')) ?>"
        data-delete-template="<?= e(url('/students/behavior/{id}/delete')) ?>"
        data-csrf-token="<?= e(csrf_token()) ?>"
    >
        <div class="behavior-modal-card" role="dialog" aria-modal="true" aria-labelledby="studentBehaviorModalTitle">
            <div class="behavior-modal-head">
                <div>
                    <h3 id="studentBehaviorModalTitle">ثبت تخلف/امتیاز</h3>
                    <p class="behavior-modal-subtitle" id="studentBehaviorStudentName">—</p>
                </div>
                <button type="button" class="behavior-modal-close js-student-behavior-close" aria-label="بستن">×</button>
            </div>
            <div class="behavior-modal-body">
                <div class="behavior-tab-wrap">
                    <button type="button" class="behavior-tab is-active js-student-behavior-tab" data-tab="violation">تخلف</button>
                    <button type="button" class="behavior-tab js-student-behavior-tab" data-tab="merit">امتیاز</button>
                </div>

                <div class="behavior-panel is-active" data-panel="violation">
                    <form method="post" class="behavior-form-modal js-student-behavior-form" data-entry-type="violation">
                        <?= csrf_field() ?>
                        <input type="hidden" name="entry_type" value="violation">
                        <input type="hidden" name="student_id" class="js-student-id-field" value="">
                        <label for="studentViolationNote">توضیحات (اختیاری)</label>
                        <textarea id="studentViolationNote" name="note" rows="3" class="form-control" placeholder="شرح تخلف..."></textarea>
                        <button type="submit" class="btn btn-sm btn-danger">ثبت تخلف</button>
                    </form>
                    <div class="behavior-history-wrap">
                        <h4>سوابق تخلف</h4>
                        <div class="behavior-empty js-student-empty-violation">هیچ تخلفی ثبت نشده است.</div>
                        <div class="behavior-history-list js-student-history-violation"></div>
                    </div>
                </div>

                <div class="behavior-panel" data-panel="merit">
                    <form method="post" class="behavior-form-modal js-student-behavior-form" data-entry-type="merit">
                        <?= csrf_field() ?>
                        <input type="hidden" name="entry_type" value="merit">
                        <input type="hidden" name="student_id" class="js-student-id-field" value="">
                        <label for="studentMeritNote">توضیحات (اختیاری)</label>
                        <textarea id="studentMeritNote" name="note" rows="3" class="form-control" placeholder="شرح امتیاز..."></textarea>
                        <button type="submit" class="btn btn-sm btn-success">ثبت امتیاز</button>
                    </form>
                    <div class="behavior-history-wrap">
                        <h4>سوابق امتیاز</h4>
                        <div class="behavior-empty js-student-empty-merit">هیچ امتیازی ثبت نشده است.</div>
                        <div class="behavior-history-list js-student-history-merit"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var modal = document.getElementById('studentBehaviorModal');
        if (!modal) {
            return;
        }
        modal.hidden = true;

        var actionTemplate = modal.getAttribute('data-action-template') || '';
        var deleteTemplate = modal.getAttribute('data-delete-template') || '';
        var csrfToken = modal.getAttribute('data-csrf-token') || '';
        var nameEl = document.getElementById('studentBehaviorStudentName');
        var openButtons = document.querySelectorAll('.js-student-behavior-btn');
        var closeButtons = modal.querySelectorAll('.js-student-behavior-close');
        var tabs = modal.querySelectorAll('.js-student-behavior-tab');
        var panels = modal.querySelectorAll('.behavior-panel');
        var forms = modal.querySelectorAll('.js-student-behavior-form');
        var idFields = modal.querySelectorAll('.js-student-id-field');
        var listViolation = modal.querySelector('.js-student-history-violation');
        var listMerit = modal.querySelector('.js-student-history-merit');
        var emptyViolation = modal.querySelector('.js-student-empty-violation');
        var emptyMerit = modal.querySelector('.js-student-empty-merit');

        function setActiveTab(tabName) {
            tabs.forEach(function (tab) {
                tab.classList.toggle('is-active', tab.getAttribute('data-tab') === tabName);
            });
            panels.forEach(function (panel) {
                panel.classList.toggle('is-active', panel.getAttribute('data-panel') === tabName);
            });
        }

        function closeModal() {
            modal.hidden = true;
            document.body.classList.remove('behavior-modal-open');
        }

        function createDeleteForm(entryId) {
            var form = document.createElement('form');
            form.method = 'post';
            form.action = deleteTemplate.replace('{id}', String(entryId));
            form.className = 'behavior-delete-form';

            var tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = csrfToken;
            form.appendChild(tokenInput);

            var button = document.createElement('button');
            button.type = 'submit';
            button.className = 'btn btn-xs btn-link';
            button.textContent = 'حذف';
            form.appendChild(button);

            return form;
        }

        function renderHistory(entries, type, listEl, emptyEl) {
            listEl.innerHTML = '';
            var filtered = entries.filter(function (entry) {
                return String(entry.entry_type || '') === type;
            });

            if (filtered.length === 0) {
                emptyEl.style.display = 'block';
                return;
            }

            emptyEl.style.display = 'none';
            filtered.forEach(function (entry) {
                var item = document.createElement('div');
                item.className = 'behavior-history-item ' + (type === 'merit' ? 'merit' : 'violation');

                var textWrap = document.createElement('div');
                textWrap.className = 'behavior-history-text';

                var note = document.createElement('span');
                note.className = 'behavior-note';
                note.textContent = String(entry.note || '—');
                textWrap.appendChild(note);

                var meta = document.createElement('small');
                meta.className = 'behavior-meta';
                meta.textContent = entry.created_at ? ('تاریخ: ' + entry.created_at) : '';
                textWrap.appendChild(meta);

                item.appendChild(textWrap);

                if (entry.id) {
                    item.appendChild(createDeleteForm(entry.id));
                }

                listEl.appendChild(item);
            });
        }

        function openModal(button) {
            var studentId = button.getAttribute('data-student-id') || '';
            var studentName = button.getAttribute('data-student-name') || '—';
            var rawBehaviors = button.getAttribute('data-student-behaviors') || '[]';
            var parsed = [];

            try {
                parsed = JSON.parse(rawBehaviors);
                if (!Array.isArray(parsed)) {
                    parsed = [];
                }
            } catch (error) {
                parsed = [];
            }

            nameEl.textContent = 'دانش‌آموز: ' + studentName;
            idFields.forEach(function (field) {
                field.value = studentId;
            });
            forms.forEach(function (form) {
                form.action = actionTemplate.replace('{id}', studentId);
            });

            renderHistory(parsed, 'violation', listViolation, emptyViolation);
            renderHistory(parsed, 'merit', listMerit, emptyMerit);
            setActiveTab('violation');

            modal.hidden = false;
            document.body.classList.add('behavior-modal-open');
        }

        openButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                openModal(button);
            });
        });

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                setActiveTab(tab.getAttribute('data-tab') || 'violation');
            });
        });

        closeButtons.forEach(function (button) {
            button.addEventListener('click', closeModal);
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.hidden) {
                closeModal();
            }
        });
    })();
    </script>
<?php endif; ?>
