<div class="section-title">
    <h2>نتایج امتحان: <?= e($student['name']) ?></h2>
</div>

<div class="news-thumb">
    <div class="news-info">
        <p><strong>سطح:</strong> <?= e($student['level_name'] ?? '—') ?></p>
        <?php if (($studentGradeRows ?? []) === []): ?>
            <p class="field-help">برای این شاگرد هنوز مضمون/نمره‌ای ثبت نشده است.</p>
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

        <a class="btn btn-default" href="<?= e(url('/students')) ?>">بازگشت</a>
    </div>
</div>
