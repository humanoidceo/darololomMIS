<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

final class StudentsController extends Controller
{
    public function index(array $params = []): void
    {
        $this->authorize('access_students', 'شما اجازه دسترسی به بخش شاگردان را ندارید.');
        clear_old();
        $db = Database::connection();

        $q = trim((string) ($_GET['q'] ?? ''));
        $level = trim((string) ($_GET['level'] ?? 'aali'));
        $year = (int) ($_GET['year'] ?? 0);
        $page = max(1, (int) ($_GET['page'] ?? 1));

        if (!in_array($level, ['aali', 'moteseta', 'ebtedai'], true)) {
            $level = 'aali';
        }
        if ($year < 1350 || $year > 1500) {
            $year = 0;
        }

        $allowedSizes = paginated_sizes();
        $pageSize = (int) ($_GET['page_size'] ?? config('pagination.default_page_size', 20));
        if (!in_array($pageSize, $allowedSizes, true)) {
            $pageSize = 20;
        }

        $filters = [];
        $bind = [];

        if ($q !== '') {
            $filters[] = '(s.name LIKE :q OR s.father_name LIKE :q OR s.mobile_number LIKE :q OR s.id_number LIKE :q)';
            $bind['q'] = '%' . $q . '%';
        }

        $filters[] = 'lvl.code = :level_code';
        $bind['level_code'] = $level;
        if ($year > 0) {
            $filters[] = 's.enrollment_year = :enrollment_year';
            $bind['enrollment_year'] = $year;
        }

        $where = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';

        $countSql = "SELECT COUNT(*)
            FROM students s
            LEFT JOIN study_levels lvl ON lvl.id = s.level_id
            LEFT JOIN school_classes sc ON sc.id = s.school_class_id
            $where";

        $countStmt = $db->prepare($countSql);
        foreach ($bind as $key => $value) {
            $countStmt->bindValue(':' . $key, $value);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $pageSize;

        $sql = "SELECT s.*, lvl.name AS level_name, sc.name AS class_name,
                (
                    SELECT GROUP_CONCAT(se.number ORDER BY se.number SEPARATOR ' ')
                    FROM student_semester ss
                    JOIN semesters se ON se.id = ss.semester_id
                    WHERE ss.student_id = s.id
                ) AS semesters_display,
                (
                    SELECT GROUP_CONCAT(cp.number ORDER BY cp.number SEPARATOR ' ')
                    FROM student_period sp
                    JOIN course_periods cp ON cp.id = sp.period_id
                    WHERE sp.student_id = s.id
                ) AS periods_display,
                (
                    SELECT COUNT(*)
                    FROM student_behaviors sb
                    WHERE sb.student_id = s.id AND sb.entry_type = 'merit'
                ) AS merit_count
            FROM students s
            LEFT JOIN study_levels lvl ON lvl.id = s.level_id
            LEFT JOIN school_classes sc ON sc.id = s.school_class_id
            $where
            ORDER BY s.created_at DESC
            LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($bind as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $students = $stmt->fetchAll();

        $studentIds = array_map(static fn (array $row): int => (int) $row['id'], $students);
        $behaviors = $this->loadBehaviorMap($studentIds);

        $this->render('students/index', [
            'title' => 'لیست دانش‌آموزان',
            'students' => $students,
            'behaviors' => $behaviors,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'q' => $q,
            'level' => $level,
            'year' => $year,
            'allowedSizes' => $allowedSizes,
        ]);
    }

    public function create(array $params = []): void
    {
        $this->authorize('register_students', 'شما اجازه ثبت‌نام شاگردان را ندارید.', '/students');

        $this->render('students/form', [
            'title' => 'ثبت دانش‌آموز',
            'student' => null,
            'linkedUser' => null,
            ...$this->references(),
            'selectedSemesters' => [],
            'selectedPeriods' => [],
            'formAction' => url('/students/store'),
        ]);
    }

    public function store(array $params = []): void
    {
        $this->authorize('register_students', 'شما اجازه ثبت‌نام شاگردان را ندارید.', '/students');
        $this->csrfCheck();
        $db = Database::connection();

        $validation = $this->validateStudentInput(null, null, null);
        if (!$validation['valid']) {
            with_old($_POST);
            flash('error', $validation['error']);
            $this->redirect('/students/create');
        }

        $image = upload_file('image', 'students', ['jpg', 'jpeg', 'png', 'webp']);
        $certificate = upload_file('certificate_file', 'student_certificates', ['pdf']);

        if ($this->isFileUploaded('image') && $image === null) {
            with_old($_POST);
            flash('error', 'آپلود عکس ناموفق بود. لطفاً دوباره تلاش کنید.');
            $this->redirect('/students/create');
        }
        if ($this->isFileUploaded('certificate_file') && $certificate === null) {
            with_old($_POST);
            flash('error', 'آپلود شهادت‌نامه ناموفق بود. لطفاً دوباره تلاش کنید.');
            $this->redirect('/students/create');
        }

        $stmt = $db->prepare('INSERT INTO students (
            name, father_name, grandfather_name, birth_date, id_number, exam_number,
            gender, current_address, village, district, area, current_street, time_start, time_end,
            permanent_address, school_class_id, mobile_number, enrollment_year, image_path, certificate_file,
            level_id, created_at
        ) VALUES (
            :name, :father_name, :grandfather_name, :birth_date, :id_number, :exam_number,
            :gender, :current_address, :village, :district, :area, :current_street, :time_start, :time_end,
            :permanent_address, :school_class_id, :mobile_number, :enrollment_year, :image_path, :certificate_file,
            :level_id, NOW()
        )');

        $stmt->execute($this->payload($image, $certificate, (string) ($validation['exam_number'] ?? '')));
        $studentId = (int) $db->lastInsertId();

        $this->syncSemesters($studentId, $validation['semester_ids']);
        $this->syncPeriods($studentId, $validation['period_ids']);
        $this->upsertStudentAccount(
            $studentId,
            trim((string) ($_POST['name'] ?? '')),
            (string) $validation['account_email'],
            $validation['account_password_hash']
        );

        clear_old();
        flash('success', 'دانش‌آموز با موفقیت ثبت شد.');
        $this->redirect('/students');
    }

    public function edit(array $params = []): void
    {
        $this->authorize('manage_students', 'شما اجازه ویرایش شاگردان را ندارید.', '/students');
        $id = $this->intParam($params, 'id');
        $db = Database::connection();

        $stmt = $db->prepare('SELECT * FROM students WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $student = $stmt->fetch();

        if (!$student) {
            flash('error', 'دانش‌آموز پیدا نشد.');
            $this->redirect('/students');
        }

        $linkedUser = $this->linkedAccount($id);

        $selectedSemesters = $db->prepare('SELECT semester_id FROM student_semester WHERE student_id = :id');
        $selectedSemesters->execute(['id' => $id]);
        $semesterIds = array_map(static fn (array $r): int => (int) $r['semester_id'], $selectedSemesters->fetchAll());

        $selectedPeriods = $db->prepare('SELECT period_id FROM student_period WHERE student_id = :id');
        $selectedPeriods->execute(['id' => $id]);
        $periodIds = array_map(static fn (array $r): int => (int) $r['period_id'], $selectedPeriods->fetchAll());

        $this->render('students/form', [
            'title' => 'ویرایش دانش‌آموز',
            'student' => $student,
            'linkedUser' => $linkedUser,
            ...$this->references(),
            'selectedSemesters' => $semesterIds,
            'selectedPeriods' => $periodIds,
            'formAction' => url('/students/' . $id . '/update'),
        ]);
    }

    public function update(array $params = []): void
    {
        $this->authorize('manage_students', 'شما اجازه ویرایش شاگردان را ندارید.', '/students');
        $this->csrfCheck();
        $id = $this->intParam($params, 'id');
        $db = Database::connection();

        $stmt = $db->prepare('SELECT image_path, certificate_file FROM students WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $student = $stmt->fetch();

        if (!$student) {
            flash('error', 'دانش‌آموز پیدا نشد.');
            $this->redirect('/students');
        }

        $linkedUser = $this->linkedAccount($id);
        $validation = $this->validateStudentInput($student, $id, $linkedUser);
        if (!$validation['valid']) {
            with_old($_POST);
            flash('error', $validation['error']);
            $this->redirect('/students/' . $id . '/edit');
        }

        $image = upload_file('image', 'students', ['jpg', 'jpeg', 'png', 'webp']) ?: $student['image_path'];
        $certificate = upload_file('certificate_file', 'student_certificates', ['pdf']) ?: $student['certificate_file'];

        if ($this->isFileUploaded('image') && $image === null) {
            with_old($_POST);
            flash('error', 'آپلود عکس ناموفق بود. لطفاً دوباره تلاش کنید.');
            $this->redirect('/students/' . $id . '/edit');
        }
        if ($this->isFileUploaded('certificate_file') && $certificate === null) {
            with_old($_POST);
            flash('error', 'آپلود شهادت‌نامه ناموفق بود. لطفاً دوباره تلاش کنید.');
            $this->redirect('/students/' . $id . '/edit');
        }

        $payload = $this->payload($image, $certificate, (string) ($validation['exam_number'] ?? ''));
        $payload['id'] = $id;

        $update = $db->prepare('UPDATE students SET
            name = :name,
            father_name = :father_name,
            grandfather_name = :grandfather_name,
            birth_date = :birth_date,
            id_number = :id_number,
            exam_number = :exam_number,
            gender = :gender,
            current_address = :current_address,
            village = :village,
            district = :district,
            area = :area,
            current_street = :current_street,
            time_start = :time_start,
            time_end = :time_end,
            permanent_address = :permanent_address,
            school_class_id = :school_class_id,
            mobile_number = :mobile_number,
            enrollment_year = :enrollment_year,
            image_path = :image_path,
            certificate_file = :certificate_file,
            level_id = :level_id,
            is_grade12_graduate = 0,
            is_graduated = 0,
            certificate_number = NULL
            WHERE id = :id');

        $update->execute($payload);

        $this->syncSemesters($id, $validation['semester_ids']);
        $this->syncPeriods($id, $validation['period_ids']);
        $this->upsertStudentAccount(
            $id,
            trim((string) ($_POST['name'] ?? '')),
            (string) $validation['account_email'],
            $validation['account_password_hash']
        );

        clear_old();
        flash('success', 'اطلاعات دانش‌آموز بروزرسانی شد.');
        $this->redirect('/students');
    }

    public function destroy(array $params = []): void
    {
        $this->authorize('manage_students', 'شما اجازه حذف شاگردان را ندارید.', '/students');
        $this->csrfCheck();
        $id = $this->intParam($params, 'id');

        $db = Database::connection();
        $db->prepare('DELETE FROM users WHERE role = :role AND student_id = :student_id')
            ->execute([
                'role' => 'student',
                'student_id' => $id,
            ]);

        $stmt = $db->prepare('DELETE FROM students WHERE id = :id');
        $stmt->execute(['id' => $id]);

        flash('success', 'دانش‌آموز حذف شد.');
        $this->redirect('/students');
    }

    public function addBehavior(array $params = []): void
    {
        $this->authorize('manage_students', 'شما اجازه ثبت رفتار برای شاگردان را ندارید.', '/students');
        $this->csrfCheck();
        $studentId = $this->intParam($params, 'id');
        $type = trim((string) ($_POST['entry_type'] ?? ''));
        $note = trim((string) ($_POST['note'] ?? ''));

        if (!in_array($type, ['merit', 'violation'], true)) {
            flash('error', 'نوع ثبت معتبر نیست.');
            $this->redirect('/students');
        }

        $db = Database::connection();
        $stmt = $db->prepare('INSERT INTO student_behaviors (student_id, entry_type, note, created_at) VALUES (:student_id, :entry_type, :note, NOW())');
        $stmt->execute([
            'student_id' => $studentId,
            'entry_type' => $type,
            'note' => $note,
        ]);

        flash('success', 'رکورد رفتاری دانش‌آموز ثبت شد.');
        $this->redirect('/students');
    }

    public function deleteBehavior(array $params = []): void
    {
        $this->authorize('manage_students', 'شما اجازه حذف رفتار شاگردان را ندارید.', '/students');
        $this->csrfCheck();
        $id = $this->intParam($params, 'id');

        $db = Database::connection();
        $stmt = $db->prepare('DELETE FROM student_behaviors WHERE id = :id');
        $stmt->execute(['id' => $id]);

        flash('success', 'رکورد رفتاری حذف شد.');
        $this->redirect('/students');
    }

    public function results(array $params = []): void
    {
        $this->authorize('access_students', 'شما اجازه مشاهده نتایج شاگردان را ندارید.', '/');
        $id = $this->intParam($params, 'id');
        $db = Database::connection();

        $studentStmt = $db->prepare('SELECT s.*, l.name AS level_name FROM students s LEFT JOIN study_levels l ON l.id = s.level_id WHERE s.id = :id LIMIT 1');
        $studentStmt->execute(['id' => $id]);
        $student = $studentStmt->fetch();

        if (!$student) {
            flash('error', 'دانش‌آموز پیدا نشد.');
            $this->redirect('/students');
        }

        $scores = $db->prepare('SELECT sub.name AS subject_name, ss.score
            FROM student_scores ss
            JOIN subjects sub ON sub.id = ss.subject_id
            WHERE ss.student_id = :id
            ORDER BY sub.name');
        $scores->execute(['id' => $id]);

        $this->render('students/results', [
            'title' => 'نتایج امتحان دانش‌آموز',
            'student' => $student,
            'scores' => $scores->fetchAll(),
        ]);
    }

    public function certificate(array $params = []): void
    {
        $this->authorize('access_students', 'شما اجازه مشاهده سرتفیکت شاگردان را ندارید.', '/');
        $id = $this->intParam($params, 'id');
        $db = Database::connection();

        $stmt = $db->prepare('SELECT s.*, l.name AS level_name, sc.name AS class_name
            FROM students s
            LEFT JOIN study_levels l ON l.id = s.level_id
            LEFT JOIN school_classes sc ON sc.id = s.school_class_id
            WHERE s.id = :id
            LIMIT 1');
        $stmt->execute(['id' => $id]);
        $student = $stmt->fetch();

        if (!$student) {
            flash('error', 'دانش‌آموز پیدا نشد.');
            $this->redirect('/students');
        }

        $countStmt = $db->prepare('SELECT COUNT(*) FROM student_behaviors WHERE student_id = :id AND entry_type = :type');
        $countStmt->execute(['id' => $id, 'type' => 'merit']);
        $meritCount = (int) $countStmt->fetchColumn();
        if ($meritCount < 3) {
            flash('error', 'برای چاپ سرتفیکت، حداقل ۳ امتیاز لازم است.');
            $this->redirect('/students');
        }

        if (trim((string) ($student['certificate_number'] ?? '')) === '') {
            $certificateCheck = $db->prepare('SELECT COUNT(*) FROM students WHERE certificate_number = :certificate_number');
            $certificateUpdate = $db->prepare('UPDATE students SET certificate_number = :certificate_number WHERE id = :id');

            for ($i = 0; $i < 20; $i++) {
                $candidate = 'cert-primary-' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
                $certificateCheck->execute(['certificate_number' => $candidate]);
                if ((int) $certificateCheck->fetchColumn() === 0) {
                    $certificateUpdate->execute([
                        'certificate_number' => $candidate,
                        'id' => $id,
                    ]);
                    $student['certificate_number'] = $candidate;
                    break;
                }
            }
        }

        $this->render('students/certificate', [
            'title' => 'سرتفیکت دانش‌آموز',
            'student' => $student,
            'meritCount' => $meritCount,
            'currentDate' => str_replace('-', '/', $this->todayJalaliDate()),
            'qrCodeDataUri' => null,
            'use_layout' => false,
        ]);
    }

    public function appreciation(array $params = []): void
    {
        $this->authorize('access_students', 'شما اجازه مشاهده تقدیرنامه شاگردان را ندارید.', '/');
        $id = $this->intParam($params, 'id');
        $db = Database::connection();

        $stmt = $db->prepare('SELECT s.*, l.name AS level_name, sc.name AS class_name,
            (
                SELECT GROUP_CONCAT(se.number ORDER BY se.number SEPARATOR \' \')
                FROM student_semester ss
                JOIN semesters se ON se.id = ss.semester_id
                WHERE ss.student_id = s.id
            ) AS semesters_display,
            (
                SELECT GROUP_CONCAT(cp.number ORDER BY cp.number SEPARATOR \' \')
                FROM student_period sp
                JOIN course_periods cp ON cp.id = sp.period_id
                WHERE sp.student_id = s.id
            ) AS periods_display
            FROM students s
            LEFT JOIN study_levels l ON l.id = s.level_id
            LEFT JOIN school_classes sc ON sc.id = s.school_class_id
            WHERE s.id = :id
            LIMIT 1');
        $stmt->execute(['id' => $id]);
        $student = $stmt->fetch();

        if (!$student) {
            flash('error', 'دانش‌آموز پیدا نشد.');
            $this->redirect('/students');
        }

        $countStmt = $db->prepare('SELECT COUNT(*) FROM student_behaviors WHERE student_id = :id AND entry_type = :type');
        $countStmt->execute(['id' => $id, 'type' => 'merit']);
        $meritCount = (int) $countStmt->fetchColumn();

        if ($meritCount < 3) {
            flash('error', 'برای چاپ تقدیرنامه، حداقل ۳ امتیاز لازم است.');
            $this->redirect('/students');
        }

        $this->render('students/appreciation', [
            'title' => 'تقدیرنامه دانش‌آموز',
            'student' => $student,
            'meritCount' => $meritCount,
            'jalaliDate' => $this->todayJalaliDate(),
            'use_layout' => false,
        ]);
    }

    public function idCard(array $params = []): void
    {
        $this->authorize('access_students', 'شما اجازه مشاهده کارت شناسایی شاگردان را ندارید.', '/');
        $id = $this->intParam($params, 'id');
        $db = Database::connection();

        $stmt = $db->prepare('SELECT s.*, sc.name AS class_name
            FROM students s
            LEFT JOIN school_classes sc ON sc.id = s.school_class_id
            WHERE s.id = :id
            LIMIT 1');
        $stmt->execute(['id' => $id]);
        $student = $stmt->fetch();

        if (!$student) {
            flash('error', 'دانش‌آموز پیدا نشد.');
            $this->redirect('/students');
        }

        $issueDate = date('Y-m-d');
        $expiryDate = date('Y-m-d', strtotime('+1 year'));

        $this->render('students/id_card', [
            'title' => 'کارت شناسایی دانش‌آموز',
            'student' => $student,
            'issueDate' => $issueDate,
            'expiryDate' => $expiryDate,
            'use_layout' => false,
        ]);
    }

    public function promoteToMoteseta(array $params = []): void
    {
        $this->authorize('manage_students', 'شما اجازه ارتقای شاگردان را ندارید.', '/students');
        $this->csrfCheck();
        $id = $this->intParam($params, 'id');
        $db = Database::connection();

        $studentStmt = $db->prepare('SELECT * FROM students WHERE id = :id LIMIT 1');
        $studentStmt->execute(['id' => $id]);
        $student = $studentStmt->fetch();

        if (!$student) {
            flash('error', 'دانش‌آموز پیدا نشد.');
            $this->redirect('/students');
        }

        $levelStmt = $db->query("SELECT id, code FROM study_levels WHERE code IN ('aali', 'moteseta')");
        $levels = $levelStmt->fetchAll();
        $levelMap = [];
        foreach ($levels as $level) {
            $levelMap[$level['code']] = (int) $level['id'];
        }

        if (($levelMap['moteseta'] ?? 0) <= 0) {
            flash('error', 'سطح متوسطه در دیتابیس موجود نیست.');
            $this->redirect('/students');
        }

        $db->prepare('UPDATE students SET level_id = :level_id WHERE id = :id')
            ->execute([
                'level_id' => $levelMap['moteseta'],
                'id' => $id,
            ]);

        $db->prepare('DELETE FROM student_semester WHERE student_id = :id')->execute(['id' => $id]);

        flash('success', 'دانش‌آموز با موفقیت به سطح متوسطه ارتقا یافت.');
        $this->redirect('/students');
    }

    private function references(): array
    {
        $db = Database::connection();
        return [
            'levels' => $db->query('SELECT * FROM study_levels ORDER BY id')->fetchAll(),
            'classes' => $db->query('SELECT sc.*, l.name AS level_name FROM school_classes sc LEFT JOIN study_levels l ON l.id = sc.level_id ORDER BY sc.name')->fetchAll(),
            'semesters' => $db->query('SELECT * FROM semesters WHERE number IN (13, 14) ORDER BY number')->fetchAll(),
            'periods' => $db->query('SELECT * FROM course_periods ORDER BY number')->fetchAll(),
        ];
    }

    private function linkedAccount(int $studentId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, email
             FROM users
             WHERE role = :role AND student_id = :student_id
             LIMIT 1'
        );
        $stmt->execute([
            'role' => 'student',
            'student_id' => $studentId,
        ]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    private function upsertStudentAccount(int $studentId, string $fullName, string $email, ?string $passwordHash): void
    {
        $db = Database::connection();
        $linked = $this->linkedAccount($studentId);

        if ($linked) {
            $fields = [
                'full_name = :full_name',
                'email = :email',
                'is_active = 1',
            ];
            $params = [
                'id' => (int) $linked['id'],
                'full_name' => $fullName,
                'email' => $email,
            ];

            if ($passwordHash !== null) {
                $fields[] = 'password_hash = :password_hash';
                $params['password_hash'] = $passwordHash;
            }

            $update = $db->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id');
            $update->execute($params);
            return;
        }

        $username = $this->generateUniqueUsername('student_' . $studentId);
        $insert = $db->prepare(
            'INSERT INTO users
            (full_name, username, email, password_hash, role, permissions, can_register_students, can_register_teachers, student_id, created_by, is_active, created_at)
            VALUES
            (:full_name, :username, :email, :password_hash, :role, :permissions, 0, 0, :student_id, :created_by, 1, NOW())'
        );

        $insert->execute([
            'full_name' => $fullName,
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash ?? password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT),
            'role' => 'student',
            'permissions' => json_encode([], JSON_UNESCAPED_UNICODE),
            'student_id' => $studentId,
            'created_by' => auth_id() ?: null,
        ]);
    }

    private function generateUniqueUsername(string $base): string
    {
        $base = strtolower(trim($base));
        $base = preg_replace('/[^a-z0-9_.-]/', '', $base) ?? '';
        if ($base === '') {
            $base = 'user';
        }
        if (mb_strlen($base) < 4) {
            $base .= '_acct';
        }

        $base = substr($base, 0, 40);
        $username = $base;
        $db = Database::connection();

        for ($i = 0; $i < 100; $i++) {
            $stmt = $db->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
            $stmt->execute(['username' => $username]);
            if (!$stmt->fetch()) {
                return $username;
            }

            $suffix = '_' . ($i + 1);
            $username = substr($base, 0, 50 - strlen($suffix)) . $suffix;
        }

        return substr($base, 0, 35) . '_' . bin2hex(random_bytes(6));
    }

    private function payload(?string $imagePath, ?string $certificatePath, string $examNumber): array
    {
        return [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'father_name' => trim((string) ($_POST['father_name'] ?? '')),
            'grandfather_name' => trim((string) ($_POST['grandfather_name'] ?? '')),
            'birth_date' => (($_POST['birth_date'] ?? '') !== '') ? $_POST['birth_date'] : null,
            'id_number' => trim((string) ($_POST['id_number'] ?? '')),
            'exam_number' => $examNumber,
            'gender' => in_array(($_POST['gender'] ?? 'male'), ['male', 'female'], true) ? $_POST['gender'] : 'male',
            'current_address' => trim((string) ($_POST['current_address'] ?? '')),
            'village' => trim((string) ($_POST['village'] ?? '')),
            'district' => trim((string) ($_POST['district'] ?? '')),
            'area' => trim((string) ($_POST['area'] ?? '')),
            'current_street' => trim((string) ($_POST['current_street'] ?? '')),
            'time_start' => (($_POST['time_start'] ?? '') !== '') ? $_POST['time_start'] : null,
            'time_end' => (($_POST['time_end'] ?? '') !== '') ? $_POST['time_end'] : null,
            'permanent_address' => trim((string) ($_POST['permanent_address'] ?? '')),
            'school_class_id' => (int) ($_POST['school_class_id'] ?? 0) ?: null,
            'mobile_number' => trim((string) ($_POST['mobile_number'] ?? '')),
            'enrollment_year' => (int) ($_POST['enrollment_year'] ?? 0) ?: null,
            'image_path' => $imagePath,
            'certificate_file' => $certificatePath,
            'level_id' => (int) ($_POST['level_id'] ?? 0) ?: null,
        ];
    }

    private function syncSemesters(int $studentId, array $semesterIds): void
    {
        $db = Database::connection();
        $db->prepare('DELETE FROM student_semester WHERE student_id = :id')->execute(['id' => $studentId]);

        $insert = $db->prepare('INSERT INTO student_semester (student_id, semester_id) VALUES (:student_id, :semester_id)');
        foreach ($semesterIds as $semesterId) {
            $sid = (int) $semesterId;
            if ($sid > 0) {
                $insert->execute(['student_id' => $studentId, 'semester_id' => $sid]);
            }
        }
    }

    private function syncPeriods(int $studentId, array $periodIds): void
    {
        $db = Database::connection();
        $db->prepare('DELETE FROM student_period WHERE student_id = :id')->execute(['id' => $studentId]);

        $insert = $db->prepare('INSERT INTO student_period (student_id, period_id) VALUES (:student_id, :period_id)');
        foreach ($periodIds as $periodId) {
            $pid = (int) $periodId;
            if ($pid > 0) {
                $insert->execute(['student_id' => $studentId, 'period_id' => $pid]);
            }
        }
    }

    private function loadBehaviorMap(array $studentIds): array
    {
        if ($studentIds === []) {
            return [];
        }

        $db = Database::connection();
        $ids = implode(',', array_fill(0, count($studentIds), '?'));

        $stmt = $db->prepare("SELECT * FROM student_behaviors WHERE student_id IN ($ids) ORDER BY created_at DESC");
        $stmt->execute($studentIds);

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[(int) $row['student_id']][] = $row;
        }

        return $map;
    }

    /**
     * @return array{
     *     valid:bool,
     *     error:string,
     *     semester_ids:array<int>,
     *     period_ids:array<int>,
     *     account_email?:string,
     *     account_password_hash?:?string,
     *     exam_number?:string
     * }
     */
    private function validateStudentInput(?array $existingStudent, ?int $studentId, ?array $linkedUser): array
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $fatherName = trim((string) ($_POST['father_name'] ?? ''));
        $grandfatherName = trim((string) ($_POST['grandfather_name'] ?? ''));
        $birthDate = trim((string) ($_POST['birth_date'] ?? ''));
        $idNumber = trim((string) ($_POST['id_number'] ?? ''));
        $examNumber = trim((string) ($_POST['exam_number'] ?? ''));
        $mobileNumber = trim((string) ($_POST['mobile_number'] ?? ''));
        $currentAddress = trim((string) ($_POST['current_address'] ?? ''));
        $currentStreet = trim((string) ($_POST['current_street'] ?? ''));
        $permanentAddress = trim((string) ($_POST['permanent_address'] ?? ''));
        $village = trim((string) ($_POST['village'] ?? ''));
        $district = trim((string) ($_POST['district'] ?? ''));
        $area = trim((string) ($_POST['area'] ?? ''));
        $timeStart = trim((string) ($_POST['time_start'] ?? ''));
        $timeEnd = trim((string) ($_POST['time_end'] ?? ''));
        $gender = (string) ($_POST['gender'] ?? '');
        $levelId = (int) ($_POST['level_id'] ?? 0);
        $schoolClassId = (int) ($_POST['school_class_id'] ?? 0);
        $semesterId = (int) ($_POST['semester_id'] ?? 0);
        $periodId = (int) ($_POST['period_id'] ?? 0);
        $enrollmentYear = (int) ($_POST['enrollment_year'] ?? 0);
        $accountEmail = mb_strtolower(trim((string) ($_POST['account_email'] ?? '')));
        $accountPassword = (string) ($_POST['account_password'] ?? '');
        $accountPasswordConfirmation = (string) ($_POST['account_password_confirmation'] ?? '');

        if ($name === '' || mb_strlen($name) < 3 || mb_strlen($name) > 255) {
            return ['valid' => false, 'error' => 'نام دانش‌آموز الزامی است (حداقل ۳ حرف).', 'semester_ids' => [], 'period_ids' => []];
        }
        if ($fatherName === '' || mb_strlen($fatherName) < 3 || mb_strlen($fatherName) > 255) {
            return ['valid' => false, 'error' => 'نام پدر الزامی است (حداقل ۳ حرف).', 'semester_ids' => [], 'period_ids' => []];
        }
        if ($grandfatherName !== '' && mb_strlen($grandfatherName) > 255) {
            return ['valid' => false, 'error' => 'نام پدر کلان بیشتر از حد مجاز است.', 'semester_ids' => [], 'period_ids' => []];
        }
        if (!$this->isValidDate($birthDate)) {
            return ['valid' => false, 'error' => 'تاریخ تولد معتبر نیست. نمونه: 2006-08-15', 'semester_ids' => [], 'period_ids' => []];
        }
        if (!in_array($gender, ['male', 'female'], true)) {
            return ['valid' => false, 'error' => 'جنسیت را انتخاب کنید.', 'semester_ids' => [], 'period_ids' => []];
        }
        if ($idNumber === '' || mb_strlen($idNumber) > 100 || !preg_match('/^[0-9A-Za-z\-\/\s]+$/u', $idNumber)) {
            return ['valid' => false, 'error' => 'نمبر تذکره معتبر نیست.', 'semester_ids' => [], 'period_ids' => []];
        }
        if ($mobileNumber === '' || !preg_match('/^[0-9+\-\s]{7,20}$/', $mobileNumber)) {
            return ['valid' => false, 'error' => 'شماره تماس معتبر نیست. نمونه: 0700123456', 'semester_ids' => [], 'period_ids' => []];
        }
        if ($permanentAddress === '' || mb_strlen($permanentAddress) < 2) {
            return ['valid' => false, 'error' => 'ولایت سکونت اصلی را وارد کنید.', 'semester_ids' => [], 'period_ids' => []];
        }
        if ($district === '' || mb_strlen($district) > 150) {
            return ['valid' => false, 'error' => 'ولسوالی سکونت اصلی را درست وارد کنید.', 'semester_ids' => [], 'period_ids' => []];
        }
        if ($village === '' || mb_strlen($village) > 150) {
            return ['valid' => false, 'error' => 'قریه سکونت اصلی را درست وارد کنید.', 'semester_ids' => [], 'period_ids' => []];
        }
        if ($currentAddress === '' || mb_strlen($currentAddress) < 2) {
            return ['valid' => false, 'error' => 'ولایت سکونت فعلی را وارد کنید.', 'semester_ids' => [], 'period_ids' => []];
        }
        if ($area === '' || mb_strlen($area) > 150) {
            return ['valid' => false, 'error' => 'ناحیه سکونت فعلی را درست وارد کنید.', 'semester_ids' => [], 'period_ids' => []];
        }
        if ($currentStreet === '' || mb_strlen($currentStreet) > 150) {
            return ['valid' => false, 'error' => 'کوچه سکونت فعلی را درست وارد کنید.', 'semester_ids' => [], 'period_ids' => []];
        }
        if ($enrollmentYear < 1350 || $enrollmentYear > 1500) {
            return ['valid' => false, 'error' => 'سال شمولیت باید بین ۱۳۵۰ تا ۱۵۰۰ باشد.', 'semester_ids' => [], 'period_ids' => []];
        }

        $timeStartSeconds = $timeStart !== '' ? $this->timeToSeconds($timeStart) : null;
        $timeEndSeconds = $timeEnd !== '' ? $this->timeToSeconds($timeEnd) : null;

        if ($timeStart !== '' && $timeStartSeconds === null) {
            return ['valid' => false, 'error' => 'تایم آغاز معتبر نیست.', 'semester_ids' => [], 'period_ids' => []];
        }
        if ($timeEnd !== '' && $timeEndSeconds === null) {
            return ['valid' => false, 'error' => 'تایم ختم معتبر نیست.', 'semester_ids' => [], 'period_ids' => []];
        }
        if ($timeStartSeconds !== null && $timeEndSeconds !== null && $timeStartSeconds >= $timeEndSeconds) {
            return ['valid' => false, 'error' => 'تایم ختم باید بعد از تایم آغاز باشد.', 'semester_ids' => [], 'period_ids' => []];
        }

        $db = Database::connection();
        $linkedUserId = (int) ($linkedUser['id'] ?? 0);

        if ($accountEmail === '' || !filter_var($accountEmail, FILTER_VALIDATE_EMAIL) || mb_strlen($accountEmail) > 190) {
            return ['valid' => false, 'error' => 'ایمیل حساب شاگرد معتبر نیست.', 'semester_ids' => [], 'period_ids' => []];
        }

        if ($linkedUserId > 0) {
            $emailStmt = $db->prepare(
                'SELECT id
                 FROM users
                 WHERE email = :email
                 AND id <> :exclude_id
                 LIMIT 1'
            );
            $emailStmt->execute([
                'email' => $accountEmail,
                'exclude_id' => $linkedUserId,
            ]);
        } else {
            $emailStmt = $db->prepare(
                'SELECT id
                 FROM users
                 WHERE email = :email
                 LIMIT 1'
            );
            $emailStmt->execute([
                'email' => $accountEmail,
            ]);
        }
        if ($emailStmt->fetch()) {
            return ['valid' => false, 'error' => 'ایمیل حساب شاگرد تکراری است.', 'semester_ids' => [], 'period_ids' => []];
        }

        if ($accountPassword === '' && $accountPasswordConfirmation !== '') {
            return ['valid' => false, 'error' => 'برای تکرار رمز، ابتدا رمز عبور جدید را وارد کنید.', 'semester_ids' => [], 'period_ids' => []];
        }

        if ($linkedUserId === 0 && $accountPassword === '') {
            return ['valid' => false, 'error' => 'برای ایجاد حساب شاگرد، رمز عبور الزامی است.', 'semester_ids' => [], 'period_ids' => []];
        }

        if ($accountPassword !== '') {
            if (mb_strlen($accountPassword) < 8) {
                return ['valid' => false, 'error' => 'رمز عبور حساب شاگرد باید حداقل ۸ کاراکتر باشد.', 'semester_ids' => [], 'period_ids' => []];
            }
            if ($accountPassword !== $accountPasswordConfirmation) {
                return ['valid' => false, 'error' => 'تکرار رمز عبور حساب شاگرد یکسان نیست.', 'semester_ids' => [], 'period_ids' => []];
            }
        }

        if ($levelId <= 0) {
            return ['valid' => false, 'error' => 'سطح آموزشی را انتخاب کنید.', 'semester_ids' => [], 'period_ids' => []];
        }

        $levelStmt = $db->prepare('SELECT id, code FROM study_levels WHERE id = :id LIMIT 1');
        $levelStmt->execute(['id' => $levelId]);
        $level = $levelStmt->fetch();
        if (!$level) {
            return ['valid' => false, 'error' => 'سطح آموزشی معتبر نیست.', 'semester_ids' => [], 'period_ids' => []];
        }
        $levelCode = (string) $level['code'];

        if ($schoolClassId > 0) {
            $classStmt = $db->prepare('SELECT id, level_id FROM school_classes WHERE id = :id LIMIT 1');
            $classStmt->execute(['id' => $schoolClassId]);
            $classRow = $classStmt->fetch();
            if (!$classRow) {
                return ['valid' => false, 'error' => 'صنف انتخاب‌شده معتبر نیست.', 'semester_ids' => [], 'period_ids' => []];
            }
            if ((int) ($classRow['level_id'] ?? 0) > 0 && (int) $classRow['level_id'] !== $levelId) {
                return ['valid' => false, 'error' => 'سطح صنف با سطح آموزشی انتخاب‌شده مطابقت ندارد.', 'semester_ids' => [], 'period_ids' => []];
            }
        }

        if ($semesterId > 0 && !$this->allIdsExist('semesters', [$semesterId])) {
            return ['valid' => false, 'error' => 'گزینه صنف انتخاب‌شده معتبر نیست.', 'semester_ids' => [], 'period_ids' => []];
        }
        if ($periodId > 0 && !$this->allIdsExist('course_periods', [$periodId])) {
            return ['valid' => false, 'error' => 'دوره انتخاب‌شده معتبر نیست.', 'semester_ids' => [], 'period_ids' => []];
        }

        $imageValidation = $this->validateUploadedFile('image', ['jpg', 'jpeg', 'png', 'webp'], 2 * 1024 * 1024, false, 'عکس');
        if ($imageValidation !== null) {
            return ['valid' => false, 'error' => $imageValidation, 'semester_ids' => [], 'period_ids' => []];
        }

        $certificateRequired = $levelCode === 'aali' && empty($existingStudent['certificate_file']);
        $certificateValidation = $this->validateUploadedFile('certificate_file', ['pdf'], 5 * 1024 * 1024, $certificateRequired, 'شهادت‌نامه');
        if ($certificateValidation !== null) {
            return ['valid' => false, 'error' => $certificateValidation, 'semester_ids' => [], 'period_ids' => []];
        }

        if ($levelCode === 'aali') {
            if ($examNumber === '' || mb_strlen($examNumber) > 100 || !preg_match('/^[0-9A-Za-z\-\/\s]+$/u', $examNumber)) {
                return ['valid' => false, 'error' => 'برای سطح عالی، نمبر کانکور الزامی و معتبر است.', 'semester_ids' => [], 'period_ids' => []];
            }
            if ($semesterId <= 0) {
                return ['valid' => false, 'error' => 'برای سطح عالی فقط یکی از صنف‌های ۱۳ یا ۱۴ را انتخاب کنید.', 'semester_ids' => [], 'period_ids' => []];
            }
            if ($periodId > 0) {
                return ['valid' => false, 'error' => 'برای سطح عالی نباید دوره انتخاب شود.', 'semester_ids' => [], 'period_ids' => []];
            }

            $semesterNumberStmt = $db->prepare('SELECT number FROM semesters WHERE id = :id LIMIT 1');
            $semesterNumberStmt->execute(['id' => $semesterId]);
            $semesterNumber = (int) ($semesterNumberStmt->fetchColumn() ?: 0);
            if (!in_array($semesterNumber, [13, 14], true)) {
                return ['valid' => false, 'error' => 'برای سطح عالی فقط صنف ۱۳ یا صنف ۱۴ قابل انتخاب است.', 'semester_ids' => [], 'period_ids' => []];
            }
        } else {
            if ($examNumber !== '') {
                return ['valid' => false, 'error' => 'برای سطح متوسطه و ابتداییه نمبر امتحان کانکور نیاز نیست.', 'semester_ids' => [], 'period_ids' => []];
            }
            if ($periodId <= 0) {
                return ['valid' => false, 'error' => 'برای ابتداییه/متوسطه دقیقاً یک دوره انتخاب کنید.', 'semester_ids' => [], 'period_ids' => []];
            }
            if ($semesterId > 0) {
                return ['valid' => false, 'error' => 'برای ابتداییه/متوسطه نباید صنف ۱۳/۱۴ انتخاب شود.', 'semester_ids' => [], 'period_ids' => []];
            }
        }

        return [
            'valid' => true,
            'error' => '',
            'semester_ids' => $levelCode === 'aali' ? [$semesterId] : [],
            'period_ids' => $levelCode === 'aali' ? [] : [$periodId],
            'account_email' => $accountEmail,
            'account_password_hash' => $accountPassword !== '' ? password_hash($accountPassword, PASSWORD_DEFAULT) : null,
            'exam_number' => $levelCode === 'aali' ? $examNumber : '',
        ];
    }

    private function allIdsExist(string $table, array $ids): bool
    {
        if ($ids === []) {
            return true;
        }

        $db = Database::connection();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        return (int) $stmt->fetchColumn() === count($ids);
    }

    private function isValidDate(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        [$year, $month, $day] = array_map('intval', explode('-', $date));
        return checkdate($month, $day, $year);
    }

    private function isValidTime(string $time): bool
    {
        return $this->timeToSeconds($time) !== null;
    }

    private function timeToSeconds(string $time): ?int
    {
        if (!preg_match('/^(\d{2}):(\d{2})(?::(\d{2}))?$/', $time, $matches)) {
            return null;
        }

        $hour = (int) ($matches[1] ?? 0);
        $minute = (int) ($matches[2] ?? 0);
        $second = isset($matches[3]) ? (int) $matches[3] : 0;

        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59 || $second < 0 || $second > 59) {
            return null;
        }

        return ($hour * 3600) + ($minute * 60) + $second;
    }

    private function isFileUploaded(string $field): bool
    {
        return isset($_FILES[$field]) && (int) ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    private function validateUploadedFile(string $field, array $allowedExtensions, int $maxBytes, bool $required, string $label): ?string
    {
        if (!isset($_FILES[$field]) || (int) ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $required ? "{$label} الزامی است." : null;
        }

        $error = (int) ($_FILES[$field]['error'] ?? UPLOAD_ERR_OK);
        if ($error !== UPLOAD_ERR_OK) {
            return "آپلود {$label} ناموفق بود.";
        }

        $name = strtolower((string) ($_FILES[$field]['name'] ?? ''));
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        if ($ext === '' || !in_array($ext, $allowedExtensions, true)) {
            return "فرمت {$label} مجاز نیست.";
        }

        $size = (int) ($_FILES[$field]['size'] ?? 0);
        if ($size > $maxBytes) {
            return "حجم {$label} بیش از حد مجاز است.";
        }

        return null;
    }

    private function todayJalaliDate(): string
    {
        [$jy, $jm, $jd] = $this->gregorianToJalali((int) date('Y'), (int) date('m'), (int) date('d'));
        return sprintf('%04d-%02d-%02d', $jy, $jm, $jd);
    }

    private function gregorianToJalali(int $gy, int $gm, int $gd): array
    {
        $gdm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        if ($gy > 1600) {
            $jy = 979;
            $gy -= 1600;
        } else {
            $jy = 0;
            $gy -= 621;
        }

        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = (365 * $gy)
            + intdiv($gy2 + 3, 4)
            - intdiv($gy2 + 99, 100)
            + intdiv($gy2 + 399, 400)
            - 80
            + $gd
            + $gdm[$gm - 1];

        $jy += 33 * intdiv($days, 12053);
        $days %= 12053;
        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            $jm = 1 + intdiv($days, 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + intdiv($days - 186, 30);
            $jd = 1 + (($days - 186) % 30);
        }

        return [$jy, $jm, $jd];
    }
}
