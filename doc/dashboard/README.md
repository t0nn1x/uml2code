# Dashboard Component Documentation

## Overview

The Dashboard component provides users with comprehensive activity insights and usage analytics through visual data representation. The dashboard integrates real-time data visualization with detailed analytics to track productivity and usage patterns.

## Comprehensive Statistics System

### Problem Solved

The original dashboard had critical limitations: statistics were calculated from the `action_history` table which only kept the latest 20 records per action type (parse, convert, generate). This resulted in:

1. **Total actions** could never exceed 60 (20 × 3 action types)
2. **Diagrams processed** was capped at 20
3. **Files generated** and **lines of code** were recalculated each time, losing historical data
4. **Language usage** statistics were incomplete and would reset when old records were deleted

### Solution: UserStatistics Entity

A comprehensive statistics tracking system has been implemented with a new `UserStatistics` entity that stores persistent, cumulative data:

#### New Database Table: `user_statistics`

```sql
CREATE TABLE user_statistics (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    total_parse_actions INT DEFAULT 0,
    total_convert_actions INT DEFAULT 0, 
    total_generate_actions INT DEFAULT 0,
    total_files_generated INT DEFAULT 0,
    total_lines_of_code BIGINT DEFAULT 0,
    language_statistics JSON,
    last_updated TIMESTAMP,
    created_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### Key Improvements

- Unlimited action tracking (no more 60-action limit)
- Accurate all-time statistics (persistent cumulative data)
- Complete language usage data (never resets)
- Real-time updates via events (automatic statistics updates)
- Backward compatible (existing functionality unchanged)

#### Migration Commands

```bash
# Migrate existing users to new statistics system
php bin/console app:migrate-user-statistics

# Run database migrations
php bin/console doctrine:migrations:migrate
```

## Features

### Enhanced Statistics

The dashboard displays four key metrics in an improved grid layout with fast-loading animated counters. Now powered by the comprehensive statistics system for unlimited, accurate tracking:

- **Diagrams Processed**: Total number of UML diagrams parsed (unlimited - no longer capped at 20)
- **Files Converted**: Total number of code files created through conversion and generation (persistent cumulative total)
- **Lines of Code**: Cumulative lines of code generated across all operations (never resets, all-time total)
- **Total Actions**: Sum of all user activities (parse, convert, generate) (unlimited - no longer capped at 60)

### Interactive Charts

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

### Enhanced Activity Feed

Recent activity list now shows:
- Action type with color-coded icons
- Programming language (if applicable)
- Lines of code generated
- Diagram name (if provided)
- File count and diagram type
- Time ago with improved formatting

## Technical Implementation

### Backend Architecture

#### Comprehensive Statistics System

The new statistics system consists of:

1. **UserStatistics Entity** (`src/Entity/UserStatistics.php`)
   - Stores cumulative statistics per user
   - Provides increment methods for atomic updates
   - Handles language statistics as JSON

2. **UserStatisticsRepository** (`src/Repository/UserStatisticsRepository.php`)
   - CRUD operations for statistics
   - Migration methods from action history
   - Comprehensive statistics retrieval

3. **UserStatisticsService** (`src/Service/UserStatisticsService.php`)
   - Business logic for statistics management
   - Dashboard data aggregation
   - Batch migration capabilities

4. **Event System**
   - `ActionRecordedEvent`: Dispatched when actions are recorded
   - `ActionRecordedListener`: Updates statistics automatically
   - Prevents circular dependencies between services

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

The service now integrates with the comprehensive statistics system via events:

```php
// Dispatches events to update comprehensive statistics
$this->eventDispatcher->dispatch(new ActionRecordedEvent($user, $history));
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

The dashboard utilizes several API endpoints:

```
GET /api/dashboard/summary      - Summary statistics and counters
GET /api/dashboard/activity     - Recent activity with enhanced metadata
GET /api/dashboard/trends       - Time-series data for charts
GET /api/dashboard/languages    - Programming language usage breakdown
```

#### Response Formats

**Summary Response (now with unlimited values):**
```json
{
    "success": true,
    "stats": {
        "diagrams_processed": 150,    // All-time total (no longer capped)
        "files_generated": 450,       // All-time total (persistent)
        "lines_of_code": 15000,       // All-time total (never resets)
        "total_actions": 500,         // All-time total (unlimited)
        "last_login": "2024-06-05T14:30:00Z",
        "member_since": "2024-05-01T09:15:00Z",
        "breakdown": {
            "parse": 150,             // Comprehensive statistics
            "convert": 200,
            "generate": 150
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

#### Caching Strategy

- Session-based data caching for improved performance
- Optimized database queries with proper indexing
- Client-side chart rendering for reduced server load

#### Memory Management

- Efficient data structures for large datasets
- Garbage collection optimization for long-running processes
- Connection pooling for database interactions

## Best Practices

1. **Data Visualization**
- Use consistent color schemes across charts
- Provide interactive elements for user engagement
- Implement responsive design for various screen sizes
- Include loading states and error handling

2. **Performance Optimization**
- Implement efficient database queries
- Use appropriate caching strategies
- Optimize frontend rendering
- Monitor resource usage

3. **User Experience**
- Provide clear visual feedback
- Implement intuitive navigation
- Ensure accessibility compliance
- Maintain consistent interface patterns

## Future Enhancements

- **Real-time Analytics**: Live data updates via WebSocket connections
- **Advanced Filtering**: More granular activity filtering options
- **Export Capabilities**: CSV/PDF export of dashboard data
- **Custom Dashboards**: User-configurable dashboard layouts
- **Comparative Analysis**: Time period comparisons and trends
- **Notification System**: Alerts for significant activity changes
