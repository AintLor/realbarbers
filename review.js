document.addEventListener('DOMContentLoaded', function() {
    const leaveReviewBtn = document.getElementById('leaveReviewBtn');
    const closeReviewBtn = document.getElementById('closeReview');
    const reviewPopup = document.getElementById('reviewPopup');
    const reviewForm = document.getElementById('reviewForm');
    const root = document.documentElement;
    const body = document.body;
    const nameInput = document.getElementById('name');
    const ratingInput = document.getElementById('rating');
    const nameError = document.getElementById('nameError');
    const ratingError = document.getElementById('ratingError');
    const reviewStatus = document.getElementById('reviewStatus');
    const reviewsList = document.getElementById('reviewsList');
    const showFullReviewsBtn = document.getElementById('showFullReviews');
    const sortDropdown = document.getElementById('sortDropdown');
    const stars = document.querySelectorAll('.star-rating .star');

    let reviews = [];
    let isFullReviewsVisible = false;
    const recentReviewsContainer = document.getElementById('recent-reviews');
    let recentReviewsData = [];
    let recentIndex = 0;

    function setStatus(message, type = '') {
        if (!reviewStatus) return;
        reviewStatus.textContent = message;
        reviewStatus.className = `review-status ${type}`.trim();
    }

    function updateStars(value) {
        if (!stars.length) return;
        stars.forEach((star) => {
            const starValue = parseInt(star.dataset.rating, 10);
            star.classList.toggle('active', starValue <= value);
        });
    }

    // Function to update rating bars
    function updateRatingBars(stats) {
        Object.entries(stats || {}).forEach(([rating, percentage]) => {
            const progressBar = document.querySelector(`.rating-bar:nth-child(${6 - rating}) .progress`);
            if (progressBar) {
                progressBar.style.width = `${percentage}%`;
                progressBar.style.transition = 'width 0.5s ease-in-out';
            }
        });
    }

    function lockScroll() {
        root.classList.add('no-scroll');
        body.classList.add('no-scroll');
    }

    function unlockScroll() {
        root.classList.remove('no-scroll');
        body.classList.remove('no-scroll');
    }

    function openReviewPopup() {
        if (!reviewPopup) return;
        reviewPopup.style.display = 'flex';
        lockScroll();
        const current = ratingInput ? parseInt(ratingInput.value || '0', 10) : 0;
        updateStars(current);
    }

    function closeReviewPopup() {
        if (!reviewPopup) return;
        reviewPopup.style.display = 'none';
        unlockScroll();
    }

    stars.forEach((star) => {
        const starValue = parseInt(star.dataset.rating, 10);
        star.addEventListener('mouseenter', () => updateStars(starValue));
        star.addEventListener('mouseleave', () => {
            const current = ratingInput ? parseInt(ratingInput.value || '0', 10) : 0;
            updateStars(current);
        });
        star.addEventListener('click', () => {
            if (ratingInput) {
                ratingInput.value = starValue;
            }
            updateStars(starValue);
            if (ratingError) {
                ratingError.textContent = '';
            }
        });
    });

    if (leaveReviewBtn && reviewPopup) {
        leaveReviewBtn.addEventListener('click', function() {
            openReviewPopup();
        });
    }

    if (closeReviewBtn && reviewPopup) {
        closeReviewBtn.addEventListener('click', function() {
            closeReviewPopup();
        });
    }

    if (reviewPopup) {
        reviewPopup.addEventListener('click', function(e) {
            if (e.target === reviewPopup) {
                closeReviewPopup();
            }
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && reviewPopup.style.display === 'flex') {
                closeReviewPopup();
            }
        });
    }

    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (nameError) {
                nameError.textContent = '';
            }
            if (ratingError) {
                ratingError.textContent = '';
            }

            if (nameInput && nameInput.value.trim() === '') {
                if (nameError) {
                    nameError.textContent = 'Please enter your name.';
                }
                return;
            }

            const ratingValue = ratingInput ? ratingInput.value : '';
            if (ratingValue === '' || parseInt(ratingValue, 10) < 1 || parseInt(ratingValue, 10) > 5) {
                if (ratingError) {
                    ratingError.textContent = 'Select a rating between 1 and 5 stars.';
                }
                return;
            }

            const formData = new FormData(reviewForm);

            fetch('review.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        setStatus('Thank you for your review!', 'success');

                        if (data.stats) {
                            updateRatingBars(data.stats);
                        }

                        reviewForm.reset();
                        if (ratingInput) {
                            ratingInput.value = '';
                        }
                        updateStars(0);

                        setTimeout(() => {
                            closeReviewPopup();
                            setStatus('', '');
                        }, 2000);

                        loadReviews();
                    } else {
                        setStatus('Error: ' + (data.message || 'Failed to submit review'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    setStatus('An error occurred. Please try again.', 'error');
                });
        });
    }

    function loadReviews() {
        fetch('review.php?action=getReviews')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                reviews = data.reviews || [];
                updateRatingBars(data.stats || {});
                displayReviews(reviews);
                renderRecentReviews();
            })
            .catch(error => {
                console.error('Error loading reviews:', error);
                setStatus('An error occurred while loading reviews.', 'error');
            });
    }

    function displayReviews(reviewsToDisplay) {
        if (!reviewsList) {
            return;
        }
        reviewsList.innerHTML = ''; // Clear previous reviews
        reviewsToDisplay.forEach(review => {
            const reviewElement = document.createElement('div');
            reviewElement.className = 'review-item';
            const date = new Date(review.created_at).toLocaleDateString();
            reviewElement.innerHTML = `
                <div class="review-header">
                    <strong>${review.name}</strong>
                    <span class="review-date">${date}</span>
                </div>
                <div class="rating">
                    ${'★'.repeat(review.rating)}${'☆'.repeat(5 - review.rating)}
                </div>
                ${review.comment ? `<p class="review-comment">${review.comment}</p>` : ''}
            `;
            reviewsList.appendChild(reviewElement);
        });
    }

    function renderRecentReviews() {
        if (!recentReviewsContainer) return;
        recentReviewsData = (reviews || []).slice(0, 3);
        if (!recentReviewsData.length) {
            recentReviewsContainer.style.display = 'none';
            return;
        }
        recentReviewsContainer.style.display = 'block';
        recentIndex = Math.min(recentIndex, recentReviewsData.length - 1);

        recentReviewsContainer.innerHTML = `
            <div class="recent-carousel">
                <div class="recent-track"></div>
            </div>
        `;

        const track = recentReviewsContainer.querySelector('.recent-track');
        const len = recentReviewsData.length;
        track.classList.add('shuffle');
        setTimeout(() => track.classList.remove('shuffle'), 380);

        const buildCard = (review, role, idx) => {
            const date = review.created_at ? new Date(review.created_at).toLocaleDateString() : '';
            const card = document.createElement('div');
            card.className = `recent-review-card ${role}`;
            card.dataset.idx = String(idx);
            const rawComment = review.comment && review.comment.trim() ? review.comment.trim() : 'Great experience, highly recommended.';
            const comment = rawComment.length > 150 ? `${rawComment.slice(0, 150)}…` : rawComment;
            const author = review.name || 'Guest';
            card.innerHTML = `
                <p class="recent-review-comment">&ldquo;${comment}&rdquo;</p>
                <div class="recent-review-stars">${'★'.repeat(review.rating || 0)}${'☆'.repeat(Math.max(0, 5 - (review.rating || 0)))}</div>
                <div class="recent-review-author">${author.toUpperCase()}</div>
                ${date ? `<div class="recent-review-role">${date}</div>` : ''}
            `;
            return card;
        };

        const visible = [];
        if (len === 1) {
            visible.push({ idx: recentIndex, role: 'is-center' });
        } else if (len === 2) {
            visible.push({ idx: recentIndex, role: 'is-center' });
            visible.push({ idx: (recentIndex + 1) % len, role: 'is-right' });
        } else {
            visible.push({ idx: (recentIndex - 1 + len) % len, role: 'is-left' });
            visible.push({ idx: recentIndex, role: 'is-center' });
            visible.push({ idx: (recentIndex + 1) % len, role: 'is-right' });
        }

        visible.forEach(item => {
            const card = buildCard(recentReviewsData[item.idx], item.role, item.idx);
            card.addEventListener('click', () => {
                recentIndex = item.idx;
                renderRecentReviews();
            });
            track.appendChild(card);
        });

        // Swipe / scrollable experience: allow wheel/drag to update active
        let startX = null;
        track.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
        });
        track.addEventListener('touchmove', (e) => {
            if (startX === null) return;
            const delta = e.touches[0].clientX - startX;
            if (Math.abs(delta) > 40) {
                recentIndex = delta > 0 ? (recentIndex - 1 + len) % len : (recentIndex + 1) % len;
                startX = null;
                renderRecentReviews();
            }
        });
        track.addEventListener('touchend', () => { startX = null; });
        track.addEventListener('wheel', (e) => {
            if (Math.abs(e.deltaX) < Math.abs(e.deltaY)) return;
            e.preventDefault();
            recentIndex = e.deltaX > 0 ? (recentIndex + 1) % len : (recentIndex - 1 + len) % len;
            renderRecentReviews();
        }, { passive: false });
    }

    // Sorting functionality with dropdown
    if (sortDropdown) {
        sortDropdown.addEventListener('change', () => {
            const sortValue = sortDropdown.value;

            let sortedReviews;
            if (sortValue === 'highest') {
                sortedReviews = [...reviews].sort((a, b) => b.rating - a.rating);
            } else if (sortValue === 'lowest') {
                sortedReviews = [...reviews].sort((a, b) => a.rating - b.rating);
            } else if (sortValue) { // Check if a specific star rating is selected
                sortedReviews = reviews.filter(review => review.rating === parseInt(sortValue, 10));
            } else {
                sortedReviews = reviews; // Default to original order
            }
            displayReviews(sortedReviews);
        });
    }

    // Toggle full reviews
    if (showFullReviewsBtn) {
        showFullReviewsBtn.addEventListener('click', () => {
            isFullReviewsVisible = !isFullReviewsVisible;
            const comments = document.querySelectorAll('.review-comment');
            comments.forEach(comment => {
                comment.style.display = isFullReviewsVisible ? 'block' : 'none';
            });
            showFullReviewsBtn.textContent = isFullReviewsVisible ? 'Show Less Reviews' : 'Show Full Reviews';
        });
    }

    // Load reviews and rating statistics on page load
    loadReviews();
});
