# FlatConnect - Direct Flat, PG & Roommate Finder

> **Tagline:** Find a place. Find a roommate. Connect directly.  
> **Project Title:** Direct Flat, PG & Roommate Finder Platform  
> **Target Audience:** Anyone moving to or living in a new city (students, working professionals, families, property owners, and existing tenants)

---

## 1. Project Overview & Problem Solved

When moving to a new city (such as Pune), newcomers face severe difficulties:
- High brokerage fees (often 1 to 2 months of rent).
- Ambiguous rules about whether bachelors or students are allowed.
- Difficulty finding compatible roommates with similar lifestyles.
- Existing tenants struggling to find replacement occupants when leaving early, risking deposit forfeiture.
- Unreliable middlemen and brokers delaying searches.

**FlatConnect** solves these problems by providing a direct web platform that connects:
1. **Property Owners** (who want responsible tenants with zero brokerage),
2. **Existing Tenants** (who need flatmates or replacement tenants to recover their security deposit), and
3. **Accommodation Seekers** (who want verified flats, PGs, rooms, or roommates directly).

---

## 2. Technology Stack

- **Frontend:** HTML5, CSS3, Vanilla JavaScript (DOM manipulation, AJAX fetch, modal dialogs, responsive drawer)
- **Backend:** PHP 8.x (using PDO - PHP Data Objects with prepared statements)
- **Database:** MySQL (`flatconnect_db`)
- **Authentication & Security:** PHP Session Management, `password_hash()` and `password_verify()` (bcrypt), CSRF Token verification, HTML output escaping (`htmlspecialchars`).
- **Environment:** XAMPP (Apache + MySQL / phpMyAdmin).

---

## 3. Directory Structure

```text
FlatConnect-Final/
│
├── index.php                 # First page loaded (Login & Register tabs)
├── choose-role.php           # Role selector page (Owner, Tenant, Seeker)
├── login.php                 # Login redirect handler
├── register.php              # Register redirect handler
├── logout.php                # Session destruction and logout handler
├── forgot-password.php       # Password reset feature
├── home.php                  # Main home page after login (Hero, Categories, Vacancies)
├── properties.php            # Find Properties with interactive filters
├── property-details.php      # Full property view, owner contact & direct enquiry modal
├── post-property.php         # Form for Property Owners to list flats/PGs
├── edit-property.php         # Owner edit listing form
├── delete-property.php       # Secure owner property deletion
├── roommates.php             # Roommate Finder with Compatibility Scores
├── post-roommate.php         # Post roommate requirement
├── replacement.php           # Tenant Replacement page for lease takeovers
├── post-replacement.php      # Post tenant replacement vacancy
├── delete-post.php           # Secure post deletion for tenants
├── owner-dashboard.php       # Role 1: Property Owner Dashboard
├── tenant-dashboard.php      # Role 2: Existing Tenant Dashboard
├── seeker-dashboard.php      # Role 3: Accommodation Seeker Dashboard
├── profile.php               # Profile management & Smart Matching preferences
├── enquiry.php               # Direct enquiry submission and owner response controller
├── save-property.php         # AJAX endpoint for saving/favoriting properties
│
├── config/
│   └── db.php                # PDO database connection
│
├── includes/
│   ├── auth.php              # Auth checks, CSRF, flash notifications, roles
│   ├── header.php            # HTML <head>, Google Fonts & stylesheets
│   ├── navbar.php            # Responsive navbar with role badge & navigation
│   └── footer.php            # Platform footer with Pune localities & quick links
│
├── assets/
│   ├── css/
│   │   └── style.css         # Complete custom styling (Navy Blue & Teal theme)
│   ├── js/
│   │   └── main.js           # Navigation, modals, AJAX favorites, image preview
│   └── images/
│       ├── logo.svg          # FlatConnect house logo
│       └── properties/       # Property illustrations & photos (prop1.svg to prop12.svg)
│
├── uploads/
│   └── properties/           # Directory for user-uploaded property pictures
│
├── database.sql              # Database schema creation script
├── seed.sql                  # Sample Pune listings, roommates, replacements & accounts
└── README.md                 # Project documentation and guide
```

---

## 4. Setup and Installation (XAMPP)

### Step 1: Start XAMPP Services
1. Open the **XAMPP Control Panel**.
2. Click **Start** for **Apache**.
3. Click **Start** for **MySQL**.
*(Both module indicators should turn green).*

### Step 2: Workspace Location & URL Access
The project is configured so it can be opened using:
```
http://localhost/FlatConnect-Final/
```

