<div class="section-title">
    <h2><?= ($mode ?? 'admin') === 'teacher' ? 'ثبت نمرات صنوف اختصاص‌داده‌شده' : 'ثبت نمرات دانش‌آموز' ?></h2>
</div>

<div class="news-thumb">
    <div class="news-info">
        <?php if (($mode ?? 'admin') === 'teacher'): ?>
            <div class="form-group full">
                <label>صنوف اختصاص‌داده‌شده به شما</label>
                <?php if (($assignment['classes'] ?? []) === []): ?>
                    <p class="field-help">هیچ صنفی برای شما تخصیص نشده است.</p>
                <?php else: ?>
                    <div class="inline-checks">
                        <?php foreach (($assignment['classes'] ?? []) as $class): ?>
                            <label><?= e((string) $class['name']) ?></label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-group full">
                <label>مضامین اختصاص‌داده‌شده به شما</label>
                <?php if (($assignment['subjects'] ?? []) === []): ?>
                    <p class="field-help">هیچ مضمونی برای شما تخصیص نشده است.</p>
                <?php else: ?>
                    <div class="inline-checks">
                        <?php foreach (($assignment['subjects'] ?? []) as $subject): ?>
                            <label><?= e((string) $subject['name']) ?></label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

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
    </div>
</div>

<script>
(function () {
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
