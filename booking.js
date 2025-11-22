document.getElementById("booking-form").addEventListener("submit", function(event) {
    console.log("checkpoint")
    event.preventDefault(); // Prevent default form submission

        // Validate fields
        if (!validateForm()) {
            return; // Stop if validation fails
        }   

    var formValid = true;
    var errorMessages = [];

    // Check for empty fields
    const fields = {
        "Full name": "client_name",
        "Email": "client_email",
        "Phone number": "client_mobile",
        "Service selection": "specialty",
        "Select Barber": "barber",
        "Preferred appointment date": "date",
        "Preferred appointment time": "time"
    };

    for (const [label, id] of Object.entries(fields)) {
        if (!document.getElementById(id)?.value) {
            errorMessages.push(`${label} is required.`);
            formValid = false;
        }
    }

    if (!formValid) {
        alert(errorMessages.join("\n"));
        return;
    }

    // Prepare data for submission
    const formData = {
        client_name: document.getElementById("client_name").value,
        client_email: document.getElementById("client_email").value,
        client_mobile: document.getElementById("client_mobile").value,
        specialty: document.getElementById("specialty").value,
        name: document.getElementById("barber").value,
        date: document.getElementById("date").value,
        time: document.getElementById("time").value
    };

 console.log("checkpoint")
 
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
        const messageDiv = document.getElementById("response-message");
        if (data.success) {
            messageDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
            // Optionally, reset the form
            document.getElementById("booking-form").reset();
        } else {
            messageDiv.innerHTML = `<div class="alert alert-danger">Error: ${data.message}</div>`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById("response-message").innerHTML = `<div class="alert alert-danger">An error occurred while submitting the form.</div>`;
    });
});

// Global array for appointments
let appointments = [];


const availableTimesMap = {
    "Barber Angelo": {
        "Sunday": ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
        "Monday": ['11:00', '14:00', '15:00', '16:00', '17:00'],
        "Tuesday": ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
        "Wednesday": [],
        "Thursday": ['11:00', '14:00', '15:00', '16:00', '17:00'],
        "Friday": ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
        "Saturday": ['11:00', '14:00', '15:00', '16:00', '17:00']
    },
    "Barber Reymart": {
        "Sunday": ['11:00', '14:00', '15:00', '16:00', '17:00'],
        "Monday": ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
        "Tuesday": [],
        "Wednesday": ['11:00', '14:00', '15:00', '16:00', '17:00'],
        "Thursday": ['11:00', '14:00', '15:00', '16:00', '17:00'],
        "Friday": ['11:00', '14:00', '15:00', '16:00', '17:00'],
        "Saturday": ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00']
    },
    "Barber Rod": {
        "Sunday": ['11:00', '14:00', '15:00', '16:00', '17:00'],
        "Monday": ['11:00', '14:00', '15:00', '16:00', '17:00'],
        "Tuesday": ['11:00', '14:00', '15:00', '16:00', '17:00'],
        "Wednesday": ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
        "Thursday": [],
        "Friday": ['11:00', '14:00', '15:00', '16:00', '17:00'],
        "Saturday": ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00']
    },
    "Barber Lyndon": {
        "Sunday": ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
        "Monday": [],
        "Tuesday": ['11:00', '14:00', '15:00', '16:00', '17:00'],
        "Wednesday": ['11:00', '14:00', '15:00', '16:00', '17:00'],
        "Thursday": ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
        "Friday": ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
        "Saturday": ['11:00', '14:00', '15:00', '16:00', '17:00']
    },
    "Barber Ed": {
        "Sunday": ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
        "Monday": ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
        "Tuesday": ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
        "Wednesday": ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
        "Thursday": ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
        "Friday": [],
        "Saturday": ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00']
    }
};

// Fetch barbers and set up event listeners when the document is loaded
document.addEventListener('DOMContentLoaded', () => {
    fetchBarbers();
    setupFormSubmission();
});

