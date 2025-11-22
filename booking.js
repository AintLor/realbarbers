document.addEventListener('DOMContentLoaded', () => {
    fetchBarbers();
    initializeDateHandlers();
});

const appointments = [];

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

const bookingForm = document.getElementById("booking-form");
if (bookingForm) {
    bookingForm.addEventListener("submit", (event) => {
        event.preventDefault();
        if (validateForm()) {
            saveAppointment();
        }
    });
}

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

function resetFormFields() {
    const fields = ['client_name', 'client_email', 'client_mobile', 'specialty', 'barber', 'date', 'time'];
    fields.forEach(field => {
        const element = document.getElementById(field);
        if (element) element.value = '';
    });
}

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

    const endpoint = appointmentId ? 'update_booking.php' : 'process_booking.php';
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
        const messageDiv = document.getElementById("response-message");
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

            const confirmationMessage = document.getElementById("confirmation-message");
            if (confirmationMessage) {
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
                confirmationMessage.style.display = 'block';
            }

            if (messageDiv) {
                messageDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
            }
        } else {
            const errorMessage = data.message || 'Unknown error occurred';
            alert('Failed to save appointment: ' + errorMessage);
            if (messageDiv) {
                messageDiv.innerHTML = `<div class="alert alert-danger">${errorMessage}</div>`;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const messageDiv = document.getElementById("response-message");
        if (messageDiv) {
            messageDiv.innerHTML = `<div class="alert alert-danger">An error occurred while submitting the form.</div>`;
        } else {
            alert('An error occurred while submitting the form.');
        }
    });
}

function renderAppointmentsList() {
    const appointmentsList = document.getElementById('appointments-list');
    if (!appointmentsList) {
        return;
    }

    appointmentsList.innerHTML = '';
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

function populateBarbers(barbers) {
    const barberSelect = document.getElementById('barber');
    if (!barberSelect) {
        return;
    }

    barberSelect.innerHTML = '<option value="" disabled selected>Select Barber</option>';
    barbers.forEach(barber => {
        const option = document.createElement('option');
        option.value = barber.name;
        option.textContent = barber.name;
        barberSelect.appendChild(option);
    });

    barberSelect.addEventListener('change', () => {
        const selectedBarber = barberSelect.value;
        const selectedDate = document.getElementById('date')?.value.trim();
        if (selectedBarber && selectedDate) {
            populateAvailableTimes(selectedBarber, selectedDate);
        }
    });
}

function populateAvailableTimes(barberName, selectedDate) {
    const timeSelect = document.getElementById('time');
    if (!timeSelect) return;

    timeSelect.innerHTML = '<option value="" disabled selected>Select Time</option>';

    const selectedDay = new Date(selectedDate).toLocaleDateString('en-US', { weekday: 'long' });
    const availableTimes = availableTimesMap[barberName]?.[selectedDay] || [];

    if (availableTimes.length === 0) {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'No available times - Barber is off';
        timeSelect.appendChild(option);
        return;
    }

    availableTimes.forEach(time => {
        const option = document.createElement('option');
        option.value = time;
        option.textContent = time;
        timeSelect.appendChild(option);
    });
}

function initializeDateHandlers() {
    const dateInput = document.getElementById('date');
    const barberSelect = document.getElementById('barber');

    if (!dateInput || !barberSelect) {
        return;
    }

    const today = new Date().toISOString().split('T')[0];
    dateInput.min = today;

    dateInput.addEventListener('change', () => {
        if (barberSelect.value) {
            populateAvailableTimes(barberSelect.value, dateInput.value);
        }
    });

    barberSelect.addEventListener('change', () => {
        if (dateInput.value) {
            populateAvailableTimes(barberSelect.value, dateInput.value);
        }
    });
}
