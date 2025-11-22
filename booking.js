document.addEventListener('DOMContentLoaded', () => {
    fetchBarbers();
    initializeDateHandlers();
    initializeBookingModal();
    initializeCaptchaModal();
});

const appointments = [];
let barbersCache = [];
let captchaModal = null;
let captchaQuestionEl = null;
let captchaAnswerInput = null;
let captchaSubmitBtn = null;
let captchaCloseEls = [];
let captchaResolve = null;
let captchaReject = null;
let captchaForm = null;

function formatTime12h(time24) {
    const [hourStr, minuteStr = '00'] = time24.split(':');
    const hour = parseInt(hourStr, 10);
    if (Number.isNaN(hour)) {
        return time24;
    }
    const hour12 = ((hour + 11) % 12) + 1;
    const minutes = minuteStr.padStart(2, '0');
    const period = hour >= 12 ? 'PM' : 'AM';
    return `${hour12}:${minutes} ${period}`;
}

let bookingModalElement = null;

function initializeBookingModal() {
    bookingModalElement = document.getElementById('booking-modal');
    if (!bookingModalElement) return;

    const closeTriggers = bookingModalElement.querySelectorAll('[data-close-modal]');
    closeTriggers.forEach(el => el.addEventListener('click', closeBookingModal));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && bookingModalElement.classList.contains('is-open')) {
            closeBookingModal();
        }
    });
}

function openBookingModal(contentHtml) {
    if (!bookingModalElement) return false;
    const body = document.getElementById('booking-modal-body');
    if (!body) return false;

    body.innerHTML = contentHtml;
    bookingModalElement.classList.add('is-open');
    bookingModalElement.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
    return true;
}

function closeBookingModal() {
    if (!bookingModalElement) return;
    bookingModalElement.classList.remove('is-open');
    bookingModalElement.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
}

function showBookingConfirmation({ clientName, barberName, date, time, specialty }) {
    const formattedTime = formatTime12h(time);
    const modalContent = `
        <ul class="booking-modal__details">
            <li><span>Client</span><strong>${clientName}</strong></li>
            <li><span>Barber</span><strong>${barberName}</strong></li>
            <li><span>Date</span><strong>${date}</strong></li>
            <li><span>Time</span><strong>${formattedTime}</strong></li>
            <li><span>Service</span><strong>${specialty}</strong></li>
        </ul>
        <p class="booking-modal__note">Please take a screenshot and present this to the receptionist on the day of your appointment.</p>
    `;

    const opened = openBookingModal(modalContent);
    if (!opened) {
        const confirmationMessage = document.getElementById("confirmation-message");
        if (confirmationMessage) {
            confirmationMessage.innerHTML = `
                <div class="alert alert-success">
                    Appointment booked successfully!<br>
                    Client: ${clientName}<br>
                    Barber: ${barberName}<br>
                    Date: ${date}<br>
                    Time: ${formattedTime}<br>
                    Service: ${specialty}<br>
                    Please take a screenshot and present this to the receptionist on the day of your appointment.
                </div>
            `;
            confirmationMessage.style.display = 'block';
        }
    }
}

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
    const barberSelect = document.getElementById('barber');
    const barberId = barberSelect?.value.trim();
    const barberName = barberSelect?.options[barberSelect.selectedIndex]?.dataset.name || '';
    const specialty = document.getElementById('specialty')?.value.trim();
    const clientName = document.getElementById('client_name')?.value.trim();
    const clientEmail = document.getElementById('client_email')?.value.trim();
    const clientMobile = document.getElementById('client_mobile')?.value.trim();

    const isDoubleBooked = appointments.some(app =>
        app.date === date && app.time === time && app.barber_id === barberId && app.id !== appointmentId
    );

    if (isDoubleBooked) {
        alert(`The selected barber is already booked on ${date} at ${formatTime12h(time)}. Please choose another time.`);
        return;
    }

    const endpoint = appointmentId ? 'update_booking.php' : 'process_booking.php';
    const requestBody = {
        id: appointmentId,
        date,
        time,
        name: barberName,
        barber_id: barberId,
        specialty,
        client_name: clientName,
        client_email: clientEmail,
        client_mobile: clientMobile
    };

    requestCaptcha('booking')
    .then(captchaAnswer => {
        requestBody.captcha_answer = captchaAnswer;
        return fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(requestBody)
        });
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

            showBookingConfirmation({ clientName, barberName, date, time, specialty });

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
        if (error?.message === 'Captcha cancelled') {
            // user cancelled; no alert
            return;
        }
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
                <span>${appointment.date} ${formatTime12h(appointment.time)} - ${appointment.specialty} with ${appointment.name} (Client: ${appointment.client_name}, Email: ${appointment.client_email}, Mobile: ${appointment.client_mobile})</span>
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
                barbersCache = data.barbers;
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
        option.value = barber.id;
        option.textContent = barber.name;
        option.dataset.name = barber.name;
        barberSelect.appendChild(option);
    });

    barberSelect.addEventListener('change', () => {
        const selectedBarberId = barberSelect.value;
        const selectedDate = document.getElementById('date')?.value.trim();
        if (selectedBarberId && selectedDate) {
            populateAvailableTimes(selectedBarberId, selectedDate);
        }
    });
}

