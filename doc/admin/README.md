# UML2Code Admin Panel

## Overview

The admin panel has been successfully implemented using EasyAdmin bundle with comprehensive user management and logging capabilities.

## Features

### 🔐 Admin Panel Access
- **URL**: `/admin`
- **Authentication**: Requires `ROLE_ADMIN` or `ROLE_SUPER_ADMIN`
- **Dark Theme**: Modern dark theme with responsive design

### 👥 User Management
- **View Users**: Complete user listing with search and filters
- **Edit Users**: Modify user details, roles, and subscription status
- **User Roles**: Support for `ROLE_USER`, `ROLE_ADMIN`, `ROLE_PREMIUM`, `ROLE_SUPER_ADMIN`
- **Subscription Management**: Free, Premium, Enterprise tiers
- **User Verification**: Track email verification status
- **Activity Tracking**: Last login timestamps

### 📊 Dashboard Analytics
- **User Statistics**: Total users, active users (24h)
- **Action Metrics**: Total actions performed
- **Error Monitoring**: Error log counts and recent errors
- **Recent Activity**: Latest user actions with details
- **Quick Actions**: Direct links to management sections

### 📝 Comprehensive Logging System

#### Action History Logs
- **User Actions**: Convert, Parse, Generate operations
- **File Tracking**: Complete file content and metadata
- **Programming Languages**: Track target languages
- **Performance Metrics**: Lines of code, file sizes
- **Searchable**: Filter by user, action type, language, date

#### System Logs
- **Database Storage**: All application logs stored in database
- **Log Levels**: Debug, Info, Notice, Warning, Error, Critical, Alert, Emergency
- **Context Data**: Request details, user information, IP addresses
- **Admin Actions**: Automatic logging of all admin panel activities
- **Error Tracking**: Detailed error logs with stack traces

### 🛠 Management Tools

#### Console Commands
```bash
# Create admin user
php bin/console app:create-admin email@domain.com password123 --first-name="John" --last-name="Doe" --super-admin

# Clean old logs (default: 30 days)
php bin/console app:clean-logs 30
```

#### Automated Features
- **Admin Action Logging**: All admin panel actions automatically logged
- **Security Logging**: Login attempts, access violations
- **Performance Monitoring**: Database queries, response times
- **Error Alerting**: Critical errors highlighted in dashboard

## Security Features

### Role-Based Access Control
- **ROLE_ADMIN**: Full admin panel access
- **ROLE_SUPER_ADMIN**: Additional delete permissions
- **Permission Levels**: Granular permissions per action

### Data Protection
- **Sensitive Data Redaction**: Passwords and tokens automatically redacted from logs
- **IP Tracking**: All admin actions tracked with IP addresses
- **Session Monitoring**: User session tracking and management

### Audit Trail
- **Complete History**: All user and admin actions logged
- **Immutable Logs**: Read-only log entries prevent tampering
- **Detailed Context**: Full request/response context for debugging

## Configuration

### Monolog Configuration
- **Development**: Info level logs to database + file
- **Production**: Warning level logs to database + stderr
- **Channels**: Excludes event and doctrine channels to reduce noise

### Database Schema
- **system_logs**: Application logs with full context
- **action_history**: User action tracking (existing)
- **users**: Enhanced with admin roles (existing)

## Usage Examples

### Accessing Admin Panel
1. Navigate to `/admin`
2. Login with admin credentials
3. View dashboard with system overview

### Managing Users
1. Click "Manage Users" from dashboard
2. Use search/filters to find specific users
3. Edit user details, roles, or subscription status
4. All changes automatically logged

### Monitoring Logs
1. Access "System Logs" section
2. Filter by level, channel, or date range
3. View detailed log entries with context
4. Monitor error trends and patterns

### Creating Admin Users
```bash
# Basic admin
php bin/console app:create-admin admin@company.com securepass123

# Super admin with details
php bin/console app:create-admin superadmin@company.com securepass123 \
  --first-name="Super" --last-name="Admin" --super-admin
```

## Maintenance

### Log Cleanup
Set up a cron job to clean old logs:
```bash
# Clean logs older than 30 days (run daily)
0 2 * * * /path/to/php /path/to/bin/console app:clean-logs 30
```

### Performance Optimization
- **Database Indexes**: Optimized indexes on log tables for fast queries
- **Log Rotation**: Automatic cleanup prevents database bloat
- **Efficient Queries**: Paginated results for large datasets

## Troubleshooting

### Common Issues
1. **Access Denied**: Ensure user has `ROLE_ADMIN` role
2. **Logs Not Appearing**: Check Monolog configuration in `config/packages/monolog.yaml`
3. **Performance Issues**: Run log cleanup command regularly

### Debug Mode
Enable detailed logging in development:
```yaml
# config/packages/monolog.yaml
when@dev:
    monolog:
        handlers:
            database:
                level: debug  # Change from info to debug
```

## Security Recommendations

1. **Strong Passwords**: Enforce strong passwords for admin users
2. **Regular Audits**: Review admin actions and user activities regularly
3. **Log Monitoring**: Set up alerts for critical errors
4. **Access Control**: Limit admin access to necessary personnel only
5. **Regular Cleanup**: Clean old logs to maintain performance

## Automatic Log Cleanup

### 🔄 Automated Cleanup Features

**Smart Triggers**
- Automatically runs after accessing admin panel or main application routes
- Triggers only when thresholds are met (1,000+ old logs OR 10,000+ total logs)
- Maximum frequency: once every 24 hours to prevent performance impact

**Configuration**
```bash
# Environment variables (add to .env)
LOG_RETENTION_DAYS=30              # Default retention period
AUTO_LOG_CLEANUP_ENABLED=true      # Enable/disable automatic cleanup
```

**Manual Control**
- **Admin Interface**: Navigate to `/admin/logs/management` for full control
- **View Statistics**: Real-time log counts and cleanup status
- **Manual Cleanup**: Custom retention periods and force cleanup
- **Monitoring**: Track last run, next scheduled, cleanup history

### 📅 Scheduled Cleanup (Optional)

For guaranteed cleanup scheduling, set up cron jobs:

```bash
# Daily cleanup at 2 AM (recommended)
0 2 * * * /usr/bin/php /path/to/project/bin/console app:auto-cleanup-logs --force

# Every 6 hours (threshold-based)
0 */6 * * * /usr/bin/php /path/to/project/bin/console app:auto-cleanup-logs
```

### 🛠 Commands Available

```bash
# Smart automatic cleanup (respects thresholds)
php bin/console app:auto-cleanup-logs

# Force cleanup regardless of thresholds
php bin/console app:auto-cleanup-logs --force

# Dry run (see what would be cleaned)
php bin/console app:auto-cleanup-logs --dry-run

# Custom retention period
php bin/console app:auto-cleanup-logs --retention-days=60

# Manual cleanup (original command)
php bin/console app:clean-logs 30
```

### 📊 Cleanup Thresholds

**Automatic triggers when:**
- Old logs count > 1,000 entries
- Total logs count > 10,000 entries
- Force flag is used
- Manual trigger from admin panel

**Default settings:**
- Retention period: 30 days
- Check interval: 24 hours maximum
- Target routes: admin, dashboard, convert, parse, generate

## Future Enhancements

- **Email Notifications**: Alert on critical errors
- **Advanced Analytics**: User behavior analysis
- **Export Features**: CSV/Excel export for logs and users
- **API Access**: REST API for external monitoring tools
- **Two-Factor Authentication**: Enhanced security for admin access
- **Log Archiving**: Archive old logs instead of deleting
- **Real-time Monitoring**: WebSocket-based real-time log monitoring 
