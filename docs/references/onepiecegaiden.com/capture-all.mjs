import { chromium } from 'playwright';

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });

// --- LOGIN ---
console.log('1. Navigating to login...');
await page.goto('https://onepiecegaiden.com/member.php?action=login', { waitUntil: 'networkidle' });
await page.screenshot({ path: 'references/onepiecegaiden.com/capturas/login_page.png', fullPage: true });
console.log('2. Login page screenshot saved');

// Check what form fields exist
const formInfo = await page.evaluate(() => {
  const inputs = Array.from(document.querySelectorAll('input'));
  return inputs.map(i => ({ name: i.name, type: i.type, id: i.id, placeholder: i.placeholder, visible: i.offsetParent !== null }));
});
console.log('3. Form inputs:', JSON.stringify(formInfo, null, 2));

// Fill using JS evaluation
console.log('4. Filling credentials via JS...');
await page.evaluate(() => {
  const u = document.querySelector('input[name="username"]');
  const p = document.querySelector('input[name="password"]');
  if (u) { u.value = 'Tibarn Laguz'; }
  if (p) { p.value = 'Kira9595'; }
});

// Submit via JS
console.log('5. Submitting form...');
await page.evaluate(() => {
  const btn = document.querySelector('input[type="submit"]');
  if (btn) { btn.click(); }
});

await page.waitForTimeout(5000);
console.log('6. Post-login URL:', page.url());

// Save auth state
await page.context().storageState({ path: 'references/onepiecegaiden.com/capturas/auth.json' });

// --- FICHA CREAR ---
console.log('7. Navigating to ficha_crear.php...');
await page.goto('https://onepiecegaiden.com/op/ficha_crear.php', { waitUntil: 'networkidle', timeout: 15000 }).catch(() => {});
await page.waitForTimeout(2000);
console.log('8. URL:', page.url());
await page.screenshot({ path: 'references/onepiecegaiden.com/capturas/ficha_crear.png', fullPage: true });
console.log('9. ficha_crear.png saved');

const fcText = await page.innerText('body');
process.stdout.write('FICHA_CREAR_CONTENT_START\n');
process.stdout.write(fcText.substring(0, 5000));
process.stdout.write('\nFICHA_CREAR_CONTENT_END\n');

// --- PERSONAJE TABS ---
console.log('10. Going to personaje.php...');
await page.goto('https://onepiecegaiden.com/op/personaje.php?uid=347', { waitUntil: 'networkidle', timeout: 15000 }).catch(() => {});
await page.waitForTimeout(2000);

// Get full list of clickable tab-like elements
const tabInfo = await page.evaluate(() => {
  const all = document.querySelectorAll('a, button, .tab, [role="tab"], li, span, div');
  const tabs = [];
  for (const el of all) {
    const text = (el.textContent || '').trim();
    const upper = text.toUpperCase();
    if (['PORTADA','BIOGRAFÍA','BIOGRAFIA','BÉLICO','BELICO','TÉCNICAS','TECNICAS','INVENTARIO'].includes(upper)) {
      const rect = el.getBoundingClientRect();
      tabs.push({
        tag: el.tagName, text, id: el.id,
        class: (el.className || '').substring(0, 60),
        onclick: (el.getAttribute('onclick') || '').substring(0, 100),
        href: el.getAttribute('href') || '',
        w: Math.round(rect.width), h: Math.round(rect.height)
      });
    }
  }
  return tabs;
});
console.log('11. Tab elements:', JSON.stringify(tabInfo, null, 2));

// Click tabs
const tabNames = ['Portada', 'Biografía', 'Bélico', 'Técnicas', 'Inventario'];
for (const tab of tabNames) {
  try {
    console.log(`12. Clicking "${tab}"...`);
    await page.evaluate((tabName) => {
      const all = document.querySelectorAll('a, button, span, div, li');
      for (const el of all) {
        if (el.textContent.trim().toUpperCase() === tabName.toUpperCase()) {
          el.click();
          return;
        }
      }
    }, tab);
    await page.waitForTimeout(2000);
    const safeName = tab.toLowerCase().replace(/ó/g, 'o').replace(/í/g, 'i');
    await page.screenshot({ path: `references/onepiecegaiden.com/capturas/personaje_tab_${safeName}.png`, fullPage: true });
    console.log(`    ${safeName}.png saved`);
  } catch (e) {
    console.log(`    Error on "${tab}": ${e.message.substring(0, 100)}`);
  }
}

await browser.close();
console.log('13. Done!');
