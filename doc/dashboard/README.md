# UML2Code Dashboard Documentation

## Overview

The UML2Code Dashboard provides users with a comprehensive overview of their activity, usage statistics, and visual insights into their UML processing history. The dashboard combines real-time data visualization with detailed analytics to help users track their productivity and understand their usage patterns.

## Features

### 📊 Enhanced Statistics

The dashboard displays four key metrics in an improved grid layout with fast-loading animated counters:

- **Diagrams Processed**: Total number of UML diagrams parsed
- **Files Converted**: Total number of code files created through conversion and generation
- **Lines of Code**: Cumulative lines of code generated across all operations (optimized for fast loading)
- **Total Actions**: Sum of all user activities (parse, convert, generate)

### 📈 Interactive Charts

#### Activity Trends Chart
- **Type**: Line chart showing activity over the last 30 days
- **Data**: Daily breakdown of convert, parse, and generate operations
- **Colors**: 
  - Convert: Green (#22C55E)
  - Parse: Blue (#3B82F6)
  - Generate: Purple (#9333EA)

#### Language Usage Chart
- **Type**: Doughnut chart showing programming language distribution
- **Data**: Breakdown of code generation by target language
- **Colors**: 30+ distinct colors supporting PHP, Java, Python, JavaScript, C#, C++, Go, Kotlin, Swift, TypeScript, Rust, Ruby, and 18+ more languages
- **Features**: Interactive legend, responsive design, extensive language support

### 🔍 Enhanced Activity Feed

Recent activity list now shows:
- Action type with color-coded icons
- Programming language (if applicable)
- Lines of code generated
- Diagram name (if provided)
- File count and diagram type
- Time ago with improved formatting

## Technical Implementation

### Backend Architecture

#### Enhanced ActionHistory Entity

The `ActionHistory` entity has been extended with new fields:

```php
// New fields added
private ?string $programmingLanguage = null;
private ?string $generatorVersion = null;
private ?int $totalLinesOfCode = null;
private ?string $diagramName = null;
private ?int $diagramSize = null;
```

#### ActionHistoryService Enhancements

New methods added for comprehensive dashboard statistics:

```php
public function getDashboardStatistics(User $user): array
public function getActivityTrends(User $user, int $days = 30): array
public function getLanguageStatistics(User $user): array
```

#### Repository Methods

New query methods in `ActionHistoryRepository` with PostgreSQL optimization:

- `getLanguageStatsByUser()`: Language usage statistics
- `getDailyActivityByUser()`: Time-series activity data (PostgreSQL-compatible date functions)
- `getTotalLinesOfCodeByUser()`: Cumulative lines of code
- `getTotalFilesByUser()`: Total files generated
- `getLastActivityByUser()`: Most recent activity timestamp
- `getWeeklyStatsByUser()`: Weekly activity aggregation (PostgreSQL-compatible)

### API Endpoints

The dashboard utilizes several new API endpoints:

```
GET /api/dashboard/summary      - Summary statistics and counters
GET /api/dashboard/activity     - Recent activity with enhanced metadata
GET /api/dashboard/trends       - Time-series data for charts
GET /api/dashboard/languages    - Programming language usage breakdown
```

#### Response Formats

**Summary Response:**
```json
{
    "success": true,
    "stats": {
        "diagrams_processed": 15,
        "files_generated": 45,
        "lines_of_code": 1250,
        "total_actions": 60,
        "last_login": "2024-06-05T14:30:00Z",
        "member_since": "2024-05-01T09:15:00Z",
        "breakdown": {
            "parse": 15,
            "convert": 25,
            "generate": 20
        }
    }
}
```

**Trends Response:**
```json
{
    "success": true,
    "trends": [
        {
            "date": "2024-06-05",
            "convert": 20,
            "parse": 6,
            "generate": 7,
            "total": 33
        }
    ]
}
```

**Languages Response:**
```json
{
    "success": true,
    "languages": [
        {
            "language": "PHP",
            "count": 5,
            "totalLines": 374
        },
        {
            "language": "JAVA",
            "count": 4,
            "totalLines": 762
        }
    ]
}
```

**Activity Response:**
```json
{
    "success": true,
    "activity": [
        {
            "id": 123,
            "actionType": "generate",
            "diagramType": "ClassDiagram",
            "programmingLanguage": "PHP",
            "createdAt": "2024-06-05T14:30:00Z",
            "fileCount": 3,
            "totalLinesOfCode": 150,
            "diagramName": "User Management System"
        }
    ]
}
```

### Frontend Architecture

#### Dashboard JavaScript Module

The enhanced `DashboardManager` provides:

- **Parallel Data Loading**: All dashboard components load simultaneously for optimal performance
- **Chart.js Integration**: Professional charts with consistent styling and error handling
- **Error Handling**: Graceful fallbacks for failed API calls and missing Chart.js library
- **Optimized Animation**: Fast 60 FPS counter animations (800ms duration) with dynamic step calculation
- **PostgreSQL Compatibility**: Robust handling of database-specific date functions

#### Chart Creation

```javascript
// Activity trends chart
createActivityTrendsChart(trendsData)

// Language usage chart  
createLanguageUsageChart(languageData)
```

#### Real-time Updates

The dashboard automatically refreshes data on page load and provides visual feedback during loading states.

## Usage Guide

### For Users

#### Viewing Your Dashboard
1. Log in to your UML2Code account
2. Navigate to the Dashboard (automatic redirect after login)
3. View your statistics, charts, and recent activity
4. Click on recent activity items to view detailed history

#### Understanding the Charts
- **Activity Trends**: Hover over data points to see exact values
- **Language Usage**: Click legend items to toggle language visibility
- Charts are responsive and adapt to different screen sizes

#### Recent Activity
- Click any activity item to open the detailed history modal
- See programming language and lines of code for each operation
- View file counts and diagram types at a glance

### For Developers

#### Adding New Metrics

To add a new statistic to the dashboard:

1. **Add field to ActionHistory entity**:
```php
#[ORM\Column(type: 'string', nullable: true)]
private ?string $newMetric = null;
```

2. **Update ActionHistoryService record method**:
```php
if (isset($metadata['newMetric'])) {
    $history->setNewMetric($metadata['newMetric']);
}
```

3. **Add repository query method**:
```php
public function getNewMetricByUser(User $user): array
{
    // Implementation
}
```

4. **Update dashboard API endpoint**:
```php
$stats['new_metric'] = $this->repository->getNewMetricByUser($user);
```

5. **Add frontend display logic**:
```javascript
document.getElementById('new-metric-count').textContent = stats.new_metric;
```

#### Custom Chart Types

To add a new chart:

1. Create container in template:
```twig
<div class="h-64" id="new-chart">
    <!-- Loading state -->
</div>
```

2. Add chart creation function:
```javascript
function createNewChart(data) {
    // Chart.js implementation
}
```

3. Add to data loading pipeline:
```javascript
await Promise.all([
    // ... existing loads
    loadNewChartData()
]);
```

## Configuration

### Chart.js Configuration

Charts use consistent styling and responsive settings with enhanced error handling:

```javascript
options: {
    responsive: true,
    maintainAspectRatio: false,
    // ... specific chart options
}

// Enhanced color palette for language support
const languageColors = [
    '#4F46E5', // Indigo - PHP
    '#EF4444', // Red - Java
    '#10B981', // Green - Python
    '#F59E0B', // Amber - JavaScript
    // ... 26 more colors for comprehensive language support
];
```

### Animation Configuration

Optimized counter animations:

```javascript
// 60 FPS animation with dynamic step calculation
const frameRate = 60;
const totalFrames = Math.ceil(duration / (1000 / frameRate));
const increment = range / totalFrames;
```

### API Rate Limiting

Dashboard API endpoints support:
- Built-in Symfony rate limiting
- User-specific data isolation
- Efficient database queries with proper indexing

### Performance Optimization

#### Database Indexes

The `action_history` table includes optimized indexes:
- `idx_user_action_created`: Composite index for filtering and sorting
- `idx_user_created`: User-specific activity queries

#### PostgreSQL Optimizations

- Raw SQL queries for complex date operations
- Proper parameter binding for all user-specific queries
- Efficient date grouping and aggregation functions

#### Frontend Performance

- **Counter Animation**: Optimized from O(n) to O(1) complexity for large numbers
- **Parallel Loading**: All API calls execute simultaneously
- **Chart Rendering**: Efficient Chart.js configuration with proper cleanup

#### Caching Strategy

Consider implementing:
- Redis caching for frequently accessed statistics
- Browser-side caching for chart data
- Database query result caching

## Security Considerations

### Data Isolation
- All dashboard data is user-specific
- Cross-user data access prevented by user filtering
- Authentication required for all dashboard endpoints

### Input Validation
- API endpoints validate user permissions
- Database queries use parameterized statements
- Chart data sanitized before display

### Performance Security
- Query limits prevent excessive data loading
- Rate limiting protects against abuse
- Efficient pagination for large datasets

## Browser Compatibility

### Supported Browsers
- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

### Chart.js Requirements
- JavaScript enabled
- Canvas support
- Modern ES6 features

### Graceful Degradation
- Fallback displays for chart loading failures
- Error states for API failures
- Progressive enhancement approach

## Troubleshooting

### Common Issues

#### Charts Not Loading
1. **Symptom**: "Failed to load chart" error message
2. **Causes**: Chart.js library not loaded, API endpoint errors, invalid data format
3. **Solutions**:
   - Verify Chart.js CDN accessibility
   - Check browser console for JavaScript errors
   - Ensure API endpoints return valid JSON with `success: true`
   - Confirm data arrays are properly formatted

#### Statistics Showing Zero
1. **Symptom**: All counters display 0 despite user activity
2. **Causes**: Database query errors, missing parameters, PostgreSQL compatibility issues
3. **Solutions**:
   - Verify user has performed actions in ActionHistory table
   - Check database migration completion: `php bin/console doctrine:migrations:status`
   - Confirm PostgreSQL date functions are working
   - Check API endpoint responses for errors

#### Counter Animation Too Slow
1. **Symptom**: Large numbers take several seconds to animate
2. **Solution**: Updated to 60 FPS animation with dynamic step calculation
3. **Performance**: Now animates any number in exactly 800ms

#### PostgreSQL Date Function Errors
1. **Symptom**: "Undefined function" errors for DATE() or SUBSTRING()
2. **Solution**: Implemented raw SQL queries with PostgreSQL-compatible functions
3. **Fixed**: Replaced Doctrine DQL with native PostgreSQL DATE() and EXTRACT() functions

#### Performance Issues
1. Monitor database query performance with `php bin/console debug:container doctrine`
2. Check network request timing in browser DevTools
3. Verify parallel API loading is functioning
4. Consider implementing Redis caching for high-traffic scenarios

### Debug Mode

Enable debug logging:
```yaml
# config/dev/monolog.yaml
monolog:
    handlers:
        main:
            level: debug
```

## Migration Guide

### From Previous Dashboard

The new dashboard is backward compatible but provides enhanced features:

1. **Existing Data**: All previous activity history remains accessible
2. **New Fields**: New metadata fields will be null for existing records
3. **API Changes**: New endpoints supplement existing ones

### Database Migration

```bash
php bin/console doctrine:migrations:migrate
```

This adds the new fields to the `action_history` table without data loss.

## Changelog

### Version 2.1 (December 2024)
- **Performance**: Optimized counter animations (800ms duration, 60 FPS)
- **Compatibility**: Full PostgreSQL support with native date functions
- **UI**: Changed "Files Generated" to "Files Converted"
- **Colors**: Extended language palette to 30+ distinct colors
- **Error Handling**: Enhanced Chart.js error handling and fallbacks
- **Bug Fixes**: Fixed missing parameter binding in repository queries

### Version 2.0 (June 2024)
- **Features**: Enhanced statistics with 4-metric grid layout
- **Charts**: Added Activity Trends and Language Usage charts
- **API**: New dashboard API endpoints
- **Database**: Extended ActionHistory entity with metadata fields

---

**Last Updated**: December 2024  
**Version**: 2.1  
**Maintainer**: UML2Code Development Team 
