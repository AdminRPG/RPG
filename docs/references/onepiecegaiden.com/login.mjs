import { chromium } from 'playwright';

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });

await page.goto('https://onepiecegaiden.com/member.php?action=login');
await page.waitForTimeout(2000);
await page.fill('input[name="username"]', 'Tibarn Laguz');
await page.fill('input[name="password"]', 'Kira9595');
await page.click('input[type="submit"]');
await page.waitForTimeout(3000);
console.log('Login URL:', page.url());

await page.goto('https://onepiecegaiden.com/op/ficha_crear.php');
await page.waitForTimeout(2000);
console.log('Ficha crear URL:', page.url());

await page.screenshot({ path: 'references/onepiecegaiden.com/capturas/ficha_crear.png', fullPage: true });
console.log('Screenshot saved');

const text = await page.innerText('body');
process.stdout.write('CONTENT_START\n');
process.stdout.write(text.substring(0, 8000));
process.stdout.write('\nCONTENT_END\n');

await browser.close();
