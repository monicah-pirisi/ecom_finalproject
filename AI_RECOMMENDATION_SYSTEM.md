# AI-Powered Recommendation System - CampusDigs Kenya

## Overview

The CampusDigs platform features an intelligent recommendation engine that helps students discover properties tailored to their preferences. The system uses a **hybrid recommendation algorithm** combining multiple AI/ML techniques to provide personalized property suggestions.

---

## 🧠 How It Works

### Hybrid Recommendation Algorithm

The system combines four main approaches:

#### 1. **Content-Based Filtering** (40% weight)
Analyzes property features and matches them to student preferences:
- **Price Similarity** (15 points): Matches properties within student's budget range
- **Location Matching** (15 points): Prioritizes student's preferred locations
- **Property Type** (10 points): Matches apartment, studio, hostel preferences

#### 2. **Collaborative Filtering** (20% weight)
Learns from similar users' behavior:
- Identifies students with similar preferences
- Recommends properties liked by similar students
- Uses wishlist and booking patterns

#### 3. **Popularity Scoring** (25% weight)
Considers social proof indicators:
- **Average Rating** (10 points): Higher rated properties score better
- **Review Count** (5 points): More reviews = more trusted
- **Booking Count** (5 points): Popular with other students
- **Wishlist Count** (5 points): High demand indicator

#### 4. **Freshness Bonus** (10% weight)
Promotes newer listings:
- Recently added properties get visibility
- Decays over 30 days

#### 5. **Availability Bonus** (5% weight)
Prioritizes immediately available properties

---

## 📊 Student Profile Building

The system builds a comprehensive profile for each student by analyzing:

### Data Sources:
1. **Wishlist Activity**
   - Properties saved
   - Common features liked
   - Location preferences

2. **Booking History**
   - Past bookings
   - Price range patterns
   - Property type preferences

3. **Search Behavior** (future)
   - Search terms
   - Filter preferences
   - Browsing patterns

4. **Review Activity** (future)
   - Properties reviewed
   - Rating patterns

### Profile Attributes:
```php
[
    'preferred_price_range' => ['min' => 8000, 'max' => 12000],
    'preferred_locations' => ['Parklands', 'Westlands', 'Kilimani'],
    'preferred_property_types' => ['apartment', 'studio'],
    'budget_avg' => 10000,
    'activity_count' => 15
]
```

---

## 🎯 Scoring Algorithm

Each property receives a **recommendation score (0-100)**:

```
Total Score = Content Score + Popularity Score + Collaborative Score +
              Freshness Score + Availability Score

Example Breakdown:
- Price match: 15/15
- Location match: 15/15
- Property type: 10/10
- Rating (4.5/5): 9/10
- Reviews (8): 4/5
- Bookings (12): 5/5
- Wishlist (6): 3/5
- Collaborative: 15/20
- Freshness: 8/10
- Available: 5/5
------------------
Total: 89/100 = 89% Match
```

---

## 🔍 Recommendation Types

### 1. Personalized Recommendations
**Endpoint**: `get_recommendations.php?type=personalized`

Uses full hybrid algorithm with student's personal profile.

**Best For:**
- Student dashboard
- Homepage personalization
- Email recommendations

### 2. Trending Properties
**Endpoint**: `get_recommendations.php?type=trending`

Ranks by recent activity (last 7 days):
- Recent bookings × 3
- Recent wishlist additions × 2
- Recent reviews × 1

**Best For:**
- "What's Hot" sections
- New user recommendations
- Property discovery

### 3. Similar Properties
**Endpoint**: `get_recommendations.php?type=similar&property_id=X`

Finds properties similar to a given property based on:
- Same location (30 points)
- Same property type (20 points)
- Similar price (±20%) (15 points)
- Same bedrooms (10 points)
- Same bathrooms (5 points)

**Best For:**
- Property detail pages
- "You might also like" sections
- Alternative suggestions

---

## 📁 Files Structure

```
controllers/
  └── recommendation_controller.php    # Core algorithm & logic

actions/
  └── get_recommendations.php          # API endpoint

view/
  └── components/
      └── recommendations.php           # Reusable UI component

dashboard_student.php                  # Integration example
```

---

## 🔌 API Usage

### Get Personalized Recommendations

```javascript
fetch('../actions/get_recommendations.php?type=personalized&limit=6')
    .then(response => response.json())
    .then(data => {
        console.log(data.recommendations);
    });
```

**Response:**
```json
{
    "success": true,
    "type": "personalized",
    "count": 6,
    "recommendations": [
        {
            "id": 12,
            "title": "Modern 2BR Apartment",
            "location": "Parklands",
            "price_monthly": 9500,
            "avg_rating": 4.5,
            "recommendation_score": 89.5,
            "recommendation_reasons": [
                "Matches your budget perfectly",
                "In your preferred location: Parklands",
                "Highly rated (4.5/5)"
            ]
        },
        // ... more properties
    ]
}
```

### Get Trending Properties

```javascript
fetch('../actions/get_recommendations.php?type=trending&limit=6')
```

### Get Similar Properties

```javascript
fetch('../actions/get_recommendations.php?type=similar&property_id=15&limit=4')
```

---

## 🎨 UI Integration

### Using the Recommendations Component

```php
<?php require_once 'view/components/recommendations.php'; ?>

<!-- Personalized Recommendations with reasons -->
<?php renderRecommendations('Recommended For You', 'personalized', 6, true); ?>

<!-- Trending Properties without reasons -->
<?php renderRecommendations('Trending Now', 'trending', 6, false); ?>
```

**Parameters:**
- `$title`: Section heading
- `$type`: 'personalized' | 'trending' | 'similar'
- `$limit`: Number of properties to show
- `$showReason`: Display recommendation reasons (true/false)

