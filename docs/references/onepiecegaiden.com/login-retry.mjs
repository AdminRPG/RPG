import { chromium } from 'playwright';

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });

// Login
await page.goto('https://onepiecegaiden.com/member.php?action=login', { waitUntil: 'networkidle' });
await page.waitForTimeout(1000);
await page.locator('input[name="username"]').fill('Tibarn Laguz');
await page.locator('input[name="password"]').fill('Kirakira9595');
await page.keyboard.press('Enter');
await page.waitForTimeout(3000);
console.log('1. Login URL:', page.url());

if (page.url().includes('action=login')) {
  console.log('LOGIN FAILED');
  const err = await page.innerText('body');
  process.stdout.write('ERROR_START\n');
  process.stdout.write(err.substring(0, 2000));
  process.stdout.write('\nERROR_END\n');
} else {
  console.log('LOGIN OK!');
  await page.context().storageState({ path: 'references/onepiecegaiden.com/capturas/auth.json' });

  // Try ficha_crear with the redirect URL parameter
  await page.goto('https://onepiecegaiden.com/op/ficha_crear.php', { waitUntil: 'networkidle', timeout: 15000 }).catch(() => {});
  await page.waitForTimeout(2000);
  console.log('2. ficha_crear URL:', page.url());

  // Screenshot
  await page.screenshot({ path: 'references/onepiecegaiden.com/capturas/ficha_crear.png', fullPage: true });
  const text = await page.innerText('body');
  process.stdout.write('FICHA_CREAR_START\n');
  process.stdout.write(text.substring(0, 8000));
  process.stdout.write('\nFICHA_CREAR_END\n');
}

await browser.close();
