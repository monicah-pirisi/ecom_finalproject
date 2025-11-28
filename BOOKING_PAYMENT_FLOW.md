# CampusDigs Kenya - Complete Booking to Payment Flow

## Overview
This document explains the complete end-to-end flow from browsing properties → booking → payment → confirmation.

---

## STEP-BY-STEP FLOW

### 1. BROWSE PROPERTIES
**File**: `view/all_properties.php`

Student browses available properties and clicks on one to see details.

↓

### 2. VIEW PROPERTY DETAILS
**File**: `view/single_property.php`

Student sees:
- Property images
- Price, location, amenities
- **Booking form** with:
  - Move-in date picker
  - Lease duration (months)
  - Special message field
  - **"Book Now" button**

↓

### 3. SUBMIT BOOKING REQUEST
**File**: `view/booking_process.php`

When student clicks "Book Now":
- ✅ Validates student is logged in
- ✅ Validates CSRF token
- ✅ Validates move-in date (must be future)
- ✅ Validates lease duration
- ✅ Calls `createBooking()` from `booking_controller.php`

**Database Action**:
```sql
INSERT INTO bookings (
    booking_reference, student_id, property_id, landlord_id,
    move_in_date, lease_duration_months, monthly_rent,
    security_deposit, total_amount, commission_amount,
    landlord_payout, message, status, payment_status
) VALUES (
    'CD202411001', 5, 12, 3,
    '2024-12-01', 4, 8000.00,
    5000.00, 37000.00, 3700.00,
    33300.00, 'Need parking space', 'pending', 'pending'
)
```

**Result**: Booking created with:
- `status` = 'pending' (awaiting landlord approval)
- `payment_status` = 'pending' (not paid yet)

↓

### 4. VIEW BOOKINGS
**File**: `view/student_bookings.php`

Student is redirected to "My Bookings" page where they see:
- All their bookings
- Booking status (pending/approved/rejected)
- **Payment status badge** (pending/paid)
- **"Pay Now" button** (if payment_status = 'pending')

**SQL Query**:
```sql
SELECT b.*,
       p.title as property_title,
       p.location as property_location,
       p.price_monthly as property_price
FROM bookings b
LEFT JOIN properties p ON b.property_id = p.id
WHERE b.student_id = 5
```

Returns booking with `payment_status` = 'pending'

↓

### 5. CLICK "PAY NOW"
**File**: `view/booking_payment.php?booking_id=123`

Student clicks "Pay Now" button, which opens payment page showing:
- Property image and title
- Booking details (check-in, check-out, duration)
- **Amount to pay** (e.g., KES 37,000.00)
- Student information
- **"Pay Now" button**

**Data Loaded**:
```php
$booking = getBookingById($booking_id); // From booking_controller.php
$property = getPropertyById($booking['property_id']);
$amount_to_pay = $property['price'];
```

↓

### 6. INITIALIZE PAYMENT
**File**: `actions/paystack_initialize.php`

When student clicks "Pay Now":

**JavaScript sends**:
```javascript
fetch('/campus_digs/actions/paystack_initialize.php', {
    method: 'POST',
    body: JSON.stringify({
        booking_id: 123,
        property_id: 12,
        amount: 37000.00,
        payment_type: 'full'
    })
})
```

**Server processes**:
```php
// Generate unique reference
$reference = 'CDIGS-P12-U5-1731801234';

// Initialize with Paystack API
$paystack_response = paystack_initialize_transaction(
    $amount,           // 37000.00
    $student_email,    // john@student.edu
    $reference,        // CDIGS-P12-U5-1731801234
    $metadata          // booking_id, property_id, etc.
);

// Store in session
$_SESSION['paystack_payment'] = [
    'reference' => $reference,
    'booking_id' => 123,
    'property_id' => 12,
    'amount' => 37000.00,
    'timestamp' => time()
];

// Return authorization URL
return json_encode([
    'status' => 'success',
    'authorization_url' => 'https://checkout.paystack.com/abcd1234',
    'reference' => $reference
]);
```

