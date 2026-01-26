# Date Statistics Selection UI - Design Reference

This document captures the UI design patterns and specifications for date statistics selection components.

## Overview

Date statistics selection components allow users to filter and view statistics based on date ranges. These components typically appear in dashboards, reports, and analytics pages.

## Common UI Patterns

### Pattern 1: Date Range Picker with Quick Filters

**Structure:**
- Date From input field
- Date To input field
- Quick filter buttons (Today, This Week, This Month, Last 30 Days, Custom)
- Apply/Filter button
- Reset/Clear button (optional)

**Layout:**
```
┌─────────────────────────────────────────────────┐
│  Date Statistics Filter                         │
├─────────────────────────────────────────────────┤
│  [Date From: 2025-01-01] [Date To: 2025-01-31] │
│  [Today] [This Week] [This Month] [Last 30 Days] │
│  [Apply Filter] [Reset]                         │
└─────────────────────────────────────────────────┘
```

### Pattern 2: Calendar-Based Selection

**Structure:**
- Calendar widget for start date
- Calendar widget for end date
- Selected range highlight
- Preset range buttons
- Statistics display cards below

**Layout:**
```
┌─────────────────────────────────────────────────┐
│  Select Date Range                              │
├─────────────────────────────────────────────────┤
│  ┌──────────┐  ┌──────────┐                    │
│  │ Start    │  │ End      │                    │
│  │ Calendar │  │ Calendar │                    │
│  └──────────┘  └──────────┘                    │
│  [Presets: Today | Week | Month | Custom]      │
└─────────────────────────────────────────────────┘
```

### Pattern 3: Dropdown with Presets

**Structure:**
- Dropdown with preset options
- Custom date range option (expands to show date inputs)
- Visual date range display
- Statistics cards update dynamically

**Layout:**
```
┌─────────────────────────────────────────────────┐
│  Time Period: [Last 30 Days ▼]                 │
│  ┌─────────────────────────────────────────┐  │
│  │ • Today                                  │  │
│  │ • This Week                              │  │
│  │ • This Month                             │  │
│  │ • Last 30 Days                           │  │
│  │ • Last 90 Days                           │  │
│  │ • This Year                               │  │
│  │ • Custom Range...                        │  │
│  └─────────────────────────────────────────┘  │
└─────────────────────────────────────────────────┘
```

### Pattern 4: Tab-Based Period Selection

**Structure:**
- Tabs for different time periods
- Date inputs for custom range
- Active period indicator
- Statistics refresh automatically

**Layout:**
```
┌─────────────────────────────────────────────────┐
│  [Today] [Week] [Month] [Quarter] [Year] [Custom] │
│  ─────────────────────────────────────────────── │
│  Custom Range: [From] to [To]                     │
└─────────────────────────────────────────────────┘
```

## Design Specifications

### Color Scheme
- **Primary Action**: Blue/Purple gradient or solid
- **Secondary Action**: Gray outline
- **Active State**: Dark background with white text
- **Hover State**: Slightly darker shade
- **Disabled State**: Light gray with reduced opacity

### Typography
- **Labels**: 0.875rem (14px), medium weight, gray color
- **Input Text**: 0.9375rem (15px), regular weight
- **Button Text**: 0.875rem (14px), semi-bold
- **Preset Labels**: 0.8125rem (13px), medium weight

### Spacing
- **Container Padding**: 1rem - 1.5rem
- **Input Gap**: 0.75rem - 1rem
- **Button Gap**: 0.5rem
- **Section Margin**: 1rem - 1.5rem

### Border Radius
- **Inputs**: 8px
- **Buttons**: 8px (or pill shape for presets)
- **Container**: 12px

### Icons
- **Calendar Icon**: `fas fa-calendar-alt` or `fas fa-calendar`
- **Filter Icon**: `fas fa-filter`
- **Reset Icon**: `fas fa-redo` or `fas fa-times`
- **Chevron Down**: `fas fa-chevron-down`

## Component States

### Default State
- Inputs show current/default date range
- Buttons in normal state
- No active selection

### Active State
- Selected preset highlighted
- Date inputs show selected range
- Apply button enabled

### Hover State
- Buttons slightly darker
- Inputs show subtle border highlight
- Cursor changes to pointer

### Disabled State
- Reduced opacity (0.5-0.6)
- Cursor: not-allowed
- No interaction possible

## Responsive Behavior

### Desktop (> 992px)
- Horizontal layout
- All controls visible
- Side-by-side date inputs
- Preset buttons in a row

### Tablet (768px - 992px)
- Horizontal layout maintained
- Slightly reduced spacing
- Preset buttons may wrap

### Mobile (< 768px)
- Vertical stack layout
- Full-width inputs
- Preset buttons in grid (2 columns)
- Larger touch targets (min 44px height)

## Statistics Display Integration

When date range is selected, statistics cards should:
1. Show loading state (skeleton/spinner)
2. Update values via AJAX
3. Display new data with smooth transition
4. Show "No data" state if range has no results
5. Display date range label (e.g., "Jan 1 - Jan 31, 2025")

## Accessibility

- **Labels**: Proper `<label>` associations
- **ARIA**: `aria-label` for icon-only buttons
- **Keyboard**: Tab navigation, Enter to submit
- **Focus**: Visible focus indicators
- **Screen Readers**: Descriptive text for all actions

## Implementation Notes

### HTML Structure Example
```html
<div class="date-statistics-selector">
    <div class="date-statistics-selector__header">
        <h3>Date Range</h3>
    </div>
    <div class="date-statistics-selector__controls">
        <div class="date-input-group">
            <label>From</label>
            <input type="date" class="date-input" />
        </div>
        <div class="date-input-group">
            <label>To</label>
            <input type="date" class="date-input" />
        </div>
    </div>
    <div class="date-statistics-selector__presets">
        <button class="preset-btn active">Today</button>
        <button class="preset-btn">This Week</button>
        <button class="preset-btn">This Month</button>
        <button class="preset-btn">Last 30 Days</button>
    </div>
    <div class="date-statistics-selector__actions">
        <button class="btn-primary">Apply</button>
        <button class="btn-secondary">Reset</button>
    </div>
</div>
```

### JavaScript Functionality
- Auto-update on preset selection
- Validate date range (end >= start)
- Format dates for display
- AJAX call to fetch statistics
- Update URL parameters
- Handle browser back/forward

## Current Implementation Locations

1. **DTR Page** (`pages/dtr.php`): Date range filter with From/To inputs
2. **Dashboard** (`pages/dashboard.php`): Schedule date picker
3. **Super Admin Dashboard** (`pages/super-admin-dashboard.php`): Date filters for statistics
4. **Audit Trail** (`pages/audit_trail.php`): Date range filtering
5. **Developer Logs** (`pages/developer-system-logs.php`): Date filters

## Future Enhancements

- [ ] Calendar popup integration
- [ ] Date range visualization (timeline)
- [ ] Comparison mode (compare two periods)
- [ ] Saved date range presets
- [ ] Export filtered data
- [ ] Real-time statistics updates
- [ ] Date range suggestions based on data availability

---

**Note**: This document will be updated when the specific UI design image is provided.
