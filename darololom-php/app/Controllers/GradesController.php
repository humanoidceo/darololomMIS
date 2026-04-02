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

            $db = Database::connection();
            $assignment = $this->teacherAssignmentData($teacherId);
            $students = [];

            if ($assignment['class_ids'] !== []) {
                $placeholders = implode(',', array_fill(0, count($assignment['class_ids']), '?'));
                $studentsStmt = $db->prepare(
                    "SELECT s.id, s.name
                     FROM students s
                     WHERE s.school_class_id IN ($placeholders)
                     ORDER BY s.name"
                );
                $studentsStmt->execute($assignment['class_ids']);
                $students = $studentsStmt->fetchAll();
            }

            $allowedStudentIds = array_map(static fn (array $row): int => (int) $row['id'], $students);
            $requestedStudentId = (int) ($_GET['student_id'] ?? 0);
            $studentId = in_array($requestedStudentId, $allowedStudentIds, true)
                ? $requestedStudentId
                : (int) ($students[0]['id'] ?? 0);

            $selectedStudent = null;
            $subjects = [];
            $scoreMap = [];

            if ($studentId > 0) {
                $selectedStudent = $this->studentProfile($studentId);
                if ($selectedStudent && in_array((int) ($selectedStudent['school_class_id'] ?? 0), $assignment['class_ids'], true)) {
                    $available = $this->availableSubjectsForStudent($selectedStudent);
                    $subjects = $this->filterSubjectsByAllowedIds($available, $assignment['subject_ids']);

                    if ($subjects !== []) {
                        $subjectIds = array_map(static fn (array $row): int => (int) $row['id'], $subjects);
                        $scorePlaceholders = implode(',', array_fill(0, count($subjectIds), '?'));
                        $scoresStmt = $db->prepare(
                            "SELECT subject_id, score
                             FROM student_scores
                             WHERE student_id = ?
                             AND subject_id IN ($scorePlaceholders)"
                        );
                        $scoresStmt->execute(array_merge([$studentId], $subjectIds));
                        foreach ($scoresStmt->fetchAll() as $row) {
                            $scoreMap[(int) $row['subject_id']] = $row['score'];
                        }
                    }
                } else {
                    $selectedStudent = null;
                }
            }

            $this->render('grades/index', [
                'title' => 'ثبت نمرات صنوف من',
                'mode' => 'teacher',
                'students' => $students,
                'selectedStudent' => $selectedStudent,
                'subjects' => $subjects,
                'scoreMap' => $scoreMap,
                'assignment' => $assignment,
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

        $student = $this->studentProfile($studentId);
        if (!$student) {
            flash('error', 'دانش‌آموز انتخاب‌شده معتبر نیست.');
            $this->redirect('/grades');
        }

        $allowedSubjects = $this->availableSubjectsForStudent($student);
        $recordedByTeacherId = null;

        if ($role === 'teacher') {
            $teacherId = (int) ($user['teacher_id'] ?? 0);
            if ($teacherId <= 0) {
                flash('error', 'حساب استاد به پروفایل استاد متصل نیست.');
                $this->redirect('/account');
            }

            $assignment = $this->teacherAssignmentData($teacherId);
            $studentClassId = (int) ($student['school_class_id'] ?? 0);
            if (!in_array($studentClassId, $assignment['class_ids'], true)) {
                flash('error', 'شما اجازه ثبت نمره برای این شاگرد را ندارید.');
                $this->redirect('/grades');
            }

            $allowedSubjects = $this->filterSubjectsByAllowedIds($allowedSubjects, $assignment['subject_ids']);
            $recordedByTeacherId = $teacherId;
        }

        $allowedSubjectIds = array_map(static fn (array $row): int => (int) $row['id'], $allowedSubjects);

        $stmt = $db->prepare('INSERT INTO student_scores (student_id, subject_id, recorded_by_teacher_id, score, created_at, updated_at)
            VALUES (:student_id, :subject_id, :recorded_by_teacher_id, :score, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
            recorded_by_teacher_id = VALUES(recorded_by_teacher_id),
            score = VALUES(score),
            updated_at = NOW()');

        foreach ($scores as $subjectId => $score) {
            $subjectId = (int) $subjectId;
            if ($subjectId <= 0 || $score === '' || !in_array($subjectId, $allowedSubjectIds, true)) {
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

        flash('success', 'نمرات با موفقیت ذخیره شد.');
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

        $subjects = $this->availableSubjectsForStudent($student);

        if ($role === 'teacher') {
            $teacherId = (int) ($user['teacher_id'] ?? 0);
            if ($teacherId <= 0) {
                $this->jsonResponse([
                    'ok' => false,
                    'message' => 'حساب استاد به پروفایل استاد متصل نیست.',
                ], 403);
            }

            $assignment = $this->teacherAssignmentData($teacherId);
            $studentClassId = (int) ($student['school_class_id'] ?? 0);

            if (!in_array($studentClassId, $assignment['class_ids'], true)) {
                $this->jsonResponse([
                    'ok' => false,
                    'message' => 'شما اجازه ثبت نمره برای این شاگرد را ندارید.',
                ], 403);
            }

            $subjects = $this->filterSubjectsByAllowedIds($subjects, $assignment['subject_ids']);
        } elseif (!is_super_admin() && !can('manage_grades')) {
            $this->jsonResponse([
                'ok' => false,
                'message' => 'شما اجازه مدیریت نمرات را ندارید.',
            ], 403);
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

        $classesStmt = $db->prepare(
            'SELECT sc.id, sc.name
             FROM teacher_class tc
             JOIN school_classes sc ON sc.id = tc.class_id
             WHERE tc.teacher_id = :teacher_id
             ORDER BY sc.name'
        );
        $classesStmt->execute(['teacher_id' => $teacherId]);
        $classes = $classesStmt->fetchAll();

        $subjectsStmt = $db->prepare(
            'SELECT s.id, s.name
             FROM teacher_subject ts
             JOIN subjects s ON s.id = ts.subject_id
             WHERE ts.teacher_id = :teacher_id
             ORDER BY s.name'
        );
        $subjectsStmt->execute(['teacher_id' => $teacherId]);
        $subjects = $subjectsStmt->fetchAll();

        return [
            'classes' => $classes,
            'subjects' => $subjects,
            'class_ids' => array_map(static fn (array $row): int => (int) $row['id'], $classes),
            'subject_ids' => array_map(static fn (array $row): int => (int) $row['id'], $subjects),
        ];
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

        if (($student['level_code'] ?? '') === 'aali') {
            $semesterStmt = $db->prepare('SELECT semester_id FROM student_semester WHERE student_id = :id ORDER BY semester_id LIMIT 1');
            $semesterStmt->execute(['id' => $student['id']]);
            $semesterId = (int) ($semesterStmt->fetchColumn() ?: 0);

            if ($semesterId <= 0) {
                return [];
            }

            $numberStmt = $db->prepare('SELECT number FROM semesters WHERE id = :id LIMIT 1');
            $numberStmt->execute(['id' => $semesterId]);
            $semesterNumber = (int) ($numberStmt->fetchColumn() ?: 0);

            $subjectSemesters = $this->subjectSemestersForAaliClass($semesterNumber);
            if ($subjectSemesters === []) {
                return [];
            }

            $placeholders = implode(',', array_fill(0, count($subjectSemesters), '?'));
            $stmt = $db->prepare("SELECT id, name FROM subjects WHERE level_id = ? AND semester IN ($placeholders) ORDER BY semester, name");
            $stmt->execute(array_merge([(int) ($student['level_id'] ?? 0)], $subjectSemesters));
            return $stmt->fetchAll();
        }

        $periodStmt = $db->prepare('SELECT period_id FROM student_period WHERE student_id = :id ORDER BY period_id LIMIT 1');
        $periodStmt->execute(['id' => $student['id']]);
        $periodId = (int) ($periodStmt->fetchColumn() ?: 0);

        if ($periodId <= 0) {
            return [];
        }

        $stmt = $db->prepare('SELECT id, name FROM subjects WHERE level_id = :level_id AND period_id = :period_id ORDER BY name');
        $stmt->execute([
            'level_id' => $student['level_id'],
            'period_id' => $periodId,
        ]);

        return $stmt->fetchAll();
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
        if (in_array($studentSemesterNumber, [13, 1, 2], true)) {
            return [1, 2, 13];
        }
        if (in_array($studentSemesterNumber, [14, 3, 4], true)) {
            return [3, 4, 14];
        }

        return [];
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
}
