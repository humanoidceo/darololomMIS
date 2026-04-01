<?php
$oldOr = static fn(string $key, mixed $fallback = ''): mixed => old($key, $subject[$key] ?? $fallback);
$selectedTeacherId = (int) old('teacher_id', (int) ($selectedTeacher['id'] ?? 0));
$selectedTeacherLabel = trim((string) old('teacher_name_display', ''));
if ($selectedTeacherLabel === '' && !empty($selectedTeacher)) {
    $selectedTeacherLabel = trim((string) ($selectedTeacher['name'] ?? ''));
    $fatherName = trim((string) ($selectedTeacher['father_name'] ?? ''));
    if ($fatherName !== '') {
        $selectedTeacherLabel .= ' (' . $fatherName . ')';
    }
}
$selectedTeacherLabel = $selectedTeacherLabel !== '' ? $selectedTeacherLabel : 'انتخاب استاد';
?>

<div class="section-title">
    <h2><?= e($subject ? 'ویرایش مضمون' : 'ثبت مضمون جدید') ?></h2>
</div>

<div class="news-thumb">
    <div class="news-info">
        <form method="post" action="<?= e($formAction) ?>" class="module-form">
            <?= csrf_field() ?>

            <div class="form-group">
                <label>نام مضمون</label>
                <input type="text" name="name" class="form-control" value="<?= e((string) $oldOr('name')) ?>" required>
            </div>

            <div class="form-group">
                <label>سطح آموزشی</label>
                <select name="level_id" id="subject_level_id" class="form-control" required>
                    <option value="">انتخاب کنید</option>
                    <?php foreach ($levels as $level): ?>
                        <option
                            value="<?= e((string) $level['id']) ?>"
                            data-level-code="<?= e((string) ($level['code'] ?? '')) ?>"
                            <?= (string) $oldOr('level_id') === (string) $level['id'] ? 'selected' : '' ?>
                        >
                            <?= e($level['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" id="subject_semester_group">
                <label>سمستر</label>
                <select name="semester" id="subject_semester" class="form-control">
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                        <option value="<?= $i ?>" <?= (string) $oldOr('semester', 1) === (string) $i ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="form-group" id="subject_period_group">
                <label>دوره</label>
                <select name="period_id" id="subject_period_id" class="form-control">
                    <option value="">—</option>
                    <?php foreach ($periods as $item): ?>
                        <option value="<?= e((string) $item['id']) ?>" <?= (string) $oldOr('period_id') === (string) $item['id'] ? 'selected' : '' ?>>
                            <?= e((string) $item['number']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>استاد مربوطه</label>
                <input type="hidden" name="teacher_id" id="subject_teacher_id" value="<?= e((string) $selectedTeacherId) ?>">
                <input type="hidden" name="teacher_name_display" id="subject_teacher_name_display" value="<?= e($selectedTeacherLabel) ?>">

                <div class="subject-instructor-combo" id="subjectInstructorCombo" data-api-url="<?= e(url('/api/subjects/teachers')) ?>">
                    <button type="button" class="form-control subject-instructor-trigger" id="subjectInstructorTrigger" aria-haspopup="listbox" aria-expanded="false">
                        <span id="subjectInstructorTriggerText"><?= e($selectedTeacherLabel) ?></span>
                        <span class="subject-instructor-arrow" aria-hidden="true">▾</span>
                    </button>
                    <div class="subject-instructor-dropdown" id="subjectInstructorDropdown" hidden>
                        <input type="text" id="subjectInstructorSearch" class="form-control" placeholder="جستجوی استاد..." autocomplete="off">
                        <div class="subject-instructor-list" id="subjectInstructorList" role="listbox"></div>
                        <div class="subject-instructor-status" id="subjectInstructorStatus"></div>
                    </div>
                </div>
                <small class="field-help">با انتخاب استاد، مضمون به صورت خودکار با همان استاد مرتبط می‌شود.</small>
            </div>

            <button class="section-btn btn btn-default" type="submit">ذخیره</button>
            <a class="btn btn-default" href="<?= e(url('/subjects')) ?>">انصراف</a>
        </form>
    </div>
</div>

<script>
(function () {
    var combo = document.getElementById('subjectInstructorCombo');
    if (!combo) return;

    var apiUrl = combo.getAttribute('data-api-url') || '';
    var trigger = document.getElementById('subjectInstructorTrigger');
    var triggerText = document.getElementById('subjectInstructorTriggerText');
    var dropdown = document.getElementById('subjectInstructorDropdown');
    var searchInput = document.getElementById('subjectInstructorSearch');
    var list = document.getElementById('subjectInstructorList');
    var status = document.getElementById('subjectInstructorStatus');
    var teacherIdInput = document.getElementById('subject_teacher_id');
    var teacherNameInput = document.getElementById('subject_teacher_name_display');

    var state = {
        opened: false,
        loadedOnce: false,
        loading: false,
        page: 1,
        hasMore: true,
        query: '',
        debounceTimer: null
    };

    function setStatus(message) {
        if (status) status.textContent = message;
    }

    function setOpened(opened) {
        state.opened = opened;
        if (dropdown) dropdown.hidden = !opened;
        if (trigger) trigger.setAttribute('aria-expanded', opened ? 'true' : 'false');

        if (opened && !state.loadedOnce) {
            fetchPage(1, '', true);
        }
        if (opened && searchInput) {
            searchInput.focus();
        }
    }

    function selectedTeacherId() {
        return parseInt((teacherIdInput && teacherIdInput.value) || '0', 10) || 0;
    }

    function buildOption(item) {
        var option = document.createElement('button');
        option.type = 'button';
        option.className = 'subject-instructor-option';
        option.setAttribute('role', 'option');
        option.setAttribute('data-id', String(item.id));
        option.setAttribute('data-label', item.label || item.name || '—');
        option.textContent = item.label || item.name || '—';

        if (item.id === selectedTeacherId()) {
            option.classList.add('is-selected');
        }

        option.addEventListener('click', function () {
            if (teacherIdInput) teacherIdInput.value = String(item.id || '');
            var label = item.label || item.name || '—';
            if (teacherNameInput) teacherNameInput.value = label;
            if (triggerText) triggerText.textContent = label;
            setOpened(false);
        });

        return option;
    }

    function renderItems(items, reset) {
        if (!list) return;
        if (reset) {
            list.innerHTML = '';
        }

        if (!items || items.length === 0) {
            if (reset) {
                setStatus('استادی پیدا نشد.');
            }
            return;
        }

        var fragment = document.createDocumentFragment();
        items.forEach(function (item) {
            fragment.appendChild(buildOption(item));
        });
        list.appendChild(fragment);
    }

    function fetchPage(page, query, reset) {
        if (!apiUrl || state.loading) return;
        state.loading = true;
        setStatus('در حال بارگذاری...');

        var url = new URL(apiUrl, window.location.origin);
        url.searchParams.set('page', String(page));
        if (query) {
            url.searchParams.set('q', query);
        }

        fetch(url.toString(), {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(function (res) {
                if (!res.ok) {
                    throw new Error('HTTP ' + res.status);
                }
                return res.json();
            })
            .then(function (data) {
                state.loadedOnce = true;
                state.page = page;
                state.hasMore = Boolean(data && data.has_more);
                var items = (data && Array.isArray(data.items)) ? data.items : [];
                renderItems(items, reset);

                if (!items.length && page === 1) {
                    setStatus('استادی پیدا نشد.');
                } else if (state.hasMore) {
                    setStatus('برای دریافت موارد بیشتر اسکرول کنید.');
                } else {
                    setStatus('پایان لیست.');
                }
            })
            .catch(function () {
                setStatus('بارگذاری لیست اساتید ناموفق بود.');
            })
            .finally(function () {
                state.loading = false;
            });
    }

    if (trigger) {
        trigger.addEventListener('click', function () {
            setOpened(!state.opened);
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            var nextQuery = (searchInput.value || '').trim();
            if (state.debounceTimer) {
                clearTimeout(state.debounceTimer);
            }

            state.debounceTimer = setTimeout(function () {
                state.query = nextQuery;
                state.page = 1;
                state.hasMore = true;
                fetchPage(1, state.query, true);
            }, 250);
        });
    }

    if (list) {
        list.addEventListener('scroll', function () {
            if (!state.opened || state.loading || !state.hasMore) return;
            var threshold = 30;
            var remaining = list.scrollHeight - list.scrollTop - list.clientHeight;
            if (remaining <= threshold) {
                fetchPage(state.page + 1, state.query, false);
            }
        });
    }

    document.addEventListener('click', function (event) {
        if (!state.opened) return;
        if (!combo.contains(event.target)) {
            setOpened(false);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && state.opened) {
            setOpened(false);
        }
    });
})();
</script>

<script>
(function () {
    var levelSelect = document.getElementById('subject_level_id');
    var semesterGroup = document.getElementById('subject_semester_group');
    var periodGroup = document.getElementById('subject_period_group');
    var semesterSelect = document.getElementById('subject_semester');
    var periodSelect = document.getElementById('subject_period_id');

    if (!levelSelect || !semesterGroup || !periodGroup || !semesterSelect || !periodSelect) return;

    function selectedLevelCode() {
        var selected = levelSelect.options[levelSelect.selectedIndex];
        if (!selected) return '';
        return (selected.getAttribute('data-level-code') || '').trim();
    }

    function toggleByLevel() {
        var code = selectedLevelCode();
        var isAali = code === 'aali';
        var isPeriodBased = code === 'ebtedai' || code === 'moteseta';

        semesterGroup.style.display = isAali ? '' : 'none';
        semesterSelect.disabled = !isAali;
        semesterSelect.required = isAali;

        periodGroup.style.display = isPeriodBased ? '' : 'none';
        periodSelect.disabled = !isPeriodBased;
        periodSelect.required = isPeriodBased;

        if (isAali) {
            periodSelect.value = '';
        } else if (isPeriodBased) {
            if (!semesterSelect.value) {
                semesterSelect.value = '1';
            }
        } else {
            semesterSelect.disabled = true;
            periodSelect.disabled = true;
        }
    }

    levelSelect.addEventListener('change', toggleByLevel);
    toggleByLevel();
})();
</script>
