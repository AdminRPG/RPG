import fs from 'fs';

const API_KEY = 'fc-d10d672308df4859a22bde52cccdabe0';

async function scrapeWithFirecrawl(url) {
  console.log('Scraping', url, 'with Firecrawl...');
  const response = await fetch('https://api.firecrawl.dev/v1/scrape', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${API_KEY}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ url })
  });

  if (!response.ok) {
    throw new Error(`Firecrawl API error: ${response.status} ${response.statusText}`);
  }

  const data = await response.json();
  if (data.success && data.data && data.data.markdown) {
    return data.data.markdown;
  } else {
    console.error('Unexpected response:', data);
    throw new Error('Failed to extract markdown from response');
  }
}

async function main() {
  try {
    const urls = [
      { url: 'https://onepiecegaiden.com/', filename: 'home_fc.md' },
      { url: 'https://onepiecegaiden.com/op/personaje.php?uid=347', filename: 'personaje_uid347_fc.md' }
    ];

    for (const {url, filename} of urls) {
      const markdown = await scrapeWithFirecrawl(url);
      fs.writeFileSync(`paginas/${filename}`, markdown);
      console.log(`Saved ${filename} (${markdown.length} bytes)`);
    }
  } catch (error) {
    console.error(error);
  }
}

main();
