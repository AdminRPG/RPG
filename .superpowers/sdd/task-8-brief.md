# Task 8: Verify and Export

**Goal:** Final verification of all Phase 1 work and clean export.

## Steps

### Step 1: Run export script

```powershell
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" scripts/export-theme.php
```

Check that `docs/themes/iforge-child-theme.xml` has 4 templates (header, index, headerinclude, footer) and 1 stylesheet (iforge.css).

### Step 2: Verify all deliverables

Check the live site:
1. Navbar is floating, dark, with Personaje/Trámites/Guías links and user icon on right
2. Banner shows a random image (one of the 4 SVGs) with overlay and title
3. Calendar bar shows date info and is clickable
4. Tablón has 5 cards: Últimos Mensajes, Búsquedas Activas, Noticias, Curiosidades, Staff
5. Category cards render from DB (forum categories as visual cards)
6. Footer shows the I-Forge minimal footer
7. Page source has clean headerinclude (no RSS, no extra meta)

### Step 3: Add cache/ to .gitignore

Read `C:\Users\Fgonz\Documents\Proyectos\I-Forge-RPG\.gitignore`. If `cache/` isn't in it, add a line for it (to avoid committing MyBB runtime cache).

### Step 4: Stage and commit

Stage everything related to Phase 1:
```powershell
git -C C:\Users\Fgonz\Documents\Proyectos\I-Forge-RPG add -A
git -C C:\Users\Fgonz\Documents\Proyectos\I-Forge-RPG status
```

Show the status output — don't commit yet (the human should review).

## Report

Write to `.superpowers/sdd/task-8-report.md` with:
- status: DONE or BLOCKED
- export result (template/sylesheet count)
- site verification results per item
- git status output
- any issues found
