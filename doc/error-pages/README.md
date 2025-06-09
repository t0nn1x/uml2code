# Custom Error Pages for UML2Code

This project now includes beautiful, responsive custom error pages that match the application's design and provide a great user experience even when things go wrong.

## 🎨 Features

- **Beautiful Design**: Error pages match application's Tailwind CSS design
- **Responsive**: Works perfectly on all devices
- **Multilingual**: Supports English and Ukrainian translations
- **Smart Routing**: Different behaviors for admin areas, API requests, and regular pages
- **Comprehensive Coverage**: Handles 404, 403, 401, 500, and generic errors
- **Maintenance Mode**: Includes a maintenance page for scheduled downtime
- **Logging**: Automatic error logging for debugging and monitoring
- **User-Friendly**: Helpful actions and information for users

## 📄 Error Pages Included

### 1. 404 - Page Not Found (`error404.html.twig`)
- **Color Scheme**: Indigo gradient
- **Features**: 
  - Helpful links to main application features (for logged-in users)
  - Go home and go back buttons
  - Beautiful visual elements

### 2. 403 - Access Denied (`error403.html.twig`)
- **Color Scheme**: Red gradient
- **Features**:
  - Different behavior for logged-in vs anonymous users
  - Login prompt for anonymous users
  - Contact information for access requests
  - Current user and timestamp information

### 3. 401 - Authentication Required (`error401.html.twig`)
- **Color Scheme**: Blue gradient
- **Features**:
  - Login and register buttons
  - Explanation of why authentication is needed
  - Information about application benefits

### 4. 500 - Internal Server Error (`error500.html.twig`)
- **Color Scheme**: Yellow/Orange gradient
- **Features**:
  - Auto-refresh functionality
  - Detailed explanation of what happened
  - Error tracking with reference IDs
  - Automatic refresh suggestion after 30 seconds

### 5. Generic Error (`error.html.twig`)
- **Color Scheme**: Gray gradient
- **Features**:
  - Handles any other HTTP errors
  - Dynamic error code and status display
  - Reference ID for support

### 6. Maintenance Mode (`maintenance.html.twig`)
- **Color Scheme**: Purple gradient
- **Features**:
  - Estimated completion time
  - What's being updated
  - Auto-refresh every 30 seconds
  - Contact information

## 🔧 Configuration

The error pages are automatically configured through:

1. **Framework Configuration** (`config/packages/framework.yaml`):
   ```yaml
   framework:
       error_controller: App\Controller\ErrorController::show
   ```

2. **Twig Configuration** (`config/packages/twig.yaml`):
   ```yaml
   twig:
       exception_controller: App\Controller\ErrorController::show
   ```

## 🎯 Smart Error Handling

### Access Denied Listener
The `AccessDeniedListener` provides intelligent handling:

- **Admin Areas**: Special 403 pages with admin-specific messaging
- **API Requests**: JSON error responses for AJAX/API calls
- **Anonymous Users**: Automatic redirect to login with return URL
- **Logged-in Users**: Appropriate access denied pages

### Error Controller
The `ErrorController` provides:

- **Automatic Logging**: Errors are logged with context information
- **Template Selection**: Chooses the right template based on error code
- **Error IDs**: Generates unique reference IDs for support
- **Context Enrichment**: Adds useful variables to templates

## 🌍 Translations

Error messages are fully translatable and included in:
- `translations/messages.en.yaml` - English translations
- `translations/messages.uk.yaml` - Ukrainian translations

### Adding New Languages
To add support for a new language:

1. Create `translations/messages.{locale}.yaml`
2. Copy the `error:` and `maintenance:` sections from existing files
3. Translate all the messages
4. Add the locale to `config/packages/framework.yaml` in `enabled_locales`

## 🧪 Testing Error Pages

Test routes are available (remove in production):

- `/test/error/404` - Test 404 page
- `/test/error/403` - Test 403 page  
- `/test/error/500` - Test 500 page
- `/test/error/maintenance` - Test maintenance page

## 🚀 Usage Examples

### Throwing Custom Errors in Controllers

```php
// Throw a 404 error
throw new NotFoundHttpException('Resource not found');

// Throw a 403 error
throw new AccessDeniedHttpException('Access denied');

// Throw a 500 error
throw new \Exception('Something went wrong');
```

### Maintenance Mode
To enable maintenance mode, you can:

1. **Route-based**: Redirect all routes to the maintenance controller
2. **Middleware-based**: Create middleware to check maintenance status
3. **Manual**: Return the maintenance template from any controller

Example maintenance controller method:
```php
public function maintenance(): Response
{
    return $this->render('bundles/TwigBundle/Exception/maintenance.html.twig', [
        'estimated_duration' => '30 minutes',
    ], new Response('', 503));
}
```

## 🔒 Security Considerations

- **Error Information**: Production error pages don't expose sensitive information
- **Logging**: Errors are logged with appropriate detail levels
- **Admin Protection**: Special handling for admin area access attempts
- **Rate Limiting**: Consider adding rate limiting for error pages to prevent abuse

## 🎨 Customization

### Styling
Error pages use existing Tailwind CSS classes and can be customized by:

1. **Colors**: Change the gradient classes (`from-indigo-50`, `to-blue-100`, etc.)
2. **Layout**: Modify the HTML structure in the templates
3. **Icons**: Replace SVG icons with preferred icon set
4. **Animations**: Add or modify CSS animations

### Content
- **Messages**: Update translations in the YAML files
- **Links**: Modify helpful links in the templates
- **Contact Info**: Update support email addresses
- **Branding**: Add your logo or brand elements

## 📊 Monitoring

The error pages automatically log errors with context:

```php
$this->logger->error('Error page displayed', [
    'status_code' => $statusCode,
    'url' => $_SERVER['REQUEST_URI'],
    'user' => $this->getUser()?->getUserIdentifier(),
    'ip' => $_SERVER['REMOTE_ADDR'],
    // ... more context
]);
```

Monitor these logs to:
- Track error frequency
- Identify problematic areas
- Monitor security attempts
- Improve user experience