**Paystack API Call**:
```
POST https://api.paystack.com/transaction/initialize
Headers:
  Authorization: Bearer sk_test_e0a6d4f1eb60277d5db8c30c4a4e76f5c0a3ed07
Body:
  {
    "email": "john@student.edu",
    "amount": 3700000,  // 37,000.00 * 100 (cents)
    "reference": "CDIGS-P12-U5-1731801234",
    "currency": "KES",
    "callback_url": "http://localhost/campus_digs/actions/paystack_callback.php",
    "metadata": {
      "booking_id": 123,
      "property_id": 12,
      "property_title": "Modern Studio Apartment",
      "student_id": 5,
      "payment_type": "full"
    }
  }
```

↓

### 7. REDIRECT TO PAYSTACK
**Location**: Paystack payment page (external)

Student is redirected to: `https://checkout.paystack.com/abcd1234`

Student enters payment details:
- Card number
- Expiry date
- CVV
- PIN (if required)
- OTP (if required)

Student completes payment on Paystack's secure page.

↓

### 8. PAYSTACK CALLBACK
**File**: `actions/paystack_callback.php?reference=CDIGS-P12-U5-1731801234`

After payment, Paystack redirects back to your site with the reference.

**Page displays**:
- Loading spinner
- "Verifying your payment..."

**JavaScript auto-verifies**:
```javascript
fetch('/campus_digs/actions/paystack_verify_payment.php', {
    method: 'POST',
    body: JSON.stringify({
        reference: 'CDIGS-P12-U5-1731801234'
    })
})
```

↓

### 9. VERIFY PAYMENT
**File**: `actions/paystack_verify_payment.php`

**Server verifies with Paystack API**:
```
GET https://api.paystack.com/transaction/verify/CDIGS-P12-U5-1731801234
Headers:
  Authorization: Bearer sk_test_e0a6d4f1eb60277d5db8c30c4a4e76f5c0a3ed07
```

**Paystack response**:
```json
{
  "status": true,
  "data": {
    "status": "success",
    "reference": "CDIGS-P12-U5-1731801234",
    "amount": 3700000,
    "currency": "KES",
    "paid_at": "2024-11-27T10:30:00.000Z",
    "channel": "card",
    "authorization": {
      "authorization_code": "AUTH_abc123xyz"
    }
  }
}
```

**Server validates**:
```php
// Check payment status
if ($transaction_data['status'] !== 'success') {
    return error('Payment not successful');
}

// Verify amount matches
$amount_paid = $transaction_data['amount'] / 100; // 37000.00
if (abs($amount_paid - $expected_amount) > 1) {
    return error('Amount mismatch');
}

// Check for duplicate
if (paymentReferenceExists($reference)) {
    return error('Payment already recorded');
}
```

**Database Transaction** (Atomic):
```php
$conn->begin_transaction();

try {
    // 1. Record payment
    INSERT INTO payments (
        booking_id, student_id, property_id, amount, currency,
        payment_method, payment_reference, authorization_code,
        payment_status, paid_at
    ) VALUES (
        123, 5, 12, 37000.00, 'KES',
        'card', 'CDIGS-P12-U5-1731801234', 'AUTH_abc123xyz',
        'completed', '2024-11-27 10:30:00'
    );

    // 2. Update booking
    UPDATE bookings
    SET payment_status = 'paid',
        payment_reference = 'CDIGS-P12-U5-1731801234'
    WHERE id = 123 AND student_id = 5;

    // 3. Log activity
    INSERT INTO activity_log (
        user_id, activity_type, description
    ) VALUES (
        5, 'payment_completed',
        'Paid KES 37,000.00 for Modern Studio Apartment'
    );

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    throw $e;
}
```

**Returns**:
```json
{
  "status": "success",
  "verified": true,
  "payment_id": 78,
  "amount_paid": "37000.00",
  "property_title": "Modern Studio Apartment",
  "payment_reference": "CDIGS-P12-U5-1731801234"
}
```

↓

### 10. PAYMENT SUCCESS PAGE
**File**: `view/payment_success.php?reference=CDIGS-P12-U5-1731801234`

