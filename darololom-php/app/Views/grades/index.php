<div class="section-title">
    <h2><?= ($mode ?? 'admin') === 'teacher' ? 'ثبت نمرات مضامین اختصاص‌داده‌شده' : 'ثبت نمرات دانش‌آموز' ?></h2>
</div>

<div class="news-thumb">
    <div class="news-info">
        <?php if (($mode ?? 'admin') === 'teacher'): ?>
            <div class="teacher-grade-header">
                <div>
                    <h3 class="teacher-grade-title">مضامین من</h3>
                    <p class="teacher-grade-lead">روی هر کارت کلیک کنید تا شاگردان همان مضمون باز شوند و نمرات را ثبت و ذخیره نمایید.</p>
                </div>
                <div class="teacher-grade-summary">
                    <?php if (($assignment['classes'] ?? []) === []): ?>
                        <span class="teacher-grade-summary-item">بدون محدودیت صنف</span>
                    <?php else: ?>
                        <span class="teacher-grade-summary-item">صنوف اختصاص‌یافته: <?= e((string) count((array) ($assignment['classes'] ?? []))) ?></span>
                    <?php endif; ?>
                    <span class="teacher-grade-summary-item">مضامین اختصاص‌یافته: <?= e((string) count((array) ($assignment['subjects'] ?? []))) ?></span>
                </div>
            </div>

            <?php if (($assignment['subjects'] ?? []) === []): ?>
                <div class="teacher-grade-empty">
                    هنوز هیچ مضمونی برای حساب شما اختصاص داده نشده است.
                </div>
            <?php else: ?>
                <div class="teacher-subject-grid">
                    <?php foreach (($assignment['subjects'] ?? []) as $subject): ?>
                        <?php $isActiveSubject = (int) (($selectedSubject['id'] ?? 0)) === (int) ($subject['id'] ?? 0); ?>
                        <a href="<?= e(url('/grades?subject_id=' . (int) ($subject['id'] ?? 0))) ?>" class="teacher-subject-card<?= $isActiveSubject ? ' is-active' : '' ?>">
                            <div class="teacher-subject-card-top">
                                <span class="teacher-subject-pill"><?= e((string) ($subject['level_name'] ?? 'مضمون')) ?></span>
                                <span class="teacher-subject-term"><?= e((string) ($subject['term_label'] ?? '—')) ?></span>
                            </div>
                            <h4 class="teacher-subject-name"><?= e((string) ($subject['name'] ?? '—')) ?></h4>
                            <p class="teacher-subject-link"><?= $isActiveSubject ? 'در حال نمره‌دهی' : 'مشاهده شاگردان و ثبت نمره' ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($selectedSubject): ?>
                <div class="teacher-grade-panel">
                    <div class="teacher-grade-panel-head">
                        <div>
                            <h3 class="teacher-grade-panel-title"><?= e((string) ($selectedSubject['name'] ?? '—')) ?></h3>
                            <p class="teacher-grade-panel-subtitle">
                                سطح <?= e((string) ($selectedSubject['level_name'] ?? '—')) ?>،
                                <?= e((string) ($selectedSubject['term_label'] ?? '—')) ?>
                            </p>
                        </div>
                        <div class="teacher-grade-panel-count">
                            <?= e((string) count((array) ($subjectStudents ?? []))) ?> شاگرد
                        </div>
                    </div>

                    <form method="post" action="<?= e(url('/grades/store')) ?>" class="module-form teacher-grade-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="subject_id" value="<?= e((string) ($selectedSubject['id'] ?? 0)) ?>">

                        <table class="table table-striped table-bordered teacher-grade-table">
                            <thead>
                                <tr>
                                    <th>شاگرد</th>
                                    <th>صنف</th>
                                    <th>نمره (0-100)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($subjectStudents ?? []) as $student): ?>
                                    <tr>
                                        <td><?= e((string) ($student['name'] ?? '—')) ?></td>
                                        <td><?= e((string) ($student['class_name'] ?? '—')) ?></td>
                                        <td>
                                            <input
                                                type="number"
                                                min="0"
                                                max="100"
                                                class="form-control"
                                                name="student_scores[<?= e((string) ($student['id'] ?? 0)) ?>]"
                                                value="<?= e((string) ($subjectScoreMap[$student['id']] ?? '')) ?>"
                                                autocomplete="off"
                                            >
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (($subjectStudents ?? []) === []): ?>
                                    <tr>
                                        <td colspan="3" class="text-center">برای این مضمون شاگردی یافت نشد.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <?php if (($subjectStudents ?? []) !== []): ?>
                            <button class="section-btn btn btn-default" type="submit">ذخیره نمرات این مضمون</button>
                        <?php endif; ?>
                    </form>
                </div>
            <?php elseif (($assignment['subjects'] ?? []) !== []): ?>
                <div class="teacher-grade-empty teacher-grade-empty-soft">
                    یک مضمون را از کارت‌های بالا انتخاب کنید تا شاگردان آن باز شوند.
                </div>
            <?php endif; ?>
        <?php else: ?>
            <form method="get" class="form-inline">
                <label>انتخاب دانش‌آموز:</label>
                <select class="form-control" name="student_id" onchange="this.form.submit()">
                    <?php if ($students === []): ?>
                        <option value="">شاگردی یافت نشد</option>
                    <?php endif; ?>
                    <?php foreach ($students as $item): ?>
                        <option value="<?= e((string) $item['id']) ?>" <?= (int) ($selectedStudent['id'] ?? 0) === (int) $item['id'] ? 'selected' : '' ?>>
                            <?= e($item['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <?php if ($selectedStudent): ?>
                <hr>
                <h3><?= e($selectedStudent['name']) ?></h3>
                <form method="post" action="<?= e(url('/grades/store')) ?>" class="module-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="student_id" value="<?= e((string) $selectedStudent['id']) ?>">
                    <input type="hidden" name="changed_subject_ids" value="" class="js-changed-subject-ids">

                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr><th>مضمون</th><th>نمره (0-100)</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($subjects as $subject): ?>
                            <tr>
                                <td><?= e($subject['name']) ?></td>
                                <td>
                                    <input type="number" min="0" max="100" class="form-control js-score-input" data-subject-id="<?= e((string) $subject['id']) ?>" name="scores[<?= e((string) $subject['id']) ?>]" value="<?= e((string) ($scoreMap[$subject['id']] ?? '')) ?>" autocomplete="off">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($subjects === []): ?>
                            <tr>
                                <td colspan="2" class="text-center">برای این شاگرد، مضمون قابل ثبت برای شما موجود نیست.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>

                    <?php if ($subjects !== []): ?>
                        <button class="section-btn btn btn-default" type="submit">ذخیره نمرات</button>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    if (<?= json_encode(($mode ?? 'admin') === 'teacher') ?>) {
        return;
    }

    var form = document.querySelector('form[action$="/grades/store"]');
    if (!form) {
        return;
    }

    var changedInput = form.querySelector('.js-changed-subject-ids');
    if (!changedInput) {
        return;
    }

    var dirtyMap = {};
    var scoreInputs = form.querySelectorAll('.js-score-input');

    function syncChangedIds() {
        changedInput.value = Object.keys(dirtyMap).join(',');
    }

    scoreInputs.forEach(function (input) {
        var subjectId = parseInt(input.getAttribute('data-subject-id') || '0', 10);
        if (!subjectId) {
            return;
        }

        function markDirty() {
            dirtyMap[String(subjectId)] = true;
            syncChangedIds();
        }

        input.addEventListener('input', markDirty);
        input.addEventListener('change', markDirty);
    });
})();
</script>
