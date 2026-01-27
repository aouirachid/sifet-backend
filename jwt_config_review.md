# JWT Configuration Review & Recommendations

This document provides a review of the JWT (JSON Web Token) configuration for the SIFET backend application and offers recommendations for improving its robustness and security.

The review is based on the contents of `config/jwt.php` and `config/auth.php`.

## Summary of Recommendations

1.  **Upgrade to an Asymmetric Signing Algorithm (RS256):** Enhance security by using a key pair for signing tokens instead of a shared secret.
2.  **Introduce a Leeway for Clock Skew:** Prevent token validation errors in distributed systems by allowing a small time difference.
3.  **Configure a Blacklist Grace Period:** Avoid race conditions and improve user experience during token refreshes in high-concurrency environments.
4.  **Review Multi-Guard JWT Implementation:** Ensure token structures are correctly handled for the different user types (`Admin` and `CompanyUser`).

---

## Detailed Recommendations

### 1. Use a Stronger, Asymmetric Algorithm (e.g., RS256)

**Current Situation:**
The configuration currently uses the `HS256` algorithm by default (`'algo' => env('JWT_ALGO', 'HS256')`). This is a symmetric algorithm, meaning the same secret (`JWT_SECRET`) is used to both sign and verify tokens. If this secret is ever compromised, an attacker could forge tokens.

**Recommendation:**
Switch to an asymmetric algorithm like `RS256`. This uses a private key to sign the token and a public key to verify it. Since the server only needs the public key for verification, the private key can be kept highly secure, reducing the risk of token forgery.

**Steps to Implement:**

1.  **Generate a private and public key pair:**
    ```bash
    openssl genrsa -out private.pem 4096
    openssl rsa -in private.pem -outform PEM -pubout -out public.pem
    ```
    Store these key files in a secure location, like the `storage` directory. Do not commit them to version control.

2.  **Update your `.env` file:**
    Add paths to the keys and change the algorithm.

    ```dotenv
    JWT_ALGO=RS256
    JWT_PRIVATE_KEY="file:///path/to/your/storage/private.pem"
    JWT_PUBLIC_KEY="file:///path/to/your/storage/public.pem"
    JWT_PASSPHRASE=null # Or your key's passphrase if you set one
    ```
    *Ensure the paths are correct for your environment.*

3.  **Update `config/jwt.php` (Optional but Recommended):**
    While the configuration reads from `.env`, you can change the default to `RS256` to make it the standard for all environments.
    ```php
    // in config/jwt.php
    'algo' => env('JWT_ALGO', 'RS256'),
    ```

### 2. Introduce a Leeway for Clock Skew

**Current Situation:**
The `leeway` is set to `0`. This means that token timestamps (`exp`, `nbf`, `iat`) must be perfectly synchronized between the server that issued the token and the server verifying it. In a multi-server or distributed environment, slight differences in system clocks (clock skew) can cause valid tokens to be rejected.

**Recommendation:**
Add a small leeway to account for minor clock discrepancies. A value of a few seconds is usually sufficient.

**Steps to Implement:**

1.  **Update your `.env` file:**
    ```dotenv
    JWT_LEEWAY=5
    ```

2.  **Ensure `config/jwt.php` reads the value:**
    The configuration is already set up correctly to read this value.
    ```php
    // in config/jwt.php
    'leeway' => (int) env('JWT_LEEWAY', 0),
    ```

### 3. Configure a Blacklist Grace Period

**Current Situation:**
The `blacklist_grace_period` is `0`. When a token is refreshed, the old token is immediately blacklisted. If multiple API requests are sent concurrently with the same (soon-to-be-refreshed) token, some of these requests might fail because the token gets blacklisted by the first request before the others are processed.

**Recommendation:**
Set a short grace period (e.g., 10 seconds) to allow in-flight requests to complete successfully even if a token refresh has occurred.

**Steps to Implement:**

1.  **Update your `.env` file:**
    ```dotenv
    JWT_BLACKLIST_GRACE_PERIOD=10
    ```
2.  **Ensure `config/jwt.php` reads the value:**
    The configuration is already set up correctly.
    ```php
    // in config/jwt.php
    'blacklist_grace_period' => (int) env('JWT_BLACKLIST_GRACE_PERIOD', 0),
    ```

### 4. Review Multi-Guard JWT Implementation

**Current Situation:**
The `config/auth.php` file defines three separate authentication guards (`api`, `landlord`, `tenant`) that all use the `jwt` driver. This is a good pattern for multi-tenancy. The `lock_subject` option in `config/jwt.php` is enabled, which is critical in this scenario. It adds a `prv` (provider) claim to the token to prevent a token intended for one user provider (e.g., `admins`) from being used to authenticate against another (e.g., `company_users`).

**Recommendation:**
This setup is generally correct, but it requires careful implementation in the authentication logic.

*   **Token Generation:** When generating a token, ensure you are authenticating against the correct guard. For example:
    ```php
    // For a landlord (Admin)
    $token = auth('landlord')->login($admin);

    // For a tenant (CompanyUser)
    $token = auth('tenant')->login($companyUser);
    ```

*   **Middleware:** Use the correct guard in your route middleware to protect endpoints.
    ```php
    // In your routes/api.php or equivalent

    // Routes for landlords
    Route::middleware('auth:landlord')->group(function () {
        // ...
    });

    // Routes for tenants
    Route::middleware('auth:tenant')->group(function () {
        // ...
    });
    ```

No configuration changes are needed here, but this review serves as a reminder to ensure the implementation correctly distinguishes between the different user types throughout the application.
