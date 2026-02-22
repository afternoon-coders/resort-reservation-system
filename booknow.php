<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Hotel Reservation</title>
    <link rel="stylesheet" href="static/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<body>

    <div class="booknow-container">

        <!-- LEFT: BOOKING FORM -->
        <div class="booknow-card">

            <div class="booknow-section-title">
                <span><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#086584"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h168q13-36 43.5-58t68.5-22q38 0 68.5 22t43.5 58h168q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-560H200v560Zm80-80h280v-80H280v80Zm0-160h400v-80H280v80Zm0-160h400v-80H280v80Zm221.5-198.5Q510-807 510-820t-8.5-21.5Q493-850 480-850t-21.5 8.5Q450-833 450-820t8.5 21.5Q467-790 480-790t21.5-8.5ZM200-200v-560 560Z"/></svg></span>
                Reservation Details
            </div>

            <div class="booknow-grid-3">
                <div>
                    <label class="booknow-label">Check-in Date</label>
                    <input type="date" class="booknow-input">
                </div>
                <div>
                    <label class="booknow-label">Check-out Date</label>
                    <input type="date" class="booknow-input">
                </div>
                <div>
                    <label class="booknow-label">Number of Guests</label>
                    <select class="booknow-select">
                    <option>Select guests</option>
                    <option>1</option>
                    <option>2</option>
                    <option>3</option>
                    <option>4+</option>
                    </select>
                </div>
            </div>

            <div style="margin-top:16px; max-width:260px;">
                <label class="booknow-label">Room Type</label>
                <select class="booknow-select">
                    <option>Select a room</option>
                    <option>Standard</option>
                    <option>Deluxe</option>
                    <option>Suite</option>
                </select>
            </div>

            <div class="booknow-divider"></div>

            <div class="booknow-section-title">
                <span><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#086584"><path d="M40-160v-112q0-34 17.5-62.5T104-378q62-31 126-46.5T360-440q66 0 130 15.5T616-378q29 15 46.5 43.5T680-272v112H40Zm720 0v-120q0-44-24.5-84.5T666-434q51 6 96 20.5t84 35.5q36 20 55 44.5t19 53.5v120H760ZM247-527q-47-47-47-113t47-113q47-47 113-47t113 47q47 47 47 113t-47 113q-47 47-113 47t-113-47Zm466 0q-47 47-113 47-11 0-28-2.5t-28-5.5q27-32 41.5-71t14.5-81q0-42-14.5-81T544-792q14-5 28-6.5t28-1.5q66 0 113 47t47 113q0 66-47 113ZM120-240h480v-32q0-11-5.5-20T580-306q-54-27-109-40.5T360-360q-56 0-111 13.5T140-306q-9 5-14.5 14t-5.5 20v32Zm296.5-343.5Q440-607 440-640t-23.5-56.5Q393-720 360-720t-56.5 23.5Q280-673 280-640t23.5 56.5Q327-560 360-560t56.5-23.5ZM360-240Zm0-400Z"/></svg></span>
                Guest Information
            </div>

            <div class="booknow-grid-2">
            <div>
                <label class="booknow-label">First Name</label>
                <input type="text" class="booknow-input" value="John">
            </div>
            <div>
                <label class="booknow-label">Last Name</label>
                <input type="text" class="booknow-input" value="Doe">
            </div>
            <div>
                <label class="booknow-label">Email Address</label>
                <input type="email" class="booknow-input" value="john@example.com">
            </div>
            <div>
                <label class="booknow-label">Phone Number</label>
                <input type="tel" class="booknow-input" value="+1 (555) 123-4567">
            </div>
            </div>

            <div style="margin-top:16px;">
            <label class="booknow-label">Special Requests (Optional)</label>
            <textarea class="booknow-textarea" placeholder="Any special requirements or requests..."></textarea>
            </div>

            <div style="margin-top:24px;">
            <button class="booknow-submit">Complete Reservation</button>
            </div>

        </div>

        <!-- RIGHT: SUMMARY -->
        <div class="summary-card">
            <div class="booknow-section-title">
                <span><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#086584"><path d="M80-200v-240q0-27 11-49t29-39v-112q0-50 35-85t85-35h160q23 0 43 8.5t37 23.5q17-15 37-23.5t43-8.5h160q50 0 85 35t35 85v112q18 17 29 39t11 49v240h-80v-80H160v80H80Zm440-360h240v-80q0-17-11.5-28.5T720-680H560q-17 0-28.5 11.5T520-640v80Zm-320 0h240v-80q0-17-11.5-28.5T400-680H240q-17 0-28.5 11.5T200-640v80Zm-40 200h640v-80q0-17-11.5-28.5T760-480H200q-17 0-28.5 11.5T160-440v80Zm640 0H160h640Z"/></svg></span>
                Booking Summary
            </div>

            <div class="booknow-summary-text">
            Select a room type to see pricing
            </div>

            <div class="booknow-secure">
            Secure payment processing
            </div>
        </div>

    </div>


</body>
</html>