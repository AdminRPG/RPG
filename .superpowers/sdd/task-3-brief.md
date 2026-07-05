# Task 3: Custom CSS — `iforge.css`

**Goal:** Add the `iforge.css` stylesheet to the "I-Forge RPG" child theme (tid=3) via the `mybb_themestylesheets` table. This stylesheet provides the full dark theme for the forum.

**Context:**
- MySQL: `C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe` -u root
- Database: mybb_foro
- Theme tid=3 ("I-Forge RPG")

## Steps

### Step 1: Insert the stylesheet

Run this SQL to add `iforge.css` to the child theme:

```sql
DELETE FROM mybb_themestylesheets WHERE name='iforge.css' AND tid=3;
INSERT INTO mybb_themestylesheets (name, tid, attachedto, stylesheet, cachefile, lastmodified)
VALUES ('iforge.css', 3, 'global', '[CSS_CONTENT]', 'cache/themes/theme3/iforge.css', UNIX_TIMESTAMP());
```

Replace [CSS_CONTENT] with the full CSS below. The CSS must be escaped for SQL single quotes (double every single quote ' → '').

### Step 2: Write the CSS content to the cache file

Write the CSS to `C:\laragon\www\iforge\cache\themes\theme3\iforge.css`.

### Step 3: Update theme disporder to include iforge.css

```sql
UPDATE mybb_themes SET properties = 'a:9:{s:11:"templateset";i:2;s:9:"inherited";a:6:{s:6:"imgdir";i:1;s:4:"logo";i:1;s:10:"tablespace";i:1;s:11:"borderwidth";i:1;s:11:"editortheme";i:1;s:9:"disporder";i:1;}s:6:"imgdir";s:6:"images";s:4:"logo";s:15:"images/logo.png";s:10:"tablespace";s:1:"5";s:11:"borderwidth";s:1:"0";s:11:"editortheme";s:8:"mybb.css";s:9:"disporder";a:1:{s:10:"iforge.css";i:1;}s:6:"colors";a:0:{}}' WHERE tid=3;
```

### CSS Content

The complete CSS to use (escape single quotes for SQL):

```css
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
  background: #0d1117;
  color: #f0f6fc;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
  padding-top: 56px;
  min-height: 100vh;
}
a { color: #e2b714; text-decoration: none; }
a:hover { color: #f0c940; }

#iforge-navbar {
  position: fixed; top: 0; left: 0; right: 0;
  z-index: 1000;
  background: #161b22;
  border-bottom: 1px solid #30363d;
  height: 56px;
}
.iforge-nav {
  max-width: 1200px; margin: 0 auto;
  display: flex; align-items: center;
  justify-content: space-between;
  height: 100%; padding: 0 20px;
}
.iforge-nav-left { display: flex; align-items: center; gap: 24px; }
.iforge-nav-right { display: flex; align-items: center; }
.iforge-nav-logo {
  font-family: Georgia, serif;
  font-size: 1.3rem; font-weight: bold;
  color: #e2b714; letter-spacing: 1px;
}
.iforge-nav-link {
  color: #8b949e; font-size: 0.9rem;
  padding: 6px 0; transition: color 0.15s;
}
.iforge-nav-link:hover { color: #f0f6fc; }

.iforge-user-menu { position: relative; }
.iforge-user-btn {
  background: none; border: 2px solid #30363d;
  border-radius: 50%; width: 36px; height: 36px;
  cursor: pointer; display: flex; align-items: center;
  justify-content: center; color: #8b949e;
}
.iforge-user-btn:hover { border-color: #e2b714; color: #e2b714; }
.iforge-dropdown {
  display: none; position: absolute; right: 0; top: 44px;
  background: #161b22; border: 1px solid #30363d;
  border-radius: 8px; min-width: 180px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.4);
  overflow: hidden;
}
.iforge-dropdown.open { display: block; }
.iforge-dropdown-item {
  display: block; padding: 10px 16px;
  color: #f0f6fc; font-size: 0.88rem;
}
.iforge-dropdown-item:hover { background: #1c2128; }
.iforge-dropdown-divider { border: none; border-top: 1px solid #30363d; margin: 4px 0; }

#iforge-banner {
  position: relative; width: 100%;
  height: 420px; overflow: hidden;
  background-size: cover; background-position: center;
  display: flex; align-items: center; justify-content: center;
}
#iforge-banner::before {
  content: ''; position: absolute; inset: 0;
  background: rgba(0,0,0,0.55);
}
.iforge-banner-content { position: relative; z-index: 1; text-align: center; }
.iforge-banner-title {
  font-family: Georgia, serif; font-size: 3.2rem;
  color: #ffffff; letter-spacing: 2px;
  margin-bottom: 8px; text-shadow: 0 2px 8px rgba(0,0,0,0.5);
}
.iforge-banner-subtitle {
  font-size: 1.05rem; color: rgba(255,255,255,0.75); letter-spacing: 1px;
}

#iforge-calendar-bar {
  display: flex; align-items: center; justify-content: center;
  gap: 12px; padding: 12px 20px;
  background: #161b22; border-bottom: 1px solid #30363d;
  font-size: 0.88rem; color: #8b949e; cursor: pointer;
}
#iforge-calendar-bar:hover { background: #1c2128; }
#iforge-calendar-bar strong { color: #f0f6fc; }

.iforge-tablon {
  max-width: 1200px; margin: 0 auto;
  padding: 24px 20px;
  display: grid; grid-template-columns: 1fr 1fr 1fr;
  gap: 16px;
}
.iforge-card {
  background: #161b22; border: 1px solid #30363d;
  border-radius: 8px; overflow: hidden;
}
.iforge-card-header {
  padding: 12px 16px;
  font-size: 0.8rem; font-weight: 600; text-transform: uppercase;
  letter-spacing: 1px; color: #8b949e;
  border-bottom: 1px solid #30363d;
}
.iforge-card-body { padding: 8px 0; }
.iforge-card-item {
  display: block; padding: 8px 16px;
  color: #f0f6fc; font-size: 0.88rem;
}
.iforge-card-item:hover { background: #1c2128; }
.iforge-card-item-meta { font-size: 0.78rem; color: #8b949e; margin-top: 2px; }
.iforge-card-more {
  display: block; padding: 10px 16px;
  text-align: center; font-size: 0.82rem;
  border-top: 1px solid #30363d; color: #8b949e;
}
.iforge-card-more:hover { background: #1c2128; color: #e2b714; }

.iforge-staff-section { grid-column: 1 / -1; }
.iforge-staff-list { display: flex; flex-wrap: wrap; gap: 12px; padding: 12px 16px; }
.iforge-staff-item {
  display: flex; align-items: center; gap: 8px;
  padding: 6px 12px; background: #0d1117;
  border-radius: 6px; font-size: 0.85rem;
}
.iforge-staff-icon { font-size: 1rem; }
.iforge-staff-name { color: #f0f6fc; }
.iforge-staff-mp { color: #58a6ff; font-size: 0.78rem; margin-left: 4px; }

.iforge-categories {
  max-width: 1200px; margin: 0 auto;
  padding: 0 20px 40px;
  display: flex; flex-direction: column; gap: 20px;
}
.iforge-category-card {
  position: relative; border-radius: 10px;
  overflow: hidden; cursor: pointer;
  min-height: 180px; background-size: cover; background-position: center;
}
.iforge-category-card:hover { transform: scale(1.01); }
.iforge-category-card::before {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(135deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.3) 100%);
}
.iforge-category-content { position: relative; z-index: 1; padding: 40px 32px; }
.iforge-category-icon { font-size: 1.8rem; margin-bottom: 8px; }
.iforge-category-title {
  font-family: Georgia, serif; font-size: 1.5rem;
  color: #ffffff; margin-bottom: 6px;
}
.iforge-category-desc { color: rgba(255,255,255,0.7); font-size: 0.9rem; max-width: 500px; }

#iforge-footer {
  background: #161b22; border-top: 1px solid #30363d;
  padding: 24px 20px; text-align: center;
  color: #8b949e; font-size: 0.82rem;
}

.iforge-badge {
  display: inline-block; padding: 1px 7px;
  border-radius: 10px; font-size: 0.7rem;
  font-weight: 600; margin-right: 4px;
}
.iforge-badge-t1 { background: #1c2e4a; color: #58a6ff; }
.iforge-badge-t2 { background: #2d1c4a; color: #a371f7; }
.iforge-badge-t3 { background: #3a2a1c; color: #f0883e; }
```

### Step 4: Verify

Run: `curl -s http://localhost/iforge/index.php | grep -c "iforge.css"`
Expected: `1`

## Report

Write to `.superpowers/sdd/task-3-report.md` with:
- status: DONE or BLOCKED
- summary of what was done
- verify output
