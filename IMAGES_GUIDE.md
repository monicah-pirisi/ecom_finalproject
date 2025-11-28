# 📸 CampusDigs Kenya - Images & Video Guide

## 🎯 What We've Added:

### ✅ Hero Section (Top of Homepage)
- **Video Background Option** (commented out - ready to use)
- **Image Background Option** (commented out - ready to use)
- Professional gradient overlay that makes text readable

### ✅ About Section (REDESIGNED!)
- **Large background image** (modern apartment) covering 3/4 of section
- **Featured image** (happy students) on left side taking 1/3 space
- **Text overlay** with gradient for perfect readability
- White text with shadows for maximum contrast
- Call-to-action buttons included

### ✅ CTA Section
- Background image with gradient overlay
- Currently using Unsplash image

### ✅ Login & Registration Pages
- **Split-screen design** with image on left side
- **Professional student images** (separate for login and register)
- **Gradient overlay** for text readability
- **Glass-morphism feature cards** with hover effects
- Using local images: `assets/images/auth/login.jpg` & `register.jpg`

### ✅ Property Details Page
- **Professional image gallery** with thumbnails
- **Full-screen lightbox** with keyboard navigation
- **Lazy loading** for performance
- **Mobile-responsive** design with touch controls

### ✅ Profile Pages (Student & Landlord)
- **Cover photo headers** with professional images
- **Avatar system** with initials placeholder
- **Dashboard stats** with color-coded icons
- **Verification badges** display
- **Responsive design** for all screen sizes

### ✅ Help & Support Pages (NEW!)
- **Hero sections** with friendly customer support images
- **Icon-based help topics** grid (4 categories)
- **Safety tips section** with infographic style
- **Contact methods** with 4 channels
- **Enhanced FAQ** with 6 comprehensive items
- **Accessibility-focused** design throughout
- **Light, illustration-based** layout

---

## 📁 Folder Structure Created:

```
campus_digs/
└── assets/
    └── images/
        ├── hero/          (For hero section backgrounds)
        ├── about/         (For about section images)
        ├── features/      (For feature images)
        └── auth/          (For login/register page backgrounds)
```

---

## 🌐 Where to Get Professional Images (FREE):

### **1. Unsplash** (unsplash.com) - RECOMMENDED
- **Best for:** High-quality, professional photos
- **License:** Free to use, no attribution required
- **Search terms:**
  - "african students"
  - "university students kenya"
  - "student accommodation"
  - "modern apartment"
  - "happy students studying"

### **2. Pexels** (pexels.com)
- **Best for:** Photos and videos
- **License:** Free, no attribution needed
- **Search terms:**
  - "kenyan university"
  - "student housing"
  - "university campus"
  - "students learning"

### **3. Pixabay** (pixabay.com)
- **Best for:** Both images and illustrations
- **License:** Free for commercial use

### **4. Coverr** (coverr.co)
- **Best for:** Background videos (10-15 seconds, looping)
- **License:** Free
- **Recommended:** Campus life, students studying, modern apartments

---

## 🎬 How to Add a Video Background to Hero Section:

### Step 1: Download a Video
1. Go to **coverr.co** or **pexels.com/videos**
2. Search: "students studying" or "university campus"
3. Download the MP4 file (choose 1080p quality)
4. Keep file size under 5MB (compress if needed using handbrake.fr)

### Step 2: Add Video to Your Site
1. Save video as: `assets/images/hero/hero-video.mp4`
2. In `index.php`, find line 580-585 and **uncomment** the video code:

```php
<!-- BEFORE (commented): -->
<!--
<video class="hero-bg-video" autoplay muted loop playsinline>
    <source src="assets/images/hero/hero-video.mp4" type="video/mp4">
</video>
-->

<!-- AFTER (uncommented): -->
<video class="hero-bg-video" autoplay muted loop playsinline>
    <source src="assets/images/hero/hero-video.mp4" type="video/mp4">
</video>
```

---

## 🖼️ How to Add an Image Background to Hero Section:

### Step 1: Download an Image
1. Go to **unsplash.com**
2. Search: "kenyan university campus" or "student accommodation"
3. Download image (choose Large size, ~1920x1080px)
4. Save as: `assets/images/hero/hero-bg.jpg`

