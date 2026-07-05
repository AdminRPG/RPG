# Task 6: Banner Images

**Goal:** Create 3-5 placeholder banner SVGs for the rotation system. Each should have a different accent color but the same dark theme.

**Context:**
- MyBB root: `C:\laragon\www\iforge\`
- Banner directory: `C:\laragon\www\iforge\images\banners\`

## Steps

### Step 1: Create banner SVGs

Create 4 SVG files in `images/banners/`. Each is a 1200x500 gradient with the I-Forge logo text centered. Use the existing `default-banner.svg` as template.

`banner-01.svg` — gold accent (#e2b714):
```svg
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="500" viewBox="0 0 1200 500">
  <defs>
    <linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:#0d1117"/>
      <stop offset="50%" style="stop-color:#1c2128"/>
      <stop offset="100%" style="stop-color:#161b22"/>
    </linearGradient>
  </defs>
  <rect fill="url(#g)" width="1200" height="500"/>
  <text x="600" y="260" font-family="Georgia,serif" font-size="72" fill="#e2b714" text-anchor="middle" letter-spacing="4">I-FORGE</text>
  <text x="600" y="310" font-family="sans-serif" font-size="22" fill="#8b949e" text-anchor="middle" letter-spacing="2">Un mundo de Cazadores</text>
</svg>
```

`banner-02.svg` — blue accent (#58a6ff) with different gradient angle:
```svg
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="500" viewBox="0 0 1200 500">
  <defs>
    <linearGradient id="g" x1="0%" y1="100%" x2="100%" y2="0%">
      <stop offset="0%" style="stop-color:#0d1117"/>
      <stop offset="50%" style="stop-color:#151b2a"/>
      <stop offset="100%" style="stop-color:#0d1117"/>
    </linearGradient>
  </defs>
  <rect fill="url(#g)" width="1200" height="500"/>
  <text x="600" y="260" font-family="Georgia,serif" font-size="72" fill="#58a6ff" text-anchor="middle" letter-spacing="4">I-FORGE</text>
  <text x="600" y="310" font-family="sans-serif" font-size="22" fill="#8b949e" text-anchor="middle" letter-spacing="2">Un mundo de Cazadores</text>
</svg>
```

`banner-03.svg` — purple accent (#a371f7) with horizontal gradient:
```svg
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="500" viewBox="0 0 1200 500">
  <defs>
    <linearGradient id="g" x1="0%" y1="0%" x2="0%" y2="100%">
      <stop offset="0%" style="stop-color:#1a1528"/>
      <stop offset="100%" style="stop-color:#0d1117"/>
    </linearGradient>
  </defs>
  <rect fill="url(#g)" width="1200" height="500"/>
  <text x="600" y="260" font-family="Georgia,serif" font-size="72" fill="#a371f7" text-anchor="middle" letter-spacing="4">I-FORGE</text>
  <text x="600" y="310" font-family="sans-serif" font-size="22" fill="#8b949e" text-anchor="middle" letter-spacing="2">Un mundo de Cazadores</text>
</svg>
```

`banner-04.svg` — green accent (#3fb950) with diagonal gradient:
```svg
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="500" viewBox="0 0 1200 500">
  <defs>
    <linearGradient id="g" x1="100%" y1="0%" x2="0%" y2="100%">
      <stop offset="0%" style="stop-color:#0d1117"/>
      <stop offset="50%" style="stop-color:#16201e"/>
      <stop offset="100%" style="stop-color:#0d1117"/>
    </linearGradient>
  </defs>
  <rect fill="url(#g)" width="1200" height="500"/>
  <text x="600" y="260" font-family="Georgia,serif" font-size="72" fill="#3fb950" text-anchor="middle" letter-spacing="4">I-FORGE</text>
  <text x="600" y="310" font-family="sans-serif" font-size="22" fill="#8b949e" text-anchor="middle" letter-spacing="2">Un mundo de Cazadores</text>
</svg>
```

### Step 2: Verify

Check that 5 files exist in `C:\laragon\www\iforge\images\banners\` (default + 4 variants).

### Step 3: Verify rotation

Load `http://localhost/iforge/index.php` a few times and confirm the banner background changes color.

## Report

Write to `.superpowers/sdd/task-6-report.md` with status and file list.