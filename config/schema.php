<?php
declare(strict_types=1);

/**
 * Light-weight schema bootstrap to ensure required tables exist for the
 * booking + review flows. This avoids manual migrations for the small app.
 */

function ensure_core_schema(mysqli $conn): void
{
    // Users
    $conn->query(
        "CREATE TABLE IF NOT EXISTS users (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            phone VARCHAR(40),
            password_hash VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    );

    // Services
    $conn->query(
        "CREATE TABLE IF NOT EXISTS services (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            duration_min SMALLINT UNSIGNED NOT NULL DEFAULT 30,
            price_cents INT UNSIGNED NOT NULL DEFAULT 0,
            active TINYINT(1) NOT NULL DEFAULT 1,
            UNIQUE KEY uniq_service_name (name)
        )"
    );

    // Barbers
    $conn->query(
        "CREATE TABLE IF NOT EXISTS barbers (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            specialty VARCHAR(120),
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_barber_name (name)
        )"
    );

    // Barber availability
    $conn->query(
        "CREATE TABLE IF NOT EXISTS barber_availability (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            barber_id BIGINT UNSIGNED NOT NULL,
            weekday ENUM('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
            time_slot TIME NOT NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE CASCADE,
            UNIQUE KEY uniq_barber_slot (barber_id, weekday, time_slot)
        )"
    );

    // Reservations
    $conn->query(
        "CREATE TABLE IF NOT EXISTS reservations (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            service_id BIGINT UNSIGNED,
            barber_id BIGINT UNSIGNED,
            scheduled_at DATETIME NOT NULL,
            status ENUM('pending','confirmed','completed','canceled') NOT NULL DEFAULT 'pending',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_res_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_res_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
            CONSTRAINT fk_res_barber FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE SET NULL,
            INDEX idx_res_user (user_id),
            INDEX idx_res_sched (scheduled_at)
        )"
    );

    // Reviews
    $conn->query(
        "CREATE TABLE IF NOT EXISTS reviews (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            reservation_id BIGINT UNSIGNED NULL,
            user_id BIGINT UNSIGNED NULL,
            name VARCHAR(255) NOT NULL,
            rating TINYINT UNSIGNED NOT NULL,
            comment TEXT,
            hidden TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_rev_res  FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL,
            CONSTRAINT fk_rev_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_rev_hidden (hidden)
        )"
    );
}

/**
 * Seed default barbers and availability slots if none exist, using the
 * legacy hard-coded schedule so the booking form works without manual setup.
 */
function seed_default_barbers(mysqli $conn): void
{
    $result = $conn->query('SELECT COUNT(*) AS total FROM barbers');
    $count = (int) ($result->fetch_assoc()['total'] ?? 0);
    if ($count > 0) {
        return;
    }

    $availability = [
        'Barber Angelo' => [
            'Sunday' => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            'Monday' => ['11:00', '14:00', '15:00', '16:00', '17:00'],
            'Tuesday' => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            'Wednesday' => [],
            'Thursday' => ['11:00', '14:00', '15:00', '16:00', '17:00'],
            'Friday' => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            'Saturday' => ['11:00', '14:00', '15:00', '16:00', '17:00'],
        ],
        'Barber Reymart' => [
            'Sunday' => ['11:00', '14:00', '15:00', '16:00', '17:00'],
            'Monday' => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            'Tuesday' => [],
            'Wednesday' => ['11:00', '14:00', '15:00', '16:00', '17:00'],
            'Thursday' => ['11:00', '14:00', '15:00', '16:00', '17:00'],
            'Friday' => ['11:00', '14:00', '15:00', '16:00', '17:00'],
            'Saturday' => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
        ],
        'Barber Rod' => [
            'Sunday' => ['11:00', '14:00', '15:00', '16:00', '17:00'],
            'Monday' => ['11:00', '14:00', '15:00', '16:00', '17:00'],
            'Tuesday' => ['11:00', '14:00', '15:00', '16:00', '17:00'],
            'Wednesday' => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            'Thursday' => [],
            'Friday' => ['11:00', '14:00', '15:00', '16:00', '17:00'],
            'Saturday' => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
        ],
        'Barber Lyndon' => [
            'Sunday' => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            'Monday' => [],
            'Tuesday' => ['11:00', '14:00', '15:00', '16:00', '17:00'],
            'Wednesday' => ['11:00', '14:00', '15:00', '16:00', '17:00'],
            'Thursday' => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            'Friday' => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            'Saturday' => ['11:00', '14:00', '15:00', '16:00', '17:00'],
        ],
        'Barber Ed' => [
            'Sunday' => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            'Monday' => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            'Tuesday' => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            'Wednesday' => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            'Thursday' => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            'Friday' => [],
            'Saturday' => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
        ],
    ];

    foreach ($availability as $barberName => $schedule) {
        $stmt = $conn->prepare('INSERT INTO barbers (name, active) VALUES (?, 1)');
        $stmt->bind_param('s', $barberName);
        $stmt->execute();
        $barberId = $conn->insert_id;
        $stmt->close();

        foreach ($schedule as $weekday => $slots) {
            foreach ($slots as $slot) {
                $stmtSlot = $conn->prepare('INSERT IGNORE INTO barber_availability (barber_id, weekday, time_slot, active) VALUES (?, ?, ?, 1)');
                $stmtSlot->bind_param('iss', $barberId, $weekday, $slot);
                $stmtSlot->execute();
                $stmtSlot->close();
            }
        }
    }
}
