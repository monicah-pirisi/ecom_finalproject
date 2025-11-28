# Paystack Payment Integration - CampusDigs Kenya

## Overview
This document describes the Paystack payment gateway integration for the CampusDigs Kenya property booking platform. The integration enables students to make secure payments for property bookings using cards, bank transfers, and mobile money.

## API Credentials

### Test Environment (Currently Active)
- **Secret Key**: `sk_test_e0a6d4f1eb60277d5db8c30c4a4e76f5c0a3ed07`
- **Public Key**: `pk_test_b4683cde714785e14069edfed46bb8b0b2d8760b`

### Production Environment (For Live Deployment)
Replace the test keys in `includes/paystack_config.php` with your live keys:
- Secret Key: `sk_live_YOUR_LIVE_SECRET_KEY`
- Public Key: `pk_live_YOUR_LIVE_PUBLIC_KEY`

## Architecture

### Payment Flow

```
1. Student clicks "Pay Now" on booking
   ↓
2. JavaScript sends payment details to paystack_initialize.php
   ↓
3. Server initializes transaction with Paystack API
   ↓
4. Student redirected to Paystack payment page
   ↓
5. Student completes payment on Paystack
   ↓
6. Paystack redirects to paystack_callback.php
   ↓
7. Callback page verifies payment with paystack_verify_payment.php
   ↓
8. Server records payment and updates booking
   ↓
9. Student sees payment confirmation
```

## File Structure

### Configuration
- **includes/paystack_config.php**: API keys, endpoints, helper functions

### Actions (Server-side)
- **actions/paystack_initialize.php**: Initialize payment transaction
- **actions/paystack_verify_payment.php**: Verify and record payment
- **actions/paystack_callback.php**: User-facing callback page

### Database
- **database/payments_table.sql**: Payment records schema
- **classes/payment_class.php**: Payment database operations
- **controllers/payment_controller.php**: Payment wrapper functions

### Views (Student-facing)
- **view/booking_payment.php**: Payment initiation page
- **view/payment_success.php**: Payment confirmation page
- **view/payment_history.php**: Student payment history
- **view/student_bookings.php**: Bookings with "Pay Now" button

### Admin
- **admin/payments.php**: Admin payment management dashboard

## Database Schema

### `payments` Table
```sql
id                  INT PRIMARY KEY AUTO_INCREMENT
booking_id          INT (nullable - can be NULL for standalone payments)
student_id          INT NOT NULL
property_id         INT NOT NULL
amount              DECIMAL(10,2) NOT NULL
currency            VARCHAR(10) DEFAULT 'KES'
payment_method      VARCHAR(50) NOT NULL (card, bank, mobile_money)
payment_reference   VARCHAR(255) UNIQUE NOT NULL
authorization_code  VARCHAR(255) (for recurring payments)
payment_status      ENUM('pending', 'completed', 'failed', 'refunded')
paid_at             DATETIME
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

### `bookings` Table Additions
```sql
payment_reference   VARCHAR(255) (links to payments table)
payment_status      ENUM('pending', 'paid', 'refunded')
```

## Key Functions

### paystack_initialize_transaction($amount, $email, $reference, $metadata)
Initializes payment with Paystack API.
- **Parameters**:
  - `$amount`: Amount in KES (will be converted to cents)
  - `$email`: Customer email
  - `$reference`: Unique transaction reference
  - `$metadata`: Additional data (booking_id, property_id, etc.)
- **Returns**: Array with authorization_url for redirect

### paystack_verify_transaction($reference)
Verifies payment status with Paystack API.
- **Parameters**:
  - `$reference`: Transaction reference to verify
- **Returns**: Array with transaction details and status

### createPayment($payment_data)
Records payment in database.
- **Parameters**: Array with payment details
- **Returns**: Payment ID on success

### getPaymentByReference($reference)
Retrieves payment record by reference.
- **Returns**: Payment data array

### getStudentPayments($student_id)
Gets all payments for a student.
- **Returns**: Array of payments

## Payment Reference Format

```
CDIGS-P{property_id}-U{user_id}-{timestamp}

Example: CDIGS-P42-U15-1731801234
```

This ensures unique references for each transaction.

## Currency Handling

Paystack requires amounts in the smallest currency unit (cents/kobo).

```php
// Converting KES to cents
$amount_in_cents = $amount * 100;

// Example: KES 5,000.00 → 500000 cents
```

When receiving payment data from Paystack:
```php
// Converting cents back to KES
$amount_in_kes = $paystack_amount / 100;

