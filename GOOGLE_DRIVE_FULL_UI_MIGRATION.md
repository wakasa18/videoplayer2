# Google Drive Style — Full UI Migration

This version replaces the previous cartoon, retro, synthwave, and game-theme layers with one Google Drive-inspired interface across the latest full system.

## Design system

- Light gray application background
- White cards and workspaces
- Blue primary actions and selected states
- Inter typography
- Fixed application header
- Desktop left navigation sidebar
- Animated mobile navigation drawer
- Rounded file/folder cards
- Drive-style list and grid controls
- Consistent forms, filters, dropdowns, menus, modals, preview windows, tables, and pagination

## Animation and interaction port

The following motion patterns were adapted from the supplied Google Drive UI reference:

- Staggered page/card entrances
- Smooth sidebar slide-in/out on mobile
- Soft card hover elevation
- Animated modal entrance
- Animated toast/status appearance
- Responsive context-menu repositioning
- Reduced-motion accessibility support

## Old UI removed

The package removes the previous visual assets, including:

- `game-theme*`
- `game-core*`
- `retro-theme*`
- `theme-base*`
- previous vault CSS versions
- previous shared-page CSS versions
- previous assignment CSS versions
- old game/retro UI JavaScript files

Only the functional module scripts remain for uploads, previews, assignments, sharing, and downloads.

## New UI files

- `app/Views/partials/drive_theme.php`
- `public/assets/css/drive-core.v2.css`
- `public/assets/css/drive-theme.v2.css`
- `public/assets/css/drive-pages.v1.css`
- `public/assets/css/drive-vault.v1.css`
- `public/assets/css/drive-assignments.v1.css`
- `public/assets/css/drive-shares.v1.css`
- `public/assets/js/drive-ui.v2.js`

## Deployment

1. Upload the complete project or replace the changed files.
2. Redeploy on Vercel.
3. Hard-refresh the browser using `Ctrl + F5` once.

No Supabase SQL change or new environment variable is required.
