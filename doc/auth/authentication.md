# Authentication Component Documentation

## Overview

The authentication system provides comprehensive user management capabilities including traditional email/password authentication and OAuth-based social logins. The system is built using Symfony Security Bundle and implements industry-standard security practices.

## Features

The authentication system provides:

- Traditional email/password authentication
- Social login via OAuth (Google, GitHub)
- Email verification for new accounts
- Password reset functionality
- Remember me functionality
- Role-based access control

## Authentication Methods

### Email/Password Authentication

Users can register with an email address and password. The password is securely hashed using Symfony's password hasher before storage.

### OAuth Authentication

Users can authenticate using:
- Google account
- GitHub account

OAuth connections are stored separately from user accounts, allowing a single user to have multiple authentication methods.

## User Entity Structure

The authentication system uses two main entities:

### User Entity (`User.php`)

```php
// Key fields:
private ?string $email;
private ?string $firstName;
private ?string $lastName;
private array $roles = [];
private ?string $password;
private bool $isVerified = false;
private ?\DateTimeInterface $lastLoginAt;
private string $subscriptionStatus = 'free';
private Collection $oauthConnections;
```

The User entity implements `UserInterface` and `PasswordAuthenticatedUserInterface` for Symfony Security integration.

### OAuthConnection Entity (`OAuthConnection.php`)

```php
// Key fields:
private ?User $user;
private ?string $provider;
private ?string $providerUserId;
private ?string $accessToken;
private ?string $refreshToken;
private ?\DateTimeInterface $expiresAt;
```

This entity stores OAuth-specific data and maintains a many-to-one relationship with the User entity.

## Authentication Flow

### Registration Process

1. User visits the registration page
2. User submits registration form with email, password, and optional personal info
3. System creates a new User entity with hashed password
4. System sends a verification email to the user's email address
5. User verifies email by clicking the link in the verification email
6. User can now log in to the system

### Login Process

1. User visits the login page
2. User provides email and password or selects social login option
3. System authenticates the user
4. On successful authentication, the user is redirected to the dashboard
5. The system records the login timestamp

## OAuth Integration

OAuth authentication is handled by the KnpUOAuth2ClientBundle, which provides:

1. OAuth client implementations for various providers
2. Integration with Symfony Security

### OAuth Authentication Flow

1. User clicks on a social login button (Google/GitHub)
2. User is redirected to the provider's authentication page
3. User grants permissions to the application
4. Provider redirects back to our application with an authorization code
5. Application exchanges the code for an access token
6. Application retrieves user info from the provider's API
7. System either:
   - Creates a new user account if the email is not found
   - Links the OAuth connection to an existing account
   - Uses an existing OAuth connection to authenticate

## Email Verification Process

New users must verify their email address before they can log in. The email verification is handled by the SymfonyCasts VerifyEmailBundle.

### Verification Flow

1. User registers with an email address
2. System generates a signed verification URL
3. System sends an email with the verification link
4. User clicks the link in the email
5. System verifies the signature and marks the user's email as verified
6. User is redirected to the login page with a success message

## Password Reset Flow

Users can reset their password if they forget it. This is handled by the SymfonyCasts ResetPasswordBundle.

### Reset Flow

1. User clicks "Forgot your password?" link on the login page
2. User submits their email address in the reset request form
3. System generates a password reset token and stores it
4. System sends an email with a reset link containing the token
5. User clicks the link and is redirected to a page to set a new password
6. User submits a new password that must meet the following requirements:
   - At least 12 characters long
   - Contains at least one uppercase letter
   - Contains at least one lowercase letter
   - Contains at least one number
   - Contains at least one special character
   - Has not been exposed in known data breaches
7. System validates the token, updates the password, and invalidates the token
8. User is redirected to the login page with a success message confirming the password has been reset

### Implementation Details

The password reset functionality is implemented using:

- `ResetPasswordController` - Handles the reset password workflow
- `ChangePasswordFormType` - Form type with password validation constraints
- Twig templates:
  - `reset_password/request.html.twig` - Initial reset request form
  - `reset_password/check_email.html.twig` - Confirmation page after reset request
  - `reset_password/reset.html.twig` - New password form
  - `reset_password/email.html.twig` - Email template with reset link

The system uses Symfony's password validation to ensure strong passwords:
```php
// Password validation constraints
new Length(['min' => 12])
new Regex(['pattern' => '/[A-Z]/']) // uppercase
new Regex(['pattern' => '/[a-z]/']) // lowercase
new Regex(['pattern' => '/[0-9]/']) // number
new Regex(['pattern' => '/[!@#$%^&*(),.?":{}|<>[\]\\-_=+]/']) // special char
new NotCompromisedPassword() // checks against known breaches
```

### Security Considerations

- Reset tokens expire after a configured time period
- Tokens are single-use and invalidated after password reset
- The system does not reveal whether an email exists in the database
- Passwords are validated for strength and checked against known breaches
- All reset-related URLs are signed to prevent tampering
- Reset tokens are stored securely in the database

## Security Configuration

The security configuration is defined in `config/packages/security.yaml` and includes:

- Password encoding settings
- User provider configuration
- Firewall configuration for public/protected areas
- Login/logout settings
- Remember me functionality
- Role hierarchy definition
- Access control rules

## Development Notes

### Testing Emails

During development, email sending can be tested using:

1. **Mailhog** - A development mail server that captures outgoing emails
   - Available at `http://localhost:8025` when running the Docker setup
   - Configure with `MAILER_DSN=smtp://mailhog:1025` in .env.local

2. **Development Auto-Verification** 
   - For development convenience, users can be auto-verified by setting `$user->setIsVerified(true)` in the registration controller

### OAuth Provider Setup

To use OAuth authentication, you need to:

1. Create OAuth applications with the providers:
   - Google: [Google Cloud Console](https://console.cloud.google.com/)
   - GitHub: [GitHub Developer Settings](https://github.com/settings/developers)

2. Configure redirect URIs:
   - Google: `http://localhost:8080/connect/google/check`
   - GitHub: `http://localhost:8080/connect/github/check`

3. Set credentials in .env.local:
   ```
   OAUTH_GOOGLE_CLIENT_ID=your_client_id
   OAUTH_GOOGLE_CLIENT_SECRET=your_client_secret
   OAUTH_GITHUB_CLIENT_ID=your_client_id
   OAUTH_GITHUB_CLIENT_SECRET=your_client_secret
   ```

### Security Considerations

For production deployment:

1. Always use HTTPS for all authentication-related routes
2. Set appropriate cookie security settings
3. Keep OAuth client secrets secure
4. Use strong password requirements
5. Protect against CSRF attacks (Symfony forms handle this automatically)
6. Implement rate limiting for login/registration attempts
