# History Feature Documentation

## Overview

The History feature provides comprehensive tracking and management of user activities within the UML2Code application. Users can review previous work, download generated files, and maintain a complete audit trail of their UML processing operations.

## Features

### Action Tracking
- **Convert**: UML diagram to code conversions
- **Parse**: UML text parsing operations  
- **Generate**: Code generation from UML models
- **Automatic Logging**: All user actions are automatically recorded

### History Browsing
- **Tabbed Interface**: Filter by action type (All, Converter, Parser, Generator)
- **Chronological Display**: Most recent actions shown first
- **File Count Summary**: Quick overview of generated files per action
- **Searchable History**: Easy navigation through past activities

### File Management
- **Individual File Download**: Download specific generated files
- **Bulk ZIP Download**: Download all files from an action as a ZIP archive
- **File Preview**: View file contents directly in the browser
- **Copy to Clipboard**: Quick copy functionality for file contents

### User Privacy
- **User-Specific**: Each user only sees their own history
- **Secure Access**: Authentication required for all history operations
- **Data Retention**: Configurable history retention policies

## User Interface

### History Modal
The history feature is accessible through a modal window that can be opened from any page in the application.

#### Components:
- **Tab Navigation**: Switch between different action types
- **History Table**: Displays actions with date, type, diagram type, and file count
- **Detail Modal**: Shows full file contents with download options

#### Table Columns:
| Column | Description |
|--------|-------------|
| Date | When the action was performed |
| Action | Type of action (Convert/Parse/Generate) |
| Diagram Type | Type of UML diagram processed |
| Files | Number of files generated with preview of filenames |
| Actions | View button to open detailed view |

### Detail View
When viewing a specific history entry:
- **File List**: All generated files with syntax highlighting
- **Individual Actions**: Copy or download each file separately
- **Bulk Download**: Download all files as a ZIP archive
- **File Preview**: Syntax-highlighted code display

## Technical Implementation

### Backend Architecture

#### Entities
```php
ActionHistory Entity:
- id: Unique identifier
- user: Associated user (with cascade delete)
- actionType: Type of action (convert/parse/generate)
- diagramType: UML diagram type (default: ClassDiagram)
- files: JSON array of file data
- createdAt: Timestamp of action
```

#### Repository Methods
- `save()`: Persist history entries
- `findLatestByUser()`: Get recent history for a user
- `deleteOldEntries()`: Cleanup old history entries
- `getStatsByUser()`: Generate usage statistics

#### API Endpoints
```
GET /api/history/general     - All user history
GET /api/history/converter   - Conversion history only
GET /api/history/parser      - Parser history only  
GET /api/history/generator   - Generator history only
GET /api/history/{id}        - Specific history entry
GET /api/history/stats       - User statistics
```

### Frontend Architecture

#### JavaScript Modules
- **HistoryManager**: Main module handling UI interactions
- **Event Handling**: Modal controls, tab switching, downloads
- **API Integration**: Fetch history data from backend
- **File Operations**: Download and copy functionality

#### JSZip Integration
- **Global Loading**: Compatible with RequireJS environments
- **Fallback Support**: CDN with local fallback
- **Error Handling**: Graceful degradation to individual downloads

### Data Flow
1. **Action Logging**: Service automatically logs user actions
2. **History Retrieval**: Frontend fetches filtered history via API
3. **Display**: History rendered in tabbed interface
4. **Detail View**: Individual entries loaded on demand
5. **File Operations**: Download/copy operations handled client-side

## Usage Guide

### For Users

#### Accessing History
1. Look for the history icon/button in the application header
2. Click to open the history modal
3. Use tabs to filter by action type

#### Viewing Past Work
1. Browse the history table to find desired action
2. Click "View" to see detailed file contents
3. Use the file preview to review generated code

#### Downloading Files
1. **Individual Files**: Click "Download" next to any file
2. **All Files**: Use "Download All" button for ZIP archive
3. **Copy Content**: Click "Copy" to copy file content to clipboard

### For Developers

#### Adding History Logging
```php
// In your service class
$this->historyService->logAction(
    $user,
    ActionHistory::ACTION_CONVERT,
    'ClassDiagram',
    $generatedFiles
);
```

#### File Format
Files should be stored as:
```php
[
    [
        'filename' => 'User.php',
        'content' => '<?php class User { ... }'
    ],
    // ... more files
]
```

## Configuration

### History Retention
Configure in `config/services.yaml`:
```yaml
parameters:
    app.history.retention_days: 30
    app.history.max_entries_per_user: 100
```

### File Size Limits
- Maximum file size per entry: 10MB
- Maximum total history per user: 100MB
- ZIP generation timeout: 30 seconds

## Security Considerations

### Access Control
- All endpoints require `ROLE_USER` authentication
- Users can only access their own history
- Cross-user data access prevented by user filtering

### Data Protection
- File contents stored as JSON in database
- No sensitive data logging (passwords, tokens, etc.)
- Automatic cleanup of old entries

### Input Validation
- File content sanitization before storage
- Maximum file size validation
- Content type verification

## Performance Optimization

### Database Indexes
- Composite index on `(user_id, action_type, created_at)`
- Optimized queries for recent history retrieval
- Efficient cleanup operations

### Frontend Optimization
- Lazy loading of detailed file contents
- Client-side ZIP generation to reduce server load
- Modal-based UI to minimize initial page weight

### Caching Strategy
- Recent history cached per user session
- File content cached in browser
- API response caching for statistics

## Best Practices

1. **Data Management**
- Implement regular cleanup procedures
- Monitor storage usage and performance
- Validate input data before storage
- Use appropriate data types for efficient queries

2. **User Experience**
- Provide clear feedback during operations
- Implement intuitive navigation patterns
- Ensure responsive design across devices
- Include error handling and recovery options

3. **Security**
- Validate user permissions for all operations
- Sanitize file contents before display
- Implement rate limiting for API endpoints
- Log security-relevant events

## Future Enhancements

- **Search Functionality**: Full-text search across file contents
- **Export Options**: CSV/JSON export of history metadata
- **Sharing Capabilities**: Controlled sharing of history entries
- **Advanced Filtering**: Date ranges, file types, and custom filters
- **Backup Integration**: Automatic backup of critical history data

 
