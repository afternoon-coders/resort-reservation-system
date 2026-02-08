<?php

$defaultContent = [
    'homepage' => [
        'heroTitle' => "Welcome to Paradise",
        'heroSubtitle' => "Escape to Barr Mont Le Paseo Island Resort, where crystal-clear waters meet pristine beaches and luxury meets tranquility.",
        'heroButtonPrimary' => "Book Your Stay",
        'heroButtonSecondary' => "Explore Rooms",
        'aboutTitle' => "Your Island Escape Awaits",
        'aboutDescription' => "Nestled on a pristine island in the Pacific, Barr Mont Le Paseo offers an unparalleled tropical retreat. With overwater bungalows, private villas, and world-class amenities, every moment here is designed to create memories that last a lifetime. Whether you seek adventure or relaxation, our island paradise has something for everyone.",
        'ctaTitle' => "Ready to Experience Paradise?",
        'ctaDescription' => "Book your dream vacation today and discover why Barr Mont Le Paseo is the ultimate island getaway.",
        'ctaButton' => "Reserve Your Room",
    ],
    'rooms' => [
        'pageTitle' => "Our Accommodations",
        'pageSubtitle' => "From ocean-view rooms to private villas, find your perfect island retreat",
        'featuresTitle' => "All Rooms Include",
    ],
    'amenities' => [
        'pageTitle' => "Resort Amenities",
        'pageSubtitle' => "Discover our world-class facilities designed for your comfort and enjoyment",
    ],
    'contact' => [
        'pageTitle' => "Contact Us",
        'pageSubtitle' => "We would love to hear from you. Get in touch with our team.",
        'address' => "Barr Mont Le Paseo Island, Pacific Ocean",
        'phone' => "+1 (555) 123-4567",
        'email' => "reservations@barrmontlepaseo.com",
    ],
];


$contentFile = 'saved_content.json';
if(file_exists($contentFile)) {
    $content = json_decode(file_get_contents($contentFile), true);
} else {
    $content = $defaultContent;
}

// Handle form submission
$savedMessage = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = $_POST['section'];
    foreach($content[$section] as $key => $value) {
        if(isset($_POST[$key])) {
            $content[$section][$key] = $_POST[$key];
        }
    }
    file_put_contents($contentFile, json_encode($content, JSON_PRETTY_PRINT));
    $savedMessage = "Changes saved successfully!";
}

