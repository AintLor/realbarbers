document.getElementById("booking-form").addEventListener("submit", function(event) {
    var formValid = true;
    var errorMessages = [];

    // Check for empty fields
    if (!document.getElementById("name").value) {
        errorMessages.push("Full name is required.");
        formValid = false;
    }
    if (!document.getElementById("email").value) {
        errorMessages.push("Email is required.");
        formValid = false;
    }
    if (!document.getElementById("phone").value) {
        errorMessages.push("Phone number is required.");
        formValid = false;
    }
    if (!document.getElementById("service").value) {
        errorMessages.push("Service selection is required.");
        formValid = false;
    }
    if (!document.getElementById("barber").value) {
        errorMessages.push("Please select a barber.");
        formValid = false;
    }
    if (!document.getElementById("appointment_date").value) {
        errorMessages.push("Preferred appointment date is required.");
        formValid = false;
    }
    if (!document.getElementById("appointment_time").value) {
        errorMessages.push("Preferred appointment time is required.");
        formValid = false;
    }

    if (!formValid) {
        event.preventDefault(); // Prevent form submission
        alert(errorMessages.join("\n"));
    } else {
        event.preventDefault(); // Prevent default form submission

        // Prepare data for submission
        const formData = {
            name: document.getElementById("name").value,
            email: document.getElementById("email").value,
            phone: document.getElementById("phone").value,
            service: document.getElementById("service").value,
            barber: document.getElementById("barber").value,
            appointment_date: document.getElementById("appointment_date").value,
            appointment_time: document.getElementById("appointment_time").value
        };

        // Send data to the server
        fetch('process_booking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                // Optionally, redirect or reset the form
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("An error occurred while submitting the form.");
        });
    }
});
