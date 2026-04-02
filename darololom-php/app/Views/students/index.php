<?php
$totalPages = max(1, (int) ceil($total / max(1, $pageSize)));
$behaviorJsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
    $behaviorJsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
}
$canOpenGradesModal = auth_role() === 'teacher' || can('manage_grades');
$returnTo = '/students?level=' . urlencode((string) $level)
    . '&q=' . urlencode((string) $q)
    . '&page_size=' . (int) $pageSize
    . '&page=' . (int) $page;
?>

<div class="section-title">
    <h2>لیست دانش‌آموزان</h2>
</div>

<div class="toolbar-row">
    <form method="get" class="filter-form form-inline">
        <input class="form-control" type="text" name="q" value="<?= e($q) ?>" placeholder="جستجو نام، پدر، موبایل یا تذکره...">
        <select name="level" class="form-control">
            <option value="aali" <?= $level === 'aali' ? 'selected' : '' ?>>عالی</option>
            <option value="moteseta" <?= $level === 'moteseta' ? 'selected' : '' ?>>متوسطه</option>
            <option value="ebtedai" <?= $level === 'ebtedai' ? 'selected' : '' ?>>ابتداییه</option>
        </select>
        <select name="page_size" class="form-control">
            <?php foreach ($allowedSizes as $size): ?>
                <option value="<?= e((string) $size) ?>" <?= (int) $pageSize === (int) $size ? 'selected' : '' ?>><?= e((string) $size) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="section-btn btn btn-default" type="submit">فیلتر</button>
    </form>

    <?php if (can('register_students')): ?>
        <a class="section-btn btn btn-default" href="<?= e(url('/students/create')) ?>">+ ثبت دانش‌آموز</a>
    <?php endif; ?>
</div>

<div class="news-thumb">
    <div class="news-info">
        <table class="table table-bordered table-hover student-table">
            <thead>
            <tr>
                <th>نام</th>
                <th>نام پدر</th>
                <th>سطح</th>
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
                    <td><?= e($student['class_name'] ?: '—') ?></td>
                    <td>
                        <?php if (!empty($student['semesters_display'])): ?>
                            سمستر: <?= e($student['semesters_display']) ?>
                        <?php elseif (!empty($student['periods_display'])): ?>
                            دوره: <?= e($student['periods_display']) ?>
                        <?php else: ?>
                            —
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
                    <a class="btn btn-default btn-sm" href="<?= e(url('/students?level=' . urlencode($level) . '&q=' . urlencode($q) . '&page_size=' . $pageSize . '&page=' . ($page - 1))) ?>">قبلی</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a class="btn btn-default btn-sm" href="<?= e(url('/students?level=' . urlencode($level) . '&q=' . urlencode($q) . '&page_size=' . $pageSize . '&page=' . ($page + 1))) ?>">بعدی</a>
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
                    <div class="grades-modal-state js-grades-modal-message" hidden></div>

                    <table class="table table-striped table-bordered grades-modal-table">
                        <thead>
                            <tr>
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
        var tableBody = modal.querySelector('.js-grades-modal-body');
        var messageEl = modal.querySelector('.js-grades-modal-message');
        var submitButton = modal.querySelector('.js-grades-modal-submit');
        var openButtons = document.querySelectorAll('.js-student-grades-btn');
        var closeButtons = modal.querySelectorAll('.js-student-grades-close');

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
            cell.colSpan = 2;
            cell.className = 'text-center grades-modal-empty-row';
            cell.textContent = message;
            row.appendChild(cell);
            tableBody.appendChild(row);
            submitButton.disabled = true;
        }

        function renderSubjects(subjects) {
            tableBody.innerHTML = '';

            if (!Array.isArray(subjects) || subjects.length === 0) {
                renderEmptyRow('برای این شاگرد مضمون قابل ثبت موجود نیست.');
                return;
            }

            var renderedCount = 0;
            subjects.forEach(function (subject) {
                var subjectId = Number(subject.id || 0);
                if (!subjectId) {
                    return;
                }

                var row = document.createElement('tr');

                var nameCell = document.createElement('td');
                nameCell.textContent = String(subject.name || '—');
                row.appendChild(nameCell);

                var inputCell = document.createElement('td');
                var input = document.createElement('input');
                input.type = 'number';
                input.min = '0';
                input.max = '100';
                input.className = 'form-control';
                input.name = 'scores[' + subjectId + ']';
                input.value = subject.score === null || typeof subject.score === 'undefined' ? '' : String(subject.score);
                inputCell.appendChild(input);
                row.appendChild(inputCell);

                tableBody.appendChild(row);
                renderedCount += 1;
            });

            if (renderedCount === 0) {
                renderEmptyRow('برای این شاگرد مضمون قابل ثبت موجود نیست.');
                return;
            }

            submitButton.disabled = false;
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
