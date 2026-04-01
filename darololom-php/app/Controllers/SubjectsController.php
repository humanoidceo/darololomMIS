<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

final class SubjectsController extends Controller
{
    public function index(array $params = []): void
    {
        $this->authorize('manage_subjects', 'شما اجازه دسترسی به مدیریت مضامین را ندارید.');
        clear_old();
        $db = Database::connection();

        $q = trim((string) ($_GET['q'] ?? ''));
        $level = trim((string) ($_GET['level'] ?? 'aali'));

        $sql = 'SELECT s.*, l.name AS level_name, l.code AS level_code, cp.number AS period_number
            FROM subjects s
            LEFT JOIN study_levels l ON l.id = s.level_id
            LEFT JOIN course_periods cp ON cp.id = s.period_id
            WHERE 1=1';

        $bind = [];
        if ($q !== '') {
            $sql .= ' AND s.name LIKE :q';
            $bind['q'] = '%' . $q . '%';
        }
        if (in_array($level, ['aali', 'moteseta', 'ebtedai'], true)) {
            $sql .= ' AND l.code = :level';
            $bind['level'] = $level;
        }

        $sql .= ' ORDER BY s.created_at DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($bind);

        $this->render('subjects/index', [
            'title' => 'لیست مضامین',
            'subjects' => $stmt->fetchAll(),
            'q' => $q,
            'level' => $level,
        ]);
    }

    public function create(array $params = []): void
    {
        $this->authorize('manage_subjects', 'شما اجازه ثبت مضمون جدید را ندارید.', '/');
        clear_old();
        $this->render('subjects/form', [
            'title' => 'ثبت مضمون',
            'subject' => null,
            'selectedTeacher' => null,
            ...$this->references(),
            'formAction' => url('/subjects/store'),
        ]);
    }

