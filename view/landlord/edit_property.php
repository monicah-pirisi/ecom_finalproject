<?php
/**
 * CampusDigs Kenya - Edit Property Page
 * Edit existing property details
 */

require_once '../../includes/config.php';
require_once '../../includes/core.php';
require_once '../../includes/locations.php';
require_once '../../controllers/property_controller.php';

requireLandlord();

$landlordId = $_SESSION['user_id'];
$flash = getFlashMessage();

// Get property ID
$propertyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$propertyId) {
    $_SESSION['error'] = 'Invalid property ID';
    header('Location: my_properties.php');
    exit();
}

// Get property details
$property = getPropertyById($propertyId);

if (!$property || $property['landlord_id'] != $landlordId) {
    $_SESSION['error'] = 'Property not found or access denied';
    header('Location: my_properties.php');
    exit();
}

// Get property images
$images = getPropertyImages($propertyId);

// Parse amenities
$amenities = $property['amenities'] ? json_decode($property['amenities'], true) : [];
$amenities = is_array($amenities) ? $amenities : [];

// Get locations and universities
$allLocations = locations_getAllLocations();
$locationsByUniversity = locations_getGroupedLocations();
$universities = locations_getAllUniversities();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Property - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">

    <style>
        .property-image-preview {
            position: relative;
            display: inline-block;
            margin: 10px;
        }
        .property-image-preview img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
        }
        .property-image-preview .delete-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
        }
        .property-image-preview .main-badge {
            position: absolute;
            top: 5px;
            left: 5px;
            background: rgba(255, 193, 7, 0.9);
            color: black;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <div class="container py-4">
        <a href="my_properties.php" class="btn btn-outline-secondary mb-3">
            <i class="fas fa-arrow-left"></i> Back to My Properties
        </a>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow">
            <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fas fa-edit"></i> Edit Property</h4>
                <span class="badge bg-<?php echo $property['status'] === 'active' ? 'success' : ($property['status'] === 'pending' ? 'dark' : 'secondary'); ?>">
                    Status: <?php echo ucfirst($property['status']); ?>
                </span>
            </div>
            <div class="card-body">
                <!-- Current Images -->
                <?php if (!empty($images)): ?>
                    <div class="mb-4">
                        <h5 class="mb-3"><i class="fas fa-images"></i> Current Property Photos</h5>
                        <div class="d-flex flex-wrap">
                            <?php foreach ($images as $image): ?>
                                <div class="property-image-preview">
                                    <?php if ($image['is_main']): ?>
                                        <span class="main-badge">Main Photo</span>
                                    <?php endif; ?>
                                    <img src="../../<?php echo htmlspecialchars($image['image_path']); ?>"
                                         alt="Property image">
                                    <button type="button" class="delete-image"
                                            onclick="deleteImage(<?php echo $image['id']; ?>, <?php echo $propertyId; ?>)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <hr>
                <?php endif; ?>

                <form method="POST" action="../../actions/landlord_properties_action.php" enctype="multipart/form-data" id="editPropertyForm">
                    <?php csrfTokenField(); ?>
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="property_id" value="<?php echo $propertyId; ?>">
                    <input type="hidden" name="landlord_id" value="<?php echo $landlordId; ?>">

                    <!-- Add New Images -->
                    <div class="mb-4">
                        <h5 class="mb-3"><i class="fas fa-plus-circle"></i> Add More Photos (Optional)</h5>
                        <input type="file" name="property_images[]" class="form-control" multiple accept="image/*">
                        <small class="text-muted">Leave empty if you don't want to add more photos</small>
                    </div>

                    <hr>

                    <!-- Basic Information -->
                    <h5 class="mb-3"><i class="fas fa-info-circle"></i> Basic Information</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Property Title *</label>
                            <input type="text" name="title" class="form-control" required
                                   value="<?php echo htmlspecialchars($property['title']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Room Type *</label>
                            <select name="room_type" class="form-select" required>
                                <?php foreach (ROOM_TYPES as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo $property['room_type'] === $value ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description *</label>
                            <textarea name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($property['description']); ?></textarea>
                        </div>
                    </div>

                    <hr>

                    <!-- Location -->
                    <h5 class="mb-3"><i class="fas fa-map-marker-alt"></i> Location</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Location/Area *</label>
                            <select name="location" class="form-select" required>
                                <option value="">Select Location</option>
                                <?php foreach ($locationsByUniversity as $university => $locs): ?>
                                    <optgroup label="<?php echo htmlspecialchars($university); ?>">
                                        <?php foreach ($locs as $loc): ?>
                                            <option value="<?php echo htmlspecialchars($loc); ?>"
                                                    <?php echo $property['location'] === $loc ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($loc); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Select the area where your property is located</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nearest University *</label>
                            <select name="university_nearby" class="form-select" required>
                                <option value="">Select University</option>
                                <?php foreach ($universities as $uni): ?>
                                    <option value="<?php echo htmlspecialchars($uni); ?>"
                                            <?php echo $property['university_nearby'] === $uni ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($uni); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Distance from Campus (km)</label>
                            <input type="number" step="0.1" name="distance_from_campus" class="form-control"
                                   value="<?php echo htmlspecialchars($property['distance_from_campus']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Capacity (Number of Occupants)</label>
                            <input type="number" name="capacity" class="form-control" min="1"
                                   value="<?php echo htmlspecialchars($property['capacity']); ?>">
                        </div>
                    </div>

                    <hr>

                    <!-- Pricing -->
                    <h5 class="mb-3"><i class="fas fa-dollar-sign"></i> Pricing</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Monthly Rent (KSh) *</label>
                            <input type="number" name="price_monthly" class="form-control" required
                                   min="<?php echo MIN_PROPERTY_PRICE; ?>" max="<?php echo MAX_PROPERTY_PRICE; ?>"
                                   value="<?php echo htmlspecialchars($property['price_monthly']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Security Deposit (KSh) *</label>
                            <input type="number" name="security_deposit" class="form-control" required
                                   value="<?php echo htmlspecialchars($property['security_deposit']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Minimum Lease (Months)</label>
                            <input type="number" name="min_lease_months" class="form-control" min="1"
                                   value="<?php echo htmlspecialchars($property['min_lease_months']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Maximum Lease (Months)</label>
                            <input type="number" name="max_lease_months" class="form-control" min="1"
                                   value="<?php echo htmlspecialchars($property['max_lease_months']); ?>">
                        </div>
                    </div>

                    <hr>

                    <!-- Amenities -->
                    <h5 class="mb-3"><i class="fas fa-check-circle"></i> Amenities & Features</h5>
                    <div class="row g-3 mb-4">
                        <?php foreach (AVAILABLE_AMENITIES as $amenity): ?>
                            <div class="col-md-4 col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="amenities[]"
                                           value="<?php echo htmlspecialchars($amenity); ?>"
                                           id="amenity_<?php echo str_replace(' ', '_', $amenity); ?>"
                                           <?php echo in_array($amenity, $amenities) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="amenity_<?php echo str_replace(' ', '_', $amenity); ?>">
                                        <?php echo htmlspecialchars($amenity); ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <hr>

                    <!-- Security Features -->
                    <h5 class="mb-3"><i class="fas fa-shield-alt"></i> Security Features</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="has_cctv" value="1" id="has_cctv"
                                       <?php echo $property['has_cctv'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="has_cctv">CCTV Surveillance</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="has_security_guard" value="1" id="has_security_guard"
                                       <?php echo $property['has_security_guard'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="has_security_guard">24/7 Security Guard</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="has_secure_entry" value="1" id="has_secure_entry"
                                       <?php echo $property['has_secure_entry'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="has_secure_entry">Secure Entry System</label>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Submit Buttons -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-warning btn-lg" id="submitBtn">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="my_properties.php" class="btn btn-outline-secondary">Cancel</a>

                        <?php if ($property['status'] === 'active'): ?>
                            <button type="button" class="btn btn-outline-danger" onclick="deactivateProperty(<?php echo $propertyId; ?>)">
                                <i class="fas fa-eye-slash"></i> Deactivate Property
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/dashboard.js"></script>
    <script>
        document.getElementById('editPropertyForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
        });

        function deleteImage(imageId, propertyId) {
            if (!confirm('Are you sure you want to delete this image?')) {
                return;
            }

            fetch('../../actions/landlord_properties_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete_image&image_id=${imageId}&property_id=${propertyId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to delete image');
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
                console.error('Error:', error);
            });
        }

        function deactivateProperty(propertyId) {
            if (!confirm('Are you sure you want to deactivate this property? It will no longer be visible to students.')) {
                return;
            }

            fetch('../../actions/landlord_properties_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete&property_id=${propertyId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'my_properties.php';
                } else {
                    alert(data.message || 'Failed to deactivate property');
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>