Student sees confirmation page with:
- ✅ Green checkmark
- Receipt number
- Payment details (amount, date, reference)
- Property details
- Booking dates
- "View Bookings" button
- "Print Receipt" button

**SQL Query**:
```sql
SELECT p.*, u.full_name as student_name, u.email as student_email,
       pr.title as property_title, pr.price as property_price,
       b.check_in_date, b.check_out_date
FROM payments p
LEFT JOIN users u ON p.student_id = u.id
LEFT JOIN properties pr ON p.property_id = pr.id
LEFT JOIN bookings b ON p.booking_id = b.id
WHERE p.payment_reference = 'CDIGS-P12-U5-1731801234'
```

---

## COMPLETE DATA FLOW DIAGRAM

```
┌─────────────────┐
│  Student Logs   │
│      In         │
└────────┬────────┘
         ↓
┌─────────────────┐
│  Browse         │
│  Properties     │
│  (all_props.php)│
└────────┬────────┘
         ↓
┌─────────────────┐
│  View Property  │
│  Details        │
│  (single.php)   │
└────────┬────────┘
         ↓
┌─────────────────┐
│  Fill Booking   │
│  Form & Submit  │
└────────┬────────┘
         ↓
┌─────────────────┐
│  booking_       │
│  process.php    │
│  ✓ Validates    │
│  ✓ Calls        │
│    createBooking│
└────────┬────────┘
         ↓
┌─────────────────────────────┐
│  DATABASE: Insert Booking   │
│  status = 'pending'         │
│  payment_status = 'pending' │
└────────┬────────────────────┘
         ↓
┌─────────────────┐
│  Redirect to    │
│  My Bookings    │
│  (student_      │
│   bookings.php) │
└────────┬────────┘
         ↓
┌─────────────────┐
│  Shows "Pay     │
│  Now" Button    │
│  (if unpaid)    │
└────────┬────────┘
         ↓
┌─────────────────┐
│  Click Pay Now  │
│  → booking_     │
│    payment.php  │
└────────┬────────┘
         ↓
┌─────────────────┐
│  Shows Amount   │
│  & Pay Button   │
└────────┬────────┘
         ↓
┌─────────────────┐
│  JavaScript:    │
│  POST to        │
│  paystack_      │
│  initialize.php │
└────────┬────────┘
         ↓
┌────────────────────────────┐
│  paystack_initialize.php   │
│  1. Generate reference     │
│  2. Call Paystack API      │
│  3. Store in session       │
│  4. Return auth URL        │
└────────┬───────────────────┘
         ↓
┌─────────────────┐
│  Redirect to    │
│  Paystack       │
│  checkout.      │
│  paystack.com   │
└────────┬────────┘
         ↓
┌─────────────────┐
│  Student Enters │
│  Card Details   │
│  & Completes    │
│  Payment        │
└────────┬────────┘
         ↓
┌─────────────────┐
│  Paystack       │
│  Redirects to   │
│  paystack_      │
│  callback.php   │
└────────┬────────┘
         ↓
┌─────────────────┐
│  Callback Page  │
│  Shows Loading  │
│  Spinner        │
└────────┬────────┘
         ↓
┌─────────────────┐
│  JavaScript:    │
│  POST to        │
│  paystack_      │
│  verify.php     │
└────────┬────────┘
         ↓
┌────────────────────────────┐
│  paystack_verify.php       │
│  1. Verify with Paystack   │
│  2. Validate amount        │
│  3. Start DB transaction   │
│  4. Insert payment record  │
│  5. Update booking status  │
│  6. Log activity           │
│  7. Commit transaction     │
└────────┬───────────────────┘
         ↓
┌─────────────────────────────┐
│  DATABASE: Payment Records  │
│  • payments table          │
│    - payment_status =       │
│      'completed'            │
│  • bookings table           │
│    - payment_status = 'paid'│
│    - payment_reference      │
└────────┬────────────────────┘
         ↓
┌─────────────────┐
│  Redirect to    │
│  payment_       │
│  success.php    │
└────────┬────────┘
         ↓
┌─────────────────┐
│  Show Receipt   │
│  ✓ Payment      │
│    confirmed    │
│  ✓ Booking paid │
│  ✓ Details      │
└─────────────────┘
```

