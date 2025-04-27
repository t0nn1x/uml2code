# OAuth Provider Setup Guide

This guide provides step-by-step instructions for setting up OAuth providers for the UML2Code application.

## Prerequisites

Before setting up OAuth providers, ensure that:

1. The base application is up and running
2. The authentication system is properly installed
3. You have access to create applications in Google Cloud Console and GitHub Developer Settings

## Google OAuth Setup

### 1. Create a Project in Google Cloud Console

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Navigate to "APIs & Services" > "Credentials"

### 2. Configure OAuth Consent Screen

1. Click on "OAuth consent screen" in the left menu
2. Select the appropriate user type (External or Internal)
3. Fill in the required information:
   - App name: "UML2Code"
   - User support email
   - Developer contact information
4. Add the required scopes:
   - `email`
   - `profile`
5. Save and continue

### 3. Create OAuth 2.0 Client ID

1. Navigate to "Credentials" in the left menu
2. Click "Create Credentials" > "OAuth client ID"
3. Select "Web application" as the application type
4. Name: "UML2Code Web Client"
5. Add authorized JavaScript origins:
   - `http://localhost:8080` (for development)
   - Your production domain (for production)
6. Add authorized redirect URIs:
   - `http://localhost:8080/connect/google/check` (for development)
   - `https://your-production-domain.com/connect/google/check` (for production)
7. Click "Create"
8. Note the Client ID and Client Secret

### 4. Configure UML2Code Application

1. Add the credentials to your `.env.local` file:
   ```
   OAUTH_GOOGLE_CLIENT_ID=your_client_id
   OAUTH_GOOGLE_CLIENT_SECRET=your_client_secret
   ```

## GitHub OAuth Setup

### 1. Create a New OAuth App

1. Go to [GitHub Developer Settings](https://github.com/settings/developers)
2. Click on "OAuth Apps" in the left menu
3. Click "New OAuth App"

### 2. Register the Application

1. Fill in the application details:
   - Application name: "UML2Code"
   - Homepage URL: `http://localhost:8080` (or your production URL)
   - Application description: "UML to Code conversion tool"
   - Authorization callback URL: `http://localhost:8080/connect/github/check` (or your production callback URL)
2. Click "Register application"
3. On the next screen, click "Generate a new client secret"
4. Note the Client ID and Client Secret

### 3. Configure UML2Code Application

1. Add the credentials to your `.env.local` file:
   ```
   OAUTH_GITHUB_CLIENT_ID=your_client_id
   OAUTH_GITHUB_CLIENT_SECRET=your_client_secret
   ```

## Testing the OAuth Integration

### Test Google Login

1. Start your application
2. Navigate to the login page
3. Click the "Sign in with Google" button
4. You should be redirected to Google's authentication page
5. After authentication, you should be redirected back to your application and logged in

### Test GitHub Login

1. Navigate to the login page
2. Click the "Sign in with GitHub" button
3. You should be redirected to GitHub's authentication page
4. After authentication, you should be redirected back to your application and logged in

## Troubleshooting

### Common Issues

1. **Redirect URI Mismatch**
   - Error: "The redirect URI in the request did not match a registered redirect URI"
   - Solution: Ensure the redirect URI in your OAuth provider settings exactly matches your application's callback URL

2. **Invalid Client ID or Secret**
   - Error: "Invalid client ID" or "Invalid client secret"
   - Solution: Verify that you're using the correct client ID and secret in your .env.local file

3. **CORS Issues**
   - Error: Cross-Origin Request Blocked
   - Solution: Ensure your domain is properly configured in the OAuth provider's allowed origins

4. **Missing Scopes**
   - Error: "Access denied" or "Insufficient permission"
   - Solution: Ensure you've configured the proper scopes in your OAuth provider settings

### Debug Logging

To enable debug logging for OAuth-related issues:

1. Set the appropriate environment variables:
   ```
   # In .env.local or .env.dev
   OAUTH_DEBUG=true
   APP_ENV=dev
   ```

2. Check the application logs in var/log/dev.log for detailed OAuth-related error messages.

## Security Considerations

1. **Never commit OAuth credentials to version control**
   - Always use environment variables or a secrets management system

2. **Restrict redirect URIs**
   - Only allow redirect URIs that belong to your application

3. **Limit OAuth scopes**
   - Only request the minimum scopes necessary for your application

4. **Implement CSRF protection**
   - The KnpUOAuth2ClientBundle handles this automatically

5. **Monitor OAuth usage**
   - Regularly review authentication logs for suspicious activity