// Handle reset
if(isset($_GET['reset'])) {
    $section = $_GET['reset'];
    $content[$section] = $defaultContent[$section];
    file_put_contents($contentFile, json_encode($content, JSON_PRETTY_PRINT));
    $savedMessage = "Section reset successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Content Management</title>
<style>
body { 
    font-family: Arial, sans-serif; background:#f5f5f5; margin:0; padding:0;
}
.container { max-width: 1200px; margin:auto; padding:20px; }
h1, h2, h3 { margin:0; }
.card { background:#fff; border-radius:8px; padding:20px; margin-bottom:20px; box-shadow:0 2px 6px rgba(0,0,0,0.1);}
.card-header { display:flex; justify-content: space-between; align-items:center; }
.card-header button { background:none; border:none; color: #007BFF; cursor:pointer; }
input, textarea { width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; margin-top:4px; }
label { font-weight:bold; }
button.save-btn { padding:8px 16px; background:#28a745; color:#fff; border:none; border-radius:4px; cursor:pointer; }
button.save-btn:hover { background:#218838; }
.tabs { display:flex; gap:10px; margin-bottom:20px; flex-wrap: wrap;}
.tabs button { padding:8px 16px; border:none; border-radius:4px; cursor:pointer; background:#ddd; }
.tabs button.active { background:#007BFF; color:#fff; }
.message { padding:10px; background:#d1e7dd; color:#0f5132; margin-bottom:20px; border-radius:4px; }
</style>
<script>
function showTab(tab) {
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(t => t.style.display = 'none');
    document.getElementById(tab).style.display = 'block';
    const buttons = document.querySelectorAll('.tabs button');
    buttons.forEach(b => b.classList.remove('active'));
    document.querySelector('.tabs button[data-tab="'+tab+'"]').classList.add('active');
}
window.onload = function() {
    showTab('homepage'); // default
}
</script>
</head>
<body>
<div class="container">
    <h1>Page Content Management</h1>
    <p>Edit the text content displayed on your website</p>

    <?php if($savedMessage): ?>
        <div class="message"><?php echo $savedMessage; ?></div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="tabs">
        <button data-tab="homepage" onclick="showTab('homepage')">Homepage</button>
        <button data-tab="rooms" onclick="showTab('rooms')">Rooms</button>
        <button data-tab="amenities" onclick="showTab('amenities')">Amenities</button>
        <button data-tab="contact" onclick="showTab('contact')">Contact</button>
    </div>

    <!-- Homepage Tab -->
    <div id="homepage" class="tab-content">
        <form method="POST">
            <input type="hidden" name="section" value="homepage">
            <div class="card">
                <div class="card-header">
                    <h3>Hero Section</h3>
                    <a href="?reset=homepage">Reset</a>
                </div>
                <label>Hero Title</label>
                <input type="text" name="heroTitle" value="<?php echo htmlspecialchars($content['homepage']['heroTitle']); ?>">
                <label>Hero Subtitle</label>
                <textarea name="heroSubtitle" rows="3"><?php echo htmlspecialchars($content['homepage']['heroSubtitle']); ?></textarea>
                <label>Primary Button</label>
                <input type="text" name="heroButtonPrimary" value="<?php echo htmlspecialchars($content['homepage']['heroButtonPrimary']); ?>">
                <label>Secondary Button</label>
                <input type="text" name="heroButtonSecondary" value="<?php echo htmlspecialchars($content['homepage']['heroButtonSecondary']); ?>">
            </div>

            <div class="card">
                <div class="card-header"><h3>About Section</h3><a href="?reset=homepage">Reset</a></div>
                <label>About Title</label>
                <input type="text" name="aboutTitle" value="<?php echo htmlspecialchars($content['homepage']['aboutTitle']); ?>">
                <label>About Description</label>
                <textarea name="aboutDescription" rows="5"><?php echo htmlspecialchars($content['homepage']['aboutDescription']); ?></textarea>
            </div>

            <div class="card">
                <div class="card-header"><h3>CTA Section</h3><a href="?reset=homepage">Reset</a></div>
                <label>CTA Title</label>
                <input type="text" name="ctaTitle" value="<?php echo htmlspecialchars($content['homepage']['ctaTitle']); ?>">
                <label>CTA Description</label>
                <textarea name="ctaDescription" rows="2"><?php echo htmlspecialchars($content['homepage']['ctaDescription']); ?></textarea>
                <label>CTA Button</label>
                <input type="text" name="ctaButton" value="<?php echo htmlspecialchars($content['homepage']['ctaButton']); ?>">
            </div>

            <button type="submit" class="save-btn">Save All Changes</button>
        </form>
    </div>

    <!-- Rooms Tab -->
    <div id="rooms" class="tab-content" style="display:none;">
        <form method="POST">
            <input type="hidden" name="section" value="rooms">
            <div class="card">
                <div class="card-header"><h3>Rooms Page Header</h3><a href="?reset=rooms">Reset</a></div>
                <label>Page Title</label>
                <input type="text" name="pageTitle" value="<?php echo htmlspecialchars($content['rooms']['pageTitle']); ?>">
                <label>Page Subtitle</label>
                <textarea name="pageSubtitle" rows="2"><?php echo htmlspecialchars($content['rooms']['pageSubtitle']); ?></textarea>
                <label>Features Title</label>
                <input type="text" name="featuresTitle" value="<?php echo htmlspecialchars($content['rooms']['featuresTitle']); ?>">
            </div>
            <button type="submit" class="save-btn">Save Changes</button>
        </form>
    </div>

    <!-- Amenities Tab -->
    <div id="amenities" class="tab-content" style="display:none;">
        <form method="POST">
            <input type="hidden" name="section" value="amenities">
            <div class="card">
                <div class="card-header"><h3>Amenities Page Header</h3><a href="?reset=amenities">Reset</a></div>
                <label>Page Title</label>
                <input type="text" name="pageTitle" value="<?php echo htmlspecialchars($content['amenities']['pageTitle']); ?>">
                <label>Page Subtitle</label>
                <textarea name="pageSubtitle" rows="2"><?php echo htmlspecialchars($content['amenities']['pageSubtitle']); ?></textarea>
            </div>
            <button type="submit" class="save-btn">Save Changes</button>
        </form>
    </div>

    <!-- Contact Tab -->
    <div id="contact" class="tab-content" style="display:none;">
        <form method="POST">
            <input type="hidden" name="section" value="contact">
            <div class="card">
                <div class="card-header"><h3>Contact Page</h3><a href="?reset=contact">Reset</a></div>
                <label>Page Title</label>
                <input type="text" name="pageTitle" value="<?php echo htmlspecialchars($content['contact']['pageTitle']); ?>">
                <label>Page Subtitle</label>
                <textarea name="pageSubtitle" rows="2"><?php echo htmlspecialchars($content['contact']['pageSubtitle']); ?></textarea>
                <label>Address</label>
                <input type="text" name="address" value="<?php echo htmlspecialchars($content['contact']['address']); ?>">
                <label>Phone</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($content['contact']['phone']); ?>">
                <label>Email</label>
                <input type="text" name="email" value="<?php echo htmlspecialchars($content['contact']['email']); ?>">
            </div>
            <button type="submit" class="save-btn">Save Changes</button>
        </form>
    </div>
</div>
</body>
</html>
