<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

final class BooksController extends Controller
{
    public function publicIndex(array $params = []): void
    {
        $this->ensureBooksTable();
        $searchQuery = $this->normalizeSearchQuery((string) ($_GET['q'] ?? ''));
        [$whereSql, $searchBindings] = $this->buildSearchFilter($searchQuery);

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pageSize = 10;
        $offset = ($page - 1) * $pageSize;

        $db = Database::connection();
        $countStmt = $db->prepare('SELECT COUNT(*) FROM books' . $whereSql);
        $this->bindParams($countStmt, $searchBindings);
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $pageSize));

        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $pageSize;
        }

        $stmt = $db->prepare(
            'SELECT *
             FROM books' . $whereSql . '
             ORDER BY created_at DESC, id DESC
             LIMIT :limit OFFSET :offset'
        );
        $this->bindParams($stmt, $searchBindings);
        $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $this->render('books/public_index', [
            'title' => 'کتابخانه الکترونیکی',
            'books' => $stmt->fetchAll(),
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
            'totalPages' => $totalPages,
            'searchQuery' => $searchQuery,
        ]);
    }

    public function show(array $params = []): void
    {
        $this->ensureBooksTable();
        $id = $this->intParam($params, 'id');

        $book = $this->bookById($id);
        if (!$book) {
            http_response_code(404);
            $this->render('books/show', [
                'title' => 'کتاب پیدا نشد',
                'book' => null,
            ]);
            return;
        }

        $this->render('books/show', [
            'title' => 'مطالعه کتاب',
            'book' => $book,
        ]);
    }

    public function index(array $params = []): void
    {
        $this->onlySuperAdmin('تنها سوپر ادمین اجازه مدیریت کتابخانه الکترونیکی را دارد.', '/dashboard');
        $this->ensureBooksTable();
        $searchQuery = $this->normalizeSearchQuery((string) ($_GET['q'] ?? ''));
        [$whereSql, $searchBindings] = $this->buildSearchFilter($searchQuery);

        $formData = $_SESSION['_old'] ?? [];
        clear_old();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pageSize = 10;
        $offset = ($page - 1) * $pageSize;

        $db = Database::connection();
        $countStmt = $db->prepare('SELECT COUNT(*) FROM books' . $whereSql);
        $this->bindParams($countStmt, $searchBindings);
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $pageSize));

        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $pageSize;
        }

        $stmt = $db->prepare(
            'SELECT *
             FROM books' . $whereSql . '
             ORDER BY created_at DESC, id DESC
             LIMIT :limit OFFSET :offset'
        );
        $this->bindParams($stmt, $searchBindings);
        $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $this->render('books/index', [
            'title' => 'کتابخانه الکترونیکی',
            'books' => $stmt->fetchAll(),
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
            'totalPages' => $totalPages,
            'formAction' => url('/library/store'),
            'oldTitle' => (string) ($formData['title'] ?? ''),
            'oldAuthor' => (string) ($formData['author'] ?? ''),
            'oldYear' => (string) ($formData['publication_year'] ?? ''),
            'searchQuery' => $searchQuery,
        ]);
    }

    public function store(array $params = []): void
    {
        $this->onlySuperAdmin('تنها سوپر ادمین اجازه مدیریت کتابخانه الکترونیکی را دارد.', '/dashboard');
        $this->csrfCheck();
        $this->ensureBooksTable();

        $title = trim((string) ($_POST['title'] ?? ''));
        $author = trim((string) ($_POST['author'] ?? ''));
        $publicationYear = (int) ($_POST['publication_year'] ?? 0);

        if (mb_strlen($title) < 2) {
            with_old($_POST);
            flash('error', 'نام کتاب حداقل ۲ حرف باشد.');
            $this->redirect('/library/manage');
        }

        if (mb_strlen($author) < 2) {
            with_old($_POST);
            flash('error', 'نام مولف حداقل ۲ حرف باشد.');
            $this->redirect('/library/manage');
        }

        if ($publicationYear < 1 || $publicationYear > 2500) {
            with_old($_POST);
            flash('error', 'سال تالیف باید بین ۱ تا ۲۵۰۰ باشد.');
            $this->redirect('/library/manage');
        }

        $coverError = $this->validateRequiredUpload(
            'cover_image',
            ['jpg', 'jpeg', 'png', 'webp'],
            'عکس پوش کتاب باید JPG، PNG یا WEBP باشد.'
        );
        if ($coverError !== null) {
            with_old($_POST);
            flash('error', $coverError);
            $this->redirect('/library/manage');
        }

        $pdfError = $this->validateRequiredUpload(
            'book_pdf',
            ['pdf'],
            'فایل کتاب باید PDF باشد.'
        );
        if ($pdfError !== null) {
            with_old($_POST);
            flash('error', $pdfError);
            $this->redirect('/library/manage');
        }

        $coverPath = upload_file('cover_image', 'books/covers', ['jpg', 'jpeg', 'png', 'webp']);
        $pdfPath = upload_file('book_pdf', 'books/files', ['pdf']);

        if ($coverPath === null || $pdfPath === null) {
            with_old($_POST);
            flash('error', 'آپلود عکس یا فایل کتاب ناموفق بود.');
            $this->redirect('/library/manage');
        }

        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO books (title, author, publication_year, cover_image_path, pdf_file_path, created_by, created_at)
             VALUES (:title, :author, :publication_year, :cover_image_path, :pdf_file_path, :created_by, NOW())'
        );
        $stmt->execute([
            'title' => $title,
            'author' => $author,
            'publication_year' => $publicationYear,
            'cover_image_path' => $coverPath,
            'pdf_file_path' => $pdfPath,
            'created_by' => auth_id() ?: null,
        ]);

        clear_old();
        flash('success', 'کتاب با موفقیت به کتابخانه الکترونیکی اضافه شد.');
        $this->redirect('/library/manage');
    }

    public function destroy(array $params = []): void
    {
        $this->onlySuperAdmin('تنها سوپر ادمین اجازه مدیریت کتابخانه الکترونیکی را دارد.', '/dashboard');
        $this->csrfCheck();
        $this->ensureBooksTable();
        $redirectUrl = $this->manageRedirectUrl();

        $id = $this->intParam($params, 'id');
        $book = $this->bookById($id);
        if (!$book) {
            flash('error', 'کتاب مورد نظر پیدا نشد.');
            $this->redirect($redirectUrl);
        }

        $stmt = Database::connection()->prepare('DELETE FROM books WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $this->deleteStoredFile((string) ($book['cover_image_path'] ?? ''));
        $this->deleteStoredFile((string) ($book['pdf_file_path'] ?? ''));

        flash('success', 'کتاب با موفقیت حذف شد.');
        $this->redirect($redirectUrl);
    }

    private function bookById(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT *
             FROM books
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $book = $stmt->fetch();

        return $book ?: null;
    }

    private function validateRequiredUpload(string $field, array $allowedExtensions, string $invalidMessage): ?string
    {
        if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return 'لطفاً فایل مربوطه را انتخاب کنید.';
        }

        if (($_FILES[$field]['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return 'آپلود فایل ناموفق بود.';
        }

        $original = (string) ($_FILES[$field]['name'] ?? '');
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            return $invalidMessage;
        }

        return null;
    }

    private function normalizeSearchQuery(string $query): string
    {
        $query = preg_replace('/\s+/u', ' ', trim($query));

        return is_string($query) ? $query : '';
    }

    private function buildSearchFilter(string $searchQuery): array
    {
        if ($searchQuery === '') {
            return ['', []];
        }

        $terms = preg_split('/\s+/u', $searchQuery, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($terms === []) {
            return ['', []];
        }

        $clauses = [];
        $bindings = [];

        foreach (array_values($terms) as $index => $term) {
            $term = $this->normalizeSearchToken($term);
            $titleParam = ':search_title_' . $index;
            $authorParam = ':search_author_' . $index;
            $yearParam = ':search_year_' . $index;
            $clauses[] = '(title LIKE ' . $titleParam . ' OR author LIKE ' . $authorParam . ' OR CAST(publication_year AS CHAR) LIKE ' . $yearParam . ')';
            $bindings[$titleParam] = '%' . $term . '%';
            $bindings[$authorParam] = '%' . $term . '%';
            $bindings[$yearParam] = '%' . $term . '%';
        }

        return [' WHERE ' . implode(' AND ', $clauses), $bindings];
    }

    private function bindParams(\PDOStatement $stmt, array $bindings): void
    {
        foreach ($bindings as $name => $value) {
            $stmt->bindValue($name, $value, PDO::PARAM_STR);
        }
    }

    private function normalizeSearchToken(string $value): string
    {
        return strtr($value, [
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
        ]);
    }

    private function manageRedirectUrl(): string
    {
        $query = $this->normalizeSearchQuery((string) ($_POST['redirect_q'] ?? ''));
        $page = max(1, (int) ($_POST['redirect_page'] ?? 1));
        $parameters = [];

        if ($query !== '') {
            $parameters['q'] = $query;
        }

        if ($page > 1) {
            $parameters['page'] = (string) $page;
        }

        return '/library/manage' . ($parameters !== [] ? '?' . http_build_query($parameters) : '');
    }

    private function ensureBooksTable(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }

        $db = Database::connection();
        $db->exec(
            'CREATE TABLE IF NOT EXISTS books (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                author VARCHAR(255) NOT NULL,
                publication_year SMALLINT UNSIGNED NOT NULL,
                cover_image_path VARCHAR(255) NOT NULL,
                pdf_file_path VARCHAR(255) NOT NULL,
                created_by INT UNSIGNED NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_books_title (title),
                INDEX idx_books_author (author),
                INDEX idx_books_year (publication_year),
                CONSTRAINT fk_books_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $ensured = true;
    }

    private function deleteStoredFile(string $publicPath): void
    {
        $publicPath = trim($publicPath);
        if ($publicPath === '' || !str_starts_with($publicPath, '/assets/uploads/')) {
            return;
        }

        $absolutePath = dirname(__DIR__, 2) . '/public' . $publicPath;
        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }
}