### Step 2: Add Image to Your Site
1. In `index.php`, find line 588 and **uncomment** the image code:

```php
<!-- BEFORE (commented): -->
<!-- <div class="hero-bg-image" style="background-image: url('assets/images/hero/hero-bg.jpg');"></div> -->

<!-- AFTER (uncommented): -->
<div class="hero-bg-image" style="background-image: url('assets/images/hero/hero-bg.jpg');"></div>
```

---

## 📷 How to Replace About Section Images:

### NEW DESIGN! The About section now has 2 images:

### Current Images (Unsplash CDN - Already Working):
1. **Background Image:** Modern apartment interior (large, covers 3/4 of section)
2. **Featured Image:** Happy students (small, left side, 1/3 of section)

### To Use Your Own Images:

1. **Download 2 images from Unsplash:**
   - **Background Image:** Modern apartment interior or campus building (wide shot)
     - Size: 1600x900px (landscape)
     - Search: "modern apartment interior" or "student accommodation"
   - **Featured Image:** Happy students group photo
     - Size: 400x500px (portrait)
     - Search: "african students smiling" or "happy university students"

2. **Optimize images:**
   - Background: 1600x900px, compress to under 500KB
   - Featured: 400x500px, compress to under 200KB
   - Use online tool: tinypng.com or squoosh.app

3. **Save images:**
   ```
   assets/images/about/background.jpg
   assets/images/about/students-featured.jpg
   ```

4. **Update index.php (around line 1054-1060):**
   ```php
   <!-- Background Image -->
   <div class="about-section-image" style="background-image: url('assets/images/about/background.jpg');">

   <!-- Featured Image -->
   <div class="about-featured-image">
       <img src="assets/images/about/students-featured.jpg" alt="Happy Students" loading="lazy">
   </div>
   ```

---

## 🎨 How to Replace CTA Background Image:

1. **Download image:** Wide image (1600x400px) of students or campus
2. **Save as:** `assets/images/cta-bg.jpg`
3. **Update index.php (around line 959):**
   ```php
   style="background-image: url('assets/images/cta-bg.jpg');"
   ```

---

## 🏠 Property Details Page - Image Gallery (NEW!):

### Current Setup:
- **Professional image gallery** with thumbnail navigation
- **Lightbox modal** for full-screen viewing
- **Lazy loading** for better performance
- **Keyboard navigation** (arrow keys and escape)
- **Active thumbnail highlighting** with green border
- **Image counter** showing current image / total images
- **Zoom icon** for easy full-screen access

### Image Requirements for Properties:

**Main Property Images:**
- **Location:** `uploads/properties/` (database-managed)
- **Quantity:** 5-10 images per property (minimum 3 recommended)
- **Size:** 1200x800px (3:2 aspect ratio)
- **Format:** JPEG (.jpg) preferred for photos
- **File Size:** Under 300KB each (compress using tinypng.com)
- **Quality:** 80-85% JPEG quality

**Image Types to Include (Per Property):**
1. **Exterior Shot** - Building front view (wide angle)
2. **Living Room/Bedroom** - Main living space (well-lit)
3. **Kitchen** - Clean, organized kitchen area
4. **Bathroom** - Modern, clean bathroom
5. **Security Features** - Gate, CCTV, security guard post
6. **Common Areas** - Study areas, lounges, etc.
7. **Neighborhood** - Nearby campus, shops, transport

### Features Implemented:
- ✅ **Main image display** (500px height, rounded corners)
- ✅ **Thumbnail gallery** (4 columns on desktop, 2 on mobile)
- ✅ **Active thumbnail border** (green, 3px with glow effect)
- ✅ **Hover effects** on thumbnails (lift up + shadow)
- ✅ **Click to view full-screen** (main image and thumbnails)
- ✅ **Full-screen lightbox modal** with dark background
- ✅ **Navigation buttons** (previous/next with circular design)
- ✅ **Keyboard shortcuts** (← → arrows, ESC to close)
- ✅ **Image counter** (both in gallery and lightbox)
- ✅ **Lazy loading** for thumbnails (first image loads immediately)

