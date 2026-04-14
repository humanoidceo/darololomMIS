<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>کارت شناسایی دانش‌آموز</title>
    <link rel="stylesheet" href="<?= e(url('/assets/css/modules/student_card_print.css')) ?>">
    <style>
        :root {
            --card-primary: #0f5132;
            --card-primary-deep: #0a3a24;
            --card-accent: #c89b3c;
            --card-accent-soft: #f5e5b8;
            --card-surface: #fffdf8;
            --card-ink: #17212b;
            --card-muted: #5b6673;
            --card-line: rgba(15, 81, 50, 0.16);
        }

        .id-actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .id-actions .btn {
            border: 0;
            border-radius: 12px;
            padding: 10px 18px;
            color: #fff;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
        }

        .id-actions .btn-print {
            background: linear-gradient(135deg, #0f766e 0%, #0f5132 100%);
        }

        .id-actions .btn-back {
            background: #334155;
        }

        .id-card-shell {
            width: 440px;
            height: 620px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
            border-radius: 26px;
            border: 1px solid rgba(200, 155, 60, 0.42);
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.16);
            background:
                radial-gradient(circle at top left, rgba(200, 155, 60, 0.16), transparent 28%),
                radial-gradient(circle at bottom right, rgba(15, 81, 50, 0.1), transparent 30%),
                linear-gradient(180deg, #fffefb 0%, #fffaf2 100%);
            direction: rtl;
        }

        .id-card-shell::before {
            content: "";
            position: absolute;
            inset: 12px;
            border: 1px solid rgba(200, 155, 60, 0.28);
            border-radius: 20px;
            pointer-events: none;
        }

        .id-card-shell::after {
            content: "";
            position: absolute;
            top: 132px;
            left: -52px;
            width: 168px;
            height: 168px;
            border-radius: 999px;
            background: rgba(200, 155, 60, 0.08);
            pointer-events: none;
        }

        .id-card-header {
            position: relative;
            padding: 20px 26px 98px;
            text-align: center;
            color: #fff;
            background:
                linear-gradient(135deg, var(--card-primary-deep) 0%, var(--card-primary) 62%, #157347 100%);
            overflow: hidden;
        }

        .id-card-header::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(45deg, rgba(255,255,255,0.08), transparent 32%),
                radial-gradient(circle at top center, rgba(245, 229, 184, 0.18), transparent 36%),
                repeating-linear-gradient(
                    135deg,
                    rgba(255,255,255,0.06) 0,
                    rgba(255,255,255,0.06) 2px,
                    transparent 2px,
                    transparent 14px
                );
            opacity: 0.95;
        }

        .id-card-header > * {
            position: relative;
            z-index: 1;
        }

        .id-card-student-id-rail {
            position: absolute;
            top: 18px;
            right: 10px;
            bottom: 18px;
            width: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
        }

        .id-card-student-id-rail span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            writing-mode: vertical-rl;
            text-orientation: mixed;
            transform: rotate(180deg);
            font-size: 10px;
            line-height: 1.2;
            font-weight: 800;
            letter-spacing: 1px;
            color: rgba(255, 246, 221, 0.94);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.18);
            white-space: nowrap;
        }

        .id-card-tazkira-rail {
            position: absolute;
            top: 18px;
            left: 10px;
            bottom: 18px;
            width: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
        }

        .id-card-tazkira-rail span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            writing-mode: vertical-rl;
            text-orientation: mixed;
            font-size: 10px;
            line-height: 1.2;
            font-weight: 800;
            letter-spacing: 1px;
            color: rgba(255, 246, 221, 0.94);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.18);
            white-space: nowrap;
        }

        .id-card-bismillah {
            margin: 0 0 8px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.2px;
            color: rgba(255, 248, 220, 0.96);
        }

        .id-card-logo-circle {
            width: 82px;
            height: 82px;
            margin: 0 auto 12px;
            border-radius: 999px;
            background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(248, 250, 252, 0.94));
            border: 3px solid rgba(245, 229, 184, 0.85);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.16);
        }

        .id-card-logo-circle img {
            width: 68px;
            height: 68px;
            object-fit: contain;
            border-radius: 999px;
        }

        .id-card-title h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 900;
            line-height: 1.35;
        }

        .id-card-title p {
            margin: 6px 0 0;
            font-size: 12px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.92);
        }

        .id-card-ornament {
            width: 140px;
            height: 12px;
            margin: 10px auto 0;
            position: relative;
        }

        .id-card-ornament::before,
        .id-card-ornament::after,
        .id-card-ornament span {
            content: "";
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(245, 229, 184, 0.95);
        }

        .id-card-ornament::before,
        .id-card-ornament::after {
            width: 44px;
            height: 2px;
        }

        .id-card-ornament::before {
            right: 0;
        }

        .id-card-ornament::after {
            left: 0;
        }

        .id-card-ornament span {
            right: 50%;
            width: 18px;
            height: 18px;
            border-radius: 4px;
            transform: translate(50%, -50%) rotate(45deg);
        }

        .id-card-curve {
            margin-top: -34px;
            position: relative;
            z-index: 2;
        }

        .id-card-photo-wrap {
            display: flex;
            justify-content: center;
            margin-top: -88px;
            margin-bottom: 12px;
            position: relative;
            z-index: 4;
            width: 100%;
        }

        .id-card-photo {
            width: 126px;
            height: 126px;
            padding: 6px;
            border-radius: 30px;
            background: linear-gradient(135deg, var(--card-accent-soft) 0%, #ffffff 100%);
            border: 1px solid rgba(200, 155, 60, 0.5);
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.16);
        }

        .id-card-photo-inner {
            width: 100%;
            height: 100%;
            border-radius: 24px;
            background: linear-gradient(180deg, #f8fafc, #e5e7eb);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .id-card-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .id-card-photo svg {
            width: 56px;
            height: 56px;
            color: #94a3b8;
        }

        .id-card-qr-card {
            width: 92px;
            padding: 5px;
            border-radius: 14px;
            border: 1px solid rgba(200, 155, 60, 0.34);
            background: rgba(255, 253, 248, 0.96);
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
            text-align: center;
            align-self: flex-end;
            flex-shrink: 0;
        }

        .id-card-qr-card img {
            display: block;
            width: 80px;
            height: 80px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
        }

        .id-card-qr-card span {
            display: block;
            font-size: 9px;
            line-height: 1.35;
            color: var(--card-primary-deep);
            font-weight: 800;
        }

        .id-card-body {
            height: calc(100% - 206px);
            padding: 0 22px 18px;
            display: flex;
            flex-direction: column;
        }

        .id-card-name {
            text-align: center;
            margin-bottom: 12px;
        }

        .id-card-name h2 {
            margin: 0;
            font-size: 26px;
            color: var(--card-primary-deep);
            font-weight: 900;
            line-height: 1.2;
        }

        .id-card-name p {
            margin: 6px 0 0;
            font-size: 12px;
            color: var(--card-muted);
            font-weight: 700;
        }

        .id-card-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
            padding: 5px 12px;
            border-radius: 999px;
            border: 1px solid rgba(200, 155, 60, 0.35);
            background: rgba(200, 155, 60, 0.12);
            color: #8a6421;
            font-size: 11px;
            font-weight: 800;
        }

        .id-card-panel {
            position: relative;
            padding: 16px 14px 14px;
            border-radius: 22px;
            border: 1px solid var(--card-line);
            background: rgba(255, 255, 255, 0.94);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
        }

        .id-card-panel::before {
            content: "";
            position: absolute;
            top: 12px;
            left: 12px;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            border: 1px solid rgba(200, 155, 60, 0.28);
            transform: rotate(45deg);
            opacity: 0.32;
        }

        .id-card-panel-title {
            margin: 0 0 10px;
            text-align: center;
            font-size: 13px;
            font-weight: 900;
            color: var(--card-primary);
        }

        .id-card-panel-main {
            display: flex;
            direction: rtl;
            align-items: flex-end;
            gap: 10px;
        }

        .id-card-info {
            font-size: 13px;
            flex: 1;
            direction: rtl;
        }

        .id-card-row {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed rgba(148, 163, 184, 0.45);
        }

        .id-card-row:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: 0;
        }

        .id-card-row .label {
            min-width: 96px;
            color: var(--card-muted);
            font-size: 12px;
            font-weight: 700;
        }

        .id-card-row .sep {
            color: var(--card-primary-deep);
            font-weight: 900;
        }

        .id-card-row .value {
            flex: 1;
            color: var(--card-ink);
            font-weight: 800;
            line-height: 1.6;
        }

        .id-card-meta {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-top: 12px;
        }

        .id-card-meta-box {
            border-radius: 16px;
            border: 1px solid rgba(200, 155, 60, 0.26);
            background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(250, 245, 234, 0.94));
            padding: 8px 8px 9px;
            text-align: center;
        }

        .id-card-meta-label {
            display: block;
            margin-bottom: 4px;
            font-size: 10px;
            color: var(--card-muted);
            font-weight: 700;
        }

        .id-card-meta-value {
            display: block;
            font-size: 12px;
            color: var(--card-primary-deep);
            font-weight: 900;
            line-height: 1.45;
        }

        .id-card-footer {
            margin-top: 10px;
            padding: 14px 18px;
            border-radius: 10px;
            text-align: center;
            color: #f8fafc;
            background: linear-gradient(135deg, var(--card-primary-deep) 0%, var(--card-primary) 62%, #157347 100%);
            font-size: 11px;
            font-weight: 700;
            line-height: 1.85;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.12);
        }

        .id-card-footer strong {
            display: block;
            margin-bottom: 2px;
            font-size: 12px;
            color: #fff6dd;
        }

        @media print {
            .id-actions {
                display: none !important;
            }

            .id-card-shell {
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body>
<?php
$imagePath = trim((string) ($student['image_path'] ?? ''));
$issueDateText = trim((string) ($issueDate ?? date('Y-m-d')));
$expiryDateText = trim((string) ($expiryDate ?? date('Y-m-d', strtotime('+1 year'))));
$levelName = trim((string) ($student['level_name'] ?? 'نامشخص'));
$validityYearsValue = (int) ($validityYears ?? (((string) ($student['level_code'] ?? '') === 'aali') ? 2 : 3));
$schoolAddress = 'چهارراهی پروژه تایمنی، جوار مسجد جامع الحاج سید منصور نادری، کابل';
$studentIdValue = (int) ($student['id'] ?? 0);
$issueDateLabel = '';
$expiryDateLabel = '';
$enrollmentYearLabel = trim((string) ($student['enrollment_year'] ?? ''));
$studentNameValue = trim((string) ($student['name'] ?? ''));
$fatherNameValue = trim((string) ($student['father_name'] ?? ''));
$tazkiraValue = trim((string) ($student['id_number'] ?? ''));
$formatCardDate = static function (string $date): string {
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return e($date);
    }

    return to_persian_number(date('Y / m / d', $timestamp));
};
$issueDateLabel = $formatCardDate($issueDateText);
$expiryDateLabel = $formatCardDate($expiryDateText);
$qrPayloadLines = [
    'دارالعلوم عالی الحاج سید منصور نادری',
    'آی دی شاگرد: ' . to_persian_number((string) $studentIdValue),
    'نام: ' . ($studentNameValue !== '' ? $studentNameValue : '-'),
    'نام پدر: ' . ($fatherNameValue !== '' ? $fatherNameValue : '-'),
    'سطح تحصیلی: ' . ($levelName !== '' ? $levelName : '-'),
    'نمبر تذکره: ' . ($tazkiraValue !== '' ? to_persian_number($tazkiraValue) : '-'),
    'تاریخ صدور: ' . $issueDateLabel,
    'تاریخ اعتبار: ' . $expiryDateLabel,
];
$qrPayload = implode("\n", $qrPayloadLines);
$qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&margin=0&format=png&data=' . rawurlencode($qrPayload);
?>
<div class="id-actions">
    <button type="button" class="btn btn-print" onclick="window.print()">چاپ مستقیم</button>
    <a href="<?= e(url('/students')) ?>" class="btn btn-back">بازگشت</a>
</div>

<div id="printCard" class="id-card-shell">
    <div class="id-card-header">
        <div class="id-card-student-id-rail">
            <span>آی دی نمبر :<?= e(to_persian_number((string) $studentIdValue)) ?></span>
        </div>
        <div class="id-card-tazkira-rail">
            <span>نمبر تذکره :<?= e(to_persian_number((string) (($student['id_number'] ?? '') !== '' ? (string) $student['id_number'] : '-'))) ?></span>
        </div>
        <div class="id-card-logo-circle">
            <img src="<?= e(url('/assets/images/logo.jpg')) ?>" alt="لوگو" onerror="this.style.display='none';">
        </div>
        <div class="id-card-title">
            <h5>دارالعلوم عالی الحاج سید منصور نادری</h5>
            <p>کارت شناسایی رسمی شاگرد</p>
        </div>
        <div class="id-card-ornament"><span></span></div>
    </div>

    <div class="id-card-curve">
        <svg viewBox="0 0 440 60" width="100%" height="60" preserveAspectRatio="none" aria-hidden="true">
            <path d="M 0,60 Q 220,0 440,60 L 440,60 L 0,60 Z" fill="#fffdf8"></path>
        </svg>
    </div>

    <div class="id-card-photo-wrap">
        <div class="id-card-photo">
            <div class="id-card-photo-inner">
                <?php if ($imagePath !== ''): ?>
                    <img src="<?= e(url($imagePath)) ?>" alt="<?= e((string) ($student['name'] ?? 'دانش‌آموز')) ?>">
                <?php else: ?>
                    <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                    </svg>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="id-card-body">
        <div class="id-card-name">
            <h3><?= e((string) ($student['name'] ?? 'نام دانش‌آموز')) ?></h3>
            <p>فرزند <?= e((string) (($student['father_name'] ?? '') !== '' ? $student['father_name'] : '-')) ?></p>
        </div>

        <div class="id-card-panel">
            <div class="id-card-panel-main">
                <div class="id-card-info">
                    <div class="id-card-row">
                        <span class="label">سطح تحصیلی</span>
                        <span class="sep">:</span>
                        <span class="value"><?= e($levelName) ?></span>
                    </div>
                   
                    <div class="id-card-row">
                        <span class="label">تاریخ صدور</span>
                        <span class="sep">:</span>
                        <span class="value"><?= $issueDateLabel ?></span>
                    </div>
                    <div class="id-card-row">
                        <span class="label">تاریخ اعتبار</span>
                        <span class="sep">:</span>
                        <span class="value"><?= $expiryDateLabel ?></span>
                    </div>
                </div>
                <div class="id-card-qr-card">
                    <img src="<?= e($qrCodeUrl) ?>" alt="QR معلومات شاگرد" loading="eager" referrerpolicy="no-referrer">
                </div>
            </div>
             <div class="id-card-footer">
            <?= e($schoolAddress) ?>
        </div>
        </div>

       
    </div>
</div>
</body>
</html>
