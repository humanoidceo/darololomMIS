<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

final class GradesController extends Controller
{
    public function index(array $params = []): void
    {
        $user = $this->requireAuth();
        $role = (string) ($user['role'] ?? '');
        clear_old();

        if ($role === 'teacher') {
            $teacherId = (int) ($user['teacher_id'] ?? 0);
            if ($teacherId <= 0) {
                flash('error', 'حساب استاد به پروفایل استاد متصل نیست.');
                $this->redirect('/account');
            }

            $assignment = $this->teacherAssignmentData($teacherId);
            $subjectRosterMap = $this->teacherSubjectRosterMap($assignment['subjects']);
            $filterOptions = $this->teacherSubjectFilterOptions($assignment['subjects'], $subjectRosterMap);
            $teacherFilters = $this->normalizeTeacherSubjectFilters($filterOptions);

            $filteredSubjects = [];
            foreach ($assignment['subjects'] as $subject) {
                if (!$this->matchesTeacherSubjectFilters($subject, $teacherFilters)) {
                    continue;
                }

                $subjectId = (int) ($subject['id'] ?? 0);
                $filteredStudents = $this->filterTeacherRosterStudents($subjectRosterMap[$subjectId] ?? [], $teacherFilters);
                $subject['student_count'] = count($filteredStudents);

                if (($teacherFilters['year'] > 0 || $teacherFilters['class_id'] > 0) && $subject['student_count'] === 0) {
                    continue;
                }

                $filteredSubjects[] = $subject;
            }

            $perPage = 10;
            $totalFilteredSubjects = count($filteredSubjects);
            $totalPages = max(1, (int) ceil($totalFilteredSubjects / $perPage));
            $currentPage = min($teacherFilters['page'], $totalPages);
            $offset = ($currentPage - 1) * $perPage;
            $subjectCards = array_slice($filteredSubjects, $offset, $perPage);
            $selectedSubject = null;
            $subjectStudents = [];
            $subjectScoreMap = [];

            $requestedSubjectId = (int) ($_GET['subject_id'] ?? 0);
            if ($requestedSubjectId > 0) {
                $selectedSubject = $this->teacherAssignedSubject($filteredSubjects, $requestedSubjectId);
            }

            if ($selectedSubject) {
                $subjectStudents = $this->filterTeacherRosterStudents(
                    $subjectRosterMap[(int) ($selectedSubject['id'] ?? 0)] ?? [],
                    $teacherFilters
                );

                if ($subjectStudents !== []) {
                    $studentIds = array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $subjectStudents);
                    $studentIds = array_values(array_filter($studentIds, static fn (int $id): bool => $id > 0));

                    if ($studentIds !== []) {
                        $db = Database::connection();
                        $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
                        $scoresStmt = $db->prepare(
                            "SELECT student_id, score
                             FROM student_scores
                             WHERE subject_id = ?
                               AND student_id IN ($placeholders)"
                        );
                        $scoresStmt->execute(array_merge([(int) $selectedSubject['id']], $studentIds));
                        foreach ($scoresStmt->fetchAll() as $row) {
                            $subjectScoreMap[(int) ($row['student_id'] ?? 0)] = $row['score'];
                        }
                    }
                }
            }

            $this->render('grades/index', [
                'title' => 'ثبت نمرات مضامین من',
                'mode' => 'teacher',
                'students' => [],
                'selectedStudent' => null,
                'subjects' => [],
                'scoreMap' => [],
                'assignment' => $assignment,
                'teacherFilters' => $teacherFilters,
                'teacherFilterOptions' => $filterOptions,
                'subjectCards' => $subjectCards,
                'subjectCardTotal' => $totalFilteredSubjects,
                'subjectPagination' => [
                    'per_page' => $perPage,
                    'current_page' => $currentPage,
                    'total_pages' => $totalPages,
                    'total_items' => $totalFilteredSubjects,
                    'from' => $totalFilteredSubjects > 0 ? ($offset + 1) : 0,
                    'to' => min($offset + $perPage, $totalFilteredSubjects),
                ],
                'selectedSubject' => $selectedSubject,
                'subjectStudents' => $subjectStudents,
                'subjectScoreMap' => $subjectScoreMap,
            ]);
            return;
        }

        $this->authorize('manage_grades', 'شما اجازه مدیریت نمرات را ندارید.');

        $db = Database::connection();
        $students = $db->query('SELECT id, name FROM students ORDER BY name')->fetchAll();
        $studentId = (int) ($_GET['student_id'] ?? ($students[0]['id'] ?? 0));

        $selectedStudent = null;
        $subjects = [];
        $scoreMap = [];

        if ($studentId > 0) {
            $selectedStudent = $this->studentProfile($studentId);
            if ($selectedStudent) {
                $subjects = $this->availableSubjectsForStudent($selectedStudent);

                $scoresStmt = $db->prepare('SELECT subject_id, score FROM student_scores WHERE student_id = :student_id');
                $scoresStmt->execute(['student_id' => $studentId]);
                foreach ($scoresStmt->fetchAll() as $row) {
                    $scoreMap[(int) $row['subject_id']] = $row['score'];
                }
            }
        }