    public function store(array $params = []): void
    {
        $this->authorize('manage_subjects', 'شما اجازه ثبت مضمون جدید را ندارید.', '/');
        $this->csrfCheck();
        $name = trim((string) ($_POST['name'] ?? ''));
        $teacherValidation = $this->validateTeacherSelection(true);

        if ($name === '') {
            with_old($_POST);
            flash('error', 'نام مضمون الزامی است.');
            $this->redirect('/subjects/create');
        }
        if (!$teacherValidation['valid']) {
            with_old($_POST);
            flash('error', $teacherValidation['error']);
            $this->redirect('/subjects/create');
        }

        $db = Database::connection();
        $stmt = $db->prepare('INSERT INTO subjects (name, level_id, semester, period_id, created_at)
            VALUES (:name, :level_id, :semester, :period_id, NOW())');
        $stmt->execute($this->payload());
        $subjectId = (int) $db->lastInsertId();
        $teacherId = (int) ($teacherValidation['teacher_id'] ?? 0);
        $this->linkTeacherToSubject($subjectId, $teacherId);

        flash('success', 'مضمون ثبت شد.');
        $this->redirect('/subjects');
    }

    public function edit(array $params = []): void
    {
        $this->authorize('manage_subjects', 'شما اجازه ویرایش مضمون را ندارید.', '/');
        clear_old();
        $id = $this->intParam($params, 'id');
        $db = Database::connection();

        $stmt = $db->prepare('SELECT * FROM subjects WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $subject = $stmt->fetch();

        if (!$subject) {
            flash('error', 'مضمون پیدا نشد.');
            $this->redirect('/subjects');
        }

        $this->render('subjects/form', [
            'title' => 'ویرایش مضمون',
            'subject' => $subject,
            'selectedTeacher' => $this->selectedTeacherForSubject($id),
            ...$this->references(),
            'formAction' => url('/subjects/' . $id . '/update'),
        ]);
    }

    public function update(array $params = []): void
    {
        $this->authorize('manage_subjects', 'شما اجازه ویرایش مضمون را ندارید.', '/');
        $this->csrfCheck();
        $id = $this->intParam($params, 'id');
        $name = trim((string) ($_POST['name'] ?? ''));
        $teacherValidation = $this->validateTeacherSelection(false);

        if ($name === '') {
            with_old($_POST);
            flash('error', 'نام مضمون الزامی است.');
            $this->redirect('/subjects/' . $id . '/edit');
        }
        if (!$teacherValidation['valid']) {
            with_old($_POST);
            flash('error', $teacherValidation['error']);
            $this->redirect('/subjects/' . $id . '/edit');
        }

        $db = Database::connection();
        $payload = $this->payload();
        $payload['id'] = $id;

        $stmt = $db->prepare('UPDATE subjects
            SET name = :name, level_id = :level_id, semester = :semester, period_id = :period_id
            WHERE id = :id');
        $stmt->execute($payload);
        $teacherId = (int) ($teacherValidation['teacher_id'] ?? 0);
        if ($teacherId > 0) {
            $this->linkTeacherToSubject($id, $teacherId);
        }

        flash('success', 'مضمون بروزرسانی شد.');
        $this->redirect('/subjects');
    }

    public function destroy(array $params = []): void
    {
        $this->authorize('manage_subjects', 'شما اجازه حذف مضمون را ندارید.', '/');
        $this->csrfCheck();
        $id = $this->intParam($params, 'id');

        $db = Database::connection();
        $db->prepare('DELETE FROM subjects WHERE id = :id')->execute(['id' => $id]);

        flash('success', 'مضمون حذف شد.');
        $this->redirect('/subjects');
    }

    public function apiTeachers(array $params = []): void
    {
        $this->authorize('manage_subjects', 'شما اجازه جستجوی اساتید را ندارید.', '/subjects');
        $q = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = 5;
        $offset = ($page - 1) * $limit;

        $db = Database::connection();

        $whereSql = '';
        if ($q !== '') {
            $whereSql = 'WHERE (t.name LIKE :q OR t.father_name LIKE :q OR t.id_number LIKE :q)';
        }

        $stmt = $db->prepare(
            'SELECT t.id, t.name, t.father_name
             FROM teachers t
             ' . $whereSql . '
             ORDER BY t.name ASC, t.id ASC
             LIMIT :limit OFFSET :offset'
        );

        if ($q !== '') {
            $stmt->bindValue(':q', '%' . $q . '%');
        }
        $stmt->bindValue(':limit', $limit + 1, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        $hasMore = count($rows) > $limit;
        if ($hasMore) {
            $rows = array_slice($rows, 0, $limit);
        }

        $items = array_map(static function (array $row): array {
            $name = trim((string) ($row['name'] ?? ''));
            $fatherName = trim((string) ($row['father_name'] ?? ''));
            $label = $name !== '' ? $name : '—';
            if ($fatherName !== '') {
                $label .= ' (' . $fatherName . ')';
            }

            return [
                'id' => (int) ($row['id'] ?? 0),
                'name' => $name,
                'father_name' => $fatherName,
                'label' => $label,
            ];
        }, $rows);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'items' => $items,
            'has_more' => $hasMore,
            'page' => $page,
        ], JSON_UNESCAPED_UNICODE);
    }

    private function references(): array
    {
        $db = Database::connection();
        return [
            'levels' => $db->query('SELECT * FROM study_levels ORDER BY id')->fetchAll(),
            'periods' => $db->query('SELECT * FROM course_periods ORDER BY number')->fetchAll(),
        ];
    }

    private function selectedTeacherForSubject(int $subjectId): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT t.id, t.name, t.father_name
             FROM teacher_subject ts
             JOIN teachers t ON t.id = ts.teacher_id
             WHERE ts.subject_id = :subject_id
             ORDER BY t.name ASC, t.id ASC
             LIMIT 1'
        );
        $stmt->execute(['subject_id' => $subjectId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    private function validateTeacherSelection(bool $required): array
    {
        $teacherId = (int) ($_POST['teacher_id'] ?? 0);
        if ($teacherId <= 0) {
            if ($required) {
                return ['valid' => false, 'error' => 'انتخاب استاد الزامی است.', 'teacher_id' => null];
            }
            return ['valid' => true, 'error' => '', 'teacher_id' => null];
        }

        $db = Database::connection();
        $stmt = $db->prepare('SELECT id FROM teachers WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $teacherId]);

        if (!$stmt->fetch()) {
            return ['valid' => false, 'error' => 'استاد انتخاب‌شده معتبر نیست.', 'teacher_id' => null];
        }

        return ['valid' => true, 'error' => '', 'teacher_id' => $teacherId];
    }

    private function linkTeacherToSubject(int $subjectId, int $teacherId): void
    {
        if ($subjectId <= 0 || $teacherId <= 0) {
            return;
        }

        $db = Database::connection();
        $stmt = $db->prepare('INSERT IGNORE INTO teacher_subject (teacher_id, subject_id) VALUES (:teacher_id, :subject_id)');
        $stmt->execute([
            'teacher_id' => $teacherId,
            'subject_id' => $subjectId,
        ]);
    }

    private function payload(): array
    {
        return [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'level_id' => (int) ($_POST['level_id'] ?? 0) ?: null,
            'semester' => (int) ($_POST['semester'] ?? 1),
            'period_id' => (int) ($_POST['period_id'] ?? 0) ?: null,
        ];
    }
}