// Example: 500000 cents → KES 5,000.00
```

## Security Features

### 1. Server-Side Verification
All payments are verified server-side with Paystack API. Never trust client-side data.

### 2. Amount Verification
```php
// Verify amount matches (1 KES tolerance for rounding)
if (abs($amount_paid - $expected_amount) > 1) {
    // Reject payment
}
```

### 3. User Authentication
```php
// Only logged-in students can make payments
if (!isLoggedIn() || $_SESSION['user_type'] !== 'student') {
    exit();
}
```

### 4. Payment Status Validation
```php
// Only mark as paid if Paystack confirms success
if ($payment_status !== 'success') {
    // Reject payment
}
```

### 5. Database Transactions
```php
// Atomic operations with rollback on error
$conn->begin_transaction();
try {
    // Record payment
    // Update booking
    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
}
```

## Testing

### Test Cards (Paystack Sandbox)
```
Success: 4084084084084081
Insufficient Funds: 4084080000000408
Declined: 4084084084084084

CVV: 408
Expiry: Any future date
PIN: 0000
OTP: 123456
```

### Testing Process
1. Create a booking as a student
2. Click "Pay Now" on the booking
3. Use test card details on Paystack payment page
4. Complete payment
5. Verify payment is recorded in database
6. Check booking payment_status is updated to 'paid'

## Common Issues & Solutions

### Issue: Payment initialized but verification fails
**Solution**: Check error logs in `error_log`. Verify secret key is correct.

### Issue: Amount mismatch error
**Solution**: Ensure amount is being converted correctly (×100 for initialize, ÷100 for verify).

### Issue: Payment reference not found
**Solution**: Check session storage in `paystack_initialize.php` and retrieval in `paystack_verify_payment.php`.

### Issue: Callback page doesn't load
**Solution**: Verify `PAYSTACK_CALLBACK_URL` in config points to correct domain.

## Production Deployment Checklist

- [ ] Replace test API keys with live keys in `includes/paystack_config.php`
- [ ] Update `PAYSTACK_CALLBACK_URL` to production domain
- [ ] Test payment flow on staging environment
- [ ] Enable SSL/HTTPS (required for live payments)
- [ ] Set up webhook URL in Paystack dashboard for payment notifications
- [ ] Configure proper error logging and monitoring
- [ ] Set up email notifications for successful payments
- [ ] Test refund process
- [ ] Review and update payment security measures
- [ ] Document refund and cancellation policies

## API Endpoints

### Initialize Payment
```
POST /actions/paystack_initialize.php

Request:
{
    "booking_id": 123,
    "property_id": 45,
    "amount": 5000.00,
    "payment_type": "full"
}

Response:
{
    "status": "success",
    "authorization_url": "https://checkout.paystack.com/...",
    "reference": "CDIGS-P45-U12-1731801234"
}
```

### Verify Payment
```
POST /actions/paystack_verify_payment.php

Request:
{
    "reference": "CDIGS-P45-U12-1731801234"
}

Response:
{
    "status": "success",
    "verified": true,
    "payment_id": 78,
    "amount_paid": "5000.00",
    "property_title": "Modern Studio Apartment",
    "payment_reference": "CDIGS-P45-U12-1731801234"
}
```

## Error Logging

All payment operations are logged for debugging:

```php
error_log("Paystack Initialize Request - Amount: KES $amount");
error_log("Paystack Verify Response: $response");
error_log("Payment recorded - ID: $payment_id");
```

Check server error logs for troubleshooting.

## Support & Resources

- **Paystack Documentation**: https://paystack.com/docs
- **Paystack Dashboard**: https://dashboard.paystack.com
- **API Reference**: https://paystack.com/docs/api
- **Test Cards**: https://paystack.com/docs/payments/test-payments

## Metadata Structure

Metadata sent to Paystack for each transaction:

```php
$metadata = [
    'booking_id' => 123,
    'property_id' => 45,
    'property_title' => 'Modern Studio Apartment',
    'student_id' => 12,
    'student_name' => 'John Doe',
    'payment_type' => 'full',
    'custom_fields' => [
        [
            'display_name' => 'Property',
            'variable_name' => 'property_title',
            'value' => 'Modern Studio Apartment'
        ],
        [
            'display_name' => 'Student',
            'variable_name' => 'student_name',
            'value' => 'John Doe'
        ]
    ]
]
```

This appears in the Paystack dashboard for easy transaction identification.

## Future Enhancements

1. **Recurring Payments**: Use authorization_code for monthly rent payments
2. **Payment Plans**: Deposit + installments support
3. **Webhooks**: Real-time payment notifications from Paystack
4. **Refund API**: Automated refund processing through Paystack
5. **Payment Analytics**: Detailed revenue reports and charts
6. **Email Receipts**: Automated email confirmations with PDF receipts
7. **SMS Notifications**: Payment confirmations via SMS
8. **Multi-Currency**: Support for USD, EUR alongside KES

---

**Last Updated**: November 2024
**Version**: 1.0
