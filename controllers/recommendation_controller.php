<?php
/**
 * CampusDigs Kenya - AI-Powered Recommendation Engine
 * Recommends properties to students based on multiple factors
 */

/**
 * Get personalized property recommendations for a student
 * Uses hybrid recommendation algorithm combining:
 * - Content-based filtering (property features)
 * - Collaborative filtering (user behavior)
 * - Popularity scoring
 * - Rating/review analysis
 *
 * @param int $studentId Student ID
 * @param int $limit Number of recommendations
 * @return array Recommended properties with scores
 */
function getRecommendedProperties($studentId, $limit = 6) {
    global $conn;

    try {
        // Get student preferences and history
        $studentProfile = getStudentProfile($studentId);

        // Get all active properties
        $properties = getAllActiveProperties();

        // Score each property
        $scoredProperties = [];
        foreach ($properties as $property) {
            $score = calculatePropertyScore($property, $studentProfile, $studentId);

            if ($score > 0) {
                $property['recommendation_score'] = $score;
                $property['recommendation_reasons'] = getRecommendationReasons($property, $studentProfile);
                $scoredProperties[] = $property;
            }
        }

        // Sort by score (highest first)
        usort($scoredProperties, function($a, $b) {
            return $b['recommendation_score'] <=> $a['recommendation_score'];
        });

        // Return top N recommendations
        return array_slice($scoredProperties, 0, $limit);

    } catch (Exception $e) {
        error_log("Recommendation error: " . $e->getMessage());
        return [];
    }
}

/**
 * Build student profile from their activity
 * Analyzes: wishlist, bookings, searches, views
 */
function getStudentProfile($studentId) {
    global $conn;

    $profile = [
        'wishlist_properties' => [],
        'booked_properties' => [],
        'preferred_price_range' => ['min' => 0, 'max' => 1000000],
        'preferred_locations' => [],
        'preferred_property_types' => [],
        'preferred_amenities' => [],
        'budget_avg' => 0,
        'activity_count' => 0
    ];

    // Get wishlist properties
    $stmt = $conn->prepare("
        SELECT p.*
        FROM wishlist w
        JOIN properties p ON w.property_id = p.id
        WHERE w.student_id = ? AND p.status = 'active'
    ");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $profile['wishlist_properties'][] = $row;
        $profile['preferred_locations'][] = $row['location'];
        $profile['preferred_property_types'][] = $row['property_type'];
    }
    $stmt->close();

    // Get booking history
    $stmt = $conn->prepare("
        SELECT p.*, b.total_amount
        FROM bookings b
        JOIN properties p ON b.property_id = p.id
        WHERE b.student_id = ? AND b.status IN ('approved', 'completed')
    ");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();

    $totalBudget = 0;
    $bookingCount = 0;

    while ($row = $result->fetch_assoc()) {
        $profile['booked_properties'][] = $row;
        $profile['preferred_locations'][] = $row['location'];
        $totalBudget += $row['price_monthly'];
        $bookingCount++;
    }
    $stmt->close();

    // Calculate average budget
    if ($bookingCount > 0) {
        $profile['budget_avg'] = $totalBudget / $bookingCount;
        $profile['preferred_price_range'] = [
            'min' => $profile['budget_avg'] * 0.7,
            'max' => $profile['budget_avg'] * 1.3
        ];
    }

    // Get most common locations
    $profile['preferred_locations'] = array_count_values($profile['preferred_locations']);
    arsort($profile['preferred_locations']);

    // Get most common property types
    $profile['preferred_property_types'] = array_count_values($profile['preferred_property_types']);
    arsort($profile['preferred_property_types']);

    $profile['activity_count'] = count($profile['wishlist_properties']) + count($profile['booked_properties']);

    return $profile;
}

/**
 * Get all active properties for recommendation
 */
function getAllActiveProperties() {
    global $conn;

    $properties = [];

    $query = "
        SELECT p.*,
               u.full_name as landlord_name,
               COALESCE(AVG(r.rating), 0) as avg_rating,
               COUNT(DISTINCT r.id) as review_count,
               COUNT(DISTINCT b.id) as booking_count,
               COUNT(DISTINCT w.id) as wishlist_count
        FROM properties p
        LEFT JOIN users u ON p.landlord_id = u.id
        LEFT JOIN reviews r ON r.property_id = p.id AND r.is_approved = 1
        LEFT JOIN bookings b ON b.property_id = p.id
        LEFT JOIN wishlist w ON w.property_id = p.id
        WHERE p.status = 'active'
        GROUP BY p.id
    ";

    $result = $conn->query($query);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $properties[] = $row;
        }
    }

    return $properties;
}