---

## 📈 Performance Optimization

### Caching Strategy (Future Enhancement)

```php
// Cache recommendations for 1 hour
$cacheKey = "recommendations_student_{$studentId}";
$cached = getFromCache($cacheKey);

if (!$cached) {
    $recommendations = getRecommendedProperties($studentId, 6);
    setCache($cacheKey, $recommendations, 3600);
}
```

### Database Indexing

Recommended indexes:
```sql
-- Speed up collaborative filtering
CREATE INDEX idx_wishlist_student ON wishlist(student_id, property_id);
CREATE INDEX idx_bookings_student ON bookings(student_id, property_id, status);

-- Speed up property queries
CREATE INDEX idx_properties_status ON properties(status, created_at);
CREATE INDEX idx_reviews_property ON reviews(property_id, is_approved);
```

---

## 🧪 Testing the System

### Test Scenario 1: New Student
**Profile**: No wishlist, no bookings
**Expected**: General trending properties, popular listings

### Test Scenario 2: Active Student
**Profile**: 5 wishlist items, 2 bookings, preferred location "Parklands"
**Expected**:
- Properties in Parklands
- Similar price range to previous bookings
- High recommendation scores (70-90%)

### Test Scenario 3: Budget-Conscious Student
**Profile**: Previous bookings all under KSh 8,000
**Expected**:
- Properties priced 5,600 - 10,400 (±30%)
- Budget-friendly options highlighted

---

## 🚀 Future Enhancements

### 1. Machine Learning Model
Train a real ML model using:
- TensorFlow.js for browser-based recommendations
- Python service with scikit-learn
- Historical booking/wishlist data

### 2. Real-Time Personalization
- Track page views
- Click-through rates
- Time spent on property pages

### 3. A/B Testing
- Test different recommendation algorithms
- Measure conversion rates
- Optimize scoring weights

### 4. Geolocation-Based
- Recommend properties near student's university
- Distance-based scoring
- Map integration

### 5. Semantic Search
- Natural language property descriptions
- Vector embeddings for similarity
- "Find me a quiet studio near campus"

### 6. Time-Based Recommendations
- Seasonal adjustments
- Academic calendar awareness
- Move-in date optimization

---

## 🔒 Privacy & Data Usage

### Data Collected:
- ✓ Wishlist additions/removals
- ✓ Booking history
- ✓ Search queries (future)
- ✗ No personal information beyond user ID
- ✗ No third-party data sharing

### User Control:
- All data is anonymized for similarity calculations
- Students can clear wishlist/history anytime
- Recommendations reset with cleared data

---

## 📊 Success Metrics

### KPIs to Track:

1. **Engagement Rate**
   - Click-through rate on recommendations
   - Target: >15%

2. **Conversion Rate**
   - Bookings from recommendations
   - Target: >5%

3. **User Satisfaction**
   - Average recommendation score shown
   - Target: >70%

4. **Coverage**
   - % of students receiving recommendations
   - Target: >80%

5. **Diversity**
   - Variety in recommended properties
   - Prevent filter bubbles

---

## 🐛 Troubleshooting

### No Recommendations Showing

**Cause**: Student has no activity
**Solution**: Show trending/popular properties instead

### Low Recommendation Scores

**Cause**: Insufficient profile data
**Solution**: Encourage wishlist usage, browsing

### Same Properties Repeatedly

**Cause**: Limited property pool or narrow preferences
**Solution**: Diversify scoring, add randomness factor

### Slow Loading

**Cause**: Complex queries on large dataset
**Solution**: Implement caching, database indexing

---

## 📝 Code Examples

### Custom Recommendation Logic

```php
// Get recommendations with custom limit
$recommendations = getRecommendedProperties($studentId, 10);

// Get similar properties
$similarProps = getSimilarProperties($propertyId, 5);

// Get trending
$trending = getTrendingProperties(8);

// Build student profile
$profile = getStudentProfile($studentId);
print_r($profile);
```

### Filtering Recommendations

```php
// Only show apartments
$recommendations = array_filter(
    getRecommendedProperties($studentId, 20),
    fn($p) => $p['property_type'] === 'apartment'
);

// Only show within budget
$recommendations = array_filter(
    getRecommendedProperties($studentId, 20),
    fn($p) => $p['price_monthly'] <= 15000
);
```

---

## 🎓 Educational Value

This recommendation system demonstrates:

1. **Hybrid Algorithms**: Combining multiple approaches
2. **Data-Driven Decisions**: Using user behavior
3. **Scalable Architecture**: Modular, extensible design
4. **Real-World ML**: Practical AI application
5. **User Experience**: Personalization improves discovery

---

## ✅ Implementation Checklist

- [x] Core recommendation algorithm
- [x] Content-based filtering
- [x] Collaborative filtering
- [x] Popularity scoring
- [x] API endpoint
- [x] UI component
- [x] Dashboard integration
- [ ] Caching layer
- [ ] Database indexing
- [ ] A/B testing framework
- [ ] Analytics tracking
- [ ] Performance monitoring

---

## 📚 References

### Recommendation System Types:
- **Content-Based**: Recommends based on item features
- **Collaborative**: Uses user behavior patterns
- **Hybrid**: Combines multiple approaches

### Scoring Techniques:
- **TF-IDF**: Term frequency analysis
- **Cosine Similarity**: Feature vector comparison
- **Matrix Factorization**: Latent feature discovery

---

**Status**: ✅ Production Ready

The AI-powered recommendation system is fully functional and integrated into the CampusDigs Kenya platform, providing personalized property suggestions to enhance student experience and increase booking conversions.