function populateAvailableTimes(barberId, selectedDate) {
    const timeSelect = document.getElementById('time');
    if (!timeSelect) return;

    timeSelect.innerHTML = '<option value="" disabled selected>Select Time</option>';

    if (!barberId || !selectedDate) {
        return;
    }

    fetch(`get_availability.php?barber_id=${encodeURIComponent(barberId)}&date=${encodeURIComponent(selectedDate)}`)
        .then(response => response.json())
        .then(data => {
            const availableTimes = data.available_times || [];
            if (!data.success || availableTimes.length === 0) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'No available times';
                timeSelect.appendChild(option);
                return;
            }

            availableTimes.forEach(time => {
                const option = document.createElement('option');
                option.value = time;
                option.textContent = formatTime12h(time);
                timeSelect.appendChild(option);
            });
        })
        .catch(() => {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Unable to load times';
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

function initializeCaptchaModal() {
    captchaModal = document.getElementById('captcha-modal');
    captchaQuestionEl = document.getElementById('captcha-question');
    captchaAnswerInput = document.getElementById('captcha-answer');
    captchaSubmitBtn = document.getElementById('captcha-submit');
    captchaCloseEls = Array.from(document.querySelectorAll('[data-captcha-close]'));

    captchaCloseEls.forEach(el => el.addEventListener('click', cancelCaptchaModal));
    captchaSubmitBtn?.addEventListener('click', submitCaptchaModal);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && captchaModal?.classList.contains('is-open')) {
            cancelCaptchaModal();
        }
    });
}

function requestCaptcha(form) {
    return new Promise((resolve, reject) => {
        captchaForm = form;
        captchaResolve = resolve;
        captchaReject = reject;
        fetch(`captcha.php?form=${encodeURIComponent(form)}`)
            .then(res => res.json())
            .then(data => {
                if (captchaQuestionEl) {
                    captchaQuestionEl.textContent = data.question || 'Solve to continue';
                }
                if (captchaAnswerInput) {
                    captchaAnswerInput.value = '';
                    captchaAnswerInput.focus();
                }
                openCaptchaModal();
            })
            .catch(err => {
                reject(err);
            });
    });
}

function openCaptchaModal() {
    if (!captchaModal) return;
    captchaModal.classList.add('is-open');
    captchaModal.setAttribute('aria-hidden', 'false');
}

function closeCaptchaModal() {
    if (!captchaModal) return;
    captchaModal.classList.remove('is-open');
    captchaModal.setAttribute('aria-hidden', 'true');
    captchaForm = null;
    captchaResolve = null;
    captchaReject = null;
}

function cancelCaptchaModal() {
    if (captchaReject) {
        captchaReject(new Error('Captcha cancelled'));
    }
    closeCaptchaModal();
}

function submitCaptchaModal() {
    if (!captchaResolve || !captchaAnswerInput) {
        closeCaptchaModal();
        return;
    }
    const answer = captchaAnswerInput.value.trim();
    if (!answer) {
        captchaAnswerInput.focus();
        return;
    }
    const resolver = captchaResolve;
    closeCaptchaModal();
    resolver(answer);
}
