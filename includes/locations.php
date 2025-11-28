<?php
/**
 * CampusDigs Kenya - Locations Configuration
 * Predefined locations organized by university
 */

// Locations grouped by university (optimized for performance)
define('UNIVERSITY_LOCATIONS', [
    'University of Nairobi (UoN)' => [
        'Parklands',
        'Chiromo/Westlands',
        'Ngara',
        'Kilimani',
        'Nairobi CBD/City Square',
        'Madaraka'
    ],
    'Kenyatta University (KU)' => [
        'Kahawa Sukari',
        'Kahawa Wendani',
        'Ruiru'
    ],
    'Strathmore University' => [
        'Madaraka',
        'South B',
        'South C'
    ],
    'United States International University (USIU)' => [
        'Kasarani',
        'Roysambu',
        'Zimmerman'
    ],
    'Jomo Kenyatta University of Agriculture & Technology (JKUAT)' => [
        'Juja',
        'Kalimoni',
        'Ruiru'
    ],
    'Technical University of Kenya (TUK)' => [
        'Nairobi CBD',
        'South B',
        'South C'
    ],
    'Catholic University of Eastern Africa (CUEA)' => [
        'Karen',
        'Lang\'ata',
        'Rongai'
    ],
    'Daystar University (Nairobi Campus)' => [
        'Valley Road/Upper Hill',
        'Kilimani',
        'Hurlingham'
    ]
]);

// Flat list of all unique locations (alphabetically sorted)
function locations_getAllLocations() {
    $allLocations = [];
    foreach (UNIVERSITY_LOCATIONS as $university => $locations) {
        $allLocations = array_merge($allLocations, $locations);
    }
    // Remove duplicates and sort
    $allLocations = array_unique($allLocations);
    sort($allLocations);
    return $allLocations;
}

// Get locations for a specific university
function locations_getLocationsForUniversity($university) {
    return UNIVERSITY_LOCATIONS[$university] ?? [];
}

// Get all universities
function locations_getAllUniversities() {
    return array_keys(UNIVERSITY_LOCATIONS);
}

// Get university for a location
function locations_getUniversityForLocation($location) {
    foreach (UNIVERSITY_LOCATIONS as $university => $locations) {
        if (in_array($location, $locations)) {
            return $university;
        }
    }
    return null;
}

// Get locations grouped by university (for dropdowns)
function locations_getGroupedLocations() {
    return UNIVERSITY_LOCATIONS;
}

?>