- If you cloned the repository or extracted it, ensure the folder `FlatConnect-Final` is located in your XAMPP web root:
  `C:\xampp\htdocs\FlatConnect-Final`
  *(Note: A Windows Directory Junction has already been established between your workspace and `C:\xampp\htdocs\FlatConnect-Final`).*

### Step 3: Import the Database via phpMyAdmin
1. Open your browser and visit:
   ```
   http://localhost/phpmyadmin/
   ```
2. Click on the **Import** tab at the top.
3. Click **Choose File** and select `database.sql` from the project folder. Click **Import** (or **Go**) at the bottom.
4. Next, click **Choose File** and select `seed.sql` from the project folder. Click **Import** (or **Go**).
5. The database `flatconnect_db` is now created and pre-loaded with:
   - 3 Test accounts (Owner, Tenant, Seeker)
   - 12 Fictional Pune rental properties (Hinjewadi, Wakad, Tathawade, Baner, Punawale, Pimpri)
   - 6 Roommate requirement profiles
   - 4 Tenant replacement listings
   - Pre-loaded sample enquiries and saved properties

*(Alternatively, you can import using the MySQL CLI:)*
```bash
C:\xampp\mysql\bin\mysql.exe -u root < database.sql
C:\xampp\mysql\bin\mysql.exe -u root < seed.sql
```

---

## 5. Single Unified Account & Dynamic Role Switching

FlatConnect uses a **single account system**:
- A single user account can act as a **Property Owner**, **Existing Tenant**, or **Accommodation Seeker**.
- Users do NOT need separate email addresses for different roles.
- No role options are displayed on the public login or registration page.

### The Unified Login Flow:
1. Open `http://localhost/FlatConnect-Final/`.
2. Enter your registered email and password.
3. Upon credential verification, you are redirected to the **Choose Your Role** page (`choose-role.php`).
4. Select one of the three interactive cards:
   - **Continue as Property Owner** &rarr; opens `owner-dashboard.php`
   - **Continue as Existing Tenant** &rarr; opens `tenant-dashboard.php`
   - **Continue as Accommodation Seeker** &rarr; opens `seeker-dashboard.php`
5. **Switch Role Anytime:** Click **Switch Role** in the top navigation bar at any point to transition between roles without logging out. All property listings, enquiries, and roommate posts remain securely tied to your primary user ID.

### Test Accounts for Demonstration:

| Account Name | Email Address | Password | Can Switch Between All Roles? |
| :--- | :--- | :--- | :--- |
| **Rajesh Sharma** | `owner@flatconnect.com` | `Password@123` | **Yes (Owner / Tenant / Seeker)** |
| **Amit Deshmukh** | `tenant@flatconnect.com` | `Password@123` | **Yes (Owner / Tenant / Seeker)** |
| **Pooja Kulkarni** | `seeker@flatconnect.com` | `Password@123` | **Yes (Owner / Tenant / Seeker)** |

---

## 6. Three User Roles & Functionality

### Role 1: Property Owner
- **Dashboard:** Overview of total properties, active listings, urgent vacancies, and enquiries received.
- **Post Property:** Add flats, PGs, or rooms with title, rent, deposit, locality, amenities, bachelor policy, available date, and photo uploads.
- **Manage Listings:** Edit details or price; mark property as Rented or Available; delete listings.
- **Enquiry Inbox:** View interested seeker messages with contact phone and email, and send direct replies.

### Role 2: Existing Tenant
- **Tenant Replacement:** Post an active tenancy vacancy when relocating early to transfer the lease to an incoming seeker without losing security deposit.
- **Roommate Requirement:** Post flatmate requirements specifying budget, sharing preference, and lifestyle habits.
- **Manage Posts:** View, edit, or delete personal replacement and roommate posts.
- **Enquiries:** Review queries from seekers wanting to replace or share the flat.

### Role 3: Accommodation Seeker
- **Smart Matching Engine:** Automatically recommends suitable properties based on preferred Pune locality, budget limit, and property type.
- **Property Search & Filters:** Filter listings by locality (Hinjewadi, Wakad, Tathawade, etc.), budget range, property type (Flat, PG, Room), furnishing, bachelor permission, and urgent vacancies.
- **Saved Favorites:** Save/bookmark favorite properties with one-click AJAX toggling.
- **Send Enquiries:** Send direct messages to property owners or tenants without any broker intermediary.
- **Roommate Finder:** Browse roommate profiles with rule-based compatibility scores.

---

