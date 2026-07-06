import { chromium } from 'playwright';

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });

await page.goto('https://onepiecegaiden.com/op/personaje.php?uid=347', { waitUntil: 'networkidle', timeout: 15000 }).catch(() => {});
await page.waitForTimeout(2000);

const tabs = [
  { id: 'portada', label: 'Portada' },
  { id: 'biografia', label: 'Biografía' },
  { id: 'belico', label: 'Bélico' },
  { id: 'tecnicas', label: 'Técnicas' },
  { id: 'inventario', label: 'Inventario' }
];

for (const tab of tabs) {
  console.log(`\n========== TAB: ${tab.label} ==========`);
  
  // Click the tab via JavaScript
  await page.evaluate((tabId) => {
    if (typeof clickPestana === 'function') {
      clickPestana(tabId);
    } else {
      const el = document.getElementById('pestana_' + tabId);
      if (el) el.click();
    }
  }, tab.id);
  await page.waitForTimeout(1500);

  // Extract visible text only
  const visibleText = await page.evaluate(() => {
    const body = document.body;
    const walker = document.createTreeWalker(body, NodeFilter.SHOW_ELEMENT, {
      acceptNode: (node) => {
        const style = window.getComputedStyle(node);
        if (style.display === 'none' || style.visibility === 'hidden') {
          return NodeFilter.FILTER_REJECT;
        }
        // Skip script and style tags
        if (node.tagName === 'SCRIPT' || node.tagName === 'STYLE' || node.tagName === 'NOSCRIPT') {
          return NodeFilter.FILTER_REJECT;
        }
        return NodeFilter.FILTER_ACCEPT;
      }
    });

    const texts = [];
    let node;
    while (node = walker.nextNode()) {
      const text = node.textContent.trim();
      if (text && node.children.length === 0) {
        texts.push(text);
      }
    }
    return texts.join('\n');
  });

  process.stdout.write(visibleText.substring(0, 10000));
  console.log('\n');
}

await browser.close();