---

## KEY FILES AND THEIR ROLES

### VIEW FILES (Student-facing)
| File | Purpose |
|------|---------|
| `view/all_properties.php` | Browse all properties |
| `view/single_property.php` | Property details + booking form |
| `view/booking_process.php` | Process booking submission |
| `view/student_bookings.php` | View all bookings + "Pay Now" button |
| `view/booking_payment.php` | Payment initiation page |
| `view/payment_success.php` | Payment confirmation |
| `view/payment_history.php` | All past payments |

### ACTION FILES (Server-side)
| File | Purpose |
|------|---------|
| `actions/paystack_initialize.php` | Initialize payment with Paystack |
| `actions/paystack_verify_payment.php` | Verify and record payment |
| `actions/paystack_callback.php` | Callback page after Paystack |

### CONTROLLER FILES
| File | Purpose |
|------|---------|
| `controllers/booking_controller.php` | Booking business logic |
| `controllers/payment_controller.php` | Payment business logic |
| `controllers/property_controller.php` | Property business logic |

### MODEL FILES (Database)
| File | Purpose |
|------|---------|
| `classes/booking_class.php` | Booking database operations |
| `classes/payment_class.php` | Payment database operations |

### CONFIG FILES
| File | Purpose |
|------|---------|
| `includes/paystack_config.php` | Paystack API configuration |

---

## DATABASE TABLES

### bookings
```sql
id, booking_reference, student_id, property_id, landlord_id,
move_in_date, lease_duration_months, monthly_rent, security_deposit,
total_amount, commission_amount, landlord_payout, message,
status (pending/approved/rejected/cancelled/completed),
payment_status (pending/paid/refunded),  ← ADDED
payment_reference (CDIGS-P12-U5-1731801234),  ← ADDED
created_at, updated_at
```

### payments
```sql
id, booking_id, student_id, property_id, amount, currency,
payment_method, payment_reference (unique), authorization_code,
payment_status (pending/completed/failed/refunded),
paid_at, created_at, updated_at
```

---

## TESTING THE COMPLETE FLOW

### Prerequisites
1. ✅ Run `database/payments_table.sql` to create payments table
2. ✅ Ensure bookings table has payment_status and payment_reference columns
3. ✅ Test API keys configured in `includes/paystack_config.php`

### Test Steps
1. **Login as Student** → http://localhost/campus_digs/login
2. **Browse Properties** → view/all_properties.php
3. **View Property** → view/single_property.php?id=1
4. **Fill Booking Form**:
   - Move-in date: (pick future date)
   - Lease duration: 4 months
   - Message: "Test booking"
   - Click "Book Now"
5. **Check My Bookings** → Should see new booking with "Pay Now" button
6. **Click "Pay Now"** → Opens booking_payment.php
7. **Click "Pay Now" on payment page** → Redirects to Paystack
8. **Enter Test Card**:
   - Card: 4084084084084081
   - CVV: 408
   - Expiry: 12/25
   - PIN: 0000
   - OTP: 123456
9. **Complete Payment** → Redirects to callback → Shows success
10. **Verify**:
    - Check `payments` table → payment_status = 'completed'
    - Check `bookings` table → payment_status = 'paid'
    - Check booking card → No more "Pay Now" button
    - Check Payment History → Payment appears

---

## FIXED BUGS

✅ **Bug 1**: Bookings created without payment_status
**Fix**: Updated `createBooking()` to set `payment_status = 'pending'`

✅ **Bug 2**: Booking queries didn't return payment_status
**Fix**: Queries use `SELECT b.*` which includes all columns

✅ **Bug 3**: Missing getBookingById() helper function
**Fix**: Added to `booking_controller.php` for payment pages to access bookings

✅ **Bug 4**: payment_history.php used wrong navbar include
**Fix**: Changed to sidebar layout matching other student pages

---

**Last Updated**: November 27, 2024