### How Landlords Should Upload Images:

1. **Take Professional Photos:**
   - Use good lighting (daytime, natural light)
   - Clean and organize the space before shooting
   - Use landscape orientation (horizontal)
   - Avoid blurry or dark images
   - Show actual property condition (honesty builds trust)

2. **Optimize Images:**
   - Resize to 1200x800px using any photo editor
   - Compress to under 300KB using tinypng.com or squoosh.app
   - Rename files descriptively: `property-1-exterior.jpg`, `property-1-bedroom.jpg`

3. **Upload Order (Recommended):**
   - 1st image: Best exterior shot (this shows first)
   - 2nd image: Main bedroom/living room
   - 3rd image: Kitchen
   - 4th image: Bathroom
   - 5th+ images: Additional rooms, amenities, security features

### CSS Customization:
The gallery appearance can be customized in [css/property-details.css](css/property-details.css?v=1):
- Thumbnail border color: `.property-thumbnail.active` (line 83-86)
- Main image height: `.property-main-image` (line 65-70)
- Lightbox background: `#imageLightbox .modal-content` (line 52-54)

---

## 👤 Profile Pages - Student & Landlord (NEW!):

### Current Setup:
- **Cover Photo Header** with professional student/campus image
- **Avatar System** with initials placeholder (upload feature coming soon)
- **Dashboard Stats** with colorful icons
- **Responsive Design** adapting to all screen sizes
- **Professional Layout** with modern card design

### Image Requirements:

**Cover Photo:**
- **Size:** 1600x300px (16:3 aspect ratio, wide)
- **Format:** JPEG (.jpg)
- **File Size:** Under 400KB (compress using tinypng.com)
- **Current:** Using Unsplash CDN (students on campus)
- **Location (future):** `uploads/covers/` (when upload feature is added)
- **Theme:** Professional, campus life, success, studying

**Profile Avatar:**
- **Size:** 400x400px (1:1 square aspect ratio)
- **Format:** JPEG (.jpg) or PNG (.png) if transparency needed
- **File Size:** Under 200KB
- **Current:** Initials placeholder with gradient background
- **Location (future):** `uploads/avatars/` (when upload feature is added)
- **Theme:** Professional headshot or casual photo

### Features Implemented:

✅ **Profile Header:**
- 300px height cover photo (200px on mobile)
- Professional gradient overlay fallback
- "Change Cover" button placeholder (disabled, coming soon)
- Smooth responsive behavior

✅ **Avatar Display:**
- 150px circular avatar (100px on mobile)
- White border with shadow
- Initials placeholder (2-letter) if no photo uploaded
- Gradient background (green to gold)
- Camera button for future upload feature

✅ **Dashboard Stats:**
- Icon-based visual stats (bookings, wishlist, reviews)
- Color-coded backgrounds (blue, red, yellow, teal)
- Hover effects with smooth transitions
- Clickable links to relevant pages
- Member duration display

✅ **Responsive Layout:**
- Desktop: Horizontal layout with avatar, info, and actions
- Mobile: Stacked vertical layout with centered content
- Tablet: Optimized middle-ground layout

### Recommended Cover Photo Searches:

**For Students:**
- "university students studying together"
- "happy college students campus"
- "students learning library"
- "diverse university students group"

**For Landlords:**
- "professional business person"
- "modern apartment building"
- "real estate professional"
- "property management office"

### Future Enhancements (Planned):

1. **Avatar Upload:**
   - Drag-and-drop file upload
   - Image cropping tool
   - Max 5MB file size limit
   - Auto-resize to 400x400px

2. **Cover Photo Upload:**
   - Similar upload system as avatar
   - Position adjustment slider
   - Max 10MB file size limit
   - Auto-resize to 1600x300px

3. **Image Moderation:**
   - Admin approval for profile pictures
   - Inappropriate content detection
   - Verification badge for verified photos

### CSS Customization:

The profile appearance can be customized in [css/profile.css](css/profile.css?v=1):
- Cover photo height: `.profile-cover` (line 25)
- Avatar size: `.profile-avatar` (line 56-60)
- Stats card colors: `.bg-primary-light`, `.bg-danger-light`, etc. (lines 185-200)

