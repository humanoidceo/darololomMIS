<?php
$current = auth_user();
$emailValue = (string) old('email', (string) ($current['email'] ?? ''));
$educationMap = ['p' => 'چهارده پاس', 'b' => 'لیسانس', 'm' => 'ماستر', 'd' => 'دوکتور'];
$activeTab = (string) ($activeTab ?? 'profile');
if (!in_array($activeTab, ['profile', 'grades'], true)) {
    $activeTab = 'profile';
}
?>

<div class="section-title">
    <h2><?= $role === 'teacher' ? 'حساب کاربری استاد' : 'حساب کاربری شاگرد' ?></h2>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="news-thumb">
            <div class="news-info">
                <?php if ($role === 'student' && $student): ?>
                    <div class="student-account-tabs" style="margin-bottom: 14px;">
                        <a class="btn btn-sm <?= $activeTab === 'profile' ? 'btn-default' : 'btn-link' ?>" href="<?= e(url('/account?tab=profile')) ?>">پروفایل</a>
                        <a class="btn btn-sm <?= $activeTab === 'grades' ? 'btn-default' : 'btn-link' ?>" href="<?= e(url('/account?tab=grades')) ?>">نمرات</a>
                    </div>

                    <?php if ($activeTab === 'profile'): ?>
                    <h3><?= e((string) $student['name']) ?></h3>
                    <p><strong>نام پدر:</strong> <?= e((string) ($student['father_name'] ?? '—')) ?></p>
                    <p><strong>سطح آموزشی:</strong> <?= e((string) ($student['level_name'] ?? '—')) ?></p>
                    <p><strong>صنف:</strong> <?= e((string) ($student['class_name'] ?? '—')) ?></p>
                    <p><strong>شماره تماس:</strong> <?= e((string) ($student['mobile_number'] ?? '—')) ?></p>
                    <p><strong>سکونت فعلی:</strong> <?= e('ولایت: ' . (string) ($student['current_address'] ?? '—') . ' | ناحیه: ' . (string) ($student['area'] ?? '—') . ' | کوچه: ' . (string) ($student['current_street'] ?? '—')) ?></p>
                    <p><strong>سکونت اصلی:</strong> <?= e('ولایت: ' . (string) ($student['permanent_address'] ?? '—') . ' | ولسوالی: ' . (string) ($student['district'] ?? '—') . ' | قریه: ' . (string) ($student['village'] ?? '—')) ?></p>
                    <?php endif; ?>

                    <?php if ($activeTab === 'grades'): ?>
                    <hr>
                    <h4>نمرات</h4>
                    <?php if (($studentGradeRows ?? []) === []): ?>
                        <p class="field-help">برای شما هنوز مضمون/نمره‌ای ثبت نشده است.</p>
                    <?php else: ?>
                        <table class="table table-bordered student-grade-sheet">
                            <thead>
                                <tr>
                                    <th>مضمون</th>
                                    <th>نمره</th>
                                    <th>مضمون</th>
                                    <th>نمره</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $gradeRows = array_values((array) ($studentGradeRows ?? []));
                                $groupedRows = [];
                                foreach ($gradeRows as $gradeRow) {
                                    $termLabel = (string) ($gradeRow['term_label'] ?? '—');
                                    if (!isset($groupedRows[$termLabel])) {
                                        $groupedRows[$termLabel] = [];
                                    }
                                    $groupedRows[$termLabel][] = $gradeRow;
                                }
                                if (($student['level_code'] ?? '') === 'aali') {
                                    foreach ([1, 2, 3, 4] as $semesterNumber) {
                                        $termLabel = 'سمستر ' . (string) $semesterNumber;
                                        if (!isset($groupedRows[$termLabel])) {
                                            $groupedRows[$termLabel] = [[
                                                'term_label' => $termLabel,
                                                'subject_name' => '',
                                                'score' => null,
                                            ]];
                                        }
                                    }
                                    uksort($groupedRows, static function (string $a, string $b): int {
                                        $aNum = (int) preg_replace('/[^0-9]/', '', $a);
                                        $bNum = (int) preg_replace('/[^0-9]/', '', $b);
                                        return $aNum <=> $bNum;
                                    });
                                }
                                $termLabels = array_keys($groupedRows);
                                $termPairs = array_chunk($termLabels, 2);
                                ?>
                                <?php foreach ($termPairs as $pair): ?>
                                    <?php
                                    $leftTerm = (string) ($pair[0] ?? '—');
                                    $rightTerm = isset($pair[1]) ? (string) $pair[1] : null;
                                    $leftRows = $groupedRows[$leftTerm] ?? [];
                                    $rightRows = $rightTerm !== null ? ($groupedRows[$rightTerm] ?? []) : [];
                                    $maxRows = max(count($leftRows), count($rightRows));
                                    $leftSum = 0;
                                    $leftMax = 0;
                                    $rightSum = 0;
                                    $rightMax = 0;
                                    ?>
                                    <tr class="grade-term-row">
                                        <td colspan="2"><strong><?= e($leftTerm) ?></strong></td>
                                        <td colspan="2"><strong><?= e((string) ($rightTerm ?? '')) ?></strong></td>
                                    </tr>
                                    <?php for ($i = 0; $i < $maxRows; $i++): ?>
                                        <?php
                                        $leftRow = $leftRows[$i] ?? null;
                                        $rightRow = $rightRows[$i] ?? null;
                                        $leftScore = is_array($leftRow) ? ($leftRow['score'] ?? null) : null;
                                        $rightScore = is_array($rightRow) ? ($rightRow['score'] ?? null) : null;
                                        $leftSubjectName = is_array($leftRow) ? trim((string) ($leftRow['subject_name'] ?? '')) : '';
                                        $rightSubjectName = is_array($rightRow) ? trim((string) ($rightRow['subject_name'] ?? '')) : '';
                                        if ($leftSubjectName !== '') {
                                            $leftMax += 100;
                                            $leftSum += $leftScore === null ? 0 : (int) $leftScore;
                                        }
                                        if ($rightSubjectName !== '') {
                                            $rightMax += 100;
                                            $rightSum += $rightScore === null ? 0 : (int) $rightScore;
                                        }
                                        ?>
                                        <tr>
                                            <td class="<?= $leftRow === null ? 'grade-empty' : '' ?>"><?= $leftRow === null ? '' : e((string) ($leftRow['subject_name'] ?? '')) ?></td>
                                            <td class="<?= $leftScore === null ? 'grade-empty' : '' ?>"><?= $leftScore === null ? '' : e((string) $leftScore) ?></td>
                                            <td class="<?= $rightRow === null ? 'grade-empty' : '' ?>"><?= $rightRow === null ? '' : e((string) ($rightRow['subject_name'] ?? '')) ?></td>
                                            <td class="<?= $rightScore === null ? 'grade-empty' : '' ?>"><?= $rightScore === null ? '' : e((string) $rightScore) ?></td>
                                        </tr>
                                    <?php endfor; ?>
                                    <?php
                                    $leftPercentText = $leftMax > 0
                                        ? rtrim(rtrim(number_format(($leftSum / $leftMax) * 100, 1, '.', ''), '0'), '.')
                                        : '';
                                    $rightPercentText = $rightMax > 0
                                        ? rtrim(rtrim(number_format(($rightSum / $rightMax) * 100, 1, '.', ''), '0'), '.')
                                        : '';
                                    $leftSummaryText = $leftMax > 0
                                        ? ('مجموع: ' . to_persian_number((string) $leftSum) . ' از ' . to_persian_number((string) $leftMax) . ' | فیصدی: ' . to_persian_number($leftPercentText) . '%')
                                        : 'مجموع: — | فیصدی: —';
                                    $rightSummaryText = $rightTerm !== null
                                        ? (
                                            $rightMax > 0
                                                ? ('مجموع: ' . to_persian_number((string) $rightSum) . ' از ' . to_persian_number((string) $rightMax) . ' | فیصدی: ' . to_persian_number($rightPercentText) . '%')
                                                : 'مجموع: — | فیصدی: —'
                                        )
                                        : '';
                                    ?>
                                    <tr class="grade-summary-row">
                                        <td colspan="2"><?= e($leftSummaryText) ?></td>
                                        <td colspan="2"><?= e($rightSummaryText) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($role === 'teacher' && $teacher): ?>
                    <h3><?= e((string) $teacher['name']) ?></h3>
                    <p><strong>نام پدر:</strong> <?= e((string) ($teacher['father_name'] ?? '—')) ?></p>
                    <p><strong>سویه تحصیلی:</strong> <?= e($educationMap[(string) ($teacher['education_level'] ?? '')] ?? '—') ?></p>
                    <p><strong>آدرس فعلی:</strong> <?= e((string) ($teacher['current_address'] ?? '—')) ?></p>
                    <p><strong>آدرس اصلی:</strong> <?= e((string) ($teacher['permanent_address'] ?? '—')) ?></p>

                    <hr>
                    <h4>صنوف اختصاص‌داده‌شده</h4>
                    <?php if (($teacherAssignments['classes'] ?? []) === []): ?>
                        <p class="field-help">صنفی برای شما تخصیص نشده است.</p>
                    <?php else: ?>
                        <div class="inline-checks">
                            <?php foreach ($teacherAssignments['classes'] as $class): ?>
                                <label><?= e((string) $class['name']) ?></label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <h4 style="margin-top:16px;">مضامین اختصاص‌داده‌شده</h4>
                    <?php if (($teacherAssignments['subjects'] ?? []) === []): ?>
                        <p class="field-help">مضمونی برای شما تخصیص نشده است.</p>
                    <?php else: ?>
                        <div class="inline-checks">
                            <?php foreach ($teacherAssignments['subjects'] as $subject): ?>
                                <label><?= e((string) $subject['name']) ?></label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div style="margin-top: 18px;">
                        <a class="section-btn btn btn-default" href="<?= e(url('/grades')) ?>">ثبت/ویرایش نمرات صنوف من</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="news-thumb auth-card">
            <div class="news-info">
                <h4>تغییر ایمیل و رمز عبور</h4>
                <p class="auth-note">تنها همین بخش قابل ویرایش است.</p>

                <form method="post" action="<?= e(url('/account/security')) ?>" class="auth-form">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label>ایمیل</label>
                        <input type="email" name="email" class="form-control" required value="<?= e($emailValue) ?>" placeholder="example@domain.com">
                    </div>

                    <div class="form-group">
                        <label>رمز عبور جدید</label>
                        <input type="password" name="password" class="form-control" placeholder="اگر تغییر نمی‌دهید خالی بگذارید" minlength="8">
                    </div>

                    <div class="form-group">
                        <label>تکرار رمز عبور جدید</label>
                        <input type="password" name="password_confirmation" class="form-control" placeholder="تکرار رمز عبور">
                    </div>

                    <div class="auth-actions">
                        <button type="submit" class="section-btn btn btn-default">ذخیره تغییرات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
