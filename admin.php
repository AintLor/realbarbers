<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
ensure_session();

if (empty($_SESSION['admin_authenticated'])) {
    header('Location: admin_login.php');
    exit;
}

$adminName = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | REAL Barbers</title>

    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200;400;600&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

    <link href="premium-real.css" rel="stylesheet">

    <style>
        /* --- ADMIN SPECIFIC STYLES --- */
        body { background: #050505; }
        
        .admin-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2rem 5vw;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .user-chip {
            font-size: 0.9rem;
            color: var(--text-secondary);
            letter-spacing: 1px;
            margin-right: 2rem;
        }

        .dashboard-grid {
            padding: 5vh 5vw;
            display: grid;
            grid-template-columns: 1fr;
            gap: 4rem;
        }

        /* PREMIUM TABLE STYLING */
        .table-container {
            width: 100%;
            overflow-x: auto;
            margin-top: 2rem;
        }

        .premium-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 1rem; /* Creates gap between rows */
            min-width: 800px;
        }

        .premium-table th {
            text-align: left;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--text-secondary);
            padding: 0 1.5rem 0.5rem;
            font-weight: 400;
        }

        .premium-table tbody tr {
            background: #0f1012;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .premium-table tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            background: #141518;
        }

        .premium-table td {
            padding: 1.5rem;
            color: #fff;
            font-size: 0.95rem;
            border-top: 1px solid rgba(255,255,255,0.03);
            border-bottom: 1px solid rgba(255,255,255,0.03);
        }

        .premium-table td:first-child {
            border-left: 1px solid rgba(255,255,255,0.03);
            border-top-left-radius: 4px;
            border-bottom-left-radius: 4px;
            color: var(--accent-color);
            font-family: var(--font-display);
        }

        .premium-table td:last-child {
            border-right: 1px solid rgba(255,255,255,0.03);
            border-top-right-radius: 4px;
            border-bottom-right-radius: 4px;
        }

        /* STATUS BADGES */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border: 1px solid;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 50px;
        }

        .status-badge.ok { border-color: #20c997; color: #20c997; background: rgba(32,201,151,0.05); }
        .status-badge.danger { border-color: #ff5c5c; color: #ff5c5c; background: rgba(255,92,92,0.05); }

        /* BUTTONS */
        .action-btn {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff;
            padding: 8px 16px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            border-color: var(--accent-color);
            color: var(--accent-color);
            box-shadow: 0 0 15px rgba(41, 121, 255, 0.2);
        }

        .refresh-btn {
            background: transparent;
            border: none;
            color: var(--accent-color);
            font-size: 0.9rem;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-left: 1rem;
        }
        
        .refresh-btn:hover { color: #fff; }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }

        .sub-text {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-left: 1rem;
        }

        .contact-stack div { line-height: 1.4; }
        .contact-stack small { color: var(--text-secondary); }

    </style>
</head>
<body>
    <div class="cursor-dot"></div>
	<div class="cursor-outline"></div>

    <nav class="admin-nav">
        <a href="index.html" class="logo">REAL<span class="text-accent">.</span></a>
        <div>
            <span class="user-chip">Logged in as <?php echo htmlspecialchars($adminName); ?></span>
            <a href="admin_login.php?action=logout" class="btn-glow" style="padding: 0.8rem 2rem; font-size: 0.8rem;">Logout</a>
        </div>
    </nav>

    <div class="dashboard-grid">
        
        <section>
            <div class="header-row">
                <div>
                    <span class="section-label">01 / Schedule</span>
                    <h2 class="big-heading" style="font-size: 2.5rem; margin-bottom: 0;">Bookings</h2>
                </div>
                <div>
                    <span id="bookingStatus" style="font-size: 0.8rem; color: #888;"></span>
                    <button id="refreshBookings" class="refresh-btn"><i class="fa fa-sync"></i> Refresh</button>
                </div>
            </div>

            <div class="table-container">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date & Time</th>
                            <th>Barber</th>
                            <th>Service</th>
                            <th>Client Info</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody id="bookingsBody">
                        <tr><td colspan="6" style="text-align:center; color: #555;">Loading secure data...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section>
            <div class="header-row">
                <div>
                    <span class="section-label">02 / Reputation</span>
                    <h2 class="big-heading" style="font-size: 2.5rem; margin-bottom: 0;">Reviews</h2>
                </div>
                <div>
                    <span id="reviewStatus" style="font-size: 0.8rem; color: #888;"></span>
                    <button id="refreshReviews" class="refresh-btn"><i class="fa fa-sync"></i> Refresh</button>
                </div>
            </div>

            <div class="table-container">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client Name</th>
                            <th>Rating</th>
                            <th width="30%">Comment</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="reviewsBody">
                        <tr><td colspan="6" style="text-align:center; color: #555;">Loading secure data...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

    </div>

    <script>
        const bookingsBody = document.getElementById('bookingsBody');
        const reviewsBody = document.getElementById('reviewsBody');
        const bookingStatus = document.getElementById('bookingStatus');
        const reviewStatus = document.getElementById('reviewStatus');

        // Cursor Logic
        const cursorDot = document.querySelector('.cursor-dot');
		const cursorOutline = document.querySelector('.cursor-outline');
		window.addEventListener('mousemove', (e) => {
			cursorDot.style.left = `${e.clientX}px`;
			cursorDot.style.top = `${e.clientY}px`;
			cursorOutline.animate({ left: `${e.clientX}px`, top: `${e.clientY}px` }, { duration: 500, fill: "forwards" });
		});

        const setStatus = (el, message) => {
            if (!el) return;
            el.textContent = message;
        };

        const formatDateTime = (dateStr, timeStr) => {
            const joined = timeStr ? `${dateStr}T${timeStr}` : dateStr;
            const date = new Date(joined);
            return isNaN(date.getTime()) ? `${dateStr} ${timeStr || ''}`.trim() : date.toLocaleString();
        };

        async function loadBookings() {
            setStatus(bookingStatus, 'Syncing...');
            try {
                const res = await fetch('admin_bookings.php');
                const data = await res.json();
                if (!res.ok || data.status !== 'success') throw new Error(data.message);
                renderBookings(data.bookings || []);
                setStatus(bookingStatus, `Last synced: ${new Date().toLocaleTimeString()}`);
            } catch (error) {
                console.error(error);
                bookingsBody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:#ff5c5c;">Error: ${error.message}</td></tr>`;
            }
        }

        function renderBookings(bookings) {
            bookingsBody.innerHTML = '';
            if (!bookings.length) {
                bookingsBody.innerHTML = '<tr><td colspan="6" style="text-align:center;">No bookings found.</td></tr>';
                return;
            }

            bookings.forEach((b, idx) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>#${b.id ?? idx + 1}</td>
                    <td>
                        <div style="font-weight:600; color:#fff;">${b.date || ''}</div>
                        <div style="color:var(--accent-color); font-size:0.85rem;">${b.time || ''}</div>
                    </td>
                    <td>${b.barber_name || '<span style="opacity:0.5">Any</span>'}</td>
                    <td>${b.service_name || ''}</td>
                    <td>
                        <div class="contact-stack">
                            <div>${b.client_name || 'Guest'}</div>
                            <small>${b.client_mobile || ''}</small>
                        </div>
                    </td>
                    <td style="opacity:0.5; font-size:0.8rem;">${formatDateTime(b.created_at || b.date, null)}</td>
                `;
                bookingsBody.appendChild(row);
            });
        }

        async function loadReviews() {
            setStatus(reviewStatus, 'Syncing...');
            try {
                const res = await fetch('review.php?action=adminReviews');
                const data = await res.json();
                if (!res.ok || data.status !== 'success') throw new Error(data.message);
                renderReviews(data.reviews || []);
                setStatus(reviewStatus, `Last synced: ${new Date().toLocaleTimeString()}`);
            } catch (error) {
                console.error(error);
                reviewsBody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:#ff5c5c;">Error: ${error.message}</td></tr>`;
            }
        }

        function renderReviews(reviews) {
            reviewsBody.innerHTML = '';
            if (!reviews.length) {
                reviewsBody.innerHTML = '<tr><td colspan="6" style="text-align:center;">No reviews yet.</td></tr>';
                return;
            }

            reviews.forEach((r, idx) => {
                const isHidden = Number(r.hidden) === 1;
                const row = document.createElement('tr');
                if(isHidden) row.style.opacity = '0.4';
                
                row.innerHTML = `
                    <td>#${r.id ?? idx + 1}</td>
                    <td style="font-weight:600;">${r.name || ''}</td>
                    <td style="color:#ffd700; letter-spacing:2px;">${'★'.repeat(r.rating || 0)}</td>
                    <td style="font-style:italic; color:#aaa;">"${r.comment ? r.comment : 'No comment'}"</td>
                    <td>${isHidden ? '<span class="status-badge danger">Hidden</span>' : '<span class="status-badge ok">Live</span>'}</td>
                    <td><button class="action-btn" data-id="${r.id}" data-hidden="${isHidden ? 0 : 1}">${isHidden ? 'Unhide' : 'Hide'}</button></td>
                `;
                reviewsBody.appendChild(row);
            });

            reviewsBody.querySelectorAll('button[data-id]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = Number(btn.dataset.id);
                    const hidden = Number(btn.dataset.hidden);
                    updateReviewVisibility(id, hidden);
                });
            });
        }

        async function updateReviewVisibility(id, hidden) {
            try {
                const res = await fetch('review.php?action=setVisibility', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, hidden })
                });
                const data = await res.json();
                if (!res.ok || data.status !== 'success') throw new Error(data.message);
                await loadReviews();
            } catch (error) {
                alert('Error updating visibility');
            }
        }

        document.getElementById('refreshBookings')?.addEventListener('click', loadBookings);
        document.getElementById('refreshReviews')?.addEventListener('click', loadReviews);
        
        loadBookings();
        loadReviews();
    </script>
</body>
</html>