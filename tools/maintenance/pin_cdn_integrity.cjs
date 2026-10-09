// Explicit maintenance command: pin exact versioned CDN resources, never run during requests.
const fs = require('node:fs');
const {execFileSync} = require('node:child_process');
const {createHash} = require('node:crypto');
const files = execFileSync('git', ['ls-files', '-z', '--', '*.php']).toString().split('\0').filter(Boolean);
const hashes = new Map();
for (const file of files) {
  const original = fs.readFileSync(file, 'utf8');
  const updated = original.replace(/<(?:script|link)\b[^>]*\b(?:src|href)="(https:\/\/(?:cdnjs.cloudflare.com|cdn.jsdelivr.net)\/[^"\s]+)"[^>]*>/g, (tag, url) => {
    if (/\bintegrity=/.test(tag)) return tag;
    if (!hashes.has(url)) {
      const bytes = execFileSync('curl', ['--fail', '--silent', '--show-error', '--proto', '=https', '--max-time', '30', url]);
      hashes.set(url, 'sha384-' + createHash('sha384').update(bytes).digest('base64'));
    }
    return tag.replace(/\s+crossorigin="[^"]*"/g, '').replace(/\/?>(?=$)/, ' integrity="' + hashes.get(url) + '" crossorigin="anonymous">');
  });
  if (updated !== original) fs.writeFileSync(file, updated);
}
console.log('Pinned ' + hashes.size + ' CDN resources.');
