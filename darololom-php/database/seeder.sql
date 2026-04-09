INSERT INTO users (
    full_name,
    username,
    email,
    password_hash,
    role,
    permissions,
    can_register_students,
    can_register_teachers,
    created_by,
    is_active
) VALUES (
    'Super Admin',
    'superadmin',
    'modeer@modeer.com',
    '$2y$10$.f6HrIM8rztM7o2zsTV2uOp0911cYxmRiP3YhacmoyVkEeV0oll7u',
    'super_admin',
    '["access_students","register_students","manage_students","access_teachers","register_teachers","manage_teachers","manage_classes","manage_subjects","manage_grades","manage_contracts","manage_users"]',
    1,
    1,
    NULL,
    1
)
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    email = VALUES(email),
    password_hash = VALUES(password_hash),
    role = 'super_admin',
    permissions = VALUES(permissions),
    can_register_students = 1,
    can_register_teachers = 1,
    is_active = 1;
