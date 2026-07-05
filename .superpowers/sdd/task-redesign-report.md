# Task Redesign Report

**Status:** DONE

## Summary per step

### Step 1: Update Stylesheet in DB
- Inserted `iforge.css` (token-based warm/parchment palette) into `mybb_themestylesheets` for **tid=4** (RPG theme, the correct one)
- Added global `img.icon` rule for consistent icon sizing
- Cleaned up stale tid=3 stylesheet entry

### Step 2: Create New Banner SVGs
- Recreated all 5 banners (`default-banner.svg`, `banner-01.svg`–`banner-04.svg`) with warm/parchment palette:
  - Light linear gradient backgrounds (`#e8e3d8` → `#f2efe8` → `#ddd8cb`)
  - I-FORGE title in accent colors (green `#4a7c59`, gold `#c4a951`, terracotta `#b85a3e`)
  - Each banner has unique gradient direction per spec
  - No dark overlay overlay since backgrounds are light

### Step 3: Update index.php PHP Variables
- **Staff icons**: Replaced emoji string literals with SVG `<img>` tags using `$mybb->settings['bburl']`:
  - `👑` → `seal.svg`, `🛡️` → `shield.svg`, `👤` → `users.svg`
- **News content**: `⚔️` → `sword.svg`, `📌` → `newspaper.svg`
- **Category cards**: `🏝️` → `sword.svg` icon
- Note: `$bburl` variable doesn't exist in PHP context; used `$mybb->settings['bburl']` throughout

### Step 4: Update MyBB Templates
- **Index template**: All emoji-corrupted sequences replaced with SVG `<img>` tags:
  - Calendar bar: `📅` → `calendar.svg` (with proper HTML entities for accented chars)
  - Card headers: `🗣️` → `speech.svg`, `🔍` → `search.svg`, `📰` → `newspaper.svg`, `💡` → `idea.svg`, `👥` → `users.svg`
  - More links: search and idea SVG icons
  - Fixed corrupted accented characters (`??ltimos` → `&Uacute;ltimos`, etc.)
- **Headerinclude template**: Changed `<meta http-equiv="Content-Type">` to `<meta charset="utf-8">`
- **Header template**: No hardcoded emojis found — left as-is
- **Footer template**: No changes needed

### Step 5: Verify
- Template now contains **9 `<img>` tags** with SVG icons
- No emoji bytes or corrupted `?` sequences remain in the template
- Banner SVGs rewritten with new warm palette
- Icon SVGs verified present: `speech.svg`, `search.svg`, `newspaper.svg`, `idea.svg`, `users.svg`, `calendar.svg`, `seal.svg`, `shield.svg` (new), `sword.svg`
- `shield.svg` was missing — created it

### Step 6: Run export
- Fixed export script constants: `THEME_TID` = 4 (was 3), `TEMPLATESET_SID` = 3 (was 2)
- Export run successful → `docs/themes/iforge-child-theme.xml`
- Exported 5 templates, 1 stylesheet

## Issues
- DB templates had emoji corruption (multibyte chars stored as `?`) — had to use context-aware pattern matching
- Theme/stylesheet tid was 4, not 3 as brief assumed — corrected in both DB and export script
- `shield.svg` icon was missing from `images/icons/` — created it
- Added global `img.icon` CSS rule for consistent icon sizing outside card headers
