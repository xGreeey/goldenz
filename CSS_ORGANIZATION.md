# CSS Organization Guide

This document explains how CSS and HTML are organized in the Golden Z-5 HR System to make editing easier and more maintainable.

## Directory Structure

```
assets/
├── css/
│   ├── style.css              # Main global styles
│   ├── font-override.css      # Font-specific overrides
│   ├── custom-icons.css        # Custom icon styles
│   ├── employees.css          # Employees page specific styles
│   ├── utilities.css          # Reusable utility classes
│   └── developer-system-logs.css  # Developer logs page styles
pages/
└── css/
    └── add_employee.css        # Add employee page styles
```

## CSS Organization Principles

### 1. **Separate External CSS Files**
- **Never** put CSS in `<style>` tags within PHP files
- **Never** use inline `style=""` attributes (use utility classes instead)
- All page-specific CSS should be in separate files under `assets/css/`

### 2. **File Naming Convention**
- Page-specific CSS: `{page-name}.css` (e.g., `employees.css`)
- Component CSS: `{component-name}.css` (e.g., `utilities.css`)
- Global CSS: `style.css`, `font-override.css`, etc.

### 3. **Including CSS Files**

CSS files are included in `includes/header.php`:

```php
<!-- Global CSS (always loaded) -->
<link href="<?php echo asset_url('css/style.css'); ?>" rel="stylesheet">
<link href="<?php echo asset_url('css/font-override.css'); ?>" rel="stylesheet">

<!-- Page-specific CSS (conditional) -->
<?php if ($page === 'employees'): ?>
<link href="<?php echo asset_url('css/employees.css'); ?>" rel="stylesheet">
<?php endif; ?>
```

## How to Add Styles for a New Page

### Step 1: Create the CSS File
Create a new file in `assets/css/` named after your page:
- Example: `assets/css/my-page.css`

### Step 2: Add CSS Link in Header
Edit `includes/header.php` and add:

```php
<?php if ($page === 'my_page'): ?>
<link href="<?php echo asset_url('css/my-page.css'); ?>" rel="stylesheet">
<?php endif; ?>
```

### Step 3: Write Your Styles
Add all your styles in the new CSS file. Use clear comments to organize:

```css
/* My Page Specific Styles */

/* Header Section */
.my-page-header {
    /* styles here */
}

/* Content Section */
.my-page-content {
    /* styles here */
}
```

## Using Utility Classes

Instead of inline styles, use utility classes from `utilities.css`:

### Before (Bad):
```html
<img src="photo.jpg" style="width: 100px; height: 100px; object-fit: cover;">
<div style="white-space: nowrap;">Text</div>
```

### After (Good):
```html
<img src="photo.jpg" class="avatar-md">
<div class="text-nowrap">Text</div>
```

## Common Utility Classes

### Avatars
- `.avatar-sm` - Small avatar (40x40px)
- `.avatar-md` - Medium avatar (100x100px)
- `.avatar-lg` - Large avatar (60x60px)
- `.avatar-placeholder` - Avatar with gradient background

### Text
- `.text-nowrap` - Prevent text wrapping
- `.text-truncate-custom` - Truncate with ellipsis (max 180px)
- `.text-truncate-medium` - Truncate with ellipsis (max 360px)

### Width
- `.min-w-150` - Minimum width 150px
- `.min-w-120` - Minimum width 120px

### Icons
- `.icon-sm` - Small icon size (0.95rem)

## Refactoring Existing Code

### Removing Inline Styles

1. **Find inline styles:**
   ```html
   <div style="width: 100px; height: 100px; object-fit: cover;">
   ```

2. **Create utility class if needed:**
   Add to `assets/css/utilities.css`:
   ```css
   .my-custom-size {
       width: 100px;
       height: 100px;
       object-fit: cover;
   }
   ```

3. **Replace inline style:**
   ```html
   <div class="my-custom-size">
   ```

### Extracting Inline Style Blocks

1. **Find `<style>` tags in PHP files**
2. **Extract CSS to separate file** in `assets/css/`
3. **Remove `<style>` block** from PHP file
4. **Add CSS link** in `includes/header.php`

## Best Practices

1. ✅ **DO**: Keep CSS in separate files
2. ✅ **DO**: Use utility classes for common styles
3. ✅ **DO**: Add comments to organize CSS sections
4. ✅ **DO**: Use semantic class names
5. ❌ **DON'T**: Put CSS in `<style>` tags
6. ❌ **DON'T**: Use inline `style=""` attributes
7. ❌ **DON'T**: Mix CSS with PHP/HTML logic

## Example: Adding Styles to a New Page

```php
// 1. Create assets/css/my-new-page.css
// 2. Add to includes/header.php:
<?php if ($page === 'my_new_page'): ?>
<link href="<?php echo asset_url('css/my-new-page.css'); ?>" rel="stylesheet">
<?php endif; ?>
// 3. Write styles in the CSS file
```

## Maintenance Tips

- **Search for inline styles**: Use your IDE to search for `style=` in HTML/PHP files
- **Search for style tags**: Look for `<style>` in PHP files
- **Keep utilities.css updated**: Add common patterns as utility classes
- **Document custom styles**: Add comments explaining complex CSS

## Questions?

If you're unsure where to put CSS:
1. Is it used on multiple pages? → `style.css` or `utilities.css`
2. Is it specific to one page? → `{page-name}.css`
3. Is it a component used in multiple places? → Create a component CSS file