---

## 🔐 Login & Registration Page Backgrounds:

### Current Setup (Using Local Images):
- **Login Page:** `assets/images/auth/login.jpg`
- **Registration Page:** `assets/images/auth/register.jpg`
- Both use professional student images with gradient overlays
- Separate images for each page for better context

### Image Requirements:

**For Login Page (`login.jpg`):**
- Size: 1200x1600px (portrait/tall orientation)
- Theme: Professional, welcoming, trustworthy
- Suggested: Students studying, group learning, campus life
- Compress to under 500KB using tinypng.com

**For Registration Page (`register.jpg`):**
- Size: 1200x1600px (portrait/tall orientation)
- Theme: Exciting, diverse, community-focused
- Suggested: Happy students, celebration, group activities
- Compress to under 500KB using tinypng.com

### How It Works:

The CSS file (`css/login.css`) has separate classes:
```css
/* Login Page */
.bg-gradient-primary.login-page {
    background-image: url('../assets/images/auth/login.jpg');
}

/* Register Page */
.bg-gradient-primary.register-page {
    background-image: url('../assets/images/auth/register.jpg');
}
```

### Recommended Image Searches:
- "african students smiling together"
- "happy university students kenya"
- "diverse student group campus"
- "students celebrating graduation"
3. **Update index.php (around line 1120):**
   ```php
   style="... background-image: url('assets/images/cta-bg.jpg'); ..."
   ```

---

## ⚡ Best Practices:

### Image Optimization:
1. **Compress all images** using tinypng.com before uploading
2. **Use WebP format** for better compression (save 30-50% file size)
3. **Recommended sizes:**
   - Hero background: 1920x1080px (max 500KB)
   - About images: 400x300px (max 200KB each)
   - CTA background: 1600x400px (max 300KB)

### Video Optimization:
1. **Keep video under 10MB** (5MB is ideal)
2. **Duration:** 10-20 seconds (set to loop)
3. **Format:** MP4 (H.264 codec)
4. **Resolution:** 1920x1080px (Full HD)
5. **Use tool:** HandBrake (free) to compress videos

### SEO & Accessibility:
1. **Always add `alt` text** to images (for screen readers)
2. **Use `loading="lazy"`** for images below the fold
3. **Descriptive file names:** `students-studying.jpg` not `IMG_1234.jpg`

---

## 🎯 Recommended Image Searches for CampusDigs:

### For Hero Section:
- "african university students campus"
- "kenyan students studying outdoor"
- "modern student accommodation kenya"
- "university campus aerial view"

### For About Section:
- "african students group smiling"
- "modern apartment interior kenya"
- "students studying together library"
- "university dormitory room"

### For CTA Section:
- "kenyan university campus wide"
- "student accommodation building exterior"
- "university students walking campus"

---

## 🚀 Quick Start (5 Minutes):

