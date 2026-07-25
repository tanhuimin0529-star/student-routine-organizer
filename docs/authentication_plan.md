# Section 5 — Authentication Planning

---

## Overview

Authentication is the security backbone of the system. All four modules and the admin dashboard are protected and accessible only to authenticated users with the correct role. The authentication system will be implemented in the `authentication/` folder and supported by shared helpers in `includes/`.

---

## 1. User Registration

### Flow Description
1. Student navigates to the Registration page (`register.php`).
2. Student fills in the registration form.
3. Client-side JavaScript validates the form before submission.
4. Form is submitted via POST to `register_handler.php`.
5. Server-side PHP validates all input fields.
6. PHP checks that the email address is not already registered in the database.
7. PHP hashes the password using a secure algorithm.
8. A new user record is inserted into the `users` table.
9. On success: the user is redirected to the Login page with a success message.
10. On failure: the user is returned to the Registration page with an appropriate error message.

### Registration Form Fields

| Field | Validation |
|-------|-----------|
| Full Name | Required, alphabetic characters and spaces, max 100 chars |
| Email Address | Required, valid email format, unique in database |
| Password | Required, min 8 characters, must contain letter and number |
| Confirm Password | Required, must match Password field |
| Role | Hidden field, defaults to "student" for self-registration |

---

## 2. User Login

### Flow Description
1. Student navigates to the Login page (`login.php`).
2. Student enters email and password.
3. Client-side JavaScript performs basic validation.
4. Form is submitted via POST to `login_handler.php`.
5. PHP queries the `users` table for a record matching the email.
6. PHP uses the password verification function to compare the input with the stored hash.
7. On credential mismatch: user is returned to login with a generic error message (do not specify whether email or password is wrong).
8. On success: PHP starts or resumes a session, stores user ID, name, email, and role in session variables.
9. PHP checks the user's role:
   - Role = "student" → redirect to `dashboard/index.php`.
   - Role = "admin" → redirect to `admin/index.php`.
10. Failed login attempts can be tracked for basic rate limiting (future enhancement).

### Login Form Fields

| Field | Validation |
|-------|-----------|
| Email Address | Required, valid email format |
| Password | Required |
| Remember Me | Checkbox (optional, triggers cookie) |

---

## 3. Logout

### Flow Description
1. User clicks the Logout link from any page.
2. Request goes to `logout.php`.
3. PHP destroys the current session (`session_destroy()`).
4. Any "Remember Me" cookie is cleared.
5. User is redirected to the Login page with a logged-out confirmation message.

---

## 4. Session Management

### Strategy
- Sessions are started at the top of every protected page via `includes/session_start.php`.
- Session variables stored on successful login:

| Session Variable | Value |
|-----------------|-------|
| `$_SESSION['user_id']` | Integer — Primary key of the user |
| `$_SESSION['user_name']` | String — User's full name |
| `$_SESSION['user_email']` | String — User's email address |
| `$_SESSION['user_role']` | String — "student" or "admin" |
| `$_SESSION['last_activity']` | Timestamp — For timeout detection |

- Session timeout: Sessions expire after 30 minutes of inactivity.
- On every protected page load, `last_activity` is checked. If expired, the session is destroyed and the user is redirected to login.
- Session fixation prevention: The session ID is regenerated after successful login (`session_regenerate_id(true)`).

---

## 5. Cookie Strategy

### Remember Me Feature
- When the user checks "Remember Me" on the login form, a secure cookie is set.
- The cookie stores an encrypted token, NOT the password or plain user ID.
- A corresponding token record is stored in the `remember_tokens` table (or `users` table extension column).
- Cookie expiry is set to 30 days.
- On returning visit, PHP reads the cookie, validates the token against the database, and auto-logs the user in.
- When the user logs out, the cookie is cleared and the token is deleted from the database.
- Cookies must use `HttpOnly` and `Secure` flags (Secure applies when HTTPS is in use).

---

## 6. Role-Based Access Control (RBAC)

### Roles Defined

| Role | Code Value | Description |
|------|-----------|-------------|
| Student | `student` | Standard self-registered user |
| Admin | `admin` | Manually assigned by developer/administrator |

### Access Control Guards

**`includes/auth_guard.php`** — Protects all student pages:
- Checks if a valid session exists.
- If no session: redirects to `authentication/login.php`.
- If session exists but role is not recognized: destroys session and redirects.

**`includes/admin_guard.php`** — Protects all admin pages:
- Checks if a valid session exists.
- Checks if `$_SESSION['user_role']` equals "admin".
- If not: redirects to `dashboard/index.php` with an access-denied message.

### Student Permissions

| Action | Allowed |
|--------|---------|
| Register / Login / Logout | ✅ |
| View own dashboard | ✅ |
| CRUD own exercise records | ✅ |
| CRUD own diary records | ✅ |
| CRUD own money records | ✅ |
| CRUD own habit records | ✅ |
| View other students' records | ❌ |
| Access admin pages | ❌ |

### Admin Permissions

| Action | Allowed |
|--------|---------|
| Login / Logout | ✅ |
| View admin dashboard | ✅ |
| View all registered users | ✅ |
| Deactivate user accounts | ✅ |
| Delete user accounts | ✅ |
| CRUD all student records | ❌ (Admin does not modify individual data) |
| Self-register via public form | ❌ (Admin accounts are created manually) |

---

## 7. Password Security Strategy

| Strategy | Description |
|----------|-------------|
| **Password Hashing** | PHP's `password_hash()` function with `PASSWORD_BCRYPT` algorithm |
| **Password Verification** | PHP's `password_verify()` function (constant-time comparison) |
| **Minimum Length** | 8 characters enforced at both client and server |
| **No Plain Text Storage** | Passwords are never stored or logged in plain text |
| **No Password in URL** | All auth forms use POST method only |
| **Generic Error Messages** | Login errors do not reveal whether email exists or not |
| **Future Enhancement** | CSRF tokens on all forms to prevent cross-site request forgery |

---

*Document prepared for: Student Routine Organizer — University Group Assignment*