// Function to set up the form submission event listener
function setupFormSubmission() {
    const submitButton = document.getElementById("submit-btn");
    if (submitButton) {
        submitButton.addEventListener("click", handleFormSubmission);
    } else {
        console.error("Submit button not found.");
    }
}

// Event handler for form submission
function handleFormSubmission(event) {
    event.preventDefault(); // Prevent default behavior

    if (validateForm()) {
        saveAppointment();
    }
}

// Function to validate the form
function validateForm() {
    let isValid = true;
    const errorMessages = [];
    const fields = {
        "Full Name": "client_name",
        "Email Address": "client_email",
        "Phone Number": "client_mobile",
        "Select Service": "specialty",
        "Select Barber": "barber",
        "Preferred Appointment Date": "date",
        "Preferred Appointment Time": "time"
    };

    for (const [label, id] of Object.entries(fields)) {
        const value = document.getElementById(id)?.value.trim();
        if (!value) {
            errorMessages.push(`${label} is required.`);
            isValid = false;
        }
    }

    const email = document.getElementById("client_email")?.value.trim();
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email && !emailPattern.test(email)) {
        errorMessages.push("Email address is invalid.");
        isValid = false;
    }

    if (!isValid) {
        alert(errorMessages.join("\n"));
    }

    return isValid;
}

// Function to reset form fields
function resetFormFields() {
    const fields = ['client_name', 'client_email', 'client_mobile', 'specialty', 'barber', 'date', 'time'];
    fields.forEach(field => {
        const element = document.getElementById(field);
        if (element) element.value = '';
    });
}

// Function to save appointment (add or update)
function saveAppointment() {
    const appointmentId = document.getElementById('id')?.value.trim();
    const date = document.getElementById('date')?.value.trim();
    const time = document.getElementById('time')?.value.trim();
    const barberName = document.getElementById('barber')?.value.trim();
    const specialty = document.getElementById('specialty')?.value.trim();
    const clientName = document.getElementById('client_name')?.value.trim();
    const clientEmail = document.getElementById('client_email')?.value.trim();
    const clientMobile = document.getElementById('client_mobile')?.value.trim();

    const isDoubleBooked = appointments.some(app => 
        app.date === date && app.time === time && app.name === barberName && app.id !== appointmentId
    );

    if (isDoubleBooked) {
        alert(`The selected barber is already booked on ${date} at ${time}. Please choose another time.`);
        return;
    }

    const endpoint = appointmentId ? 'update_booking.php' : 'http://localhost/PROG%20management/barber-website/process_booking.php';
    const requestBody = {
        id: appointmentId,
        date,
        time,
        name: barberName,
        specialty,
        client_name: clientName,
        client_email: clientEmail,
        client_mobile: clientMobile
    };

    fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(requestBody)
    })
    .then(async response => {
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.indexOf("application/json") !== -1) {
            return response.json();
        } else {
            const text = await response.text();
            throw new Error(`Server returned non-JSON response: ${text}`);
        }
    })
    .then(data => {
        if (data.success) {
            if (appointmentId) {
                const index = appointments.findIndex(app => app.id === appointmentId);
                if (index !== -1) {
                    appointments[index] = { ...appointments[index], ...data.appointment };
                }
            } else {
                appointments.push(data.appointment);
            }
            renderAppointmentsList();
            resetFormFields();

            // Log the values to check if they are correct
            console.log(`Client: ${clientName}, Barber: ${barberName}, Date: ${date}, Time: ${time}`);

            // Show confirmation message
            const confirmationMessage = document.getElementById("confirmation-message");
            confirmationMessage.innerHTML = `
                <div class="alert alert-success">
                    Appointment booked successfully!<br>
                    Client: ${clientName}<br>
                    Barber: ${barberName}<br>
                    Date: ${date}<br>
                    Time: ${time}<br>
                    Service: ${specialty}<br>
                    please take a screenshot and present this to the receptionist on the day of your appointment.
                </div>
            `;
            confirmationMessage.style.display = 'block'; // Show the message
        } else {
            const errorMessage = data.message || 'Unknown error occurred';
            alert('Failed to save appointment: ' + errorMessage);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving the appointment. Please try again later.');
    });
}