1. **Keep current Unsplash images** (they're already professional and working!)
2. **Add hero video:**
   - Download from coverr.co: "students studying" video
   - Save as `assets/images/hero/hero-video.mp4`
   - Uncomment video code in index.php line 581-585
   - Refresh page

3. **Done!** Your site now has professional visuals.

---

## 🆘 Need Help?

- **Images not showing?** Check file paths and make sure files are uploaded
- **Video not playing?** Check file format (must be MP4) and file size (under 10MB)
- **Page loading slow?** Compress images using tinypng.com
- **Want different style?** Contact me for custom CSS adjustments

---

## 📝 Notes:

- Current setup uses **Unsplash CDN** for instant professional look
- Switch to **local images** anytime by following the guide above
- **Both options work great** - CDN is faster but local gives you control
- All images have **lazy loading** for better performance

---

## 🎉 Latest Updates:

### Property Details Page:

#### What Was Added:

✅ **Professional Image Gallery:**
- Main image with 500px height (300px on mobile)
- Thumbnail grid: 4 columns desktop, 2 columns mobile
- Active thumbnail border with green highlight
- Smooth hover effects with lift animation
- Image counter showing "1 / 5" format

✅ **Full-Screen Lightbox:**
- Click any image to view full-screen
- Dark modal background (95% opacity)
- Previous/Next navigation buttons
- Keyboard shortcuts: ← → arrows, ESC to close
- Image counter in lightbox view
- Smooth fade-in animation

✅ **Performance Optimizations:**
- Lazy loading for all thumbnails (first image loads immediately)
- Optimized image loading strategy
- Smooth transitions and animations
- Mobile-responsive design

✅ **User Experience Features:**
- Zoom icon on main image for easy discovery
- Active thumbnail highlighting
- Circular navigation buttons
- Mobile-friendly touch controls
- Print-friendly layout

### Files Modified:
1. **[view/single_property.php](view/single_property.php)** - Enhanced image gallery structure
2. **[css/property-details.css](css/property-details.css?v=1)** - New CSS file for property page styles
3. **[IMAGES_GUIDE.md](IMAGES_GUIDE.md)** - Updated documentation with property image specs

### How to Use:
1. Landlords upload 5-10 images per property (1200x800px, under 300KB each)
2. Images stored in `uploads/properties/` directory
3. First image becomes the main display image
4. All images appear as thumbnails below
5. Students can click any image to view full-screen
6. Navigation with mouse clicks or keyboard arrows

#### Next Steps:
- Add more properties with professional images
- Consider adding image upload guidelines for landlords
- Optional: Add watermark feature for property images
- Optional: Add image moderation/approval system

---

### Student Profile Page (NEW!):

#### What Was Added:

✅ **Professional Profile Header:**
- Cover photo (300px height, 200px on mobile)
- Using Unsplash CDN for campus/student images
- Gradient overlay fallback
- "Change Cover" button placeholder (coming soon)

✅ **Avatar System:**
- Circular avatar (150px desktop, 100px mobile)
- Initials placeholder with gradient background
- White border with shadow for depth
- Camera upload button (coming soon)
- Supports future profile picture uploads

✅ **Enhanced Dashboard Stats:**
- Visual icon-based statistics
- Color-coded backgrounds (blue, red, yellow, teal)
- Smooth hover effects with translation
- Clickable links to relevant pages
- Member duration calculation

✅ **Verification Badges:**
- Email verification status
- Account verification status
- Visual badge display with icons
- Color-coded (green for verified, yellow for pending)

✅ **Responsive Design:**
- Desktop: Horizontal layout with 3-column structure
- Mobile: Vertical stacked layout, centered alignment
- Tablet: Optimized middle-ground design
- Smooth transitions between breakpoints

#### Files Modified:
1. **[view/student_profile.php](view/student_profile.php)** - Added header, cover photo, enhanced stats
2. **[css/profile.css](css/profile.css?v=1)** - New dedicated CSS for profile styling
3. **[IMAGES_GUIDE.md](IMAGES_GUIDE.md)** - Updated with profile image specifications

#### How to Use:
1. Profile page automatically displays for logged-in students
2. Cover photo uses Unsplash CDN (campus theme)
3. Avatar shows user's initials if no photo uploaded
4. Stats display booking/wishlist counts (currently 0, updates dynamically)
5. Edit Profile button links to settings page
6. My Bookings button links to bookings page

#### Future Enhancements:
- Avatar upload with drag-and-drop
- Cover photo upload with position adjustment
- Image cropping tool
- Profile picture moderation system
- Verification badge for verified photos

---

### 🆘 Help & Support Pages (NEW!):

#### What Was Added:

✅ **Hero Section:**
- Professional customer support image
- Welcoming headline and description
- Two CTA buttons: "Contact Support" and "Browse FAQs"
- Light gradient background for clean look
- Desktop: Side-by-side layout, Mobile: Stacked

✅ **Help Topics Grid:**
- 4 color-coded help cards (blue, green, teal, orange)
- Icon-based visual design
- Getting Started, Booking Properties, Payments, Account sections
- Hover effects with lift animation
- Clickable cards linking to detailed guides

✅ **Safety Tips Section:**
- 4 important safety tips with icons
- Red/orange gradient background for emphasis
- Cards: Visit Properties, Secure Payments, Read Agreements, Report Issues
- Icon-based infographic style
- Clear, concise safety messaging

✅ **Contact Methods (Support Page):**
- 4 contact options: Email, Phone, WhatsApp, Social Media
- Circular icon backgrounds with color coding
- Response time information
- Operating hours clearly displayed

✅ **Enhanced FAQ Section:**
- 6 comprehensive FAQ items
- Icon-based question headers
- Collapsible accordion design
- Color-coded icons for visual hierarchy
- Detailed, step-by-step answers
- ARIA labels for accessibility

✅ **Live Chat Placeholder:**
- Coming soon feature card
- Clear messaging about availability
- Professional icon design
- Disabled state with tooltip

✅ **Design Features:**
- Light backgrounds with subtle gradients
- Illustration-based rather than photo-heavy
- Clear visual hierarchy throughout
- Icon-driven design language
- Accessibility-focused (ARIA labels, focus states)
- Responsive on all screen sizes
- High contrast mode support
- Reduced motion support for accessibility

#### Files Modified:
1. **[view/help.php](view/help.php)** - Complete redesign with hero, topics grid, safety section
2. **[view/support.php](view/support.php)** - Enhanced with hero, contact methods, expanded FAQs
3. **[css/help.css](css/help.css?v=1)** - NEW dedicated CSS for help/support pages (500+ lines)
4. **[IMAGES_GUIDE.md](IMAGES_GUIDE.md)** - Updated with help/support specifications

#### Image Requirements:

**Hero Images (Unsplash CDN):**
- Help page: Friendly support team (photo-1553877522-43269d4ea984)
- Support page: Student getting help (photo-1486312338219-ce68d2c6f44d)
- Size: 600x500px optimized
- Both images show welcoming, approachable customer service

**Icon-Based Design:**
- FontAwesome 6.4.0 icons throughout
- No heavy photography needed
- Lightweight, fast-loading
- Professional appearance

#### How to Use:

**Help Center (help.php):**
1. Accessible to all logged-in users
2. 4 main help topics with icons
3. Safety tips section with 4 important guidelines
4. Links to Support page for contact
5. Smooth navigation with anchor links

**Support Center (support.php):**
1. Accessible to all users (logged in or not)
2. 4 contact methods displayed
3. Live chat placeholder for future feature
4. 6 comprehensive FAQ items covering:
   - How to book properties
   - Payment methods
   - Account verification
   - Cancellation policy
   - Reporting problems
   - Leaving reviews
5. Back navigation to Help page and browser history

#### CSS Customization:

The help/support appearance can be customized in [css/help.css](css/help.css?v=1):
- Hero height: `.help-hero` (line 15-20)
- Card colors: `.icon-primary`, `.icon-success`, etc. (lines 55-70)
- Safety section: `.safety-section` (lines 115-140)
- FAQ styling: `.faq-section` (lines 215-260)

#### Recommended Icon Usage:

**Help Topics:**
- 📖 Getting Started: `fa-book`
- 🏠 Bookings: `fa-home`
- 💳 Payments: `fa-credit-card`
- 👤 Account: `fa-user-shield`

**Safety Tips:**
- 👁 Visit Properties: `fa-eye`
- 🔒 Secure Payments: `fa-lock`
- 📄 Read Agreements: `fa-file-contract`
- ⚠️ Report Issues: `fa-exclamation-triangle`

**Contact Methods:**
- 📧 Email: `fa-envelope`
- 📞 Phone: `fa-phone`
- 💬 WhatsApp: `fa-whatsapp`
- 🐦 Social Media: `fa-twitter`

#### Accessibility Features:
- ARIA labels on all interactive elements
- Keyboard navigation support (Tab, Enter, Escape)
- Focus states with visible outlines
- High contrast mode support
- Reduced motion support for animations
- Screen reader friendly structure
- Semantic HTML5 elements

#### Performance:
- Lightweight icon-based design (no heavy images)
- Single CSS file shared between pages
- Minimal external dependencies
- Fast page load times
- Mobile-optimized responsive design

#### Future Enhancements:
- Live chat integration (Intercom, Tawk.to)
- Video tutorials embedded in help topics
- Interactive step-by-step guides
- Search functionality for FAQs
- User feedback/rating system for help articles
- Multi-language support (English/Swahili)
- Email support form with file attachments
