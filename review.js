document.addEventListener('DOMContentLoaded', function() {
    const leaveReviewBtn = document.getElementById('leaveReviewBtn');
    const reviewPopup = document.getElementById('reviewPopup');
    const reviewForm = document.getElementById('reviewForm');
    const nameInput = document.getElementById('name');
    const ratingInput = document.getElementById('rating');
    const nameError = document.getElementById('nameError');
    const ratingError = document.getElementById('ratingError');
    const reviewStatus = document.getElementById('reviewStatus');
    const reviewsList = document.getElementById('reviewsList');
    const showFullReviewsBtn = document.getElementById('showFullReviews');
    const sortDropdown = document.getElementById('sortDropdown'); // Dropdown for sorting

    let reviews = [];
    let isFullReviewsVisible = false;

    // Function to update rating bars
    function updateRatingBars(stats) {
        Object.entries(stats).forEach(([rating, percentage]) => {
            const progressBar = document.querySelector(`.rating-bar:nth-child(${6 - rating}) .progress`);
            if (progressBar) {
                progressBar.style.width = `${percentage}%`;
                progressBar.style.transition = 'width 0.5s ease-in-out';
            }
        });
    }

    leaveReviewBtn.addEventListener('click', function() {
        reviewPopup.style.display = 'block';
    });

    reviewPopup.addEventListener('click', function(e) {
        if (e.target === reviewPopup) {
            reviewPopup.style.display = 'none';
        }
    });

    reviewForm.addEventListener('submit', function(e) {
        e.preventDefault();
        nameError.textContent = '';
        ratingError.textContent = '';

        if (nameInput.value.trim() === '') {
            nameError.textContent = 'Please enter your name.';
            return;
        }

        const ratingValue = ratingInput.value;
        if (ratingValue === '' || parseInt(ratingValue) < 1 || parseInt(ratingValue) > 5) {
            ratingError.textContent = 'Please select a valid rating (1-5).';
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
                reviewStatus.textContent = 'Thank you for your review!';
                reviewStatus.className = 'success';

                if (data.stats) {
                    updateRatingBars(data.stats);
                }

                reviewForm.reset();
                ratingInput.value = ''; // Reset the rating dropdown

                setTimeout(() => {
                    reviewPopup.style.display = 'none';
                    reviewStatus.textContent = '';
                }, 2000);

                loadReviews();
            } else {
                reviewStatus.textContent = 'Error: ' + (data.message || 'Failed to submit review');
                reviewStatus.className = 'error';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            reviewStatus.textContent = 'An error occurred. Please try again.';
            reviewStatus.className = 'error';
        });
    });

    function loadReviews() {
        fetch('review.php?action=getReviews')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            reviews = data.reviews; // Store the reviews for sorting
            updateRatingBars(data.stats);
            displayReviews(reviews);
        })
        .catch(error => {
            console.error('Error loading reviews:', error);
            reviewStatus.textContent = 'An error occurred while loading reviews.';
            reviewStatus.className = 'error';
        });
    }

    function displayReviews(reviewsToDisplay) {
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
    sortDropdown.addEventListener('change', () => {
        const sortValue = sortDropdown.value;

        let sortedReviews;
        if (sortValue === 'highest') {
            sortedReviews = [...reviews].sort((a, b) => b.rating - a.rating);
        } else if (sortValue === 'lowest') {
            sortedReviews = [...reviews].sort((a, b) => a.rating - b.rating);
        } else if (sortValue) { // Check if a specific star rating is selected
            sortedReviews = reviews.filter(review => review.rating === parseInt(sortValue));
        } else {
            sortedReviews = reviews; // Default to original order
        }
        displayReviews(sortedReviews);
    });

    // Toggle full reviews
    showFullReviewsBtn.addEventListener('click', () => {
        isFullReviewsVisible = !isFullReviewsVisible;
        const comments = document.querySelectorAll('.review-comment');
        comments.forEach(comment => {
            comment.style.display = isFullReviewsVisible ? 'block' : 'none';
        });
        showFullReviewsBtn.textContent = isFullReviewsVisible ? 'Show Less Reviews' : 'Show Full Reviews';
    });

    // Load reviews and rating statistics on page load
    loadReviews();
});