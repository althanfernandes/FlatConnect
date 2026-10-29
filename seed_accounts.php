<?php
/**
 * FlatConnect - Safe, Repeatable User Account Seeder
 * 
 * Safely creates or updates the three fictional test accounts:
 * 1. Owner:  owner@flatconnect.com  / Password@123
 * 2. Tenant: tenant@flatconnect.com / Password@123
 * 3. Seeker: seeker@flatconnect.com / Password@123
 * 
 * Does NOT delete any existing properties, enquiries, or roommate posts.
 */

require_once __DIR__ . '/config/db.php';

$password_plain = 'Password@123';
$hashed_password = password_hash($password_plain, PASSWORD_DEFAULT);

$accounts = [
    [
        'id'       => 1,
        'name'     => 'Rajesh Sharma',
        'email'    => 'owner@flatconnect.com',
        'mobile'   => '9822011223',
        'role'     => 'owner',
        'location' => 'Hinjewadi',
        'budget'   => 25000,
        'type'     => 'Flat',
        'furnish'  => 'Fully Furnished',
        'bio'      => 'Experienced flat and PG owner with properties across Hinjewadi, Wakad and Tathawade.'
    ],
    [
        'id'       => 2,
        'name'     => 'Amit Deshmukh',
        'email'    => 'tenant@flatconnect.com',
        'mobile'   => '9890123456',
        'role'     => 'tenant',
        'location' => 'Wakad',
        'budget'   => 12000,
        'type'     => 'Flat',
        'furnish'  => 'Semi-Furnished',
        'bio'      => 'Software engineer currently renting a 2BHK flat in Wakad; looking for replacement tenant and roommates.'
    ],
    [
        'id'       => 3,
        'name'     => 'Pooja Kulkarni',
        'email'    => 'seeker@flatconnect.com',
        'mobile'   => '9765432100',
        'role'     => 'seeker',
        'location' => 'Hinjewadi',
        'budget'   => 18000,
        'type'     => 'Flat',
        'furnish'  => 'Semi-Furnished',
        'bio'      => 'Relocating to Pune for IT job in Hinjewadi Phase 2. Looking for clean, quiet flat or PG.'
    ],
    [
        'id'       => 4,
        'name'     => 'Sunil Patil',
        'email'    => 'sunil.owner@flatconnect.com',
        'mobile'   => '9822033445',
        'role'     => 'owner',
        'location' => 'Baner',
        'budget'   => 30000,
        'type'     => 'Flat',
        'furnish'  => 'Fully Furnished',
        'bio'      => 'Real estate owner in Baner and Punawale offering bachelor-friendly rental spaces.'
    ],
    [
        'id'       => 5,
        'name'     => 'Priya Mehta',
        'email'    => 'priya.tenant@flatconnect.com',
        'mobile'   => '9822055667',
        'role'     => 'tenant',
        'location' => 'Tathawade',
        'budget'   => 8000,
        'type'     => 'PG',
        'furnish'  => 'Fully Furnished',
        'bio'      => 'Final year engineering student looking for flatmate and successor for rented room.'
    ],
    [
        'id'       => 6,
        'name'     => 'Rahul Joshi',
        'email'    => 'rahul.seeker@flatconnect.com',
        'mobile'   => '9822077889',
        'role'     => 'seeker',
        'location' => 'Tathawade',
        'budget'   => 9000,
        'type'     => 'Room',
        'furnish'  => 'Semi-Furnished',
        'bio'      => 'Master student at JSPM looking for budget accommodation nearby.'
    ]
];

echo "--- Seeding / Updating User Accounts ---\n";

foreach ($accounts as $acc) {
    // Check if user exists by email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$acc['email']]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update password hash and details safely without touching any linked properties
        $update = $pdo->prepare("
            UPDATE users SET 
                name = ?, mobile = ?, password = ?, role = ?, 
                preferred_location = ?, max_budget = ?, preferred_type = ?, preferred_furnishing = ?, bio = ? 
            WHERE id = ?
        ");
        $update->execute([
            $acc['name'], $acc['mobile'], $hashed_password, $acc['role'],
            $acc['location'], $acc['budget'], $acc['type'], $acc['furnish'], $acc['bio'],
            $existing['id']
        ]);
        echo "[UPDATED] {$acc['name']} ({$acc['email']}) - Role: {$acc['role']}\n";
    } else {
        // Insert new user record
        $insert = $pdo->prepare("
            INSERT INTO users (id, name, email, mobile, password, role, preferred_location, max_budget, preferred_type, preferred_furnishing, bio) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insert->execute([
            $acc['id'], $acc['name'], $acc['email'], $acc['mobile'], $hashed_password, $acc['role'],
            $acc['location'], $acc['budget'], $acc['type'], $acc['furnish'], $acc['bio']
        ]);
        echo "[INSERTED] {$acc['name']} ({$acc['email']}) - Role: {$acc['role']}\n";
    }
}

// Verification step
echo "\n--- Verifying Account Logins with password_verify() ---\n";
foreach ($accounts as $acc) {
    $v_stmt = $pdo->prepare("SELECT email, password, role FROM users WHERE email = ? LIMIT 1");
    $v_stmt->execute([$acc['email']]);
    $u = $v_stmt->fetch();

    if ($u && password_verify($password_plain, $u['password'])) {
        echo "✓ Login Check PASSED: {$u['email']} -> {$u['role']}\n";
    } else {
        echo "✗ Login Check FAILED: {$acc['email']}\n";
    }
}

echo "\nDatabase seeding and verification completed successfully!\n";