        $this->render('grades/index', [
            'title' => 'ثبت نمرات',
            'mode' => 'admin',
            'students' => $students,
            'selectedStudent' => $selectedStudent,
            'subjects' => $subjects,
            'scoreMap' => $scoreMap,
            'assignment' => null,
        ]);
    }

    public function store(array $params = []): void
    {
        $user = $this->requireAuth();
        $role = (string) ($user['role'] ?? '');

        if ($role !== 'teacher') {
            $this->authorize('manage_grades', 'شما اجازه ثبت نمرات را ندارید.', '/');
        }
        $this->csrfCheck();

        if ($role === 'teacher' && array_key_exists('subject_id', $_POST) && is_array($_POST['student_scores'] ?? null)) {
            $this->storeTeacherSubjectScores($user);
            return;
        }

        $db = Database::connection();

        $studentId = (int) ($_POST['student_id'] ?? 0);
        if ($studentId <= 0) {
            flash('error', 'دانش‌آموز انتخاب نشده است.');
            $this->redirect('/grades');
        }

        $scores = $_POST['scores'] ?? [];
        if (!is_array($scores)) {
            $scores = [];
        }
        $hasChangedSubjectIdsField = array_key_exists('changed_subject_ids', $_POST);
        $changedSubjectIds = $this->parseChangedSubjectIds((string) ($_POST['changed_subject_ids'] ?? ''));
        $overrideScores = $_POST['override_scores'] ?? [];
        if (!is_array($overrideScores)) {
            $overrideScores = [];
        }
        $overrideReasons = $_POST['override_reasons'] ?? [];
        if (!is_array($overrideReasons)) {
            $overrideReasons = [];
        }

        $student = $this->studentProfile($studentId);
        if (!$student) {
            flash('error', 'دانش‌آموز انتخاب‌شده معتبر نیست.');
            $this->redirect('/grades');
        }

        $currentSubjects = $this->availableSubjectsForStudent($student);
        $allowedSubjects = $currentSubjects;
        $allModalSubjects = $this->allModalSubjectsForStudent($student);
        $recordedByTeacherId = null;
        $assignment = null;

        if ($role === 'teacher') {
            $teacherId = (int) ($user['teacher_id'] ?? 0);
            if ($teacherId <= 0) {
                flash('error', 'حساب استاد به پروفایل استاد متصل نیست.');
                $this->redirect('/account');
            }

            $assignment = $this->teacherAssignmentData($teacherId);
            $allowedSubjects = $this->filterSubjectsByAllowedIds($allowedSubjects, $assignment['subject_ids']);
            $allModalSubjects = $this->filterSubjectsByAllowedIds($allModalSubjects, $assignment['subject_ids']);
            $recordedByTeacherId = $teacherId;
        }

        $allowedSubjectIds = array_map(static fn (array $row): int => (int) $row['id'], $allowedSubjects);
        $allowedSubjectMap = [];
        foreach ($allowedSubjectIds as $allowedSubjectId) {
            $allowedSubjectMap[(int) $allowedSubjectId] = true;
        }

        $allModalSubjectNameMap = [];
        foreach ($allModalSubjects as $modalSubject) {
            $modalSubjectId = (int) ($modalSubject['id'] ?? 0);
            if ($modalSubjectId > 0) {
                $allModalSubjectNameMap[$modalSubjectId] = (string) ($modalSubject['name'] ?? '');
            }
        }

        $overrideUpdates = [];
        foreach ($overrideScores as $subjectIdRaw => $scoreRaw) {
            $subjectId = (int) $subjectIdRaw;
            $scoreText = trim((string) $scoreRaw);
            if ($subjectId <= 0 || $scoreText === '') {
                continue;
            }

            if (!isset($allModalSubjectNameMap[$subjectId])) {
                continue;
            }
            if (isset($allowedSubjectMap[$subjectId])) {
                continue;
            }

            $reason = trim((string) ($overrideReasons[$subjectIdRaw] ?? $overrideReasons[$subjectId] ?? ''));
            if ($reason === '' || mb_strlen($reason) < 3) {
                flash('error', 'برای تغییر نمره مضمون قفل‌شده، نوشتن دلیل حداقل ۳ حرف الزامی است.');
                $returnTo = trim((string) ($_POST['return_to'] ?? ''));
                if ($this->isSafeReturnPath($returnTo)) {
                    $this->redirect($returnTo);
                }
                $this->redirect('/grades?student_id=' . $studentId);
            }

            $overrideUpdates[] = [
                'subject_id' => $subjectId,
                'subject_name' => $allModalSubjectNameMap[$subjectId],
                'score' => max(0, min(100, (int) $scoreText)),
                'reason' => $reason,
            ];
        }

        $overrideSubjectIds = array_values(array_unique(array_map(
            static fn (array $item): int => (int) ($item['subject_id'] ?? 0),
            $overrideUpdates
        )));
        $previousOverrideScoreMap = $this->scoreMapForStudentSubjectIds($studentId, $overrideSubjectIds);

        $stmt = $db->prepare('INSERT INTO student_scores (student_id, subject_id, recorded_by_teacher_id, score, created_at, updated_at)
            VALUES (:student_id, :subject_id, :recorded_by_teacher_id, :score, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
            recorded_by_teacher_id = VALUES(recorded_by_teacher_id),
            score = VALUES(score),
            updated_at = NOW()');

        $overrideAppliedCount = 0;
        try {
            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }

            foreach ($scores as $subjectId => $score) {
                $subjectId = (int) $subjectId;
                if ($subjectId <= 0 || $score === '' || !in_array($subjectId, $allowedSubjectIds, true)) {
                    continue;
                }
                if ($hasChangedSubjectIdsField && !in_array($subjectId, $changedSubjectIds, true)) {
                    continue;
                }

                $scoreValue = max(0, min(100, (int) $score));
                $stmt->execute([
                    'student_id' => $studentId,
                    'subject_id' => $subjectId,
                    'recorded_by_teacher_id' => $recordedByTeacherId,
                    'score' => $scoreValue,
                ]);
            }

            foreach ($overrideUpdates as $overrideUpdate) {
                $subjectId = (int) ($overrideUpdate['subject_id'] ?? 0);
                if ($subjectId <= 0) {
                    continue;
                }

                $newScore = (int) ($overrideUpdate['score'] ?? 0);
                $stmt->execute([
                    'student_id' => $studentId,
                    'subject_id' => $subjectId,
                    'recorded_by_teacher_id' => $recordedByTeacherId,
                    'score' => $newScore,
                ]);
                $overrideAppliedCount += 1;

                $this->appendLockedScoreChangeLog([
                    'changed_at' => date('c'),
                    'actor_user_id' => (int) ($user['id'] ?? 0),
                    'actor_role' => (string) ($user['role'] ?? ''),
                    'actor_teacher_id' => (int) ($user['teacher_id'] ?? 0),
                    'student_id' => $studentId,
                    'subject_id' => $subjectId,
                    'subject_name' => (string) ($overrideUpdate['subject_name'] ?? ''),
                    'old_score' => $previousOverrideScoreMap[$subjectId] ?? null,
                    'new_score' => $newScore,
                    'reason' => (string) ($overrideUpdate['reason'] ?? ''),
                ]);
            }

            if ($db->inTransaction()) {
                $db->commit();
            }
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            flash('error', 'ذخیره نمرات ناموفق بود. لطفاً دوباره تلاش کنید.');
            $returnTo = trim((string) ($_POST['return_to'] ?? ''));
            if ($this->isSafeReturnPath($returnTo)) {
                $this->redirect($returnTo);
            }
            $this->redirect('/grades?student_id=' . $studentId);
        }

        $promoted = $this->promoteStudentIfEligible($student, $currentSubjects);
        $successMessage = 'نمرات با موفقیت ذخیره شد.';
        if ($overrideAppliedCount > 0) {
            $successMessage .= ' ' . (string) $overrideAppliedCount . ' نمرهٔ قفل‌شده با دلیل تغییر به‌روزرسانی شد.';
        }
        if ($promoted) {
            $successMessage .= ' شاگرد به مرحله بعد ارتقا یافت.';
        }
        flash('success', $successMessage);
        $returnTo = trim((string) ($_POST['return_to'] ?? ''));
        if ($this->isSafeReturnPath($returnTo)) {
            $this->redirect($returnTo);
        }

        $this->redirect('/grades?student_id=' . $studentId);
    }

    public function studentModalData(array $params = []): void
    {
        $user = $this->requireAuth();
        $role = (string) ($user['role'] ?? '');
        $studentId = $this->intParam($params, 'id');

        if ($studentId <= 0) {
            $this->jsonResponse([
                'ok' => false,
                'message' => 'دانش‌آموز انتخاب‌شده معتبر نیست.',
            ], 422);
        }

        $student = $this->studentProfile($studentId);
        if (!$student) {
            $this->jsonResponse([
                'ok' => false,
                'message' => 'دانش‌آموز پیدا نشد.',
            ], 404);
        }

        $editableSubjects = $this->availableSubjectsForStudent($student);
        $subjects = $this->allModalSubjectsForStudent($student);

        if ($role === 'teacher') {
            $teacherId = (int) ($user['teacher_id'] ?? 0);
            if ($teacherId <= 0) {
                $this->jsonResponse([
                    'ok' => false,
                    'message' => 'حساب استاد به پروفایل استاد متصل نیست.',
                ], 403);
            }

            $assignment = $this->teacherAssignmentData($teacherId);
            $editableSubjects = $this->filterSubjectsByAllowedIds($editableSubjects, $assignment['subject_ids']);
            $subjects = $this->filterSubjectsByAllowedIds($subjects, $assignment['subject_ids']);
        } elseif (!is_super_admin() && !can('manage_grades')) {
            $this->jsonResponse([
                'ok' => false,
                'message' => 'شما اجازه مدیریت نمرات را ندارید.',
            ], 403);
        }

        $editableMap = [];
        foreach ($editableSubjects as $subject) {
            $subjectId = (int) ($subject['id'] ?? 0);
            if ($subjectId > 0) {
                $editableMap[$subjectId] = true;
            }
        }

        $scoreMap = [];
        if ($subjects !== []) {
            $db = Database::connection();
            $subjectIds = array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $subjects);
            $subjectIds = array_values(array_filter($subjectIds, static fn (int $id): bool => $id > 0));

            if ($subjectIds !== []) {
                $placeholders = implode(',', array_fill(0, count($subjectIds), '?'));
                $scoresStmt = $db->prepare(
                    "SELECT subject_id, score
                     FROM student_scores
                     WHERE student_id = ?
                     AND subject_id IN ($placeholders)"
                );
                $scoresStmt->execute(array_merge([$studentId], $subjectIds));

                foreach ($scoresStmt->fetchAll() as $row) {
                    $scoreMap[(int) $row['subject_id']] = (int) $row['score'];
                }
            }
        }

        $subjectItems = [];
        foreach ($subjects as $subject) {
            $subjectKey = (int) ($subject['id'] ?? 0);
            if ($subjectKey <= 0) {
                continue;
            }

            $subjectItems[] = [
                'id' => $subjectKey,
                'name' => (string) ($subject['name'] ?? ''),
                'term_label' => (string) ($subject['term_label'] ?? '—'),
                'term_order' => (int) ($subject['term_order'] ?? 0),
                'editable' => isset($editableMap[$subjectKey]),
                'score' => $scoreMap[$subjectKey] ?? null,
            ];
        }

        $this->jsonResponse([
            'ok' => true,
            'student' => [
                'id' => (int) $student['id'],
                'name' => (string) ($student['name'] ?? ''),
            ],
            'subjects' => $subjectItems,
        ]);
    }

    private function teacherAssignmentData(int $teacherId): array
    {
        $db = Database::connection();

        $subjectsStmt = $db->prepare(
            'SELECT s.id, s.name, s.level_id, s.semester, s.period_id,
                    l.name AS level_name, l.code AS level_code, cp.number AS period_number
             FROM teacher_subject ts
             JOIN subjects s ON s.id = ts.subject_id
             LEFT JOIN study_levels l ON l.id = s.level_id
             LEFT JOIN course_periods cp ON cp.id = s.period_id
             WHERE ts.teacher_id = :teacher_id
             ORDER BY s.name'
        );
        $subjectsStmt->execute(['teacher_id' => $teacherId]);
        $subjects = $subjectsStmt->fetchAll();

        foreach ($subjects as &$subject) {
            $subject['term_label'] = $this->subjectTermLabel($subject);
        }
        unset($subject);

        return [
            'subjects' => $subjects,
            'subject_ids' => array_map(static fn (array $row): int => (int) $row['id'], $subjects),
        ];
    }

    private function teacherSubjectRosterMap(array $subjects): array
    {
        $map = [];
        foreach ($subjects as $subject) {
            $subjectId = (int) ($subject['id'] ?? 0);
            if ($subjectId <= 0) {
                continue;
            }

            $map[$subjectId] = $this->studentsForTeacherSubject($subject);
        }

        return $map;
    }

    private function teacherSubjectFilterOptions(array $subjects, array $subjectRosterMap): array
    {
        $levels = [];
        foreach ($subjects as $subject) {
            $levelCode = (string) ($subject['level_code'] ?? '');
            $levelName = (string) ($subject['level_name'] ?? '');
            if ($levelCode !== '' && $levelName !== '') {
                $levels[$levelCode] = $levelName;
            }
        }

        $orderedLevels = [];
        foreach (['aali', 'moteseta', 'ebtedai'] as $code) {
            if (isset($levels[$code])) {
                $orderedLevels[] = [
                    'code' => $code,
                    'name' => $levels[$code],
                ];
            }
        }

        $years = [];
        $classes = [];
        foreach ($subjectRosterMap as $students) {
            foreach ($students as $student) {
                $year = (int) ($student['enrollment_year'] ?? 0);
                if ($year > 0) {
                    $years[$year] = $year;
                }

                $classId = (int) ($student['school_class_id'] ?? 0);
                $className = trim((string) ($student['class_name'] ?? ''));
                if ($classId > 0 && $className !== '') {
                    $classes[$classId] = [
                        'id' => $classId,
                        'name' => $className,
                    ];
                }
            }
        }

        rsort($years, SORT_NUMERIC);
        uasort($classes, static fn (array $a, array $b): int => strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));

        return [
            'levels' => $orderedLevels,
            'years' => array_values($years),
            'classes' => array_values($classes),
        ];
    }

    private function normalizeTeacherSubjectFilters(array $filterOptions): array
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        if (mb_strlen($q) > 100) {
            $q = mb_substr($q, 0, 100);
        }

        $validLevelCodes = array_map(
            static fn (array $level): string => (string) ($level['code'] ?? ''),
            $filterOptions['levels'] ?? []
        );
        $level = trim((string) ($_GET['level'] ?? ''));
        if (!in_array($level, $validLevelCodes, true)) {
            $level = '';
        }

        $validYears = array_map('intval', $filterOptions['years'] ?? []);
        $year = (int) ($_GET['year'] ?? 0);
        if (!in_array($year, $validYears, true)) {
            $year = 0;
        }

        $validClassIds = array_map(
            static fn (array $class): int => (int) ($class['id'] ?? 0),
            $filterOptions['classes'] ?? []
        );
        $classId = (int) ($_GET['class_id'] ?? 0);
        if (!in_array($classId, $validClassIds, true)) {
            $classId = 0;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));

        return [
            'q' => $q,
            'level' => $level,
            'year' => $year,
            'class_id' => $classId,
            'page' => $page,
        ];
    }

    private function matchesTeacherSubjectFilters(array $subject, array $filters): bool
    {
        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $haystacks = [
                mb_strtolower((string) ($subject['name'] ?? '')),
                mb_strtolower((string) ($subject['level_name'] ?? '')),
                mb_strtolower((string) ($subject['term_label'] ?? '')),
            ];
            $needle = mb_strtolower($query);
            $matched = false;
            foreach ($haystacks as $haystack) {
                if ($haystack !== '' && mb_strpos($haystack, $needle) !== false) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                return false;
            }
        }

        $level = (string) ($filters['level'] ?? '');
        if ($level !== '' && (string) ($subject['level_code'] ?? '') !== $level) {
            return false;
        }

        return true;
    }

    private function filterTeacherRosterStudents(array $students, array $filters): array
    {
        $year = (int) ($filters['year'] ?? 0);
        $classId = (int) ($filters['class_id'] ?? 0);

        if ($year <= 0 && $classId <= 0) {
            return $students;
        }

        $filtered = [];
        foreach ($students as $student) {
            if ($year > 0 && (int) ($student['enrollment_year'] ?? 0) !== $year) {
                continue;
            }
            if ($classId > 0 && (int) ($student['school_class_id'] ?? 0) !== $classId) {
                continue;
            }

            $filtered[] = $student;
        }

        return $filtered;
    }

    private function filterSubjectsByAllowedIds(array $subjects, array $allowedIds): array
    {
        if ($subjects === [] || $allowedIds === []) {
            return [];
        }

        $allowedMap = [];
        foreach ($allowedIds as $id) {
            $allowedMap[(int) $id] = true;
        }

        $filtered = [];
        foreach ($subjects as $subject) {
            $subjectId = (int) ($subject['id'] ?? 0);
            if ($subjectId > 0 && isset($allowedMap[$subjectId])) {
                $filtered[] = $subject;
            }
        }

        return $filtered;
    }

    private function teacherAssignedSubject(array $subjects, int $subjectId): ?array
    {
        foreach ($subjects as $subject) {
            if ((int) ($subject['id'] ?? 0) === $subjectId) {
                return $subject;
            }
        }

        return null;
    }

    private function subjectTermLabel(array $subject): string
    {
        $levelCode = (string) ($subject['level_code'] ?? '');
        if ($levelCode === 'aali') {
            $termOrder = $this->normalizeAaliTermOrder((int) ($subject['semester'] ?? 0));
            return $termOrder > 0 ? ('سمستر ' . (string) $termOrder) : 'سمستر —';
        }

        $periodNumber = (int) ($subject['period_number'] ?? 0);
        return $periodNumber > 0 ? ('دوره ' . (string) $periodNumber) : 'دوره —';
    }

    private function studentsForTeacherSubject(array $subject): array
    {
        $subjectId = (int) ($subject['id'] ?? 0);
        $levelId = (int) ($subject['level_id'] ?? 0);
        if ($subjectId <= 0 || $levelId <= 0) {
            return [];
        }

        $db = Database::connection();
        $sql = 'SELECT s.id, s.name, s.school_class_id, sc.name AS class_name, s.level_id, l.code AS level_code, s.enrollment_year
            FROM students s
            LEFT JOIN school_classes sc ON sc.id = s.school_class_id
            LEFT JOIN study_levels l ON l.id = s.level_id
            WHERE s.level_id = ?';
        $params = [$levelId];

        $sql .= ' ORDER BY s.name';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $candidates = $stmt->fetchAll();

        $students = [];
        foreach ($candidates as $candidate) {
            $availableSubjects = $this->availableSubjectsForStudent($candidate);
            foreach ($availableSubjects as $availableSubject) {
                if ((int) ($availableSubject['id'] ?? 0) === $subjectId) {
                    $students[] = $candidate;
                    break;
                }
            }
        }

        return $students;
    }

    private function allModalSubjectsForStudent(array $student): array
    {
        $levelId = (int) ($student['level_id'] ?? 0);
        if ($levelId <= 0) {
            return [];
        }

        $db = Database::connection();
        $rows = [];

        if (($student['level_code'] ?? '') === 'aali') {
            $stmt = $db->prepare(
                'SELECT id, name, semester
                 FROM subjects
                 WHERE level_id = :level_id
                   AND semester IS NOT NULL
                   AND semester > 0
                 ORDER BY semester, name'
            );
            $stmt->execute(['level_id' => $levelId]);
            $rows = $stmt->fetchAll();

            foreach ($rows as &$row) {
                $semester = (int) ($row['semester'] ?? 0);
                $termOrder = $this->normalizeAaliTermOrder($semester);
                $row['term_order'] = $termOrder;
                $row['term_label'] = $termOrder > 0
                    ? ('سمستر ' . (string) $termOrder)
                    : ('سمستر ' . (string) $semester);
            }
            unset($row);
        } else {
            $stmt = $db->prepare(
                'SELECT s.id, s.name, cp.number AS period_number
                 FROM subjects s
                 JOIN course_periods cp ON cp.id = s.period_id
                 WHERE s.level_id = :level_id
                 ORDER BY cp.number, s.name'
            );
            $stmt->execute(['level_id' => $levelId]);
            $rows = $stmt->fetchAll();

            foreach ($rows as &$row) {
                $periodNumber = (int) ($row['period_number'] ?? 0);
                $row['term_order'] = $periodNumber;
                $row['term_label'] = 'دوره ' . (string) ($periodNumber > 0 ? $periodNumber : '—');
            }
            unset($row);
        }

        $rows = $this->uniqueSubjectsById($rows);
        usort($rows, static function (array $a, array $b): int {
            $orderCompare = ((int) ($a['term_order'] ?? 0)) <=> ((int) ($b['term_order'] ?? 0));
            if ($orderCompare !== 0) {
                return $orderCompare;
            }
            return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        return $rows;
    }

    private function studentProfile(int $studentId): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT s.*, l.code AS level_code
            FROM students s
            LEFT JOIN study_levels l ON l.id = s.level_id
            WHERE s.id = :id
            LIMIT 1');
        $stmt->execute(['id' => $studentId]);
        $student = $stmt->fetch();

        return $student ?: null;
    }

    private function availableSubjectsForStudent(array $student): array
    {
        $db = Database::connection();
        $levelId = (int) ($student['level_id'] ?? 0);
        if ($levelId <= 0) {
            return [];
        }

        $classId = (int) ($student['school_class_id'] ?? 0);
        $classSemesterNumber = 0;
        $classPeriodId = 0;
        if ($classId > 0) {
            $classStmt = $db->prepare(
                'SELECT sc.period_id, se.number AS semester_number
                 FROM school_classes sc
                 LEFT JOIN semesters se ON se.id = sc.semester_id
                 WHERE sc.id = :class_id
                 LIMIT 1'
            );
            $classStmt->execute(['class_id' => $classId]);
            $classRow = $classStmt->fetch();
            if ($classRow) {
                $classSemesterNumber = (int) ($classRow['semester_number'] ?? 0);
                $classPeriodId = (int) ($classRow['period_id'] ?? 0);
            }
        }

        if (($student['level_code'] ?? '') === 'aali') {
            $semesterNumber = 0;
            $semesterStmt = $db->prepare(
                'SELECT se.number
                 FROM student_semester ss
                 JOIN semesters se ON se.id = ss.semester_id
                 WHERE ss.student_id = :id
                 ORDER BY se.number DESC
                 LIMIT 1'
            );
            $semesterStmt->execute(['id' => $student['id']]);
            $semesterNumber = (int) ($semesterStmt->fetchColumn() ?: 0);
            if ($semesterNumber <= 0) {
                $semesterNumber = $classSemesterNumber;
            }

            $subjectSemesters = $this->subjectSemestersForAaliClass($semesterNumber);
            if ($subjectSemesters === []) {
                return [];
            }

            $placeholders = implode(',', array_fill(0, count($subjectSemesters), '?'));
            $stmt = $db->prepare("SELECT id, name FROM subjects WHERE level_id = ? AND semester IN ($placeholders) ORDER BY semester, name");
            $stmt->execute(array_merge([$levelId], $subjectSemesters));
            return $this->uniqueSubjectsById($stmt->fetchAll());
        }

        $periodId = 0;
        $periodStmt = $db->prepare(
            'SELECT sp.period_id
             FROM student_period sp
             JOIN course_periods cp ON cp.id = sp.period_id
             WHERE sp.student_id = :id
             ORDER BY cp.number DESC
             LIMIT 1'
        );
        $periodStmt->execute(['id' => $student['id']]);
        $periodId = (int) ($periodStmt->fetchColumn() ?: 0);
        if ($periodId <= 0) {
            $periodId = $classPeriodId;
        }

        if ($periodId <= 0) {
            return [];
        }

        $stmt = $db->prepare('SELECT id, name FROM subjects WHERE level_id = :level_id AND period_id = :period_id ORDER BY name');
        $stmt->execute([
            'level_id' => $levelId,
            'period_id' => $periodId,
        ]);

        return $this->uniqueSubjectsById($stmt->fetchAll());
    }

    /**
     * سمسترهای مضامین سطح عالی را بر اساس صنف شاگرد برمی‌گرداند.
     * 1 و 2 مربوط صنف 13، و 3 و 4 مربوط صنف 14 است.
     * مقادیر 13 و 14 برای سازگاری داده‌های قبلی نیز لحاظ شده‌اند.
     *
     * @return array<int>
     */
    private function subjectSemestersForAaliClass(int $studentSemesterNumber): array
    {
        if ($studentSemesterNumber === 13) {
            return [1, 2, 13];
        }
        if ($studentSemesterNumber === 14) {
            return [3, 4, 14];
        }
        if (in_array($studentSemesterNumber, [1, 2, 3, 4], true)) {
            return [$studentSemesterNumber];
        }

        return [];
    }

    private function promoteStudentIfEligible(array $student, array $currentSubjects): bool
    {
        $studentId = (int) ($student['id'] ?? 0);
        $levelId = (int) ($student['level_id'] ?? 0);
        if ($studentId <= 0 || $levelId <= 0 || $currentSubjects === []) {
            return false;
        }

        $subjectIds = array_values(array_unique(array_filter(
            array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $currentSubjects),
            static fn (int $id): bool => $id > 0
        )));
        if ($subjectIds === []) {
            return false;
        }

        $db = Database::connection();
        $placeholders = implode(',', array_fill(0, count($subjectIds), '?'));
        $scoresStmt = $db->prepare(
            "SELECT subject_id, score
             FROM student_scores
             WHERE student_id = ?
               AND subject_id IN ($placeholders)"
        );
        $scoresStmt->execute(array_merge([$studentId], $subjectIds));
        $scoreMap = [];
        foreach ($scoresStmt->fetchAll() as $scoreRow) {
            $scoreMap[(int) ($scoreRow['subject_id'] ?? 0)] = (int) ($scoreRow['score'] ?? -1);
        }

        foreach ($subjectIds as $subjectId) {
            if (!isset($scoreMap[$subjectId]) || $scoreMap[$subjectId] < 50) {
                return false;
            }
        }

        if (($student['level_code'] ?? '') === 'aali') {
            $currentSemesterNumber = $this->currentAaliSemesterNumber($student);
            $nextSemesterNumber = $this->nextAaliSemesterNumber($currentSemesterNumber);
            if ($nextSemesterNumber <= 0) {
                return false;
            }

            $semesterStmt = $db->prepare('SELECT id FROM semesters WHERE number = :number LIMIT 1');
            $semesterStmt->execute(['number' => $nextSemesterNumber]);
            $nextSemesterId = (int) ($semesterStmt->fetchColumn() ?: 0);
            if ($nextSemesterId <= 0) {
                return false;
            }

            $db->prepare('DELETE FROM student_semester WHERE student_id = :student_id')
                ->execute(['student_id' => $studentId]);
            $db->prepare('INSERT INTO student_semester (student_id, semester_id) VALUES (:student_id, :semester_id)')
                ->execute([
                    'student_id' => $studentId,
                    'semester_id' => $nextSemesterId,
                ]);
            $db->prepare('DELETE FROM student_period WHERE student_id = :student_id')
                ->execute(['student_id' => $studentId]);

            $classStmt = $db->prepare(
                'SELECT id
                 FROM school_classes
                 WHERE level_id = :level_id AND semester_id = :semester_id
                 ORDER BY id ASC
                 LIMIT 1'
            );
            $classStmt->execute([
                'level_id' => $levelId,
                'semester_id' => $nextSemesterId,
            ]);
            $nextClassId = (int) ($classStmt->fetchColumn() ?: 0);
            if ($nextClassId > 0) {
                $db->prepare('UPDATE students SET school_class_id = :class_id WHERE id = :student_id')
                    ->execute([
                        'class_id' => $nextClassId,
                        'student_id' => $studentId,
                    ]);
            }

            return true;
        }

        $currentPeriodId = $this->currentPeriodId($student);
        if ($currentPeriodId <= 0) {
            return false;
        }

        $periodNumberStmt = $db->prepare('SELECT number FROM course_periods WHERE id = :id LIMIT 1');
        $periodNumberStmt->execute(['id' => $currentPeriodId]);
        $currentPeriodNumber = (int) ($periodNumberStmt->fetchColumn() ?: 0);
        if ($currentPeriodNumber <= 0) {
            return false;
        }

        $nextPeriodNumber = $currentPeriodNumber + 1;
        $nextPeriodStmt = $db->prepare('SELECT id FROM course_periods WHERE number = :number LIMIT 1');
        $nextPeriodStmt->execute(['number' => $nextPeriodNumber]);
        $nextPeriodId = (int) ($nextPeriodStmt->fetchColumn() ?: 0);
        if ($nextPeriodId <= 0) {
            return false;
        }

        $db->prepare('DELETE FROM student_period WHERE student_id = :student_id')
            ->execute(['student_id' => $studentId]);
        $db->prepare('INSERT INTO student_period (student_id, period_id) VALUES (:student_id, :period_id)')
            ->execute([
                'student_id' => $studentId,
                'period_id' => $nextPeriodId,
            ]);
        $db->prepare('DELETE FROM student_semester WHERE student_id = :student_id')
            ->execute(['student_id' => $studentId]);

        $classStmt = $db->prepare(
            'SELECT id
             FROM school_classes
             WHERE level_id = :level_id AND period_id = :period_id
             ORDER BY id ASC
             LIMIT 1'
        );
        $classStmt->execute([
            'level_id' => $levelId,
            'period_id' => $nextPeriodId,
        ]);
        $nextClassId = (int) ($classStmt->fetchColumn() ?: 0);
        if ($nextClassId > 0) {
            $db->prepare('UPDATE students SET school_class_id = :class_id WHERE id = :student_id')
                ->execute([
                    'class_id' => $nextClassId,
                    'student_id' => $studentId,
                ]);
        }

        return true;
    }

    private function currentAaliSemesterNumber(array $student): int
    {
        $studentId = (int) ($student['id'] ?? 0);
        if ($studentId <= 0) {
            return 0;
        }

        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT se.number
             FROM student_semester ss
             JOIN semesters se ON se.id = ss.semester_id
             WHERE ss.student_id = :student_id
             ORDER BY se.number DESC
             LIMIT 1'
        );
        $stmt->execute(['student_id' => $studentId]);
        $semesterNumber = (int) ($stmt->fetchColumn() ?: 0);
        if ($semesterNumber > 0) {
            return $semesterNumber;
        }

        $classId = (int) ($student['school_class_id'] ?? 0);
        if ($classId <= 0) {
            return 0;
        }

        $classStmt = $db->prepare(
            'SELECT se.number
             FROM school_classes sc
             LEFT JOIN semesters se ON se.id = sc.semester_id
             WHERE sc.id = :class_id
             LIMIT 1'
        );
        $classStmt->execute(['class_id' => $classId]);

        return (int) ($classStmt->fetchColumn() ?: 0);
    }

    private function currentPeriodId(array $student): int
    {
        $studentId = (int) ($student['id'] ?? 0);
        if ($studentId <= 0) {
            return 0;
        }

        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT sp.period_id
             FROM student_period sp
             JOIN course_periods cp ON cp.id = sp.period_id
             WHERE sp.student_id = :student_id
             ORDER BY cp.number DESC
             LIMIT 1'
        );
        $stmt->execute(['student_id' => $studentId]);
        $periodId = (int) ($stmt->fetchColumn() ?: 0);
        if ($periodId > 0) {
            return $periodId;
        }

        return (int) ($student['school_class_id'] ?? 0) > 0
            ? $this->periodIdFromClass((int) $student['school_class_id'])
            : 0;
    }

    private function periodIdFromClass(int $classId): int
    {
        if ($classId <= 0) {
            return 0;
        }

        $stmt = Database::connection()->prepare(
            'SELECT period_id
             FROM school_classes
             WHERE id = :class_id
             LIMIT 1'
        );
        $stmt->execute(['class_id' => $classId]);

        return (int) ($stmt->fetchColumn() ?: 0);
    }

    private function nextAaliSemesterNumber(int $currentSemesterNumber): int
    {
        if ($currentSemesterNumber === 13) {
            return 14;
        }
        if ($currentSemesterNumber === 14) {
            return 0;
        }
        if (in_array($currentSemesterNumber, [1, 2, 3], true)) {
            return $currentSemesterNumber + 1;
        }

        return 0;
    }

    private function normalizeAaliTermOrder(int $semester): int
    {
        if ($semester === 13) {
            return 1;
        }
        if ($semester === 14) {
            return 3;
        }
        if (in_array($semester, [1, 2, 3, 4], true)) {
            return $semester;
        }

        return 0;
    }

    /**
     * @param array<int, array<string, mixed>> $subjects
     * @return array<int, array<string, mixed>>
     */
    private function uniqueSubjectsById(array $subjects): array
    {
        $seen = [];
        $unique = [];
        foreach ($subjects as $subject) {
            $subjectId = (int) ($subject['id'] ?? 0);
            if ($subjectId <= 0 || isset($seen[$subjectId])) {
                continue;
            }
            $seen[$subjectId] = true;
            $unique[] = $subject;
        }

        return $unique;
    }

    /**
     * @return array<int>
     */
    private function parseChangedSubjectIds(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/\s*,\s*/', $raw) ?: [];
        $ids = [];
        foreach ($parts as $part) {
            $id = (int) $part;
            if ($id > 0) {
                $ids[$id] = true;
            }
        }

        return array_map('intval', array_keys($ids));
    }

    /**
     * @param array<int> $subjectIds
     * @return array<int, int|null>
     */
    private function scoreMapForStudentSubjectIds(int $studentId, array $subjectIds): array
    {
        if ($studentId <= 0 || $subjectIds === []) {
            return [];
        }

        $subjectIds = array_values(array_unique(array_filter(
            array_map('intval', $subjectIds),
            static fn (int $id): bool => $id > 0
        )));
        if ($subjectIds === []) {
            return [];
        }

        $db = Database::connection();
        $placeholders = implode(',', array_fill(0, count($subjectIds), '?'));
        $stmt = $db->prepare(
            "SELECT subject_id, score
             FROM student_scores
             WHERE student_id = ?
               AND subject_id IN ($placeholders)"
        );
        $stmt->execute(array_merge([$studentId], $subjectIds));

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $subjectId = (int) ($row['subject_id'] ?? 0);
            if ($subjectId > 0) {
                $map[$subjectId] = isset($row['score']) ? (int) $row['score'] : null;
            }
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function appendLockedScoreChangeLog(array $entry): void
    {
        $storageDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0775, true);
        }

        $payload = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            return;
        }

        @file_put_contents($storageDir . '/locked_score_changes.log', $payload . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function isSafeReturnPath(string $path): bool
    {
        if ($path === '' || $path[0] !== '/') {
            return false;
        }
        if (preg_match('/[\r\n]/', $path) === 1 || str_starts_with($path, '//')) {
            return false;
        }

        $parts = parse_url($path);
        if ($parts === false) {
            return false;
        }

        return !isset($parts['scheme']) && !isset($parts['host']);
    }

    private function jsonResponse(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function storeTeacherSubjectScores(array $user): void
    {
        $teacherId = (int) ($user['teacher_id'] ?? 0);
        if ($teacherId <= 0) {
            flash('error', 'حساب استاد به پروفایل استاد متصل نیست.');
            $this->redirect('/account');
        }

        $subjectId = (int) ($_POST['subject_id'] ?? 0);
        if ($subjectId <= 0) {
            flash('error', 'مضمون انتخاب نشده است.');
            $this->redirect('/grades');
        }

        $assignment = $this->teacherAssignmentData($teacherId);
        $selectedSubject = $this->teacherAssignedSubject($assignment['subjects'], $subjectId);
        if (!$selectedSubject) {
            flash('error', 'شما فقط می‌توانید نمرات مضامین اختصاص‌داده‌شده به خود را ثبت کنید.');
            $this->redirect('/grades');
        }

        $allowedStudents = $this->studentsForTeacherSubject($selectedSubject);
        if ($allowedStudents === []) {
            flash('error', 'برای این مضمون شاگرد قابل نمره‌دهی یافت نشد.');
            $this->redirect('/grades?subject_id=' . $subjectId);
        }

        $allowedStudentIds = [];
        foreach ($allowedStudents as $student) {
            $studentId = (int) ($student['id'] ?? 0);
            if ($studentId > 0) {
                $allowedStudentIds[$studentId] = true;
            }
        }

        $submittedScores = $_POST['student_scores'] ?? [];
        if (!is_array($submittedScores)) {
            $submittedScores = [];
        }

        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO student_scores (student_id, subject_id, recorded_by_teacher_id, score, created_at, updated_at)
             VALUES (:student_id, :subject_id, :recorded_by_teacher_id, :score, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
             recorded_by_teacher_id = VALUES(recorded_by_teacher_id),
             score = VALUES(score),
             updated_at = NOW()'
        );

        $savedCount = 0;
        $updatedStudentIds = [];

        try {
            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }

            foreach ($submittedScores as $studentIdRaw => $scoreRaw) {
                $studentId = (int) $studentIdRaw;
                if ($studentId <= 0 || !isset($allowedStudentIds[$studentId])) {
                    continue;
                }

                $scoreText = trim((string) $scoreRaw);
                if ($scoreText === '') {
                    continue;
                }

                $stmt->execute([
                    'student_id' => $studentId,
                    'subject_id' => $subjectId,
                    'recorded_by_teacher_id' => $teacherId,
                    'score' => max(0, min(100, (int) $scoreText)),
                ]);
                $savedCount++;
                $updatedStudentIds[$studentId] = true;
            }

            foreach (array_keys($updatedStudentIds) as $updatedStudentId) {
                $student = $this->studentProfile((int) $updatedStudentId);
                if (!$student) {
                    continue;
                }

                $currentSubjects = $this->availableSubjectsForStudent($student);
                $this->promoteStudentIfEligible($student, $currentSubjects);
            }

            if ($db->inTransaction()) {
                $db->commit();
            }
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            flash('error', 'ذخیره نمرات ناموفق بود. لطفاً دوباره تلاش کنید.');
            $this->redirect('/grades?subject_id=' . $subjectId);
        }

        if ($savedCount === 0) {
            flash('error', 'هیچ نمره‌ای برای ذخیره وارد نشد.');
            $this->redirect('/grades?subject_id=' . $subjectId);
        }

        flash('success', 'نمرات مضمون با موفقیت ذخیره شد.');
        $this->redirect('/grades?subject_id=' . $subjectId);
    }
}