/**
 * Calculate recommendation score for a property
 * Higher score = better recommendation
 */
function calculatePropertyScore($property, $studentProfile, $studentId) {
    global $conn;

    $score = 0;

    // Skip if student already booked this property
    foreach ($studentProfile['booked_properties'] as $booked) {
        if ($booked['id'] == $property['id']) {
            return 0;
        }
    }

    // Skip if already in wishlist (show different recommendations)
    foreach ($studentProfile['wishlist_properties'] as $wishlist) {
        if ($wishlist['id'] == $property['id']) {
            return 0;
        }
    }

    // 1. CONTENT-BASED SCORING (40% weight)
    $contentScore = 0;

    // Price similarity (15 points max)
    if ($studentProfile['budget_avg'] > 0) {
        $priceDiff = abs($property['price_monthly'] - $studentProfile['budget_avg']);
        $priceScore = max(0, 15 - ($priceDiff / $studentProfile['budget_avg'] * 15));
        $contentScore += $priceScore;
    } else {
        // If no budget history, prefer lower prices
        $contentScore += 10;
    }

    // Location match (15 points max)
    if (!empty($studentProfile['preferred_locations'])) {
        $topLocations = array_keys($studentProfile['preferred_locations']);
        if (in_array($property['location'], $topLocations)) {
            $locationRank = array_search($property['location'], $topLocations);
            $contentScore += max(5, 15 - ($locationRank * 3));
        }
    }

    // Property type match (10 points max)
    if (!empty($studentProfile['preferred_property_types'])) {
        $topTypes = array_keys($studentProfile['preferred_property_types']);
        if (in_array($property['property_type'], $topTypes)) {
            $contentScore += 10;
        }
    }

    $score += $contentScore;

    // 2. POPULARITY SCORING (25% weight)
    $popularityScore = 0;

    // Rating score (10 points max)
    $popularityScore += ($property['avg_rating'] / 5) * 10;

    // Review count (5 points max)
    $popularityScore += min(5, $property['review_count'] * 0.5);

    // Booking count (5 points max)
    $popularityScore += min(5, $property['booking_count'] * 0.3);

    // Wishlist count (5 points max)
    $popularityScore += min(5, $property['wishlist_count'] * 0.5);

    $score += $popularityScore;

    // 3. COLLABORATIVE FILTERING (20% weight)
    $collaborativeScore = 0;

    // Find similar students and their preferences
    $similarStudents = findSimilarStudents($studentId, $studentProfile);

    foreach ($similarStudents as $similarStudent) {
        // Check if similar student liked this property
        $stmt = $GLOBALS['conn']->prepare("
            SELECT COUNT(*) as count
            FROM wishlist
            WHERE student_id = ? AND property_id = ?
        ");
        $stmt->bind_param("ii", $similarStudent['student_id'], $property['id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result['count'] > 0) {
            $collaborativeScore += 5;
        }
        $stmt->close();
    }

    $score += min(20, $collaborativeScore);

    // 4. FRESHNESS BONUS (10% weight)
    $daysOld = (time() - strtotime($property['created_at'])) / 86400;
    $freshnessScore = max(0, 10 - ($daysOld / 30));
    $score += $freshnessScore;

    // 5. AVAILABILITY BONUS (5% weight)
    if ($property['availability_status'] == 'available') {
        $score += 5;
    }

    return round($score, 2);
}

/**
 * Find students with similar preferences
 */
function findSimilarStudents($studentId, $studentProfile) {
    global $conn;

    $similarStudents = [];

    // Find students who liked similar properties
    if (!empty($studentProfile['wishlist_properties'])) {
        $propertyIds = array_column($studentProfile['wishlist_properties'], 'id');
        $placeholders = implode(',', array_fill(0, count($propertyIds), '?'));

        $query = "
            SELECT student_id, COUNT(*) as similarity_score
            FROM wishlist
            WHERE property_id IN ($placeholders)
              AND student_id != ?
            GROUP BY student_id
            ORDER BY similarity_score DESC
            LIMIT 5
        ";

        $stmt = $conn->prepare($query);
        $types = str_repeat('i', count($propertyIds)) . 'i';
        $params = array_merge($propertyIds, [$studentId]);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $similarStudents[] = $row;
        }
        $stmt->close();
    }

    return $similarStudents;
}

/**
 * Generate human-readable reasons for recommendation
 */
function getRecommendationReasons($property, $studentProfile) {
    $reasons = [];

    // Price match
    if ($studentProfile['budget_avg'] > 0) {
        $priceDiff = abs($property['price_monthly'] - $studentProfile['budget_avg']);
        $percentDiff = ($priceDiff / $studentProfile['budget_avg']) * 100;

        if ($percentDiff < 10) {
            $reasons[] = "Matches your budget perfectly";
        } elseif ($property['price_monthly'] < $studentProfile['budget_avg']) {
            $reasons[] = "Great value within your budget";
        }
    }

    // Location match
    if (!empty($studentProfile['preferred_locations'])) {
        $topLocation = array_key_first($studentProfile['preferred_locations']);
        if ($property['location'] === $topLocation) {
            $reasons[] = "In your preferred location: " . $topLocation;
        }
    }

    // High rating
    if ($property['avg_rating'] >= 4.5) {
        $reasons[] = "Highly rated (" . number_format($property['avg_rating'], 1) . "/5)";
    }

    // Popular
    if ($property['booking_count'] > 5) {
        $reasons[] = "Popular with students";
    }

    // Good reviews
    if ($property['review_count'] >= 3) {
        $reasons[] = $property['review_count'] . " verified reviews";
    }

    // New listing
    $daysOld = (time() - strtotime($property['created_at'])) / 86400;
    if ($daysOld < 7) {
        $reasons[] = "New listing";
    }

    // Limit to 3 reasons
    return array_slice($reasons, 0, 3);
}

/**
 * Get trending properties (most active recently)
 */
function getTrendingProperties($limit = 6) {
    global $conn;

    $query = "
        SELECT p.*,
               u.full_name as landlord_name,
               COALESCE(AVG(r.rating), 0) as avg_rating,
               COUNT(DISTINCT r.id) as review_count,
               (
                   COUNT(DISTINCT CASE WHEN b.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN b.id END) * 3 +
                   COUNT(DISTINCT CASE WHEN w.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN w.id END) * 2 +
                   COUNT(DISTINCT CASE WHEN r.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN r.id END) * 1
               ) as trend_score
        FROM properties p
        LEFT JOIN users u ON p.landlord_id = u.id
        LEFT JOIN reviews r ON r.property_id = p.id AND r.is_approved = 1
        LEFT JOIN bookings b ON b.property_id = p.id
        LEFT JOIN wishlist w ON w.property_id = p.id
        WHERE p.status = 'active'
        GROUP BY p.id
        HAVING trend_score > 0
        ORDER BY trend_score DESC, p.created_at DESC
        LIMIT ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $properties = [];
    while ($row = $result->fetch_assoc()) {
        $properties[] = $row;
    }
    $stmt->close();

    return $properties;
}

/**
 * Get properties similar to a given property
 * Based on: location, price range, property type, amenities
 */
function getSimilarProperties($propertyId, $limit = 4) {
    global $conn;

    // Get source property
    $stmt = $conn->prepare("SELECT * FROM properties WHERE id = ?");
    $stmt->bind_param("i", $propertyId);
    $stmt->execute();
    $sourceProperty = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$sourceProperty) {
        return [];
    }

    // Find similar properties
    $priceMin = $sourceProperty['price_monthly'] * 0.8;
    $priceMax = $sourceProperty['price_monthly'] * 1.2;

    $query = "
        SELECT p.*,
               u.full_name as landlord_name,
               COALESCE(AVG(r.rating), 0) as avg_rating,
               COUNT(DISTINCT r.id) as review_count,
               (
                   (CASE WHEN p.location = ? THEN 30 ELSE 0 END) +
                   (CASE WHEN p.property_type = ? THEN 20 ELSE 0 END) +
                   (CASE WHEN p.price_monthly BETWEEN ? AND ? THEN 15 ELSE 0 END) +
                   (CASE WHEN p.bedrooms = ? THEN 10 ELSE 0 END) +
                   (CASE WHEN p.bathrooms = ? THEN 5 ELSE 0 END)
               ) as similarity_score
        FROM properties p
        LEFT JOIN users u ON p.landlord_id = u.id
        LEFT JOIN reviews r ON r.property_id = p.id AND r.is_approved = 1
        WHERE p.status = 'active'
          AND p.id != ?
        GROUP BY p.id
        HAVING similarity_score > 0
        ORDER BY similarity_score DESC, avg_rating DESC
        LIMIT ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "ssddiiii",
        $sourceProperty['location'],
        $sourceProperty['property_type'],
        $priceMin,
        $priceMax,
        $sourceProperty['bedrooms'],
        $sourceProperty['bathrooms'],
        $propertyId,
        $limit
    );
    $stmt->execute();
    $result = $stmt->get_result();

    $properties = [];
    while ($row = $result->fetch_assoc()) {
        $properties[] = $row;
    }
    $stmt->close();

    return $properties;
}
?>
