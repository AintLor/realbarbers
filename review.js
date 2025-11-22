document.addEventListener('DOMContentLoaded', function() {
    const leaveReviewBtn = document.getElementById('leaveReviewBtn');
    const closeReviewBtn = document.getElementById('closeReview');
    const reviewPopup = document.getElementById('reviewPopup');
    const reviewForm = document.getElementById('reviewForm');
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
            reviewPopup.style.display = 'flex';
            const current = ratingInput ? parseInt(ratingInput.value || '0', 10) : 0;
            updateStars(current);
        });
    }

    if (closeReviewBtn && reviewPopup) {
        closeReviewBtn.addEventListener('click', function() {
            reviewPopup.style.display = 'none';
        });
    }

    if (reviewPopup) {
        reviewPopup.addEventListener('click', function(e) {
            if (e.target === reviewPopup) {
                reviewPopup.style.display = 'none';
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
                            if (reviewPopup) {
                                reviewPopup.style.display = 'none';
                            }
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