// Function to render the appointments list
function renderAppointmentsList() {
    const appointmentsList = document.getElementById('appointments-list');
    if (appointmentsList) {
        appointmentsList.innerHTML = ''; // Clear previous entries
        appointments.forEach(appointment => {
            const appointmentDiv = document.createElement('div');
            appointmentDiv.classList.add('appointment-entry');

            appointmentDiv.innerHTML = `
                <div class="appointment-details">
                    <span>${appointment.date} ${appointment.time} - ${appointment.specialty} with ${appointment.name} (Client: ${appointment.client_name}, Email: ${appointment.client_email}, Mobile: ${appointment.client_mobile})</span>
                </div>
            `;
            appointmentsList.appendChild(appointmentDiv);
        });
    }
}

// Fetch barbers from the server
function fetchBarbers() {
    fetch('get_barbers.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch barbers.');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && Array.isArray(data.barbers)) {
                populateBarbers(data.barbers);
            } else {
                alert('Failed to load barbers.');
            }
        })
        .catch(error => {
            console.error('Error fetching barbers:', error);
            alert('An error occurred while fetching barbers.');
        });
}

// Populate barber dropdown
function populateBarbers(barbers) {
    const barberSelect = document.getElementById('barber');
    if (barberSelect) {
        barberSelect.innerHTML = '<option value="" disabled selected>Select Barber</option>';
        barbers.forEach(barber => {
            const option = document.createElement('option');
            option.value = barber.name;
            option.textContent = barber.name;
            barberSelect.appendChild(option);
        });

        // Add event listener for barber selection
        barberSelect.addEventListener('change', () => {
            const selectedBarber = barberSelect.value;
            const selectedDate = document.getElementById('date')?.value.trim();
            if (selectedBarber && selectedDate) {
                populateAvailableTimes(selectedBarber, selectedDate);
            }
        });
    }
}

// Function to populate available times for a selected barber on a specific date
function populateAvailableTimes(barberName, selectedDate) {
    const timeSelect = document.getElementById('time');
    if (!timeSelect) return;

    // Clear existing options
    timeSelect.innerHTML = '<option value="" disabled selected>Select Time</option>';

    // Convert the selected date to a day of the week
    const selectedDay = new Date(selectedDate).toLocaleDateString('en-US', { weekday: 'long' });
    console.log(`Fetching available times for ${barberName} on ${selectedDay}`); // Debug line

    // Get available times for the selected barber and day
    const availableTimes = availableTimesMap[barberName]?.[selectedDay] || [];

    // If no times available for this day
    if (availableTimes.length === 0) {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'No available times - Barber is off';
        timeSelect.appendChild(option);
        return;
    }

    // Add available times as options
    availableTimes.forEach(time => {
        const option = document.createElement('option');
        option.value = time;
        option.textContent = time;
        timeSelect.appendChild(option);
    });

    console.log(`Available times for ${barberName} on ${selectedDay}:`, availableTimes); // Debug line
}

// Add event listeners for date and barber selection
document.addEventListener('DOMContentLoaded', () => {
    const dateInput = document.getElementById('date');
    const barberSelect = document.getElementById('barber');

    if (dateInput && barberSelect) {
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        dateInput.min = today;

        // Listen for date changes
        dateInput.addEventListener('change', () => {
            if (barberSelect.value) {
                populateAvailableTimes(barberSelect.value, dateInput.value);
            }
        });

        // Listen for barber changes
        barberSelect.addEventListener('change', () => {
            if (dateInput.value) {
                populateAvailableTimes(barberSelect.value, dateInput.value);
            }
        });
    } else {
        console.error("Date input or barber select not found."); // Debug line
    }
});