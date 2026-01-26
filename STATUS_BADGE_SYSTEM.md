# Status Badge Design System

This document describes the comprehensive status badge system implemented to match the design specifications.

## Overview

The status badge system provides a consistent way to display user/entity statuses with proper colorways, icons, and layout. It supports five status types, each with three visual variations, and includes both light and dark mode support.

## Status Types

1. **Verified** (Blue/Purple) - Checkmark icon (✓)
2. **Blocked** (Red) - Blocked circle icon (🚫)
3. **Inactive** (Gray) - Minus circle icon (⊖)
4. **Active** (Green) - Checkmark icon (✓)
5. **Unverified** (Orange) - Asterisk icon (*)

## Visual Variations

Each status has three visual styles:

1. **Light Outline** - White background with very light colored border and text
2. **Medium Outline** - White background with medium colored border and text
3. **Filled** - Solid dark background with white text and icon

## Usage

### Basic Structure

```html
<span class="status-badge-system [status]-[variation]">
    <i></i>
    <span>Status Text</span>
</span>
```

### Examples

#### Verified Status

```html
<!-- Light Outline -->
<span class="status-badge-system verified-outline-light">
    <i></i>
    <span>Verified</span>
</span>

<!-- Medium Outline -->
<span class="status-badge-system verified-outline-medium">
    <i></i>
    <span>Verified</span>
</span>

<!-- Filled -->
<span class="status-badge-system verified-filled">
    <i></i>
    <span>Verified</span>
</span>
```

#### Blocked Status

```html
<!-- Light Outline -->
<span class="status-badge-system blocked-outline-light">
    <i></i>
    <span>Blocked</span>
</span>

<!-- Medium Outline -->
<span class="status-badge-system blocked-outline-medium">
    <i></i>
    <span>Blocked</span>
</span>

<!-- Filled -->
<span class="status-badge-system blocked-filled">
    <i></i>
    <span>Blocked</span>
</span>
```

#### Inactive Status

```html
<!-- Light Outline -->
<span class="status-badge-system inactive-outline-light">
    <i></i>
    <span>Inactive</span>
</span>

<!-- Medium Outline -->
<span class="status-badge-system inactive-outline-medium">
    <i></i>
    <span>Inactive</span>
</span>

<!-- Filled -->
<span class="status-badge-system inactive-filled">
    <i></i>
    <span>Inactive</span>
</span>
```

#### Active Status

```html
<!-- Light Outline -->
<span class="status-badge-system active-outline-light">
    <i></i>
    <span>Active</span>
</span>

<!-- Medium Outline -->
<span class="status-badge-system active-outline-medium">
    <i></i>
    <span>Active</span>
</span>

<!-- Filled -->
<span class="status-badge-system active-filled">
    <i></i>
    <span>Active</span>
</span>
```

#### Unverified Status

```html
<!-- Light Outline -->
<span class="status-badge-system unverified-outline-light">
    <i></i>
    <span>Unverified</span>
</span>

<!-- Medium Outline -->
<span class="status-badge-system unverified-outline-medium">
    <i></i>
    <span>Unverified</span>
</span>

<!-- Filled -->
<span class="status-badge-system unverified-filled">
    <i></i>
    <span>Unverified</span>
</span>
```

## Color Specifications

### Light Mode

#### Verified (Blue/Purple)
- **Light Outline**: Border `#dbeafe`, Text/Icon `#93c5fd`
- **Medium Outline**: Border `#60a5fa`, Text/Icon `#3b82f6`
- **Filled**: Background `#4c1d95`, Text/Icon `#ffffff`

#### Blocked (Red)
- **Light Outline**: Border `#fee2e2`, Text/Icon `#fca5a5`
- **Medium Outline**: Border `#f87171`, Text/Icon `#ef4444`
- **Filled**: Background `#991b1b`, Text/Icon `#ffffff`

#### Inactive (Gray)
- **Light Outline**: Border `#e5e7eb`, Text/Icon `#9ca3af`
- **Medium Outline**: Border `#6b7280`, Text/Icon `#4b5563`
- **Filled**: Background `#1f2937`, Text/Icon `#ffffff`

#### Active (Green)
- **Light Outline**: Border `#dcfce7`, Text/Icon `#86efac`
- **Medium Outline**: Border `#4ade80`, Text/Icon `#22c55e`
- **Filled**: Background `#166534`, Text/Icon `#ffffff`

#### Unverified (Orange)
- **Light Outline**: Border `#fed7aa`, Text/Icon `#fdba74`
- **Medium Outline**: Border `#fb923c`, Text/Icon `#f97316`
- **Filled**: Background `#c2410c`, Text/Icon `#ffffff`

### Dark Mode

Dark mode automatically adjusts outline styles to use muted background colors with lighter borders and text. Filled styles remain the same in both modes.

## Icons

Icons are automatically applied via CSS using Font Awesome icons:
- **Verified/Active**: Checkmark (`\f00c`)
- **Blocked**: Ban icon (`\f05e`)
- **Inactive**: Minus circle (`\f056`)
- **Unverified**: Asterisk (`\f069`)

## Dark Mode Support

The system supports dark mode through:
1. CSS `@media (prefers-color-scheme: dark)` queries
2. Manual dark mode classes (`.dark-mode` or `[data-theme="dark"]`)

Dark mode automatically adjusts outline styles to use darker, muted backgrounds with lighter colored borders and text for better contrast.

## Implementation Notes

- All badges use `border-radius: 9999px` for pill-shaped appearance
- Icons are automatically included via CSS `::before` pseudo-elements
- Text is capitalized by default
- Badges are inline-flex for proper alignment
- Spacing between icon and text is `0.375rem`
- Font size is `0.75rem` (12px)
- Font weight is `600` (semi-bold)

## Migration Guide

To migrate existing status badges to the new system:

1. Replace old badge classes with `status-badge-system [status]-[variation]`
2. Add an empty `<i></i>` tag before the status text
3. Choose the appropriate variation (light outline, medium outline, or filled)
4. Ensure Font Awesome is loaded for icons to display

## Example Migration

**Before:**
```html
<span class="badge badge-status-active">Active</span>
```

**After:**
```html
<span class="status-badge-system active-filled">
    <i></i>
    <span>Active</span>
</span>
```
