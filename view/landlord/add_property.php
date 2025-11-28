<?php
/**
 * CampusDigs Kenya - Add Property Page
 * Form for landlords to add new properties
 */

require_once '../../includes/config.php';
require_once '../../includes/core.php';
require_once '../../includes/locations.php';

requireLandlord();

$landlordId = $_SESSION['user_id'];
$flash = getFlashMessage();

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
    <title>Add Property - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container py-4">
        <a href="../../dashboard_landlord.php" class="btn btn-outline-secondary mb-3">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow">
            <div class="card-header bg-warning text-white">
                <h4 class="mb-0"><i class="fas fa-plus-circle"></i> Add New Property</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="../../actions/landlord_properties_action.php" enctype="multipart/form-data" id="addPropertyForm">
                    <?php csrfTokenField(); ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="landlord_id" value="<?php echo $landlordId; ?>">

                    <!-- Property Images -->
                    <div class="mb-4">
                        <h5 class="mb-3"><i class="fas fa-images"></i> Property Photos</h5>
                        <div class="alert alert-info">
                            <small><i class="fas fa-info-circle"></i> Add at least 3 high-quality photos. First photo will be the main image.</small>
                        </div>
                        <input type="file" name="property_images[]" class="form-control" multiple accept="image/*" required>
                    </div>

                    <hr>

                    <!-- Basic Information -->
                    <h5 class="mb-3"><i class="fas fa-info-circle"></i> Basic Information</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Property Title *</label>
                            <input type="text" name="title" class="form-control" required placeholder="e.g., Modern Studio Near UoN">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Room Type *</label>
                            <select name="room_type" class="form-select" required>
                                <option value="">Select Type</option>
                                <?php foreach (ROOM_TYPES as $value => $label): ?>
                                    <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description *</label>
                            <textarea name="description" class="form-control" rows="4" required 
                                      placeholder="Describe your property, nearby amenities, and what makes it special..."></textarea>
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
                                            <option value="<?php echo htmlspecialchars($loc); ?>">
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
                                    <option value="<?php echo htmlspecialchars($uni); ?>">
                                        <?php echo htmlspecialchars($uni); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Select the nearest university to attract students</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Distance from Campus (km)</label>
                            <input type="number" step="0.1" name="distance_from_campus" class="form-control" placeholder="e.g., 2.5">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Capacity (Number of Occupants)</label>
                            <input type="number" name="capacity" class="form-control" value="1" min="1">
                        </div>
                    </div>

                    <hr>

                    <!-- Pricing -->
                    <h5 class="mb-3"><i class="fas fa-dollar-sign"></i> Pricing</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Monthly Rent (KSh) *</label>
                            <input type="number" name="price_monthly" class="form-control" required min="<?php echo MIN_PROPERTY_PRICE; ?>" 
                                   max="<?php echo MAX_PROPERTY_PRICE; ?>" placeholder="e.g., 12000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Security Deposit (KSh) *</label>
                            <input type="number" name="security_deposit" class="form-control" required placeholder="Usually equal to monthly rent">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Minimum Lease (Months)</label>
                            <input type="number" name="min_lease_months" class="form-control" value="4" min="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Maximum Lease (Months)</label>
                            <input type="number" name="max_lease_months" class="form-control" value="12" min="1">
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
                                           id="amenity_<?php echo str_replace(' ', '_', $amenity); ?>">
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
                                <input class="form-check-input" type="checkbox" name="has_cctv" value="1" id="has_cctv">
                                <label class="form-check-label" for="has_cctv">CCTV Surveillance</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="has_security_guard" value="1" id="has_security_guard">
                                <label class="form-check-label" for="has_security_guard">24/7 Security Guard</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="has_secure_entry" value="1" id="has_secure_entry">
                                <label class="form-check-label" for="has_secure_entry">Secure Entry System</label>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Terms -->
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" required id="terms">
                        <label class="form-check-label" for="terms">
                            I confirm that all information provided is accurate and I have authority to list this property. I agree to CampusDigs terms and commission structure (10% of booking amount).
                        </label>
                    </div>

                    <!-- Submit -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-warning btn-lg" id="submitBtn">
                            <i class="fas fa-check"></i> Submit Property for Review
                        </button>
                        <a href="../../dashboard_landlord.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/dashboard.js"></script>
    <script>
        document.getElementById('addPropertyForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';
        });
    </script>
</body>
</html>