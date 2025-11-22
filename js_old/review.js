
document.addEventListener('DOMContentLoaded', function() {
    const leaveReviewBtn = document.getElementById('leaveReviewBtn');
    const reviewPopup = document.getElementById('reviewPopup');
    const reviewForm = document.getElementById('reviewForm');
    const nameInput = document.getElementById('name');
    const ratingInput = document.getElementById('rating');
    const stars = document.querySelectorAll('.star');
    const nameError = document.getElementById('nameError');
    const ratingError = document.getElementById('ratingError');

    leaveReviewBtn.addEventListener('click', function() {
        reviewPopup.style.display = 'block';
    });

    reviewPopup.addEventListener('click', function(e) {
        if (e.target === reviewPopup) {
            reviewPopup.style.display = 'none';
        }
    });

    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = this.getAttribute('data-rating');
            ratingInput.value = rating;
            stars.forEach(s => s.classList.remove('active'));
            for (let i = 0; i < rating; i++) {
                stars[i].classList.add('active');
            }
        });
    });

    reviewForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Reset error messages
        nameError.textContent = '';
        ratingError.textContent = '';

        // Validate name
        if (nameInput.value.trim() === '') {
            nameError.textContent = 'Please enter your name';
            return;
        }

        // Validate rating
        if (ratingInput.value === '') {
            ratingError.textContent = 'Please select a rating';
            return;
        }

        // If validation passes, submit the form
        this.submit();
    });
});