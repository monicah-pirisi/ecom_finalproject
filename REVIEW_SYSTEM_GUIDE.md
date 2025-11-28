# Student Review System Guide

## Overview
The review system allows students to rate and review properties after their lease period ends. Reviews must be approved by admin before being publicly visible.

## Booking & Review Workflow

### Step 1: Create & Approve Booking
1. **Student** creates a booking for a property
   - Status: `pending`
   - Payment Status: `pending`

2. **Landlord** reviews and approves the booking
   - Go to Dashboard → Manage Bookings
   - Click on pending booking
   - Click "Approve Booking"
   - Status: `approved`

### Step 2: Payment
3. **Student** completes payment
   - Go to My Bookings
   - Click "Pay Now" on approved booking
   - Complete Paystack payment
   - Payment Status: `paid`

### Step 3: Complete Booking (New!)
4. **Landlord** marks booking as completed
   - Go to Manage Bookings
   - Click on a paid booking
   - After lease period ends, click **"Mark as Completed"**
   - Status: `completed`
   - This action enables students to leave reviews

### Step 4: Leave Review
5. **Student** submits review
   - Go to My Bookings
   - Find completed booking
   - Click **"Leave a Review"** button (only visible for completed bookings)
   - Select rating (1-5 stars)
   - Write optional comment (max 1000 characters)
   - Click "Submit Review"
   - Review Status: `pending` (awaiting admin approval)

### Step 5: Moderate Review
6. **Admin** approves or rejects review
   - Go to Admin Panel → Manage Reviews (if implemented)
   - Review student reviews
   - Approve good reviews → publicly visible
   - Reject inappropriate reviews

## Quick Test Instructions

To test the review system immediately:

### 1. Create Test Booking (as Student)
```
- Login as student
- Browse properties and create a booking
- Note the booking ID
```

### 2. Approve Booking (as Landlord)
```
- Login as landlord (property owner)
- Go to Manage Bookings
- Approve the student's booking
```

### 3. Complete Payment (as Student)
```
- Login as student
- Go to My Bookings
- Click "Pay Now" and complete test payment
```

### 4. Mark as Completed (as Landlord)
```
- Login as landlord
- Go to Manage Bookings
- Click on the paid booking
- Click "Mark as Completed" button
```

### 5. Leave Review (as Student)
```
- Login as student
- Go to My Bookings
- Find the completed booking
- Click "Leave a Review"
- Select star rating and write comment
- Submit
```

### 6. Approve Review (as Admin)
```
- Login as admin
- Go to Manage Reviews (if page exists)
- Approve the student's review
- Review now appears on property page
```

## Database Tables

### bookings table
- `status`: pending, approved, rejected, cancelled, completed
- `payment_status`: pending, paid, refunded

### reviews table
- `property_id`: Which property is being reviewed
- `student_id`: Who wrote the review
- `booking_id`: Which booking this review is for
- `rating`: 1-5 stars
- `comment`: Review text (optional, max 1000 chars)
- `is_approved`: 0 = pending, 1 = approved
- `created_at`: When review was submitted

## Key Features

✓ Only students with completed bookings can review
✓ One review per booking (prevents duplicate reviews)
✓ Reviews require admin approval before being public
✓ 5-star rating system with optional text comment
✓ Real-time star selection UI
✓ Reviews linked to specific bookings for authenticity

## Files Modified

1. `view/student_bookings.php` - Added review button and modal
2. `actions/submit_review_action.php` - Handles review submission
3. `controllers/review_controller.php` - Review business logic
4. `classes/booking_class.php` - Added has_review field to queries
5. `view/landlord/booking_details.php` - Added "Mark as Completed" button
6. `actions/landlord_bookings_action.php` - Complete booking action handler

## Notes

- The "Mark as Completed" button only appears for bookings that have been paid
- Landlords should mark bookings as completed after the lease term ends
- Students cannot review until landlord marks booking as completed
- This prevents fake reviews from students who haven't actually stayed at the property
- Reviews are moderated to ensure quality and prevent spam

## Troubleshooting

**Q: Student can't see "Leave a Review" button**
A: Check that:
   1. Booking status is `completed` (landlord must mark it)
   2. Student hasn't already reviewed this booking
   3. Student is viewing their "My Bookings" page

**Q: Landlord can't see "Mark as Completed" button**
A: Check that:
   1. Booking status is `approved`
   2. Payment status is `paid`
   3. Landlord is viewing the booking details page

**Q: Review doesn't appear on property page**
A: Reviews require admin approval first. Check admin panel to approve the review.