## 7. Innovative Features Implemented

### A. Smart Matching Recommendation Engine
- **How it works:** Uses a transparent, rule-based matching formula in SQL.
- When an accommodation seeker specifies their preferred locality, maximum budget, property type, and furnishing preference in `profile.php`, the system computes a match score:
  - Location alignment: **+40 points**
  - Within budget constraint: **+30 points**
  - Property type match: **+20 points**
  - Furnishing status match: **+10 points**
- Properties are ranked and displayed in real time on the Seeker Dashboard.

### B. Roommate Compatibility Scoring
- Evaluates mutual compatibility between a seeker and prospective flatmates based on:
  - Preferred locality overlap (**+25%**)
  - Budget difference within ₹1,500 – ₹3,000 (**+15%**)
  - Sharing preference compatibility (**+10%**)
- Displays an intuitive compatibility badge (e.g., `95% Match`) on every roommate card.

### C. Urgent Vacancy Highlighting
- Flags properties available for immediate move-in with an **Urgent Vacancy** badge.
- Seekers can filter specifically for urgent vacancies on the property search page.

---

## 8. Step-by-Step Project Demonstration Flow

When demonstrating FlatConnect to an examiner, professor, or user:

1. **First Page Check (`index.php`)**:
   - Open `http://localhost/FlatConnect-Final/`.
   - Point out that the site opens directly to the attractive Login / Register interface (no unauthenticated homepage access).
   - Click **Create New Account** to show the 3-role selector (Property Owner, Existing Tenant, Accommodation Seeker).
2. **Demonstrate Property Owner Role**:
   - Log in using `owner@flatconnect.com` / `Password@123`.
   - You are taken to `owner-dashboard.php`.
   - Show metrics, listed properties, and received enquiries.
   - Click **Post New Property** to show the form, facilities checklist, and photo upload.
   - Open an enquiry and submit a reply.
   - Click **Logout**.
3. **Demonstrate Accommodation Seeker Role**:
   - Log in using `seeker@flatconnect.com` / `Password@123`.
   - You are redirected to `seeker-dashboard.php`.
   - Show the **Smart Matching Recommendations** section tailored to Hinjewadi / ₹18,000 budget.
   - Navigate to **Find Properties** (`properties.php`) and demonstrate filters (e.g., filter by location "Tathawade", or type "PG").
   - Click **View Details** on any property (`property-details.php`). Show property photos, owner direct phone number, and click **Send Direct Enquiry**.
   - Click the heart icon to save the property and verify it updates in the **Saved Properties** section.
4. **Demonstrate Roommate Finder & Compatibility**:
   - Click **Find Roommates** (`roommates.php`).
   - Highlight the **Roommate Compatibility Score** (e.g., `95% Match`).
   - Click **Post Roommate Requirement** modal to demonstrate adding a new flatmate search.
5. **Demonstrate Tenant Replacement**:
   - Click **Tenant Replacement** (`replacement.php`).
   - Explain the concept: departing tenants post their active lease so a newcomer can take over without brokers or losing deposits.
   - Log out, then log in as `tenant@flatconnect.com` to show the **Tenant Dashboard** (`tenant-dashboard.php`) managing replacement vacancies.

---

## 9. Security Implementation Highlights

- **Prepared Statements (PDO):** Every SQL query with user input uses parameterized statements, preventing SQL Injection vulnerabilities.
- **Password Hashing:** Passwords are never stored in plain text; they are encrypted using PHP's native `password_hash($pass, PASSWORD_DEFAULT)` (bcrypt) and checked with `password_verify()`.
- **CSRF Protection:** Critical forms (login, registration, posting, enquiries, profile updates) validate cryptographic session tokens.
- **XSS Prevention:** Output rendering uses a secure escaping helper `e()` (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`).
- **Access Control & Ownership Verification:** Protected pages verify user session roles, and owners can only edit/delete their own listings.

---

## 10. Future Development Roadmap

Features planned for upcoming versions:
1. **Interactive Map Integration:** Leaflet/OpenStreetMap integration to visualize flats relative to colleges and tech parks in Pune.
2. **Digital Rental Agreement Generator:** Automated lease agreement PDF drafting between owners and tenants.
3. **Real-time In-App Messaging:** WebSocket-based instant chat with read receipts.
4. **Virtual 360-Degree Property Tours:** Support for panoramic room photo viewing.

---

*FlatConnect - Direct Flat, PG & Roommate Finder. Created with clean PHP, MySQL, HTML5, CSS3, and JavaScript.*
