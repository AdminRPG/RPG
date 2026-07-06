import { chromium } from 'playwright';
import fs from 'fs';

async function main() {
  console.log('Launching browser...');
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });

  try {
    console.log('Navigating to http://localhost/iforge/...');
    const response = await page.goto('http://localhost/iforge/', { waitUntil: 'networkidle', timeout: 10000 });
    
    console.log('Response status:', response ? response.status() : 'No response');
    
    // Ensure capture directories exist
    if (!fs.existsSync('references/local/capturas')) {
      fs.mkdirSync('references/local/capturas', { recursive: true });
    }
    if (!fs.existsSync('references/local/paginas')) {
      fs.mkdirSync('references/local/paginas', { recursive: true });
    }

    // Capture screenshot
    const screenshotPath = 'references/local/capturas/home.png';
    await page.screenshot({ path: screenshotPath, fullPage: true });
    console.log('Screenshot saved to', screenshotPath);

    // Save HTML and text
    const html = await page.content();
    fs.writeFileSync('references/local/paginas/home.html', html);
    
    const text = await page.innerText('body');
    fs.writeFileSync('references/local/paginas/home_text.txt', text);
    console.log('HTML and text content extracted successfully.');

  } catch (error) {
    console.error('Error capturing local forum:', error);
  } finally {
    await browser.close();
  }
}

main();
